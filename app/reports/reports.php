<?php
// reports.php — View all airport reports (AC1)
// IT490 MVP | ns87 | US-03
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$_SESSION['login_time'] = time();

require_once __DIR__ . '/mq_helper.php';

$reports  = [];
$mqError  = false;
$response = mq_send_and_receive(
    ['action' => 'report.get_all'],
    QUEUE_REPORT_REQUEST,
    QUEUE_REPORT_RESPONSE
);

if ($response && $response['status'] === 'success') {
    $reports = $response['reports'];
} else {
    $mqError = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Airport Reports — OnTheRadar ns87</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        h2   { color: #2c3e50; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 14px; color: #2c7be5; text-decoration: none; }
        .nav a.btn { background: #2c7be5; color: white; padding: 7px 14px; border-radius: 3px; }
        .report-card { border: 1px solid #ddd; border-radius: 4px; padding: 16px; margin-bottom: 14px; background: #fafafa; }
        .report-card h3 { margin: 0 0 6px; color: #2c3e50; font-size: 16px; }
        .report-meta  { font-size: 12px; color: #888; margin-bottom: 8px; }
        .report-body  { color: #333; margin-bottom: 10px; }
        .report-loc   { font-size: 12px; color: #2c7be5; }
        .actions a    { margin-right: 10px; font-size: 13px; }
        .edit-btn     { color: #f0ad4e; text-decoration: none; }
        .delete-btn   { color: #dc3545; text-decoration: none; }
        .error-box    { background: #ffe0e0; color: red; padding: 12px; border-radius: 4px; }
        .empty-box    { background: #e8f4fd; padding: 20px; border-radius: 4px; text-align: center; color: #555; }
        .success-box  { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 14px; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="reports.php">All Reports</a>
        <a href="report_create.php" class="btn">+ New Report</a>
        <a href="logout.php" style="color:#dc3545;">Log Out</a>
    </div>

    <h2>✈️ Newark Airport Reports</h2>

    <?php if (isset($_GET['created'])): ?>
        <div class="success-box">✅ Your report was posted successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="success-box">✅ Your report was updated successfully!</div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="success-box">✅ Your report was deleted.</div>
    <?php endif; ?>

    <?php if ($mqError): ?>
        <div class="error-box">⚠️ Could not load reports. Please try again later.</div>
    <?php elseif (empty($reports)): ?>
        <div class="empty-box">
            <p>No reports yet. Be the first to post one!</p>
            <a href="report_create.php">Create a Report</a>
        </div>
    <?php else: ?>
        <p style="color:#555;font-size:14px;">Showing <?= count($reports) ?> recent report(s)</p>
        <?php foreach ($reports as $r): ?>
            <div class="report-card">
                <h3>[<?= htmlspecialchars($r['category']) ?>]</h3>
                <div class="report-meta">
                    Posted by <?= htmlspecialchars($r['author_email']) ?>
                    &bull; <?= date('M j, Y g:i A', strtotime($r['created_at'])) ?>
                    <?php if ($r['updated_at'] !== $r['created_at']): ?>
                        &bull; <em>edited</em>
                    <?php endif; ?>
                </div>
                <div class="report-body"><?= nl2br(htmlspecialchars($r['content'])) ?></div>
                <div class="report-loc">�� <?= htmlspecialchars($r['location']) ?></div>
                <?php if ((int)$r['user_id'] === (int)$_SESSION['user_id']): ?>
                    <div class="actions" style="margin-top:10px;">
                        <a href="report_edit.php?id=<?= $r['id'] ?>" class="edit-btn">✏️ Edit</a>
                        <a href="report_delete.php?id=<?= $r['id'] ?>"
                           class="delete-btn"
                           onclick="return confirm('Delete this report?')">��️ Delete</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
