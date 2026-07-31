<?php

// rma9
// App promotion script for Dev -> QA and QA -> Production

require_once __DIR__ . '/../lib/inventory.php';

try {
    // rma9
    // Read the command line options
    $options = getopt('', [
        'from:',
        'to:',
        'file:',
        'release:',
        'dry-run'
    ]);

    // Store the command line values
    $from = $options['from'] ?? null;
    $to = $options['to'] ?? null;
    $file = $options['file'] ?? null;
    $release = $options['release'] ?? null;
    $dryRun = array_key_exists('dry-run', $options);

    // rma9
    // Make sure both source and destination lanes were provided
    if (!$from || !$to) {
        throw new RuntimeException(
            "Usage: php app/promote.php --from=development --to=qa --file=filename.php [--dry-run]"
        );
    }

    // rma9
    // User must choose either a single file or a release folder
    if (!$file && !$release) {
        throw new RuntimeException(
            "You must provide either --file or --release."
        );
    }

    // Do not allow both options at the same time
    if ($file && $release) {
        throw new RuntimeException(
            "Use either --file or --release, not both."
        );
    }

    // rma9
    // Load the inventory and validate the promotion path
    $inventory = loadInventory();
    validatePromotion($inventory, $from, $to);

    // Get the App configuration for both lanes
    $sourceConfig = getRoleConfiguration($inventory, $from, 'app');
    $targetConfig = getRoleConfiguration($inventory, $to, 'app');

    $sourceBase = rtrim($sourceConfig['source_path'], '/');
    $targetBase = rtrim($targetConfig['destination_path'], '/');

    $sourceHost = $sourceConfig['host'];
    $sourceUser = $sourceConfig['user'];
    $sourcePort = $sourceConfig['port'] ?? 22;

    $targetHost = $targetConfig['host'];
    $targetUser = $targetConfig['user'];
    $targetPort = $targetConfig['port'] ?? 22;
    $service = $targetConfig['service'] ?? 'apache2';

    // This command is only needed when the source file must be
    // retrieved from a remote VM, such as QA -> Production
    $fetchCommand = null;

    // rma9
    // Promote a single file
    if ($file) {

        // Prevent invalid file paths
        if (
            str_contains($file, '..') ||
            str_starts_with($file, '/')
        ) {
            throw new RuntimeException("Invalid file path.");
        }

        // Build the source and destination paths
        $sourceItem = $sourceBase . '/' . $file;
        $targetItem = $targetBase . '/' . $file;
        $targetDirectory = dirname($targetItem);

        // Temporary file on the VM running this promotion tool
        $stagedFile = '/tmp/promotion-' . basename($file);

        // Development -> QA:
        // the Development source file is local on appdev
        if ($from === 'development') {

            if (!file_exists($sourceItem)) {
                throw new RuntimeException(
                    "Source file does not exist: {$sourceItem}"
                );
            }

            $promotionSource = $sourceItem;
        } else {

            // QA -> Production:
            // retrieve the tested file remotely from appqa
            $fetchCommand = sprintf(
                'scp -P %d %s@%s:%s %s',
                $sourcePort,
                escapeshellarg($sourceUser),
                escapeshellarg($sourceHost),
                escapeshellarg($sourceItem),
                escapeshellarg($stagedFile)
            );

            $promotionSource = $stagedFile;
        }

        // Create the destination folder if needed
        $mkdirCommand = sprintf(
            'ssh -p %d %s@%s %s',
            $targetPort,
            escapeshellarg($targetUser),
            escapeshellarg($targetHost),
            escapeshellarg("sudo mkdir -p {$targetDirectory}")
        );

        // Copy the selected file to the target VM
        $copyCommand = sprintf(
            'scp -P %d %s %s@%s:%s',
            $targetPort,
            escapeshellarg($promotionSource),
            escapeshellarg($targetUser),
            escapeshellarg($targetHost),
            escapeshellarg('/tmp/' . basename($file))
        );

        // Move the file into the web directory
        $installCommand = sprintf(
            'ssh -p %d %s@%s %s',
            $targetPort,
            escapeshellarg($targetUser),
            escapeshellarg($targetHost),
            escapeshellarg(
                "sudo install -m 0644 /tmp/" .
                basename($file) .
                " {$targetItem}"
            )
        );

        $description = "single file {$file}";
    }

    // rma9
    // Promote an entire release folder
    if ($release) {

        // Prevent invalid folder names
        if (
            str_contains($release, '..') ||
            str_starts_with($release, '/')
        ) {
            throw new RuntimeException("Invalid release name.");
        }

        $sourceItem = __DIR__ . '/releases/' . $release;

        // Make sure the release folder exists locally
        if (!is_dir($sourceItem)) {
            throw new RuntimeException(
                "Release folder does not exist: {$sourceItem}"
            );
        }

        // Create the destination folder
        $mkdirCommand = sprintf(
            'ssh -p %d %s@%s %s',
            $targetPort,
            escapeshellarg($targetUser),
            escapeshellarg($targetHost),
            escapeshellarg("sudo mkdir -p {$targetBase}")
        );

        // Copy the release folder
        $copyCommand = sprintf(
            'scp -P %d -r %s/. %s@%s:/tmp/%s/',
            $targetPort,
            escapeshellarg($sourceItem),
            escapeshellarg($targetUser),
            escapeshellarg($targetHost),
            escapeshellarg($release)
        );

        // Install the release files
        $installCommand = sprintf(
            'ssh -p %d %s@%s %s',
            $targetPort,
            escapeshellarg($targetUser),
            escapeshellarg($targetHost),
            escapeshellarg(
                "sudo cp -a /tmp/{$release}/. {$targetBase}/"
            )
        );

        $description = "release {$release}";
    }

    // rma9
    // Restart Apache after the promotion finishes
    $restartCommand = sprintf(
        'ssh -p %d %s@%s %s',
        $targetPort,
        escapeshellarg($targetUser),
        escapeshellarg($targetHost),
        escapeshellarg("sudo systemctl restart {$service}")
    );

    // Store all commands in order
    $commands = [];

    // QA -> Production needs to retrieve the file from appqa first
    if ($fetchCommand !== null) {
        $commands[] = $fetchCommand;
    }

    $commands[] = $mkdirCommand;
    $commands[] = $copyCommand;
    $commands[] = $installCommand;
    $commands[] = $restartCommand;

    // Show promotion information
    echo "Promotion: {$from} -> {$to}\n";
    echo "Item: {$description}\n";
    echo "Source: {$sourceUser}@{$sourceHost}:{$sourceBase}\n";
    echo "Target: {$targetUser}@{$targetHost}:{$targetBase}\n";

    // rma9
    // Run each command unless this is a dry run
    foreach ($commands as $command) {
        echo "\n{$command}\n";

        if (!$dryRun) {
            passthru($command, $exitCode);

            if ($exitCode !== 0) {
                throw new RuntimeException(
                    "Command failed with exit code {$exitCode}."
                );
            }
        }
    }

    $status = $dryRun ? 'DRY RUN' : 'SUCCESS';

    // Write a success message to the log
    writePromotionLog(
        "{$status}: App {$description} promoted from {$from} to {$to}"
    );

    echo "\nPromotion completed: {$status}\n";

} catch (Throwable $error) {

    // Log the error and display it
    writePromotionLog("FAILED: " . $error->getMessage());

    fwrite(
        STDERR,
        "Promotion failed: " . $error->getMessage() . PHP_EOL
    );

    exit(1);
}
