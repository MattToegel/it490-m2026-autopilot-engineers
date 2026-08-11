<?php
//cao39 - US-04 AC7 - Admin Activity Log Page
session_start();

require_once "../auth/auth_protect.php";
require_once "admin_client.php";

if (($_SESSION['role'] ?? '') !== 'admin')
{
    die("Access Denied");
}

$currentAdminId = (int)($_SESSION['user_id'] ?? 0);

$response = sendAdminRequest(
    "activity.adm.log",
    ["admin_user_id" => $currentAdminId]
);

$logs  = [];
$error = null;

if ($response === null)
{
    $error = "Admin service unavailable.";
}
elseif (($response['status'] ?? '') === 'success')
{
    $logs = $response['logs'] ?? [];
}
else
{
    $error = $response['message'] ?? 'Unable to load activity log.';
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Activity Log | OnTheRadar</title>
<link rel="stylesheet" href="admin.css">
</head>

<body>
<div class="admin-page">

<header class="admin-header">
<h1>OnTheRadar Admin Panel</h1>
<nav>
<a href="admin_dashboard.php">Dashboard</a>
<a href="admin_users.php">Users</a>
<a href="admin_reports.php">Reports</a>
<a href="admin_roles.php">Roles</a>
<a href="../dashboard.php">User Dashboard</a>
</nav>
</header>

<main class="admin-content">

<h2>Administrator Activity Log</h2>

<p>
<a href="admin_dashboard.php" class="admin-cancel-button">&laquo; Back to Dashboard</a>
</p>

<?php if ($error): ?>
<div class="admin-error">
<?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endif; ?>

<section class="admin-card">

<table class="admin-table">
<thead>
<tr>
<th>Log ID</th>
<th>Admin ID</th>
<th>Action</th>
<th>Affected User</th>
<th>Affected Report</th>
<th>Notes</th>
<th>Date</th>
</tr>
</thead>

<tbody>
<?php if (count($logs) === 0): ?>
<tr>
<td colspan="7">No activity log entries found.</td>
</tr>
<?php else: ?>
<?php foreach ($logs as $log): ?>
<tr>
<td><?php echo htmlspecialchars($log['log_id'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($log['admin_username'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($log['action_type'], ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($log['affected_username'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($log['affected_report_id'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($log['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($log['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>

</section>

</main>

<footer class="admin-footer">
OnTheRadar
</footer>

</div>
</body>
</html>
