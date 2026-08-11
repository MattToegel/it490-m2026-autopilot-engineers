<?php

// rma9: Loads Composer packages, including RabbitMQ and Resend.
require_once __DIR__ . '/vendor/autoload.php';

// rma9: Loads API VM environment variables from the ignored .env file.
$env = parse_ini_file(__DIR__ . '/.env');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$resendApiKey = $env['RESEND_API_KEY'] ?? '';

if ($resendApiKey === '')
{
    exit("RESEND_API_KEY is missing from .env\n");
}

// rma9: Creates the Resend client used to send verification emails.
$resend = Resend::client($resendApiKey);

// rma9: Connects the email worker to RabbitMQ.
$connection = new AMQPStreamConnection(
    $env['RABBITMQ_HOST'],
    (int)($env['RABBITMQ_PORT'] ?? 5672),
    $env['RABBITMQ_USER'],
    $env['RABBITMQ_PASSWORD'],
    $env['RABBITMQ_VHOST'] ?? '/'
);

$channel = $connection->channel();

// rma9: Declares the verification-email queue.
$channel->queue_declare(
    'email.verification',
    false,
    true,
    false,
    false
);

echo "Email worker connected to RabbitMQ.\n";
echo "Waiting on queue: email.verification\n";

// rma9: Handles one verification-email job received through RabbitMQ.
$callback = function (AMQPMessage $message) use ($resend): void
{
    $job = json_decode($message->body, true);

    $email = trim($job['email'] ?? '');
    $code = trim($job['verification_code'] ?? '');

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !preg_match('/^\d{6}$/', $code)
    )
    {
        echo "Invalid verification email job.\n";
        $message->ack();
        return;
    }

    try
    {
        $resend->emails->send([
            'from' => 'OnTheRadar <onboarding@resend.dev>',
            'to' => [$email],
            'subject' => 'Your OnTheRadar verification code',
            'html' => "
                <h2>Verify your OnTheRadar account</h2>
                <p>Your verification code is:</p>
                <h1>{$code}</h1>
                <p>This code expires in 10 minutes.</p>
            ",
        ]);

        echo "Verification email sent to {$email}\n";

        $message->ack();
    }
    catch (Throwable $exception)
    {
        echo "Email send failed: "
            . $exception->getMessage()
            . "\n";

        // rma9: Requeues the message so it can be tried again.
        $message->nack(false, true);
    }
};

// rma9: Consumes verification-email jobs from RabbitMQ.
$channel->basic_consume(
    'email.verification',
    '',
    false,
    false,
    false,
    false,
    $callback
);

while ($channel->is_consuming())
{
    $channel->wait();
}
