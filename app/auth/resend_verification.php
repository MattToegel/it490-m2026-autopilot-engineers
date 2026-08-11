<?php

// rma9: Starts the session so we can access the pending verification email.
if (session_status() !== PHP_SESSION_ACTIVE)
{
    session_start();
}

// rma9: Loads the RabbitMQ authentication client.
require_once __DIR__ . '/auth_client.php';

// rma9: Gets the email saved after registration.
$email = $_SESSION['pending_verification_email'] ?? '';

// rma9: Sends the user back to registration if no account is pending.
if ($email === '')
{
    header('Location: register.php');
    exit;
}

try
{
    // rma9: Asks the DB consumer to generate and email a new code.
    $response = sendAuthRequest(
        'user.resend_verification',
        [
            'email' => $email
        ]
    );

    // rma9: Handles a timeout or unavailable DB consumer.
    if ($response === null)
    {
        $_SESSION['verification_error'] =
            'The verification service is unavailable. Please try again.';

        header('Location: verify.php');
        exit;
    }

    // rma9: Shows confirmation when the new code was generated successfully.
    if (($response['status'] ?? '') === 'success')
    {
        $_SESSION['verification_success'] =
            $response['message'] ?? 'A new verification code was sent.';

        header('Location: verify.php');
        exit;
    }

    // rma9: Shows an error returned by the DB consumer.
    $_SESSION['verification_error'] =
        $response['message'] ?? 'Unable to resend the verification code.';

    header('Location: verify.php');
    exit;
}
catch (Throwable $exception)
{
    // rma9: Prevents technical RabbitMQ errors from showing in the browser.
    $_SESSION['verification_error'] =
        'Unable to resend the verification code right now.';

    header('Location: verify.php');
    exit;
}
