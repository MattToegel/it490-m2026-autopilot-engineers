<?php
// unsave_flight.php
// tad46: Removes a saved flight for the current user, then redirects back to the dashboard
// tad46: Follows the same post-then-redirect pattern used by login.php and register.php

session_start();

// tad46: Bounce unauthenticated visitors so they can't fire unsave requests
require_once __DIR__ . '/../auth/auth_protect.php';

require_once __DIR__ . '/flight_client.php';

// tad46: Only process POST requests - a stray GET should just redirect back
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: ../dashboard.php');
    exit;
}

$savedFlightId = filter_input(INPUT_POST, 'saved_flight_id', FILTER_VALIDATE_INT);

// tad46: If the ID is missing or not a valid integer, redirect back with an error flag
// tad46: The dashboard reads ?unsave=error from the URL and shows a banner
if (!$savedFlightId)
{
    header('Location: ../dashboard.php?unsave=error');
    exit;
}

// tad46: Publish flight.unsave to the DB VM and wait for the reply
// tad46: The DB handler enforces ownership (WHERE saved_flight_id = ? AND user_id = ?)
// tad46: so a user can't remove another user's flight even by guessing the ID
$response = sendFlightRequest('flight.unsave',
[
    'user_id'         => $_SESSION['user_id'],
    'saved_flight_id' => $savedFlightId,
]);

// tad46: Redirect back with a status flag so the dashboard can show a confirmation
if ($response && ($response['status'] ?? '') === 'success')
{
    header('Location: ../dashboard.php?unsave=success');
    exit;
}

// tad46: Anything else (null timeout, error status, not found) goes back with an error flag
header('Location: ../dashboard.php?unsave=error');
exit;