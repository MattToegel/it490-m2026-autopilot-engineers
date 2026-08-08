<?php
// reports_consumer.php
// tad46: DB VM consumer that handles airport report requests
// tad46: Listens on db.reports queue, dispatches by routing key
// tad46: Handles create, list, edit, and delete operations

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

// tad46: Create a logger instance that identifies messages as coming from db-reports
$logger = new Logger('db-reports');

// tad46: Queue this consumer listens on
$queue = 'db.reports';

echo "DB VM reports consumer listening on '$queue'...\n";

// tad46: Create Report handler 
function handleCreateReport($db, $logger, $data)
{
    // tad46: Required fields
    if (empty($data['user_id']) || empty($data['category']) || empty($data['comment_text']))
    {
        $logger->warning("Report create missing required fields");
        return ['status' => 'error', 'message' => 'missing required fields'];
    }

    // tad46: Basic length validation on comment text
    if (strlen($data['comment_text']) > 2000)
    {
        $logger->warning("Report create rejected (comment too long)");
        return ['status' => 'error', 'message' => 'comment too long (max 2000 chars)'];
    }

    // tad46: Optional fields default appropriately
    $airportCode = $data['airport_code'] ?? 'EWR';
    $terminal    = $data['terminal']     ?? null;

    $stmt = $db->prepare
    (
        "INSERT INTO airport_reports 
         (user_id, airport_code, terminal, category, comment_text) 
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('issss',
        $data['user_id'],
        $airportCode,
        $terminal,
        $data['category'],
        $data['comment_text']
    );

    try
    {
        $stmt->execute();
        $reportId = $stmt->insert_id;
        $logger->info("Report created: user_id={$data['user_id']}, category={$data['category']}, report_id=$reportId");
        return
        [
            'status'    => 'success',
            'report_id' => $reportId,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Report create DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}

// tad46: List Reports handler 
// tad46: Returns recent reports from ALL users (users can see everyone's reports)
function handleListReports($db, $logger, $data)
{
    // tad46: default limit, capped for safety
    $limit = (int)($data['limit'] ?? 20);
    if ($limit < 1)   $limit = 1;
    if ($limit > 100) $limit = 100;
 
    // tad46: optional user_id filter - when present, adds AND r.user_id = ?
    $filterByUser = !empty($data['user_id']);
    $userId       = $filterByUser ? (int)$data['user_id'] : null;
 
    // tad46: JOIN users to show who submitted each report (username)
    // ns87: US-03 AC8 - when an admin flags a report, admin_consumer.php already records
    // ns87: the reason they typed into admin_activity_logs.notes. These two subqueries
    // ns87: read the most recent one back, so the report's author can see WHY it was
    // ns87: flagged. The reason is still stored once, by the admin side - this only reads.
    $sql = "SELECT r.report_id, r.user_id, u.username,
                   r.airport_code, r.terminal, r.category, r.comment_text,
                   r.report_status, r.created_at, r.updated_at,
                   (SELECT a.notes
                      FROM admin_activity_logs a
                     WHERE a.affected_report_id = r.report_id
                       AND a.action_type = 'create_notice'
                     ORDER BY a.created_at DESC
                     LIMIT 1) AS flag_reason,
                   (SELECT a.created_at
                      FROM admin_activity_logs a
                     WHERE a.affected_report_id = r.report_id
                       AND a.action_type = 'create_notice'
                     ORDER BY a.created_at DESC
                     LIMIT 1) AS flagged_at
            FROM airport_reports r
            JOIN users u ON r.user_id = u.user_id";

    // ns87: US-03 AC5 / AC6 - one query, two audiences, two different rules.
    if ($filterByUser)
    {
        // ns87: AC5 - the author's own history: every report they wrote, in EVERY status.
        // ns87: The 'active' filter used to apply here too, so a flagged report vanished
        // ns87: from its own author's history and AC8 had nothing left to show.
        $sql .= " WHERE r.user_id = ?";
    }
    else
    {
        // ns87: AC6 - the community feed: everyone else only sees reports an admin has
        // ns87: not flagged, so a flagged report stays hidden until it is reviewed.
        $sql .= " WHERE r.report_status = 'active'";
    }

    $sql .= " ORDER BY r.created_at DESC LIMIT ?";
 
    $stmt = $db->prepare($sql);
 
    if ($filterByUser)
    {
        $stmt->bind_param('ii', $userId, $limit);
    }
    else
    {
        $stmt->bind_param('i', $limit);
    }
 
    try
    {
        $stmt->execute();
        $result = $stmt->get_result();
 
        $reports = [];
        while ($row = $result->fetch_assoc())
        {
            $reports[] = $row;
        }
 
        $count = count($reports);
        $scope = $filterByUser ? "user_id=$userId" : "all users";
        $logger->info("Listed $count active reports ($scope)");
        return
        [
            'status'  => 'success',
            'count'   => $count,
            'reports' => $reports,
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("List reports DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}


// tad46: Edit Report handler 
// tad46: Users can only edit reports they created (ownership check enforced in WHERE)
function handleEditReport($db, $logger, $data)
{
    if (empty($data['user_id']) || empty($data['report_id']))
    {
        $logger->warning("Report edit missing required fields");
        return ['status' => 'error', 'message' => 'missing required fields'];
    }

    // Nothing to update
    if (empty($data['comment_text']) && empty($data['category']))
    {
        $logger->warning("Report edit had no fields to change");
        return ['status' => 'error', 'message' => 'no fields to update'];
    }

    // Length validation on comment
    if (!empty($data['comment_text']) && strlen($data['comment_text']) > 2000)
    {
        return ['status' => 'error', 'message' => 'comment too long (max 2000 chars)'];
    }

    // tad46: Build UPDATE dynamically based on which fields are provided
    $updates = [];
    $types = '';
    $values = [];

    if (!empty($data['comment_text']))
    {
        $updates[] = 'comment_text = ?';
        $types .= 's';
        $values[] = $data['comment_text'];
    }

    if (!empty($data['category']))
    {
        $updates[] = 'category = ?';
        $types .= 's';
        $values[] = $data['category'];
    }

    // tad46: Add report_id and user_id for the WHERE clause
    $types .= 'ii';
    $values[] = $data['report_id'];
    $values[] = $data['user_id'];

    // tad46: CRITICAL: AND user_id = ? enforces ownership - a user cannot edit
    // tad46: another user's report even if they know its ID
    $sql = "UPDATE airport_reports SET " . implode(', ', $updates) . 
           " WHERE report_id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$values);

    try
    {
        $stmt->execute();

        if ($stmt->affected_rows === 0)
        {
            // tad46: Either the report doesn't exist, or the user doesn't own it
            $logger->warning("Report edit failed (not found or not owned): user_id={$data['user_id']}, report_id={$data['report_id']}");
            return ['status' => 'error', 'message' => 'report not found'];
        }

        $logger->info("Report edited: user_id={$data['user_id']}, report_id={$data['report_id']}");
        return ['status' => 'success'];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Report edit DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}

// tad46: Delete Report handler 
// tad46: Users can only delete their OWN reports (ownership enforced in WHERE)
function handleDeleteReport($db, $logger, $data)
{
    if (empty($data['user_id']) || empty($data['report_id']))
    {
        $logger->warning("Report delete missing required fields");
        return ['status' => 'error', 'message' => 'missing required fields'];
    }

    // tad46: Same ownership pattern as unsave in flights_consumer
    $stmt = $db->prepare
    (
        "DELETE FROM airport_reports 
         WHERE report_id = ? AND user_id = ?"
    );
    $stmt->bind_param('ii', $data['report_id'], $data['user_id']);

    try
    {
        $stmt->execute();
        if ($stmt->affected_rows === 0)
        {
            $logger->warning("Report delete failed (not found or not owned): user_id={$data['user_id']}, report_id={$data['report_id']}");
            return ['status' => 'error', 'message' => 'report not found'];
        }

        $logger->info("Report deleted: user_id={$data['user_id']}, report_id={$data['report_id']}");
        return ['status' => 'success'];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Report delete DB error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'database error'];
    }
}

// tad46: Main callback 
$callback = function ($msg) use ($db, $channel, $logger)
{
    $data = json_decode($msg->body, true);
    $routingKey = $msg->getRoutingKey();

    echo "Received [$routingKey]: " . $msg->body . "\n";

    try
    {
        if ($routingKey === 'report.create')
        {
            $response = handleCreateReport($db, $logger, $data);
        }
        else if ($routingKey === 'report.list')
        {
            $response = handleListReports($db, $logger, $data);
        }
        else if ($routingKey === 'report.edit')
        {
            $response = handleEditReport($db, $logger, $data);
        }
        else if ($routingKey === 'report.delete')
        {
            $response = handleDeleteReport($db, $logger, $data);
        }
        else
        {
            $logger->warning("Unknown reports routing key received: $routingKey");
            $response = ['status' => 'error', 'message' => 'unknown action'];
        }
    }
    catch (Exception $e)
    {
        $logger->error("Reports consumer error: " . $e->getMessage());
        $response = ['status' => 'error', 'message' => 'internal error'];
    }

    // tad46: Build the response with the same correlation_id from the request
    $replyMsg = new AMQPMessage(json_encode($response),
    [
        'correlation_id' => $msg->get('correlation_id'),
    ]);

    // tad46: Publish the reply to the App VM's reply_to queue
    $channel->basic_publish($replyMsg, '', $msg->get('reply_to'));

    // tad46: Ack the original request so RabbitMQ removes it from db.reports
    $msg->ack();

    echo "Replied: " . json_encode($response) . "\n\n";
};

// tad46: Register the callback with the queue
$channel->basic_consume($queue, '', false, false, false, false, $callback);

// tad46: Keep listening forever
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

// tad46: Cleanup
$channel->close();
$connection->close();
$logger->close();
$db->close();