<?php
// places_client.php
// ns87: App VM -> RabbitMQ -> API VM transport for Google Places lookups (US-03 stretch).
// ns87:
// ns87: The App VM holds NO Google credentials. It publishes a request onto the
// ns87: app.requests exchange, the API VM's worker performs the Google Places call using
// ns87: the key in api/.env, and the reply comes back on a temporary reply queue.
// ns87: This mirrors the envelope search.php already uses for search.flight /
// ns87: search.airport / search.route, so the API worker parses it with no special case.
// ns87:
// ns87: Envelope the API worker expects:
// ns87:   { "routing_key": "<key>", "payload": { ... } }

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendPlacesRequest($routingKey, $payload)
{
    $env = parse_ini_file(__DIR__ . '/../.env');

    $connection = new AMQPStreamConnection
    (
        $env['RABBITMQ_HOST'],
        $env['RABBITMQ_PORT'] ?? 5672,
        $env['RABBITMQ_USER'],
        $env['RABBITMQ_PASSWORD'],
        $env['RABBITMQ_VHOST'] ?? '/'
    );
    $channel = $connection->channel();

    // ns87: exclusive auto-delete reply queue, same as report_client.php
    list($callbackQueue, , ) = $channel->queue_declare('', false, false, true, false);

    $correlationId = uniqid('places_', true);
    $response = null;

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

    // ns87: the API worker reads routing_key out of the BODY as well as the AMQP
    // ns87: routing key, so both are sent - see its $supportedRoutingKeys check.
    $body =
    [
        'routing_key' => $routingKey,
        'payload'     => $payload,
    ];

    $msg = new AMQPMessage(json_encode($body),
    [
        'correlation_id' => $correlationId,
        'reply_to'       => $callbackQueue,
        'content_type'   => 'application/json',
    ]);

    $channel->basic_publish($msg, 'app.requests', $routingKey);

    // ns87: external API calls are slower than DB calls, so allow a longer window
    // ns87: than report_client.php's 5s before giving up.
    $timeout = 8;
    $start = time();
    while (!$response && (time() - $start) < $timeout)
    {
        try
        {
            $channel->wait(null, false, 1);
        }
        catch (Exception $e)
        {
            // partial-wait timeout is expected while polling
        }
    }

    $channel->close();
    $connection->close();

    // ns87: null means nothing answered in time. Callers must treat this as
    // ns87: "Places unavailable" and fall back, per the proposal's failure-handling plan.
    return $response;
}
