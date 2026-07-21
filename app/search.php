<?php
// search.php
// xml: Original flight search page - search type form (flight/airport/route),
// xml: sendFlightRequest calls, response normalization, results table, changeSearch() JS
// tad46: Added ?q= support from the landing search bar with query type auto-detection
// tad46: Added Save button on each result row wired to /flight/save_flight.php (US-05 AC1)

session_start();

$isLoggedIn    = !empty($_SESSION['user_id']);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
require_once __DIR__ . '/flight/flight_client.php';

$result  = null;
$error   = null;
$flights = [];

// tad46: figure out what the user is searching for
$query      = null;
$searchType = null;
$origin     = null;
$destination = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $searchType = $_POST['search_type'] ?? '';

    if ($searchType === 'flight')
    {
        $query = strtoupper(trim($_POST['flight_number'] ?? ''));
    }
    elseif ($searchType === 'airport')
    {
        $query = strtoupper(trim($_POST['airport'] ?? ''));
    }
    elseif ($searchType === 'route')
    {
        $origin      = strtoupper(trim($_POST['origin'] ?? ''));
        $destination = strtoupper(trim($_POST['destination'] ?? ''));
    }
}
elseif (!empty($_GET['q']))
{
    // tad46: auto-detect from the landing bar
    $raw = strtoupper(trim($_GET['q']));

    if (preg_match('#^([A-Z]{3})\s*(?:-|TO|→)\s*([A-Z]{3})$#', $raw, $m))
    {
        // "EWR-ATH", "EWR TO ATH", "EWR → ATH" -> route
        $searchType  = 'route';
        $origin      = $m[1];
        $destination = $m[2];
    }
    elseif (preg_match('#^[A-Z]{3}$#', $raw))
    {
        // three letters alone -> airport code
        $searchType = 'airport';
        $query      = $raw;
    }
    elseif (preg_match('#^[A-Z]{1,3}\s?\d{1,5}$#', $raw))
    {
        // airline prefix + digits -> flight number
        $searchType = 'flight';
        $query      = $raw;
    }
    else
    {
        // fall back to flight number search with whatever they typed
        $searchType = 'flight';
        $query      = $raw;
    }
}

// xml: search dispatch by type - original logic, tad46 wrapped it to accept both entry paths
if ($searchType)
{
    try
    {
        switch ($searchType)
        {
            case 'flight':
                if ($query === '') { throw new Exception('Enter a flight number.'); }
                $result = sendFlightRequest('search.flight',
                [
                    'routing_key' => 'search.flight',
                    'payload' => ['flight_number' => $query],
                ]);
                break;

            case 'airport':
                if ($query === '') { throw new Exception('Enter an airport code.'); }
                $result = sendFlightRequest('search.airport',
                [
                    'routing_key' => 'search.airport',
                    'payload' => ['airport' => $query],
                ]);
                break;

            case 'route':
                if (!$origin || !$destination) { throw new Exception('Enter both origin and destination.'); }
                $result = sendFlightRequest('search.route',
                [
                    'routing_key' => 'search.route',
                    'payload' => ['origin' => $origin, 'destination' => $destination],
                ]);
                break;

            default:
                throw new Exception('Invalid search type');
        }

        // tad46: guard against a null/empty response slipping through and being
        // tad46: mistaken for a clean zero-result search (sendFlightRequest should
        // tad46: throw instead of returning null, but this is a safety net)
        if ($result === null)
        {
            $error = 'An unexpected error occurred while contacting the flight service.';
        }
        // xml: If the API worker returned an error, display the message
        elseif (($result['status'] ?? '') === 'error')
        {
            $error = $result['message'] ?? 'An unexpected error occurred.';
        }
        else
        {
            // xml: normalize the response: API worker responses vary in shape
            $possible =
            [
                $result['flight']              ?? null,
                $result['flights']             ?? null,
                $result['payload']['flight']   ?? null,
                $result['payload']['flights']  ?? null,
                $result['payload']['results']  ?? null,
                $result['payload']['data']     ?? null,
                $result['payload']             ?? null,
            ];

            foreach ($possible as $item)
            {
                if (is_array($item) && !empty($item))
                {
                    // Multiple-flight response (airport or route search)
                    if (isset($item[0]) && is_array($item[0]))
                    {
                        $flights = $item;
                        break;
                    }

                    // Single-flight response (flight number search)
                    if (isset($item['flight_number']))
                    {
                        $flights[] = $item;
                        break;
                    }
                }
            }
        }
    }
    catch (Exception $e)
    {
        $error = $e->getMessage();
    }
}

// tad46: what to prefill in the form
$prefillFlight  = ($searchType === 'flight')  ? ($query ?? '') : '';
$prefillAirport = ($searchType === 'airport') ? ($query ?? '') : '';
$prefillOrigin  = $origin ?? '';
$prefillDest    = $destination ?? '';

// tad46: return_to for the Save buttons - preserve the current search
$returnTo = '/search.php';
if (!empty($_GET['q']))
{
    $returnTo .= '?q=' . urlencode($_GET['q']);
}

// tad46: save outcome banner
$saveNotice = null;
if (isset($_GET['save']))
{
    $saveNotice = match ($_GET['save'])
    {
        'success'   => ['type' => 'success', 'text' => 'Flight saved to your watchlist.'],
        'duplicate' => ['type' => 'error',   'text' => 'That flight is already on your watchlist.'],
        default     => ['type' => 'error',   'text' => 'Could not save that flight. Please try again.'],
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Search | OnTheRadar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* xml: original page structure and layout */
        *
        {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body
        {
            font-family: "Noto Sans Georgian", Arial, sans-serif;
            background: #f7f7fb;
            color: #17171c;
            width: 100%;
            min-height: 100vh;
        }

        .search-page
        {
            width: 100%;
            max-width: 100vh;
            margin: 40px auto;
        }

        .search-page__back
        {
            display: inline-block;
            margin-bottom: 18px;
            color: #080878;
            font-weight: 700;
            text-decoration: none;
        }

        .card
        {
            background: #ffffff;
            padding: 30px;
            border: 1px solid #d9d9e4;
            border-radius: 14px;
            box-shadow: 0 10px 30px rgba(8, 8, 120, 0.08);
            margin-bottom: 26px;
        }

        h1, h2
        {
            margin-bottom: 20px;
            color: #080878;
        }

        label
        {
            display: block;
            font-weight: 600;
            margin-top: 15px;
        }

        input, select
        {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            border: 1px solid #ccc;
            border-radius: 7px;
            font: inherit;
        }

        input:focus, select:focus
        {
            outline: none;
            border-color: #080878;
            box-shadow: 0 0 0 3px rgba(8, 8, 120, 0.14);
        }

        .search-submit
        {
            margin-top: 25px;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #080878;
            color: #ffffff;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }

        .search-submit:hover
        {
            background: #05054f;
        }

        .hidden
        {
            display: none;
        }

        table
        {
            width: 100%;
            border-collapse: collapse;
        }

        thead
        {
            background: #080878;
            color: #ffffff;
        }

        th, td
        {
            padding: 12px;
            text-align: left;
        }

        td
        {
            border-bottom: 1px solid #ddd;
        }

        tr:hover
        {
            background: #f5f5fa;
        }

        .flight-number-cell
        {
            color: #ef2b2d;
            font-weight: 700;
        }

        .save-flight-btn
        {
            padding: 8px 14px;
            border: none;
            border-radius: 7px;
            background: #080878;
            color: #ffffff;
            font: inherit;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
        }

        .save-flight-btn:hover
        {
            background: #05054f;
        }

        .notice
        {
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 22px;
            font-weight: 500;
        }

        .notice--success
        {
            border: 1px solid #b3ddb3;
            background: #e7f6e7;
            color: #1f6421;
        }

        .notice--error
        {
            border: 1px solid #efaaaa;
            background: #ffe7e7;
            color: #a31313;
        }
    </style>
</head>

<body>
    <div class="search-page">

        <a href="/landing.php" class="search-page__back">← Back to OnTheRadar</a>

        <?php if ($saveNotice): ?>
            <div class="notice notice--<?php echo $saveNotice['type']; ?>">
                <?php echo htmlspecialchars($saveNotice['text'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h1>Flight Search</h1>

            <form method="POST" action="/search.php">
                <label>Search Type</label>
                <select name="search_type" id="search_type" onchange="changeSearch()">
                    <option value="flight"  <?php echo $searchType === 'flight'  ? 'selected' : ''; ?>>Flight Number</option>
                    <option value="airport" <?php echo $searchType === 'airport' ? 'selected' : ''; ?>>Airport</option>
                    <option value="route"   <?php echo $searchType === 'route'   ? 'selected' : ''; ?>>Route</option>
                </select>

                <div id="flight" class="<?php echo ($searchType && $searchType !== 'flight') ? 'hidden' : ''; ?>">
                    <label>Flight Number</label>
                    <input name="flight_number" placeholder="UA124" value="<?php echo htmlspecialchars($prefillFlight, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div id="airport" class="<?php echo $searchType === 'airport' ? '' : 'hidden'; ?>">
                    <label>Airport Code</label>
                    <input name="airport" placeholder="EWR" value="<?php echo htmlspecialchars($prefillAirport, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div id="route" class="<?php echo $searchType === 'route' ? '' : 'hidden'; ?>">
                    <label>Origin</label>
                    <input name="origin" placeholder="EWR" value="<?php echo htmlspecialchars($prefillOrigin, ENT_QUOTES, 'UTF-8'); ?>">

                    <label>Destination</label>
                    <input name="destination" placeholder="ATH" value="<?php echo htmlspecialchars($prefillDest, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <button class="search-submit">Search</button>
            </form>
        </div>

        <?php if ($error): ?>
            <!-- tad46: API worker or validation error - always shown alone -->
            <div class="card notice--error">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </div>

        <?php elseif ($searchType && empty($flights)): ?>
            <!-- tad46: a search ran, came back clean, but matched nothing -->
            <div class="card">
                <h2>No flights found</h2>
                <p>Your search completed but no flights matched. Try a different flight number, airport, or route.</p>
            </div>

        <?php elseif (!empty($flights)): ?>
            <div class="card">
                <h2>Results</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Flight</th>
                            <th>Airline</th>
                            <th>Status</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Terminal</th>
                            <th>Departure</th>
                            <th>Aircraft</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($flights as $flight): ?>
                            <tr>
                                <td class="flight-number-cell">
                                    <?php echo htmlspecialchars($flight['flight_number'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><?php echo htmlspecialchars($flight['airline'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($flight['status'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($flight['departure_airport'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($flight['arrival_airport'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($flight['terminal'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($flight['scheduled_departure'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($flight['aircraft_model'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php if ($isLoggedIn && !empty($flight['flight_number'])): ?>
                                        <!-- tad46: US-05 AC1 - save this flight to the watchlist -->
                                        <form method="post" action="/flight/save_flight.php">
                                            <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="flight_number" value="<?php echo htmlspecialchars($flight['flight_number'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="airline" value="<?php echo htmlspecialchars($flight['airline'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="departure_airport" value="<?php echo htmlspecialchars($flight['departure_airport'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="arrival_airport" value="<?php echo htmlspecialchars($flight['arrival_airport'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="save-flight-btn">Save</button>
                                        </form>
                                    <?php elseif (!empty($flight['flight_number'])): ?>
                                        <!-- tad46: guests see a login link instead of the Save button -->
                                        <a href="/auth/login.php" class="save-flight-btn" style="text-decoration:none; display:inline-block;">
                                            Log in to save
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <script>
        // xml: original search type switcher
        function changeSearch()
        {
            let type = document.getElementById("search_type").value;

            document.getElementById("flight").classList.add("hidden");
            document.getElementById("airport").classList.add("hidden");
            document.getElementById("route").classList.add("hidden");

            document.getElementById(type).classList.remove("hidden");
        }
    </script>
</body>
</html>
