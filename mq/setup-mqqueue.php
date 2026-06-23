<?php
// cao39 MQ Queue Setup
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

try {

    echo "Starting the RabbitMQ setup...\n\n";

    // cao39 Define the RabbitMQ host
    $rabbitmqHost = "localhost";

    // cao39 Define the RabbitMQ port (default port)
    $rabbitmqPort = 5672;

    // cao39 RabbitMQ Username
    $rabbitmqUser = "it490";

    // cao39 RabbitMQ Password
    $rabbitmqPassword = "it490";

    echo "Establishing connection to the RabbitMQ server...\n";

    $connection = new AMQPStreamConnection($rabbitmqHost, $rabbitmqPort, $rabbitmqUser, $rabbitmqPassword);

    // cao39 Opens a channel to publish messages and set up queues/exchanges
    $channel = $connection->channel();

    echo "Connection successful!\n\n";

    // cao39 Exchange that handling incoming app requests
    $exchangeRequest = "app.requests";

    // cao39 Exchange for outgoing responses
    $exchangeResponse = "app.responses";

    // cao39 Exchange that handles dead-letters (failed messages)
    $exchangeDeadLetter = "deadletter";

    echo "Making the request exchange...\n";

    // cao39 Routes messages in a topic exchange using matching routing keys
    $channel->exchange_declare($exchangeRequest, "topic", false, true, false);

    echo "Making the response exchange...\n";

    // cao39 Create the response exchange for job replies & any results
    $channel->exchange_declare($exchangeResponse, "topic", false, true, false);

    echo "Making the dead letter exchange...\n";

    // cao39 Creates the dead letter exchange (fanout sends to all of the bound queues)
    $channel->exchange_declare($exchangeDeadLetter, "fanout", false, true, false);

    // cao39 API requests queue
    $apiQueue = "api.requests";

    // cao39 Database logging queue
    $databaseQueue = "db.logs";

    // cao39 APP results queue
    $resultsQueue = "app.results";

    // cao39 Dead-letter messages queue
    $deadLetterQueue = "dlq";

    echo "Making the API queue...\n";

    // cao39 API queue
    $channel->queue_declare($apiQueue, false, true, false, false);

    echo "Making the database queue...\n";

    // cao39 Database queue
    $channel->queue_declare($databaseQueue, false, true, false, false);

    echo "Making APP results queue...\n";

    // cao39 App results queue
    $channel->queue_declare($resultsQueue, false, true, false, false);

    echo "Making the dead letter queue...\n";

    // cao39 Dead letter queue
    $channel->queue_declare($deadLetterQueue, false, true, false, false);

    echo "Making the queue bindings...\n";

    // cao39 Bind login requests to the API queue
    $channel->queue_bind($apiQueue, $exchangeRequest, "user.login");

    // cao39 Bind registration requests to the API queue
    $channel->queue_bind($apiQueue, $exchangeRequest, "user.register");

    // cao39 Bind flight search requests to the API queue
    $channel->queue_bind($apiQueue, $exchangeRequest, "search.flight");

    // cao39 Bind logging messages to the DB queue
    $channel->queue_bind($databaseQueue, $exchangeRequest, "log.insert");

    // cao39 Bind successful job results
    $channel->queue_bind($resultsQueue, $exchangeResponse, "job.complete");

    // cao39 Bind the failed job results
    $channel->queue_bind($resultsQueue, $exchangeResponse, "job.failed");

    // cao39 Bind the dead letter queue
    $channel->queue_bind($deadLetterQueue, $exchangeDeadLetter);

    echo "\nRabbitMQ setup completed successfully!\n";

    // cao39 Close channel
    $channel->close();

    // cao39 Close connection to RabbitMQ server
    $connection->close();

    echo "Connection closed.\n";

// cao39 Catches any errors that happen
} catch (Exception $e) {

    echo "RabbitMQ setup failed.\n";

    // cao39 Print the error messages from exception
    echo "Error: " . $e->getMessage() . "\n";
}