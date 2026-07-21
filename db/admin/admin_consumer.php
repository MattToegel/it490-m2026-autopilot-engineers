<?php
//cao39 US-04 Admin CM Consumer.
//cao39 DB VM Responsible for admin requests
//cao39 admin consumer will handle the user admin list, searches, role updates, report deletions, content reports, and alerts
// tad46: EDIT - added admin_activity_logs table writes for each admin action (US-04 audit trail)
// tad46: The Logger class publishes to the db.logs pipeline (logs table), which is separate
// tad46: from admin_activity_logs. This edit records admin actions in BOTH places.

//cao39 Load the Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

//cao39 Load the Logger class and publish log events
require_once __DIR__ . '/../logging/testlogger.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//cao39 Sourcing the environmental variables
$env = parse_ini_file(__DIR__ . '/../.env');

//cao39 Connect the admin consumer to MySQL
$db = new mysqli( "localhost", $env['MYSQL_USER'], $env['MYSQL_PASSWORD'], $env['MYSQL_DATABASE']);

if ($db->connect_error)
{
    die("The MySQL connection has failed: " . $db->connect_error);
}

//cao39 Connect the admin consumer to RabbitMQ
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
    echo "Please check the broker connection\n";
    exit(1);
}

$channel = $connection->channel();

$queue = "db.admin";

$logger = new Logger("db-admin");

echo "DB VM admin consumer listening on '$queue'...\n";


// tad46: NEW - writes one row into admin_activity_logs for every admin action.
// tad46: admin_user_id comes from the request payload; the App side must include
// tad46: the logged-in admin's user_id when publishing admin requests.
// tad46: affected_user_id / affected_report_id are nullable - pass null when not relevant.
function logAdminActivity($db, $logger, $adminUserId, $actionType, $affectedUserId, $affectedReportId, $notes)
{
    // tad46: don't let a missing admin_user_id kill the action itself - log a warning and skip
    if (empty($adminUserId))
    {
        $logger->warning("Admin activity not recorded (missing admin_user_id) for action: {$actionType}");
        return;
    }

    $stmt = $db->prepare
    (
        "INSERT INTO admin_activity_logs
         (admin_user_id, action_type, affected_user_id, affected_report_id, notes)
         VALUES (?, ?, ?, ?, ?)"
    );

    // tad46: bind_param needs variables - nulls pass through fine for the nullable columns
    $stmt->bind_param
    (
        "isiis",
        $adminUserId,
        $actionType,
        $affectedUserId,
        $affectedReportId,
        $notes
    );

    try
    {
        $stmt->execute();
    }
    catch (mysqli_sql_exception $e)
    {
        // tad46: an audit-write failure shouldn't break the admin action, log and continue
        $logger->error("Failed to write admin_activity_logs row: " . $e->getMessage());
    }
}


//cao39 US-04 AC1 - View a list of all users and their roles assigned
// tad46: EDIT - now accepts $data so the admin's user_id is available for the audit log
function listUsers($db, $logger, $data)
{

    $result = $db->query(
        "SELECT user_id, username, email, role
         FROM users"
    );



    $users = [];



    while ($row = $result->fetch_assoc())
    {
        $users[] = $row;
    }



    $logger->info(
        "Administrator viewed all of the users and their roles"
    );

    // tad46: - record the view action in admin_activity_logs
    logAdminActivity(
        $db,
        $logger,
        $data['admin_user_id'] ?? null,
        'list_users',
        null,
        null,
        'Viewed all users and roles'
    );

    return
    [
        "status" => "success",
        "users" => $users
    ];

}


//cao39 form can accept either a numeric User ID or a username.
//cao39 Case-insensitive exact match. Returns an error status if no user found.
function lookupUserByUsername($db, $data, $logger)
{
    if (empty($data['username']))
    {
        $logger->warning(
            "Administrator attempted a username lookup without providing username"
        );

        return
        [
            "status"  => "error",
            "message" => "Missing username"
        ];
    }

    $stmt = $db->prepare
    (
        "SELECT user_id, username
         FROM users
         WHERE LOWER(username) = LOWER(?)"
    );

    $stmt->bind_param
    (
        "s",
        $data['username']
    );

    try
    {
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0)
        {
            $logger->warning(
                "Administrator looked up username='{$data['username']}', but no matching user was found."
            );

            return
            [
                "status"  => "error",
                "message" => "No user found with that username"
            ];
        }

        $row = $result->fetch_assoc();

        $logger->info(
            "Administrator looked up username='{$data['username']}' -> user_id={$row['user_id']}"
        );

        return
        [
            "status"  => "success",
            "user_id" => (int)$row['user_id'],
            "username"=> $row['username']
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error(
            "Failed to look up username='{$data['username']}': " .
            $e->getMessage()
        );

        return
        [
            "status"  => "error",
            "message" => "Unable to look up username"
        ];
    }
}

//cao39 US-04 AC2 - Loads reports for moderation
// NEW - returns all airport_reports rows for the admin_reports.php
// moderation table. Mirrors listUsers()'s structure.
function listReports($db, $data, $logger)
{
    $result = $db->query(
        "SELECT report_id, user_id, airport_code, terminal,
                category, comment_text, report_status, created_at
         FROM airport_reports
         ORDER BY created_at DESC"
    );

    $reports = [];

    while ($row = $result->fetch_assoc())
    {
        $reports[] = $row;
    }

    $logger->info(
        "Administrator viewed all airport reports"
    );

    logAdminActivity(
        $db,
        $logger,
        $data['admin_user_id'] ?? null,
        'list_reports',
        null,
        null,
        'Viewed all airport reports'
    );

    return
    [
        "status"  => "success",
        "reports" => $reports
    ];
}

//cao39 US-04 AC2 - Flags a report with a warning/notice
//cao39 admin gets to mark a report as flagged and records the reason in

function createNotice($db, $data, $logger)
{
    if (empty($data['report_id']) || empty($data['reason']))
    {
        $logger->warning(
            "Administrator attempted to flag a report with missing report_id or reason"
        );

        return
        [
            "status"  => "error",
            "message" => "Missing report_id or reason"
        ];
    }

    $stmt = $db->prepare
    (
        "UPDATE airport_reports
         SET report_status = 'flagged'
         WHERE report_id = ?"
    );

    $stmt->bind_param
    (
        "i",
        $data['report_id']
    );

    try
    {
        $stmt->execute();

        if ($stmt->affected_rows === 0)
        {
            $logger->warning(
                "Administrator attempted to flag report_id={$data['report_id']}, but no matching report was found."
            );

            return
            [
                "status"  => "error",
                "message" => "Report not found"
            ];
        }

        $logger->info(
            "Administrator flagged report_id={$data['report_id']} with reason: {$data['reason']}"
        );

        logAdminActivity(
            $db,
            $logger,
            $data['admin_user_id'] ?? null,
            'create_notice',
            null,
            (int)$data['report_id'],
            $data['reason']
        );

        return
        [
            "status"  => "success",
            "message" => "Warning has been created"
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error(
            "Failed to flag report_id={$data['report_id']}: " .
            $e->getMessage()
        );

        return
        [
            "status"  => "error",
            "message" => "Unable to create warning"
        ];
    }
}

//cao39 US-04 AC2 - Admin removes the offensive reports
function deleteReport($db, $data, $logger)
{
    //cao39 Checks to see if a report id was entered
    if (empty($data['report_id']))
    {
        $logger->warning(
            "Administrator attempted to delete a report without providing report_id"
        );

        return
        [
            "status"  => "error",
            "message" => "Missing report_id"
        ];
    }

    $stmt = $db->prepare
    (
        "DELETE FROM airport_reports
         WHERE report_id = ?"
    );

    $stmt->bind_param
    (
        "i",
        $data['report_id']
    );

    try
    {
        $stmt->execute();


        //cao39 - Reviews to see if a report was deleted
        if ($stmt->affected_rows === 0)
        {
            $logger->warning(
                "Administrator attempted to delete report_id={$data['report_id']}, but no matching report was found."
            );

            return
            [
                "status"  => "error",
                "message" => "Report not found"
            ];
        }

        //cao39 - Logging successful delete report action
        $logger->info(
            "Administrator deleted report_id={$data['report_id']}"
        );

        // tad46: record the deletion in logs with the affected report id
         logAdminActivity(
            $db,
            $logger,
            $data['admin_user_id'] ?? null,
            'delete_report',
            null,
            null,
            "Deleted report_id={$data['report_id']}: " . ($data['notes'] ?? 'Removed report for community guideline violation')
        );

        return
        [
            "status"  => "success",
            "message" => "Report has been deleted"
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error(
            "Failed to delete report_id={$data['report_id']}: " .
            $e->getMessage()
        );

        return
        [
            "status"  => "error",
            "message" => "Unable to delete report"
        ];
    }
}

//cao39 US-04 AC3 - Update the user(s) roles
function updateRole($db, $data, $logger)
{
    //cao39 - Checks to see what if the required fields are filled
    if (empty($data['user_id']) || empty($data['role']))
    {
        $logger->warning(
            "Attempted to update a user's role with missing user_id or role information"
        );

        return
        [
            "status"  => "error",
            "message" => "Missing user_id or role"
        ];
    }

    $stmt = $db->prepare
    (
        "UPDATE users
         SET role = ?
         WHERE user_id = ?"
    );

    $stmt->bind_param
    (
        "si",
        $data['role'],
        $data['user_id']
    );

    try
    {
        $stmt->execute();


        //cao39 - Reviews to see if a user role was actually updated
        if ($stmt->affected_rows === 0)
        {
            $logger->warning(
                "Administrator attempted to update role for user_id={$data['user_id']}, but no changes were made."
            );
return
            [
                "status"  => "error",
                "message" => "User not found or role already assigned"
            ];
        }


        //cao39 - Log a successful role update action
        $logger->info(
            "Administrator updated role for user_id={$data['user_id']} to {$data['role']}"
        );

        // tad46: NEW - record the role change in admin_activity_logs with the affected user id
        logAdminActivity(
            $db,
            $logger,
            $data['admin_user_id'] ?? null,
            'update_role',
            (int)$data['user_id'],
            null,
            "Role changed to {$data['role']}"
        );

        return
        [
            "status"  => "success",
            "message" => "Role has been updated"
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error(
            "Failed to update role for user_id={$data['user_id']}: " .
            $e->getMessage()
        );

        return
        [
            "status"  => "error",
            "message" => "Unable to update role"
        ];
    }
}

//cao39 The RabbitMQ consumer
$callback = function($msg) use ($db, $channel, $logger)
{
    try
    {
        $routingKey = $msg->getRoutingKey();

        $data = json_decode
        (
            $msg->body,
            true
        );

        echo "Received [$routingKey]: "
            . json_encode($data)
            . "\n";


    switch($routingKey)
    {
        case "usr.adm.list":

            // tad46: EDIT - now passes $data through so listUsers can read admin_user_id
            $response = listUsers($db,$logger,$data);


            break;



        case "role.adm.update":

            $response=updateRole($db,$data,$logger);

            break;

	
	    case "content.adm.report":

            $response=listReports($db,$data,$logger);

            break;


	    case "create.adm.notice":

            $response=createNotice($db,$data,$logger);

            break;


        case "report.adm.delete":

            $response=deleteReport($db,$data,$logger);

            break;



        
        //cao39 -  role update form (admin_roles.php).
        case "role.adm.lookup":

            $response=lookupUserByUsername($db,$data,$logger);

            break;



        default:

                $logger->warning
                (
                    "Unknown admin routing key received: $routingKey"
                );

            $response=[
                "status"=>"error",
                "message"=>"Unknown admin request"
            ];

    }

//cao39 sending back the responses to the APP VM
    $props = $msg->get_properties();



    if(isset($props['reply_to']))
    {


        $reply = new AMQPMessage
        (
            json_encode($response),
            [
                "correlation_id" =>
                $props['correlation_id'] ?? ''
            ]
        );



        $channel->basic_publish(
            $reply,
            "",
            $props['reply_to']
        );



        echo "Replied: "
        . json_encode($response)
        . "\n\n";


    }



    $msg->ack();

    }
    catch (Exception $e)
    {
        $logger->error(
            "Admin consumer error: " . $e->getMessage()
        );

        $msg->nack(false, false);
    }

};

$channel->basic_consume($queue,"",false,false,false,false,$callback);



while ($channel->is_consuming())
{
    try
    {
        $channel->wait();
    }
    catch (\PhpAmqpLib\Exception\AMQPBasicCancelException $e)
    {
        $logger->warning("Consumer cancelled by broker. Quiting.");
        break;
    }
}


$channel->close();
$connection->close();
$logger->close();
$db->close();
?>
