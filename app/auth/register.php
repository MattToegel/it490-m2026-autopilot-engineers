<?php

// rma9: Starts the session so the pending verification email can be stored.
if (session_status() !== PHP_SESSION_ACTIVE)
{
    session_start();
}

// register.php
// tad46: Registration form and handler for the App VM
// rma9: Added Task 3 App-side invalid-input handling before MQ publish
// rma9: Added confirm-password validation for MVP US-01 AC1
// rma9: Added redirect from registration to the email verification page

require_once __DIR__ . '/auth_client.php'; // tad46: used to send authentication requests through RabbitMQ
require_once __DIR__ . '/../logging/app_log.php'; // tad46: used to publish App VM logs

$error = null; // tad46: stores validation or backend error messages

// rma9: Only process registration logic after the user submits the form.
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // rma9: Collect and safely normalize form input.
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // rma9: Collect the confirmation password separately.
    // rma9: Do not trim passwords because spaces may intentionally be part of them.
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // rma9: Log the submitted email only.
    // rma9: Never log plaintext passwords.
    publishAppLog('info', "Registration form submitted for {$email}");

    // rma9: Validate all registration input before publishing anything to RabbitMQ.
    if ($username === '')
    {
        $error = 'Username is required.';
    }
    elseif ($email === '')
    {
        $error = 'Email is required.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $error = 'Please enter a valid email address.';
    }
    elseif ($password === '')
    {
        $error = 'Password is required.';
    }
    elseif (strlen($password) < 8)
    {
        $error = 'Password must be at least 8 characters long.';
    }
    elseif ($confirmPassword === '')
    {
        $error = 'Please confirm your password.';
    }
    elseif ($password !== $confirmPassword)
    {
        // rma9: Stop registration before MQ publish when the two passwords differ.
        $error = 'Passwords do not match.';
    }
    else
    {
        // rma9: Only valid registration data reaches RabbitMQ.
        $response = sendAuthRequest(
            'user.register',
            [
                'username' => $username,
                'email' => $email,
                'password' => $password,
            ]
        );

        // tad46: Handle successful registration response.
        if ($response && ($response['status'] ?? '') === 'success')
        {
            publishAppLog('info', "User registered: {$email}");

            // rma9: Store the new user's email for the verification page.
            $_SESSION['pending_verification_email'] = $email;

            // rma9: Redirect the newly registered user to verify.php.
            header('Location: verify.php');
            exit;
        }

        // tad46: Handle failed registration response.
        $backendMessage = $response['message'] ?? null;

        publishAppLog(
            'warning',
            'Registration failed: ' . ($backendMessage ?? 'no response')
        );

        // rma9: Show a safe message instead of exposing raw MQ or socket details.
        $error = $backendMessage
            ?: 'The account service is temporarily unavailable. Please try again later.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- rma9: Allows the page to scale properly on mobile devices. -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Account | OnTheRadar</title>

    <!-- rma9: Noto Sans Georgian font used in the Figma design. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- rma9: Loads the shared OTR authentication page styling. -->
    <link rel="stylesheet" href="../public/auth_styles.css">
</head>

<body>
    <!-- rma9: Full registration application frame. -->
    <div class="app-frame">

        <!-- rma9: Shared OnTheRadar navigation header. -->
        <header class="site-header">
            <a href="register.php" class="brand">
                <img
                    src="../assets/otr-logo.svg"
                    alt="OnTheRadar logo"
                    class="brand-logo"
                >

                <span>OnTheRadar</span>
            </a>

            <nav class="site-nav" aria-label="Main navigation">
                <a href="#" class="nav-link">
                    <img src="../assets/search-icon.svg" alt="">
                    <span>Search</span>
                </a>

                <a href="#" class="nav-link">
                    <img src="../assets/airport-map-icon.svg" alt="">
                    <span>Airport Map</span>
                </a>

                <a href="#" class="nav-link">
                    <img src="../assets/community-icon.svg" alt="">
                    <span>Community</span>
                </a>

                <button
                    type="button"
                    class="icon-button"
                    aria-label="Notifications"
                >
                    <img src="../assets/notification-icon.svg" alt="">
                </button>

                <a
                    href="login.php"
                    class="icon-button"
                    aria-label="User account"
                >
                    <img src="../assets/user-icon.svg" alt="">
                </a>
            </nav>
        </header>

        <!-- rma9: Airport background containing the registration form. -->
        <main class="registration-background">
            <section
                class="registration-card"
                aria-labelledby="register-heading"
            >
                <img
                    src="../assets/plane_radar_transparent.svg"
                    alt="OnTheRadar Logo"
                    class="form-logo"
                >

                <h1 id="register-heading">Create Your Account</h1>

                <p class="form-tagline">Stay on the radar</p>

                <?php if ($error): ?>
                    <!-- rma9: Securely displays registration errors. -->
                    <div class="auth-error" role="alert">
                        <?php
                        echo htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>
                    </div>
                <?php endif; ?>

                <!-- rma9: Sends registration information back to this page. -->
                <form method="post" id="registration-form">

                    <div class="form-group">
                        <label for="username">Username</label>

                        <div class="input-shell">
                            <img
                                src="../assets/at-symbol.svg"
                                alt=""
                                class="field-icon"
                            >

                            <input
                                id="username"
                                name="username"
                                type="text"
                                autocomplete="username"
                                value="<?php
                                echo htmlspecialchars(
                                    $_POST['username'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>

                        <div class="input-shell">
                            <img
                                src="../assets/mail-icon.svg"
                                alt=""
                                class="field-icon"
                            >

                            <input
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                value="<?php
                                echo htmlspecialchars(
                                    $_POST['email'] ?? '',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>

                        <div class="input-shell">
                            <img
                                src="../assets/lock-icon.svg"
                                alt=""
                                class="field-icon"
                            >

                            <input
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="password"
                                aria-label="Show password"
                            >
                                <img
                                    src="../assets/eye-icon.svg"
                                    alt=""
                                >
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">
                            Confirm Password
                        </label>

                        <div class="input-shell">
                            <img
                                src="../assets/lock-icon.svg"
                                alt=""
                                class="field-icon"
                            >

                            <input
                                id="confirm_password"
                                name="confirm_password"
                                type="password"
                                autocomplete="new-password"
                                minlength="8"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="confirm_password"
                                aria-label="Show confirmed password"
                            >
                                <img
                                    src="../assets/eye-icon.svg"
                                    alt=""
                                >
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="create-account-button">
                        Create Account
                    </button>
                </form>

                <p class="or-text">or</p>

                <p class="login-text">
                    Already have one?
                    <a href="login.php">Login</a>
                </p>
            </section>
        </main>

        <footer class="site-footer">
            OnTheRadar
        </footer>
    </div>

    <script src="../public/auth_script.js"></script>
</body>
</html>