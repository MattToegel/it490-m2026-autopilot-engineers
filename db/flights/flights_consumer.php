<?php
// flights_consumer.php
// tad46: DB VM consumer that handles saved flight requests
// tad46: Listens on db.flights queue, dispatches by routing key
// tad46: Handles save, unsave, list, and cache operations

// tad46: Load Composer autoloader (vendor lives two folders up at db/vendor/)
require_once __DIR__ . '/../vendor/autoload.php';

// tad46: Load the Logger class from the logging folder so we can publish log events
require_once __DIR__ . '/../logging/testlogger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// tad46: Load credentials from .env at the db/ root
$env = parse_ini_file(__DIR__ . '/../.env');

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

// tad46: Create a logger instance that identifies messages as coming from db-flights
$logger = new Logger('db-flights');

// tad46: Queue this consumer listens on
$queue = 'db.flights';

echo "DB VM flights consumer listening on '$queue'...\n";


// tad46: Save Flight handler
// tad46: Adds a new row to saved_flights for the user
function handleSaveFlight($db, $logger, $data)
{
    if (empty($data['user_id']) || empty($data['flight_number']))
    {
        $logger->warning("Save flight attempt missing required fields");
        return ['status' => 'error', 'message' => 'missing required fields'];
    }
 
    $airline           = $data['airline']           ?? null;
    $departureAirport  = $data['departure_airport'] ?? null;
    $arrivalAirport    = $data['arrival_airport']   ?? null;
 
    $stmt = $db->prepare
    (
        "INSERT INTO saved_flights
         (user_id, flight_number, airline, departure_airport, arrival_airport)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issss',
        $data['user_id'],
        $data['flight_number'],
        $airline,
        $departureAirport,
        $arrivalAirport
    );
 
    try
    {
        $stmt->execute();
        $savedFlightId = $stmt->insert_id;
        $logger->info("Flight saved: user_id={$data['user_id']}, flight={$data['flight_number']}, saved_flight_id=$savedFlightId");
 
        // tad46: NEW - activity notification; appears in the dashboard Notifications panel
        // tad46: wrapped in its own try/catch so a notification failure never fails the save
        try
        {
            $notif = $db->prepare
            (
                "INSERT INTO flight_alerts
                 (user_id, saved_flight_id, flight_number, alert_type, alert_message)
                 VALUES (?, ?, ?, 'saved', ?)"
            );
            $notifUserId = (int)$data['user_id'];
            $notifMsg    = "You are now tracking {$data['flight_number']}.";
            $notif->bind_param('iiss', $notifUserId, $savedFlightId, $data['flight_number'], $notifMsg);
            $notif->execute();
        }
        catch (mysqli_sql_exception $e)
        {
            $logger->error("Save notification insert failed: " . $e->getMessage());
        }
 
        return
        [
            'status'          => 'success',
            'saved_flight_id' => $savedFlightId,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        if ($e->getCode() === 1062)   // duplicate key
        {
            // tad46: EDIT - fixed undefined variables ($userId/$flightNumber were never set here)
            $logger->warning("Duplicate save attempt: user_id={$data['user_id']}, flight={$data['flight_number']}");
            return ['status' => 'error', 'message' => 'flight already saved'];
        }
        $logger->error("Save flight DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}



// tad46: Unsave Flight handler
// tad46: Removes a saved flight - user can only remove their OWN saved flights
function handleUnsaveFlight($db, $logger, $data)
{
    if (empty($data['user_id']) || empty($data['saved_flight_id']))
    {
        $logger->warning("Unsave flight attempt missing required fields");
        return ['status' => 'error', 'message' => 'missing required fields'];
    }
 
    // tad46: NEW - fetch the flight number BEFORE the update so the
    // tad46: notification can name the flight being removed
    $lookup = $db->prepare
    (
        "SELECT flight_number FROM saved_flights
         WHERE saved_flight_id = ? AND user_id = ?"
    );
    $lookup->bind_param('ii', $data['saved_flight_id'], $data['user_id']);
    $lookup->execute();
    $flightRow    = $lookup->get_result()->fetch_assoc();
    $flightNumber = $flightRow['flight_number'] ?? null;
 
    $stmt = $db->prepare(
    "UPDATE saved_flights
     SET removed_at = NOW()
     WHERE saved_flight_id = ? AND user_id = ? AND removed_at IS NULL"
);
    $stmt->bind_param('ii', $data['saved_flight_id'], $data['user_id']);
 
    try
    {
        $stmt->execute();
        if ($stmt->affected_rows === 0)
        {
            $logger->warning("Unsave flight failed (not found or not owned): user_id={$data['user_id']}, saved_flight_id={$data['saved_flight_id']}");
            return ['status' => 'error', 'message' => 'flight not found'];
        }
 
        $logger->info("Flight unsaved: user_id={$data['user_id']}, saved_flight_id={$data['saved_flight_id']}");
 
        // tad46: NEW - activity notification; also confirms alerts stopped (AC4 tie-in)
        // tad46: saved_flight_id intentionally omitted (NULL) since the row is now soft-deleted
        if ($flightNumber)
        {
            try
            {
                $notif = $db->prepare
                (
                    "INSERT INTO flight_alerts
                     (user_id, flight_number, alert_type, alert_message)
                     VALUES (?, ?, 'removed', ?)"
                );
                $notifUserId = (int)$data['user_id'];
                $notifMsg    = "{$flightNumber} removed from your watchlist. Flight alerts for it have stopped.";
                $notif->bind_param('iss', $notifUserId, $flightNumber, $notifMsg);
                $notif->execute();
            }
            catch (mysqli_sql_exception $e)
            {
                $logger->error("Unsave notification insert failed: " . $e->getMessage());
            }
        }
 
        return ['status' => 'success'];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Unsave flight DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}



// tad46: List Saved Flights handler
// tad46: Returns all saved flights belonging to a specific user
function handleListSavedFlights($db, $logger, $data)
{
    if (empty($data['user_id']))
    {
        $logger->warning("List saved flights missing user_id");
        return ['status' => 'error', 'message' => 'missing user_id'];
    }

    $stmt = $db->prepare
    (
        "SELECT sf.saved_flight_id, sf.flight_number,
                COALESCE(c.`airline`, sf.airline) AS airline,
                COALESCE(c.departure_airport, sf.departure_airport) AS departure_airport,
                COALESCE(c.arrival_airport, sf.arrival_airport) AS arrival_airport,
                sf.saved_at, sf.removed_at,
                c.`status`, c.`terminal`, c.`gate`,
                c.scheduled_departure, c.estimated_departure,
                c.scheduled_arrival, c.estimated_arrival,
                c.delay_minutes, c.cancellation_status,
                c.last_updated AS cache_updated
         FROM saved_flights sf
         LEFT JOIN cached_flight_data c
            ON REPLACE(c.flight_number, ' ', '') = REPLACE(sf.flight_number, ' ', '')
         WHERE sf.user_id = ?
         ORDER BY sf.saved_at DESC"
    );
    $stmt->bind_param('i', $data['user_id']);

    try
    {
        $stmt->execute();
        $result = $stmt->get_result();

        $flights = [];
        while ($row = $result->fetch_assoc())
        {
            $flights[] = $row;
        }

        $count = count($flights);
        $logger->info("Listed $count saved flights for user_id={$data['user_id']}");
        return
        [
            'status'  => 'success',
            'count'   => $count,
            'flights' => $flights,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("List saved flights DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}


// tad46: Cache Flight handler
// tad46: Upserts a flight into cached_flight_data from the API worker.
// tad46: Fire-and-forget - no reply is sent for this routing key.
function handleCacheFlight($db, $logger, $data)
{
    if (empty($data['flight_number']))
    {
        return ['status' => 'error', 'message' => 'missing flight_number'];
    }
 
    // tad46: bind_param needs real variables. Pull each field from the payload into a local.
    $flightNumber        = $data['flight_number'];
    $airline             = $data['airline']              ?? null;
    $status              = $data['status']               ?? null;
 
    $departureAirport    = $data['departure_airport']    ?? null;
    $arrivalAirport      = $data['arrival_airport']      ?? null;
 
    $terminal            = $data['terminal']             ?? null;
    $gate                = $data['gate']                 ?? null;
    $arrivalTerminal     = $data['arrival_terminal']     ?? null;
    $arrivalGate         = $data['arrival_gate']         ?? null;
 
    $scheduledDeparture  = $data['scheduled_departure']  ?? null;
    $estimatedDeparture  = $data['estimated_departure']  ?? null;
    $actualDeparture     = $data['actual_departure']     ?? null;
    $scheduledArrival    = $data['scheduled_arrival']    ?? null;
    $estimatedArrival    = $data['estimated_arrival']    ?? null;
    $actualArrival       = $data['actual_arrival']       ?? null;
 
    $aircraftModel       = $data['aircraft_model']       ?? null;
    $aircraftRegistration = $data['aircraft_registration'] ?? null;
 
    // tad46: derive delay_minutes from scheduled vs estimated departure (falls back to 0)
    $delayMinutes = 0;
    if ($scheduledDeparture && $estimatedDeparture)
    {
        $schedTs = strtotime($scheduledDeparture);
        $estTs   = strtotime($estimatedDeparture);
        if ($schedTs !== false && $estTs !== false && $estTs > $schedTs)
        {
            $delayMinutes = (int)round(($estTs - $schedTs) / 60);
        }
    }
 
    // tad46: derive cancellation_status from the status text
    $cancellationStatus = 0;
    if ($status && stripos($status, 'cancel') !== false)
    {
        $cancellationStatus = 1;
    }
 
    // tad46: normalize AeroDataBox datetime format (2026-07-17T15:30:00.000Z)
    // tad46: to MySQL DATETIME (2026-07-17 15:30:00). MySQL tolerates ISO but this is cleaner.
    $toMysqlDatetime = function ($iso)
    {
        if (!$iso) return null;
        $ts = strtotime($iso);
        return $ts === false ? null : date('Y-m-d H:i:s', $ts);
    };
    $scheduledDeparture  = $toMysqlDatetime($scheduledDeparture);
    $estimatedDeparture  = $toMysqlDatetime($estimatedDeparture);
    $actualDeparture     = $toMysqlDatetime($actualDeparture);
    $scheduledArrival    = $toMysqlDatetime($scheduledArrival);
    $estimatedArrival    = $toMysqlDatetime($estimatedArrival);
    $actualArrival       = $toMysqlDatetime($actualArrival);
 
    // tad46: INSERT with ON DUPLICATE KEY UPDATE:
    //   - new flight -> insert row
    //   - existing flight -> overwrite all fields with new data
    // tad46: works because of the UNIQUE constraint on flight_number in the schema.
    // tad46: reserved words (status, terminal, gate, airline) are backticked for safety.
    $stmt = $db->prepare
    (
        "INSERT INTO cached_flight_data
         (flight_number, `airline`, `status`,
          departure_airport, arrival_airport,
          `terminal`, `gate`, arrival_terminal, arrival_gate,
          scheduled_departure, estimated_departure, actual_departure,
          scheduled_arrival, estimated_arrival, actual_arrival,
          aircraft_model, aircraft_registration,
          delay_minutes, cancellation_status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            `airline` = VALUES(`airline`),
            `status` = VALUES(`status`),
            departure_airport = VALUES(departure_airport),
            arrival_airport = VALUES(arrival_airport),
            `terminal` = VALUES(`terminal`),
            `gate` = VALUES(`gate`),
            arrival_terminal = VALUES(arrival_terminal),
            arrival_gate = VALUES(arrival_gate),
            scheduled_departure = VALUES(scheduled_departure),
            estimated_departure = VALUES(estimated_departure),
            actual_departure = VALUES(actual_departure),
            scheduled_arrival = VALUES(scheduled_arrival),
            estimated_arrival = VALUES(estimated_arrival),
            actual_arrival = VALUES(actual_arrival),
            aircraft_model = VALUES(aircraft_model),
            aircraft_registration = VALUES(aircraft_registration),
            delay_minutes = VALUES(delay_minutes),
            cancellation_status = VALUES(cancellation_status),
            last_updated = CURRENT_TIMESTAMP"
    );
 
    // tad46: 19 params, all strings except delay_minutes (i) and cancellation_status (i)
    $stmt->bind_param
    (
        'sssssssssssssssssii',
        $flightNumber,
        $airline,
        $status,
        $departureAirport,
        $arrivalAirport,
        $terminal,
        $gate,
        $arrivalTerminal,
        $arrivalGate,
        $scheduledDeparture,
        $estimatedDeparture,
        $actualDeparture,
        $scheduledArrival,
        $estimatedArrival,
        $actualArrival,
        $aircraftModel,
        $aircraftRegistration,
        $delayMinutes,
        $cancellationStatus
    );
 
    try
    {
        $stmt->execute();
        $logger->info("Cached flight data updated: {$flightNumber}");
        return ['status' => 'success'];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Cache flight failed: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}


function handleCacheLookup($db, $logger, $data)
{
    if (empty($data['flight_number']))
    {
        return ['status' => 'error', 'message' => 'missing flight_number'];
    }

    $flightNumber = $data['flight_number'];

    $stmt = $db->prepare(
    "SELECT flight_number, `airline`, `status`,
            departure_airport, arrival_airport,
            `terminal`, `gate`, arrival_terminal, arrival_gate,
            scheduled_departure, estimated_departure, actual_departure,
            scheduled_arrival, estimated_arrival, actual_arrival,
            aircraft_model, aircraft_registration,
            delay_minutes, cancellation_status,
            last_updated AS cached_at,
            TIMESTAMPDIFF(SECOND, last_updated, NOW()) AS age_seconds
     FROM cached_flight_data
     WHERE flight_number = ?"
);
    $stmt->bind_param('s', $flightNumber);
    try
    {
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Cache lookup DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }

    if (!$row)
    {
        $logger->info("Cache miss for {$flightNumber}");
        return ['found' => false, 'flight' => null];
    }

    $age = $row['age_seconds'];
    unset($row['age_seconds']);

    $logger->info("Cache hit for {$flightNumber} (age {$age}s)");
    return
    [
        'found'  => true,
        'flight' => $row,
    ];
}


// ----- Main callback -----
$callback = function ($msg) use ($db, $channel, $logger)
{
    $data = json_decode($msg->body, true);
    $routingKey = $msg->getRoutingKey();

    echo "Received [$routingKey]: " . $msg->body . "\n";

    try
    {
        // Dispatch to the correct handler based on routing key
        if ($routingKey === 'flight.save')
        {
            $response = handleSaveFlight($db, $logger, $data);
        }
        else if ($routingKey === 'flight.unsave')
        {
            $response = handleUnsaveFlight($db, $logger, $data);
        }
        else if ($routingKey === 'flight.list')
        {
            $response = handleListSavedFlights($db, $logger, $data);
        }
        else if ($routingKey === 'flight.cache')
        {
            $response = handleCacheFlight($db, $logger, $data);
        }
        else if ($routingKey === 'flight.cache_lookup')
        {
            $response = handleCacheLookup($db, $logger, $data);
        }
        else
        {
            $logger->warning("Unknown flights routing key received: $routingKey");
            $response = ['status' => 'error', 'message' => 'unknown action'];
        }
    }
    catch (Exception $e)
    {
        // Safety net: catch anything unexpected so the consumer doesn't crash
        $logger->error("Flights consumer error: " . $e->getMessage());
        $response = ['status' => 'error', 'message' => 'internal error'];
    }

    // tad46: Only reply when there's a reply_to address.
    // tad46: flight.cache is fire-and-forget from the API worker and has no
    // tad46: reply_to - reading $msg->get('reply_to') on that message would
    // tad46: throw and kill the consumer (fixes the AMQPMessage->get() crash).
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

    // Ack the original request so RabbitMQ removes it from db.flights
    $msg->ack();
};

// Register the callback with the queue
$channel->basic_consume($queue, '', false, false, false, false, $callback);

// Keep listening forever
while ($channel->is_consuming())
{
    try
    {
        $channel->wait();
    }
    catch (\PhpAmqpLib\Exception\AMQPBasicCancelException $e)
    {
        $logger->warning("Consumer cancelled by broker (queue probably recreated). Exiting.");
        break;
    }
}

// Cleanup
$channel->close();
$connection->close();
$logger->close();
$db->close();