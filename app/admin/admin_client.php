<?php
// cao39 - App VM client for US-04 Admin Community Management
// cao39 - Sends admin requests to the DB VM through RabbitMQ

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;



// cao39 - Sends admin request APP VM -> RabbitMQ -> DB VM
function sendAdminRequest($routingKey, $data)
{

    // cao39 - Load environment variables
    $env = parse_ini_file(__DIR__ . '/../.env');

    // tad46: EDIT  attach the logged-in admin's user_id to every
    // tad46: admin request so the DB consumer can record the action in
    // tad46: admin_activity_logs. Callers don't need to pass it themselves;
    // tad46: an explicitly provided admin_user_id is left untouched.
    if (empty($data['admin_user_id']) && !empty($_SESSION['user_id']))
    {
        $data['admin_user_id'] = (int)$_SESSION['user_id'];
    }

    // Connect to RabbitMQ
    $connection = new AMQPStreamConnection
    (
        $env['RABBITMQ_HOST'],
        $env['RABBITMQ_PORT'] ?? 5672,
        $env['RABBITMQ_USER'],
        $env['RABBITMQ_PASSWORD'],
        $env['RABBITMQ_VHOST'] ?? '/'
    );


    $channel = $connection->channel();



    // Temporary response queue
    list($callbackQueue, , ) =
        $channel->queue_declare('', false, false, true, false);



    // Create unique request ID
    $correlationId = uniqid('admin_', true);

    $response = null;



    // Listen for DB response
    $channel->basic_consume
    (
        $callbackQueue,'',false,true,false,false,
        function ($msg) use (&$response, $correlationId)
        {

            if ($msg->get('correlation_id') === $correlationId)
            {
                $response = json_decode($msg->body, true);
            }

        }
    );



    // Build RabbitMQ message
    $msg = new AMQPMessage
    (
        json_encode($data),
        [
            'correlation_id'=>$correlationId,
            'reply_to'=>$callbackQueue,
            'content_type'=>'application/json'
        ]
    );



    // Send request through MQ
    $channel->basic_publish
    (
        $msg,
        'app.requests',
        $routingKey
    );



    // Wait for response
    $timeout = 5;
    $start = time();


    while (!$response && (time()-$start)<$timeout)
    {
        try
        {
            $channel->wait(null,false,1);
        }
        catch(Exception $e)
        {
            // Continue waiting
        }
    }



    $channel->close();
    $connection->close();



    return $response;

}

?>
