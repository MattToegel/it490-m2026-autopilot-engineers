<?php
//cao39 - US-04 AC5 - Admin User Violations Page
// admin_user_violations.php
// Shows one user's full violation/warning history, pulled from
// admin_activity_logs via the usr.adm.violations routing key.

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

// AC5 - fetch this user's violation/warning history
$violationsResponse = sendAdminRequest(
    "usr.adm.violations",
    [
        "admin_user_id" => $currentAdminId,
        "user_id"       => $targetUserId,
    ]
);

$violations      = [];
$violationsError = null;

if ($violationsResponse === null)
{
    $violationsError = "Admin service unavailable.";
}
elseif (($violationsResponse['status'] ?? '') === 'success')
{
    $violations = $violationsResponse['violations'] ?? [];
}
else
{
    $violationsError = $violationsResponse['message'] ?? 'Could not load violation history.';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>
User Violations | OnTheRadar
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
Violations &amp; Warnings - User ID <?php echo (int)$targetUserId; ?>
</h2>

<p>
<a href="admin_users.php" class="admin-cancel-button">&laquo; Back to Users</a>
</p>

<section class="admin-card">

<?php if ($violationsError): ?>

<div class="admin-error">
<?php echo htmlspecialchars($violationsError, ENT_QUOTES, 'UTF-8'); ?>
</div>

<?php else: ?>

<table class="admin-table">

<thead>
<tr>
<th>Log ID</th>
<th>Issued By (Admin ID)</th>
<th>Related Report ID</th>
<th>Reason</th>
<th>Date</th>
</tr>
</thead>

<tbody>

<?php if (count($violations) === 0): ?>

<tr>
<td colspan="5">No violations or warnings found for this user.</td>
</tr>

<?php else: ?>

<?php foreach ($violations as $violation): ?>

<tr>
<td><?php echo htmlspecialchars($violation['log_id'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($violation['admin_user_id'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($violation['affected_report_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($violation['notes'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($violation['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
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
