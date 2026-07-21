<?php
// report_delete.php - Delete your own Newark airport report (US-03 AC4) ns-87 Noaman
// ns87: Converted to the team system: report_client.php transport, team session vars.
// ns87: Ownership is enforced in reports_consumer.php (returns 'forbidden' on mismatch).

session_start();

require_once __DIR__ . '/../auth/auth_protect.php';
require_once __DIR__ . '/report_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// nms37: accept report_id from the POST form (community list / dashboard) or a GET id fallback
$reportId = (int)($_POST['report_id'] ?? $_GET['id'] ?? 0);

if ($reportId > 0) {
    $response = sendReportRequest('report.delete', [
        'user_id'   => $currentUserId,
        'report_id' => $reportId,
    ]);

    if ($response && ($response['status'] ?? '') === 'success') {
        header('Location: /reports/reports.php?deleted=1');
        exit;
    }
}

// nms37: forbidden, not found, or service down - fall back to the list
header('Location: /reports/reports.php');
exit;