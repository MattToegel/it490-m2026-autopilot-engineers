<?php
// alerts_consumer.php
// tad46: DB VM consumer that handles flight alert requests
// tad46: Listens on db.alerts queue, dispatches by routing key
// tad46: Handles alert.create, alert.list, and alert.mark_read
// tad46:
// tad46: US-05 AC4 lives in handleAlertCreate: an alert is only inserted if
// tad46: the user still has this flight in saved_flights. When a user unsaves
// tad46: their flight, subsequent alert.create calls for that flight return
// tad46: "suppressed" instead of creating a row.

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../logging/testlogger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$env = parse_ini_file(__DIR__ . '/../../../.env');

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

$logger = new Logger('db-alerts');

$queue = 'db.alerts';

echo "DB VM alerts consumer listening on '$queue'...\n";


// tad46: Create Alert handler
// tad46: US-05 AC4: only creates the alert row if the user's saved_flights entry
// tad46: is still active (removed_at IS NULL). If they unsaved the flight, we
// tad46: return "suppressed" and no row is written - that's the AC4 evidence.
function handleAlertCreate($db, $logger, $data)
{
    if (empty($data['user_id']) || empty($data['flight_number'])
        || empty($data['alert_type']) || empty($data['alert_message']))
    {
        $logger->warning("Alert create missing required fields");
        return ['status' => 'error', 'message' => 'missing required fields'];
    }

    $userId       = (int)$data['user_id'];
    $flightNumber = $data['flight_number'];
    $alertType    = $data['alert_type'];
    $alertMessage = $data['alert_message'];

    // tad46: Check if the user is still tracking this flight.
    // tad46: The removed_at filter respects soft-deletes (US-05 AC5 history).
    $lookup = $db->prepare
    (
        "SELECT saved_flight_id
         FROM saved_flights
         WHERE user_id = ? AND flight_number = ? AND removed_at IS NULL"
    );
    $lookup->bind_param('is', $userId, $flightNumber);
    $lookup->execute();
    $row = $lookup->get_result()->fetch_assoc();

    if (!$row)
    {
        $logger->info("Alert suppressed - user_id={$userId} is not tracking {$flightNumber}");
        return
        [
            'status'  => 'suppressed',
            'message' => 'user is not tracking this flight',
        ];
    }

    $savedFlightId = (int)$row['saved_flight_id'];

    $stmt = $db->prepare
    (
        "INSERT INTO flight_alerts
         (user_id, saved_flight_id, flight_number, alert_type, alert_message)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iisss',
        $userId,
        $savedFlightId,
        $flightNumber,
        $alertType,
        $alertMessage
    );

    try
    {
        $stmt->execute();
        $alertId = $stmt->insert_id;
        $logger->info("Alert created: alert_id={$alertId}, user_id={$userId}, flight={$flightNumber}, type={$alertType}");
        return
        [
            'status'   => 'success',
            'alert_id' => $alertId,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Alert create DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}


// tad46: List Alerts handler
// tad46: If unread_only is set in the payload, filters to is_read = 0.
function handleAlertList($db, $logger, $data)
{
    if (empty($data['user_id']))
    {
        return ['status' => 'error', 'message' => 'missing user_id'];
    }

    $userId     = (int)$data['user_id'];
    $unreadOnly = !empty($data['unread_only']);
    $limit      = (int)($data['limit'] ?? 20);
    if ($limit < 1)   $limit = 1;
    if ($limit > 100) $limit = 100;

    $sql = "SELECT alert_id, user_id, saved_flight_id, flight_number,
                   alert_type, alert_message, is_read, created_at
            FROM flight_alerts
            WHERE user_id = ?";

    if ($unreadOnly)
    {
        $sql .= " AND is_read = 0";
    }

    $sql .= " ORDER BY created_at DESC LIMIT ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('ii', $userId, $limit);

    try
    {
        $stmt->execute();
        $result = $stmt->get_result();

        $alerts = [];
        while ($row = $result->fetch_assoc())
        {
            $alerts[] = $row;
        }

        $logger->info("Listed " . count($alerts) . " alerts for user_id={$userId}"
            . ($unreadOnly ? " (unread only)" : ""));

        return
        [
            'status' => 'success',
            'count'  => count($alerts),
            'alerts' => $alerts,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Alert list DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}


// tad46: Mark Alert Read handler
// tad46: Users can only mark their own alerts (ownership guard in WHERE).
function handleAlertMarkRead($db, $logger, $data)
{
    if (empty($data['user_id']) || empty($data['alert_id']))
    {
        return ['status' => 'error', 'message' => 'missing required fields'];
    }
 
    $userId  = (int)$data['user_id'];
    $alertId = (int)$data['alert_id'];
 
    // tad46: delete flag -> hard delete; otherwise mark read (kept for history)
    if (!empty($data['delete']))
    {
        $stmt = $db->prepare
        (
            "DELETE FROM flight_alerts
             WHERE alert_id = ? AND user_id = ?"
        );
    }
    else
    {
        $stmt = $db->prepare
        (
            "UPDATE flight_alerts
             SET is_read = 1
             WHERE alert_id = ? AND user_id = ?"
        );
    }
    $stmt->bind_param('ii', $alertId, $userId);
 
    try
    {
        $stmt->execute();
        if ($stmt->affected_rows === 0)
        {
            $logger->warning("Alert dismiss failed (not found or not owned): user_id={$userId}, alert_id={$alertId}");
            return ['status' => 'error', 'message' => 'alert not found'];
        }
        $action = !empty($data['delete']) ? 'deleted' : 'marked read';
        $logger->info("Alert {$action}: alert_id={$alertId}, user_id={$userId}");
        return ['status' => 'success'];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Alert dismiss DB error: " . $e->getMessage());
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
        if ($routingKey === 'alert.create')
        {
            $response = handleAlertCreate($db, $logger, $data);
        }
        else if ($routingKey === 'alert.list')
        {
            $response = handleAlertList($db, $logger, $data);
        }
        else if ($routingKey === 'alert.mark_read')
        {
            $response = handleAlertMarkRead($db, $logger, $data);
        }
        else
        {
            $logger->warning("Unknown alerts routing key received: $routingKey");
            $response = ['status' => 'error', 'message' => 'unknown action'];
        }
    }
    catch (Exception $e)
    {
        $logger->error("Alerts consumer error: " . $e->getMessage());
        $response = ['status' => 'error', 'message' => 'internal error'];
    }

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