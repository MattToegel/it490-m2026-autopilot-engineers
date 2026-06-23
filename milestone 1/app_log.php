<?php
//rma9: this tells the computer this file is written in php
//rma9: this file is for sending app vm log events to rabbitmq
//rma9: the app vm does not write the final log file directly, it sends the log message through mq

//rma9: this sets the timezone so the timestamp uses our class/local timezone
date_default_timezone_set('America/New_York');

//rma9: this loads composer libraries from the vendor folder
//rma9: we need this because the rabbitmq php package is installed through composer
require_once __DIR__ . '/vendor/autoload.php';

//rma9: this lets php use the rabbitmq connection class
//rma9: this class is what connects the app vm to the mq server
use PhpAmqpLib\Connection\AMQPStreamConnection;

//rma9: this lets php use the rabbitmq message class
//rma9: this class is what packages my log event into a message rabbitmq can send
use PhpAmqpLib\Message\AMQPMessage;

//rma9: this reads the .env file in the same folder as this script
//rma9: the .env file holds the rabbitmq host, port, username, and password
$env = parse_ini_file(__DIR__ . '/.env');

//rma9: this function sends one app vm log event to rabbitmq
//rma9: future app code can call this function whenever it needs to create a log event
function publishAppLog($level, $messageText): bool {
    //rma9: this lets the function use the .env settings loaded above
    global $env;

    //rma9: this gets the rabbitmq server vm tailscale ip from the .env file
    //rma9: the app vm needs this ip so it knows where rabbitmq is running
    $rabbitmqHost = $env['RABBITMQ_HOST'];

    //rma9: this gets the rabbitmq port from the .env file
    //rma9: port 5672 is the normal rabbitmq port for message traffic
    $rabbitmqPort = $env['RABBITMQ_PORT'];

    //rma9: this gets the rabbitmq username from the .env file
    //rma9: the app vm needs this username to log into rabbitmq
    $rabbitmqUser = $env['RABBITMQ_USER'];

    //rma9: this gets the rabbitmq password from the .env file
    //rma9: the app vm needs this password to authenticate with rabbitmq
    $rabbitmqPassword = $env['RABBITMQ_PASSWORD'];

    //rma9: this is the exchange made in the rabbitmq setup script
    //rma9: app log messages are sent into this exchange first
    $exchangeName = "app.requests";

    //rma9: this is the routing key for log messages
    //rma9: in the mq setup script, log.insert routes messages to the db.logs queue
    $routingKey = "log.insert";

    try {
        //rma9: this starts the protected section where rabbitmq connection errors can be caught

        //rma9: this opens the connection from the app vm to the rabbitmq server
        //rma9: it uses the ip, port, username, and password from the .env file
        $connection = new AMQPStreamConnection(
            $rabbitmqHost,
            $rabbitmqPort,
            $rabbitmqUser,
            $rabbitmqPassword
        );

        //rma9: this opens a rabbitmq channel
        //rma9: rabbitmq sends/publishes messages through a channel
        $channel = $connection->channel();

        //rma9: this creates the log event as a php array before sending it
        //rma9: these fields help the team know where the log came from and what happened
        $logEvent = [
            "source" => "appdev", //rma9: this says the log came from my app vm hostname
            "role" => "app", //rma9: this says the log came from the app server role
            "level" => $level, //rma9: this stores the log level passed into the function
            "message" => $messageText, //rma9: this stores the log message passed into the function
            "created_at" => date("Y-m-d H:i:s") //rma9: this adds the time the log event was created
        ];

        //rma9: this turns the php array into json text
        //rma9: json is used so the other vms can read the log message clearly
        $logJson = json_encode($logEvent);

        //rma9: this checks if the json conversion failed
        //rma9: if the log cannot become json, it should not be sent to rabbitmq
        if ($logJson === false) {
            //rma9: this throws an error so the catch block can handle the json problem
            throw new Exception("Could not convert App VM log event to JSON.");
        }

        //rma9: this creates the rabbitmq message using the json log
        //rma9: this is the actual message object that rabbitmq will route
        $rabbitMessage = new AMQPMessage($logJson, [
            "content_type" => "application/json", //rma9: this tells the listener that the message body is json
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT //rma9: this makes the message more durable if queues are durable
        ]);

        //rma9: this publishes the app vm log message to rabbitmq
        //rma9: it sends the message to app.requests with routing key log.insert
        $channel->basic_publish($rabbitMessage, $exchangeName, $routingKey);

        //rma9: this closes the rabbitmq channel after the message is sent
        //rma9: closing it keeps the script clean after publishing
        $channel->close();

        //rma9: this closes the rabbitmq connection after the message is sent
        //rma9: closing it prevents leaving an open connection behind
        $connection->close();

        //rma9: this prints the json log message in the terminal
        //rma9: this helps with screenshot proof during testing and demo
        echo "App VM log message: " . $logJson . "\n";

        //rma9: this returns true because the log was sent successfully
        //rma9: the demo script can use this to print that the log sent
        return true;

    } catch (Exception $e) {
        //rma9: this catches connection, json, or publishing errors

        //rma9: this prints the error so I can see why the log did not send
        echo "App VM log publish failed: " . $e->getMessage() . "\n";

        //rma9: this returns false because the log did not send successfully
        //rma9: the demo script can use this to print that the log failed
        return false;
    }
}
