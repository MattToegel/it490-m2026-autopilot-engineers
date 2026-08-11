<?php
// flight_client.php
// tad46: Sends flight requests to the DB VM through RabbitMQ and waits for a response
// tad46: Same reply_to + correlation_id pattern as auth_client.php, just scoped to flights
require_once __DIR__ . '/../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
function sendFlightRequest($routingKey, $data)
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
    // tad46: "flight_" prefix makes it easy to distinguish from auth traffic when debugging
    $correlationId = uniqid('flight_', true);
    $response = null;
    $rawBody  = null;
    // tad46: Set up the callback that will run when the response arrives
    $channel->basic_consume
    (
        $callbackQueue, '', false, true, false, false,
        function ($msg) use (&$response, &$rawBody, $correlationId)
        {
            if ($msg->get('correlation_id') === $correlationId)
            {
                $rawBody  = $msg->body;
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

    if ($rawBody === null)
    {
        throw new Exception('The flight service did not respond in time. Please try again.');
    }

    if ($response === null)
    {
        // tad46: json_decode failed - the worker replied but sent malformed JSON
        throw new Exception('Received an invalid response from the flight service.');
    }

    // tad46: Returns the decoded response (never null - failures above throw instead)
    return $response;
}
