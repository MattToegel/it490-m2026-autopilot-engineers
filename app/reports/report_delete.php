<?php
// report_delete.php — Delete your own report (AC4)
// IT490 MVP | ns87 | US-03
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/mq_helper.php';

$reportId = (int)($_GET['id'] ?? 0);

if ($reportId > 0) {
    $response = mq_send_and_receive([
        'action'    => 'report.delete',
        'user_id'   => $_SESSION['user_id'],
        'report_id' => $reportId,
    ], QUEUE_REPORT_REQUEST, QUEUE_REPORT_RESPONSE);

    if ($response && $response['status'] === 'success') {
        header('Location: reports.php?deleted=1');
        exit;
    } elseif ($response && $response['status'] === 'forbidden') {
        header('Location: reports.php');
        exit;
    }
}

header('Location: reports.php');
exit;
