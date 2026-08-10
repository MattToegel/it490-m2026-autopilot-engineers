<?php
// test_cancellation_alert.php - manual test trigger, not part of the app
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$env = parse_ini_file(__DIR__ . '/.env');
$connection = new AMQPStreamConnection($env['RABBITMQ_HOST'], $env['RABBITMQ_PORT'] ?? 5672, $env['RABBITMQ_USER'], $env['RABBITMQ_PASSWORD'], $env['RABBITMQ_VHOST'] ?? '/');
$channel = $connection->channel();

$payload = [
    'flight_number' => 'UA1991',
    'flight' => ['flight_number' => 'UA1991', 'scheduled_departure' => '2026-08-05 19:54:00'],
    'changes' => [
        'status' => ['old' => 'EnRoute', 'new' => 'Cancelled'],
    ],
];

$msg = new AMQPMessage(json_encode($payload), ['content_type' => 'application/json']);
$channel->basic_publish($msg, 'app.responses', 'flight.status_change');

echo "Published synthetic cancellation for UA1991\n";
$channel->close();
$connection->close();