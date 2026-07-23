---

## **Group Meeting 14 @ Sun July 14, 2026 4:00pm \- 5:00pm (EDT)**

Agenda:

- Review next steps from prior meeting  
  - Review MVP Assignment Tasks  
  - Live Demo  
  - Q\&A  
  - Next Steps

- Meeting Information  
- Team name: AutoPilot Engineers  
- Meeting date: **Sunday, July 19, 2026**   
- Start time:4:00 PM  
- End time: 5:15 pm (Main Meeting) 6:54 pm (Demo Meeting)  
- Meeting location or channel: Zoom \- [https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1](https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1)   
- Facilitator: Caitlin Ortiz  
- Note taker: Rosmy Antony  
- Related milestone or deliverable: MVP   
    
- Attendance:

| Team Member | Present? | Notes |
| :---- | :---- | :---- |
| Rosmy A. | Yes |  |
| Caitlin O. | Yes |  |
| Xaidyn L. | Yes |  |
| Tristan D. | Yes |  |
| Noaman S. | Yes |  |

- If someone is absent, note whether they provided an update before or after the meeting.  
    
    
- Meeting Goals  
  - Review progress and next steps from the previous meeting.  
  - Review and confirm each member’s MVP assignment tasks.  
  - Test the MVP features through a live demo and identify anything that still needs fixing.  
  - Answer team questions and agree on the final steps needed for the MVP submission.

  


- Checkpoint Status  
  Summarize the current status of the project areas that matter for this meeting or milestone. Early in the semester, some areas may be "not started yet" or "not required yet."

| Area | Current State | Needs Attention? | Evidence On Link |
| :---- | :---- | :---- | :---- |
| Project Planning | \- | \- | \- |
| GitHub Workflow | Team members were working on their own branches and contributing their assigned MVP files. Changes still needed to be reviewed, merged, and organized for the final demo. | Yes — avoid merge conflicts and make sure the correct working code is merged into the required branch. | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1)  |
| Application Feature Work | Core MVP features were implemented and demonstrated, including account registration, login, profile updates, logout, flight-related features, and other assigned application functions. Some testing and fixes were still needed. | Yes — complete end-to-end testing, fix errors, and confirm each feature meets its acceptance criteria. | [LINK](https://drive.google.com/file/d/1bvKKusnc1ikCQnCTFBsyuwKqJ6DNY0bP/view?usp=sharing) to DEMO |
| Data, API, or message queue work | The application communicated with the API and database components via RabbitMQ. Flight data, authentication requests, and other messages were being tested across the VM architecture. | Yes — verify queue routing, responses, database storage, API error handling, and communication between all services. | [LINK](https://drive.google.com/drive/folders/1WjhpQV9nxC79VuVPXwuQy9_00vtkx86i?usp=drive_link) [LINK](https://drive.google.com/drive/folders/1htxAr1uti6N9R4IPzmpYvXk9w28QrzZV?usp=drive_link) |
| Deployment or server work | The development VMs and services were being used for the MVP demo. The team checked that the App, MQ, API, and DB components were running and accessible. | Yes — keep all VMs online, verify connectivity and service configuration, and prepare for the later QA and production lanes. | [LINK](https://drive.google.com/drive/folders/1htxAr1uti6N9R4IPzmpYvXk9w28QrzZV?usp=drive_link) [LINK](https://drive.google.com/drive/folders/1gSULdTW1yYLTGesR_gtQNvj_R74Yp0f4?usp=drive_link) |

		

- Use only the rows that are relevant. Add rows when the team needs to track something specific such as authentication, logging, a failing VM, or an external API issue.  
    
- Decisions Made \- Record each decision clearly.

| Decision | Reason | Owner | Evidence or Link |
| :---- | :---- | :---- | :---- |
| Use one combined recording for the MVP demonstration. | Keeps the team’s submission organized and allows all required features to be shown in one video. | Rosmy A. Caitlin O. Xaidyn L. Tristan D. Noaman S. | [LINK](https://drive.google.com/file/d/1bvKKusnc1ikCQnCTFBsyuwKqJ6DNY0bP/view?usp=drive_link) |
| Demonstrate the authentication and flight-search features live. | A live demonstration provides visible proof that the required MVP behaviors and acceptance criteria work end to end. | Rosmy A. Caitlin O. Xaidyn L. Tristan D. Noaman S. | [LINK](https://drive.google.com/file/d/1bvKKusnc1ikCQnCTFBsyuwKqJ6DNY0bP/view?usp=drive_link) |
| Keep communication between the App VM and database services routed through RabbitMQ. | Preserves the required multi-VM architecture and prevents the App VM from directly accessing the database. | Rosmy A. Caitlin O. Xaidyn L. Tristan D. Noaman S. | [LINK](https://docs.google.com/document/d/1vHp27VC99WBMHw24rhIDhkcy7VimSMG8S7UfQjy0Lb0/edit?usp=sharing) |
| Complete and test changes on individual feature branches before merging them. | Reduces merge conflicts, protects working code, and preserves clear GitHub evidence for each team member’s contribution. | Rosmy A. Caitlin O. Xaidyn L. Tristan D. Noaman S. | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1) |

- Good decision notes explain why the team chose one path over another, especially when the choice affects architecture, data, deployment, security, or milestone scope.  
    
- Task Updates  
- Each team member should report what changed since the last meeting. Keep updates tied to visible work, blocked work, or decisions the team needs to make.  
- 

| Team Member | Completed Since Last Meeting | In Progress | Blocked By | GitHub Evidence |
| :---- | :---- | :---- | :---- | :---- |
| Caitlin O. | Reviewed RabbitMQ setup and helped verify that team services could communicate during MVP testing. Coordinated the meeting and live-demo preparation. | Final RabbitMQ routing checks and organizing the MVP submission evidence. | N/A | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1) |
| Xaidyn L. | Continued implementing and testing the flight-search workflow and external API responses. Helped verify that flight information could be returned to the application. | Testing flight-status results, missing-data responses, and API error handling. | N/A | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1) |
| Rosmy A. | Completed and demonstrated the application-side authentication workflow, including registration, login, profile updates, and logout. Continued integrating the frontend with RabbitMQ requests. | Final end-to-end authentication testing, interface fixes. | N/A | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1) |
| Tristan D. | Continued database-side work and helped verify that user and application data were stored correctly. Reviewed database evidence needed for the MVP. | Final database validation and confirming that updates are processed correctly through RabbitMQ. | N/A | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1) |

- GitHub evidence can include issues, branches, commits, pull requests, reviews, project board cards, or documentation updates. If there is no evidence yet, list the next action that will create it.  
    
- Action Items

| Action Item | Owner | Due Date | GitHub Issue or PR | Done Criteria |
| :---- | :---- | :---- | :---- | :---- |
| Complete final testing of registration, login, profile updates, password changes, and logout. | Rosmy A. | Before MVP submission | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/113) | All authentication features work correctly, valid and invalid inputs display the expected messages, sessions persist, and logout blocks protected-page access. |
| Complete flight-search testing and confirm that results and API errors display correctly. | Xaidyn L. | Before MVP submission | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/114) | A user can search for a flight, valid flight data is displayed, and missing or failed API responses produce a clear error message. |
| Complete and test the project’s admin page. | Caitlin O. | Before MVP submission | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/99) | The admin page loads correctly and allows an authorized admin to review and manage users and reports. |
| Verify database storage and updates for authentication and MVP data. | Tristan D. | Before MVP submission | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1) | Registration and profile changes are stored correctly, passwords remain hashed, and database evidence is captured. |
| Review member branches and merge the approved MVP changes into the required shared branch. | Entire Team | Before MVP submission | [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1) | Required pull requests are reviewed and merged without conflicts, and the shared branch contains the working MVP version. |
| Record and organize the final live MVP demonstration. | Caitlin O. | Before MVP submission | [LINK](https://drive.google.com/drive/folders/1BJ25S-sDOpf4jtQor3t8OtoNODo4hXQD?usp=drive_link) | The recording demonstrates all required MVP acceptance criteria and is uploaded as one accessible submission video. |
| Collect final GitHub, VM, RabbitMQ, API, and database evidence. | Entire team | Before MVP submission | [LINK](https://docs.google.com/document/d/1vHp27VC99WBMHw24rhIDhkcy7VimSMG8S7UfQjy0Lb0/edit?usp=sharing) | Screenshots, logs, commits, pull requests, and test results are added to the submission document. |


- Done criteria should describe observable evidence, such as "endpoint returns expected JSON", "pull request approved and merged", or "server log screenshot added to docs".  
    
- Risks And Blockers  
  List risks or blockers that could affect the next milestone. Leave this section short if there are no active blockers.


| Risk Or Blocker | Impact | Current Plan | Owner |
| :---- | :---- | :---- | :---- |
| N/A | N/A | N/A | N/A |
| N/A | N/A | N/A | N/A |

- Include technical blockers, team communication issues, missing credentials, server problems, unstable APIs, merge conflicts, unclear requirements, or testing gaps.  
    
- Evidence To Save  
  - GitHub issues: Save links to all issues showing assigned tasks, progress updates, completed work, and acceptance criteria.  
  - Branches: Save the names and links for each team member’s branch that contains their MVP work.  
  - Reflections: Complete and save each team member’s required reflection portion before submission.  
  - Screenshots: Capture screenshots of all working MVP features, GitHub activity, terminal commands, logs, and test results.  
  - Local-to-remote code upload evidence: Save terminal output showing code files being added, committed, and pushed from local branches to the remote GitHub repository.  
  - VM evidence: Capture screenshots and command output from the App, MQ, API, and DB VMs showing services running, network connectivity, application tests, RabbitMQ communication, and database results.  
  - Pull requests and commits: Save links to the commits and pull requests used to review and merge completed work.  
  - Final demo evidence: Save the meeting recording, live-demo recording, and submission links.


- GitHub repository, branch, issue, pull request, or commit links: [LINK](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/4?closed=1)  
- Server screenshots or command output: [LINK](https://docs.google.com/document/d/1vHp27VC99WBMHw24rhIDhkcy7VimSMG8S7UfQjy0Lb0/edit?usp=sharing)  
- Logs or monitoring output: [LINK](https://docs.google.com/document/d/1vHp27VC99WBMHw24rhIDhkcy7VimSMG8S7UfQjy0Lb0/edit?usp=sharing)  
- Architecture diagram updates:  
- Deployment URLs:  
- API test output: [LINK](https://docs.google.com/document/d/1vHp27VC99WBMHw24rhIDhkcy7VimSMG8S7UfQjy0Lb0/edit?usp=sharing)  
- Database evidence: [LINK](https://docs.google.com/document/d/1vHp27VC99WBMHw24rhIDhkcy7VimSMG8S7UfQjy0Lb0/edit?usp=sharing)  
- Team communication evidence:  
- Next Meeting  
  - Next meeting date: 7/22/26  
  - Main focus:   
    - Review next steps from prior meeting  
    - Review screenshots from MVP  
    - Review Milestone 3 and assign tasks  
  - Preparation needed before meeting:  
- AI Or Tool Assistance  
- Disclose any meaningful AI or automation help used for planning, troubleshooting, code, documentation, or meeting notes. Include what it helped with, what the team changed, and how the result was checked. If none was used, write "No meaningful AI assistance used."  
  - No AI assistance   
- Link of Meeting Recording: [https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk\_jC7V3HezHz?usp=drive\_link](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=drive_link) 

- Additional notes (If needed):