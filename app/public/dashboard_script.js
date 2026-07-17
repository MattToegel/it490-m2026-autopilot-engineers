// tad46: dashboard interactions
// tad46: only the watchlist/history tab switching and placeholder report delete remain client-side
// tad46: the flights remove action is now a POST form to unsave_flight.php (no JS needed)
// tad46: report delete will be wired to a similar report_delete.php once reports_consumer.php is deployed

(function ()
{
    "use strict";

    // tad46:  saved flights tabs (US-05 AC5 soft-delete history view) 
    const savedCard = document.getElementById("saved-card");
    if (savedCard)
    {
        const tabs = savedCard.querySelectorAll(".saved-tab");
        tabs.forEach((tab) =>
        {
            tab.addEventListener("click", () =>
            {
                tabs.forEach((t) =>
                {
                    t.classList.remove("is-active");
                    t.setAttribute("aria-selected", "false");
                });
                tab.classList.add("is-active");
                tab.setAttribute("aria-selected", "true");
                savedCard.classList.toggle("show-history", tab.dataset.view === "history");
            });
        });
    }

    // tad46:  report delete (US-03 AC4, placeholder) 
    // tad46: client-side removal only for now; wire to a POST endpoint (report_delete.php)
    // tad46: alongside reports_consumer.php once that queue exists
    document.addEventListener("click", (e) =>
    {
        const del = e.target.closest("[data-report-delete]");
        if (!del)
        {
            return;
        }

        const item = del.closest(".report-item");
        if (item)
        {
            item.remove();
        }
    });
})();