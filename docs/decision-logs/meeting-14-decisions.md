# Meeting 14 Decision Log

**Meeting Date:** July 19, 2026

**Meeting:** Group Meeting 14

**Team:** AutoPilot Engineers

**Facilitator:** Caitlin Ortiz

**Note Taker:** Rosmy Antony

**Related Milestone:** MVP

**Related Meeting Notes:** Group Meeting 14 notes

---

## Decision D-072 - Use One Combined Recording for the MVP Demonstration

## Date:

July 19, 2026

## Status:

Accepted

## Decision:

The team will use one combined video recording for the final MVP demonstration.

## Rationale:

Using one recording keeps the submission organized and allows all required MVP functionality to be demonstrated in one continuous video rather than across several separate recordings.

## Consequences:

All required MVP acceptance criteria must be demonstrated within the combined recording. Team members must coordinate the order of the demo and make sure all required services and VMs are available during recording.

## Related Vote:

Accepted by all five team members during Group Meeting 14.

## Related Issue/PR Links:

* Group Meeting 14 notes
* MVP demonstration recording
* MVP submission materials
* Related evidence folder

---

## Decision D-073 - Demonstrate Authentication and Flight Search Live

## Date:

July 19, 2026

## Status:

Accepted

## Decision:

The MVP demonstration will include live end-to-end testing of the required authentication and flight-search workflows.

## Rationale:

A live demonstration provides visible evidence that the required behaviors work across the actual project architecture instead of only showing screenshots or isolated code.

## Consequences:

The App, RabbitMQ, API Worker, and Database services must be running and reachable during the demo. Registration, login, account-management behavior, and flight-search behavior must be tested before recording.

## Related Vote:

Accepted by all five team members during Group Meeting 14.

## Related Issue/PR Links:

* Group Meeting 14 notes
* MVP demo recording
* Authentication issue #113
* Flight-search issue #114
* MVP acceptance-criteria evidence

---

## Decision D-074 - Require Feature Branch Testing Before MVP Merge

## Date:

July 19, 2026

## Status:

Accepted

## Decision:

Each team member must complete and test their assigned MVP changes on their individual feature branch before those changes are merged into the shared MVP branch.

## Rationale:

Testing changes before merging reduces the likelihood of broken code, merge conflicts, and incomplete functionality entering the shared MVP version.

## Consequences:

Pull requests should only be merged after the corresponding feature has been tested and reviewed. Conflicting or incomplete changes must be corrected on the feature branch before integration.

## Related Vote:

Accepted by all five team members during Group Meeting 14.

## Related Issue/PR Links:

* Group Meeting 14 notes
* MVP GitHub milestone
* Individual feature branches
* Related pull requests
* Final shared MVP branch

---

## Decision D-075 - Perform Final End-to-End Acceptance Testing Before MVP Submission

## Date:

July 19, 2026

## Status:

Accepted

## Decision:

The team will complete final end-to-end testing of the required MVP workflows before submitting the milestone.

## Rationale:

The team needs to confirm that the integrated application satisfies the required acceptance criteria and that individual features continue to work after branch merges.

## Consequences:

Authentication, flight search, API responses, database updates, RabbitMQ communication, and other required MVP behaviors must be verified before final submission. Any failed test must be corrected and retested.

## Related Vote:

Accepted by the team during Group Meeting 14.

## Related Issue/PR Links:

* Group Meeting 14 notes
* MVP issues
* Authentication issue #113
* Flight-search issue #114
* MVP demonstration evidence
* Final MVP pull requests

---

## Decision D-076 - Collect Final MVP Evidence in a Shared Submission Set

## Date:

July 19, 2026

## Status:

Accepted

## Decision:

The team will collect the final MVP GitHub, VM, RabbitMQ, API, database, screenshot, test-result, and demo evidence into the shared submission materials.

## Rationale:

The final MVP submission requires evidence from multiple project components. Keeping the evidence together allows the team to verify that every requirement is supported before submission.

## Consequences:

Each team member must provide evidence for their assigned work, including relevant GitHub issues, branches, commits, pull requests, screenshots, terminal output, test results, and VM evidence.

## Related Vote:

Accepted by all five team members during Group Meeting 14.

## Related Issue/PR Links:

* Group Meeting 14 notes
* MVP evidence document
* MVP GitHub milestone
* Final demonstration recording
* Related GitHub issues and pull requests
