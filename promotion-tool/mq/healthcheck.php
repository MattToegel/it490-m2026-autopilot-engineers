<?php
//cao39 - Milestone 3 - RabbitMQ Health check
//cao39 - checks and verifies that the RabbitMQ service is running
//include which lane you are putting in when running the file <development|qa|production>
//example php healthcheck.php production

require_once __DIR__ . '/../lib/inventory.php';

// cao39
// Puts everything in a try/catch function so any failure exits with no errors
try {

    // cao39 - Ask the user to input which lane they want to use

    $lane = $argv[1] ?? null;

    // cao39
    // Only allow the three real lane names
    if (!in_array($lane, ['development', 'qa', 'production'], true)) {
        throw new RuntimeException(
            "Usage: php mq/healthcheck.php <development (mqdev)|qa (mqqa)|production (mqprod)>"
        );
    }

    //cao39 -  Load the shared inventory.json into a PHP array
    $inventory = loadInventory();

    // Pulls the 'mq' section for the lane requested by the user input
    // e.g. lane='production' -> $inventory['lanes']['production']['mq']
    $config = getRoleConfiguration($inventory, $lane, 'mq');

    // cao39
    //Use the connection details for that lane's MQ VM
    $host    = $config['host'];              // the mq host e.g. "mqqa"
    $user    = $config['user'];               // SSH login for that VM
    $port    = $config['port'] ?? 22;         // SSH port, default 22 if it wasn't set already
    $service = $config['service'] ?? 'rabbitmq-server'; 

    // cao39
    // Create the remote command as a SSH call.
    // escapeshellarg() wraps each dynamic value so nothing in the

    $checkCommand = sprintf(
        'ssh -p %d %s@%s %s',
        $port,
        escapeshellarg($user),
        escapeshellarg($host),
        escapeshellarg("sudo systemctl is-active {$service}")
    );

    // cao39 - print mq lane chosen and the target 
    echo "RabbitMQ Healthcheck: {$lane}\n";
    echo "Target: {$user}@{$host}\n";
    echo "\n{$checkCommand}\n";

    // cao39
    // Actually run the SSH command. 
    exec($checkCommand . ' 2>&1', $output, $exitCode);

    // cao39 - systemctl is-active prints "active" and the service is running
    $serviceStatus = trim($output[0] ?? '');

    //cao39 - provides a Healthy status only if the SSH command AND the service was successful

    $isHealthy = ($exitCode === 0 && $serviceStatus === 'active');

    //cao39 converts the boolean into a readable status 
    $status = $isHealthy ? 'HEALTHY' : 'UNHEALTHY';

    // cao39
    // Write one line to the shared promotion.log
    writePromotionLog(
        "{$status}: MQ service check for lane {$lane} on {$host} ({$service})"
    );

    //cao39 -  Print the final result to the console output
    echo "\nRabbitMQ Healthcheck result: {$status}\n";

    // cao39
    // Exit with a non-zero code on failure 
    if (!$isHealthy) {
        exit(1);
    }

// cao39
// Catches anything unexpected like a missing inventory.json or unknown lane

} catch (Throwable $error) {

    //cao39 -  Log the failure to the shared log with the FAILED prefix
    writePromotionLog("FAILED: " . $error->getMessage());

    //cao39 -  Also print the error to stderr so it's visible in the terminal
    fwrite(
        STDERR,
        "Healthcheck failed: " . $error->getMessage() . PHP_EOL
    );

    exit(1);
}
