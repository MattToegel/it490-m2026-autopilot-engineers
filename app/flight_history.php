<?php
// flight_history.php
// tad46: US-05 AC5 - full saved flight history page
// tad46: Shows every flight the user has EVER saved, active and removed,
// tad46: with saved/removed timestamps. Removed rows are the soft-deleted
// tad46: entries (removed_at set) that the watchlist filters out.
// tad46: Reuses the dashboard sidebar/header so it feels like the same app.

session_start();

require_once __DIR__ . '/auth/auth_protect.php';
require_once __DIR__ . '/flight/flight_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$username = htmlspecialchars($_SESSION['username'] ?? 'Traveler', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8');

// tad46: flight.list returns ALL rows (active + removed) - exactly what history needs
$listResponse = sendFlightRequest('flight.list',
[
    'user_id' => $currentUserId,
]);

$allFlights  = [];
$listError   = null;

if ($listResponse === null)
{
    $listError = 'Saved flights service is temporarily unavailable.';
}
elseif (($listResponse['status'] ?? '') === 'success')
{
    $allFlights = $listResponse['flights'] ?? [];
}
else
{
    $listError = $listResponse['message'] ?? 'Could not load your flight history.';
}

// tad46: counts for the summary row
$totalCount   = count($allFlights);
$activeCount  = count(array_filter($allFlights, fn($f) => empty($f['removed_at'])));
$removedCount = $totalCount - $activeCount;

// tad46: date formatter - MySQL timestamp to a friendlier display
function historyDate(?string $mysqlTimestamp): string
{
    if (!$mysqlTimestamp)
    {
        return '-';
    }
    $ts = strtotime($mysqlTimestamp);
    return $ts === false ? '-' : date('M j, Y g:i A', $ts);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight History | OnTheRadar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/public/dashboard_styles.css">

    <style>
        /* tad46: page-scoped additions for the history table */
        .history-chip
        {
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .history-chip--active
        {
            background: #e7f6e7;
            color: #1f6421;
        }

        .history-chip--removed
        {
            background: #eeeeF2;
            color: #555560;
        }

        /* tad46: removed rows recede visually but stay legible */
        .history-row--removed td
        {
            opacity: 0.6;
        }

        .history-summary
        {
            display: flex;
            gap: 22px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--otr-border);
            color: var(--otr-muted);
            font-size: 0.9rem;
        }

        .history-summary strong
        {
            color: var(--otr-blue);
        }
    </style>
</head>

<body>
    <div class="dashboard-page">

        <!-- ==================== TOP HEADER ==================== -->
        <header class="top-header">
            <a href="/dashboard.php" class="top-header__brand">
                <img src="/assets/otr-logo.svg" alt="OnTheRadar logo" class="top-header__logo">
                <span class="top-header__brand-name">OnTheRadar</span>
            </a>

            <nav class="top-header__nav" aria-label="Main navigation">
                <a href="/search.php" class="top-header__link">
                    <img src="/assets/search-icon.svg" alt="" aria-hidden="true">
                    <span>Search</span>
                </a>

                <a href="/dashboard.php#airport-conditions" class="top-header__link">
                    <img src="/assets/airport-map-icon.svg" alt="" aria-hidden="true">
                    <span>Airport Map</span>
                </a>

                <a href="/reports/reports.php" class="top-header__link">
                    <img src="/assets/community-icon.svg" alt="" aria-hidden="true">
                    <span>Community</span>
                </a>

                <button type="button" class="theme-toggle" aria-label="Toggle dark mode">
                    <span class="theme-toggle__circle"></span>
                </button>

                <a href="/dashboard.php#notifications" class="top-header__icon-button" aria-label="Notifications">
                    <img src="/assets/notification-icon.svg" alt="">
                </a>

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
            </nav>
        </header>

        <main class="dashboard-layout">

            <!-- tad46: same sidebar as dashboard.php; Stats is the active tab here -->
            <aside class="dashboard-sidebar" aria-label="Dashboard navigation">
                <div class="sidebar-profile">
                    <div class="sidebar-profile__avatar">
                        <img src="/assets/user-icon.svg" alt="">
                    </div>
                    <p class="sidebar-profile__name"><?php echo $username; ?></p>
                </div>

                <nav class="sidebar-menu">
                    <a href="/dashboard.php" class="sidebar-menu__link">
                        <img src="/assets/home_icon_dashboard.svg" alt="" aria-hidden="true">
                        <span>Dashboard</span>
                    </a>
                    <a href="/landing.php" class="sidebar-menu__link">
                        <img src="/assets/flight_dashboard_icon.svg" alt="" aria-hidden="true">
                        <span>Flight</span>
                    </a>
                    <a href="/flight_history.php" class="sidebar-menu__link sidebar-menu__link--active" aria-current="page">
                        <img src="/assets/tracked_trips.svg" alt="" aria-hidden="true">
                        <span>Stats</span>
                    </a>
                    <a href="/reports/reports.php" class="sidebar-menu__link">
                        <img src="/assets/reports_dashboard.svg" alt="" aria-hidden="true">
                        <span>Reports</span>
                    </a>
                    <a href="/auth/profile.php" class="sidebar-menu__link">
                        <img src="/assets/gear_dashboard.svg" alt="" aria-hidden="true">
                        <span>Settings</span>
                    </a>
                    <?php if ($role === 'admin'): ?>
                        <a href="/admin/admin.php" class="sidebar-menu__link">
                            <img src="/assets/user_dashboard.svg" alt="" aria-hidden="true">
                            <span>Admin</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </aside>

            <section class="dashboard-main" aria-label="Flight history content">

                <header class="dashboard-welcome">
                    <h1>Flight History</h1>
                </header>

                <!-- ==================== SAVED FLIGHT HISTORY (US-05 AC5) ==================== -->
                <section class="dashboard-panel" id="flight-history" aria-labelledby="flight-history-title">
                    <header class="dashboard-panel__header">
                        <h2 id="flight-history-title">All Saved Flights</h2>
                        <a href="/dashboard.php#tracked-flights" class="dashboard-panel__link">Back to watchlist</a>
                    </header>

                    <?php if ($listError): ?>
                        <div class="dashboard-alert dashboard-alert--error" role="alert">
                            <?php echo htmlspecialchars($listError, ENT_QUOTES, 'UTF-8'); ?>
                        </div>

                    <?php elseif ($totalCount === 0): ?>
                        <div class="tracked-flights-empty">
                            <img src="/assets/flight_dashboard_icon.svg" alt="" aria-hidden="true">
                            <h3>No flight history yet</h3>
                            <p>Flights you save will appear here, including ones you later remove.</p>
                            <a href="/search.php" class="dashboard-button">Search flights</a>
                        </div>

                    <?php else: ?>
                        <!-- tad46: summary counts - active vs removed at a glance -->
                        <div class="history-summary">
                            <span><strong><?php echo (int)$totalCount; ?></strong> total saved</span>
                            <span><strong><?php echo (int)$activeCount; ?></strong> currently tracking</span>
                            <span><strong><?php echo (int)$removedCount; ?></strong> removed</span>
                        </div>

                        <div class="tracked-flights-table-wrap">
                            <table class="tracked-flights-table">
                                <thead>
                                    <tr>
                                        <th>Flight</th>
                                        <th>Airline</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th>Saved</th>
                                        <th>Removed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allFlights as $flight): ?>
                                        <?php $isRemoved = !empty($flight['removed_at']); ?>
                                        <tr class="<?php echo $isRemoved ? 'history-row--removed' : ''; ?>">
                                            <td class="tracked-flights-table__flight-number">
                                                <?php echo htmlspecialchars($flight['flight_number'] ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($flight['airline'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($flight['departure_airport'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($flight['arrival_airport'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(historyDate($flight['saved_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(historyDate($flight['removed_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <?php if ($isRemoved): ?>
                                                    <span class="history-chip history-chip--removed">Removed</span>
                                                <?php else: ?>
                                                    <span class="history-chip history-chip--active">Tracking</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
        </main>

        <footer class="dashboard-footer">
            <span>OnTheRadar</span>
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
</body>
</html>