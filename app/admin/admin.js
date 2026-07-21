// ==========================================================
// OnTheRadar Admin JS
// US-04 Administration Community Management
//
// Handles:
//  - Admin profile dropdown
//  - Theme toggle
//  - Report delete confirmations
//  - Admin alerts
//  - User searching
//
// cao39 -  Created specifically for the Admin Panel
// ==========================================================


(function ()
{
    "use strict";



    // ======================================================
    // cao39 - 
    // Profile dropdown menu
    //
    // Handles the top-right user icon dropdown:
    // Dashboard
    // Settings
    // Admin Panel
    // Logout
    // ======================================================


    const profileButton =
        document.getElementById("profile-button");


    const profileMenu =
        document.getElementById("profile-menu");



    if (profileButton && profileMenu)
    {

        profileButton.addEventListener(
            "click",
            function ()
            {

                profileMenu.classList.toggle(
                    "show"
                );

            }
        );



        // Close dropdown if user clicks elsewhere

        document.addEventListener(
            "click",
            function(event)
            {

                if (
                    !profileButton.contains(event.target)
                    &&
                    !profileMenu.contains(event.target)
                )
                {

                    profileMenu.classList.remove(
                        "show"
                    );

                }

            }
        );

    }






    // ======================================================
    // tad46 toggle theme
    // ======================================================


    const themeToggle =
        document.querySelector(".theme-toggle");



    const savedTheme =
        localStorage.getItem(
            "otr-theme"
        );



    if(savedTheme === "dark")
    {

        document.body.classList.add(
            "dark-mode"
        );

    }



    if(themeToggle)
    {


        themeToggle.addEventListener(
            "click",
            function()
            {

                document.body.classList.toggle(
                    "dark-mode"
                );



                if(
                    document.body.classList.contains(
                        "dark-mode"
                    )
                )
                {

                    localStorage.setItem(
                        "otr-theme",
                        "dark"
                    );

                }

                else
                {

                    localStorage.setItem(
                        "otr-theme",
                        "light"
                    );

                }


            }
        );

    }






    // ======================================================
    // cao39 - 
    // Report deletion confirmation
    //
    // Used by admin_reports.php
    //
    // Prevents accidental deletion of reports.
    // ======================================================


    const deleteForms =
        document.querySelectorAll(
            "[data-delete-report]"
        );



    deleteForms.forEach(
        function(form)
        {

            form.addEventListener(
                "submit",
                function(event)
                {


                    const confirmed =
                        confirm(
                            "Are you sure you want to delete this report?"
                        );



                    if(!confirmed)
                    {

                        event.preventDefault();

                    }


                }
            );


        }
    );







    // ======================================================
    // 
    // cao39 - Automatically hide success/error messages
    //
    // Used on:
    // admin_users.php
    // admin_reports.php
    // admin_roles.php
    //
    // Messages disappear after 5 seconds.
    // ======================================================


    const alerts =
        document.querySelectorAll(
            ".admin-success, .admin-error"
        );



    alerts.forEach(
        function(alert)
        {


            setTimeout(
                function()
                {

                    alert.style.opacity = "0";


                    setTimeout(
                        function()
                        {

                            alert.style.display =
                                "none";

                        },
                        500
                    );


                },
                5000
            );


        }
    );








    // ======================================================
    // cao39 - 
    // Client-side user table search
    //
    // Supports future:
    // usr.adm.search RabbitMQ feature
    //
    // This does NOT replace backend searching.
    // It only filters the currently loaded table.
    // ======================================================


    const userSearch =
        document.getElementById(
            "user-search"
        );



    const userRows =
        document.querySelectorAll(
            ".user-row"
        );



    if(userSearch)
    {


        userSearch.addEventListener(
            "keyup",
            function()
            {

                const searchValue =
                    userSearch.value.toLowerCase();



                userRows.forEach(
                    function(row)
                    {


                        const rowText =
                            row.textContent.toLowerCase();



                        if(
                            rowText.includes(
                                searchValue
                            )
                        )
                        {

                            row.style.display =
                                "";

                        }

                        else
                        {

                            row.style.display =
                                "none";

                        }


                    }
                );


            }
        );


    }








    // cao39 - Adds active class automatically.
    // ======================================================


    const currentPage =
        window.location.pathname;



    const navLinks =
        document.querySelectorAll(
            ".sidebar-menu__link"
        );



    navLinks.forEach(
        function(link)
        {


            if(
                currentPage.includes(
                    link.getAttribute("href")
                )
            )
            {

                link.classList.add(
                    "sidebar-menu__link--active"
                );

            }


        }
    );



})();
