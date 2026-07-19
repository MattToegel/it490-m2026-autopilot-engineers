<?php
session_start();

require_once __DIR__ . '/auth_protect.php';
require_once __DIR__ . '/auth_client.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: profile.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($username === '' && $email === '' && $newPassword === '')
{
    header('Location: profile.php?error=' . urlencode('Please enter at least one change.'));
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
{
    header('Location: profile.php?error=' . urlencode('Please enter a valid email address.'));
    exit;
}

if ($newPassword !== '')
{
    if ($currentPassword === '')
    {
        header('Location: profile.php?error=' . urlencode('Current password is required to change your password.'));
        exit;
    }

    if ($newPassword !== $confirmPassword)
    {
        header('Location: profile.php?error=' . urlencode('New passwords do not match.'));
        exit;
    }

    if (strlen($newPassword) < 8)
    {
        header('Location: profile.php?error=' . urlencode('New password must be at least 8 characters.'));
        exit;
    }
}

$payload = [
    'user_id' => $_SESSION['user_id'],
];

if ($username !== '')
{
    $payload['username'] = $username;
}

if ($email !== '')
{
    $payload['email'] = $email;
}

if ($newPassword !== '')
{
    $payload['current_password'] = $currentPassword;
    $payload['new_password'] = $newPassword;
}

try
{
    $response = sendAuthRequest('user.update_profile', $payload);
}
catch (Throwable $e)
{
    header('Location: profile.php?error=' . urlencode('The profile service is currently unavailable.'));
    exit;
}

if (!$response)
{
    header('Location: profile.php?error=' . urlencode('The profile update request timed out.'));
    exit;
}

if (($response['status'] ?? '') === 'success')
{
    if ($username !== '')
    {
        $_SESSION['username'] = $username;
    }

    $message = $response['message'] ?? 'Profile updated successfully.';

    if ($message === 'no changes applied')
    {
        $message = 'No changes were needed.';
    }
    else
    {
        $message = 'Profile updated successfully.';
    }

    header('Location: profile.php?success=' . urlencode($message));
    exit;
}

$message = $response['message'] ?? 'Unable to update profile.';

if (
    $message === 'current password is incorrect' ||
    $message === 'current password incorrect'
)
{
    $message = 'Your current password is incorrect.';
}
elseif (
    $message === 'email already in use' ||
    $message === 'username already in use' ||
    $message === 'email or username already taken'
)
{
    $message = 'That email or username is already in use.';
}
elseif ($message === 'no fields to update')
{
    $message = 'Please enter at least one change.';
}

header('Location: profile.php?error=' . urlencode($message));
exit;
