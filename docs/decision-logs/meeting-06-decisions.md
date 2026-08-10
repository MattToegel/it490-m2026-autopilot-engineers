# Meeting 6 Decision Log

**Meeting Date:** June 19, 2026

**Meeting:** Group Meeting 6

**Team:** AutoPilot Engineers

**Facilitator:** Caitlin Ortiz

**Note Taker:** Caitlin Ortiz

**Related Meeting Notes:** Group Meeting 6 notes

---

## Decision D-028 - Require Noaman to Create a Project VM

## Date:

June 19, 2026

## Status:

Accepted

## Decision:

Noaman Shahid will create and configure a project VM so that he can participate in the team’s cross-VM work and SSH into Rosmy Antony’s VM when required by the assigned project responsibilities.

## Rationale:

The team’s distributed architecture depends on each member having an available VM. Without Noaman’s VM, some cross-VM testing and script work could not be completed as planned.

## Consequences:

Noaman must create the VM, connect it to the team’s network, and verify that he can use it for SSH and project testing. The missing VM was identified as a blocker that could delay milestone progress.

## Related Vote:

Accepted by the team during Group Meeting 6.

## Related Issue/PR Links:

* Group Meeting 6 notes
* Related VM setup issue, when available
* VM creation evidence
* Meeting recording

---

## Decision D-029 - Standardize RabbitMQ Connections on the `it490` User

## Date:

June 19, 2026

## Status:

Accepted

## Decision:

The team will use `it490` as the standard RabbitMQ user for project message-queue connections.

## Rationale:

Using the same RabbitMQ account across the project reduces configuration differences between servers and helps avoid authentication or connection problems caused by team members using different RabbitMQ usernames.

## Consequences:

RabbitMQ client and server configuration files must use the `it490` user where applicable. Team members must ensure that this user has the required RabbitMQ permissions for the project queues and virtual host.

## Related Vote:

Accepted by the team during Group Meeting 6.

## Related Issue/PR Links:

* Group Meeting 6 notes
* RabbitMQ configuration issues
* Setup scripts
* Message queue documentation
* Meeting recording

---

## Decision D-030 - Hold a Dedicated Centralized Logging Work Session

## Date:

June 19, 2026

## Status:

Accepted

## Decision:

The team will use the June 21, 2026 meeting as a dedicated work session for centralized logging scripts, troubleshooting, and integration.

## Rationale:

The team wanted to keep logging implementations consistent across components and troubleshoot errors together instead of developing incompatible logging approaches independently.

## Consequences:

Team members must review logging requirements before the meeting and bring working setup scripts and VMs. The session will be used to create, test, and align centralized logging behavior across the project architecture.

## Related Vote:

Accepted by all five team members during Group Meeting 6.

## Related Issue/PR Links:

* Group Meeting 6 notes
* Centralized logging issue or branch
* Logging script issues
* Related pull requests
* Meeting recording

---

## Decision D-031 - Create a Dedicated Centralized Logging Branch

## Date:

June 19, 2026

## Status:

Accepted

## Decision:

The team will create a dedicated GitHub branch for centralized logging work.

## Rationale:

Centralized logging affects multiple project components and requires coordinated script changes, testing, screenshots, and documentation. Keeping this work on a dedicated branch allows the team to combine and review the changes before merging them into the main project branch.

## Consequences:

Logging scripts, supporting documentation, screenshots, and related changes should be collected on the centralized logging branch before being submitted through a pull request.

## Related Vote:

Accepted by the team as part of the preparation for the June 21 logging work session.

## Related Issue/PR Links:

* Group Meeting 6 notes
* Centralized logging branch
* Centralized logging GitHub issue
* Related pull request, when created

---

## Decision D-032 - Use Consistent Logging Behavior Across Project Components

## Date:

June 19, 2026

## Status:

Accepted

## Decision:

The team will coordinate the logging scripts so that project components follow a similar logging format and workflow.

## Rationale:

Using a consistent format makes centralized logs easier to review, troubleshoot, and document. It also helps ensure that App, RabbitMQ, Database, and API-related components produce compatible logging information.

## Consequences:

Team members developing logging scripts must coordinate message format, required fields, and test behavior. Any inconsistent implementations should be corrected during the centralized logging work session.

## Related Vote:

Accepted by the team during Group Meeting 6.

## Related Issue/PR Links:

* Group Meeting 6 notes
* Centralized logging documentation
* Logging scripts
* Related GitHub issues and pull requests

---

## Decision D-033 - Move the June 21 Meeting to 3:00 p.m.

## Date:

June 19, 2026

## Status:

Accepted

## Decision:

The June 21, 2026 team meeting will begin at 3:00 p.m. Eastern Time.

## Rationale:

The earlier start gives the team additional time for logging implementation, troubleshooting, testing, and milestone preparation.

## Consequences:

All team members must plan to attend the June 21 work session beginning at 3:00 p.m.

## Related Vote:

Accepted by the team during Group Meeting 6.

## Related Issue/PR Links:

* Group Meeting 6 notes
* Team Zoom meeting
* Team schedule
