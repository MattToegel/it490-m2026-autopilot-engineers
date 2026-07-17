<?php
// report_edit.php — Edit your own report (AC3)
// IT490 MVP | ns87 | US-03
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$_SESSION['login_time'] = time();

require_once __DIR__ . '/mq_helper.php';

$reportId = (int)($_GET['id'] ?? $_POST['report_id'] ?? 0);
$error    = '';

$categories = [
    'TSA Wait Time', 'Gate Change', 'Flight Delay',
    'Bathroom / Facilities', 'Food & Dining', 'Parking', 'General Alert',
];

// Load the report first
$existing = null;
if ($reportId > 0) {
    $res = mq_send_and_receive(
        ['action' => 'report.get_one', 'report_id' => $reportId],
        QUEUE_REPORT_REQUEST, QUEUE_REPORT_RESPONSE
    );
    if ($res && $res['status'] === 'success') {
        $existing = $res['report'];
        // Make sure this user owns it
        if ((int)$existing['user_id'] !== (int)$_SESSION['user_id']) {
            header('Location: reports.php');
            exit;
        }
    } else {
        header('Location: reports.php');
        exit;
    }
} else {
    header('Location: reports.php');
    exit;
}

// Handle the update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $content  = trim($_POST['content']  ?? '');

    if (empty($category) || empty($content)) {
        $error = 'Please fill in all fields.';
    } elseif (!in_array($category, $categories)) {
        $error = 'Please select a valid category.';
    } elseif (strlen($content) < 5) {
        $error = 'Report content is too short.';
    } else {
        $response = mq_send_and_receive([
            'action'    => 'report.update',
            'user_id'   => $_SESSION['user_id'],
            'report_id' => $reportId,
            'category'  => $category,
            'content'   => $content,
        ], QUEUE_REPORT_REQUEST, QUEUE_REPORT_RESPONSE);

        if ($response && $response['status'] === 'success') {
            header('Location: reports.php?updated=1');
            exit;
        } elseif ($response && $response['status'] === 'forbidden') {
            header('Location: reports.php');
            exit;
        } else {
            $error = 'Could not update report. Please try again.';
        }
    }
}

$currentCategory = $_POST['category'] ?? $existing['category'];
$currentContent  = $_POST['content']  ?? $existing['content'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Report — OnTheRadar ns87</title>
    <style>
        body     { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; }
        h2       { color: #2c3e50; }
        .nav     { margin-bottom: 20px; }
        .nav a   { margin-right: 14px; color: #2c7be5; text-decoration: none; }
        label    { display: block; margin-top: 14px; font-weight: bold; color: #333; }
        select, textarea { width: 100%; padding: 8px; margin-top: 6px; box-sizing: border-box;
                           border: 1px solid #ccc; border-radius: 3px; font-size: 14px; }
        textarea { height: 120px; resize: vertical; }
        button   { margin-top: 18px; width: 100%; padding: 11px;
                   background: #f0ad4e; color: white; border: none;
                   border-radius: 3px; cursor: pointer; font-size: 15px; }
        button:hover { background: #d4901a; }
        .error   { color: red; background: #ffe0e0; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
        .meta    { font-size: 13px; color: #888; margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="reports.php">← Back to Reports</a>
        <a href="logout.php" style="color:#dc3545;">Log Out</a>
    </div>

    <h2>✏️ Edit Your Report</h2>
    <p class="meta">Originally posted: <?= date('M j, Y g:i A', strtotime($existing['created_at'])) ?></p>

    <?php if ($error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="report_id" value="<?= $reportId ?>">

        <label>Category</label>
        <select name="category" required>
            <option value="">-- Select a category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"
                    <?= ($currentCategory === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Report Details</label>
        <textarea name="content" required><?= htmlspecialchars($currentContent) ?></textarea>

        <button type="submit">Save Changes</button>
    </form>
</body>
</html>
