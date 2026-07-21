<?php
/*

xml: This file is the code for the protected access pages of our platform.
if user is not in a logged in session, then the user is redirected to
the login page. The page will be protected until user logs in with
valid credentials.
*/

// xml: conditional statement that checks if there is a logged-in session
if (!isset($_SESSION['user_id']))
{
    // xml: redirects user to login page if condition is true
    header('Location: /auth/login.php');
    exit;
}