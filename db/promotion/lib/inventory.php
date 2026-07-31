<?php

// tad46
// Loads the shared inventory.json file
function loadInventory(): array
{
    $inventoryPath = __DIR__ . '/../inventory.json';

    // Make sure inventory.json exists
    if (!file_exists($inventoryPath)) {
        throw new RuntimeException("Inventory file not found: {$inventoryPath}");
    }

    // Read the JSON file
    $json = file_get_contents($inventoryPath);

    if ($json === false) {
        throw new RuntimeException("Unable to read inventory file.");
    }

    // Convert JSON into a PHP array
    $inventory = json_decode($json, true);

    // Stop if the JSON is invalid
    if (!is_array($inventory)) {
        throw new RuntimeException("Inventory JSON is invalid.");
    }

    return $inventory;
}

// tad46
// Checks if the requested promotion is allowed
function validatePromotion(array $inventory, string $from, string $to): void
{
    // Never allow Development directly to Production
    if ($from === 'development' && $to === 'production') {
        throw new RuntimeException(
            "Direct development to production promotion is not allowed."
        );
    }

    // Check if the promotion exists in allowed_promotions
    foreach ($inventory['allowed_promotions'] ?? [] as $promotion) {
        if (
            ($promotion['from'] ?? '') === $from &&
            ($promotion['to'] ?? '') === $to
        ) {
            return;
        }
    }

    throw new RuntimeException(
        "Promotion from {$from} to {$to} is not allowed."
    );
}

// tad46
// Returns the configuration for a specific lane and role
function getRoleConfiguration(
    array $inventory,
    string $lane,
    string $role
): array {

    // Make sure the requested role exists
    if (!isset($inventory['lanes'][$lane][$role])) {
        throw new RuntimeException(
            "No configuration found for role {$role} in lane {$lane}."
        );
    }

    return $inventory['lanes'][$lane][$role];
}

// tad46
// Writes promotion messages into promotion.log
function writePromotionLog(string $message): void
{
    $logDirectory = __DIR__ . '/../logs';
    $logFile = $logDirectory . '/promotion.log';

    // Create logs folder if it doesn't exist
    if (!is_dir($logDirectory)) {
        mkdir($logDirectory, 0775, true);
    }

    // Format the log entry with date and time
    $entry = sprintf(
        "[%s] %s%s",
        date('Y-m-d H:i:s'),
        $message,
        PHP_EOL
    );

    // Add the new log entry to the file
    file_put_contents($logFile, $entry, FILE_APPEND);
}

// tad46
// DB-specific addition: returns the backup directory path.
// (App doesn't need this - migrate.php/rollback.php use it for
// mysqldump output and release snapshots.)
function getBackupDirectory(): string
{
    $backupDirectory = __DIR__ . '/../backups';

    if (!is_dir($backupDirectory)) {
        mkdir($backupDirectory, 0775, true);
    }

    return $backupDirectory;
}

// tad46
// DB-specific addition: name of the table that tracks which
// migrations have already been applied, so migrate.php doesn't
// re-run the same migration twice.
function getMigrationsTableName(): string
{
    return 'otr_migrations_applied';
}