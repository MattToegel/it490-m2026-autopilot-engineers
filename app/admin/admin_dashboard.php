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

<link rel="stylesheet" href="/public/notif_bell.css">

<!-- rma9: Load shared light and dark mode styles for the dashboard. -->
    <link rel="stylesheet" href="/public/theme.css?v=10">
 
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
     .theme-toggle) -->
 
<div class="admin-header-controls">
 
 
<!-- rma9: Shared dashboard toggle matching the Settings page toggle. -->
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
 
 


<div class="bell-wrapper">

<button type="button" id="adminBellButton" class="bell-icon" aria-label="Notifications" aria-expanded="false">

<img src="../assets/notification-icon.svg" alt="" style="width:26px;height:26px;object-fit:contain;">

<?php if ($unreadCount > 0): ?>
<span class="bell-badge"><?php echo $unreadCount > 9 ? '9+' : (int)$unreadCount; ?></span>
<?php endif; ?>

</button>


<div class="bell-dropdown" id="adminBellDropdown">

<div class="bell-dropdown-head">
<span>Notifications</span>
<?php if ($unreadCount > 0): ?>
<span class="bell-dropdown-count"><?php echo (int)$unreadCount; ?> unread</span>
<?php endif; ?>
</div>

<?php if ($alertsError): ?>

<p class="bell-empty">
<?php echo htmlspecialchars($alertsError, ENT_QUOTES, 'UTF-8'); ?>
</p>

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
 
 
 
 
<!-- cao39 - US-04 landing cards - link out to the pages that
     actually implement each acceptance criterion -->
<div class="dashboard">
 
 
 
<!-- cao39 - US-04 AC1 - view all users + their roles -->
 
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
 
 
 
 
<!-- cao39 - US-04 AC2 - remove inappropriate reports/comments -->
 
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
  
 
<!-- cao39 - US-04 AC3 - update user roles -->
 
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
 
 
<!-- cao39 - US-04 AC7 - view administrator activity log -->

<div class="admin-card">


<h3>
Activity Log
</h3>


<p>
View a record of administrator actions
</p>


<a href="admin_activity_log.php">
View Activity Log
</a>


</div> 
 
</div>
 
 
 
</main>
 
 
 
<!-- ==================== FOOTER ==================== -->
 
<footer class="admin-footer">
 
 
OnTheRadar
 
 
</footer>
 
 
 
 
</div>
 
 
<script>
const profileButton = document.getElementById("profile-button");
const profileDropdown = document.getElementById("profile-menu");

if (profileButton && profileDropdown)
{
    profileButton.addEventListener("click", function(e)
    {
        e.stopPropagation();
        profileDropdown.classList.toggle("show");
        profileButton.setAttribute("aria-expanded", profileDropdown.classList.contains("show"));
    });

    document.addEventListener("click", function(e)
    {
        if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target))
        {
            profileDropdown.classList.remove("show");
            profileButton.setAttribute("aria-expanded", "false");
        }
    });
}

     // tad46: bell dropdown toggle - was previously a CSS-only checkbox trick
     // that couldn't actually work (checkbox and .bell-dropdown weren't
     // true siblings), replaced with click-toggle matching notif_bell.js
     const adminBellButton = document.getElementById('adminBellButton');
     const adminBellDropdown = document.getElementById('adminBellDropdown');

     if (adminBellButton && adminBellDropdown)
     {
     adminBellButton.addEventListener('click', function (e)
     {
          e.stopPropagation();
          adminBellDropdown.classList.toggle('show');
          adminBellButton.setAttribute('aria-expanded', adminBellDropdown.classList.contains('show'));
     });

     document.addEventListener('click', function (e)
     {
          if (!adminBellButton.contains(e.target) && !adminBellDropdown.contains(e.target))
          {
               adminBellDropdown.classList.remove('show');
               adminBellButton.setAttribute('aria-expanded', 'false');
          }
     });
     }
     // tad46: AJAX dismiss for the admin bell dropdown - same pattern as
     // dashboard.php's notification panel, adapted to this page's
     // bell-alert-* class names.
     document.querySelectorAll('.bell-alert-dismiss-form').forEach(function (form)
     {
     form.addEventListener('submit', async function (e)
     {
          e.preventDefault();

          const alertId = form.querySelector('input[name="alert_id"]').value;
          const listItem = form.closest('.bell-alert-item');
          const button = form.querySelector('.bell-alert-dismiss');

          button.disabled = true;

          try
          {
               const res = await fetch('../flight/mark_alert_read.php',
               {
                    method: 'POST',
                    headers:
                    {
                         'Content-Type': 'application/x-www-form-urlencoded',
                         'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'include',
                    body: 'alert_id=' + encodeURIComponent(alertId),
               });

               const data = await res.json();

               if (data.status === 'success')
               {
                    if (listItem)
                    {
                         listItem.classList.add('bell-alert-item--read');
                         button.replaceWith(Object.assign(document.createElement('span'),
                         {
                         className: 'bell-alert-read-tag',
                         textContent: 'Read',
                         }));
                    }

                    // tad46: keep the badge + dropdown-header count in sync
                    const badge = document.querySelector('.bell-badge');
                    const countLabel = document.querySelector('.bell-dropdown-count');

                    if (badge)
                    {
                         const current = parseInt(badge.textContent, 10) || 0;
                         const next = Math.max(0, current - 1);
                         if (next === 0) { badge.remove(); }
                         else { badge.textContent = next > 9 ? '9+' : next; }
                    }

                    if (countLabel)
                    {
                         const current = parseInt(countLabel.textContent, 10) || 0;
                         const next = Math.max(0, current - 1);
                         if (next === 0) { countLabel.remove(); }
                         else { countLabel.textContent = next + ' unread'; }
                    }
               }
               else
               {
                    button.disabled = false;
               }
          }
          catch (err)
          {
               console.error('Dismiss failed:', err);
               button.disabled = false;
          }
     });
     });
</script>

<!-- rma9: Load shared theme behavior and restore the saved dashboard theme. -->
<script src="/public/theme.js?v=5"></script> 

</body>
 
 
</html>
