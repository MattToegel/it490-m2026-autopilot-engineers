---

## **Group Meeting 7 @ Sun Jun 21, 2026 3:00pm \- 5:00pm (EDT)**

Agenda:

- Review next steps from prior meeting   
- Work on Milestone 1   
- Next Steps   
- Q\&A 

- Meeting Information  
- Team name: AutoPilot Engineers  
- Meeting date: Sunday, June 21, 2026   
- Start time: **3:00PM**  
- End time: **5:09 PM**  
- Meeting location or channel: Zoom \-   
- [https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1](https://njit-edu.zoom.us/j/98538580202?pwd=BbZLFpaVKvolH9beZ16UbvrTdAtaC5.1)   
- Facilitator: Caitlin Ortiz  
- Note taker: Tristan Duncan  
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
  - List one to three goals for this meeting.  
  - Go over RabbitMQ centralized logging logic  
  - Decide message JSON format  
  - Create php logging scripts  
  - Setup repo github file environment  
      
- Checkpoint Status  
  Summarize the current status of the project areas that matter for this meeting or milestone. Early in the semester, some areas may be "not started yet" or "not required yet."

| Area | Current State | Needs Attention? | Evidence On Link |
| :---- | :---- | :---- | :---- |
| Project Planning | Done | The proposal is still waiting for approval. | [Proposal](https://docs.google.com/document/d/1jx4oN-L4ZmAsTHRSFy4U3N0kojVeXm6egBuTDpgLU00/edit?usp=drive_link%20) |
| GitHub Workflow | In Progress | Milestone 1 group assignment is still in progress. Tasks are tracked by issues tied to each person on Github.Upload meeting notes for Issue \#45 | Current Issue List:  “[Milestone 1](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues)” [Issue \#45](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/45) |
| Application Feature Work | Not Started Yet |  |  |
| Data, API, or message queue work | In Progress | Currently creating the message queues and logging publisher and consumer scripts so that we can send and receive messages through RabbitMQ. See the API selection within our proposal.  | [Github Issues](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues) [Proposal w/API information](https://docs.google.com/document/d/1jx4oN-L4ZmAsTHRSFy4U3N0kojVeXm6egBuTDpgLU00/edit?usp=sharing)  |
| Deployment or server work | In Progress | Servers should be set up and running for Milestone 1 work.  The team will proceed with Tasks 3-6 for the main RabbitMQ message logging logic. | [Milestone 1](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/1) |

		

- Use only the rows that are relevant. Add rows when the team needs to track something specific such as authentication, logging, a failing VM, or an external API issue.  
    
- Decisions Made \- Record each decision clearly.

| Decision | Reason | Owner | Evidence or Link |
| :---- | :---- | :---- | :---- |
| Decided message JSON format. | Message format must be uniform for all vm so that there are no errors. | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | See Google Drive [Meeting 7 Video](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=sharing) |
| Decided VM File structure. | Efficiently pull credentials for rabbitmq and mysql  | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | See Google Drive [Meeting 7 Video](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=sharing) |
| Decided RabbitMQ queue setup | Cailtin has set up the message queues for each vm so that the mq server can effectively forward messages. | Caitlin O. | See Google Drive [Meeting 7 Video](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=sharing) |
| Decided Milestone 1 task question split. | To split the workload, the milestone 1 questions have been split between team members for completion.Issues will be created on github to show work split. | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | See Google Drive [Meeting 7 Video](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=sharing) |
| Decided vm repo file structure  | To mitigate credentials being uploaded into the repo, the team decided to implement .env files for .gitignore and use shared, commonly used files. | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | See Google Drive [Meeting 7 Video](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=sharing) |

- Good decision notes explain why the team chose one path over another, especially when the choice affects architecture, data, deployment, security, or milestone scope.  
    
- Task Updates  
- Each team member should report what changed since the last meeting. Keep updates tied to visible work, blocked work, or decisions the team needs to make.  
- 

| Team Member | Completed Since Last Meeting | In Progress | Blocked By | GitHub Evidence |
| :---- | :---- | :---- | :---- | :---- |
| Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | Screenshot of the VMs and setup scripts testing is due  along with documentation. (OVERDUE)  | SHOULD BE COMPLETE | N/A | See [Milestone 1](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/1) Issues |
| Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | Creating issues for setup scripts on Sunday. (OVERDUE) | SHOULD BE COMPLETE | N/A | See [Milestone 1](https://github.com/MattToegel/it490-m2026-autopilot-engineers/milestone/1) Issues |
| Noaman S.  | Upload group meeting 5 notes to the repository. (OVERDUE) | In Progress | N/A | See [Issue \#35](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/35) |

- GitHub evidence can include issues, branches, commits, pull requests, reviews, project board cards, or documentation updates. If there is no evidence yet, list the next action that will create it.  
    
- Action Items

| Action Item | Owner | Due Date | GitHub Issue or PR | Done Criteria |
| :---- | :---- | :---- | :---- | :---- |
| Task 3-6 centralized logging message queue logic to be done by Monday for Milestone 1\. | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | 6/22/2026 | In Progress | The APP, API, and DB servers can effectively publish and consume messages. The MQ server can effectively act as a message broker and send messages over set up queues.  |
| Task 3-4  questions to be answered by Monday 11.59 p.m.Create issues for questions 3-4. | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  | 6/22/2026 | In Progress | Everyone has posted their answers to the questions in the [Team Responsibilities](https://docs.google.com/document/d/1w8rYQ3RzV0sdpgm1yOgthC30k5WPPm-nSWVxj1zgqY0/edit?usp=sharing) document in the drive.   |
| Set up github issues for milestone 1 tasks 1-2 Fill out meeting 5 notes and submit on github issue \#35Complete RabbitMQ Logging scripts by Monday 8 p.m. | Noaman S.  | 6/22/2026 | In Progress | Noaman has created the github issues for the milestone 1, tasks 1-2.  He has filled out the meeting 5 notes to completion and submitted to the github [issue \#35](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/35). Noaman can remote into Rosmy’s appdev vm and complete the necessary milestone 1 logging work. |
| Create a Github issue and Upload Milestone 1, Task 3 queue setup code and document queue and routing key setup in the google drive. | Caitlin O. | 6/22/2026 |  | Caitlin has uploaded her code for the rabbitmq queue setup and created a document illustrating the queues and routing key setups intended for each vm. |


- Done criteria should describe observable evidence, such as "endpoint returns expected JSON", "pull request approved and merged", or "server log screenshot added to docs".  
    
- Risks And Blockers  
  List risks or blockers that could affect the next milestone. Leave this section short if there are no active blockers.


| Risk Or Blocker | Impact | Current Plan | Owner |
| :---- | :---- | :---- | :---- |
| VMs are not fully setup | We wouldn't be able to efficiently proceed with Milestone 1 rabbitmq message logging. | Please have vms fully configured and ready. | Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.  |
|  |  |  |  |

- Include technical blockers, team communication issues, missing credentials, server problems, unstable APIs, merge conflicts, unclear requirements, or testing gaps.  
    
- Evidence To Save  
- List evidence that should be captured before the next submission. Use this as a reminder list, not a full archive.  
    
- GitHub repository, branch, issue, pull request, or commit links: [Main Github](https://github.com/MattToegel/it490-m2026-autopilot-engineers)  
- Server screenshots or command output: [Github Issues](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues)  
- Logs or monitoring output: N/A  
- Architecture diagram updates: N/A  
- Deployment URLs: N/A  
- API test output: N/A  
- Database evidence: N/A  
- Team communication evidence: **Discord**   
- Next Meeting  
  - Next meeting date: **Monday, 6/21/2026 \- 1 p.m. to 5 p.m.**  
- Main focus:  
  - **Complete logging scripts for M1 and test message logging.**  
  - **Complete M1 questions and upload answers to github and submit canvas assignment**  
  - ***Maybe go over Milestone 2***   
- Preparation needed before meeting:  
  - **Complete your assigned questions in the [Team Responsibilities](https://docs.google.com/document/d/1w8rYQ3RzV0sdpgm1yOgthC30k5WPPm-nSWVxj1zgqY0/edit?usp=sharing) document.**  
  - **Create all issues intended for your work.**  
  - **Make sure all vms are up and running with ssh**  
- AI Or Tool Assistance  
- Disclose any meaningful AI or automation help used for planning, troubleshooting, code, documentation, or meeting notes. Include what it helped with, what the team changed, and how the result was checked. If none was used, write "No meaningful AI assistance used."  
- Link of Meeting Recording: [https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk\_jC7V3HezHz?usp=drive\_link](https://drive.google.com/drive/folders/1RxQKr05zTps6ocw7L5qOk_jC7V3HezHz?usp=drive_link) 

- Additional notes (If needed):