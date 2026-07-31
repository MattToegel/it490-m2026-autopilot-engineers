<?php
// tad46 - DB rollback/restore helper.
// Usage: php rollback.php <lane> <backup-file>
//   php rollback.php qa /home/tad46/it490-m2026-autopilot-engineers/db/promotion/backups/qa-0727-2000.sql
//
// List available backups with:
//   ls /home/tad46/it490-m2026-autopilot-engineers/db/promotion/backups/
//
// Restores a lane's DB from a specific backup produced by migrate.php,
// then re-checks the migrations table so its state matches what was
// actually restored.
//
// Rewritten to use the shared function-based lib/inventory.php.

require_once __DIR__ . '/lib/inventory.php';

$lane = $argv[1] ?? null;
$backupFile = $argv[2] ?? null;

if ($lane === null || $backupFile === null || !file_exists($backupFile))
{
    fwrite(STDERR, "Usage: php rollback.php <lane> <backup-file>\n");
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

$envPath = __DIR__ . "/.env.{$lane}";
$creds = parse_ini_file($envPath);

if ($creds === false)
{
    fwrite(STDERR, "FAILED: Could not read {$envPath}\n");
    exit(1);
}

try
{
    $cmd = sprintf(
        'mysql -h %s -u %s -p%s %s < %s',
        escapeshellarg($target['host']),
        escapeshellarg($creds['DB_USER']),
        escapeshellarg($creds['DB_PASS']),
        escapeshellarg($target['db_name']),
        escapeshellarg($backupFile)
    );

    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0)
    {
        throw new RuntimeException("mysql restore command exited with code {$exitCode}");
    }

    writePromotionLog("SUCCESS: DB rollback on {$lane} from {$backupFile}");

    echo "Restored {$lane} DB from {$backupFile}\n";
    echo "Verify manually: check the migrations table and a known row count/value.\n";
}
catch (Throwable $e)
{
    writePromotionLog("FAILED: DB rollback on {$lane} from {$backupFile}: " . $e->getMessage());
    fwrite(STDERR, "FAILED to restore: " . $e->getMessage() . "\n");
    exit(1);
}