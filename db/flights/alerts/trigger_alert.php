<?php
// trigger_alert.php
// tad46: Manual alert trigger for MVP evidence recording.
// tad46: Publishes alert.create; the handler creates the row only if the user
// tad46: still has this flight saved (AC4 proof mechanism).
//
// Usage:
//   php trigger_alert.php <user_id> <flight_number> [type] [message]
//
// AC4 demo flow:
//   1. Save a flight from the app
//   2. Run this script - alert created, appears in dashboard notifications
//   3. Unsave the flight from the app
//   4. Run this script again - "suppressed", no new alert, notifications unchanged

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($argc < 3)
{
    echo "Usage: php trigger_alert.php <user_id> <flight_number> [alert_type] [message]\n";
    echo "Example: php trigger_alert.php 5 \"UA 1742\" delay \"Flight delayed 25 minutes\"\n";
    exit(1);
}

$userId       = (int)$argv[1];
$flightNumber = $argv[2];
$alertType    = $argv[3] ?? 'delay';
$alertMessage = $argv[4] ?? "Flight {$flightNumber} is now delayed by 25 minutes";

$env = parse_ini_file(__DIR__ . '/../../../.env');

$connection = new AMQPStreamConnection(
    $env['RABBITMQ_HOST'],
    $env['RABBITMQ_PORT'] ?? 5672,
    $env['RABBITMQ_USER'],
    $env['RABBITMQ_PASSWORD'],
    $env['RABBITMQ_VHOST'] ?? '/'
);
$channel = $connection->channel();

list($replyQueue, , ) = $channel->queue_declare('', false, false, true, false);

$corrId   = uniqid('trigger_', true);
$response = null;

$channel->basic_consume(
    $replyQueue, '', false, true, false, false,
    function ($msg) use (&$response, $corrId)
    {
        if ($msg->get('correlation_id') === $corrId)
        {
            $response = json_decode($msg->body, true);
        }
    }
);

$payload = json_encode([
    'user_id'       => $userId,
    'flight_number' => $flightNumber,
    'alert_type'    => $alertType,
    'alert_message' => $alertMessage,
]);

$msg = new AMQPMessage($payload, [
    'correlation_id' => $corrId,
    'reply_to'       => $replyQueue,
    'content_type'   => 'application/json',
]);

$channel->basic_publish($msg, 'app.requests', 'alert.create');

echo "Published alert.create for user_id={$userId}, flight={$flightNumber}\n";
echo "Waiting for DB response...\n";

$start = time();
while ($response === null && (time() - $start) < 5)
{
    try { $channel->wait(null, false, 1); }
    catch (Exception $e) {}
}

$channel->close();
$connection->close();

if ($response === null)
{
    echo "\nERROR: No response from DB VM within 5 seconds.\n";
    exit(1);
}

echo "\nDB responded:\n";
echo json_encode($response, JSON_PRETTY_PRINT) . "\n";

if ($response['status'] === 'success')
{
    echo "\nSUCCESS: Alert created with alert_id={$response['alert_id']}\n";
    echo "The user was still tracking this flight, so the alert was created.\n";
}
else if ($response['status'] === 'suppressed')
{
    echo "\nSUPPRESSED: The user is no longer tracking this flight.\n";
    echo "No alert row was created. This is US-05 AC4 in action.\n";
}
else
{
    echo "\nERROR: {$response['message']}\n";
    exit(1);
}