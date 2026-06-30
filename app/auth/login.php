<?php
// login.php
// tad46 - Login form and session creation handler for the App VM

session_start();
require_once __DIR__ . '/auth_client.php';

$error = null;

// tad46: Show a success message if the user just registered
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // tad46: Send the login request to the DB VM through RabbitMQ
    $response = sendAuthRequest('user.login', 
    [
        'email'    => $_POST['email']    ?? '',
        'password' => $_POST['password'] ?? '',
    ]);

    if ($response && $response['status'] === 'success') 
    {
        // tad46: Login worked, create the session
        $_SESSION['user_id']  = $response['user_id'];
        $_SESSION['username'] = $response['username'];
        $_SESSION['role']     = $response['role'];

        // tad46: Redirect to the protected dashboard
        header('Location: dashboard.php');
        exit;
    } 
    else 
    {
        $error = $response['message'] ?? 'Service unavailable; Please try again';
    }
}

?>
<!DOCTYPE html>
<html>
<head><title>Log In</title></head>
<link rel="stylesheet" href="auth_style.css">
<body>
    <h1>Log In</h1>

    <?php if ($registered): ?>
        <p style="color: green;">Registration successful! Please log in.</p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form method="post">
        <label>Email: <input name="email" type="email" required></label><br>
        <label>Password: <input name="password" type="password" required></label><br>
        <button>Log In</button>
    </form>

    <p>Don't have an account? <a href="register.php">Register</a></p>
</body>
</html>
