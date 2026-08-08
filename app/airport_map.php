<?php
// airport_map.php
// ns87: Stretch feature - interactive Newark Liberty (EWR) terminal map.
// ns87:
// ns87: This is deliberately NOT a standalone widget. It reads the same live community
// ns87: reports as /reports/reports.php, over the same App -> RabbitMQ -> DB VM path,
// ns87: using the existing 'report.list' routing key. No new queues, no new bindings,
// ns87: no schema change. Every terminal pin on the map is backed by real rows in
// ns87: airport_reports on the DB VM.
// ns87:
// ns87: Because it sends no user_id, reports_consumer.php applies the community rule
// ns87: and returns report_status = 'active' rows only - so a report an admin has
// ns87: flagged (US-03 AC6) is hidden from this map too, exactly as it is hidden from
// ns87: the community feed. The moderation rule holds everywhere, not just on one page.

session_start();

require_once __DIR__ . '/auth/auth_protect.php';
require_once __DIR__ . '/reports/report_client.php';

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$username = htmlspecialchars($_SESSION['username'] ?? 'Traveler', ENT_QUOTES, 'UTF-8');
$role     = htmlspecialchars($_SESSION['role'] ?? 'user', ENT_QUOTES, 'UTF-8');

// ns87: EWR passenger terminals with their real approximate coordinates.
// ns87: The terminal keys match the values report_create.php stores, so a report
// ns87: tagged "Terminal B" lands on the Terminal B pin with no translation layer.
$terminals =
[
    'A' => [
        'name'  => 'Terminal A',
        'lat'   => 40.6839,
        'lng'   => -74.1739,
        'blurb' => 'Domestic - United, JetBlue, American',
    ],
    'B' => [
        'name'  => 'Terminal B',
        'lat'   => 40.6899,
        'lng'   => -74.1802,
        'blurb' => 'International arrivals and departures',
    ],
    'C' => [
        'name'  => 'Terminal C',
        'lat'   => 40.6952,
        'lng'   => -74.1770,
        'blurb' => 'United hub - domestic and international',
    ],
];

// ns87: same request the community feed makes. No user_id, so the DB VM returns
// ns87: active reports only (US-03 AC6).
$response = sendReportRequest('report.list', ['limit' => 50]);

$reports   = [];
$loadError = null;

if ($response === null)
{
    $loadError = 'Reports service is temporarily unavailable, so live conditions cannot be shown.';
}
elseif (($response['status'] ?? '') === 'success')
{
    $reports = $response['reports'] ?? [];
}
else
{
    $loadError = $response['message'] ?? 'Could not load airport conditions.';
}

// ns87: bucket the reports onto their terminal. Anything without a terminal tag is
// ns87: still counted, just shown in the list rather than pinned to a building.
$byTerminal   = ['A' => [], 'B' => [], 'C' => []];
$unassigned   = [];

foreach ($reports as $r)
{
    $t = strtoupper(trim($r['terminal'] ?? ''));

    if (isset($byTerminal[$t]))
    {
        $byTerminal[$t][] = $r;
    }
    else
    {
        $unassigned[] = $r;
    }
}

// ns87: build the payload the map script consumes. Everything is escaped here rather
// ns87: than in JavaScript, so nothing a user typed into a report can inject markup.
$mapPoints = [];

foreach ($terminals as $key => $meta)
{
    $items = [];

    foreach (array_slice($byTerminal[$key], 0, 6) as $r)
    {
        $items[] =
        [
            'category' => (string)($r['category'] ?? 'General'),
            'text'     => (string)($r['comment_text'] ?? ''),
            'author'   => (string)($r['username'] ?? 'A traveler'),
            'when'     => !empty($r['created_at'])
                ? date('M j, g:i A', strtotime($r['created_at']))
                : '',
        ];
    }

    $mapPoints[] =
    [
        'key'    => $key,
        'name'   => $meta['name'],
        'lat'    => $meta['lat'],
        'lng'    => $meta['lng'],
        'blurb'  => $meta['blurb'],
        'count'  => count($byTerminal[$key]),
        'items'  => $items,
    ];
}

$totalPinned = count($byTerminal['A']) + count($byTerminal['B']) + count($byTerminal['C']);

// ns87: busiest terminal drives the summary line at the top of the page
$busiest = null;
foreach ($mapPoints as $p)
{
    if ($busiest === null || $p['count'] > $busiest['count'])
    {
        $busiest = $p;
    }
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

    <!-- ns87: Leaflet + OpenStreetMap. No API key and no account needed, which keeps
         the VM free of credentials for a stretch feature. -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>

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

        .airport-map-summary strong
        {
            color: var(--otr-blue);
        }

        #ewr-map
        {
            height: 460px;
            width: 100%;
            border-radius: 8px;
            background: #eceff5;
            z-index: 0;
        }

        .airport-map-fallback
        {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 100%;
            padding: 30px;
            text-align: center;
            color: var(--otr-muted);
            font-size: 0.9rem;
        }

        .terminal-legend
        {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-top: 18px;
        }

        .terminal-card
        {
            padding: 14px 16px;
            border: 1px solid var(--otr-border);
            border-radius: 8px;
            background: #fff;
        }

        .terminal-card__head
        {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 5px;
        }

        .terminal-card__name
        {
            font-weight: 700;
            color: var(--otr-blue);
        }

        .terminal-card__count
        {
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .count-quiet  { background: #e3f7ea; color: #1c6b3a; }
        .count-some   { background: #fff1d8; color: #855d11; }
        .count-busy   { background: #ffe0e0; color: #a11b1b; }

        .terminal-card__blurb
        {
            font-size: 0.8rem;
            color: var(--otr-muted);
            line-height: 1.45;
        }

        /* ns87: Leaflet renders popups in its own container, so these live outside
           the normal cascade and need explicit sizing. */
        .map-popup__title
        {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 2px;
        }

        .map-popup__meta
        {
            font-size: 0.75rem;
            color: #666;
            margin-bottom: 8px;
        }

        .map-popup__item
        {
            padding: 6px 0;
            border-top: 1px solid #eee;
            font-size: 0.8rem;
            line-height: 1.45;
            max-width: 260px;
        }

        .map-popup__cat
        {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 999px;
            background: #ececff;
            color: #33338a;
            font-size: 0.68rem;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .map-popup__empty
        {
            font-size: 0.8rem;
            color: #666;
            padding-top: 6px;
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

            <!-- ns87: same sidebar as the dashboard so this reads as one application -->
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
                        <h2 id="airport-map-title">Live Terminal Conditions</h2>
                        <a href="/reports/reports.php" class="dashboard-panel__link">View all reports</a>
                    </header>

                    <div class="airport-map-summary">
                        <span><strong><?php echo $totalPinned; ?></strong> reports pinned to terminals</span>
                        <span><strong><?php echo count($unassigned); ?></strong> without a terminal tag</span>
                        <?php if ($busiest && $busiest['count'] > 0): ?>
                            <span>Most activity: <strong><?php echo htmlspecialchars($busiest['name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($loadError): ?>
                        <div class="dashboard-alert dashboard-alert--error" role="alert">
                            <?php echo htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php endif; ?>

                    <div style="padding: 18px 20px;">
                        <div id="ewr-map" role="application" aria-label="Map of Newark Liberty International Airport terminals">
                            <div class="airport-map-fallback" id="map-fallback">
                                <img src="/assets/airport-map-icon.svg" alt="" aria-hidden="true" width="34">
                                <p>Loading the terminal map&hellip;</p>
                            </div>
                        </div>

                        <!-- ns87: the same counts as text, so the page still communicates the
                             conditions if the tile server is unreachable from the VM -->
                        <div class="terminal-legend">
                            <?php foreach ($mapPoints as $p): ?>
                                <?php
                                $countClass = 'count-quiet';
                                if ($p['count'] >= 3)      { $countClass = 'count-busy'; }
                                elseif ($p['count'] >= 1)  { $countClass = 'count-some'; }
                                ?>
                                <article class="terminal-card">
                                    <div class="terminal-card__head">
                                        <span class="terminal-card__name">
                                            <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <span class="terminal-card__count <?php echo $countClass; ?>">
                                            <?php echo (int)$p['count']; ?>
                                            <?php echo $p['count'] === 1 ? 'report' : 'reports'; ?>
                                        </span>
                                    </div>
                                    <p class="terminal-card__blurb">
                                        <?php echo htmlspecialchars($p['blurb'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                </article>
                            <?php endforeach; ?>
                        </div>
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

    <script>
        // ns87: terminal data built server-side from live DB rows. JSON_HEX_* keeps any
        // ns87: user-submitted report text from breaking out of this script block.
        const TERMINALS = <?php
            echo json_encode(
                $mapPoints,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
        ?>;

        function escapeHtml(value)
        {
            const d = document.createElement("div");
            d.textContent = value == null ? "" : String(value);
            return d.innerHTML;
        }

        function popupHtml(t)
        {
            let html = '<div class="map-popup__title">' + escapeHtml(t.name) + '</div>';
            html += '<div class="map-popup__meta">' + escapeHtml(t.blurb) + '</div>';

            if (!t.items.length)
            {
                html += '<div class="map-popup__empty">No active traveller reports for this terminal right now.</div>';
                return html;
            }

            t.items.forEach(function (item)
            {
                html += '<div class="map-popup__item">';
                html += '<span class="map-popup__cat">' + escapeHtml(item.category) + '</span><br>';
                html += escapeHtml(item.text);
                html += '<br><small>' + escapeHtml(item.author);
                if (item.when) { html += ' &middot; ' + escapeHtml(item.when); }
                html += '</small></div>';
            });

            return html;
        }

        function markerColour(count)
        {
            if (count >= 3) { return "#d64545"; }
            if (count >= 1) { return "#d69a45"; }
            return "#2f8f52";
        }

        window.addEventListener("load", function ()
        {
            const fallback = document.getElementById("map-fallback");

            // ns87: the VM needs outbound internet for Leaflet and the tile server.
            // ns87: If either is blocked the terminal cards above still carry the data,
            // ns87: so the page degrades to a readable summary instead of a blank box.
            if (typeof L === "undefined")
            {
                if (fallback)
                {
                    fallback.innerHTML =
                        '<img src="/assets/airport-map-icon.svg" alt="" width="34">' +
                        '<p>The map tiles could not be reached from this server. ' +
                        'Live terminal conditions are listed below.</p>';
                }
                return;
            }

            if (fallback) { fallback.remove(); }

            const map = L.map("ewr-map").setView([40.6895, -74.1745], 14);

            L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png",
            {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const bounds = [];

            TERMINALS.forEach(function (t)
            {
                const marker = L.circleMarker([t.lat, t.lng],
                {
                    radius: 13 + Math.min(t.count, 6) * 2,
                    color: "#ffffff",
                    weight: 2,
                    fillColor: markerColour(t.count),
                    fillOpacity: 0.85
                }).addTo(map);

                marker.bindTooltip(t.name + " - " + t.count + (t.count === 1 ? " report" : " reports"),
                {
                    permanent: false,
                    direction: "top"
                });

                marker.bindPopup(popupHtml(t), { maxWidth: 300 });

                bounds.push([t.lat, t.lng]);
            });

            if (bounds.length)
            {
                map.fitBounds(bounds, { padding: [60, 60] });
            }
        });
    </script>
</body>
</html>
