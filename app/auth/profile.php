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

    <!-- rma9: Load the profile page styles -->
    <link
        rel="stylesheet"
        href="../public/profile_styles.css"
    >
</head>

<body>

<div class="settings-page">

    <!-- rma9: Top navigation bar -->
    <header class="top-navbar">

        <a
            class="nav-logo"
            href="../dashboard.php"
            aria-label="Go to dashboard"
        >
            <img
                src="../assets/otr-logo.svg"
                alt="OnTheRadar logo"
            >
        </a>

        <!-- rma9: Main navigation links -->
        <nav
            class="nav-links"
            aria-label="Main navigation"
        >

            <a href="../search.php">
                <img
                    src="../assets/search-icon.svg"
                    alt=""
                >
                <span>Search</span>
            </a>

            <a href="../airport_map.php">
                <img
                    src="../assets/airport-map-icon.svg"
                    alt=""
                >
                <span>Airport Map</span>
            </a>

            <a href="../community.php">
                <img
                    src="../assets/community-icon.svg"
                    alt=""
                >
                <span>Community</span>
            </a>

        </nav>

        <!-- rma9: Navigation actions -->
        <div class="nav-actions">

            <button
                type="button"
                class="nav-theme-toggle"
                aria-label="Toggle dark mode"
            ></button>

            <a
                class="nav-icon-button"
                href="../notifications.php"
                aria-label="View notifications"
            >
                <img
                    src="../assets/notification-icon.svg"
                    alt=""
                >
            </a>

            <!-- rma9: Account dropdown lets the user return to the dashboard,
                 open settings, access admin tools when allowed, or log out. -->
            <div class="user-menu">

                <button
                    type="button"
                    class="nav-icon-button"
                    id="userMenuButton"
                    aria-label="User menu"
                    aria-expanded="false"
                >
                    <img
                        src="../assets/user-icon.svg"
                        alt=""
                    >
                </button>

                <div
                    class="user-dropdown"
                    id="userDropdown"
                >
                    <div class="user-dropdown-header">
                        <?= htmlspecialchars($username) ?>
                    </div>

                    <a href="../dashboard.php">
                        Dashboard
                    </a>

                    <a href="profile.php">
                        Settings
                    </a>

                    <?php if ($role === 'admin'): ?>
                        <a href="../admin/admin.php">
                            Admin Panel
                        </a>
                    <?php endif; ?>

                    <div class="user-dropdown-divider"></div>

                    <a
                        href="logout.php"
                        class="logout-link"
                    >
                        Log Out
                    </a>
                </div>

            </div>

        </div>

    </header>

    <!-- rma9: Sidebar and main page content -->
    <div class="settings-layout">

        <!-- rma9: Left dashboard sidebar -->
        <aside class="sidebar">

            <div class="sidebar-top">

                <!-- rma9: Logged-in user information -->
                <div class="sidebar-profile">

                    <img
                        class="sidebar-user-icon"
                        src="../assets/user_dashboard.svg"
                        alt="User profile"
                    >

                    <p class="sidebar-username">
                        <?= htmlspecialchars($username) ?>
                    </p>

                </div>

                <!-- rma9: Sidebar navigation -->
                <nav
                    class="sidebar-menu"
                    aria-label="Dashboard navigation"
                >

                    <a href="../dashboard.php">
                        <img
                            src="../assets/home_icon_dashboard.svg"
                            alt=""
                        >
                        <span>Dashboard</span>
                    </a>

                    <a href="../search.php">
                        <img
                            src="../assets/flight_dashboard_icon.svg"
                            alt=""
                        >
                        <span>Flight</span>
                    </a>

                    <a href="../stats.php">
                        <img
                            src="../assets/stats_dashboard.svg"
                            alt=""
                        >
                        <span>Stats</span>
                    </a>

                    <a href="../reports.php">
                        <img
                            src="../assets/report_dashboard.svg"
                            alt=""
                        >
                        <span>Reports</span>
                    </a>

                    <a
                        href="profile.php"
                        class="active"
                    >
                        <img
                            src="../assets/gear_dashboard.svg"
                            alt=""
                        >
                        <span>Settings</span>
                    </a>

                </nav>

            </div>

            <!-- rma9: Sidebar footer text -->
            <div class="sidebar-footer">
                OnTheRadar
            </div>

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

                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="<?= htmlspecialchars($email) ?>"
                                autocomplete="email"
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

                        <button
                            type="button"
                            class="toggle-switch"
                            aria-label="Toggle dark mode"
                        ></button>

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

    </div>

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

</body>

</html>