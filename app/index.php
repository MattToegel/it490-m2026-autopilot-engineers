<?php
// index.php
// tad46: Entry point - route based on whether the user is logged in
session_start();

if (isset($_SESSION['user_id']))
{
    header('Location: /dashboard.php');
    exit;
}

// tad46: Not logged in - send them to the public landing page
// tad46: (or /auth/login.php directly if we skip the landing MVP scope)
header('Location: /landing.php');
exit;