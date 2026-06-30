<?php

//cao39: RabbitMQ Queue Toplogy Setup for Milestone 2


require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

try {

    echo "Starting RabbitMQ Queue Setup\n";
    //cao39: Adding the RabbitMQ settings
    $rabbitmqHost = getenv('RABBITMQ_HOST') ?: "localhost";
    $rabbitmqPort = (int)(getenv('RABBITMQ_PORT') ?: 5672);
    $rabbitmqUser = getenv('RABBITMQ_USER') ?: "it490";
    $rabbitmqPassword = getenv('RABBITMQ_PASSWORD') ?: "it490";

    echo "Connecting to RabbitMQ\n";
    //cao39: Opening a RabbitMQ connection
    $connection = new AMQPStreamConnection(
        $rabbitmqHost,
        $rabbitmqPort,
        $rabbitmqUser,
        $rabbitmqPassword
    );
    //Cao39: communication channel has been opened
    $channel = $connection->channel();
    echo "Sucessfull Connection!\n\n";

    //cao39: Exchange created for App VM authentication requests
    $exchangeRequest = "app.requests";
    $exchangeResponse = "app.responses";
    $exchangeDeadLetter = "deadletter";

    echo "Creating exchanges...\n";

    //cao39: Exchange for incoming requests
    $channel->exchange_declare($exchangeRequest, "topic", false, true, false);

    //cao39: Exchange for outgoing responses
    $channel->exchange_declare($exchangeResponse, "topic", false, true, false);

    //cao39: Fanout exchange for dead letters
    $channel->exchange_declare($exchangeDeadLetter, "fanout", false, true, false);


    $authQueue = "db.auth";
    $apiQueue = "api.requests";
    $databaseQueue = "db.logs";
    $responseQueue = "app.reply";
    $deadLetterQueue = "dlq";

    echo "Creating queues with DLQ support...\n";

    //cao39: Dead Letter Queue will automatically delete route failed messages after 30 seconds
    $dlqArgs = new AMQPTable([
        'x-dead-letter-exchange' => $exchangeDeadLetter,
        'x-message-ttl' => 30000
    ]);


    //cao39: Authentication Queue
    $channel->queue_declare($authQueue, false, true, false, false, false, $dlqArgs);
    echo "  • Created '$authQueue' (with DLQ)\n";

    //cao39: API Queue
    $channel->queue_declare($apiQueue, false, true, false, false, false, $dlqArgs);
    echo "  • Created '$apiQueue' (with DLQ)\n";

    //cao39: Log Queue
    $channel->queue_declare($databaseQueue, false, true, false, false, false, $dlqArgs);
    echo "  • Created '$databaseQueue' (with DLQ)\n";

    //cao39: Response Queue so that the APP VM can receive results
    $channel->queue_declare($responseQueue, false, true, false, false, false);
    echo "  • Created '$responseQueue'\n";

    //cao39: Dead Letter Queue
    $channel->queue_declare($deadLetterQueue, false, true, false, false, false);
    echo "  • Created '$deadLetterQueue'\n";

    echo "\nBinding queues to exchanges...\n";


    //cao39: Authentication Routing 
    $channel->queue_bind($authQueue, $exchangeRequest, "user.register");
    $channel->queue_bind($authQueue, $exchangeRequest, "user.login");
    $channel->queue_bind($authQueue, $exchangeRequest, "user.verify");

    //cao39: API Routing
    $channel->queue_bind($apiQueue, $exchangeRequest, "search.flight");

    //cao39: Log Routing
    $channel->queue_bind($databaseQueue, $exchangeRequest, "log.insert");

    //cao39: Routing response going back to the APP VM
    $channel->queue_bind($responseQueue, $exchangeResponse, "job.complete");
    $channel->queue_bind($responseQueue, $exchangeResponse, "job.failed");

    //cao39: DLQ Bind
    $channel->queue_bind($deadLetterQueue, $exchangeDeadLetter);


    echo "RabbitMQ topology has been created.\n";

    $channel->close();
    $connection->close();

    echo "Communication chanel and connection closed.\n";

} catch (Exception $e) {
    echo "\nRabbitMQ setup has failed.\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}