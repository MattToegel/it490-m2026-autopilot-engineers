<?php
// report_create.php - Create a new Newark airport report (US-03 AC2)
// ns87: Converted to the team system: report_client.php transport, team session vars,
// ns87: team schema fields (comment_text/terminal/airport_code), shared auth_styles.css.

session_start();

require_once __DIR__ . '/../auth/auth_protect.php';
require_once __DIR__ . '/report_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$error = '';

// nms37: kept from the original - the 7 useful categories
$categories = [
    'TSA Wait Time',
    'Gate Change',
    'Flight Delay',
    'Bathroom / Facilities',
    'Food & Dining',
    'Parking',
    'General Alert',
];

// nms37: EWR terminals for the optional terminal tag
$terminals = ['A', 'B', 'C'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $comment  = trim($_POST['comment_text'] ?? '');
    $terminal = trim($_POST['terminal'] ?? '');

    // nms37: same App-side validation the original had (kept per teammate's review)
    if ($category === '' || $comment === '') {
        $error = 'Please choose a category and describe the situation.';
    } elseif (!in_array($category, $categories, true)) {
        $error = 'Please select a valid category.';
    } elseif (strlen($comment) < 5) {
        $error = 'Your report is too short. Please add a little more detail.';
    } elseif ($terminal !== '' && !in_array($terminal, $terminals, true)) {
        $error = 'Please select a valid terminal.';
    } else {
        // CONFIRM request field names with reports_consumer.php
        $response = sendReportRequest('report.create', [
            'user_id'      => $currentUserId,
            'category'     => $category,
            'comment_text' => $comment,
            'terminal'     => $terminal,
            'airport_code' => 'EWR',
        ]);

        if ($response && ($response['status'] ?? '') === 'success') {
            header('Location: /reports/reports.php?created=1');
            exit;
        }
        $error = $response['message'] ?? 'Could not post your report. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Report | OnTheRadar</title>

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
            <section class="registration-card" aria-labelledby="create-heading">
                <h1 id="create-heading">Post an Airport Report</h1>
                <p class="form-tagline">Help other travelers at Newark (EWR)</p>

                <?php if ($error): ?>
                    <div class="auth-error" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="report-form">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">-- Select a category --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>>
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
                                <option value="<?= $t ?>"
                                    <?= (($_POST['terminal'] ?? '') === $t) ? 'selected' : '' ?>>
                                    Terminal <?= $t ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comment_text">Report Details</label>
                        <textarea id="comment_text" name="comment_text" required
                            placeholder="e.g. TSA line at Terminal B is about 45 minutes long."><?= htmlspecialchars($_POST['comment_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <button type="submit" class="create-account-button">Post Report</button>
                </form>
            </section>
        </main>

        <footer class="site-footer">OnTheRadar</footer>
    </div>
</body>
</html>