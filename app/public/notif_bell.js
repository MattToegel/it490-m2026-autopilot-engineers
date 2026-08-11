// notif_bell.js
// tad46: Shared header bell dropdown logic - site-wide notifications.
// tad46: Fetches /flight/get_alerts.php once on page load and renders the
// tad46: badge + dropdown from that response. Any page that includes this
// tad46: script AND has the bell markup (see header snippet) gets working
// tad46: notifications, without duplicating PHP alert-fetch logic per page.
// tad46: NEW - dismiss buttons in the dropdown, wired via event delegation
// tad46: since items are re-rendered on every loadAlerts() call.
(function ()
{
    const bellButton  = document.getElementById("notifBellButton");
    const dropdown    = document.getElementById("notifDropdown");
    const badge       = document.getElementById("notifBellBadge");
    const countLabel  = document.getElementById("notifDropdownCount");
    const body        = document.getElementById("notifDropdownBody");

    if (!bellButton || !dropdown)
    {
        return;
    }

    // tad46: mirrors alertTypeClass() in dashboard.php - keep these in sync
    function alertTypeClass(type)
    {
        const map =
        {
            delay:         "alert-chip-delay",
            gate_change:   "alert-chip-gate",
            cancellation:  "alert-chip-cancel",
            status_change: "alert-chip-status",
            saved:         "alert-chip-status",
            removed:       "alert-chip-muted",
        };
        return map[type] || "alert-chip-muted";
    }

    // tad46: mirrors timeAgo() in dashboard.php, JS version for client-side render
    function timeAgo(mysqlTimestamp)
    {
        const ts = new Date(mysqlTimestamp.replace(" ", "T") + "Z").getTime();
        if (isNaN(ts)) return "recently";
        const seconds = Math.max(0, Math.floor((Date.now() - ts) / 1000));
        if (seconds < 60) return "just now";
        if (seconds < 3600) return Math.floor(seconds / 60) + "m ago";
        if (seconds < 86400) return Math.floor(seconds / 3600) + "h ago";
        return Math.floor(seconds / 86400) + "d ago";
    }

    function escapeHtml(str)
    {
        const div = document.createElement("div");
        div.textContent = str || "";
        return div.innerHTML;
    }

    function render(data)
    {
        const alerts = data.alerts || [];
        const unread = data.unread_count || 0;

        updateBadge(unread);

        if (data.status !== "success")
        {
            body.innerHTML = '<div class="notif-dropdown-empty">Notifications are temporarily unavailable.</div>';
            return;
        }

        if (alerts.length === 0)
        {
            body.innerHTML = '<div class="notif-dropdown-empty">No notifications yet.</div>';
            return;
        }

        const items = alerts.slice(0, 8).map(function (alert)
        {
            const readClass = alert.is_read ? " notif-dropdown-item--read" : "";
            // tad46: NEW - dismiss button, only shown for unread items
            const dismissHtml = alert.is_read
                ? '<span class="notif-dropdown-read-tag">Read</span>'
                : '<button type="button" class="notif-dropdown-dismiss" data-alert-id="' + alert.alert_id + '">Dismiss</button>';

            return (
                '<li class="notif-dropdown-item' + readClass + '" data-alert-id="' + alert.alert_id + '">' +
                    '<div class="notif-dropdown-item__top">' +
                        '<span class="notification-chip ' + alertTypeClass(alert.alert_type) + '">' +
                            escapeHtml(alert.alert_type) +
                        '</span>' +
                        '<span class="notif-dropdown-flight">' + escapeHtml(alert.flight_number) + '</span>' +
                    '</div>' +
                    '<p class="notif-dropdown-message">' + escapeHtml(alert.alert_message) + '</p>' +
                    '<span class="notif-dropdown-time">' + timeAgo(alert.created_at) + '</span>' +
                    dismissHtml +
                '</li>'
            );
        }).join("");

        body.innerHTML = '<ul class="notif-dropdown-list">' + items + '</ul>';
    }

    // tad46: NEW - shared badge/count update, extracted so dismiss can call it too
    function updateBadge(unread)
    {
        if (unread > 0)
        {
            badge.textContent = unread > 9 ? "9+" : unread;
            badge.style.display = "";
            countLabel.textContent = unread + " unread";
            countLabel.style.display = "";
        }
        else
        {
            badge.style.display = "none";
            countLabel.style.display = "none";
        }
    }

    function loadAlerts()
    {
        fetch("/flight/get_alerts.php")
            .then(function (res) { return res.json(); })
            .then(render)
            .catch(function ()
            {
                body.innerHTML = '<div class="notif-dropdown-empty">Notifications are temporarily unavailable.</div>';
            });
    }

    // tad46: NEW - dismiss handler, delegated on the body container since
    // items are re-rendered each time loadAlerts() runs
    body.addEventListener("click", async function (e)
    {
        const btn = e.target.closest(".notif-dropdown-dismiss");
        if (!btn)
        {
            return;
        }

        const alertId = btn.dataset.alertId;
        btn.disabled = true;

        try
        {
            const res = await fetch("/flight/mark_alert_read.php",
            {
                method: "POST",
                headers:
                {
                    "Content-Type": "application/x-www-form-urlencoded",
                    "X-Requested-With": "XMLHttpRequest",
                },
                credentials: "include",
                body: "alert_id=" + encodeURIComponent(alertId),
            });

            const data = await res.json();

            if (data.status === "success")
            {
                const item = btn.closest(".notif-dropdown-item");
                if (item)
                {
                    item.classList.add("notif-dropdown-item--read");
                    btn.replaceWith(Object.assign(document.createElement("span"),
                    {
                        className: "notif-dropdown-read-tag",
                        textContent: "Read",
                    }));
                }

                // tad46: keep the badge count accurate without a full refetch
                const current = parseInt(badge.textContent, 10) || 0;
                updateBadge(Math.max(0, current - 1));
            }
            else
            {
                btn.disabled = false;
            }
        }
        catch (err)
        {
            console.error("Dismiss failed:", err);
            btn.disabled = false;
        }
    });

    bellButton.addEventListener("click", function (e)
    {
        e.stopPropagation();
        dropdown.classList.toggle("show");
        bellButton.setAttribute("aria-expanded", dropdown.classList.contains("show"));
    });

    document.addEventListener("click", function (e)
    {
        if (!bellButton.contains(e.target) && !dropdown.contains(e.target))
        {
            dropdown.classList.remove("show");
            bellButton.setAttribute("aria-expanded", "false");
        }
    });

    loadAlerts();
})();