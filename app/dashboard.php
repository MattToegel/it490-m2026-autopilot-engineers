<?php
// dashboard.php
// tad46: Example protected page

// xml: Session Persistence
session_start();

// xml: Authorization guard, Bounce unauthenticated visitors to login [Authorization Guard Code Goes Here]
require_once __DIR__ . '/auth_protect.php';

?>
<!DOCTYPE html>
<html>
<head><title>Dashboard</title></head>
<link rel="stylesheet" href="auth_style.css">
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
    <p>You are logged in as <strong><?php echo htmlspecialchars($_SESSION['role']); ?></strong>.</p>

    <?php if ($_SESSION['role'] === 'admin'): ?>
        <p><a href="admin.php">Go to admin panel</a></p>
    <?php endif; ?>

    <p><a href="logout.php">Log out</a></p>
</body>
</html>
