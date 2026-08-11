<?php

//cao39 - RabbitMQ Queue Setup Updated for Milestone 2

require_once __DIR__ . '/../../../IT490-2026/vendor/autoload.php';



use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Wire\AMQPTable;

try {

    echo "Starting RabbitMQ Queue Setup\n";

    $rabbitmqHost = getenv('RABBITMQ_HOST') ?: "localhost";
    $rabbitmqPort = (int)(getenv('RABBITMQ_PORT') ?: 5672);
    $rabbitmqUser = getenv('RABBITMQ_USER') ?: "it490";
    $rabbitmqPassword = getenv('RABBITMQ_PASS') ?: "it490";

    echo "Connecting to RabbitMQ...\n";

    $connection = new AMQPStreamConnection(
        $rabbitmqHost,
        $rabbitmqPort,
        $rabbitmqUser,
        $rabbitmqPassword
    );

    $channel = $connection->channel();
    echo "Connection successful.\n\n";

    $exchangeRequest = "app.requests";
    $exchangeResponse = "app.responses";
    $exchangeDeadLetter = "deadletter";

    echo "Creating exchanges...\n";

    //cao39 Exchange for incoming requests
    $channel->exchange_declare($exchangeRequest, "topic", false, true, false);

    //cao39 Exchange for outgoing responses
    $channel->exchange_declare($exchangeResponse, "topic", false, true, false);

    //cao39 Fanout exchange for dead letters
    $channel->exchange_declare($exchangeDeadLetter, "fanout", false, true, false);


    //cao39 - adding database authorization queue
    $authQueue = "db.auth";
    //cao39 - Adding API request queue
    $apiQueue = "api.requests";
    //cao39 - Adding database log queue
    $databaseQueue = "db.logs";
    //cao39 - Adding APP response queue
    $responseQueue = "app.reply";
    //cao39 - Adding dead letter queue
    $deadLetterQueue = "dlq";
    //cao39 - Adding flight queue
    $flightQueue = "db.flights";
    //cao39 - Adding db admin queue - US-04
    $adminDashQueue = "db.admin";
    //cao39 - Adding reports queue - US-03
    $reportQueue = "db.reports";
    //tad46 - Adding reports queue - US-05
    $alertQueue = "db.alerts";
    //rma9 - Adding email verification queue
    $emailVerificationQueue = "email.verification";

    echo"\nRemoving current queues\n";

    //cao39 - Adding a queue to clear out existing channels to make sure that the process to set up mq queues does not terminate due to duplicate queues
    $removingQueues = [$authQueue, $apiQueue, $databaseQueue, $flightQueue, $responseQueue, $deadLetterQueue, $adminDashQueue, $reportQueue, $alertQueue, $emailVerificationQueue];
    //rma9 - $emailVerificationQueue -> removes the email verification queue during mq reset
    foreach ($removingQueues as $queue) {
        try {
          $channel->queue_delete($queue);
          echo " Removed '$queue'\n";
        } catch (Exception $e) {
            echo "\nSkipping, '$queue' is not found\n";
        }
}
    echo "Creating queues with DLQ support...\n";

    //cao39 - Dead Letter Queue will automatically delete route failed messages after 24 hours
    $dlqArgs = new AMQPTable([
        'x-dead-letter-exchange' => $exchangeDeadLetter,
        'x-message-ttl' => 86400000
    ]);


    //cao39 Authentication Queue
    $channel->queue_declare($authQueue, false, true, false, false, false, $dlqArgs);
    echo "  Created '$authQueue'\n";

    //cao39 API Queue
    $channel->queue_declare($apiQueue, false, true, false, false, false, $dlqArgs);
    echo "  Created '$apiQueue'\n";

    //cao39 Log Queue
    $channel->queue_declare($databaseQueue, false, true, false, false, false, $dlqArgs);
    echo "  Created '$databaseQueue'\n";

    //cao39 Flight Queue
    $channel->queue_declare($flightQueue, false, true, false, false, false, $dlqArgs);
    echo "  Created '$flightQueue'\n";

    //cao39 Response Queue so that the APP VM can receive results
    $channel->queue_declare($responseQueue, false, true, false, false, false);
    echo "  Created '$responseQueue'\n";

    //cao39 Dead Letter Queue
    $channel->queue_declare($deadLetterQueue, false, true, false, false, false);
    echo "  Created '$deadLetterQueue'\n";

     //cao39 Admin Queue - US-04
    $channel->queue_declare($adminDashQueue, false, true, false, false, false, $dlqArgs);
    echo " Created '$adminDashQueue'\n";

    //cao39 Report Queue - US-03
    $channel->queue_declare($reportQueue, false, true, false, false, false, $dlqArgs);
    echo " Created '$reportQueue'\n";

    //tad46 Alerts Queue - US-05
    $channel->queue_declare($alertQueue, false, true, false, false, false, $dlqArgs);
    echo " Created '$alertQueue'\n";

    //rma9 - creates the email verification queue used by email_worker.php
    $channel->queue_declare(
    $emailVerificationQueue,
    false,
    true,
    false,
    false
    );

    //rma9 - confirms the email verification queue was created
    //cao39 - updated code so the echo would work for Stretch Feature 2
    echo " Created '$emailVerificationQueue'\n";

    echo "\nBinding queues to exchanges...\n";

    //cao39 Authentication Routing (Isolated to db.auth)
    $channel->queue_bind($authQueue, $exchangeRequest, "user.register");
    $channel->queue_bind($authQueue, $exchangeRequest, "user.login");
    $channel->queue_bind($authQueue, $exchangeRequest, "user.verify");
    // rma9: Routes resend-code requests to the DB auth consumer.
    $channel->queue_bind(
    $authQueue,
    $exchangeRequest,
    "user.resend_verification"
    );
    //rma9:user update info binding
    $channel->queue_bind($authQueue, $exchangeRequest, "user.update_profile");

    // cao39 Milestone 2 - Verifying the session for persistence session
    $channel->queue_bind($authQueue, $exchangeRequest, "session.validate");

    //cao39 API Routing
    //cao39 US-02 - Flight Search - Search flight numbers
    $channel->queue_bind($apiQueue, $exchangeRequest, "search.flight");
    //cao39 US-02 - Flight Search - Search for airports
    $channel->queue_bind($apiQueue, $exchangeRequest, "search.airport");
    //cao39 US-02 - Flight Search - destination and origin of route
    $channel->queue_bind($apiQueue, $exchangeRequest, "search.route");

    //cao39 Flight Routing
    $channel->queue_bind($flightQueue, $exchangeRequest, "flight.save");
    $channel->queue_bind($flightQueue, $exchangeRequest, "flight.unsave");
    $channel->queue_bind($flightQueue, $exchangeRequest, "flight.list");
    $channel->queue_bind($flightQueue, $exchangeRequest, "flight.cache");
    //cao39 US-02 - List number of flights per user
    $channel->queue_bind($flightQueue, $exchangeRequest, "search.list");
    $channel->queue_bind($flightQueue, $exchangeRequest, "search.save");
    $channel->queue_bind($flightQueue, $exchangeRequest, "search.get_count");
    //cao39 - US-03 Flight cache lookup
    $channel->queue_bind($flightQueue, $exchangeRequest, "flight.cache_lookup");

    //cao39 Log Routing
    $channel->queue_bind($databaseQueue, $exchangeRequest, "log.insert");

    //cao39 Routing response going back to the APP VM
    $channel->queue_bind($responseQueue, $exchangeResponse, "job.complete");
    $channel->queue_bind($responseQueue, $exchangeResponse, "job.failed");

    //cao39 DLQ Bind
    $channel->queue_bind($deadLetterQueue, $exchangeDeadLetter);

    //cao39 US-04 - Admin Routing
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "usr.adm.list");
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "role.adm.update");
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "role.adm.lookup");
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "report.adm.delete");
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "content.adm.report");
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "create.adm.notice");
    //cao39 US-04 AC4 List reports from Users
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "usr.adm.reports");
    //cao39 US-04 AC5 View a history of Warnings and Violations for each user
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "usr.adm.violations");
    //cao39 US-04 AC6 Search for Users
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "usr.adm.search");
    //cao39 US-04 AC7 Admin Activity Logging
    $channel->queue_bind($adminDashQueue, $exchangeRequest, "activity.adm.log");

    //cao39 US-03 - Reports Routing
    $channel->queue_bind($reportQueue, $exchangeRequest, "report.create");
    $channel->queue_bind($reportQueue, $exchangeRequest, "report.list");
    $channel->queue_bind($reportQueue, $exchangeRequest, "report.edit");
    $channel->queue_bind($reportQueue, $exchangeRequest, "report.delete");

    //tad46 US-05 - Alerts Routing
    $channel->queue_bind($alertQueue, $exchangeRequest, "alert.create");
    $channel->queue_bind($alertQueue, $exchangeRequest, "alert.list");
    $channel->queue_bind($alertQueue, $exchangeRequest, "alert.mark_read");
    $channel->queue_bind($alertQueue, $exchangeResponse, "flight.status_change");

    echo "RabbitMQ setup completed successfully!\n";

    $channel->close();
    $connection->close();

    echo "Connection closed.\n";

} catch (Exception $e) {
    echo "\nRabbitMQ setup has failed.\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>