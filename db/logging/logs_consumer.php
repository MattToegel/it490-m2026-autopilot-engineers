<?php

// logs_consumer.php
// tad46: Consumes from db.logs queue, writes to MySQL, sends bad messages to deadletter
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Load credentials from .env
$env = parse_ini_file(__DIR__ . '/../.env');

// Connect to MySQL on this same VM
$db = new mysqli
(
    'localhost',
    $env['MYSQL_USER'],
    $env['MYSQL_PASSWORD'],
    $env['MYSQL_DATABASE']
);

if ($db->connect_error) 
{
    die("MySQL connection failed: " . $db->connect_error . "\n");
}

// Try to connect to the RabbitMQ broker; catches Exceptions
try 
{
    $connection = new AMQPStreamConnection
    (
        $env['RABBITMQ_HOST'],
        $env['RABBITMQ_PORT'] ?? 5672,
        $env['RABBITMQ_USER'],
        $env['RABBITMQ_PASSWORD'],
        $env['RABBITMQ_VHOST'] ?? '/'
    );
} 
catch (Exception $e) 
{
    echo "Can't connect to RabbitMQ at {$env['RABBITMQ_HOST']}:{$env['RABBITMQ_PORT']}\n";
    echo "Is the rabbitmq broker running and reachable?\n";
    exit(1);
}

$channel = $connection->channel();

// Team-agreed queue and exchange names
$queue        = 'db.logs';
$deadLetterEx = 'deadletter';

// Local mirrored log file (per the assignment requirement)
$logFile = __DIR__ . '/db_listener.log';
echo "DB VM logs consumer listening on '$queue'...\n";

// Callback for every message received
$callback = function ($msg) use ($db, $channel, $deadLetterEx, $logFile) 
{
    $body = $msg->body;
    echo "Received: $body\n";

    // Try to decode the JSON
    $data = json_decode($body, true);

    // Validate required fields
    $required = ['source', 'level', 'message', 'created_at'];
    $isValid = is_array($data);
    if ($isValid) 
    {
        foreach ($required as $field) 
        {
            if (!isset($data[$field])) 
            {
                echo "Bad message, missing field: $field\n";
                $isValid = false;
                break;
            }
        }
    } 
    else 
    {
        echo "Bad message, not valid JSON\n";
    }

    // Route bad messages to the deadletter exchange and ack the original
    if (!$isValid) 
    {
        $badMsg = new AMQPMessage($body);
        $channel->basic_publish($badMsg, $deadLetterEx, 'log.bad');
        $msg->ack();
        
        // Add the rejection to the local mirrored log file
        $logLine = sprintf
        (
            "[%s] [DLQ] Routed malformed message: %s\n",
            date('Y-m-d H:i:s'),
            $body
        );
        file_put_contents($logFile, $logLine, FILE_APPEND);
        
        echo "Routed to deadletter exchange.\n\n";
        return;
    }

    // Valid message and insert it into MySQL
    $stmt = $db->prepare
    (
        "INSERT INTO logs (source, level, message, created_at) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param
    ('ssss',
        $data['source'],
        $data['level'],
        $data['message'],
        $data['created_at']
    );
    if ($stmt->execute()) 
    {
        // Add the entry to the local mirrored log file
        $logLine = sprintf
        (
            "[%s] [%s] [%s] %s\n",
            $data['created_at'],
            $data['source'],
            strtoupper($data['level']),
            $data['message']
        );

        file_put_contents($logFile, $logLine, FILE_APPEND);
        echo "Saved to MySQL and added to local log.\n\n";
        $msg->ack();
    } 
    else 
    {
        echo "DB insert failed: " . $stmt->error . "\n";
        $badMsg = new AMQPMessage($body);
        $channel->basic_publish($badMsg, $deadLetterEx, 'log.bad');
        $msg->ack();
        
        // Add the rejection to the local mirrored log file
        $logLine = sprintf
        (
            "[%s] [DLQ] DB insert failed, routed message: %s\n",
            date('Y-m-d H:i:s'),
            $body
        );
        file_put_contents($logFile, $logLine, FILE_APPEND);
        
        echo "Routed to deadletter exchange.\n\n";
    }
};

// Start consuming from db.logs
$channel->basic_consume($queue, '', false, false, false, false, $callback);

// Keep listening for messages forever
while ($channel->is_consuming())
{
    try
    {
        $channel->wait();
    }
    catch (\PhpAmqpLib\Exception\AMQPBasicCancelException $e)
    {
        echo "Consumer cancelled by broker (queue probably recreated). Exiting.\n";
        
        // Also write to the local mirrored log so the exit is captured
        $logLine = sprintf(
            "[%s] [db-logs] [WARNING] Consumer cancelled by broker. Exiting cleanly.\n",
            date('Y-m-d H:i:s')
        );
        file_put_contents($logFile, $logLine, FILE_APPEND);
        
        break;
    }
}

$channel->close();
$connection->close();
$db->close();