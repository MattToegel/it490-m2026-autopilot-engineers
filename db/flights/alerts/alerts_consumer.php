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

$env = parse_ini_file(__DIR__ . '/../../.env');

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
 
    // tad46: NEW - TTL purge, runs opportunistically on every list call.
    // tad46: Deletes notifications (read OR unread) older than 3 days so
    // tad46: dismissed/old alerts don't accumulate forever. No cron needed.
    try
    {
       $purge = $db->prepare
        (
            "DELETE FROM flight_alerts
            WHERE user_id = ?
            AND (
                (is_read = 1 AND created_at < (NOW() - INTERVAL 4 HOUR))
                OR (is_read = 0 AND created_at < (NOW() - INTERVAL 1 DAY))
            )"
        );
        $purge->bind_param('i', $userId);
        $purge->execute();
        if ($purge->affected_rows > 0)
        {
            $logger->info("Purged {$purge->affected_rows} expired alert(s) for user_id={$userId}");
        }
    }
    catch (mysqli_sql_exception $e)
    {
        // tad46: purge failure should never block the list from returning
        $logger->error("Alert TTL purge failed: " . $e->getMessage());
    }
 
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

// tad46: computes an actual minutes-delayed figure from scheduled vs the
// NEW estimated time, since $changes only records THAT the estimated time
// changed, not by how much. $data['flight'] holds the full new-flight
// snapshot (see FlightPublisher::buildStatusChange), which includes the
// scheduled time needed to diff against.
function calculateDelayMinutes(?string $scheduled, ?string $newEstimated): int
{
    if (!$scheduled || !$newEstimated)
    {
        return 0;
    }

    $schedTs = strtotime($scheduled);
    $estTs   = strtotime($newEstimated);

    if ($schedTs === false || $estTs === false || $estTs <= $schedTs)
    {
        return 0;
    }

    return (int)round(($estTs - $schedTs) / 60);
}

// tad46: Status Change Notify handler
// tad46: Routing key: flight.status_change (published by the API worker's
// tad46: cacheAndDiffFlight() when FlightTransformer::hasChanged() is true)
function handleStatusChangeNotify($db, $logger, $data)
{
    if (empty($data['flight_number']))
    {
        $logger->warning("Status change notify missing flight_number");
        return ['status' => 'error', 'message' => 'missing flight_number'];
    }

    $flightNumber = $data['flight_number'];
    $changes      = $data['changes'] ?? [];
    $flightSnap   = $data['flight'] ?? [];

    $alertType = 'status_change';
    $parts     = [];

    $newStatus   = $changes['status']['new'] ?? null;
    $isCancelled = $newStatus && stripos($newStatus, 'cancel') !== false;

    if ($isCancelled)
    {
        // tad46: cancellation takes over completely - gate/delay/status
        // details are irrelevant once a flight is cancelled, so nothing
        // else gets evaluated or appended to the message.
        $alertType    = 'cancellation';
        $alertMessage = "{$flightNumber}: Flight has been cancelled.";
    }
    else
    {
        if (isset($changes['gate']))
        {
            $alertType = 'gate_change';
            $newGate   = $changes['gate']['new'] ?? null;
            $parts[]   = $newGate ? "Gate changed to {$newGate}." : "Gate information changed.";
        }

        if (isset($changes['arrival_gate']))
        {
            $alertType      = $alertType === 'status_change' ? 'gate_change' : $alertType;
            $newArrivalGate = $changes['arrival_gate']['new'] ?? null;
            $parts[]        = $newArrivalGate ? "Arrival gate changed to {$newArrivalGate}." : "Arrival gate changed.";
        }

        if (isset($changes['estimated_departure']))
        {
            $scheduledDeparture = $flightSnap['scheduled_departure'] ?? null;
            $newEstimated       = $changes['estimated_departure']['new'] ?? null;
            $delayMinutes       = calculateDelayMinutes($scheduledDeparture, $newEstimated);

            if ($delayMinutes > 0)
            {
                $alertType = 'delay';
                $parts[]   = "Now delayed by {$delayMinutes} minutes.";
            }
            else
            {
                $alertType = $alertType === 'status_change' ? 'delay' : $alertType;
                $parts[]   = "Estimated departure updated.";
            }
        }

        if (isset($changes['estimated_arrival']))
        {
            $scheduledArrival = $flightSnap['scheduled_arrival'] ?? null;
            $newEstimatedArr  = $changes['estimated_arrival']['new'] ?? null;
            $arrivalDelay     = calculateDelayMinutes($scheduledArrival, $newEstimatedArr);

            if ($arrivalDelay > 0)
            {
                $alertType = 'delay';
                $parts[]   = "Now arriving {$arrivalDelay} minutes late.";
            }
            else
            {
                $alertType = $alertType === 'status_change' ? 'delay' : $alertType;
                $parts[]   = "Estimated arrival updated.";
            }
        }

        if (isset($changes['status']) && empty($parts))
        {
            $parts[] = "Status updated to {$newStatus}.";
        }

        $alertMessage = !empty($parts)
            ? "{$flightNumber}: " . implode(' ', $parts)
            : "{$flightNumber} has an updated status.";
    }

    // tad46: OPTIONAL - duplicate/near-duplicate suppression, commented out.
    // Uncomment to skip firing a new alert if this exact flight+alert_type
    // already fired within the last N minutes, so a flight with a shifting
    // estimate doesn't spam three near-identical "delayed" alerts in a row.
    // Left disabled for now since firing on every real detected change is
    // more accurate to the underlying data; enable if alert volume becomes a UX concern.
    /*
    $cooldownMinutes = 15;

    $recentCheck = $db->prepare
    (
        "SELECT alert_id FROM flight_alerts
         WHERE flight_number = ? AND alert_type = ?
           AND created_at > (NOW() - INTERVAL ? MINUTE)
         LIMIT 1"
    );
    $recentCheck->bind_param('ssi', $flightNumber, $alertType, $cooldownMinutes);
    $recentCheck->execute();
    $recent = $recentCheck->get_result()->fetch_assoc();

    if ($recent)
    {
        $logger->info("Suppressed duplicate {$alertType} alert for {$flightNumber} - one already fired within {$cooldownMinutes} min");
        return ['status' => 'success', 'notified' => 0, 'suppressed' => true];
    }
    */

    // tad46: fan-out - find every user currently tracking this flight
    // tad46: (removed_at IS NULL respects soft-delete / AC4 semantics,
    // tad46: same rule handleAlertCreate already uses for single-user alerts)
    $lookup = $db->prepare
    (
        "SELECT user_id, saved_flight_id
        FROM saved_flights
        WHERE REPLACE(UPPER(flight_number), ' ', '') = REPLACE(UPPER(?), ' ', '')
        AND removed_at IS NULL"
    );
    $lookup->bind_param('s', $flightNumber);
    $lookup->execute();
    $trackers = $lookup->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($trackers))
    {
        $logger->info("Status change for {$flightNumber} - no active trackers, nothing to notify");
        return ['status' => 'success', 'notified' => 0];
    }

    $insert = $db->prepare
    (
        "INSERT INTO flight_alerts
         (user_id, saved_flight_id, flight_number, alert_type, alert_message)
         VALUES (?, ?, ?, ?, ?)"
    );

    $notifiedCount = 0;
    foreach ($trackers as $tracker)
    {
        try
        {
            $userId        = (int)$tracker['user_id'];
            $savedFlightId = (int)$tracker['saved_flight_id'];
            $insert->bind_param('iisss', $userId, $savedFlightId, $flightNumber, $alertType, $alertMessage);
            $insert->execute();
            $notifiedCount++;
        }
        catch (mysqli_sql_exception $e)
        {
            $logger->error("Status change notify insert failed for user_id={$tracker['user_id']}: " . $e->getMessage());
        }
    }

    $logger->info("Status change for {$flightNumber} - notified {$notifiedCount} tracker(s), type={$alertType}");
    return ['status' => 'success', 'notified' => $notifiedCount];
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
        else if ($routingKey === 'flight.status_change')    
        {
            $response = handleStatusChangeNotify($db, $logger, $data);
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
        echo "Replied: " . json_encode($response) . "\n";
    }

    echo "\n";

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