<?php
//xml: This ensures that the timezone for this code is in the correct manner
date_default_timezone_set('America/New_York');
//xml: This gives acces to call the RabbitMQ library
require_once __DIR__ . '/vendor/autoload.php';

//xml: This imports Rabbit dependencies (connection and message)
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$env = parse_ini_file(__DIR__ . '/.env');
/*xml: This function sends a message off to RabbitMQ.
 This will allow for calling of the function and
usage whenever a log is needed to be created.*/
function publishLog(): bool {
    global $env;
//xml: This is the IP off the RabbitMQ server to start a connection
    $rabbitHost = $env['RABBITMQ_HOST'];

//xml" This is the port for RabbitMq
    $rabbitPort = $env['RABBITMQ_PORT'];

//xml: username and password for RabbitMQ
    $rabbitUser = $env['RABBITMQ_USER'];
    $rabbitPassword = $env['RABBITMQ_PASSWORD'];
//xml: this creates a RabbitMQ connection
    try {
        $connection = new AMQPStreamConnection(
            $rabbitHost,
            $rabbitPort,
            $rabbitUser,
            $rabbitPassword
        );

//xml: This command starts a channel connection for RabbitMQ

        $channel = $connection->channel();

/*xml: This is a static log that will test for both a valid 
and invalid log message that will get sent through RabbitMQ into 
the database consumer*/
        $log = [
//xml: level of log
            
//xml: Log message that will be sent
            'message' => 'this is to test log for API worker',
//xml: The source  of where the message will be sent from
            'source' => 'api-worker',
//xml: The timestamp that will be sent off with the
            'created_at' => date('Y-m-d H:i:s'),
        ];

//xml: This converts the array that is in php an transforms it into JSON 
        $rabbitMessage = new AMQPMessage(json_encode($log), [
            'content' => 'application/json',
            'delivery' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ]);
/*xml: This publishes the message off the the server using routing keys 
to help find which queue will recieve the messages*/
        $channel->basic_publish($rabbitMessage, 'app.requests', 'log.insert');

//xml: This closes the channel and the connection
        $channel->close();
        $connection->close();

        return true;

//xml: This exception and message is thrown if any error happens 
    } catch (Exception $e) {
        echo "Log publish failed: " . $e->getMessage() . "\n";
        return false;
    }
}

