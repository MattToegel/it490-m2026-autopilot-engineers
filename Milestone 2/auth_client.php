<?php
// auth_client.php
// tad46: Sends auth requests to the DB VM through RabbitMQ and waits for a response
// tad46: Uses the correlation_id + reply_to pattern to match responses to requests

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendAuthRequest($routingKey, $data) 
{
    // tad46: Load credentials from .env at the repo root
    $env = parse_ini_file(__DIR__ . '/../.env');

    // tad46: Open a connection to RabbitMQ
    $connection = new AMQPStreamConnection
    (
        $env['RABBITMQ_HOST'],
        $env['RABBITMQ_PORT'] ?? 5672,
        $env['RABBITMQ_USER'],
        $env['RABBITMQ_PASSWORD'],
        $env['RABBITMQ_VHOST'] ?? '/'
    );
    $channel = $connection->channel();

    // tad46: Make a temporary, exclusive reply queue for this specific request
    // tad46: RabbitMQ auto-generates a unique name; the queue dies when the connection closes
    list($callbackQueue, , ) = $channel->queue_declare('', false, false, true, false);

    // tad46: Generate a unique correlation ID so we can match the response
    $correlationId = uniqid('auth_', true);
    $response = null;

    // tad46: Set up the callback that will run when the response arrives
    $channel->basic_consume
    (
        $callbackQueue, '', false, true, false, false,
        function ($msg) use (&$response, $correlationId) 
        {
            if ($msg->get('correlation_id') === $correlationId) 
            {
                $response = json_decode($msg->body, true);
            }
        }
    );

    // tad46: Build and publish the request
    $msg = new AMQPMessage(json_encode($data), 
    [
        'correlation_id' => $correlationId,
        'reply_to'       => $callbackQueue,
        'content_type'   => 'application/json',
    ]);
    $channel->basic_publish($msg, 'app.requests', $routingKey);

    // tad46: Wait for the response with a timeout so the page doesn't hang forever
    $timeout = 5; // 5 seconds
    $start = time();
    while (!$response && (time() - $start) < $timeout) 
    {
        try 
        {
            $channel->wait(null, false, 1);
        } 
        catch (Exception $e) 
        {
            // tad46: Timeout on this iteration is fine, keep looping until full timeout
        }
    }

    $channel->close();

    $connection->close();

    // tad46: Returns null if no response came back within the timeout window
    return $response;
}