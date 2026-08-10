# Meeting 2 Decision Log

**Meeting Date:** June 7, 2026

**Meeting:** Group Meeting 2

**Team:** AutoPilot Engineers

**Facilitator:** Caitlin Ortiz

**Note Taker:** Tristan Duncan

**Related Meeting Notes:** Group Meeting 2 notes

---

## Decision D-008 - Use Tailscale for VM Connectivity

## Date:

June 7, 2026

## Status:

Accepted

## Decision:

The team will use Tailscale to connect team members’ virtual machines across their separate local networks.

## Rationale:

The project requires communication between App, RabbitMQ, Database, and API Worker virtual machines owned by different team members. Tailscale provides a shared private network that allows the team to connect to and test services across those VMs.

## Consequences:

Each team member must install Tailscale, connect their assigned VM to the team network, and verify that they can communicate with the other project VMs. Team members may also use Tailscale SSH when remote access is needed.

## Related Vote:

Accepted by all five team members during Group Meeting 2 after Tristan Duncan demonstrated Tailscale.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/7
---

## Decision D-009 - Use GitHub Issues and Branches for Project Work

## Date:

June 7, 2026

## Status:

Accepted

## Decision:

The team will use GitHub issues to document project tasks and separate branches to complete changes before merging them into the shared repository.

## Rationale:

Using an issue-and-branch workflow gives the team visible evidence of assigned work and reduces the risk of team members overwriting one another’s changes.

## Consequences:

Project work should be connected to a GitHub issue when possible. Team members should create branches for their assigned changes and merge completed work through the team’s GitHub workflow.

## Related Vote:

Accepted by the team during the GitHub workflow review in Group Meeting 2.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/7

---

## Decision D-010 - Establish an Initial Project Work Split

## Date:

June 7, 2026

## Status:

Proposed

## Decision:

The team proposed organizing project work into the following general areas:

* Frontend: Rosmy Antony will oversee the frontend, with all team members contributing to web pages when needed.
* Backend: Noaman Shahid, Caitlin Ortiz, and Xaidyn Liranzo will focus primarily on backend-related work.
* Database: Tristan Duncan will focus primarily on database-related work.

## Rationale:

Dividing the project into frontend, backend, and database responsibilities gives each team member a clearer area of focus while still allowing collaboration on features that cross multiple components.

## Consequences:

The proposed split will guide initial task assignments. Responsibilities may be refined as the system architecture, user stories, and milestone requirements become more detailed.

## Related Vote:

Discussed during Group Meeting 2. The meeting notes describe this as a proposed work split rather than a finalized assignment.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/7

---

## Decision D-011 - Define Initial Candidate Milestone Features

## Date:

June 7, 2026

## Status:

Proposed

## Decision:

The team proposed the following features as initial milestone targets:

* User registration
* User login
* Flight search functionality

## Rationale:

These features represent the minimum core behaviors needed to demonstrate account access and the main purpose of the flight-tracking application.

## Consequences:

The team will use these proposed features while developing the formal milestone plan. Final milestone scope and acceptance criteria may change after reviewing course requirements and receiving feedback.

## Related Vote:

Discussed by the team during Group Meeting 2. The features were recorded as possible milestones and were not yet finalized.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/7
---

## Decision D-012 - Defer Final API Selection to the Next Meeting

## Date:

June 7, 2026

## Status:

Accepted

## Decision:

The team will review the researched flight and user-reporting APIs at the next meeting before selecting the APIs that will be used in the project.

## Rationale:

Each team member researched possible APIs, but the team needed additional time to compare their functionality, limits, reliability, and suitability before making a final selection.

## Consequences:

Each team member must bring their API research to the next meeting. The project’s final external API integration cannot be confirmed until the team completes this review.

## Related Vote:

Accepted by all five team members during Group Meeting 2.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/7
