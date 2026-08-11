<?php
// rma9: Starts the session so the page can access the pending verification email.
if (session_status() !== PHP_SESSION_ACTIVE)
{
    session_start();
}

// rma9: Gets the email saved after a successful registration.
$email = $_SESSION['pending_verification_email'] ?? '';

// rma9: Prevents users from opening the verification page without registering first.
if ($email === '')
{
    header('Location: register.php');
    exit;
}

// rma9: Retrieves a verification error message from the previous request, if one exists.
$error = $_SESSION['verification_error'] ?? null;

// rma9: Retrieves the success message after a new code is requested.
$success = $_SESSION['verification_success'] ?? null;

// rma9: Removes the stored error so it does not appear again after the page refreshes.
unset($_SESSION['verification_error']);

// rma9: Removes the success message after displaying it once.
unset($_SESSION['verification_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- rma9: Allows the verification page to scale correctly on mobile devices. -->
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Verify Email | OnTheRadar</title>

    <!-- rma9: Loads the same font used by the existing OTR authentication pages. -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- rma9: Reuses the shared registration and login page styling. -->
    <link rel="stylesheet" href="../public/auth_styles.css">
</head>

<body>
    <!-- rma9: Main application frame shared across the OTR authentication pages. -->
    <div class="app-frame">

        <!-- rma9: Displays the shared OnTheRadar navigation header. -->
        <header class="site-header">
            <a href="register.php" class="brand">
                <img
                    src="../assets/otr-logo.svg"
                    alt="OnTheRadar logo"
                    class="brand-logo"
                >

                <span>OnTheRadar</span>
            </a>
        </header>

        <!-- rma9: Displays the verification form over the airport background. -->
        <main class="registration-background">
            <section class="registration-card">

                <img
                    src="../assets/plane_radar_transparent.svg"
                    alt="OnTheRadar logo"
                    class="form-logo"
                >

                <h1>Verify Your Email</h1>

                <p class="form-tagline">Stay on the radar</p>

                <!-- rma9: Shows the email address that received the verification code. -->
                <p>
                    Enter the 6-digit verification code sent to:

                    <strong>
                        <?= htmlspecialchars(
                            $email,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </strong>
                </p>

                <?php if ($success): ?>
                    <!-- rma9: Confirms that a fresh verification code was sent. -->
                    <div class="auth-success" role="status">
                        <?= htmlspecialchars(
                            $success,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <!-- rma9: Safely displays an invalid or expired code message. -->
                    <div class="auth-error" role="alert">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </div>
                <?php endif; ?>

                <!-- rma9: Sends the entered verification code to the processing page. -->
                <form method="post" action="verify_process.php">

                    <div class="form-group">
                        <label for="verification_code">
                            Verification Code
                        </label>

                        <div class="input-shell">
                            <input
                                id="verification_code"
                                name="verification_code"
                                type="text"
                                inputmode="numeric"
                                pattern="[0-9]{6}"
                                maxlength="6"
                                autocomplete="one-time-code"
                                placeholder="Enter 6-digit code"
                                required
                            >
                        </div>
                    </div>

                    <button
                        type="submit"
                        class="create-account-button"
                    >
                        Verify Email
                    </button>
                </form>

                <!-- rma9: Gives the user an option to request another verification code. -->
                <p class="login-text">
                    Didn’t receive a code?
                    <a href="resend_verification.php">
                        Resend Code
                    </a>
                </p>

                <!-- rma9: Allows the user to return to the registration page. -->
                <p class="login-text">
                    <a href="register.php">
                        Back to registration
                    </a>
                </p>

            </section>
        </main>

        <!-- rma9: Displays the shared OTR footer. -->
        <footer class="site-footer">
            OnTheRadar
        </footer>

    </div>
</body>
</html>