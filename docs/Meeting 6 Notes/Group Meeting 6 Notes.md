---

## **Group Meeting 6 @ Fri Jun 19, 2026 1:00pm \- 2:00pm (EDT)**

Agenda:

- Review next steps from prior meeting   
- Review Module 3   
- Assign Tasks/Roles for Module 3 Milestone 2   
- Next Steps   
- Q\&A 

- Meeting Information  
- Team name: AutoPilot Engineers  
- Meeting date:Friday, June 19, 2026   
- Start time: 1:10pm  
- End time:1:54PM  
- Meeting location or channel: Zoom \-   
- [https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1](https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1)   
- Facilitator: Caitlin Ortiz	  
- Note taker: Caitlin Ortiz  
- Related milestone or deliverable:  
    
- Attendance:

| Team Member | Present? | Notes |
| :---- | :---- | :---- |
| [Caitlin Ortiz](mailto:cao39@njit.edu) | Yes |  |
| Noaman Shahid | Yes |  |
| Rosmy Antony | Yes |  |
| Tristan Duncan | Yes |  |
| Xaidyn Liranzo | Yes |  |

- If someone is absent, note whether they provided an update before or after the meeting.  
    
- Meeting Goals  
  - Check-in on Milestone 1 status  
  - Ensure VMs are setup for Milestone 1  
  - Review Milestone 2  
      
- Checkpoint Status  
  Summarize the current status of the project areas that matter for this meeting or milestone. Early in the semester, some areas may be "not started yet" or "not required yet."

| Area | Current State | Needs Attention? | Evidence On Link |
| :---- | :---- | :---- | :---- |
| Project Planning | Done | No as it is still pending Professor approval. | [https://docs.google.com/document/d/1jx4oN-L4ZmAsTHRSFy4U3N0kojVeXm6egBuTDpgLU00/edit?usp=drive\_link](https://docs.google.com/document/d/1jx4oN-L4ZmAsTHRSFy4U3N0kojVeXm6egBuTDpgLU00/edit?usp=drive_link)  |
| GitHub Workflow | In Progress | The team is working together on the Milestone 1 group assignment. To get credit for the assignment, each member needs to create or update their assigned GitHub issues and document their work.  The meeting facilitator also needs to upload meeting notes to close Issue \#44. | Current Issue List \- See Issues marked  “[Milestone 1](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues)” Meeting Notes Issue \- [\#44](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/44) |
| Application Feature Work | Not Started Yet |  |  |
| Data, API, or message queue work | In Progress | Per Milestone 1, we are working on creating our message queues to ensure that we can send and receive through RabbitMQ. API’s have been selected as well. See the API selection within our proposal. | [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/36) [**Proposal w/API information**](https://docs.google.com/document/d/1jx4oN-L4ZmAsTHRSFy4U3N0kojVeXm6egBuTDpgLU00/edit?usp=sharing)  |
| Deployment or server work | In Progress | Currently working on the server to prepare for the next steps of the project. | [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/36) |

		

- Use only the rows that are relevant. Add rows when the team needs to track something specific such as authentication, logging, a failing VM, or an external API issue.  
    
- Decisions Made \- Record each decision clearly.

| Decision | Reason | Owner | Evidence or Link |
| :---- | :---- | :---- | :---- |
| Noaman to create a VM  | A VM needs to be created to ssh into Rosmy’s VM based on assigned roles and to finish scripts | Noaman S.  | Discussed uring meeting: [https://drive.google.com/file/d/1gaq3Zolu9zdks\_RXrB5J79uM\_pmId2ju/view?usp=drive\_link](https://drive.google.com/file/d/1gaq3Zolu9zdks_RXrB5J79uM_pmId2ju/view?usp=drive_link)  |
| Team will meet on Sunday, 6/21 to go over logging scripts | Team wants to ensure that we are all keeping a similar format and troubleshoot errors together | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | To be discussed in our upcoming meeting \- [https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1](https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1)  |
| User for the Rabbit MQ connection will be it490 | This is to make sure we are all using the same user to connect to Rabbit MQ as there can be complications if our servers run under different users when trying to send messages through. | Caitlin O.  | Discussed uring meeting: [https://drive.google.com/file/d/1gaq3Zolu9zdks\_RXrB5J79uM\_pmId2ju/view?usp=drive\_link](https://drive.google.com/file/d/1gaq3Zolu9zdks_RXrB5J79uM_pmId2ju/view?usp=drive_link)  |
| Change start time for the 6/21 meeting to 3pm | Updating the start time for the 6/21 meeting to 3pm to allot more time for troubleshooting | Caitlin O.  | [https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1](https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1)  |

- Good decision notes explain why the team chose one path over another, especially when the choice affects architecture, data, deployment, security, or milestone scope.  
    
- Task Updates  
- Each team member should report what changed since the last meeting. Keep updates tied to visible work, blocked work, or decisions the team needs to make.  
- 

| Team Member | Completed Since Last Meeting | In Progress | Blocked By | GitHub Evidence |
| :---- | :---- | :---- | :---- | :---- |
| Rosmy A. Caitlin O. Tristan D. Xaidyn L.  | Complete the VM based on your assigned role. | Noaman S. is still working on creating a VM to ssh into the Rosmy’s VM |  | [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/36](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/36)  [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/38](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/38)  [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/39](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/39)  [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/40](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/40)  |
| Rosmy A. Caitlin O. Tristan D. Xaidyn L. | Setup scripts for each assigned VMs have been created |  |  | \-[https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/37](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/37)     \=[https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/41](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/41)    \-[https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/42](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/42)    \-[https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/43](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/43)    |

- GitHub evidence can include issues, branches, commits, pull requests, reviews, project board cards, or documentation updates. If there is no evidence yet, list the next action that will create it.  
    
- Action Items

| Action Item | Owner | Due Date | GitHub Issue or PR | Done Criteria |
| :---- | :---- | :---- | :---- | :---- |
|  Logging scripts  | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | 6/21 | Pending GitHub Issue/PR | Scripts are able to log messages while the VMs are on the same network |
| Complete the Milestone 1 responses  | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | 6/21 | The responses will be in the completed Milestone 1 form submitted to the PR | All responses, Github links, and screenshots have been added to the Milestone 1 form to be submitted to the PR and submitted to Canvas |
| Review Milestone 2’s assignment | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | 6/22 | N/A | Review the assignment on Canvas  |


- Done criteria should describe observable evidence, such as "endpoint returns expected JSON", "pull request approved and merged", or "server log screenshot added to docs".  
    
- Risks And Blockers  
  List risks or blockers that could affect the next milestone. Leave this section short if there are no active blockers.


| Risk Or Blocker | Impact | Current Plan | Owner |
| :---- | :---- | :---- | :---- |
| VMs not created  | Not having a VM created can suspend the team’s progress to finish tasks on time | Team provided resources to create a VM and join the team’s Tailscale network | Noaman S.  |
|  |  |  |  |

- Include technical blockers, team communication issues, missing credentials, server problems, unstable APIs, merge conflicts, unclear requirements, or testing gaps.  
    
- Evidence To Save  
- Screenshots of VM creation added to an issue in Github  
- Screenshots of setup scripts for each teammate and their dedicated VM  
  - GitHub repository, branch, issue, pull request, or commit links:  
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/36](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/36)    
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/38](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/38)     
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/39](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/39)    
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/40](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/40)   
  - Server screenshots or command output:  
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/37](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/37)   
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/41](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/41)   
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/42](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/42)   
    [https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/43](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/43)   
  - Logs or monitoring output: N/A  
  - Architecture diagram updates: Creating the VMs this week to reflect Architecture diagram  
  - Deployment URLs: N/A  
  - API test output: N/A  
- Database evidence: N/A  
- Team communication evidence: [https://drive.google.com/file/d/1gaq3Zolu9zdks\_RXrB5J79uM\_pmId2ju/view?usp=drive\_link](https://drive.google.com/file/d/1gaq3Zolu9zdks_RXrB5J79uM_pmId2ju/view?usp=drive_link) & Discord  
- Next Meeting  
- Next meeting date:6/21/2026  
- Main focus:  
  -  Create the centralized-logging Github branch   
  - Work on/testing logging scripts  
  - Combine the screenshots and scripts into a pull request  
- Preparation needed before meeting:  
  - All VMs are created  
  - Setup scripts can be run without errors  
  - Review statements that can be used for logging and the workflow we have to follow per the IT490 system architecture  
- AI Or Tool Assistance  
- Disclose any meaningful AI or automation help used for planning, troubleshooting, code, documentation, or meeting notes. Include what it helped with, what the team changed, and how the result was checked. If none was used, write "No meaningful AI assistance used."  
  - No meaningful AI assistance used  
- Link of Meeting Recording: [https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk\_jC7V3HezHz?usp=drive\_link](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=drive_link)

- Additional notes (If needed):