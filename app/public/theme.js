// rma9: Controls and saves light/dark mode across all user-facing pages.
(function ()
{
    // rma9: Name used to save the selected theme in the browser.
    const storageKey = "otr-theme";

    // rma9: Gets the saved theme from localStorage.
    function getTheme()
    {
        const savedTheme = localStorage.getItem(storageKey);

        // rma9: Only accepts valid light or dark theme values.
        if (savedTheme === "dark" || savedTheme === "light")
        {
            return savedTheme;
        }

        // rma9: Uses light mode as the default theme.
        return "light";
    }

    // rma9: Updates every theme toggle button on the current page.
    function updateButtons(theme)
    {
        const isDark = theme === "dark";

        // rma9: Finds all buttons marked as theme toggle buttons.
        document.querySelectorAll("[data-theme-toggle]")
            .forEach(function (button)
            {
                // rma9: Finds the sun or moon symbol inside the toggle.
                const icon = button.querySelector(
                    ".theme-toggle__symbol"
                );

                // rma9: Adds the dark class when dark mode is active.
                button.classList.toggle("is-dark", isDark);

                // rma9: Updates the accessibility state of the toggle.
                button.setAttribute(
                    "aria-pressed",
                    isDark ? "true" : "false"
                );

                // rma9: Updates the button label for screen readers.
                button.setAttribute(
                    "aria-label",
                    isDark
                        ? "Switch to light mode"
                        : "Switch to dark mode"
                );

                // rma9: Shows a moon in dark mode and a sun in light mode.
                if (icon)
                {
                    icon.textContent = isDark ? "☾" : "☀";
                }
            });
    }

    // rma9: Applies the selected theme to the page.
    function applyTheme(theme, saveTheme = true)
    {
        // rma9: Adds the selected theme to the root HTML element.
        document.documentElement.setAttribute(
            "data-theme",
            theme
        );

        // rma9: Saves the selected theme when the user changes it.
        if (saveTheme)
        {
            localStorage.setItem(storageKey, theme);
        }

        // rma9: Keeps every visible theme toggle synchronized.
        updateButtons(theme);
    }

    // rma9: Applies the saved theme immediately when this file loads.
    // rma9: This reduces the delay before dark mode appears.
    const initialTheme = getTheme();

    document.documentElement.setAttribute(
        "data-theme",
        initialTheme
    );

    // rma9: Connects the theme toggles after the page content loads.
    document.addEventListener("DOMContentLoaded", function ()
    {
        // rma9: Synchronizes toggle position and symbol with the saved theme.
        applyTheme(initialTheme, false);

        // rma9: Adds a click event to every theme toggle button.
        document.querySelectorAll("[data-theme-toggle]")
            .forEach(function (button)
            {
                button.addEventListener("click", function ()
                {
                    // rma9: Reads the theme currently applied to the page.
                    const currentTheme =
                        document.documentElement.getAttribute(
                            "data-theme"
                        );

                    // rma9: Switches between light and dark mode.
                    const nextTheme =
                        currentTheme === "dark"
                            ? "light"
                            : "dark";

                    // rma9: Applies and saves the selected theme.
                    applyTheme(nextTheme);
                });
            });
    });

    // rma9: Keeps the theme synchronized across multiple open tabs.
    window.addEventListener("storage", function (event)
    {
        if (
            event.key === storageKey &&
            (
                event.newValue === "dark" ||
                event.newValue === "light"
            )
        )
        {
            // rma9: Applies the theme changed in another browser tab.
            applyTheme(event.newValue, false);
        }
    });
})();