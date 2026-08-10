<?php
// login.php
// tad46: Login form and session creation handler for the App VM
// rma9: Added the OnTheRadar login interface for MVP US-01 AC2
// rma9: Added App-side login input validation before MQ publishing

session_start();

require_once __DIR__ . '/auth_client.php';

$error = null;

// rma9: Gets the one-time success message after email verification.
$loginSuccess = $_SESSION['login_success'] ?? null;

// rma9: Removes the success message so it only appears once.
unset($_SESSION['login_success']);

// tad46: show a success message when the user arrives after registering
$registered = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    // rma9: safely collect the login form values
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // rma9: validate required login fields before sending the request
    if ($email === '')
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
    else
    {
        // tad46: send the login request to the DB VM through RabbitMQ
        $response = sendAuthRequest(
            'user.login',
            [
                'email' => $email,
                'password' => $password,
            ]
        );

        if ($response && ($response['status'] ?? '') === 'success')
        {
            // tad46: create the authenticated user session
            $_SESSION['user_id'] = $response['user_id'];
            $_SESSION['username'] = $response['username'];
            $_SESSION['email'] = $response['email'] ?? '';
            $_SESSION['role'] = $response['role'];

            // rma9: redirect to the dashboard located in the main app folder
            header('Location: ../dashboard.php');
            exit;
        }

        // tad46: display the backend error or a safe fallback message
        $error = $response['message']
            ?? 'Service unavailable. Please try again.';
    }
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

    <title>Log In | OnTheRadar</title>

    <!-- rma9: Noto Sans Georgian font used throughout the design -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- rma9: shared authentication stylesheet -->
    <link
        rel="stylesheet"
        href="../public/auth_styles.css"
    >
</head>

<body>
    <div class="app-frame">

        <!-- rma9: shared OnTheRadar navigation header -->
        <header class="site-header">
            <a href="login.php" class="brand">
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
                    <img
                        src="../assets/notification-icon.svg"
                        alt=""
                    >
                </button>

                <a
                    href="login.php"
                    class="icon-button"
                    aria-label="User account"
                >
                    <img
                        src="../assets/user-icon.svg"
                        alt=""
                    >
                </a>
            </nav>
        </header>

        <!-- rma9: airport background containing the login form -->
        <main class="registration-background">
            <section
                class="registration-card login-card"
                aria-labelledby="login-heading"
            >
                <img
                    src="../assets/plane_radar_transparent.svg"
                    alt="OnTheRadar logo"
                    class="form-logo"
                >

                <h1 id="login-heading">Welcome Back</h1>

                <p class="form-tagline">
                    Stay on the radar
                </p>

                <?php if ($loginSuccess): ?>
                    <div class="auth-success" role="status">
                        <?= htmlspecialchars(
                            $loginSuccess,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </div>
                <?php endif; ?>

                <?php if ($registered): ?>
                    <div class="auth-success" role="status">
                        Registration successful! Please log in.
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
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

                <form method="post" id="login-form">
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
                        <label for="login-password">
                            Password
                        </label>

                        <div class="input-shell">
                            <img
                                src="../assets/lock-icon.svg"
                                alt=""
                                class="field-icon"
                            >

                            <input
                                id="login-password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="password-toggle"
                                data-target="login-password"
                                aria-label="Show password"
                            >
                                <img
                                    src="../assets/eye-icon.svg"
                                    alt=""
                                >
                            </button>
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="create-account-button login-button"
                    >
                        Log In
                    </button>
                </form>

                <p class="or-text">or</p>

                <p class="login-text">
                    Don’t have an account?
                    <a href="register.php">Register</a>
                </p>
            </section>
        </main>

        <footer class="site-footer">
            OnTheRadar
        </footer>
    </div>

    <!-- rma9: shared password-visibility JavaScript -->
    <script src="../public/auth_script.js"></script>
</body>
</html>