<?php
//cao39 US-04 Admin CM Consumer.
//cao39 DB VM Responsible for admin requests
//cao39 admin consumer will handle the user admin list, searches, role updates, report deletions, content reports, and alerts

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


function logAdminActivity($db, $logger, $adminUserId, $actionType, $affectedUserId, $affectedReportId, $notes)
{
    // tad46 and cao39: don't let a missing admin_user_id kill the action itself - log a warning and skip
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

    // tad46 and cao39: - record the view action in admin_activity_logs. tad46 addded $data 
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


//cao39 US-04 AC6 - Search users by username or email
// Powers the search box in admin_users.php. If the search term is
// all digits, it's treated as an exact user_id match (plus a
// partial username/email match too, in case a username happens to
// be numeric). Otherwise it's treated as a partial match against
// username or email.
function searchUsers($db, $data, $logger)
{
    // Same validation pattern as every other handler - stop
    // immediately if the required field is missing
    if (empty($data['search']))
    {
        $logger->warning(
            "Administrator attempted a user search without providing a search term"
        );

        return
        [
            "status"  => "error",
            "message" => "Missing search term"
        ];
    }

    // Grab the raw search text and build a wildcard version of it
    // for LIKE matching (e.g. "jo" becomes "%jo%" to match "john",
    // "banjo", "jocelyn", etc.)
    $searchTerm = $data['search'];
    $likeTerm   = "%{$searchTerm}%";

    // cao39
    // If the search term is entirely digits, it could be a user ID -
    // build a query that checks user_id as an exact match AND still
    // checks username/email as partial matches, so a numeric search
    // term isn't limited to ID lookups only
    if (ctype_digit($searchTerm))
    {
        $stmt = $db->prepare
        (
            "SELECT user_id, username, email, role
             FROM users
             WHERE user_id = ?
                OR username LIKE ?
                OR email LIKE ?"
        );

        // Cast to int for the exact user_id match
        $userId = (int)$searchTerm;

        // "iss" = one integer param, two string params, matching
        // the order of the placeholders above
        $stmt->bind_param("iss", $userId, $likeTerm, $likeTerm);
    }
    else
    {
        // Not numeric - only check username/email as partial matches
        $stmt = $db->prepare
        (
            "SELECT user_id, username, email, role
             FROM users
             WHERE username LIKE ?
                OR email LIKE ?"
        );

        $stmt->bind_param("ss", $likeTerm, $likeTerm);
    }

    try
    {
        $stmt->execute();

        $result = $stmt->get_result();

        // Same result-collection loop pattern used everywhere else
        // in this file
        $users = [];

        while ($row = $result->fetch_assoc())
        {
            $users[] = $row;
        }

        $logger->info(
            "Administrator searched users with term='{$searchTerm}', found " . count($users) . " result(s)"
        );

        // AC7 tie-in - every admin action gets logged, including
        // searches, same as list_users/view_user_reports/etc.
        logAdminActivity(
            $db,
            $logger,
            $data['admin_user_id'] ?? null,
            'search_users',
            null,
            null,
            "Searched users with term: {$searchTerm}"
        );

        return
        [
            "status" => "success",
            "users"  => $users
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error(
            "Failed to search users with term='{$searchTerm}': " .
            $e->getMessage()
        );

        return
        [
            "status"  => "error",
            "message" => "Unable to search users"
        ];
    }
}

//cao39 - US-04 AC7 - Admin views the full administrator activity log
function listActivityLog($db, $data, $logger)
{
    $result = $db->query(
        "SELECT admin_activity_logs.log_id,
                admin_activity_logs.admin_user_id,
                admins.username AS admin_username,
                admin_activity_logs.action_type,
                admin_activity_logs.affected_user_id,
                affected_users.username AS affected_username,
                admin_activity_logs.affected_report_id,
                admin_activity_logs.notes,
                admin_activity_logs.created_at
         FROM admin_activity_logs
         JOIN users AS admins ON admin_activity_logs.admin_user_id = admins.user_id
         LEFT JOIN users AS affected_users ON admin_activity_logs.affected_user_id = affected_users.user_id
         ORDER BY admin_activity_logs.created_at DESC
         LIMIT 200"
    );

    $logs = [];

    while ($row = $result->fetch_assoc())
    {
        $logs[] = $row;
    }

    $logger->info("Administrator viewed the activity log");

    return
    [
        "status" => "success",
        "logs"   => $logs
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

	// cao39 - US-04 AC7 - record the lookup itself in the activity log,
	// same as every other admin action. This is the AC3 "update role by
	// username" flow's first step - the role change itself is logged
	// separately by updateRole(), this captures the lookup step too.
	logAdminActivity(
	    $db,
	    $logger,
	    $data['admin_user_id'] ?? null,
	    'lookup_user_by_username',
	    (int)$row['user_id'],
	    null,
	    "Looked up username={$data['username']}"
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
//returns all airport_reports rows for the admin_reports.php
function listReports($db, $data, $logger)
{
    $result = $db->query(
        "SELECT airport_reports.report_id,
            airport_reports.user_id,
            users.username,
            airport_reports.airport_code,
            airport_reports.terminal,
            airport_reports.category,
            airport_reports.comment_text,
            airport_reports.report_status,
            airport_reports.created_at
         FROM airport_reports
         JOIN users ON airport_reports.user_id = users.user_id
         ORDER BY airport_reports.created_at DESC"
    );

    $reports = [];

    while ($row = $result->fetch_assoc())
    {
        $reports[] = $row;
    }

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

    // cao39 - check the report's current state FIRST, before running
    // the UPDATE. This lets us tell apart three distinct outcomes:
    // 1) report doesn't exist at all, 2) report exists but is already
    // flagged, 3) report exists and is being flagged for the first time.
    // affected_rows alone can't tell these apart, since a no-op UPDATE
    // (setting 'flagged' on a row that's already 'flagged') also
    // returns affected_rows = 0, identical to "no row matched."
    $checkStmt = $db->prepare
    (
        "SELECT report_id, report_status
         FROM airport_reports
         WHERE report_id = ?"
    );

    $checkStmt->bind_param("i", $data['report_id']);
    $checkStmt->execute();

    $existingReport = $checkStmt->get_result()->fetch_assoc();

    // cao39 - Case 1: report doesn't exist
    if (!$existingReport)
    {
        $logger->warning(
            "Attempted to flag report_id={$data['report_id']}, but no matching report was found."
        );

        return
        [
            "status"  => "error",
            "message" => "Report not found"
        ];
    }

    // cao39 - Case 2: report exists but is already flagged
    if ($existingReport['report_status'] === 'flagged')
    {
        $logger->warning(
            "Attempted to flag report_id={$data['report_id']}, but it was already flagged."
        );

        return
        [
            "status"  => "error",
            "message" => "This report has already been flagged"
        ];
    }

    // cao39 - Case 3: report exists and is not yet flagged - proceed as normal
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

        $logger->info(
            "Administrator flagged report_id={$data['report_id']} with reason: {$data['reason']}"
        );


        // cao39 - US-04 AC2 - notify the flagged user directly via the
        // dedicated user_warnings table, so the warning reaches the
        // user's own account, not just the admin-facing activity log
        $reportOwnerStmt = $db->prepare(
            "SELECT user_id FROM airport_reports WHERE report_id = ?"
        );
        $reportOwnerStmt->bind_param("i", $data['report_id']);
        $reportOwnerStmt->execute();
        $reportOwner = $reportOwnerStmt->get_result()->fetch_assoc();

        logAdminActivity(
            $db,
            $logger,
            $data['admin_user_id'] ?? null,
            'create_notice',
            $reportOwner['user_id'] ?? null,
            (int)$data['report_id'],
            $data['reason']
        );

        if ($reportOwner)
        {
            $warningStmt = $db->prepare(
                "INSERT INTO user_warnings
                 (user_id, report_id, admin_user_id, warning_message)
                 VALUES (?, ?, ?, ?)"
            );

            $warningStmt->bind_param(
                "iiis",
                $reportOwner['user_id'],
                $data['report_id'],
                $data['admin_user_id'],
                $data['reason']
            );

            try
            {
                $warningStmt->execute();
            }
            catch (mysqli_sql_exception $e)
            {
                // cao39 - a failed warning notification shouldn't
                // undo the flag itself, which already succeeded
                $logger->error("Failed to send warning to user: " . $e->getMessage());
            }
        }

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

        // tad46: record the role change in admin_activity_logs with the affected user id
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

//cao39 - US-04 AC4 - Displays all reports that a user has submitted
function listReportsByUser($db, $data, $logger)
{
    if (empty($data['user_id']))
    {
        $logger->warning("Administrator attempted to view report history without providing user_id");
        return ["status" => "error", "message" => "Missing user_id"];
    }

    $stmt = $db->prepare(
        "SELECT report_id, airport_code, terminal, category, comment_text, report_status, created_at
         FROM airport_reports
         WHERE user_id = ?
         ORDER BY created_at DESC"
    );
    $stmt->bind_param("i", $data['user_id']);

    try
    {
        $stmt->execute();
        $result = $stmt->get_result();
        $reports = [];
        while ($row = $result->fetch_assoc()) { $reports[] = $row; }

        $logger->info("Administrator viewed report history for user_id={$data['user_id']}");

        logAdminActivity(
            $db, $logger,
            $data['admin_user_id'] ?? null,
            'view_user_reports',
            (int)$data['user_id'],
            null,
            "Viewed report history for user_id={$data['user_id']}"
        );

        return ["status" => "success", "reports" => $reports];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error("Failed to load report history for user_id={$data['user_id']}: " . $e->getMessage());
        return ["status" => "error", "message" => "Unable to load report history"];
    }
}

// cao39 - US-04 AC5 - Admin views all violations/warnings for one user

function listUserViolations($db, $data, $logger)
{
    
    if (empty($data['user_id']))
    {
        $logger->warning(
            "Administrator attempted to view violations without providing user_id"
        );

        return
        [
            "status"  => "error",
            "message" => "Missing user_id"
        ];
    }

    //cao39 - US-04 AC5 Filters admin activity logs to just this user's warning
    // entries - action_type='create_notice' is what createNotice()
    // writes every time a report gets flagged
	$stmt = $db->prepare
	(
	    "SELECT admin_activity_logs.log_id,
	            admin_activity_logs.admin_user_id,
	            users.username AS admin_username,
	            admin_activity_logs.action_type,
	            admin_activity_logs.affected_report_id,
	            admin_activity_logs.notes,
	            admin_activity_logs.created_at
	     FROM admin_activity_logs
	     JOIN users ON admin_activity_logs.admin_user_id = users.user_id
	     WHERE admin_activity_logs.affected_user_id = ?
	       AND admin_activity_logs.action_type = 'create_notice'
	     ORDER BY admin_activity_logs.created_at DESC"
	);

    $stmt->bind_param("i", $data['user_id']);
   try
    {
        $stmt->execute();

        $result = $stmt->get_result();
        $violations = [];

        while ($row = $result->fetch_assoc())
        {
            $violations[] = $row;
        }

        $logger->info(
            "Administrator viewed violations for user_id={$data['user_id']}"
        );

        // cao39 - US-04 AC7 viewing violation history
        logAdminActivity(
            $db,
            $logger,
            $data['admin_user_id'] ?? null,
            'view_user_violations',
            (int)$data['user_id'],
            null,
            "Viewed violation history for user_id={$data['user_id']}"
        );

        return
        [
            "status"     => "success",
            "violations" => $violations
        ];
    }
    catch (mysqli_sql_exception $e)
    {
        $logger->error(
            "Failed to load violations for user_id={$data['user_id']}: " .
            $e->getMessage()
        );

        return
        [
            "status"  => "error",
            "message" => "Unable to load violations"
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

            // cao39 - 
            $response = listUsers($db,$logger,$data);


            break;


         //cao39 - US-04 AC6 - search users by username or email
        case "usr.adm.search":

            $response=searchUsers($db,$data,$logger);

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

	//cao39 - US-04 AC4 - pull up reports listed by a specific user

        case "usr.adm.reports":

	    $response=listReportsByUser($db,$data,$logger);

	    break;

        case "report.adm.delete":

            $response=deleteReport($db,$data,$logger);

            break;

	//cao39 -  US-04 AC5 - view a single user's violation/warning history

        case "usr.adm.violations":

            $response = listUserViolations($db, $data, $logger);

            break;
        
        //cao39 -  role update form (admin_roles.php).
        case "role.adm.lookup":

            $response=lookupUserByUsername($db,$data,$logger);

            break;

	//cao39 - added actiivity log routing case
        case "activity.adm.log":

            $response=listActivityLog($db,$data,$logger);

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
