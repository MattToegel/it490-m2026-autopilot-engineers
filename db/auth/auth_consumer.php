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
    if (
        empty($data['username']) ||
        empty($data['email']) ||
        empty($data['password'])
    ) 
    {
        $logger->warning("Registration attempt missing fields");

        return [
            'status' => 'error',
            'message' => 'missing fields'
        ];
    }

    // rma9: Hash the user's password before storing it.
    $hash = password_hash(
        $data['password'],
        PASSWORD_BCRYPT
    );

    $role = 'user';

    // rma9: Generate a random six-digit email verification code.
    $verificationCode = (string) random_int(
        100000,
        999999
    );

    // rma9: Store only a secure hash of the code in the database.
    $verificationCodeHash = password_hash(
        $verificationCode,
        PASSWORD_DEFAULT
    );

    // rma9: The verification code expires after ten minutes.
    $verificationExpiresAt = date(
        'Y-m-d H:i:s',
        time() + 600
    );

    $stmt = $db->prepare
    (
        "INSERT INTO users
        (
            username,
            email,
            password_hash,
            role,
            email_verified,
            verification_code_hash,
            verification_expires_at
        )
        VALUES (?, ?, ?, ?, 0, ?, ?)"
    );

    $stmt->bind_param(
        'ssssss',
        $data['username'],
        $data['email'],
        $hash,
        $role,
        $verificationCodeHash,
        $verificationExpiresAt
    );

    try 
    {
        $stmt->execute();

        $userId = $stmt->insert_id;

        // rma9: Access the RabbitMQ channel already created by this consumer.
        global $channel;

        // rma9: Build the message for the email worker.
        $emailPayload = [
            'email' => $data['email'],
            'verification_code' => $verificationCode
        ];

        $emailMessage = new AMQPMessage(
            json_encode($emailPayload),
            [
                'content_type' => 'application/json',
                'delivery_mode' =>
                    AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        // rma9: Publish the verification email job to RabbitMQ.
        $channel->basic_publish(
            $emailMessage,
            '',
            'email.verification'
        );

        $logger->info(
            "New user registered: {$data['email']} " .
            "(user_id=$userId)"
        );

        $logger->info(
            "Verification email queued for {$data['email']} " .
            "(user_id=$userId)"
        );

        return [
            'status' => 'success',
            'user_id' => $userId,
            'username' => $data['username'],
            'email' => $data['email'],
            'role' => $role,
            'verification_required' => true
        ];
    } 
    catch (mysqli_sql_exception $e) 
    {
        $logger->warning(
            "Registration failed " .
            "(duplicate email or username): {$data['email']}"
        );

        return [
            'status' => 'error',
            'message' => 'email or username already taken'
        ];
    }
}


// rma9: Checks the verification code entered by the user.
function handleVerifyEmail($db, $logger, $data)
{
    if (empty($data['email']) || empty($data['verification_code']))
    {
        $logger->warning("Email verification attempt missing fields");

        return [
            'status' => 'error',
            'message' => 'email and verification code are required'
        ];
    }

    $email = trim($data['email']);
    $verificationCode = trim($data['verification_code']);

    // rma9: Verification codes must contain exactly six numbers.
    if (!preg_match('/^\d{6}$/', $verificationCode))
    {
        return [
            'status' => 'error',
            'message' => 'verification code must be six digits'
        ];
    }

    // rma9: Find the user and their stored verification information.
    $stmt = $db->prepare(
        "SELECT
            user_id,
            email_verified,
            verification_code_hash,
            verification_expires_at
        FROM users
        WHERE email = ?"
    );

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if (!$user)
    {
        $logger->warning(
            "Verification attempted for unknown email: {$email}"
        );

        return [
            'status' => 'error',
            'message' => 'account not found'
        ];
    }

    if ((int)$user['email_verified'] === 1)
    {
        return [
            'status' => 'success',
            'message' => 'email is already verified'
        ];
    }

    if (empty($user['verification_code_hash']))
    {
        return [
            'status' => 'error',
            'message' => 'no verification code is available'
        ];
    }

    // rma9: Reject the code when its expiration time has passed.
    if (
        empty($user['verification_expires_at']) ||
        strtotime($user['verification_expires_at']) < time()
    )
    {
        $logger->warning(
            "Expired verification code used for: {$email}"
        );

        return [
            'status' => 'error',
            'message' => 'verification code has expired'
        ];
    }

    // rma9: Compare the submitted code with the stored secure hash.
    if (
        !password_verify(
            $verificationCode,
            $user['verification_code_hash']
        )
    )
    {
        $logger->warning(
            "Incorrect verification code used for: {$email}"
        );

        return [
            'status' => 'error',
            'message' => 'incorrect verification code'
        ];
    }

    // rma9: Mark the account verified and remove the used code.
    $updateStmt = $db->prepare(
        "UPDATE users
        SET
            email_verified = 1,
            verified_at = NOW(),
            verification_code_hash = NULL,
            verification_expires_at = NULL
        WHERE user_id = ?"
    );

    $updateStmt->bind_param('i', $user['user_id']);
    $updateStmt->execute();

    $logger->info(
        "Email verified: {$email} (user_id={$user['user_id']})"
    );

    return [
        'status' => 'success',
        'message' => 'email verified successfully',
        'user_id' => $user['user_id']
    ];
}


// rma9: Generates and sends a new email verification code.
function handleResendVerification($db, $logger, $data)
{
    if (empty($data['email']))
    {
        return [
            'status' => 'error',
            'message' => 'email is required'
        ];
    }

    $email = trim($data['email']);

    // rma9: Looks up the pending account.
    $stmt = $db->prepare(
        "SELECT user_id, email_verified
         FROM users
         WHERE email = ?"
    );

    $stmt->bind_param('s', $email);
    $stmt->execute();

    $user = $stmt->get_result()->fetch_assoc();

    if (!$user)
    {
        return [
            'status' => 'error',
            'message' => 'account not found'
        ];
    }

    if ((int)$user['email_verified'] === 1)
    {
        return [
            'status' => 'success',
            'message' => 'email is already verified'
        ];
    }

    // rma9: Generates a fresh six-digit code.
    $verificationCode = (string) random_int(
        100000,
        999999
    );

    // rma9: Stores only the code hash.
    $verificationCodeHash = password_hash(
        $verificationCode,
        PASSWORD_DEFAULT
    );

    // rma9: Gives the new code a fresh ten-minute expiration.
    $verificationExpiresAt = date(
        'Y-m-d H:i:s',
        time() + 600
    );

    $updateStmt = $db->prepare(
        "UPDATE users
         SET
            verification_code_hash = ?,
            verification_expires_at = ?
         WHERE user_id = ?"
    );

    $updateStmt->bind_param(
        'ssi',
        $verificationCodeHash,
        $verificationExpiresAt,
        $user['user_id']
    );

    $updateStmt->execute();

    // rma9: Publishes the new code to the API email worker.
    global $channel;

    $emailPayload = [
        'email' => $email,
        'verification_code' => $verificationCode
    ];

    $emailMessage = new AMQPMessage(
        json_encode($emailPayload),
        [
            'content_type' => 'application/json',
            'delivery_mode' =>
                AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]
    );

    $channel->basic_publish(
        $emailMessage,
        '',
        'email.verification'
    );

    $logger->info(
        "New verification code queued for {$email} " .
        "(user_id={$user['user_id']})"
    );

    return [
        'status' => 'success',
        'message' => 'new verification code sent'
    ];
}


// tad46: Login handler
function handleLogin($db, $logger, $data) 
{
    if (empty($data['email']) || empty($data['password'])) 
    {
        $logger->warning("Login attempt missing fields");
        return ['status' => 'error', 'message' => 'missing fields'];
    }

  // rma9: Selects the user's email with the login account data
// so it can be returned to the App VM and stored in the session.
    $stmt = $db->prepare
    (
	"SELECT user_id, username, email, password_hash, role, email_verified
 	FROM users
 	WHERE email = ?" 
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

   // rma9: Blocks login until the user's email has been verified.
if ((int)$result['email_verified'] !== 1)
{
    $logger->warning(
        "Login blocked for unverified email: {$data['email']}"
    );

    return [
        'status' => 'error',
        'message' => 'please verify your email before logging in'
    ];
}


// rma9: Returns the verified user's account information,
// including the email needed for the profile settings page.
    $logger->info("Successful login: {$data['email']} (user_id={$result['user_id']})");

return 
[
    'status'   => 'success',
    'user_id'  => $result['user_id'],
    'username' => $result['username'],
    'email'    => $result['email'],
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
       
	else if ($routingKey === 'user.verify')
	{
   	 $response = handleVerifyEmail($db, $logger, $data);
	}
	
	else if ($routingKey === 'user.resend_verification')
	{
    	$response = handleResendVerification(
        $db,
        $logger,
        $data
	    );
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
