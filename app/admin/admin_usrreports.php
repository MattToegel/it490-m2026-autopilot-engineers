<?php
//cao39 - US-04 AC4 - Admin User Reports Page
// admin_user_reports.php
// Shows one user's full report submission history, pulled via
// the usr.adm.reports routing key.

session_start();

require_once "../auth/auth_protect.php";
require_once "admin_client.php";

// Only administrators may access
if (($_SESSION['role'] ?? '') !== 'admin')
{
    die("Access Denied");
}

// Grab the target user_id from the query string
$targetUserId = (int)($_GET['user_id'] ?? 0);

if ($targetUserId <= 0)
{
    die("Invalid user ID.");
}

$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

//cao39 - Find a username
$targetUserResponse = sendAdminRequest(
    "usr.adm.search",
    [
        "admin_user_id" => $currentAdminId,
        "search"        => (string)$targetUserId,
    ]
);

//cao39-  fallback just in case the lookup fails
$targetUsername = "User ID {$targetUserId}"; 

if (($targetUserResponse['status'] ?? '') === 'success')
{
    foreach ($targetUserResponse['users'] ?? [] as $u)
    {
        if ((int)$u['user_id'] === $targetUserId)
        {
            $targetUsername = $u['username'];
            break;
        }
    }
}

//cao39  -  AC4 - fetch this user's report history
$reportsResponse = sendAdminRequest(
    "usr.adm.reports",
    [
        "admin_user_id" => $currentAdminId,
        "user_id"       => $targetUserId,
    ]
);

$reports      = [];
$reportsError = null;

if ($reportsResponse === null)
{
    $reportsError = "Admin service unavailable.";
}
elseif (($reportsResponse['status'] ?? '') === 'success')
{
    $reports = $reportsResponse['reports'] ?? [];
}
else
{
    $reportsError = $reportsResponse['message'] ?? 'Could not load report history.';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
User Reports | OnTheRadar
</title>

<link rel="stylesheet" href="admin.css">

</head>

<body>

<div class="admin-page">

<header class="admin-header">

<h1>
OnTheRadar Admin Panel
</h1>

<nav>
<a href="admin_dashboard.php">Dashboard</a>
<a href="admin_users.php">Users</a>
<a href="admin_reports.php">Reports</a>
<a href="admin_roles.php">Roles</a>
<a href="../dashboard.php">User Dashboard</a>
</nav>

</header>

<main class="admin-content">

<h2>
Report History - <?php echo htmlspecialchars($targetUsername, ENT_QUOTES, 'UTF-8'); ?>
</h2>

<p>
<a href="admin_users.php" class="admin-cancel-button">&laquo; Back to Users</a>
</p>

<section class="admin-card">

<?php if ($reportsError): ?>

<div class="admin-error">
<?php echo htmlspecialchars($reportsError, ENT_QUOTES, 'UTF-8'); ?>
</div>

<?php else: ?>

<table class="admin-table">

<thead>
<tr>
<th>Report ID</th>
<th>Airport</th>
<th>Category</th>
<th>Comment</th>
<th>Status</th>
<th>Submitted</th>
</tr>
</thead>

<tbody>

<?php if (count($reports) === 0): ?>

<tr>
<td colspan="6">No reports found for this user.</td>
</tr>

<?php else: ?>

<?php foreach ($reports as $report): ?>

<tr>
<td><?php echo htmlspecialchars($report['report_id'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($report['airport_code'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($report['category'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($report['comment_text'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($report['report_status'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
</tr>

<?php endforeach; ?>

<?php endif; ?>

</tbody>

</table>

<?php endif; ?>

</section>

</main>

<footer class="admin-footer">
OnTheRadar Admin
</footer>

</div>

</body>
</html>
