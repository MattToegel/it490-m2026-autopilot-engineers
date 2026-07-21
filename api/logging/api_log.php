
<?php

/**xml: this is the file that is in charge of publish the logs and
 *actities that occur with on my vm.
 *The log message consists of a source, level, message, and a created time
 * variable.
 */

//xml: This enforces the time zone for my logs
date_default_timezone_set('America/New_York');
use PhpAmqpLib\Message\AMQPMessage;

/*xml: This function is in chagre of creating the log message that
will be shipped off to the database for saving of logs. This function
will create the message, publish it to the RabbitMQ server and display
that log to my terminal for me to see as well */
function logConvo(string $level, string $message, string $source = "api_worker"): void {
    $entry = [
        "source" => $source,
        "level" => strtoupper($level),
        "message" => $message,
        "created_at" => date("Y-m-d H:i:s")
    ];

    global $channel;

    //xml: shows me the log message on my terminal
    echo "[{$entry['level']}] {$entry['source']}: {$entry['message']}\n";

    if (!isset($channel)){
        return;
    }

	//xml: Converts the log to JSON and publishes to RabbitMQ
    try
    {
        $amqpMessage = new AMQPMessage(
            json_encode(
                $entry,
                JSON_UNESCAPED_SLASHES
            ),
            [
                "content_type" => "application/json",
                "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ]
        );

        $channel->basic_publish(
            $amqpMessage,
            "app.requests",
            "log.insert"
        );
    }
//xml: If the RabbitMQ cannot publish my message, then I will get a failure message
    catch (Exception $e)
    {
        fwrite(
            STDERR,
            "Could not publish log: " . $e->getMessage() . "\n"
        );
    }
}
