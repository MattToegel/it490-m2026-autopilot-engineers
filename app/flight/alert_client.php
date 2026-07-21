<?php
// alert_client.php
// tad46: RPC helper for the alerts queue.
// tad46: Same reply_to + correlation_id pattern as flight_client.php.

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendAlertRequest($routingKey, $data)
{
    $env = parse_ini_file(__DIR__ . '/../.env');

    $connection = new AMQPStreamConnection(
        $env['RABBITMQ_HOST'],
        $env['RABBITMQ_PORT'] ?? 5672,
        $env['RABBITMQ_USER'],
        $env['RABBITMQ_PASSWORD'],
        $env['RABBITMQ_VHOST'] ?? '/'
    );
    $channel = $connection->channel();

    list($callbackQueue, , ) = $channel->queue_declare('', false, false, true, false);

    $correlationId = uniqid('alert_', true);
    $response = null;

    $channel->basic_consume(
        $callbackQueue, '', false, true, false, false,
        function ($msg) use (&$response, $correlationId)
        {
            if ($msg->get('correlation_id') === $correlationId)
            {
                $response = json_decode($msg->body, true);
            }
        }
    );

    $msg = new AMQPMessage(json_encode($data), [
        'correlation_id' => $correlationId,
        'reply_to'       => $callbackQueue,
        'content_type'   => 'application/json',
    ]);
    $channel->basic_publish($msg, 'app.requests', $routingKey);

    $timeout = 5;
    $start = time();
    while (!$response && (time() - $start) < $timeout)
    {
        try { $channel->wait(null, false, 1); }
        catch (Exception $e) {}
    }

    $channel->close();
    $connection->close();

    return $response;
}