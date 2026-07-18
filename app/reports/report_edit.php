<?php
// report_edit.php - Edit your own Newark airport report (US-03 AC3) ns87-Noaman
// ns87: Converted to the team system: report_client.php transport, team session vars,
// ns87: team schema fields. Ownership is checked here AND again in reports_consumer.php.

session_start();

require_once __DIR__ . '/../auth/auth_protect.php';
require_once __DIR__ . '/report_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$reportId = (int)($_GET['id'] ?? $_POST['report_id'] ?? 0);
$error = '';

$categories = [
    'TSA Wait Time', 'Gate Change', 'Flight Delay',
    'Bathroom / Facilities', 'Food & Dining', 'Parking', 'General Alert',
];
$terminals = ['A', 'B', 'C'];

if ($reportId <= 0) {
    header('Location: /reports/reports.php');
    exit;
}

// nms37: load the existing report to pre-fill the form and verify ownership
// CONFIRM: report.get_one returns ['report' => [...]] with user_id/category/comment_text/terminal
$res = sendReportRequest('report.get_one', ['report_id' => $reportId]);

if (!$res || ($res['status'] ?? '') !== 'success' || empty($res['report'])) {
    header('Location: /reports/reports.php');
    exit;
}

$existing = $res['report'];

// nms37: App-side ownership guard (the DB enforces it again as the real boundary)
if ((int)($existing['user_id'] ?? 0) !== $currentUserId) {
    header('Location: /reports/reports.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $comment  = trim($_POST['comment_text'] ?? '');
    $terminal = trim($_POST['terminal'] ?? '');

    if ($category === '' || $comment === '') {
        $error = 'Please choose a category and describe the situation.';
    } elseif (!in_array($category, $categories, true)) {
        $error = 'Please select a valid category.';
    } elseif (strlen($comment) < 5) {
        $error = 'Your report is too short.';
    } elseif ($terminal !== '' && !in_array($terminal, $terminals, true)) {
        $error = 'Please select a valid terminal.';
    } else {
        $response = sendReportRequest('report.update', [
            'user_id'      => $currentUserId,
            'report_id'    => $reportId,
            'category'     => $category,
            'comment_text' => $comment,
            'terminal'     => $terminal,
        ]);

        if ($response && ($response['status'] ?? '') === 'success') {
            header('Location: /reports/reports.php?updated=1');
            exit;
        } elseif ($response && ($response['status'] ?? '') === 'forbidden') {
            header('Location: /reports/reports.php');
            exit;
        }
        $error = $response['message'] ?? 'Could not update your report. Please try again.';
    }
}

// nms37: values shown in the form (posted value wins on validation error)
$curCategory = $_POST['category'] ?? ($existing['category'] ?? '');
$curComment  = $_POST['comment_text'] ?? ($existing['comment_text'] ?? '');
$curTerminal = $_POST['terminal'] ?? ($existing['terminal'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Report | OnTheRadar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/public/auth_styles.css">
    <link rel="stylesheet" href="/public/reports_styles.css">
</head>
<body>
    <div class="app-frame">

        <header class="site-header">
            <a href="/dashboard.php" class="brand">
                <img src="/assets/otr-logo.svg" alt="OnTheRadar logo" class="brand-logo">
                <span>OnTheRadar</span>
            </a>
            <nav class="site-nav" aria-label="Main navigation">
                <a href="/reports/reports.php" class="nav-link"><span>Back to Reports</span></a>
                <a href="/auth/logout.php" class="icon-button" aria-label="Log out">
                    <img src="/assets/user-icon.svg" alt="">
                </a>
            </nav>
        </header>

        <main class="registration-background">
            <section class="registration-card" aria-labelledby="edit-heading">
                <h1 id="edit-heading">Edit Your Report</h1>

                <?php if ($error): ?>
                    <div class="auth-error" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="report-form">
                    <input type="hidden" name="report_id" value="<?= $reportId ?>">

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">-- Select a category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= ($curCategory === $cat) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="terminal">Terminal (optional)</label>
                        <select id="terminal" name="terminal">
                            <option value="">-- Not sure --</option>
                            <?php foreach ($terminals as $t): ?>
                                <option value="<?= $t ?>" <?= ($curTerminal === $t) ? 'selected' : '' ?>>
                                    Terminal <?= $t ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comment_text">Report Details</label>
                        <textarea id="comment_text" name="comment_text" required><?= htmlspecialchars($curComment, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <button type="submit" class="create-account-button">Save Changes</button>
                </form>
            </section>
        </main>

        <footer class="site-footer">OnTheRadar</footer>
    </div>
</body>
</html>
