<?php
// mark_alert_read.php
// tad46: Dismiss an alert (marks it read). POST-only, post-then-redirect pattern.
// tad46: DB handler enforces ownership (WHERE alert_id = ? AND user_id = ?).

session_start();
require_once __DIR__ . '/../auth/auth_protect.php';
require_once __DIR__ . '/alert_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: /../dashboard.php');
    exit;
}

$alertId = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT);

$returnTo = $_POST['return_to'] ?? '/../dashboard.php';
if (!preg_match('#^/[^/]#', $returnTo))
{
    $returnTo = '/../dashboard.php';
}

if (!$alertId)
{
    header('Location: ' . $returnTo . '?alert=error');
    exit;
}

$response = sendAlertRequest('alert.mark_read',
[
    'user_id'  => (int)($_SESSION['user_id'] ?? 0),
    'alert_id' => $alertId,
]);

if ($response && ($response['status'] ?? '') === 'success')
{
    header('Location: ' . $returnTo . '?alert=dismissed');
    exit;
}

header('Location: ' . $returnTo . '?alert=error');
exit;