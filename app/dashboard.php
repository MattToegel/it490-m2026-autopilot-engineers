<?php
// dashboard.php
// tad46: Protected user dashboard for the App VM
// tad46: Dashboard markup matches OnTheRadar visual style
// tad46: Surfaces MVP US-05 saved-flight management, US-02 flight search entry, and US-03 own reports
// tad46: US-05 saved-flights list/remove wired to DB VM through flight_client.php
// tad46: US-03 own reports wired to DB VM through report_client.php (user_id filter)
// tad46: Drawer partial provides the sitewide "post a report" affordance
// rma9: Updated dashboard layout and intentional map placeholder while map API work is in progress

session_start();

require_once __DIR__ . '/auth/auth_protect.php';
require_once __DIR__ . '/flight/flight_client.php';
require_once __DIR__ . '/reports/report_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$username = htmlspecialchars($_SESSION['username'] ?? 'Traveler', ENT_QUOTES, 'UTF-8');
$role = htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8');

// tad46: ------ US-05 saved flights (LIVE from DB VM) ------
$listResponse = sendFlightRequest('flight.list',
[
    'user_id' => $currentUserId,
]);

$savedFlights = [];
$savedListError = null;

if ($listResponse === null)
{
    $savedListError = 'Saved flights service is temporarily unavailable.';
}
elseif (($listResponse['status'] ?? '') === 'success')
{
    foreach ($listResponse['flights'] ?? [] as $row)
    {
        $savedFlights[] = [
            'saved_flight_id' => (int)$row['saved_flight_id'],
            'flight' => $row['flight_number'],
            'route' => trim(
                ($row['departure_airport'] ?? '')
                . ' to '
                . ($row['arrival_airport'] ?? ''),
                ' to '
            ) ?: '-',
            'status' => 'Awaiting update',
            'dept' => '-',
            'arriv' => '-',
            'updated' => '-',
            'active' => true,
        ];
    }
}
else
{
    $savedListError = $listResponse['message'] ?? 'Could not load saved flights.';
}

// tad46: ------ unsave outcome banner ------
$unsaveNotice = null;

if (isset($_GET['unsave']))
{
    if ($_GET['unsave'] === 'success')
    {
        $unsaveNotice = [
            'type' => 'success',
            'text' => 'Flight removed from your watchlist.',
        ];
    }
    else
    {
        $unsaveNotice = [
            'type' => 'error',
            'text' => 'Could not remove that flight. Please try again.',
        ];
    }
}

// tad46: ------ US-03 own reports (LIVE, filtered by user_id) ------
$myReports = [];
$myReportsError = null;

$myReportsResp = sendReportRequest('report.list',
[
    'user_id' => $currentUserId,
    'limit' => 10,
]);

if ($myReportsResp === null)
{
    $myReportsError = 'Reports service is temporarily unavailable.';
}
elseif (($myReportsResp['status'] ?? '') === 'success')
{
    $myReports = $myReportsResp['reports'] ?? [];
}
else
{
    $myReportsError = $myReportsResp['message'] ?? 'Could not load your reports.';
}

// tad46: ------ report action notices ------
$reportNotice = null;

if (isset($_GET['report_posted']))
{
    $reportNotice = [
        'type' => 'success',
        'text' => 'Your report has been posted.',
    ];
}

if (isset($_GET['report_updated']))
{
    $reportNotice = [
        'type' => 'success',
        'text' => 'Your report has been updated.',
    ];
}

if (isset($_GET['report_deleted']))
{
    $reportNotice = [
        'type' => 'success',
        'text' => 'Your report has been removed.',
    ];
}

if (isset($_GET['report_error']))
{
    $reportNotice = [
        'type' => 'error',
        'text' => match ($_GET['report_error'])
        {
            'category' => 'Please choose a category.',
            'empty' => 'A comment is required.',
            'length' => 'Comment is too long.',
            'missing' => 'Missing report information.',
            'service' => 'The reports service is temporarily unavailable.',
            default => 'Something went wrong. Please try again.',
        },
    ];
}

// tad46: ------ drawer edit pre-fill (when Edit clicked on this page) ------
$editingReport = null;

if (!empty($_GET['report_edit']))
{
    $editReportId = filter_input(INPUT_GET, 'report_edit', FILTER_VALIDATE_INT);

    if ($editReportId)
    {
        foreach ($myReports as $report)
        {
            if ((int)$report['report_id'] === $editReportId)
            {
                $editingReport = $report;
                break;
            }
        }
    }
}

// tad46: ------ US-02 flight search freshness (placeholder for now) ------
$apiAvailable = true;
$lastUpdated = '2026-07-15 14:32';

// tad46: ------ stats ------
$savedCount = count(array_filter(
    $savedFlights,
    fn(array $flight): bool => $flight['active']
));

$reportCount = count($myReports);
$alertCount = 2;
$searchCount = 4;

// helpers
function savedFlightStatusClass(string $status): string
{
    $lower = strtolower($status);

    if (str_contains($lower, 'cancel') || str_contains($lower, 'delay'))
    {
        return 'saved-status-bad';
    }

    if (str_contains($lower, 'board') || str_contains($lower, 'gate'))
    {
        return 'saved-status-warn';
    }

    if (str_contains($lower, 'on time') || str_contains($lower, 'landed'))
    {
        return 'saved-status-ok';
    }

    return '';
}

function reportCategoryClass(string $category): string
{
    return match (strtolower($category))
    {
        'tsa' => 'report-chip-tsa',
        'bathroom' => 'report-chip-bath',
        'accident' => 'report-chip-acc',
        'food' => 'report-chip-food',
        default => 'report-chip-muted',
    };
}

function timeAgo(string $mysqlTimestamp): string
{
    $timestamp = strtotime($mysqlTimestamp);

    if ($timestamp === false)
    {
        return 'recently';
    }

    $seconds = max(0, time() - $timestamp);

    if ($seconds < 60)
    {
        return 'just now';
    }

    if ($seconds < 3600)
    {
        $minutes = (int)floor($seconds / 60);
        return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
    }

    if ($seconds < 86400)
    {
        $hours = (int)floor($seconds / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }

    $days = (int)floor($seconds / 86400);
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
}

// tad46: variables for the drawer partial
$currentPagePath = '/dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | OnTheRadar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <!-- Dashboard-specific stylesheet only -->
    <link rel="stylesheet" href="/public/dashboard_styles.css">
    <link rel="stylesheet" href="/public/reports_styles.css">

</head>

<body>
    <div class="dashboard-page">

        <!-- ==================== TOP HEADER ==================== -->
        <header class="top-header">
            <a href="/dashboard.php" class="top-header__brand">
                <img
                    src="/assets/otr-logo.svg"
                    alt="OnTheRadar logo"
                    class="top-header__logo"
                >

                <span class="top-header__brand-name">OnTheRadar</span>
            </a>

            <nav class="top-header__nav" aria-label="Main navigation">
                <a href="/landing.php" class="top-header__link">
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

                <button
                    type="button"
                    class="theme-toggle"
                    aria-label="Toggle dark mode"
                >
                    <span class="theme-toggle__circle"></span>
                </button>

                <button
                    type="button"
                    class="top-header__icon-button"
                    aria-label="Notifications"
                >
                    <img src="/assets/notification-icon.svg" alt="">
                </button>

                <div class="user-menu">
    <button
        type="button"
        class="top-header__icon-button"
        id="userMenuButton"
        aria-label="User menu"
        aria-expanded="false"
    >
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

        <a href="/auth/logout.php" class="logout-link">
            Log Out
        </a>
    </div>
</div>
            </nav>
        </header>

        <!-- ==================== DASHBOARD LAYOUT ==================== -->
        <main class="dashboard-layout">

            <!-- ==================== LEFT SIDEBAR ==================== -->
            <aside class="dashboard-sidebar" aria-label="Dashboard navigation">
                <div class="sidebar-profile">
                    <div class="sidebar-profile__avatar">
                        <img src="/assets/user-icon.svg" alt="">
                    </div>

                    <p class="sidebar-profile__name">
                        <?php echo $username; ?>
                    </p>
                </div>

                <nav class="sidebar-menu">
                    <a
                        href="/dashboard.php"
                        class="sidebar-menu__link sidebar-menu__link--active"
                        aria-current="page"
                    >
                        <img src="/assets/home_icon_dashboard.svg" alt="" aria-hidden="true">
                        <span>Dashboard</span>
                    </a>

                    <a href="/landing.php" class="sidebar-menu__link">
                        <img src="/assets/flight_dashboard_icon.svg" alt="" aria-hidden="true">
                        <span>Flight</span>
                    </a>

                    <a href="#dashboard-stats" class="sidebar-menu__link">
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

            <!-- ==================== RIGHT CONTENT ==================== -->
            <section class="dashboard-main" aria-label="Dashboard content">

                <!-- Greeting -->
                <header class="dashboard-welcome">
                    <h1>Hello <?php echo $username; ?>!</h1>
                </header>

                <?php if (!$apiAvailable): ?>
                    <div class="dashboard-alert dashboard-alert--warning" role="status">
                        Live flight data is unavailable right now. Showing the latest saved information.
                    </div>
                <?php endif; ?>

                <!-- ==================== FOUR FIGMA STAT CARDS ==================== -->
                <section
                    class="dashboard-stats"
                    id="dashboard-stats"
                    aria-label="Dashboard statistics"
                >
                    <article class="metric-card">
                        <div class="metric-card__icon">
                            <img
                                src="/assets/tracked_trips.svg"
                                alt=""
                                aria-hidden="true"
                            >
                        </div>

                        <div class="metric-card__content">
                            <p class="metric-card__value">
                                <?php echo (int)$savedCount; ?>
                            </p>

                            <p class="metric-card__label">
                                Flights<br>tracked
                            </p>
                        </div>
                    </article>

                    <article class="metric-card">
                        <div class="metric-card__icon">
                            <img
                                src="/assets/saved_flights.svg"
                                alt=""
                                aria-hidden="true"
                            >
                        </div>

                        <div class="metric-card__content">
                            <p class="metric-card__value">
                                <?php echo (int)$searchCount; ?>
                            </p>

                            <p class="metric-card__label">
                                Total<br>searches
                            </p>
                        </div>
                    </article>

                    <article class="metric-card">
                        <div class="metric-card__icon">
                            <img
                                src="/assets/active_alerts.svg"
                                alt=""
                                aria-hidden="true"
                            >
                        </div>

                        <div class="metric-card__content">
                            <p class="metric-card__value">
                                <?php echo (int)$alertCount; ?>
                            </p>

                            <p class="metric-card__label">
                                Flight<br>alerts
                            </p>
                        </div>
                    </article>

                    <article class="metric-card">
                        <div class="metric-card__icon">
                            <img
                                src="/assets/report_dashboard.svg"
                                alt=""
                                aria-hidden="true"
                            >
                        </div>

                        <div class="metric-card__content">
                            <p class="metric-card__value">
                                <?php echo (int)$reportCount; ?>
                            </p>

                            <p class="metric-card__label">
                                Reports<br>posted
                            </p>
                        </div>
                    </article>
                </section>

                <!-- ==================== MAIN FIGMA CONTENT GRID ==================== -->
                <div class="dashboard-content-grid">

                    <!-- LEFT COLUMN -->
                    <div class="dashboard-content-grid__left">

                        <!-- Tracked Flights -->
                        <section
                            class="dashboard-panel tracked-flights-panel"
                            id="tracked-flights"
                            aria-labelledby="tracked-flights-title"
                        >
                            <header class="dashboard-panel__header">
                                <h2 id="tracked-flights-title">Tracked Flights</h2>
                            </header>

                            <?php if ($unsaveNotice): ?>
                                <?php
                                $unsaveNoticeClass = $unsaveNotice['type'] === 'success'
                                    ? 'dashboard-alert dashboard-alert--success'
                                    : 'dashboard-alert dashboard-alert--error';
                                ?>

                                <div class="<?php echo $unsaveNoticeClass; ?>" role="status">
                                    <?php
                                    echo htmlspecialchars(
                                        $unsaveNotice['text'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($savedListError): ?>
                                <div class="dashboard-alert dashboard-alert--error" role="alert">
                                    <?php
                                    echo htmlspecialchars(
                                        $savedListError,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </div>

                            <?php elseif (count($savedFlights) === 0): ?>
                                <div class="tracked-flights-empty">
                                    <img
                                        src="/assets/flight_dashboard_icon.svg"
                                        alt=""
                                        aria-hidden="true"
                                    >

                                    <h3>No tracked flights yet</h3>

                                    <p>
                                        Search for a flight and save it to begin tracking it.
                                    </p>

                                    <a href="/landing.php" class="dashboard-button">
                                        Search flights
                                    </a>
                                </div>

                            <?php else: ?>
                                <div class="tracked-flights-table-wrap">
                                    <table class="tracked-flights-table">
                                        <thead>
                                            <tr>
                                                <th>Flight</th>
                                                <th>From</th>
                                                <th>To</th>
                                                <th>Status</th>
                                                <th>Departure</th>
                                                <th>Arrival</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($savedFlights as $flight): ?>
                                                <?php
                                                $routeParts = array_map(
                                                    'trim',
                                                    explode(' to ', $flight['route'], 2)
                                                );

                                                $departureAirport = $routeParts[0] ?? '-';
                                                $arrivalAirport = $routeParts[1] ?? '-';
                                                ?>

                                                <tr>
                                                    <td class="tracked-flights-table__flight-number">
                                                        <?php
                                                        echo htmlspecialchars(
                                                            $flight['flight'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        echo htmlspecialchars(
                                                            $departureAirport,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        echo htmlspecialchars(
                                                            $arrivalAirport,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <span
                                                            class="flight-status <?php echo savedFlightStatusClass($flight['status']); ?>"
                                                        >
                                                            <?php
                                                            echo htmlspecialchars(
                                                                $flight['status'],
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            );
                                                            ?>
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        echo htmlspecialchars(
                                                            $flight['dept'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php
                                                        echo htmlspecialchars(
                                                            $flight['arriv'],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        );
                                                        ?>
                                                    </td>

                                                    <td>
                                                        <?php if ($flight['active']): ?>
                                                            <form
                                                                method="post"
                                                                action="/flight/unsave_flight.php"
                                                                class="tracked-flight-remove-form"
                                                            >
                                                                <input
                                                                    type="hidden"
                                                                    name="saved_flight_id"
                                                                    value="<?php echo (int)$flight['saved_flight_id']; ?>"
                                                                >

                                                                <button
                                                                    type="submit"
                                                                    class="tracked-flight-remove"
                                                                >
                                                                    Remove
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </section>

                        <!-- Information panel -->
                        <section
                            class="dashboard-panel information-panel"
                            aria-labelledby="information-title"
                        >
                            <header class="dashboard-panel__header">
                                <h2 id="information-title">Information</h2>
                            </header>

                            <div class="information-panel__content">
                                <img
                                    src="/assets/notification-icon.svg"
                                    alt=""
                                    aria-hidden="true"
                                    class="information-panel__icon"
                                >

                                <div>
                                    <h3>Stay updated throughout your trip</h3>

                                    <p>
                                        Track flights to receive status, delay, cancellation,
                                        departure, arrival, and gate-change updates.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <aside
                        class="airport-conditions-panel"
                        id="airport-conditions"
                        aria-labelledby="airport-conditions-title"
                    >
                        <header class="airport-conditions-panel__header">
                            <div>
                                <p>EWR</p>
                                <h2 id="airport-conditions-title">
                                    Airport Conditions
                                </h2>
                            </div>

                            <img
                                src="/assets/airport-map-icon.svg"
                                alt=""
                                aria-hidden="true"
                            >
                        </header>

                        <div class="airport-conditions-panel__map">
                            <div class="airport-conditions-panel__map-placeholder">
                                <img
                                    src="/assets/airport-map-icon.svg"
                                    alt=""
                                    aria-hidden="true"
                                >

                                <p>Live airport map integration is in progress.</p>
                            </div>
                        </div>

                        <div class="airport-conditions-panel__details">
                            <div class="airport-condition-row">
                                <span>Airport</span>
                                <strong>Newark Liberty International</strong>
                            </div>

                            <div class="airport-condition-row">
                                <span>Code</span>
                                <strong>EWR</strong>
                            </div>

                            <div class="airport-condition-row">
                                <span>Tracked flights</span>
                                <strong><?php echo (int)$savedCount; ?></strong>
                            </div>

                            <div class="airport-condition-row">
                                <span>Community reports</span>
                                <strong><?php echo (int)$reportCount; ?></strong>
                            </div>

                            <div class="airport-condition-row">
                                <span>Last updated</span>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $lastUpdated,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <a
                            href="/reports/reports.php"
                            class="airport-conditions-panel__button"
                        >
                            View airport reports
                        </a>
                    </aside>
                </div>

                <!-- ==================== USER REPORTS ==================== -->
                <section
                    class="dashboard-panel user-reports-panel"
                    aria-labelledby="user-reports-title"
                >
                    <header class="dashboard-panel__header">
                        <h2 id="user-reports-title">My Reports</h2>

                        <a href="/reports/reports.php" class="dashboard-panel__link">
                            View Community
                        </a>
                    </header>

                    <?php if ($reportNotice): ?>
                        <?php
                        $reportNoticeClass = $reportNotice['type'] === 'success'
                            ? 'dashboard-alert dashboard-alert--success'
                            : 'dashboard-alert dashboard-alert--error';
                        ?>

                        <div class="<?php echo $reportNoticeClass; ?>" role="status">
                            <?php
                            echo htmlspecialchars(
                                $reportNotice['text'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($myReportsError): ?>
                        <div class="dashboard-alert dashboard-alert--error" role="alert">
                            <?php
                            echo htmlspecialchars(
                                $myReportsError,
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>
                        </div>

                    <?php elseif (count($myReports) === 0): ?>
                        <div class="user-reports-empty">
                            <p>You have not posted any airport reports yet.</p>
                        </div>

                    <?php else: ?>
                        <ul class="user-reports-list">
                            <?php foreach ($myReports as $report): ?>
                                <li class="user-report">
                                    <div class="user-report__top">
                                        <span
                                            class="user-report__category <?php echo reportCategoryClass($report['category']); ?>"
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $report['category'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                        </span>

                                        <?php if (!empty($report['terminal'])): ?>
                                            <span class="user-report__terminal">
                                                Terminal
                                                <?php
                                                echo htmlspecialchars(
                                                    $report['terminal'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                                ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="user-report__time">
                                            <?php
                                            echo htmlspecialchars(
                                                timeAgo($report['created_at']),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                            ?>
                                        </span>
                                    </div>

                                    <p class="user-report__text">
                                        <?php
                                        echo nl2br(
                                            htmlspecialchars(
                                                $report['comment_text'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        );
                                        ?>
                                    </p>

                                    <div class="user-report__actions">
                                        <a
                                            href="?report_edit=<?php echo (int)$report['report_id']; ?>"
                                            class="user-report__edit"
                                        >
                                            Edit
                                        </a>

                                        <form
                                            method="post"
                                            action="/reports/delete_report.php"
                                            onsubmit="return confirm('Remove this report?');"
                                        >
                                            <input
                                                type="hidden"
                                                name="return_to"
                                                value="<?php echo $currentPagePath; ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="report_id"
                                                value="<?php echo (int)$report['report_id']; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="user-report__delete"
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </section>
            </section>
        </main>

        <footer class="dashboard-footer">
            <span>OnTheRadar</span>
        </footer>
    </div>

    <script src="/public/dashboard_script.js"></script>

    <script>
const userButton = document.getElementById("userMenuButton");
const userDropdown = document.getElementById("userDropdown");

if (userButton && userDropdown)
{
    userButton.addEventListener("click", function(e)
    {
        e.stopPropagation();

        userDropdown.classList.toggle("show");

        userButton.setAttribute(
            "aria-expanded",
            userDropdown.classList.contains("show")
        );
    });

    document.addEventListener("click", function(e)
    {
        if (
            !userButton.contains(e.target) &&
            !userDropdown.contains(e.target)
        )
        {
            userDropdown.classList.remove("show");
            userButton.setAttribute("aria-expanded", "false");
        }
    });
}
</script>
    
<?php include __DIR__ . '/reports/reports_drawer.php'; ?>
</body>
</html>