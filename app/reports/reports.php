<?php
// reports.php - Community view of recent Newark airport reports (US-03 AC1)
// ns87: Converted to the team system: report_client.php transport, team session vars,
// ns87: team schema fields (report_id/comment_text/terminal), shared auth_styles.css.

session_start();

// nms37: team session guard - redirects to login.php if no user_id in session
require_once __DIR__ . '/../auth/auth_protect.php';
require_once __DIR__ . '/report_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// nms37: pull recent reports from every user (community feed).
// CONFIRM: reports_consumer.php must return ALL users' reports when no user_id is passed.
$response = sendReportRequest('report.list', ['limit' => 50]);

$reports = [];
$loadError = null;

if ($response === null) {
    $loadError = 'Reports service is temporarily unavailable. Please try again later.';
} elseif (($response['status'] ?? '') === 'success') {
    $reports = $response['reports'] ?? [];
} else {
    $loadError = $response['message'] ?? 'Could not load reports.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airport Reports | OnTheRadar</title>

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
                <a href="/dashboard.php" class="nav-link"><span>Dashboard</span></a>
                <a href="/reports/reports.php" class="nav-link"><span>Community</span></a>
                <a href="/auth/logout.php" class="icon-button" aria-label="Log out">
                    <img src="/assets/user-icon.svg" alt="">
                </a>
            </nav>
        </header>

        <main class="registration-background">
            <section class="reports-panel" aria-labelledby="reports-heading">
                <div class="reports-panel__head">
                    <h1 id="reports-heading">Newark Airport Reports</h1>
                    <a href="/reports/report_create.php" class="create-account-button reports-new-btn">
                        Post a Report
                    </a>
                </div>

                <?php if (isset($_GET['created'])): ?>
                    <div class="auth-success" role="status">Your report was posted.</div>
                <?php elseif (isset($_GET['updated'])): ?>
                    <div class="auth-success" role="status">Your report was updated.</div>
                <?php elseif (isset($_GET['deleted'])): ?>
                    <div class="auth-success" role="status">Your report was removed.</div>
                <?php endif; ?>

                <?php if ($loadError): ?>
                    <div class="auth-error" role="alert">
                        <?= htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php elseif (empty($reports)): ?>
                    <div class="reports-empty">
                        <p>No reports yet. Be the first to post one.</p>
                        <a href="/reports/report_create.php">Create a report</a>
                    </div>
                <?php else: ?>
                    <ul class="reports-list">
                        <?php foreach ($reports as $r): ?>
                            <?php
                            // CONFIRM field names with reports_consumer.php:
                            $rid      = (int)($r['report_id'] ?? 0);
                            $ownerId  = (int)($r['user_id'] ?? 0);
                            $category = $r['category'] ?? 'General';
                            $comment  = $r['comment_text'] ?? '';
                            $terminal = $r['terminal'] ?? '';
                            $author   = $r['username'] ?? ($r['author'] ?? 'A traveler');
                            $created  = $r['created_at'] ?? '';
                            $isOwner  = ($ownerId === $currentUserId && $currentUserId > 0);
                            ?>
                            <li class="report-item">
                                <div class="report-item__top">
                                    <span class="report-item__category">
                                        <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <?php if ($terminal !== ''): ?>
                                        <span class="report-item__terminal">
                                            Terminal <?= htmlspecialchars($terminal, ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php endif; ?>
                                    <span class="report-item__meta">
                                        by <?= htmlspecialchars($author, ENT_QUOTES, 'UTF-8') ?>
                                        <?php if ($created !== ''): ?>
                                            &bull; <?= htmlspecialchars(date('M j, Y g:i A', strtotime($created)), ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    </span>
                                </div>

                                <p class="report-item__text">
                                    <?= nl2br(htmlspecialchars($comment, ENT_QUOTES, 'UTF-8')) ?>
                                </p>

                                <?php if ($isOwner): ?>
                                    <div class="report-item__actions">
                                        <a href="/reports/report_edit.php?id=<?= $rid ?>" class="report-item__edit">Edit</a>
                                        <form method="post" action="/reports/report_delete.php"
                                              onsubmit="return confirm('Remove this report?');">
                                            <input type="hidden" name="report_id" value="<?= $rid ?>">
                                            <button type="submit" class="report-item__delete">Delete</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </main>

        <footer class="site-footer">OnTheRadar</footer>
    </div>
</body>
</html>
