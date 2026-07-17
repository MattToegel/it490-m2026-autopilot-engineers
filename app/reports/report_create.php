<?php
// report_create.php — Create a new airport report (AC2)
// IT490 MVP | ns87 | US-03
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
$_SESSION['login_time'] = time();

require_once __DIR__ . '/mq_helper.php';

$error   = '';
$success = false;

$categories = [
    'TSA Wait Time',
    'Gate Change',
    'Flight Delay',
    'Bathroom / Facilities',
    'Food & Dining',
    'Parking',
    'General Alert',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $content  = trim($_POST['content']  ?? '');

    if (empty($category) || empty($content)) {
        $error = 'Please fill in all fields.';
    } elseif (!in_array($category, $categories)) {
        $error = 'Please select a valid category.';
    } elseif (strlen($content) < 5) {
        $error = 'Report content is too short. Please describe the situation.';
    } else {
        $response = mq_send_and_receive([
            'action'   => 'report.create',
            'user_id'  => $_SESSION['user_id'],
            'category' => $category,
            'content'  => $content,
            'location' => 'Newark Liberty International Airport',
        ], QUEUE_REPORT_REQUEST, QUEUE_REPORT_RESPONSE);

        if ($response && $response['status'] === 'success') {
            header('Location: reports.php?created=1');
            exit;
        } else {
            $error = 'Could not post report. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Report — OnTheRadar ns87</title>
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
                   background: #2c7be5; color: white; border: none;
                   border-radius: 3px; cursor: pointer; font-size: 15px; }
        button:hover { background: #1a5cbf; }
        .error   { color: red; background: #ffe0e0; padding: 10px; border-radius: 4px; margin-bottom: 12px; }
        .hint    { font-size: 12px; color: #888; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="reports.php">← Back to Reports</a>
        <a href="logout.php" style="color:#dc3545;">Log Out</a>
    </div>

    <h2>�� Create Airport Report</h2>
    <p style="color:#555;">Posting as: <strong><?= htmlspecialchars($_SESSION['user_email']) ?></strong></p>

    <?php if ($error): ?>
        <div class="error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <label>Category</label>
        <select name="category" required>
            <option value="">-- Select a category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>"
                    <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Report Details</label>
        <textarea name="content" required placeholder="Describe what's happening at the airport..."><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
        <p class="hint">Be specific — e.g. "TSA line at Terminal B is 45 minutes long"</p>

        <button type="submit">Post Report</button>
    </form>
</body>
</html>
