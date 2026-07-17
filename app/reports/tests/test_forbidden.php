<?php
// test_forbidden.php — proves DB-side ownership check (AC3 / AC4)
// Account B (user 2) tries to edit report 1, which is owned by Account A (user 1).
require_once '/var/www/html/mq_helper.php';

echo "TEST: user 2 (tester2) tries to EDIT report 1 (owned by user 1 noaman)...\n";

$payload = [
    'action'    => 'report.update',
    'user_id'   => 2,          // Account B — NOT the owner
    'report_id' => 1,          // report owned by Account A
    'category'  => 'HACKED',
    'content'   => 'Account B is trying to change Account A report',
];

$response = mq_send_and_receive($payload, QUEUE_REPORT_REQUEST, QUEUE_REPORT_RESPONSE);

echo "\n--- Database responded ---\n";
print_r($response);

if (isset($response['status']) && $response['status'] === 'forbidden') {
    echo "\n✅ SUCCESS: The database REFUSED the edit. Ownership is enforced (AC3).\n";
} else {
    echo "\n⚠️ Unexpected response — tell your tutor.\n";
}
