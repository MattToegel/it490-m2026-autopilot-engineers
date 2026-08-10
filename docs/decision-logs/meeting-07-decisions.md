# Meeting 7 Decision Log

**Meeting Date:** June 21, 2026

**Meeting:** Group Meeting 7

**Team:** AutoPilot Engineers

**Facilitator:** Caitlin Ortiz

**Note Taker:** Tristan Duncan

**Related Meeting Notes:** Group Meeting 7 notes

---

## Decision D-034 - Standardize the RabbitMQ Message JSON Format

## Date:

June 21, 2026

## Status:

Accepted

## Decision:

The team will use a consistent JSON message format for communication between the App, API, Database, and RabbitMQ-related components.

## Rationale:

All VMs must exchange messages in the same structure so publishers and consumers can correctly interpret the data. Using a uniform JSON format reduces parsing errors and prevents different components from sending incompatible message structures.

## Consequences:

All RabbitMQ publisher and consumer scripts must follow the agreed JSON structure. Changes to the message format must be coordinated across the team so that all components remain compatible.

## Related Vote:

Accepted by all five team members during Group Meeting 7.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/45

---

## Decision D-035 - Standardize the VM Repository File Structure

## Date:

June 21, 2026

## Status:

Accepted

## Decision:

The team will use a consistent file structure across project VMs for shared configuration files, RabbitMQ scripts, database-related files, and environment configuration.

## Rationale:

A standardized structure makes it easier for team members to locate configuration and script files across different VMs. It also simplifies shared setup, troubleshooting, and documentation.

## Consequences:

Team members must organize project files using the agreed directory structure. Shared scripts and configuration files should use consistent paths whenever possible.

## Related Vote:

Accepted by all five team members during Group Meeting 7.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/45

---

## Decision D-036 - Store Credentials in `.env` Files and Exclude Them from Git

## Date:

June 21, 2026

## Status:

Accepted

## Decision:

Sensitive credentials such as RabbitMQ and MySQL usernames, passwords, hosts, and other configuration values will be stored in `.env` files. These files will be excluded from the GitHub repository using `.gitignore`.

## Rationale:

Credentials should not be committed to the shared repository. Using environment files allows each VM to maintain its required configuration without exposing passwords or other sensitive values in source control.

## Consequences:

Scripts must load required credentials from environment configuration instead of hardcoding them directly in committed source files. `.env` files must remain excluded from Git, and team members must maintain the required values locally on their assigned VMs.

## Related Vote:

Accepted by all five team members during Group Meeting 7.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/45

---

## Decision D-037 - Use Dedicated RabbitMQ Queues for Project Components

## Date:

June 21, 2026

## Status:

Accepted

## Decision:

The RabbitMQ server will use designated message queues and routing configuration so the App, API, and Database-related components can publish and consume the messages intended for their roles.

## Rationale:

The project architecture requires RabbitMQ to act as the message broker between separate VMs. Dedicated queues and routing configuration allow messages to reach the correct component instead of being handled by unrelated consumers.

## Consequences:

The RabbitMQ queue setup must define the required queues, exchanges, bindings, and routing keys. Publisher and consumer scripts must use the correct routing configuration for their assigned component.

## Related Vote:

Accepted by the team during Group Meeting 7 after reviewing Caitlin Ortiz’s RabbitMQ queue setup.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/45

---

## Decision D-038 - Complete Centralized Logging Through RabbitMQ

## Date:

June 21, 2026

## Status:

Accepted

## Decision:

The team will implement centralized logging using RabbitMQ so that the App, API, and Database servers can publish log messages and the appropriate consumer can receive and process them through the project message-queue architecture.

## Rationale:

Milestone 1 requires centralized logging behavior across multiple project components. Using RabbitMQ allows the distributed VMs to send logging messages through the existing messaging infrastructure instead of maintaining isolated logging systems.

## Consequences:

The team must complete and test the required publisher and consumer scripts. The App, API, and DB VMs must be able to publish messages successfully, while RabbitMQ must route the messages through the configured queues.

## Related Vote:

Accepted by all five team members during Group Meeting 7.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/45

---

## Decision D-039 - Split Milestone 1 Response Questions Across Team Members

## Date:

June 21, 2026

## Status:

Accepted

## Decision:

The written Milestone 1 questions and documentation responsibilities will be divided among team members, with individual GitHub issues used to document the work split where applicable.

## Rationale:

Dividing the milestone questions allows multiple sections to be completed at the same time and gives each team member clear ownership of part of the submission.

## Consequences:

Each assigned team member must complete their section, add the response to the shared Team Responsibilities document, and provide supporting GitHub evidence when required.

## Related Vote:

Accepted by all five team members during Group Meeting 7.

## Related Issue/PR Links:

https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/45
