<?php
// auth_consumer.php

/* 
DB VM consumer that handles user registration and login requests
Listens on db.auth queue, calls the right handler based on routing key,
and replies back to the App VM through the response queue 
*/

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load the test Logger class from the logging folder
require_once __DIR__ . '/../logging/testlogger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Load credentials from .env at the db/ root
$env = parse_ini_file(__DIR__ . '/../.env');

// Open a connection to MySQL on this same VM
// Using localhost since MySQL runs on the DB VM itself
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

// Try to connect to the RabbitMQ broker
// If the broker is down, exit with a friendly msg 
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
} catch (Exception $e) 
{
    echo "Can't connect to RabbitMQ at {$env['RABBITMQ_HOST']}:{$env['RABBITMQ_PORT']}\n";
    echo "Is the broker running and reachable?\n";
    exit(1);
}

$channel = $connection->channel();

// Create a logger instance that identifies messages as coming from db-auth
$logger = new Logger('db-auth');

// Queue this consumer listens on
$queue = '';

echo "DB VM auth consumer listening on '$queue'...\n";

// Registration handler
function handleRegister($db, $logger, $data) 
{
    // Check that required fields are present
    if (empty($data['email']) || empty($data['password'])) 
    {
        $logger->warning("Registration attempt missing fields");
        return ['status' => 'error', 'message' => 'missing fields'];
    }

    // Hash the password using bcrypt (PHP auto-generates a salt)
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);

    // Prepared statement prevents SQL injection
    $stmt = $db->prepare
    (
        "INSERT INTO users (email, password_hash) VALUES (?, ?)"
    );
    $stmt->bind_param('ss', $data['email'], $hash);

    if ($stmt->execute()) 
    {
        $logger->info("New user registered: {$data['email']}");
        return ['status' => 'success', 'user_id' => $stmt->insert_id];
    } 
    else 
    {
        // Most likely cause: duplicate email
        $logger->warning("Registration failed (likely duplicate email): {$data['email']}");
        return ['status' => 'error', 'message' => 'email already taken'];
    }
}

// Login handler
function handleLogin($db, $logger, $data) 
{
    if (empty($data['email']) || empty($data['password'])) 
    {
        $logger->warning("Login attempt missing fields");
        return ['status' => 'error', 'message' => 'missing fields'];
    }

    // Look up the user by email
    $stmt = $db->prepare
    (
        "SELECT id, password_hash FROM users WHERE email = ?"
    );

    $stmt->bind_param('s', $data['email']);

    $stmt->execute();

    $result = $stmt->get_result()->fetch_assoc();

    if (!$result)
    {
        $logger->warning("Login attempt for unknown email: {$data['email']}");
        return ['status' => 'error', 'message' => 'invalid credentials'];
    }

    // Verify the submitted password against the stored hash
    if (!password_verify($data['password'], $result['password_hash'])) 
    {
        $logger->warning("Failed login (bad password) for: {$data['email']}");
        return ['status' => 'error', 'message' => 'invalid credentials'];
    }

    $logger->info("Successful login: {$data['email']}");
    return ['status' => 'success', 'user_id' => $result['id']];
}

// Main callback
$callback = function ($msg) use ($db, $channel, $logger) 
{
    $data = json_decode($msg->body, true);
    $routingKey = $msg->getRoutingKey();

    echo "Received [$routingKey]: " . $msg->body . "\n";

    // Dispatch to the correct handler
    if ($routingKey === 'user.register') 
    {
        $response = handleRegister($db, $logger, $data);
    } 
    else if ($routingKey === 'user.login') 
    {
        $response = handleLogin($db, $logger, $data);
    } 
    else 
    {
        $logger->warning("Unknown auth routing key received: $routingKey");
        $response = ['status' => 'error', 'message' => 'unknown action'];
    }

    // Build the response with the same correlation_id from the request
    $replyMsg = new AMQPMessage(json_encode($response), 
    [
        'correlation_id' => $msg->get('correlation_id'),
    ]);

    // Publish the reply to the App VM's reply_to queue
    $channel->basic_publish($replyMsg, '', $msg->get('reply_to'));

    // Ack the original request so RabbitMQ removes it from db.auth
    $msg->ack();

    echo "Replied: " . json_encode($response) . "\n\n";
};

// Register the callback with the queue
$channel->basic_consume($queue, '', false, false, false, false, $callback);

// Listen forever
while ($channel->is_consuming()) 
{
    $channel->wait();
}

// Cleanup
$channel->close();

$connection->close();

$logger->close();

$db->close();