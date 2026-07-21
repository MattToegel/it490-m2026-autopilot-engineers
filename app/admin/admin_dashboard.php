<?php
//cao39 - admin dashboard
// admin_dashboard.php
// US-04 - Admin Community Management landing page
// Links out to Users (AC1/AC6), Reports (AC2), Roles (AC3)
 
session_start();
 
require_once "../auth/auth_protect.php";
require_once "../flight/alert_client.php";
 
// - Only administrators may access
if (($_SESSION['role'] ?? '') !== 'admin')
{
    die("Access Denied");
}
 
$username = htmlspecialchars(
    $_SESSION['username'] ?? 'Administrator',
    ENT_QUOTES,
    'UTF-8'
);
 
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
 
// tad46: US-05 AC4 - bell shows the admin's own personal flight alerts,
// tad46: same source dashboard.php uses (admins are still users with
// tad46: their own saved_flights/flight_alerts rows)
$alertsResponse = sendAlertRequest('alert.list',
[
    'user_id' => $currentUserId,
    'limit'   => 20,
]);
 
$allAlerts   = [];
$alertsError = null;
 
if ($alertsResponse === null)
{
    $alertsError = 'Alerts service is temporarily unavailable.';
}
elseif (($alertsResponse['status'] ?? '') === 'success')
{
    $allAlerts = $alertsResponse['alerts'] ?? [];
}
else
{
    $alertsError = $alertsResponse['message'] ?? 'Could not load alerts.';
}
 
$unreadCount = count(array_filter($allAlerts, fn($a) => empty($a['is_read'])));
 
// tad46: same helper dashboard.php uses to render alert timestamps
function timeAgo(string $mysqlTimestamp): string
{
    $timestamp = strtotime($mysqlTimestamp);
    if ($timestamp === false) return 'recently';
    $seconds = max(0, time() - $timestamp);
    if ($seconds < 60) return 'just now';
    if ($seconds < 3600)
    {
        $m = (int)floor($seconds / 60);
        return $m . ' minute' . ($m === 1 ? '' : 's') . ' ago';
    }
    if ($seconds < 86400)
    {
        $h = (int)floor($seconds / 3600);
        return $h . ' hour' . ($h === 1 ? '' : 's') . ' ago';
    }
    $d = (int)floor($seconds / 86400);
    return $d . ' day' . ($d === 1 ? '' : 's') . ' ago';
}
?>
 
 
 
<!DOCTYPE html>
 
<html lang="en">
 
 
<head>
 
 
<meta charset="UTF-8">
 
<meta name="viewport"
      content="width=device-width, initial-scale=1.0">
 
 
<title>
Admin Dashboard | OnTheRadar
</title>
 
 
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap" rel="stylesheet">
 
 
<link rel="stylesheet"
      href="admin.css">
 
<link rel="stylesheet"
      href="../public/bell_dropdown.css">
 
 
</head>
 
 
 
 
<body>
 
 
 
<div class="admin-page">
 
 
 
<!-- ==================== HEADER ==================== -->
<!-- Brand/logo, page nav, and the header controls (theme
     toggle, notification bell, profile dropdown) all live
     in one row here, matching dashboard.php's top-header. -->
 
<header class="admin-header">
 
 
<!-- Brand block: logo + wordmark, links back to this page -->
 
<a href="admin_dashboard.php" class="admin-brand">
 
<img src="../assets/otr-logo.svg"
     class="logo"
     alt="OnTheRadar Logo">
 
<span class="brand-name">
OnTheRadar Admin Panel
</span>
 
</a>
 
 
 
<!-- Admin section nav -->
 
<nav>
 
 
<a href="admin_dashboard.php">
Dashboard
</a>
 
 
<a href="admin_users.php">
Users
</a>
 
 
<a href="admin_reports.php">
Reports
</a>
 
 
<a href="admin_roles.php">
Roles
</a>
 
 
<a href="../dashboard.php">
User Dashboard
</a>
 
 
</nav>
 
 
 
<!-- Theme toggle / notification bell / profile menu.
     IDs and classes here match what admin.js already
     listens for (#profile-button, #profile-menu,
     .theme-toggle) - no JS changes needed. -->
 
<div class="admin-header-controls">
 
 
<!-- tad46: dark mode toggle - functional + persists via
     localStorage in admin.js, but no .dark-mode CSS
     rules exist anywhere yet, so it has no visual
     effect until that's built -->
<button type="button" class="theme-toggle" aria-label="Toggle dark mode">
<span class="theme-toggle-circle"></span>
</button>
 
 
<!-- tad46: US-05 AC4 - notification bell, reuses bell_dropdown.css
     and the admin's own personal flight alerts via
     alert_client.php (same source dashboard.php uses) -->
<div class="bell-wrapper">
 
<!-- hidden checkbox drives the dropdown open/closed via
     bell_dropdown.css sibling selector - no JS needed -->
<label class="bell-icon" aria-label="Notifications">
 
<input type="checkbox" class="bell-toggle-input">
 
<img src="../assets/notification-icon.svg" alt="" style="width:26px;height:26px;object-fit:contain;">
 
<?php if ($unreadCount > 0): ?>
<span class="bell-badge"><?php echo $unreadCount > 9 ? '9+' : (int)$unreadCount; ?></span>
<?php endif; ?>
 
</label>
 
 
<div class="bell-dropdown">
 
<div class="bell-dropdown-head">
<span>Notifications</span>
<?php if ($unreadCount > 0): ?>
<span class="bell-dropdown-count"><?php echo (int)$unreadCount; ?> unread</span>
<?php endif; ?>
</div>
 
 
<?php // tad46: service unreachable ?>
<?php if ($alertsError): ?>
 
<p class="bell-empty">
<?php echo htmlspecialchars($alertsError, ENT_QUOTES, 'UTF-8'); ?>
</p>
 
<?php //tad46 : reachable, but nothing to show ?>
<?php elseif (count($allAlerts) === 0): ?>
 
<p class="bell-empty">
No notifications
</p>
 
<?php else: ?>
 
<ul class="bell-alert-list">
 
<?php foreach ($allAlerts as $alert): ?>
 
<li class="bell-alert-item">
 
<div class="bell-alert-top">
<span class="bell-alert-flight"><?php echo htmlspecialchars($alert['flight_number'], ENT_QUOTES, 'UTF-8'); ?></span>
<span class="bell-alert-when"><?php echo htmlspecialchars(timeAgo($alert['created_at']), ENT_QUOTES, 'UTF-8'); ?></span>
</div>
 
<p class="bell-alert-msg">
<?php echo htmlspecialchars($alert['alert_message'], ENT_QUOTES, 'UTF-8'); ?>
</p>
 
<!-- : dismiss deletes the alert row, same pattern as
     dashboard.php's notification dismiss form -->
<?php if (empty($alert['is_read'])): ?>
<form method="post" action="../flight/mark_alert_read.php" class="bell-alert-dismiss-form">
<input type="hidden" name="return_to" value="/admin/admin_dashboard.php">
<input type="hidden" name="alert_id" value="<?php echo (int)$alert['alert_id']; ?>">
<input type="hidden" name="delete_alert" value="1">
<button type="submit" class="bell-alert-dismiss">Dismiss</button>
</form>
<?php endif; ?>
 
</li>
 
<?php endforeach; ?>
 
</ul>
 
<?php endif; ?>
 
</div>
 
</div>
 
 
<!-- : profile dropdown - Dashboard/Settings/Admin Panel/Log Out -->
<div class="user-menu">
 
<button type="button" id="profile-button" class="icon-button" aria-label="User menu" aria-expanded="false">
<img src="../assets/user-icon.svg" alt="">
</button>
 
<div class="user-dropdown" id="profile-menu">
 
<div class="user-dropdown-header">
<?php echo $username; ?>
</div>
 
<a href="../dashboard.php">Dashboard</a>
<a href="../auth/profile.php">Settings</a>
<a href="admin_dashboard.php">Admin Panel</a>
 
<div class="user-dropdown-divider"></div>
 
<a href="../auth/logout.php" class="logout-link">Log Out</a>
 
</div>
 
</div>
 
 
</div>
 
 
 
</header>
 
 
 
 
 
<!-- ==================== MAIN CONTENT ==================== -->
 
<main class="admin-content">
 
 
 
<h2>
Admin Dashboard
</h2>
 
 
<p>
Logged in as: <strong><?php echo $username; ?></strong>
</p>
 
 
 
 
<!-- US-04 landing cards - link out to the pages that
     actually implement each acceptance criterion -->
<div class="dashboard">
 
 
 
<!-- US-04 AC1 - view all users + their roles -->
 
<div class="admin-card">
 
 
<h3>
User Management
</h3>
 
 
<p>
View all registered users and their assigned roles.
</p>
 
 
<a href="admin_users.php">
Manage Users
</a>
 
 
</div>
 
 
 
 
<!-- US-04 AC2 - remove inappropriate reports/comments -->
 
<div class="admin-card">
 
 
<h3>
Reports
</h3>
 
 
<p>
Review airport reports and remove inappropriate content.
</p>
 
 
<a href="admin_reports.php">
View Reports
</a>
 
 
</div>
 
 
 
 
<!-- : placeholder - points at admin_reports.php until this
     has its own page/routing key -->
<div class="admin-card">
 
 
<h3>
Airport Condition Reports
</h3>
 
 
<p>
Browse submitted airport condition updates.
</p>
 
 
<a href="admin_reports.php">
View Conditions
</a>
 
 
</div>
 
 
 
 
<!-- : placeholder - "flag a warning" half of AC2; no
     user_warnings table or working routing key yet
     (content.adm.report / create.adm.notice are bound
     but unhandled in admin_consumer.php) -->
<div class="admin-card">
 
 
<h3>
Recent Warnings
</h3>
 
 
<p>
See users and reports currently flagged with warnings.
</p>
 
 
<a href="admin_reports.php">
View Warnings
</a>
 
 
</div>
 
 
 
 
<!-- : placeholder - points at admin_reports.php until this
     has its own page/routing key -->
<div class="admin-card">
 
 
<h3>
Moderations
</h3>
 
 
<p>
Track moderation actions taken across the platform.
</p>
 
 
<a href="admin_reports.php">
View Moderations
</a>
 
 
</div>
 
 
 
 
<!-- US-04 AC3 - update user roles -->
 
<div class="admin-card">
 
 
<h3>
Roles
</h3>
 
 
<p>
Promote or demote users by updating account roles.
</p>
 
 
<a href="admin_roles.php">
Update Roles
</a>
 
 
</div>
 
 
 
 
</div>
 
 
 
</main>
 
 
 
<!-- ==================== FOOTER ==================== -->
 
<footer class="admin-footer">
 
 
OnTheRadar Admin
 
 
</footer>
 
 
 
 
</div>
 
 
 
</body>
 
 
</html>
