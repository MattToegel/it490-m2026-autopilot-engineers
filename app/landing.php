<?php
// landing.php
// xml: Original landing page search box, airport card, live flight map, saved flights layout
// tad46: Made the search bar functional (submits to /search.php with ?q=)
// tad46: Replaced the static saved-flights placeholders with live flight.list data from the DB VM
// tad46: Landing is guest-viewable; saved flights panel and user menu gate on login

session_start();

require_once __DIR__ . '/flight/flight_client.php';

// tad46: login flag drives all conditional features on this page
$isLoggedIn    = !empty($_SESSION['user_id']);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$username = htmlspecialchars($_SESSION['username'] ?? 'Traveler', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8');

// tad46: Only fetched when logged in; guests skip the RPC round trip entirely
$savedFlights   = [];
$savedListError = null;

if ($isLoggedIn)
{
    $listResponse = sendFlightRequest('flight.list', ['user_id' => $currentUserId]);

    if ($listResponse === null)
    {
        $savedListError = 'Saved flights are temporarily unavailable.';
    }
    elseif (($listResponse['status'] ?? '') === 'success')
    {
        $savedFlights = $listResponse['flights'] ?? [];
    }
    else
    {
        $savedListError = $listResponse['message'] ?? 'Could not load saved flights.';
    }
}

// tad46: active count drives the empty state; removed (soft-deleted) rows don't count
$activeSavedCount = count(array_filter($savedFlights, fn($f) => empty($f['removed_at'])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnTheRadar</title>

    <!-- rma9: Apply the saved theme before rendering to prevent a light-mode flash. -->
    <script>
    (function ()
    {
    const savedTheme = localStorage.getItem("otr-theme");

    document.documentElement.setAttribute(
        "data-theme",
        savedTheme === "dark" ? "dark" : "light"
    );
    })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/public/landing_styles.css">
    <link rel="stylesheet" href="/public/notif_bell.css">
    <link rel="stylesheet" href="/public/dashboard_styles.css">

    <!-- rma9: Version number forces the browser to load updated contrast styles. -->
<link rel="stylesheet" href="/public/theme.css?v=10">
</head>

<body>
    <div class="dashboard-page">

        <!-- ==================== TOP HEADER ==================== -->
        <header class="top-header">
            <a href="/landing.php" class="top-header__brand">

                <!-- rma9: Use separate light and dark mode logo assets. -->
                <span class="top-header__logo-wrap">
                    <img
                        src="/assets/otr-logo.svg"
                        alt="OnTheRadar logo"
                        class="top-header__logo top-header__logo--light"
                    >

                    <img
                        src="/assets/otr-logo-dark.png"
                        alt="OnTheRadar logo"
                        class="top-header__logo top-header__logo--dark"
                    >
                </span>

                <span class="top-header__brand-name">
                    OnTheRadar
                </span>

            </a>

            <nav class="top-header__nav" aria-label="Main navigation">
                <a href="/search.php" class="top-header__link">
                    <img src="/assets/search-icon.svg" alt="" aria-hidden="true">
                    <span>Search</span>
                </a>

                <a href="#airport-conditions" class="top-header__link">
                    <img src="/assets/airport-map-icon.svg" alt="" aria-hidden="true">
                    <span>Airport Map</span>
                </a>

                <a href="/reports/reports.php" class="top-header__link">
                    <img src="/assets/community-icon.svg" alt="" aria-hidden="true">
                    <span>Community</span>
                </a>

                <!-- rma9: Shared landing-page toggle matching the Settings page toggle. -->
                <button
                    type="button"
                    class="theme-toggle"
                    data-theme-toggle
                    aria-label="Switch to dark mode"
                    aria-pressed="false"
                >
                    <!-- rma9: Shows the sun in light mode and moon in dark mode. -->
                    <span
                        class="theme-toggle__symbol"
                        aria-hidden="true"
                    >
                        ☀
                    </span>

                    <!-- rma9: White circle slides left or right when the theme changes. -->
                    <span class="theme-toggle__circle"></span>
                </button>

                <?php if ($isLoggedIn): ?>
                    <!-- tad46: notifications + user menu only make sense with a session -->
                    <!-- tad46: site-wide bell dropdown - see notif_bell.js / get_alerts.php -->
                    <div class="notif-menu">
                        <button type="button" class="top-header__icon-button bell-link" id="notifBellButton" aria-label="Notifications" aria-expanded="false">
                            <img src="/assets/notification-icon.svg" alt="">
                            <span class="bell-badge" id="notifBellBadge" style="display:none;"></span>
                        </button>

                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-dropdown-header">
                                <strong>Notifications</strong>
                                <span class="notifications-count" id="notifDropdownCount" style="display:none;"></span>
                            </div>
                            <div class="notif-dropdown-body" id="notifDropdownBody">
                                <div class="notif-dropdown-empty">Loading...</div>
                            </div>
                            <a href="/dashboard.php#notifications" class="notif-dropdown-viewall">View all in dashboard</a>
                        </div>
                    </div>

                    <div class="user-menu">
                        <button type="button" class="top-header__icon-button" id="userMenuButton" aria-label="User menu" aria-expanded="false">
                            <img src="/assets/user-icon.svg" alt="">
                        </button>

                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-dropdown-header">
                                <?= $username ?>
                            </div>
                            <a href="/dashboard.php">Dashboard</a>
                            <a href="/auth/profile.php">Settings</a>
                            <?php if ($role === 'admin'): ?>
                                <a href="/admin/admin.php">Admin Panel</a>
                            <?php endif; ?>
                            <div class="user-dropdown-divider"></div>
                            <a href="/auth/logout.php" class="logout-link">Log Out</a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- tad46: guests get a login link instead of the user menu -->
                    <a href="/auth/login.php" class="top-header__link">
                        <span>Log in</span>
                    </a>
                <?php endif; ?>
            </nav>
        </header>

        <main class="radar">

            <section class="hero-grid">

                <aside class="airport-card">
                    <h3>Newark Airport Intl.</h3>
                    <img src="images/airport-map.png" alt="EWR airport map preview">
                    <a href="#">View Map ↗</a>
                </aside>

                <section class="hero">
                    <h1>Stay<span>OnTheRadar</span></h1>

                    <!-- xml: original search box design -->
                    <form class="search-box" method="get" action="/search.php">
                        <img src="/assets/search-icon.svg" alt="" aria-hidden="true">
                        <input
                            type="text"
                            name="q"
                            placeholder="route, city, flight#"
                            autocomplete="off"
                            required
                        >
                    </form>
                </section>

            </section>

            <section class="dashboard-grid">

                <section class="map-panel">
                    <div class="panel-header">Live Flight Map</div>
                    <div id="map"></div>
                </section>

                <!-- xml: original saved flights panel design -->
                <!-- tad46: replaced static placeholder rows with LIVE data from the DB VM -->
                <aside class="saved-panel">
                    <h2>Your Saved Flights</h2>

                    <?php if (!$isLoggedIn): ?>
                        <div class="saved-flight saved-flight--empty">
                            <p>Log in to track flights.</p>
                            <p class="saved-flight__hint">Your watchlist and alerts live here once you're signed in.</p>
                        </div>

                        <a href="/auth/login.php" class="saved-panel__link">
                            Log in →
                        </a>

                    <?php elseif ($savedListError): ?>
                        <div class="saved-flight saved-flight--error">
                            <?php echo htmlspecialchars($savedListError, ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                        <a href="/dashboard.php#tracked-flights" class="saved-panel__link">
                            Manage in dashboard →
                        </a>

                    <?php elseif ($activeSavedCount === 0): ?>
                        <!-- tad46: empty state keys on ACTIVE count so users with only removed flights see this too -->
                        <div class="saved-flight saved-flight--empty">
                            <p>No saved flights yet.</p>
                            <p class="saved-flight__hint">Search for a flight above and save it to track it here.</p>
                        </div>

                        <a href="/dashboard.php#tracked-flights" class="saved-panel__link">
                            Manage in dashboard →
                        </a>

                    <?php else: ?>
                        <?php foreach ($savedFlights as $flight): ?>
                            <?php if (!empty($flight['removed_at'])) continue; // tad46: landing shows active flights only (removed rows stay in DB for AC5 history) ?>
                            <?php
                            $flightNumber = htmlspecialchars($flight['flight_number'] ?? '-', ENT_QUOTES, 'UTF-8');
                            $airline      = htmlspecialchars($flight['airline'] ?? '', ENT_QUOTES, 'UTF-8');

                            $route = trim(
                                ($flight['departure_airport'] ?? '')
                                . ' → '
                                . ($flight['arrival_airport'] ?? ''),
                                ' → '
                            );
                            $route = htmlspecialchars($route ?: 'Route unavailable', ENT_QUOTES, 'UTF-8');
                            ?>
                            <div class="saved-flight">
                                <span class="green"></span>
                                <div class="saved-flight__info">
                                    <strong><?php echo $flightNumber; ?></strong>
                                    <small>
                                        <?php echo $airline ? $airline . ' · ' : ''; ?><?php echo $route; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <a href="/dashboard.php#tracked-flights" class="saved-panel__link">
                            Manage in dashboard →
                        </a>
                    <?php endif; ?>
                </aside>

            </section>

        </main>

        <footer>
            OnTheRadar
        </footer>
    </div>

    <script>
        const userButton = document.getElementById("userMenuButton");
        const userDropdown = document.getElementById("userDropdown");

        if (userButton && userDropdown)
        {
            userButton.addEventListener("click", function(e)
            {
                e.stopPropagation();
                userDropdown.classList.toggle("show");
                userButton.setAttribute("aria-expanded", userDropdown.classList.contains("show"));
            });

            document.addEventListener("click", function(e)
            {
                if (!userButton.contains(e.target) && !userDropdown.contains(e.target))
                {
                    userDropdown.classList.remove("show");
                    userButton.setAttribute("aria-expanded", "false");
                }
            });
        }
        
    </script>

    <script src="/public/notif_bell.js"></script>

    <!-- rma9: Load shared theme behavior and restore the saved landing-page theme. -->
    <script src="/public/theme.js?v=5"></script>
</body>
</html>