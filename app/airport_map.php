<?php
// airport_map.php
// ns87: US-03 stretch feature - Newark (EWR) terminal view backed by Google Places.
// ns87:
// ns87: ARCHITECTURE (matches the approved proposal, API 2 - Google Places):
// ns87:   App VM  -> app.requests exchange -> api.requests queue -> API VM worker
// ns87:   The API VM performs the Google Places call with the key in api/.env and
// ns87:   replies with terminal metadata. The App VM never holds a Google credential
// ns87:   and never calls Google directly from the browser.
// ns87:
// ns87: Terminal names and place IDs come from Places. The report counts layered on top
// ns87: come from this project's own US-03 pipeline via report.list, so a report an admin
// ns87: has flagged is excluded here too (AC6) - no user_id is sent, so reports_consumer
// ns87: applies the community rule.
// ns87:
// ns87: FAILURE HANDLING (proposal, API 2): "If for some reason Google Places API is not
// ns87: available, we can still store reports locally in our DB and reports already created
// ns87: can be viewed. If location data is not available, a message will alert the users
// ns87: that information cannot be retrieved at this time." That is implemented below -
// ns87: the page falls back to the locally stored terminal tags and shows a notice.

session_start();

require_once __DIR__ . '/auth/auth_protect.php';
require_once __DIR__ . '/places/places_client.php';
require_once __DIR__ . '/reports/report_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$username = htmlspecialchars($_SESSION['username'] ?? 'Traveler', ENT_QUOTES, 'UTF-8');
$role     = htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8');

// ns87: --- 1. terminal metadata from the API VM (Google Places) ---
$placesResponse = sendPlacesRequest('places.terminals', ['airport' => 'EWR']);

$terminals     = [];
$placesNotice  = null;
$placesLive    = false;

if ($placesResponse === null)
{
    $placesNotice = 'Airport location details cannot be retrieved at this time. '
                  . 'Showing terminals from the tags saved with each report.';
}
elseif (($placesResponse['status'] ?? '') === 'success' && !empty($placesResponse['terminals']))
{
    $placesLive = true;

    foreach ($placesResponse['terminals'] as $t)
    {
        $code = strtoupper(trim($t['code'] ?? ''));
        if ($code === '') { continue; }

        $terminals[$code] =
        [
            'code'     => $code,
            'name'     => $t['name']     ?? ('Terminal ' . $code),
            'place_id' => $t['place_id'] ?? null,
        ];
    }
}
else
{
    $placesNotice = $placesResponse['message']
        ?? 'Airport location details cannot be retrieved at this time. '
         . 'Showing terminals from the tags saved with each report.';
}

// ns87: fallback set - the same A/B/C values report_create.php validates and stores.
// ns87: Used when Places is unavailable so the page still works with local data only.
if (empty($terminals))
{
    foreach (['A', 'B', 'C'] as $code)
    {
        $terminals[$code] =
        [
            'code'     => $code,
            'name'     => 'Terminal ' . $code,
            'place_id' => null,
        ];
    }
}

// ns87: --- 2. live community reports (this project's own US-03 pipeline) ---
// ns87: no user_id -> reports_consumer returns report_status = 'active' only, so a
// ns87: flagged report is hidden here exactly as it is on the community feed (AC6).
$reportsResponse = sendReportRequest('report.list', ['limit' => 50]);

$reports      = [];
$reportsError = null;

if ($reportsResponse === null)
{
    $reportsError = 'Reports service is temporarily unavailable.';
}
elseif (($reportsResponse['status'] ?? '') === 'success')
{
    $reports = $reportsResponse['reports'] ?? [];
}
else
{
    $reportsError = $reportsResponse['message'] ?? 'Could not load airport conditions.';
}

// ns87: --- 3. group reports onto their terminal ---
// ns87: trim/strtoupper first because array keys are exact and case-sensitive; a stored
// ns87: value of "a" or " A" would otherwise miss its terminal and be counted as untagged.
$byTerminal = [];
foreach (array_keys($terminals) as $code)
{
    $byTerminal[$code] = [];
}
$untagged = [];

foreach ($reports as $r)
{
    $code = strtoupper(trim($r['terminal'] ?? ''));

    if ($code !== '' && isset($byTerminal[$code]))
    {
        $byTerminal[$code][] = $r;
    }
    else
    {
        // ns87: terminal is optional on the report form, so blank is normal
        $untagged[] = $r;
    }
}

$totalTagged = 0;
foreach ($byTerminal as $list)
{
    $totalTagged += count($list);
}

function terminalLoadClass(int $count): string
{
    if ($count >= 3) { return 'terminal-load--busy'; }
    if ($count >= 1) { return 'terminal-load--some'; }
    return 'terminal-load--quiet';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Airport Map | OnTheRadar</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="/public/dashboard_styles.css">

    <style>
        /* ns87: page-scoped styles, same approach flight_history.php uses */
        .airport-map-summary
        {
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            padding: 14px 20px;
            border-bottom: 1px solid var(--otr-border);
            color: var(--otr-muted);
            font-size: 0.9rem;
        }

        .airport-map-summary strong { color: var(--otr-blue); }

        .terminal-grid
        {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            padding: 18px 20px;
        }

        .terminal-card
        {
            border: 1px solid var(--otr-border);
            border-radius: 10px;
            background: #fff;
            overflow: hidden;
        }

        .terminal-card__head
        {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 13px 15px;
            border-bottom: 1px solid var(--otr-border);
        }

        .terminal-card__name
        {
            font-weight: 700;
            color: var(--otr-blue);
            font-size: 1.02rem;
        }

        .terminal-load
        {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .terminal-load--quiet { background: #e3f7ea; color: #1c6b3a; }
        .terminal-load--some  { background: #fff1d8; color: #855d11; }
        .terminal-load--busy  { background: #ffe0e0; color: #a11b1b; }

        .terminal-card__place-id
        {
            padding: 6px 15px;
            border-bottom: 1px solid var(--otr-border);
            background: #fafafc;
            font-size: 0.7rem;
            color: var(--otr-muted);
            word-break: break-all;
        }

        .terminal-card__reports { padding: 6px 15px 13px; }

        .terminal-report
        {
            padding: 9px 0;
            border-bottom: 1px solid #f0f0f4;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .terminal-report:last-child { border-bottom: 0; }

        .terminal-report__cat
        {
            display: inline-block;
            padding: 2px 8px;
            margin-bottom: 4px;
            border-radius: 999px;
            background: #ececff;
            color: #33338a;
            font-size: 0.68rem;
            font-weight: 700;
        }

        .terminal-report__meta
        {
            display: block;
            margin-top: 3px;
            color: var(--otr-muted);
            font-size: 0.74rem;
        }

        .terminal-card__empty
        {
            padding: 14px 0 4px;
            color: var(--otr-muted);
            font-size: 0.83rem;
        }

        .places-source-note
        {
            margin: 0;
            padding: 10px 20px;
            border-bottom: 1px solid var(--otr-border);
            font-size: 0.78rem;
            color: var(--otr-muted);
        }
    </style>
</head>

<body>
    <div class="dashboard-page">

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

                <a href="/airport_map.php" class="top-header__link">
                    <img src="/assets/airport-map-icon.svg" alt="" aria-hidden="true">
                    <span>Airport Map</span>
                </a>

                <a href="/reports/reports.php" class="top-header__link">
                    <img src="/assets/community-icon.svg" alt="" aria-hidden="true">
                    <span>Community</span>
                </a>

                <a href="/dashboard.php#notifications" class="top-header__icon-button" aria-label="Notifications">
                    <img src="/assets/notification-icon.svg" alt="">
                </a>

                <div class="user-menu">
                    <button type="button" class="top-header__icon-button" id="userMenuButton" aria-label="User menu" aria-expanded="false">
                        <img src="/assets/user-icon.svg" alt="">
                    </button>

                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-header"><?php echo $username; ?></div>
                        <a href="/dashboard.php">Dashboard</a>
                        <a href="/auth/profile.php">Settings</a>
                        <?php if ($role === 'admin'): ?>
                            <a href="/admin/admin_dashboard.php">Admin Panel</a>
                        <?php endif; ?>
                        <div class="user-dropdown-divider"></div>
                        <a href="/auth/logout.php" class="logout-link">Log Out</a>
                    </div>
                </div>
            </nav>
        </header>

        <main class="dashboard-layout">

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
                    <a href="/flight_history.php" class="sidebar-menu__link">
                        <img src="/assets/tracked_trips.svg" alt="" aria-hidden="true">
                        <span>Stats</span>
                    </a>
                    <a href="/airport_map.php" class="sidebar-menu__link sidebar-menu__link--active" aria-current="page">
                        <img src="/assets/airport-map-icon.svg" alt="" aria-hidden="true">
                        <span>Airport Map</span>
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
                        <a href="/admin/admin_dashboard.php" class="sidebar-menu__link">
                            <img src="/assets/user_dashboard.svg" alt="" aria-hidden="true">
                            <span>Admin</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </aside>

            <section class="dashboard-main" aria-label="Airport map content">

                <header class="dashboard-welcome">
                    <h1>Newark Liberty Airport Map</h1>
                </header>

                <section class="dashboard-panel" id="airport-map" aria-labelledby="airport-map-title">
                    <header class="dashboard-panel__header">
                        <h2 id="airport-map-title">Terminal Conditions</h2>
                        <a href="/reports/reports.php" class="dashboard-panel__link">View all reports</a>
                    </header>

                    <div class="airport-map-summary">
                        <span><strong><?php echo $totalTagged; ?></strong> reports tagged to a terminal</span>
                        <span><strong><?php echo count($untagged); ?></strong> without a terminal tag</span>
                        <span>Location data:
                            <strong><?php echo $placesLive ? 'Google Places (live)' : 'local report tags'; ?></strong>
                        </span>
                    </div>

                    <?php if ($placesNotice): ?>
                        <!-- ns87: proposal failure-handling plan for API 2 -->
                        <div class="dashboard-alert dashboard-alert--warning" role="status" style="margin: 14px 20px;">
                            <?php echo htmlspecialchars($placesNotice, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($reportsError): ?>
                        <div class="dashboard-alert dashboard-alert--error" role="alert" style="margin: 14px 20px;">
                            <?php echo htmlspecialchars($reportsError, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <p class="places-source-note">
                        Terminal names and place identifiers are requested from the Google Places API by the
                        API VM. Reports flagged by an administrator are not shown here, the same as on the
                        community feed.
                    </p>

                    <div class="terminal-grid">
                        <?php foreach ($terminals as $code => $meta): ?>
                            <?php $items = $byTerminal[$code] ?? []; ?>
                            <article class="terminal-card">
                                <div class="terminal-card__head">
                                    <span class="terminal-card__name">
                                        <?php echo htmlspecialchars($meta['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <span class="terminal-load <?php echo terminalLoadClass(count($items)); ?>">
                                        <?php echo count($items); ?>
                                        <?php echo count($items) === 1 ? 'report' : 'reports'; ?>
                                    </span>
                                </div>

                                <?php if (!empty($meta['place_id'])): ?>
                                    <!-- ns87: the Google place ID the proposal says we may store -->
                                    <div class="terminal-card__place-id">
                                        Place ID: <?php echo htmlspecialchars($meta['place_id'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="terminal-card__reports">
                                    <?php if (empty($items)): ?>
                                        <p class="terminal-card__empty">No active traveller reports for this terminal.</p>
                                    <?php else: ?>
                                        <?php foreach (array_slice($items, 0, 5) as $r): ?>
                                            <div class="terminal-report">
                                                <span class="terminal-report__cat">
                                                    <?php echo htmlspecialchars($r['category'] ?? 'General', ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <?php echo nl2br(htmlspecialchars($r['comment_text'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                                                <span class="terminal-report__meta">
                                                    <?php echo htmlspecialchars($r['username'] ?? 'A traveler', ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if (!empty($r['created_at'])): ?>
                                                        &middot; <?php echo htmlspecialchars(date('M j, g:i A', strtotime($r['created_at'])), ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <script>
        // ns87: user dropdown, same behaviour as the other pages
        const userButton = document.getElementById("userMenuButton");
        const userDropdown = document.getElementById("userDropdown");

        if (userButton && userDropdown)
        {
            userButton.addEventListener("click", function (e)
            {
                e.stopPropagation();
                userDropdown.classList.toggle("show");
                userButton.setAttribute("aria-expanded", userDropdown.classList.contains("show"));
            });

            document.addEventListener("click", function (e)
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
