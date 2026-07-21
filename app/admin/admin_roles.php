<?php
//cao39 - admin roles
// admin_roles.php
// US-04 AC3 - Admin updates a user's role
// MQ Queue Routing: role.adm.update
 

session_start();

require_once "../auth/auth_protect.php";
require_once "admin_client.php";
require_once "../flight/alert_client.php";

//cao39 - Only administrators may access
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

//tad46 : US-05 AC4 - bell shows the admin's own personal flight alerts,
//tad46 : same source dashboard.php/admin_dashboard.php use
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

// : allowed roles - keeps the select honest and blocks anything
// : outside this list before it ever reaches the queue
$allowedRoles = ['user', 'admin'];

// : sticky form value - re-populated below on a failed/validation-error
// : submit so the admin doesn't have to retype the identifier.
// : Accepts either a numeric User ID or a username.
$submittedIdentifier = trim($_POST['identifier'] ?? '');
$submittedRole       = $_POST['role'] ?? 'user';

$message  = null;
$messageIsError = false;

if (isset($_POST['update']))
{
    if ($submittedIdentifier === '')
    {
        $message = 'Please enter a User ID or username.';
        $messageIsError = true;
    }
    elseif (!in_array($submittedRole, $allowedRoles, true))
    {
        $message = 'Please choose a valid role.';
        $messageIsError = true;
    }
    else
    {
        // : if it's all digits, treat it as a user ID directly.
        // : otherwise, resolve it as a username first via the
        // : admin service (role.adm.lookup) before we touch
        // : role.adm.update.
        $resolvedUserId = null;

        if (ctype_digit($submittedIdentifier))
        {
            $resolvedUserId = (int)$submittedIdentifier;
        }
        else
        {
            $lookupResponse = sendAdminRequest(
                "role.adm.lookup",
                [
                    "username" => $submittedIdentifier,
                ]
            );

            if ($lookupResponse === null)
            {
                $message = 'Admin service unavailable.';
                $messageIsError = true;
            }
            elseif (($lookupResponse['status'] ?? '') === 'success' && !empty($lookupResponse['user_id']))
            {
                $resolvedUserId = (int)$lookupResponse['user_id'];
            }
            else
            {
                $message = $lookupResponse['message'] ?? 'No user found with that username.';
                $messageIsError = true;
            }
        }

        if ($resolvedUserId !== null)
        {
            $response = sendAdminRequest(
                "role.adm.update",
                [
                    "user_id" => $resolvedUserId,
                    "role"    => $submittedRole,
                ]
            );

            if ($response === null)
            {
                $message = 'Admin service unavailable.';
                $messageIsError = true;
            }
            elseif (($response['status'] ?? '') === 'success')
            {
                $message = $response['message'] ?? 'Role has been updated.';
                $messageIsError = false;
            }
            else
            {
                $message = $response['message'] ?? 'Unable to update role.';
                $messageIsError = true;
            }
        }
    }
}
?>



<!DOCTYPE html>

<html lang="en">


<head>


<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">


<title>
Update User Role | OnTheRadar
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

<header class="admin-header">


<a href="admin_dashboard.php" class="admin-brand">

<img src="../assets/otr-logo.svg"
     class="logo"
     alt="OnTheRadar Logo">

<span class="brand-name">
OnTheRadar Admin Panel
</span>

</a>



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



<div class="admin-header-controls">


<button type="button" class="theme-toggle" aria-label="Toggle dark mode">
<span class="theme-toggle-circle"></span>
</button>


<div class="bell-wrapper">

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


<?php
if ($alertsError): ?>

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
<input type="hidden" name="return_to" value="/admin/admin_roles.php">
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
Update User Role
</h2>


<p>
Promote or demote a user by their User ID or username.
</p>




<?php if ($message): ?>

<div class="<?php echo $messageIsError ? 'admin-error' : 'admin-success'; ?>">

<?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>

</div>

<?php endif; ?>




<section class="admin-card">


<form method="POST" class="role-update-form">


<div class="form-group">

<label for="identifier">
User ID or Username
</label>

<input
    type="text"
    id="identifier"
    name="identifier"
    placeholder="e.g. 42 or jsmith"
    value="<?php echo htmlspecialchars($submittedIdentifier, ENT_QUOTES, 'UTF-8'); ?>"
    required>

</div>



<div class="form-group">

<label for="role">
New Role
</label>

<select id="role" name="role">

<option value="user" <?php echo $submittedRole === 'user' ? 'selected' : ''; ?>>
User
</option>

<option value="admin" <?php echo $submittedRole === 'admin' ? 'selected' : ''; ?>>
Admin
</option>

</select>

</div>



<button type="submit" name="update" value="1" class="admin-button">
Update Role
</button>


</form>


</section>



</main>





<!-- ==================== FOOTER ==================== -->

<footer class="admin-footer">


OnTheRadar Admin


</footer>




</div>



</body>


</html>
