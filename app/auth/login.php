<?php
// login.php
// tad46: Login form and session creation handler for the App VM

session_start();

require_once __DIR__ . '/auth_client.php';

$error = null;

// tad46: show a success message when the user arrives after registering
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // tad46: send the login request to the DB VM through RabbitMQ
    $response = sendAuthRequest(
        'user.login',
        [
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
        ]
    );

    if ($response && ($response['status'] ?? '') === 'success')
    {
        // tad46: create the authenticated user session after a successful login
        $_SESSION['user_id'] = $response['user_id'];
        $_SESSION['username'] = $response['username'];
        $_SESSION['role'] = $response['role'];

        // rma9: dashboard.php was moved from app/auth to the main app folder
        header('Location: ../dashboard.php');
        exit;
    }

    // tad46: display the backend error or a safe service-unavailable message
    $error = $response['message']
        ?? 'Service unavailable. Please try again.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Log In</title>
</head>

<body>
    <h1>Log In</h1>

    <?php if ($registered): ?>
        <p style="color: green;">
            Registration successful! Please log in.
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color: red;">
            <?php
            echo htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            );
            ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <label>
            Email:
            <input
                name="email"
                type="email"
                required
            >
        </label>

        <br><br>

        <label>
            Password:
            <input
                name="password"
                type="password"
                required
            >
        </label>

        <br><br>

        <button type="submit">
            Log In
        </button>
    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register</a>
    </p>
</body>
</html>