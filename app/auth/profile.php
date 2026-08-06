<?php
// rma9: Start the logged-in user session
session_start();

// rma9: Block users who are not logged in
require_once __DIR__ . '/auth_protect.php';

// rma9: Get update messages from the redirect URL
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// rma9: Store session values for the page
$username = $_SESSION['username'] ?? '';
$email = $_SESSION['email'] ?? '';
$role = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Settings | OnTheRadar</title>

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

    <!-- rma9: Load the project font -->
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Georgian:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

        <!-- rma9: dashboard header same here -->
    <link rel="stylesheet" href="/public/dashboard_styles.css">
    <link rel="stylesheet" href="/public/notif_bell.css">
    <!-- rma9: Load the profile page styles -->
    <link
        rel="stylesheet"
        href="../public/profile_styles.css"
    >

    <!-- rma9: Load shared light and dark mode styles. -->
    <link rel="stylesheet" href="/public/theme.css?v=10">
</head>

<body>

<div class="settings-page">

    <!-- rma9: Uses the same top navigation bar as the dashboard. -->
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

            <a
                href="/dashboard.php#airport-conditions"
                class="top-header__link"
            >
                <img src="/assets/airport-map-icon.svg" alt="" aria-hidden="true">
                <span>Airport Map</span>
            </a>

            <a href="/reports/reports.php" class="top-header__link">
                <img src="/assets/community-icon.svg" alt="" aria-hidden="true">
                <span>Community</span>
            </a>

            <!-- rma9: Header toggle for switching between light and dark mode. -->
            <button
                type="button"
                class="theme-toggle"
                data-theme-toggle
                aria-label="Switch to dark mode"
                aria-pressed="false"
            >
                <span
                    class="theme-toggle__symbol"
                    aria-hidden="true"
                >
                    ☀
                </span>

                <span class="theme-toggle__circle"></span>
            </button>

            <div class="notif-menu">

                <button
                    type="button"
                    class="top-header__icon-button bell-link"
                    id="notifBellButton"
                    aria-label="Notifications"
                    aria-expanded="false"
                >
                    <img src="/assets/notification-icon.svg" alt="">

                    <span
                        class="bell-badge"
                        id="notifBellBadge"
                        style="display:none;"
                    ></span>
                </button>

                <div class="notif-dropdown" id="notifDropdown">

                    <div class="notif-dropdown-header">
                        <strong>Notifications</strong>

                        <span
                            class="notifications-count"
                            id="notifDropdownCount"
                            style="display:none;"
                        ></span>
                    </div>

                    <div
                        class="notif-dropdown-body"
                        id="notifDropdownBody"
                    >
                        <div class="notif-dropdown-empty">Loading...</div>
                    </div>

                    <a
                        href="/dashboard.php#notifications"
                        class="notif-dropdown-viewall"
                    >
                        View all in dashboard
                    </a>

                </div>

            </div>

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
                        <?= htmlspecialchars(
                            $username,
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </div>

                    <a href="/dashboard.php">Dashboard</a>
                    <a href="/auth/profile.php">Settings</a>

                    <?php if ($role === 'admin'): ?>
                        <a href="/admin/admin.php">Admin Panel</a>
                    <?php endif; ?>

                    <div class="user-dropdown-divider"></div>

                    <a
                        href="/auth/logout.php"
                        class="logout-link"
                    >
                        Log Out
                    </a>

                </div>

            </div>

        </nav>

    </header>

    <!-- rma9: Sidebar and main page content -->
    <main class="dashboard-layout">

        <!-- rma9: Exact dashboard.php sidebar, with Settings active. -->
        <aside class="dashboard-sidebar" aria-label="Dashboard navigation">

            <div class="sidebar-profile">

                <div class="sidebar-profile__avatar">
                    <img src="/assets/user-icon.svg" alt="">
                </div>

                <p class="sidebar-profile__name">
                    <?= htmlspecialchars(
                        $username,
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>

            </div>

            <nav class="sidebar-menu">

                <a
                    href="/dashboard.php"
                    class="sidebar-menu__link"
                >
                    <img
                        src="/assets/home_icon_dashboard.svg"
                        alt=""
                        aria-hidden="true"
                    >
                    <span>Dashboard</span>
                </a>

                <a
                    href="/landing.php"
                    class="sidebar-menu__link"
                >
                    <img
                        src="/assets/radar_icon.svg"
                        alt=""
                        aria-hidden="true"
                    >
                    <span>Search Flights</span>
                </a>

                <a
                    href="/flight_history.php"
                    class="sidebar-menu__link"
                >
                    <img
                        src="/assets/flight_dashboard_icon.svg"
                        alt=""
                        aria-hidden="true"
                    >
                    <span>Flight History</span>
                </a>

                <a
                    href="/reports/reports.php"
                    class="sidebar-menu__link"
                >
                    <img
                        src="/assets/report_dashboard.svg"
                        alt=""
                        aria-hidden="true"
                    >
                    <span>Community Reports</span>
                </a>

                <a
                    href="/auth/profile.php"
                    class="sidebar-menu__link sidebar-menu__link--active"
                    aria-current="page"
                >
                    <img
                        src="/assets/gear_dashboard.svg"
                        alt=""
                        aria-hidden="true"
                    >
                    <span>Settings</span>
                </a>

                <?php if ($role === 'admin'): ?>

                    <a
                        href="/admin/admin_dashboard.php"
                        class="sidebar-menu__link"
                    >
                        <img
                            src="/assets/user_dashboard.svg"
                            alt=""
                            aria-hidden="true"
                        >
                        <span>Admin</span>
                    </a>

                <?php endif; ?>

            </nav>

        </aside>

        <!-- rma9: Main settings section -->
        <main class="settings-main">

            <h1 class="settings-title">
                Settings
            </h1>

            <!-- rma9: Show success message -->
            <?php if ($success): ?>

                <div
                    class="profile-message success"
                    role="status"
                >
                    <?= htmlspecialchars($success) ?>
                </div>

            <?php endif; ?>

            <!-- rma9: Show error message -->
            <?php if ($error): ?>

                <div
                    class="profile-message error"
                    role="alert"
                >
                    <?= htmlspecialchars($error) ?>
                </div>

            <?php endif; ?>

            <div class="settings-grid">

                <!-- rma9: Account management card -->
                <section class="settings-card account-card">

                    <h2>Account Info</h2>

                    <!-- rma9: Profile update form -->
                    <form
                        class="profile-form"
                        action="update_profile.php"
                        method="POST"
                    >

                        <div class="form-group">

                            <label for="username">
                                Username
                            </label>

                            <input
                                id="username"
                                type="text"
                                value="<?= htmlspecialchars($username) ?>"
                                readonly
                            >

                        </div>

                        <div class="form-group">

                            <label for="email">
                                Email Address
                            </label>

                            <!-- rma9: Displays the registered email as read-only
                                 account information for security. -->
                            <input
                                id="email"
                                type="email"
                                value="<?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>"
                                readonly
                            >

                        </div>

                        <div class="form-group">

                            <label for="role">
                                Role
                            </label>

                            <input
                                id="role"
                                type="text"
                                value="<?= htmlspecialchars($role) ?>"
                                readonly
                            >

                        </div>

                        <div class="password-divider"></div>

                        <h3 class="password-heading">
                            Change Password
                        </h3>

                        <div class="form-group">

                            <label for="current_password">
                                Current Password
                            </label>

                            <input
                                id="current_password"
                                type="password"
                                name="current_password"
                                autocomplete="current-password"
                            >

                        </div>

                        <div class="form-group">

                            <label for="new_password">
                                New Password
                            </label>

                            <input
                                id="new_password"
                                type="password"
                                name="new_password"
                                autocomplete="new-password"
                            >

                        </div>

                        <div class="form-group">

                            <label for="confirm_password">
                                Confirm New Password
                            </label>

                            <input
                                id="confirm_password"
                                type="password"
                                name="confirm_password"
                                autocomplete="new-password"
                            >

                        </div>

                        <button
                            type="submit"
                            class="update-profile-button"
                        >
                            Update Profile
                        </button>

                    </form>

                </section>

                <!-- rma9: Appearance settings -->
                <section class="settings-card appearance-card">

                    <h2>Appearance</h2>

                    <div class="appearance-row">

                        <span>Dark Mode</span>

                        <!-- rma9: Settings toggle synchronized with the header toggle. -->
                        <button
                            type="button"
                            class="theme-toggle"
                            data-theme-toggle
                            aria-label="Switch to dark mode"
                            aria-pressed="false"
                        >
                            <span
                                class="theme-toggle__symbol"
                                aria-hidden="true"
                            >
                                ☀
                            </span>

                            <span class="theme-toggle__circle"></span>
                        </button>

                    </div>

                </section>

                <!-- rma9: Notification settings -->
                <section class="settings-card notification-card">

                    <h2>Notification Preference</h2>

                    <div class="notification-box">

                        <div class="notification-row">

                            <span>Flight</span>

                            <button
                                type="button"
                                class="toggle-switch"
                                aria-label="Toggle flight notifications"
                            ></button>

                        </div>

                        <div class="notification-row">

                            <span>Airport</span>

                            <button
                                type="button"
                                class="toggle-switch"
                                aria-label="Toggle airport notifications"
                            ></button>

                        </div>

                        <div class="notification-row">

                            <span>Reports</span>

                            <button
                                type="button"
                                class="toggle-switch"
                                aria-label="Toggle report notifications"
                            ></button>

                        </div>

                    </div>

                </section>

                <!-- rma9: Accessibility settings -->
                <section class="settings-card accessibility-card">

                    <h2>Accessibility</h2>

                    <div class="accessibility-row">

                        <span class="accessibility-label">
                            Text Size
                        </span>

                        <div
                            class="text-size-control"
                            aria-label="Text size control"
                        >

                            <span class="text-size-small">
                                A
                            </span>

                            <span class="text-size-middle"></span>

                            <span class="text-size-large">
                                A
                            </span>

                        </div>

                    </div>

                </section>

                <!-- rma9: Privacy section -->
                <section class="settings-card privacy-card">

                    <h2>Privacy</h2>

                    <div class="privacy-lines">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                </section>

            </div>

        </main>

    </main>

</div>

<script>
// rma9: Gets the user menu button and dropdown so the
// account menu can be opened and closed.
const userButton = document.getElementById("userMenuButton");
const userDropdown = document.getElementById("userDropdown");

if (userButton && userDropdown)
{
    // rma9: Opens or closes the account dropdown when
    // the user icon is clicked.
    userButton.addEventListener("click", function(event)
    {
        event.stopPropagation();

        userDropdown.classList.toggle("show");

        // rma9: Keeps the accessibility state synchronized
        // with whether the dropdown is visible.
        userButton.setAttribute(
            "aria-expanded",
            userDropdown.classList.contains("show")
        );
    });

    // rma9: Closes the dropdown when the user clicks
    // anywhere outside the account menu.
    document.addEventListener("click", function(event)
    {
        if (
            !userButton.contains(event.target) &&
            !userDropdown.contains(event.target)
        )
        {
            userDropdown.classList.remove("show");

            userButton.setAttribute(
                "aria-expanded",
                "false"
            );
        }
    });
}
</script>

<script src="/public/notif_bell.js"></script>

<!-- rma9: Load shared light and dark mode behavior. -->
<script src="/public/theme.js?v=5"></script>

</body>

</html>