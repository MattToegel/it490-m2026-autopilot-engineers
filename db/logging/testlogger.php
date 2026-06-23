<?php
// logger.php - Application-callable logging interface
// Publishes log events to the app.requests exchange with routing key log.insert

date_default_timezone_set('America/New_York');

require_once __DIR__ . '/../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Logger 
{
    private $connection;
    private $channel;
    private $source;

    public function __construct($source) 
    {
        $this->source = $source;

        // Load .env values
        $env = parse_ini_file(__DIR__ . '/../.env');

        $this->connection = new AMQPStreamConnection
        (
            $env['RABBITMQ_HOST'],
            $env['RABBITMQ_PORT'] ?? 5672,
            $env['RABBITMQ_USER'],
            $env['RABBITMQ_PASSWORD'],
            $env['RABBITMQ_VHOST'] ?? '/'
        );
        $this->channel = $this->connection->channel();
    }

    public function publishLog($level, $message) 
    {
        $payload = 
        [
            'source'     => $this->source,
            'level'      => $level,
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $msg = new AMQPMessage
        (
            json_encode($payload),
            [
                'content_type'  => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]
        );

        // Publish to the team's request exchange with the log.insert routing key
        $this->channel->basic_publish($msg, 'app.requests', 'log.insert');
        echo "Published [$level] from {$this->source}: $message\n";
    }

    // Shortcut methods for each level
    public function info($message)    
    { 
        $this->publishLog('info', $message); 
    }

    public function warning($message) 
    { 
        $this->publishLog('warning', $message); 
    }

    public function error($message)   
    { 
        $this->publishLog('error', $message); 
    }

    public function close() 
    {
        $this->channel->close();
        $this->connection->close();
    }
}