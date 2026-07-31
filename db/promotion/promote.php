<?php
// tad46 - DB *code* promotion for the MS3 promotion tool.
// Separate from migrate.php: migrate.php changes the remote SCHEMA by
// connecting to MySQL directly (no files move). This script moves the
// actual consumer PHP files onto the target VM using SFTP - never Git.
//
// Usage:
//   php promote.php qa         --from=development
//   php promote.php production --from=qa --release=<id>   (reuses the EXACT
//                                                            snapshot already
//                                                            tested in qa)
//
// Rewritten to use the shared function-based lib/inventory.php, matching
// the app promotion script's style.

require_once __DIR__ . '/lib/inventory.php';

function fail(string $message): void
{
    writePromotionLog("FAILED: {$message}");
    fwrite(STDERR, "FAILED: {$message}\n");
    exit(1);
}

// ---- 1. Parse arguments ----
$targetLane = $argv[1] ?? null;
$fromLane = null;
$releaseId = null;

foreach ($argv as $arg)
{
    if (str_starts_with($arg, '--from='))
    {
        $fromLane = substr($arg, strlen('--from='));
    }
    if (str_starts_with($arg, '--release='))
    {
        $releaseId = substr($arg, strlen('--release='));
    }
}

if ($targetLane === null || $fromLane === null)
{
    fwrite(STDERR, "Usage: php promote.php <target-lane> --from=<source-lane> [--release=<id>]\n");
    exit(1);
}

// ---- 2. Load inventory + enforce development->qa / qa->production only ----
try
{
    $inventory = loadInventory();
    validatePromotion($inventory, $fromLane, $targetLane);
}
catch (RuntimeException $e)
{
    fail($e->getMessage());
}

// tad46 - qa->production MUST reuse an existing release snapshot, never
// re-copy the live repo. This is what guarantees "the same tested release"
// moves forward rather than something rebuilt after qa signed off.
if ($fromLane === 'qa' && $releaseId === null)
{
    fail("Promoting qa->production requires --release=<id> from the release that was already tested in qa.");
}

// ---- 3. Resolve the release snapshot to send ----
$releasesDir = getBackupDirectory() . '/db-releases';
if (!is_dir($releasesDir))
{
    mkdir($releasesDir, 0755, true);
}

if ($fromLane === 'development')
{
    // New release: snapshot the current, tested repo checkout on dbdev.
    // Shortened format: mmdd-HHMM (e.g. 0727-1927) instead of full timestamp.
    $releaseId = $releaseId ?? date('md-Hi');
    $releasePath = "{$releasesDir}/{$releaseId}";

    $sourceConfig = getRoleConfiguration($inventory, $fromLane, 'db');
    $repoDbFolder = rtrim($sourceConfig['source_path'], '/');

    if (!is_dir($repoDbFolder))
    {
        fail("Source folder not found: {$repoDbFolder}");
    }

    // tad46 - only these are actually application code that should run on
    // qa/production. Everything else (secrets, the tool itself, the legacy
    // schema file) is deliberately left out.
    $includeDirs = ['admin', 'auth', 'ConsumerManager', 'flights', 'logging', 'reports'];
    $includeFiles = ['composer.json', 'composer.lock'];

    // Things that must NEVER be promoted to qa/production this way:
    //   .env              - dev secrets; qa/production get their own .env.<lane> placed manually
    //   promotion/         - the promotion tool itself; doesn't belong on qa/production
    //   db_schema.sql      - legacy/reference only; migrate.php's migrations/ files are the
    //                        real source of schema truth
    //   consumer_logs/     - runtime logs, not code

    mkdir($releasePath, 0755, true);

    foreach ($includeDirs as $dir)
    {
        $src = "{$repoDbFolder}/{$dir}";
        if (is_dir($src))
        {
            exec(sprintf('cp -r %s %s', escapeshellarg($src), escapeshellarg("{$releasePath}/{$dir}")), $out, $code);
            if ($code !== 0)
            {
                fail("Could not stage {$dir} into the release snapshot");
            }
        }
    }

    // tad46 - ConsumerManager/consumer_logs is runtime output, not code -
    // strip it back out of the snapshot.
    $runtimeLogsInSnapshot = "{$releasePath}/ConsumerManager/consumer_logs";
    if (is_dir($runtimeLogsInSnapshot))
    {
        exec(sprintf('rm -rf %s', escapeshellarg($runtimeLogsInSnapshot)));
    }

    foreach ($includeFiles as $file)
    {
        $src = "{$repoDbFolder}/{$file}";
        if (is_file($src))
        {
            copy($src, "{$releasePath}/{$file}");
        }
    }

    if (!is_dir($releasePath) || count(scandir($releasePath)) <= 2)
    {
        fail("Release snapshot ended up empty - check {$repoDbFolder} contents");
    }
}
else
{
    // qa->production: reuse the exact snapshot already made during development->qa.
    $releasePath = "{$releasesDir}/{$releaseId}";

    if (!is_dir($releasePath))
    {
        fail("No existing release snapshot found for id {$releaseId}. Cannot rebuild from source for qa->production.");
    }
}

// ---- 4. Look up the target VM ----
$target = getRoleConfiguration($inventory, $targetLane, 'db');
$remoteHost = $target['host'];
$remoteUser = $target['user'];
$remotePort = $target['port'] ?? 22;
$remotePath = rtrim($target['destination_path'], '/');

// ---- 5. Backup whatever is currently on the target BEFORE overwriting ----
$backupDir = getBackupDirectory();
$remoteBackupTar = "/tmp/otr-db-backup-{$releaseId}.tgz";
$localBackupFile = "{$backupDir}/{$targetLane}-code-{$releaseId}.tgz";

$backupCmd = sprintf(
    'ssh -p %d %s@%s "if [ -d %s ]; then tar czf %s -C %s .; else touch %s; fi"',
    $remotePort,
    escapeshellarg($remoteUser),
    escapeshellarg($remoteHost),
    escapeshellarg($remotePath),
    escapeshellarg($remoteBackupTar),
    escapeshellarg($remotePath),
    escapeshellarg($remoteBackupTar)
);
exec($backupCmd, $out, $code);

if ($code !== 0)
{
    fail("Could not create pre-change backup on {$targetLane} ({$remoteHost}).");
}

// Pull that backup down locally too, so a restore doesn't depend on /tmp surviving on the remote VM.
$pullCmd = sprintf(
    'sftp -P %d -o BatchMode=yes %s@%s:%s %s',
    $remotePort,
    escapeshellarg($remoteUser),
    escapeshellarg($remoteHost),
    escapeshellarg($remoteBackupTar),
    escapeshellarg($localBackupFile)
);
exec($pullCmd, $out, $code);

if ($code !== 0 || !file_exists($localBackupFile))
{
    fail("Backup created on {$targetLane} but could not be retrieved to {$localBackupFile}.");
}

// ---- 6. Push the release folder to the target via SFTP ----
// tad46 - create the remote directory via plain ssh first (mkdir -p is
// idempotent). sftp's own `mkdir` command has no -p equivalent and
// aborts the whole batch if the directory is already there.
$ensureDirCmd = sprintf(
    'ssh -p %d %s@%s "mkdir -p %s"',
    $remotePort,
    escapeshellarg($remoteUser),
    escapeshellarg($remoteHost),
    escapeshellarg($remotePath)
);
exec($ensureDirCmd, $out, $code);

if ($code !== 0)
{
    fail("Could not ensure remote directory {$remotePath} exists on {$targetLane}.");
}

$batchFile = tempnam(sys_get_temp_dir(), 'sftp-batch-');
$batchCommands = [
    "put -r {$releasePath}/* {$remotePath}/",
];
file_put_contents($batchFile, implode("\n", $batchCommands) . "\n");

$sftpCmd = sprintf(
    'sftp -P %d -o BatchMode=yes -b %s %s@%s',
    $remotePort,
    escapeshellarg($batchFile),
    escapeshellarg($remoteUser),
    escapeshellarg($remoteHost)
);

exec($sftpCmd . ' 2>&1', $sftpOutput, $sftpExitCode);
unlink($batchFile);

if ($sftpExitCode !== 0)
{
    fail("SFTP transfer to {$targetLane} ({$remoteHost}) failed: " . implode(' | ', $sftpOutput));
}

// ---- 7. Success ----
// tad46 - deliberately NOT auto-restarting consumers here. Restart/start
// them manually (manage-consumers.sh) when you're ready to test.
writePromotionLog("SUCCESS: DB promote {$fromLane} -> {$targetLane}, release {$releaseId}, path {$remotePath}");

echo "Promoted release {$releaseId} to {$targetLane} ({$remoteHost}:{$remotePath})\n";
echo "Pre-change backup saved locally to: {$localBackupFile}\n";
echo "Release snapshot kept at: {$releasePath} (reuse with --release={$releaseId} for the next lane)\n";