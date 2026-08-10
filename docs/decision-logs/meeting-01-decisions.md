# Meeting 1 Decision Log

**Meeting Date:** June 1, 2026

**Meeting:** Group Meeting 1

**Team:** AutoPilot Engineers

**Related Meeting Notes:** Group Meeting 1 notes

---

## Decision D-001 - Confirm OnTheRadar Project Idea

## Date:

June 1, 2026

## Status:

Accepted

## Decision:

The team will develop OnTheRadar, a flight-tracking and airport-guide application that also allows users to submit airport condition reports.

## Rationale:

The project combines external flight data with user-generated airport information. This gives the team enough functionality to demonstrate application development, API integration, messaging, database storage, and user interaction.

## Consequences:

The project will require a browser-facing application, user accounts, flight searches, airport reports, RabbitMQ communication, database storage, and an API worker.

## Related Vote:

Accepted by all five team members during Group Meeting 1.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/2

---

## Decision D-002 - Limit the Initial Scope to Newark Domestic Flights

## Date:

June 1, 2026

## Status:

Accepted

## Decision:

The first version of the project will focus on domestic flights arriving at and departing from Newark Liberty International Airport, with United Airlines as the initial airline focus.

## Rationale:

The summer semester is short, so limiting the first version to one airport and a smaller flight scope will make the project more realistic and easier to complete. Additional airlines and Tri-State area airports may be added if time permits.

## Consequences:

Initial flight searches, interface content, API testing, and airport-reporting features will primarily use EWR and domestic flight data.

## Related Vote:

Accepted by all five team members during Group Meeting 1.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/2

---

## Decision D-003 - Use PHP and MySQL

## Date:

June 1, 2026

## Status:

Accepted

## Decision:

The team will use PHP for server-side application development and MySQL for persistent data storage.

## Rationale:

The team previously used PHP and MySQL in earlier courses and is familiar with both technologies. Using familiar tools reduces the amount of time needed to learn a new technology during the shortened semester.

## Consequences:

The App Server will host PHP pages and endpoints. The Database Server will use MySQL to store user accounts, flight searches, saved data, airport reports, and other application information.

## Related Vote:

Accepted by all five team members during Group Meeting 1.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/2

---

## Decision D-004 - Build an Internal Backend

## Date:

June 1, 2026

## Status:

Accepted

## Decision:

The team will create and maintain its own backend services and database instead of relying entirely on external flight APIs.

## Rationale:

The professor required the project to include an internal backend. External APIs may supply flight information, but the team must manage its own users, application data, processing, and system behavior.

## Consequences:

The system will include separate App, RabbitMQ, Database, and API Worker components. User accounts, airport reports, search history, and other application data will be handled by the team’s infrastructure.

## Related Vote:

Accepted by the team based on the professor’s project requirements.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/2

---

## Decision D-005 - Assign Initial Component Ownership

## Date:

June 1, 2026

## Status:

Accepted

## Decision:

The initial project responsibilities were assigned as follows:

* App Server: Rosmy Antony and Noaman Shahid
* RabbitMQ Server: Caitlin Ortiz
* Database Server: Tristan Duncan
* API Worker: Xaidyn Liranzo

## Rationale:

Assigning ownership ensures that every major component has responsible team members and reduces duplicated or overlapping work.

## Consequences:

Each owner is responsible for configuring, documenting, testing, and providing evidence for their assigned component. Team members must coordinate when features require communication across multiple servers.

## Related Vote:

Accepted by all five team members during Group Meeting 1.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/2

---

## Decision D-006 - Hold Two Weekly Team Meetings

## Date:

June 1, 2026

## Status:

Accepted

## Decision:

The team will hold recurring meetings on Mondays and Sundays from 4:00 p.m. to 5:00 p.m. Eastern Time.

## Rationale:

Meeting at the beginning and end of the week allows the team to assign tasks, review progress, identify blockers, and prepare for upcoming deadlines.

## Consequences:

Team members are expected to attend both meetings or provide an update when they cannot attend.

## Related Vote:

Accepted by all five team members during Group Meeting 1.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/2

---

## Decision D-007 - Use a Rotating Note-Taking Schedule

## Date:

June 1, 2026

## Status:

Accepted

## Decision:

Meeting note-taking responsibility will rotate in the following order:

1. Caitlin Ortiz
2. Tristan Duncan
3. Xaidyn Liranzo
4. Rosmy Antony
5. Noaman Shahid

## Rationale:

A rotation distributes documentation responsibilities evenly instead of placing the responsibility on one team member.

## Consequences:

Each team member must prepare and maintain the meeting notes for their assigned meeting.

## Related Vote:

Accepted by all five team members during Group Meeting 1.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/2
