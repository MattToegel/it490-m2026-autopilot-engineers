<?php
// reports.php - Community view of recent Newark airport reports (US-03 AC1)
// ns87: Converted to the team system: report_client.php transport, team session vars,
// ns87: team schema fields (report_id/comment_text/terminal), shared auth_styles.css.

session_start();

// nms37: team session guard - redirects to login.php if no user_id in session
require_once __DIR__ . '/../auth/auth_protect.php';
require_once __DIR__ . '/report_client.php';

// tad46: added grab session variables
$isLoggedIn    = !empty($_SESSION['user_id']);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$username = htmlspecialchars($_SESSION['username'] ?? 'Traveler', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8');

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

    <!-- rma9: Apply the saved theme before rendering to prevent a light-mode flash. -->
    <script>
    (function ()
    {
    const savedTheme = localStorage.getItem("otr-theme");

    document.documentElement.setAttribute(
        "data-theme",
        savedTheme === "dark" ? "dark" : "light"
    );
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="/public/auth_styles.css">
    <link rel="stylesheet" href="/public/reports_styles.css">
    <link rel="stylesheet" href="/public/dashboard_styles.css">
    <link rel="stylesheet" href="/public/notif_bell.css">

    <!-- rma9: Load shared light and dark mode styles for community reports. -->
    <link rel="stylesheet" href="/public/theme.css?v=10">
</head>
<body>
    <div class="app-frame">

        <!-- tad46: added actual top header to be uniform across webpages  -->
        <header class="top-header">
            <a href="/landing.php" class="top-header__brand">

                <!-- rma9: Use separate light and dark mode logo assets. -->
                <span class="top-header__logo-wrap">
                    <img
                        src="/assets/otr-logo.svg"
                        alt="OnTheRadar logo"
                        class="top-header__logo top-header__logo--light"
                    >

                    <img
                        src="/assets/otr-logo-dark.png"
                        alt="OnTheRadar logo"
                        class="top-header__logo top-header__logo--dark"
                    >
                </span>

                <span class="top-header__brand-name">
                    OnTheRadar
                </span>

            </a>

            <nav class="top-header__nav" aria-label="Main navigation">
                <a href="/search.php" class="top-header__link">
                    <img src="/assets/search-icon.svg" alt="" aria-hidden="true">
                    <span>Search</span>
                </a>

                <a href="#airport-conditions" class="top-header__link">
                    <img src="/assets/airport-map-icon.svg" alt="" aria-hidden="true">
                    <span>Airport Map</span>
                </a>

                <a href="/reports/reports.php" class="top-header__link">
                    <img src="/assets/community-icon.svg" alt="" aria-hidden="true">
                    <span>Community</span>
                </a>

                <!-- rma9: Shared reports-page toggle matching the Settings page toggle. -->
                <button
                    type="button"
                    class="theme-toggle"
                    data-theme-toggle
                    aria-label="Switch to dark mode"
                    aria-pressed="false"
                >
                    <!-- rma9: Shows the sun in light mode and moon in dark mode. -->
                    <span
                        class="theme-toggle__symbol"
                        aria-hidden="true"
                    >
                        ☀
                    </span>

                    <!-- rma9: White circle slides left or right when the theme changes. -->
                    <span class="theme-toggle__circle"></span>
                </button>

                <!-- tad46: added updated notification panel -->
                <?php if ($isLoggedIn ?? true): ?>
                <div class="notif-menu">
                    <button type="button" class="top-header__icon-button bell-link" id="notifBellButton" aria-label="Notifications" aria-expanded="false">
                        <img src="/assets/notification-icon.svg" alt="">
                        <span class="bell-badge" id="notifBellBadge" style="display:none;"></span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-dropdown-header">
                            <strong>Notifications</strong>
                            <span class="notifications-count" id="notifDropdownCount" style="display:none;"></span>
                        </div>
                        <div class="notif-dropdown-body" id="notifDropdownBody">
                            <div class="notif-dropdown-empty">Loading...</div>
                        </div>
                        <a href="/dashboard.php#notifications" class="notif-dropdown-viewall">View all in dashboard</a>
                    </div>
                </div>
                <?php endif; ?>

                <div class="user-menu">
                    <button type="button" class="top-header__icon-button" id="userMenuButton" aria-label="User menu" aria-expanded="false">
                        <img src="/assets/user-icon.svg" alt="">
                    </button>

                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-header">
                            <?= $username ?>
                        </div>
                        <a href="/dashboard.php">Dashboard</a>
                        <a href="/auth/profile.php">Settings</a>
                        <?php if ($role === 'admin'): ?>
                            <a href="/admin/admin.php">Admin Panel</a>
                        <?php endif; ?>
                        <div class="user-dropdown-divider"></div>
                        <a href="/auth/logout.php" class="logout-link">Log Out</a>
                    </div>
                </div>
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

    <!-- tad46: added usser dropdown menu functionality -->
    <script>
        const userButton = document.getElementById("userMenuButton");
        const userDropdown = document.getElementById("userDropdown");

        if (userButton && userDropdown)
        {
            userButton.addEventListener("click", function(e)
            {
                e.stopPropagation();
                userDropdown.classList.toggle("show");
                userButton.setAttribute("aria-expanded", userDropdown.classList.contains("show"));
            });

            document.addEventListener("click", function(e)
            {
                if (!userButton.contains(e.target) && !userDropdown.contains(e.target))
                {
                    userDropdown.classList.remove("show");
                    userButton.setAttribute("aria-expanded", "false");
                }
            });
        }
    </script>

    <script src="/public/notif_bell.js"></script>

    <!-- rma9: Load shared theme behavior and restore the saved reports-page theme. -->
    <script src="/public/theme.js?v=5"></script>
</body>
</html>