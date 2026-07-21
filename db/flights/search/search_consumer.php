<?php
// search_consumer.php
// tad46: DB VM consumer that handles search history requests
// tad46: Listens on db.search queue, dispatches by routing key
// tad46: Handles search.record (write) and search.list (read)

// tad46: Load Composer autoloader (vendor is three folders up now: search -> flights -> db -> repo)
require_once __DIR__ . '/../../vendor/autoload.php';

// tad46: Load the Logger class from the logging folder
require_once __DIR__ . '/../../logging/testlogger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// tad46: Load credentials from .env at the db/ root
$env = parse_ini_file(__DIR__ . '/../../../.env');

// tad46: Open a connection to MySQL on this same VM
$db = new mysqli
(
    'localhost',
    $env['MYSQL_USER'],
    $env['MYSQL_PASSWORD'],
    $env['MYSQL_DATABASE']
);
if ($db->connect_error)
{
    die("MySQL connection failed: " . $db->connect_error . "\n");
}

// tad46: Try to connect to the RabbitMQ broker
try
{
    $connection = new AMQPStreamConnection
    (
        $env['RABBITMQ_HOST'],
        $env['RABBITMQ_PORT'] ?? 5672,
        $env['RABBITMQ_USER'],
        $env['RABBITMQ_PASSWORD'],
        $env['RABBITMQ_VHOST'] ?? '/'
    );
}
catch (Exception $e)
{
    echo "Can't connect to RabbitMQ at {$env['RABBITMQ_HOST']}:{$env['RABBITMQ_PORT']}\n";
    echo "Is the broker running and reachable?\n";
    exit(1);
}
$channel = $connection->channel();

// tad46: Create a logger instance that identifies messages as coming from db-search
$logger = new Logger('db-search');

// tad46: Queue this consumer listens on
$queue = 'db.search';

echo "DB VM search consumer listening on '$queue'...\n";


// tad46: Record Search handler
// tad46: Anonymous searches (no user_id) skip this write - only logged-in users
// tad46: get search history entries.
function handleSearchRecord($db, $logger, $data)
{
    if (empty($data['user_id']))
    {
        return ['status' => 'skipped', 'message' => 'anonymous search not recorded'];
    }

    $userId           = (int)$data['user_id'];
    $searchType       = $data['search_type']       ?? 'number';
    $airportCode      = $data['airport_code']      ?? null;
    $flightNumber     = $data['flight_number']     ?? null;
    $departureAirport = $data['departure_airport'] ?? null;
    $arrivalAirport   = $data['arrival_airport']   ?? null;

    $stmt = $db->prepare
    (
        "INSERT INTO search_history
         (user_id, search_type, airport_code, flight_number,
          departure_airport, arrival_airport)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('isssss',
        $userId,
        $searchType,
        $airportCode,
        $flightNumber,
        $departureAirport,
        $arrivalAirport
    );

    try
    {
        $stmt->execute();
        $searchId = $stmt->insert_id;
        $logger->info("Search recorded: search_id={$searchId}, user_id={$userId}, type={$searchType}");
        return
        [
            'status'    => 'success',
            'search_id' => $searchId,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Search record DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}


// tad46: List Searches handler
// tad46: Powers the dashboard's "Recent searches" stat and (future) search history page.
function handleSearchList($db, $logger, $data)
{
    if (empty($data['user_id']))
    {
        return ['status' => 'error', 'message' => 'missing user_id'];
    }

    $userId = (int)$data['user_id'];
    $limit  = (int)($data['limit'] ?? 10);
    if ($limit < 1)  $limit = 1;
    if ($limit > 50) $limit = 50;

    $stmt = $db->prepare
    (
        "SELECT search_id, search_type, airport_code, flight_number,
                departure_airport, arrival_airport, searched_at
         FROM search_history
         WHERE user_id = ?
         ORDER BY searched_at DESC
         LIMIT ?"
    );
    $stmt->bind_param('ii', $userId, $limit);

    try
    {
        $stmt->execute();
        $result = $stmt->get_result();

        $searches = [];
        while ($row = $result->fetch_assoc())
        {
            $searches[] = $row;
        }

        $logger->info("Listed " . count($searches) . " searches for user_id={$userId}");
        return
        [
            'status'   => 'success',
            'count'    => count($searches),
            'searches' => $searches,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Search list DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}


// ----- Main callback -----
$callback = function ($msg) use ($db, $channel, $logger)
{
    $data = json_decode($msg->body, true);
    $routingKey = $msg->getRoutingKey();

    echo "Received [$routingKey]: " . $msg->body . "\n";

    try
    {
        if ($routingKey === 'search.record')
        {
            $response = handleSearchRecord($db, $logger, $data);
        }
        else if ($routingKey === 'search.list')
        {
            $response = handleSearchList($db, $logger, $data);
        }
        else
        {
            $logger->warning("Unknown search routing key received: $routingKey");
            $response = ['status' => 'error', 'message' => 'unknown action'];
        }
    }
    catch (Exception $e)
    {
        $logger->error("Search consumer error: " . $e->getMessage());
        $response = ['status' => 'error', 'message' => 'internal error'];
    }

    // tad46: only reply when there's a reply_to - same guard as flights_consumer
    $props = $msg->get_properties();
    if (isset($props['reply_to']))
    {
        $replyMsg = new AMQPMessage(json_encode($response),
        [
            'correlation_id' => $props['correlation_id'] ?? '',
        ]);
        $channel->basic_publish($replyMsg, '', $props['reply_to']);
        echo "Replied: " . json_encode($response) . "\n\n";
    }

    $msg->ack();
};

$channel->basic_consume($queue, '', false, false, false, false, $callback);

// tad46: Keep listening forever, handling broker-initiated cancellations gracefully
while ($channel->is_consuming())
{
    try
    {
        $channel->wait();
    }
    catch (\PhpAmqpLib\Exception\AMQPBasicCancelException $e)
    {
        $logger->warning("Consumer cancelled by broker (queue probably recreated). Exiting cleanly.");
        break;
    }
}

$channel->close();
$connection->close();
$logger->close();
$db->close();
