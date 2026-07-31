<?php
// tad46 - DB migration runner for the MS3 promotion tool.
// Usage: php migrate.php <lane> --from=<lane> [--release=<id>]
//   php migrate.php qa         --from=development
//   php migrate.php production --from=qa --release=<id-from-promote>
//
// Rewritten to use the shared function-based lib/inventory.php
// (loadInventory/validatePromotion/getRoleConfiguration/writePromotionLog)
// so this matches the same library the app promotion script uses.

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
$releaseIdArg = null;

foreach ($argv as $arg)
{
    if (str_starts_with($arg, '--from='))
    {
        $fromLane = substr($arg, strlen('--from='));
    }
    if (str_starts_with($arg, '--release='))
    {
        $releaseIdArg = substr($arg, strlen('--release='));
    }
}

if ($targetLane === null || $fromLane === null)
{
    fwrite(STDERR, "Usage: php migrate.php <target-lane> --from=<source-lane> [--release=<id>]\n");
    exit(1);
}

// tad46 - use the passed-in --release= if given (so this run shares an id
// with a matching promote.php run), otherwise generate a new one.
// Shortened format: mmdd-HHMM (e.g. 0727-1927) instead of full timestamp.
$releaseId = $releaseIdArg ?? date('md-Hi');

// ---- 2. Load inventory + enforce dev->qa / qa->prod only ----
try
{
    $inventory = loadInventory();
    validatePromotion($inventory, $fromLane, $targetLane);
}
catch (RuntimeException $e)
{
    fail($e->getMessage());
}

// ---- 3. Connect to the TARGET lane's DB ----
$target = getRoleConfiguration($inventory, $targetLane, 'db');

try
{
    $envPath = __DIR__ . "/.env.{$targetLane}"; // tad46 - per-lane secrets, never committed
    $creds = parse_ini_file($envPath);

    if ($creds === false)
    {
        throw new RuntimeException("Could not read {$envPath}");
    }

    $mysqli = new mysqli($target['host'], $creds['DB_USER'], $creds['DB_PASS'], $target['db_name']);

    if ($mysqli->connect_errno)
    {
        throw new RuntimeException("Connection failed: {$mysqli->connect_error}");
    }
}
catch (Throwable $e)
{
    fail("Could not connect to {$targetLane} DB: " . $e->getMessage());
}

// ---- 4. Make sure the tracking table exists ----
$migTable = getMigrationsTableName();

$mysqli->query("
    CREATE TABLE IF NOT EXISTS `{$migTable}` (
        `migration_name` VARCHAR(255) NOT NULL PRIMARY KEY,
        `applied_at` DATETIME NOT NULL,
        `release_id` VARCHAR(64) NOT NULL
    )
");

// ---- 5. Backup before touching anything ----
$backupDir = getBackupDirectory();
$backupFile = "{$backupDir}/{$targetLane}-{$releaseId}.sql";

try
{
    // tad46 - shelling out to mysqldump keeps this simple; swap host/creds
    // for the real target when wiring this to run over ssh against a
    // remote lane rather than locally.
    $cmd = sprintf(
        'mysqldump -h %s -u %s -p%s %s > %s',
        escapeshellarg($target['host']),
        escapeshellarg($creds['DB_USER']),
        escapeshellarg($creds['DB_PASS']),
        escapeshellarg($target['db_name']),
        escapeshellarg($backupFile)
    );

    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0 || !file_exists($backupFile) || filesize($backupFile) === 0)
    {
        throw new RuntimeException("mysqldump did not produce a usable backup file");
    }
}
catch (Throwable $e)
{
    fail("Backup failed, aborting before any migration runs: " . $e->getMessage());
}

// ---- 6. Find migrations already applied ----
$applied = [];
$result = $mysqli->query("SELECT migration_name FROM `{$migTable}`");
while ($row = $result->fetch_assoc())
{
    $applied[$row['migration_name']] = true;
}

// ---- 7. Walk ordered migration files, skip anything already applied ----
$migrationsDir = __DIR__ . '/migrations';
$files = glob("{$migrationsDir}/*.sql");
sort($files); // filenames are zero-padded/numbered so alphabetical == ordered

$appliedThisRun = [];

foreach ($files as $file)
{
    $name = basename($file);

    if (isset($applied[$name]))
    {
        continue; // already applied - this is the "don't run it twice" check
    }

    $sql = file_get_contents($file);

    try
    {
        if (!$mysqli->multi_query($sql))
        {
            throw new RuntimeException($mysqli->error);
        }

        // drain multi_query results
        while ($mysqli->more_results() && $mysqli->next_result())
        {
            // no-op, just clearing buffered results
        }

        $stmt = $mysqli->prepare("INSERT INTO `{$migTable}` (migration_name, applied_at, release_id) VALUES (?, NOW(), ?)");
        $stmt->bind_param('ss', $name, $releaseId);
        $stmt->execute();

        $appliedThisRun[] = $name;
    }
    catch (Throwable $e)
    {
        // tad46 - stop on first failure rather than continuing partially applied
        fail("Migration {$name} failed: " . $e->getMessage());
    }
}

// ---- 8. Success: log the result ----
writePromotionLog("SUCCESS: DB migrate {$fromLane} -> {$targetLane}, release {$releaseId}, applied " . count($appliedThisRun) . " migration(s)");

echo "Applied " . count($appliedThisRun) . " migration(s) to {$targetLane}. Release: {$releaseId}\n";
echo "Backup saved to: {$backupFile}\n";