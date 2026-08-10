# Meeting 11 Decision Log

**Meeting Date:** June 30, 2026

**Meeting:** Group Meeting 11

**Team:** AutoPilot Engineers

**Facilitator:** Caitlin Ortiz

**Note Taker:** Caitlin Ortiz

**Related Milestone:** Post-Milestone 2 / Project UI Planning

**Related Meeting Notes:** Group Meeting 11 notes

---

## Decision D-054 - Organize the Application Around Approximately 6-7 Main Pages

## Date:

June 30, 2026

## Status:

Accepted

## Decision:

The team will organize the OnTheRadar application around approximately six to seven primary pages or interface areas:

* Landing Page
* User Account / Dashboard
* Admin Dashboard, if kept separate
* Settings
* Community
* Login / Register
* Airport Map

## Rationale:

Limiting the number of main pages gives users a clearer navigation structure and keeps the application scope manageable while still supporting the project’s required features.

## Consequences:

Frontend development and navigation will be organized around these primary interface areas. The Admin Dashboard may later be integrated into the regular dashboard if a separate page is unnecessary.

## Related Vote:

Accepted by all five team members during Group Meeting 11.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/88

---

## Decision D-055 - Use Recognizable Icons for Major User Actions

## Date:

June 30, 2026

## Status:

Accepted

## Decision:

The team will use recognizable interface icons for common actions, including:

* A warning or airport-report icon for submitting airport condition reports
* A bell icon for viewing notifications
* A person/account icon for accessing the user dashboard, settings, and logout options

## Rationale:

Using familiar visual icons helps users quickly understand available actions without requiring large amounts of instructional text.

## Consequences:

The frontend should use these icons consistently across relevant pages. Each icon should trigger the corresponding user action or navigation behavior.

## Related Vote:

Accepted by all five team members during Group Meeting 11.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/88

---

## Decision D-056 - Begin with a Static Newark Airport Map

## Date:

June 30, 2026

## Status:

Accepted

## Decision:

The initial version of OnTheRadar will use a static Newark Liberty International Airport map as a reference tool for users.

## Rationale:

A static map gives users a way to view the airport layout, terminals, and important locations without requiring the team to immediately build a more complex interactive mapping system. This allows development effort to remain focused on higher-priority MVP features.

## Consequences:

The initial airport map will primarily serve as a navigation and reference feature. Users will not submit reports directly through the static map. A more interactive map may be considered later as a stretch feature.

## Related Vote:

Accepted by all five team members during Group Meeting 11.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/88

---

## Decision D-057 - Use White and Navy Blue as the Primary Site Colors

## Date:

June 30, 2026

## Status:

Accepted

## Decision:

The primary OnTheRadar interface will use a white background with navy blue as a primary text and interface color.

## Rationale:

The team wanted the application to have a clean airport-oriented appearance. White and blue were selected to create a clear and calm interface for users navigating potentially busy travel information.

## Consequences:

Frontend pages, navigation elements, buttons, text, and other interface components should generally follow the agreed white and navy color direction unless later design revisions are approved.

## Related Vote:

Accepted by all five team members during Group Meeting 11.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/88

---

## Decision D-058 - Place Multi-Option Flight Search on the Landing Page

## Date:

June 30, 2026

## Status:

Accepted

## Decision:

The landing page will include a flight search feature that allows users to search using information such as:

* City
* Flight number
* Flight route
* Airline

The page may also show airport flight information beneath the search area.

## Rationale:

Providing multiple search methods makes it easier for users to locate a specific flight even when they only know part of the flight information.

## Consequences:

The frontend search interface and API integration must support the agreed search inputs where technically available. Search results should connect to the project’s flight-tracking functionality.

## Related Vote:

Accepted by all five team members during Group Meeting 11.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/88

---

## Decision D-059 - Prompt Users to Save or Track a Flight After Search

## Date:

June 30, 2026

## Status:

Accepted

## Decision:

After a user searches for and selects a flight, the interface will display an option asking whether they want to save or track that flight.

If the user is not logged in, selecting this option will direct them to the login or registration workflow.

## Rationale:

The save/track prompt connects flight search to the user-account functionality and gives users a reason to create an account when they want persistent flight tracking.

## Consequences:

The application must determine whether a user has an active authenticated session before allowing saved or tracked flights. Unauthenticated users attempting to use the feature should be directed to login or registration.

## Related Vote:

Accepted by all five team members during Group Meeting 11.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/88
---

## Decision D-060 - Treat an Interactive Airport Map as a Stretch Feature

## Date:

June 30, 2026

## Status:

Proposed

## Decision:

The team proposed an interactive airport map that could help users locate airport shops and other points of interest as a project stretch feature.

## Rationale:

An interactive map would expand the airport-guide functionality but is not required for the initial implementation. Keeping it as a stretch feature prevents it from interfering with completion of higher-priority MVP functionality.

## Consequences:

The initial application can proceed with the static map defined in D-056. The interactive version may be developed later if the team completes the required project functionality and has sufficient time.

## Related Vote:

Discussed during Group Meeting 11 and assigned for addition to the project proposal as a stretch feature.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/88
