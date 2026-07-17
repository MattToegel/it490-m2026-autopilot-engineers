<?php
// mq_helper.php — Reusable RabbitMQ send+receive helper
// IT490 MVP | ns87

require_once __DIR__ . '/app/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function mq_send_and_receive(array $payload, string $requestQueue, string $responseQueue): ?array {
    $correlationId             = uniqid('req_', true);
    $payload['correlation_id'] = $correlationId;

    try {
        $connection = new AMQPStreamConnection(MQ_HOST, MQ_PORT, MQ_USER, MQ_PASS, MQ_VHOST);
        $channel    = $connection->channel();
        $channel->queue_declare($requestQueue,  false, true, false, false);
        $channel->queue_declare($responseQueue, false, true, false, false);

        $msg = new AMQPMessage(json_encode($payload), [
            'delivery_mode'  => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'correlation_id' => $correlationId,
        ]);
        $channel->basic_publish($msg, '', $requestQueue);

        $response = null;
        $start    = time();
        while ($response === null && (time() - $start) < 10) {
            $incoming = $channel->basic_get($responseQueue);
            if ($incoming && $incoming->get('correlation_id') === $correlationId) {
                $response = json_decode($incoming->body, true);
                $channel->basic_ack($incoming->getDeliveryTag());
            } elseif ($incoming) {
                $channel->basic_nack($incoming->getDeliveryTag(), false, true);
            }
            if ($response === null) usleep(200000);
        }

        $channel->close();
        $connection->close();
        return $response;

    } catch (Exception $e) {
        error_log('[mq_helper] Error: ' . $e->getMessage());
        return null;
    }
}
