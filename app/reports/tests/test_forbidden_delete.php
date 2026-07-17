<?php
require_once '/var/www/html/mq_helper.php';
echo "TEST: user 2 (tester2) tries to DELETE report 1 (owned by user 1 noaman)...\n";
$payload = [
    'action'    => 'report.delete',
    'user_id'   => 2,
    'report_id' => 1,
];
$response = mq_send_and_receive($payload, QUEUE_REPORT_REQUEST, QUEUE_REPORT_RESPONSE);
echo "\n--- Database responded ---\n";
print_r($response);
if (isset($response['status']) && $response['status'] === 'forbidden') {
    echo "\n✅ SUCCESS: The database REFUSED the delete. Ownership is enforced (AC4).\n";
} else {
    echo "\n⚠️ Unexpected response — tell your tutor.\n";
}
