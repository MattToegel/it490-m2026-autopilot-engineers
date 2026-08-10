<?php
// get_alerts.php
// tad46: Lightweight JSON endpoint for the header bell dropdown.
// tad46: Lets ANY page show real notification data without that page
// tad46: needing to run the alert.list RPC call itself server-side.
// tad46: Guests (no session) get an empty, non-error response - the bell
// tad46: markup is only rendered for logged-in users anyway (see header
// tad46: snippet), but this stays defensive in case it's ever hit directly.

session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id']))
{
    echo json_encode(['status' => 'success', 'alerts' => [], 'unread_count' => 0]);
    exit;
}

require_once __DIR__ . '/alert_client.php';

$response = sendAlertRequest('alert.list',
[
    'user_id' => (int)$_SESSION['user_id'],
    'limit'   => 20,
]);

if ($response === null || ($response['status'] ?? '') !== 'success')
{
    echo json_encode(['status' => 'error', 'alerts' => [], 'unread_count' => 0]);
    exit;
}

$alerts = $response['alerts'] ?? [];
$unread = count(array_filter($alerts, fn($a) => empty($a['is_read'])));

echo json_encode(
[
    'status'       => 'success',
    'alerts'       => $alerts,
    'unread_count' => $unread,
]);