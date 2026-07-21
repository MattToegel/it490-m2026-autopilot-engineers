<?php
// save_flight.php
// tad46: Adds a flight to the current user's watchlist, then redirects back
// tad46: Follows the same post-then-redirect pattern as unsave_flight.php
// tad46: The flight_number typically comes from a search result the user clicked "Save" on

session_start();

// tad46: Bounce unauthenticated visitors so they can't fire save requests
require_once __DIR__ . '/../auth/auth_protect.php';

require_once __DIR__ . '/flight_client.php';
require_once __DIR__ . '/../logging/app_log.php';

// tad46: Only process POST requests - a stray GET should just redirect back
if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    header('Location: /../dashboard.php');
    exit;
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);

// tad46: Where to send the user back after the save
// tad46: Likely a search results page or the landing page - honor the caller
$returnTo = $_POST['return_to'] ?? '/../dashboard.php';

// tad46: Only allow local paths so this can't redirect off-site
if (!preg_match('#^/[^/]#', $returnTo))
{
    $returnTo = '/../dashboard.php';
}

// tad46: return_to may already carry a query string (e.g. /search.php?q=UA124),
// tad46: so pick the right separator for appending the save outcome flag
$sep = (strpos($returnTo, '?') !== false) ? '&' : '?';

// tad46: Required - the flight number the user wants to track
$flightNumber = trim($_POST['flight_number'] ?? '');

if ($flightNumber === '')
{
    header('Location: ' . $returnTo . $sep . 'save=missing');
    exit;
}

// tad46: Optional fields - if the caller had them from a search result, pass them along
// tad46: The DB handler stores them; if missing they default to null
$airline          = trim($_POST['airline']           ?? '');
$departureAirport = trim($_POST['departure_airport'] ?? '');
$arrivalAirport   = trim($_POST['arrival_airport']   ?? '');

publishAppLog('info', "Save flight submitted by user_id={$currentUserId} for flight={$flightNumber}");

// tad46: Publish flight.save to the DB VM and wait for the reply
// tad46: The DB handler enforces uniqueness via UNIQUE KEY (user_id, flight_number)
// tad46: so a user cannot save the same flight twice
$response = sendFlightRequest('flight.save',
[
    'user_id'           => $currentUserId,
    'flight_number'     => $flightNumber,
    'airline'           => $airline           ?: null,
    'departure_airport' => $departureAirport  ?: null,
    'arrival_airport'   => $arrivalAirport    ?: null,
]);

// tad46: Success - the flight is now on the user's watchlist
if ($response && ($response['status'] ?? '') === 'success')
{
    publishAppLog('info', "Flight saved: saved_flight_id=" . ($response['saved_flight_id'] ?? '?'));
    header('Location: ' . $returnTo . $sep . 'save=success');
    exit;
}

// tad46: Duplicate save is a common outcome worth calling out specifically
// tad46: (DB returns 'flight already saved' in that case)
$backendMessage = $response['message'] ?? null;

if ($backendMessage === 'flight already saved')
{
    header('Location: ' . $returnTo . $sep . 'save=duplicate');
    exit;
}

// tad46: Anything else (null timeout, other error) goes back with a generic error flag
publishAppLog('warning', 'Save flight failed: ' . ($backendMessage ?? 'no response'));
header('Location: ' . $returnTo . $sep . 'save=error');
exit;