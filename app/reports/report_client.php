<?php
// report_client.php ns87-noaman
// nms37: Sends US-03 report requests to the DB VM through RabbitMQ and waits for a response.
// nms37: Mirrors auth_client.php exactly — correlation_id + reply_to RPC over the
// nms37: app.requests topic exchange, dispatched by routing key (report.create, report.list, etc.).

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendReportRequest($routingKey, $data)
{
    // nms37: Load shared credentials from .env at the app/ root (same file auth_client.php uses)
    $env = parse_ini_file(__DIR__ . '/../.env');

    // nms37: Open a connection to RabbitMQ
    $connection = new AMQPStreamConnection(
        $env['RABBITMQ_HOST'],
        $env['RABBITMQ_PORT'] ?? 5672,
        $env['RABBITMQ_USER'],
        $env['RABBITMQ_PASSWORD'],
        $env['RABBITMQ_VHOST'] ?? '/'
    );
    $channel = $connection->channel();

    // nms37: Temporary, exclusive reply queue for this one request; dies with the connection
    list($callbackQueue, , ) = $channel->queue_declare('', false, false, true, false);

    // nms37: Unique correlation ID so we only accept our own reply
    $correlationId = uniqid('report_', true);
    $response = null;

    $channel->basic_consume(
        $callbackQueue, '', false, true, false, false,
        function ($msg) use (&$response, $correlationId) {
            if ($msg->get('correlation_id') === $correlationId) {
                $response = json_decode($msg->body, true);
            }
        }
    );

    // nms37: Publish to the shared topic exchange with the report.* routing key
    $msg = new AMQPMessage(json_encode($data), [
        'correlation_id' => $correlationId,
        'reply_to'       => $callbackQueue,
        'content_type'   => 'application/json',
    ]);
    $channel->basic_publish($msg, 'app.requests', $routingKey);

    // nms37: Wait up to 5s so the page never hangs forever
    $timeout = 5;
    $start = time();
    while (!$response && (time() - $start) < $timeout) {
        try {
            $channel->wait(null, false, 1);
        } catch (Exception $e) {
            // nms37: per-iteration timeout is fine, keep looping until the full timeout
        }
    }

    $channel->close();
    $connection->close();

    // nms37: null if no reply arrived within the timeout
    return $response;
}
