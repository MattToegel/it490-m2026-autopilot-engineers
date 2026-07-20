<?php
// auth_consumer.php

/* 
tad46:
DB VM consumer that handles user registration, login, and profile updates
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

// tad46: Try to connect to the RabbitMQ broker
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
    } 
    catch (mysqli_sql_exception $e) 
    {
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

    $stmt = $db->prepare
    (
        "SELECT user_id, username, password_hash, role FROM users WHERE email = ?"
    );
    $stmt->bind_param('s', $data['email']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

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


// tad46: Update Profile handler 
function handleUpdateProfile($db, $logger, $data)
{
    if (empty($data['user_id']))
    {
        $logger->warning("Profile update missing user_id");
        return ['status' => 'error', 'message' => 'missing user_id'];
    }

    // tad46: If they're changing password, verify current password first
    if (!empty($data['new_password']))
    {
        if (empty($data['current_password']))
        {
            $logger->warning("Profile password change missing current_password for user_id={$data['user_id']}");
            return ['status' => 'error', 'message' => 'current password required to change password'];
        }

        $verifyStmt = $db->prepare
        (
            "SELECT password_hash FROM users WHERE user_id = ?"
        );
        $verifyStmt->bind_param('i', $data['user_id']);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result()->fetch_assoc();

        if (!$verifyResult || !password_verify($data['current_password'], $verifyResult['password_hash']))
        {
            $logger->warning("Profile password change failed (bad current password) for user_id={$data['user_id']}");
            return ['status' => 'error', 'message' => 'current password incorrect'];
        }
    }

    // tad46: Build the UPDATE statement dynamically based on which fields are provided
    $updates = [];
    $types = '';
    $values = [];

    if (!empty($data['username']))
    {
        $updates[] = 'username = ?';
        $types .= 's';
        $values[] = $data['username'];
    }

    if (!empty($data['email']))
    {
        $updates[] = 'email = ?';
        $types .= 's';
        $values[] = $data['email'];
    }

    if (!empty($data['new_password']))
    {
        $newHash = password_hash($data['new_password'], PASSWORD_BCRYPT);
        $updates[] = 'password_hash = ?';
        $types .= 's';
        $values[] = $newHash;
    }

    if (empty($updates))
    {
        $logger->warning("Profile update had no fields to change for user_id={$data['user_id']}");
        return ['status' => 'error', 'message' => 'no fields to update'];
    }

    $types .= 'i';
    $values[] = $data['user_id'];

    $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);

    try
    {
        $stmt->execute();

        if ($stmt->affected_rows === 0)
        {
            $logger->warning("Profile update made no changes for user_id={$data['user_id']}");
            return ['status' => 'success', 'message' => 'no changes applied'];
        }

        $updatedFields = [];
        if (!empty($data['username'])) $updatedFields[] = 'username';
        if (!empty($data['email'])) $updatedFields[] = 'email';
        if (!empty($data['new_password'])) $updatedFields[] = 'password';

        $logger->info("Profile updated for user_id={$data['user_id']}: " . implode(', ', $updatedFields));
        return
        [
            'status'  => 'success',
            'message' => 'profile updated',
            'updated' => $updatedFields,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->warning("Profile update failed (likely duplicate): user_id={$data['user_id']}");
        return ['status' => 'error', 'message' => 'email or username already taken'];
    }
}


// tad46: Main callback
$callback = function ($msg) use ($db, $channel, $logger) 
{
    try 
    {
        $data = json_decode($msg->body, true);
        $routingKey = $msg->getRoutingKey();

        // tad46: Print a sanitized version of the message (hide passwords)
        $logData = $data;
        if (isset($logData['password'])) $logData['password'] = '***';
        if (isset($logData['current_password'])) $logData['current_password'] = '***';
        if (isset($logData['new_password'])) $logData['new_password'] = '***';
        echo "Received [$routingKey]: " . json_encode($logData) . "\n";

        // tad46: Dispatch to the correct handler
        if ($routingKey === 'user.register') 
        {
            $response = handleRegister($db, $logger, $data);
        } 
        else if ($routingKey === 'user.login') 
        {
            $response = handleLogin($db, $logger, $data);
        } 
        else if ($routingKey === 'user.update_profile')
        {
            $response = handleUpdateProfile($db, $logger, $data);
        }
        else 
        {
            $logger->warning("Unknown auth routing key received: $routingKey");
            $response = ['status' => 'error', 'message' => 'unknown action'];
        }

        // tad46: Guarded reply pattern - same crash-prevention as flights consumer
        $props = $msg->get_properties();
        if (isset($props['reply_to']))
        {
            $replyMsg = new AMQPMessage(json_encode($response), 
            [
                'correlation_id' => $props['correlation_id'] ?? '',
            ]);
            $channel->basic_publish($replyMsg, '', $props['reply_to']);
            echo "Replied: " . json_encode($response) . "\n\n";
        }

        $msg->ack();

    } 
    catch (\PhpAmqpLib\Exception\AMQPBasicCancelException $e)
    {
        $logger->warning("Consumer cancelled by broker (queue probably recreated). Exiting.");
        exit(0);
    }
    catch (Exception $e) 
    {
        $logger->error("Auth consumer error: " . $e->getMessage());

        $props = $msg->get_properties();
        if (isset($props['reply_to']))
        {
            $errorResponse = ['status' => 'error', 'message' => 'internal error'];
            $replyMsg = new AMQPMessage(json_encode($errorResponse), 
            [
                'correlation_id' => $props['correlation_id'] ?? '',
            ]);
            $channel->basic_publish($replyMsg, '', $props['reply_to']);
        }
        $msg->ack();
    }
};

// tad46: Register the callback with the queue
$channel->basic_consume($queue, '', false, false, false, false, $callback);

// tad46: Listen forever with graceful handling of broker-initiated cancellations
while ($channel->is_consuming()) 
{
    try
    {
        $channel->wait();
    }
    catch (\PhpAmqpLib\Exception\AMQPBasicCancelException $e)
    {
        $logger->warning("Consumer cancelled by broker (queue probably recreated). Exiting cleanly.");
        break;
    }
}

// tad46: Cleanup
$channel->close();
$connection->close();
$logger->close();
$db->close();