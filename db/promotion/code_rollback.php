<?php
// code_rollback.php
// tad46 - restores a lane's CODE from a specific .tgz backup created by
// promote.php's pre-change backup step (see backups/<lane>-code-<release>.tgz).
// Companion to rollback.php, which only restores the DATABASE - this one
// only restores files, never touches the schema.
//
// Usage: php code_rollback.php <lane> <backup-file>
//   php code_rollback.php qa /home/tad46/it490-m2026-autopilot-engineers/db/promotion/backups/qa-code-0806-1930.tgz

require_once __DIR__ . '/lib/inventory.php';

$lane = $argv[1] ?? null;
$backupFile = $argv[2] ?? null;

if ($lane === null || $backupFile === null || !file_exists($backupFile))
{
    fwrite(STDERR, "Usage: php code_rollback.php <lane> <backup-file>\n");
    exit(1);
}

try
{
    $inventory = loadInventory();
    $target = getRoleConfiguration($inventory, $lane, 'db');
}
catch (RuntimeException $e)
{
    fwrite(STDERR, "FAILED: " . $e->getMessage() . "\n");
    exit(1);
}

$remoteHost = $target['host'];
$remoteUser = $target['user'];
$remotePort = $target['port'] ?? 22;
$remotePath = rtrim($target['destination_path'], '/');

$remoteBackupTar = '/tmp/otr-code-rollback-' . basename($backupFile);

// ---- 1. Push the backup .tgz to the target via SFTP ----
$batchFile = tempnam(sys_get_temp_dir(), 'sftp-rollback-');
file_put_contents($batchFile, "put {$backupFile} {$remoteBackupTar}\n");

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
    writePromotionLog("FAILED: code_rollback could not upload backup to {$lane}: " . implode(' | ', $sftpOutput));
    fwrite(STDERR, "FAILED: could not upload backup to {$lane} ({$remoteHost}): " . implode(' | ', $sftpOutput) . "\n");
    exit(1);
}

if ($remotePath === '' || $remotePath === '/' || !str_contains($remotePath, '/db'))
{
    fwrite(STDERR, "FAILED: refusing to wipe suspicious-looking remote path '{$remotePath}' - check inventory.json destination_path for {$lane}.\n");
    exit(1);
}

$restoreCmd = sprintf(
    'ssh -p %d %s@%s "rm -rf %s/* && tar -xzf %s -C %s && rm -f %s"',
    $remotePort,
    escapeshellarg($remoteUser),
    escapeshellarg($remoteHost),
    escapeshellarg($remotePath),
    escapeshellarg($remoteBackupTar),
    escapeshellarg($remotePath),
    escapeshellarg($remoteBackupTar)
);
exec($restoreCmd, $restoreOutput, $restoreExitCode);

if ($restoreExitCode !== 0)
{
    writePromotionLog("FAILED: code_rollback extract failed on {$lane}: " . implode(' | ', $restoreOutput));
    fwrite(STDERR, "FAILED: extracting backup on {$lane} ({$remoteHost}) failed: " . implode(' | ', $restoreOutput) . "\n");
    exit(1);
}

// ---- 3. Success ----
writePromotionLog("SUCCESS: code_rollback on {$lane} from " . basename($backupFile) . " (target directory wiped first)");

echo "Restored code on {$lane} ({$remoteHost}:{$remotePath}) from " . basename($backupFile) . "\n";
echo "Target directory was wiped before extracting - this now exactly matches the backup.\n";
echo "Verify manually: check a known file/version marker matches the backed-up release.\n";
echo "Note: consumers on {$lane} should be restarted to pick up the restored code.\n";