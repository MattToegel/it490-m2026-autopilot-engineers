<?php
// auth_consumer.php

/* 
tad46:
DB VM consumer that handles user registration and login requests
Listens on db.auth queue, calls the right handler based on routing key,
and replies back to the App VM through the response queue 
*/

// tad46: Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// tad46: Load the test Logger class from the logging folder
require_once __DIR__ . '/../logging/testlogger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// tad46: Load credentials from .env at the db/ root
$env = parse_ini_file(__DIR__ . '/../.env');

// tad46: Open a connection to MySQL on this same VM
// tad46: Using localhost since MySQL runs on the DB VM itself
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

// tad46: Create a logger instance that identifies messages as coming from db-auth
$logger = new Logger('db-auth');

// tad46: Queue this consumer listens on
$queue = 'db.auth';

echo "DB VM auth consumer listening on '$queue'...\n";

// tad46: Registration handler
function handleRegister($db, $logger, $data) 
{
    if (empty($data['username']) || empty($data['email']) || empty($data['password'])) 
    {
        $logger->warning("Registration attempt missing fields");
        return ['status' => 'error', 'message' => 'missing fields'];
    }

    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    $role = 'user';

    $stmt = $db->prepare
    (
        "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param('ssss', $data['username'], $data['email'], $hash, $role);

    try 
    {
        $stmt->execute();
        $userId = $stmt->insert_id;
        $logger->info("New user registered: {$data['email']} (user_id=$userId)");
        return [
            'status'   => 'success',
            'user_id'  => $userId,
            'username' => $data['username'],
            'role'     => $role,
        ];
        
    } catch (mysqli_sql_exception $e) 
    {
        // Duplicate email or username (UNIQUE constraint violation)
        $logger->warning("Registration failed (duplicate email or username): {$data['email']}");
        return ['status' => 'error', 'message' => 'email or username already taken'];
    }
}

// tad46: Login handler
function handleLogin($db, $logger, $data) 
{
    if (empty($data['email']) || empty($data['password'])) 
    {
        $logger->warning("Login attempt missing fields");
        return ['status' => 'error', 'message' => 'missing fields'];
    }

    // tad46: Pull user_id, username, role, and stored hash
    $stmt = $db->prepare
    (
        "SELECT user_id, username, password_hash, role FROM users WHERE email = ?"
    );
    $stmt->bind_param('s', $data['email']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    // tad46: Use generic message to prevent email leaks
    if (!$result) 
    {
        $logger->warning("Login attempt for unknown email: {$data['email']}");
        return ['status' => 'error', 'message' => 'invalid credentials'];
    }

    if (!password_verify($data['password'], $result['password_hash'])) 
    {
        $logger->warning("Failed login (bad password) for: {$data['email']}");
        return ['status' => 'error', 'message' => 'invalid credentials'];
    }

    $logger->info("Successful login: {$data['email']} (user_id={$result['user_id']})");
    return 
    [
        'status'   => 'success',
        'user_id'  => $result['user_id'],
        'username' => $result['username'],
        'role'     => $result['role'],
    ];
}

// tad46: Main callback
$callback = function ($msg) use ($db, $channel, $logger) 
{
    try 
    {
        $data = json_decode($msg->body, true);
        $routingKey = $msg->getRoutingKey();

        // tad46: Print a sanitized version of the message (hide password)
        $logData = $data;
        if (isset($logData['password'])) 
        {
            $logData['password'] = '***';
        }
        echo "Received [$routingKey]: " . json_encode($logData) . "\n";

        // tad46: Dispatch to the correct handler (still uses the original $data with the real password)
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

        // tad46: Build the response with the same correlation_id from the request
        $replyMsg = new AMQPMessage(json_encode($response), 
        [
            'correlation_id' => $msg->get('correlation_id'),
        ]);

        // tad46: Publish the reply to the App VM's reply_to queue
        $channel->basic_publish($replyMsg, '', $msg->get('reply_to'));

        // tad46: Ack the original request so RabbitMQ removes it from db.auth
        $msg->ack();

        echo "Replied: " . json_encode($response) . "\n\n";

    } catch (Exception $e) 
    {
        $logger->error("Auth consumer error: " . $e->getMessage());

        // tad46: Still reply so the App VM doesn't time out forever
        $errorResponse = ['status' => 'error', 'message' => 'internal error'];
        $replyMsg = new AMQPMessage(json_encode($errorResponse), 
        [
            'correlation_id' => $msg->get('correlation_id'),
        ]);
        $channel->basic_publish($replyMsg, '', $msg->get('reply_to'));
        $msg->ack();
    }
};

// tad46: Register the callback with the queue
$channel->basic_consume($queue, '', false, false, false, false, $callback);

// tad46: Listen forever on the queue
while ($channel->is_consuming()) 
{
    $channel->wait();
}

// tad46: Cleanup and close connections
$channel->close();

$connection->close();

$logger->close();

$db->close();