# Meeting 15 Decision Log

**Meeting Date:** July 22, 2026

**Meeting:** Group Meeting 15

**Team:** AutoPilot Engineers

**Facilitator:** Caitlin Ortiz

**Note Taker:** Noaman Shahid

**Related Milestone:** Milestone 3

**Related Meeting Notes:** Group Meeting 15 notes

---

## Decision D-077 - Create QA and Production VMs for Each Assigned Role

## Date:

July 22, 2026

## Status:

Accepted

## Decision:

Each team member will create separate QA and Production VMs corresponding to their assigned project role.

## Rationale:

Milestone 3 requires the project to expand beyond the existing development environment into separate QA and Production deployment lanes.

## Consequences:

The team will maintain Dev, QA, and Production environments for the project architecture. Each component owner must create and configure the QA and Production versions of their assigned server role.

## Related Vote:

Accepted by all five team members during Group Meeting 15.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/5

---

## Decision D-078 - Create Backups Before Promoting Changes Between Environments

## Date:

July 22, 2026

## Status:

Accepted

## Decision:

The team will create backups before promoting changes from Development to QA or between other deployment environments.

## Rationale:

A backup provides a recovery point if a promoted change introduces an error or causes the target environment to stop working correctly.

## Consequences:

The promotion workflow must include a backup step before files or configuration changes are applied to the target environment. The team must retain enough information to restore the previous working state when necessary.

## Related Vote:

Accepted by the team during Group Meeting 15.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/5

---

## Decision D-079 - Use a Shared `inventory.json` for Environment Information

## Date:

July 22, 2026

## Status:

Accepted

## Decision:

The Milestone 3 promotion process will use an `inventory.json` file to organize information about the project environments and target servers.

## Rationale:

Tristan demonstrated the inventory configuration during the meeting. Keeping environment information in a shared configuration file reduces repeated manual entry and gives the promotion process a consistent source for server and environment details.

## Consequences:

Promotion scripts can read required environment information from `inventory.json` instead of requiring users to repeatedly enter server details manually. The inventory must be maintained when environment information changes.

## Related Vote:

Accepted by the team during Group Meeting 15 after reviewing the `inventory.json` approach.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/5

---

## Decision D-080 - Use a Shared Tracker to Assign Milestone 3 Responses

## Date:

July 22, 2026

## Status:

Accepted

## Decision:

Caitlin Ortiz will provide a shared Milestone 3 response sheet so team members can assign themselves specific written response sections.

## Rationale:

A shared assignment tracker gives the team visibility into which responses already have owners and prevents multiple members from completing the same section unnecessarily.

## Consequences:

Team members must select their assigned responses from the shared sheet and complete the corresponding documentation by the required deadline.

## Related Vote:

Accepted by the team during Group Meeting 15.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/5

---

## Decision D-081 - Require Every Team Member to Complete Milestone 3 Section 4

## Date:

July 22, 2026

## Status:

Accepted

## Decision:

Every team member will complete the required work associated with Section 4 of Milestone 3.

## Rationale:

Section 4 requires individual participation or evidence from each member rather than being completed by only one representative for the group.

## Consequences:

Each member must provide their own required Section 4 work and supporting evidence. Completion of another team member’s Section 4 response does not satisfy the requirement for the rest of the team.

## Related Vote:

Accepted by all five team members during Group Meeting 15.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/5

---

## Decision D-082 - Begin Maintaining Conversation Logs for Milestone 3 Work

## Date:

July 22, 2026

## Status:

Accepted

## Decision:

The team will begin maintaining the required conversation logs for Milestone 3, with Caitlin Ortiz beginning the initial logging work.

## Rationale:

The milestone requires documented evidence of project discussions and decisions. Maintaining the logs as work progresses is easier and more accurate than reconstructing the conversations at the end of the milestone.

## Consequences:

Relevant project discussions must be captured in the required conversation-log format and included with the team’s Milestone 3 documentation.

## Related Vote:

Accepted by the team during Group Meeting 15.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/5
