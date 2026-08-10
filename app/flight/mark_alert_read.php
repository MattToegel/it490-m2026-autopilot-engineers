<?php
// mark_alert_read.php
// tad46: Dismiss an alert (marks it read). POST-only.
// tad46: Supports two modes:
// tad46:   - Normal form POST -> redirect (existing behavior, JS-disabled fallback)
// tad46:   - AJAX POST (X-Requested-With: XMLHttpRequest) -> JSON response, no redirect
// tad46: DB handler enforces ownership (WHERE alert_id = ? AND user_id = ?).

session_start();
require_once __DIR__ . '/../auth/auth_protect.php';
require_once __DIR__ . '/alert_client.php';

// tad46: detect AJAX calls so we can skip the redirect and return JSON instead
$isAjax = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

function respond(bool $isAjax, bool $success, string $returnTo = '/../dashboard.php'): void
{
    if ($isAjax)
    {
        header('Content-Type: application/json');
        echo json_encode(['status' => $success ? 'success' : 'error']);
        exit;
    }

    header('Location: ' . $returnTo . ($success ? '?alert=dismissed' : '?alert=error'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    respond($isAjax, false);
}

$alertId = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT);

$returnTo = $_POST['return_to'] ?? '/../dashboard.php';
if (!preg_match('#^/[^/]#', $returnTo))
{
    $returnTo = '/../dashboard.php';
}

if (!$alertId)
{
    respond($isAjax, false, $returnTo);
}

$response = sendAlertRequest('alert.mark_read',
[
    'user_id'  => (int)($_SESSION['user_id'] ?? 0),
    'alert_id' => $alertId,
]);

$success = $response && ($response['status'] ?? '') === 'success';
respond($isAjax, $success, $returnTo);