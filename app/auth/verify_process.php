<?php

// rma9: Starts the session so we can access the email saved during registration.
if (session_status() !== PHP_SESSION_ACTIVE)
{
    session_start();
}

// rma9: Loads the RabbitMQ authentication client.
require_once __DIR__ . '/auth_client.php';

// rma9: Prevents direct access unless the form was submitted.
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: verify.php');
    exit;
}

// rma9: Gets the pending email stored after registration.
$email = $_SESSION['pending_verification_email'] ?? '';

// rma9: Gets the six-digit code entered by the user.
$verificationCode = trim(
    $_POST['verification_code'] ?? ''
);

// rma9: Sends users back to registration when no pending email exists.
if ($email === '')
{
    header('Location: register.php');
    exit;
}

// rma9: Checks that the code contains exactly six numbers.
if (!preg_match('/^\d{6}$/', $verificationCode))
{
    $_SESSION['verification_error'] =
        'Please enter a valid 6-digit verification code.';

    header('Location: verify.php');
    exit;
}

try
{
    // rma9: Sends the entered code to the DB auth consumer through RabbitMQ.
    $response = sendAuthRequest(
        'user.verify',
        [
            'email' => $email,
            'verification_code' => $verificationCode
        ]
    );

    // rma9: Handles a timeout or unavailable DB auth consumer.
    if ($response === null)
    {
        $_SESSION['verification_error'] =
            'Verification service is unavailable. Please try again.';

        header('Location: verify.php');
        exit;
    }

    // rma9: Clears the pending email after successful verification.
    if (($response['status'] ?? '') === 'success')
    {
        unset($_SESSION['pending_verification_email']);
        unset($_SESSION['verification_error']);

        $_SESSION['login_success'] =
            'Email verified successfully. You can now log in.';

        header('Location: login.php');
        exit;
    }

    // rma9: Displays the DB response for an incorrect or expired code.
    $_SESSION['verification_error'] =
        $response['message'] ?? 'Email verification failed.';

    header('Location: verify.php');
    exit;
}
catch (Throwable $exception)
{
    // rma9: Prevents technical RabbitMQ errors from appearing in the browser.
    $_SESSION['verification_error'] =
        'Unable to verify your email right now. Please try again.';

    header('Location: verify.php');
    exit;
}
