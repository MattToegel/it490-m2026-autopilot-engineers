// tad46: report drawer open/close logic
// tad46: The drawer starts open if the server rendered it that way (edit mode).
// tad46: Otherwise, clicking the FAB opens it, and Escape/close/scrim close it.

(function ()
{
    "use strict";

    const fab    = document.getElementById("report-fab");
    const drawer = document.getElementById("report-drawer");
    const scrim  = document.getElementById("report-scrim");
    const close  = document.getElementById("report-drawer-close");

    if (!drawer || !scrim || !fab)
    {
        return;
    }

    function openDrawer()
    {
        drawer.hidden = false;
        scrim.hidden  = false;

        // tad46: reflow before adding the class so the transition animates
        requestAnimationFrame(() =>
        {
            drawer.classList.add("is-open");
            scrim.classList.add("is-open");
        });

        fab.setAttribute("aria-expanded", "true");
        // tad46: shift focus into the drawer for keyboard users
        const firstField = drawer.querySelector("select, textarea, input");
        if (firstField)
        {
            firstField.focus();
        }
    }

    function closeDrawer()
    {
        drawer.classList.remove("is-open");
        scrim.classList.remove("is-open");

        // tad46: hide after the transition finishes so tab order is right
        setTimeout(() =>
        {
            drawer.hidden = true;
            scrim.hidden  = true;
        }, 280);

        fab.setAttribute("aria-expanded", "false");
        fab.focus();

        // tad46: if the URL still has ?report_edit=X, strip it so a refresh
        // tad46: doesn't reopen the edit view unexpectedly
        if (window.location.search.includes("report_edit="))
        {
            const url = new URL(window.location.href);
            url.searchParams.delete("report_edit");
            window.history.replaceState({}, "", url.toString());
        }
    }

    fab.addEventListener("click", () =>
    {
        // tad46: FAB always opens a fresh Create form, even in edit mode -
        // tad46: closing first clears any pre-fill by reloading with the
        // tad46: bare URL if we're mid-edit
        if (window.location.search.includes("report_edit="))
        {
            const url = new URL(window.location.href);
            url.searchParams.delete("report_edit");
            window.location.assign(url.toString());
            return;
        }
        openDrawer();
    });

    close.addEventListener("click", closeDrawer);
    scrim.addEventListener("click", closeDrawer);

    document.addEventListener("keydown", (e) =>
    {
        if (e.key === "Escape" && !drawer.hidden)
        {
            closeDrawer();
        }
    });
})();
