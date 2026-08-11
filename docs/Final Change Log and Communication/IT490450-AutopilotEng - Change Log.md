**IT490 Project Change Log**  
---

**IT490 Project Change Log Template**

Use this template near the end of the semester to summarize the final project state. The change log should help the instructor trace what changed, who contributed, how the system evolved, and what evidence supports the final demo.

This is a final synthesis, not a dump of every weekly update. Link the clearest evidence for important project decisions, implementation work, testing, deployment, and limitations.

**Timing For This Document**

Complete this change log after all major project features are complete and team communication about implementation and troubleshooting has finished. It may be easier to keep it up to date as you go.

**Communication Collection (Required)**

Collect all class-related project communication and include it in a shared folder.

Unrelated private messages, credentials, secrets, and non-course content may be redacted. Do not include passwords, tokens, private keys, or personal content that is not related to the course project.

Include communication from channels such as:

* WhatsApp  
* Discord  
* Email  
* Text messages  
* Meeting minutes or voice-chat notes  
* GitHub issues, PR discussions, and reviews  
* Any other class-related coordination channel

Collection requirements:

1. Capture all meaningful project communication, including planning, decisions, blockers, troubleshooting, and resolution.  
2. Preserve clear team-member identity for each message. If usernames are unclear, annotate to real first names.  
3. Keep records chronological when possible. Organize by module/week if helpful for searching.  
4. Exports are preferred; chronological screenshots are acceptable when export is not available.  
5. Store all evidence in one shared Google Drive folder used by the entire group.  
6. Ensure folder access is granted to the instructor or required NJIT group before submission.  
7. One team member submits the shared folder link on behalf of the group.

If the shared folder is inaccessible, grading may be impacted.

**Communication Collection Index**

Use this index to show what was collected and where it is located:

NOTE: If you're correctly using my Discord server, I should be able to provide text-based channel/thread exports for you upon request.

| Source | Date Range | Export Method | Collected By | Location In Shared Folder | Notes |
| :---- | :---- | :---- | :---- | :---- | :---- |
| Discord | 5/26/2026 \- 8/10/2026 | Export / Members screenshotted each discussion day | **Rosmy A. Caitlin O. Tristan D. Xaidyn L. Noaman S.** | [IT490 \-](https://drive.google.com/drive/u/0/folders/1AAHA-Z7UtWJbc4PXFZe4pIkSsSKLYkBt) IT490450-AutopilotEng \- Main Document |  |
| WhatsApp | x | Export / screenshots |  |  |  |
| Email | x | Export / screenshots |  |  |  |
| GitHub | x | Native links / exports |  |  |  |
| Voice chat notes | x | Minutes / transcript |  |  |  |
| Other | x |  |  |  |  |

**Shared Folder Submission**

* Shared folder URL: [https://drive.google.com/drive/u/0/folders/1AAHA-Z7UtWJbc4PXFZe4pIkSsSKLYkBt](https://drive.google.com/drive/u/0/folders/1AAHA-Z7UtWJbc4PXFZe4pIkSsSKLYkBt)   
* Access granted to:   
  * **Rosmy A. rma9 \- Editors**  
  * **Caitlin O.  cao39- Editors**  
  * **Tristan D. tad46 \- Editors**  
  * **Xaidyn L. xml \- Editors**  
  * **Noaman S. ns87 \- Editors**  
  * **NJIT students and staff \- Viewers**  
* Submitted by: **Caitlin O.  cao39**  
* Submission date/time: 8/10/2026 \- 10:30pm

**Communication Quality Reflection**

Briefly summarize communication quality for the term. Address:

* Quantity and consistency of communication  
* Quality and professionalism (including code of conduct)  
* How communication supported collaboration, issue resolution, and delivery  
* Any notable improvements made over the semester  
* The majority of the team was able to complete and collaborate on tasks, projects, and assignments together via Discord. However, we did have one team member that couldn’t set up their own VM since the start of class and did not submit their tasks or meeting notes on time or follow the guidelines of the template with little to no communication. He would cause delays and take time from meetings to focus on issues he either has had assistance with before or resources offered and not reviewed them on his own. 

**Submission Links**

* Team repository: [https://github.com/MattToegel/it490-m2026-autopilot-engineers](https://github.com/MattToegel/it490-m2026-autopilot-engineers)   
* Main branch: [https://github.com/MattToegel/it490-m2026-autopilot-engineers/tree/main](https://github.com/MattToegel/it490-m2026-autopilot-engineers/tree/main)   
* Final demo branch or tag: [https://github.com/MattToegel/it490-m2026-autopilot-engineers/tree/final-deliverables-tad46](https://github.com/MattToegel/it490-m2026-autopilot-engineers/tree/final-deliverables-tad46)   
* Final deployment URL:  N/A \- hosted on the tailscale network using a PHP server  
* Final presentation or recording URL, if required: [https://drive.google.com/file/d/1BrvvCSSKFLP6uFWG\_e7Tm0RzEIcMYqWG/view?usp=drive\_link](https://drive.google.com/file/d/1BrvvCSSKFLP6uFWG_e7Tm0RzEIcMYqWG/view?usp=drive_link)   
* Project board: [https://github.com/users/MattToegel/projects/48](https://github.com/users/MattToegel/projects/48)   
* Architecture diagram: User facing browser \-\> App \-\> RabbitMQ \-\> API (-\> External API which is AeroDataBox) or DB   
* Main README: [https://github.com/MattToegel/it490-m2026-autopilot-engineers/blob/main/README.md](https://github.com/MattToegel/it490-m2026-autopilot-engineers/blob/main/README.md)   
* Server or deployment documentation:

**Final Project Summary**

Briefly summarize the final system.

Include:

* What the system does  
  * OnTheRadar is a flight-tracking and airport program that allows those who are traveling to get real-time flight data and notifications for saved flights. In addition, it can be a personal guide to display the current airport conditions from a collective user input. A dedicated team of admins maintain safety and consistency by moderating reports submitted by users, handling profiles, and observing account activities.  
* Who uses it  
  * Travellers who are going or preparing to go on a trip via an airplane at EWR  
* What external API or service data is used  
  * [AeroDataBox](https://aerodatabox.com/)  
* What team-owned data is stored  
  * Saved and tracked flights for each user, flight alerts and notifications on flight updates for each user, user submitted airport condition reports, admin activity logs, user warnings when a report is flagged by an admin, centralized logs, cached flight information from the API, account login information (such as emails, usernames, user ids, and passwords)  
* What the final demo should prove  
  * This platform should prove to be a space for gaining reliable insight into flight status and airport conditions with a community monitored for accuracy and safety. OnTheRadar is giving users the ability to log into their account to look up and stay updated on real-time changes to their saved flights. Notifications continue to inform travelers during their airport experience and admins are keeping track of reports and violations to ensure content is relevant and maintain a safe environment.

**Final Architecture Snapshot**

Summarize the final system architecture at a high level. Include the required course services and any additional services the team used.

| Service | Final Responsibility | Host, URL, Or Location | Best Evidence |
| :---- | :---- | :---- | :---- |
| APP | User-facing application and business logic | appdev, appqa, and appprod Final Production application is hosted on appprod through the team’s Tailscale network app | [LINK](https://drive.google.com/file/d/1T44RcvHAlPjSEPaeZbsNseL_GpFni7s7/view?usp=sharing) TO FINAL DEMO FOR APPPROD |
| DB | Team-owned stored data | dbdev, dbqa, dbprodAThe final prod environment is hosted on dbprodA through tailscale. | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)  |
| RabbitMQ | Cross-server communication | mqdev, mqqa, mqprod The final prod environment is hosted on mqprod connected through tailscale.  | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)  |
| API | External API requests and response handling | There were three API VMs across production (dev, qa, and prod)Basically, I will be going into the production final architectureThe final API prod architecture is in charge of one of the most pivotal aspects of our program. My API worker takes in flight search requests from the api.request queue. From there, my api sends that request off to my processflightsearch function. Based on the routing key, my function then sends those requests off to search-specific functions. From there, the request is sent off to my flight fetcher to find the data and fetch it from the API. This raw information is then transformed and sent back to the App Server to display to users. I have a cache function and lookup cache function that work hand in hand to reduce API usage and serve users with saved flight data that may be recent enough to display to them (120s).  | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)  |
| Other, if used |  |  |  |

Do not include passwords, tokens, private keys, or secret connection strings.

**Change Summary**

Summarize the most important changes since the proposal and MVP milestone.

1. Google Places API was not implemented for US-03 (Noaman)  
2. US-03 not implemented (Noaman)  
3. Interactive map stretch feature not implemented (Noaman)

Focus on meaningful system changes, not every small commit. Good entries explain what changed, why it mattered, and where the supporting evidence lives.

**Team Contributions**

Each team member should have visible contribution evidence. Use the strongest links for each person rather than listing every commit.

| Team Member | Main Contributions | Related Issues | Related Pull Requests Or Commits | Demo Area |
| :---- | :---- | :---- | :---- | :---- |
| Tristan Duncan (tad46)  | Created the Tailnet so that all vms could connect to the RabbitMQ and effectively communicate. Developed MySQL Database Schema  and Maintained the Database logs upkeep  MS3 promotion tool (DB lane) \- schema migrations, code promotion, rollback Flight Saving System (US-05 AC1) \- save/unsave to watchlist, Assisted with User Dashboard Design Flight Status Notifications (US-05 AC5–AC8) \- flight history, alert content, timestamps, dismiss behavior  | [\#25](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/25) [\#57](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/57) [\#106](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/106) [\#107](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/107)  [\#108](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/108) [\#140](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/140) [\#141](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/141) [\#143](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/143) [\#159](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/159) [\#160](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/160)  [\#161](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/161) [\#162](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/162)  | [\#121](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/121) [\#153](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/153) [\#179](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/179)  | MS3 promotion demo (dev → QA → production)  Save a flight  Flight History, live alerts (gate /delay /cancellation), dismiss \+ re-trigger [Final Prod Demo Video](https://drive.google.com/file/d/1HgyjoeBXK0Xc5PLqvOx0mYi5QXYoW9LU/view?usp=sharing)  |
| Rosmy Antony (rma9) | **Secure Account Modifications** \- US-01 (AC4-AC6) : logout/session termination, password updates, current password validation, profile and account confirmation, and error handling  **Email Verification Stretch Feature** \- app side registration, verification workflow, verification page, validation and error feedback, and login integration **MS3 Promotion Tool (APP LANE) \-** Appdev \-\> QA \-\> Production promotion, validation, backup, and rollback **APP VM/ UI Integration**: user facing authentication, contributed to dashboard, built settings/profile page, and final Production integration **UI/UX Design and App Integration:** Created the initial skeleton sketches and wireframes that helped establish the application’s visual direction and page layouts. Contributed to the implementation of the final user facing pages such as authentication, dashboard, settings/profile page, navigation, and overall App UI.  | [101](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/101) [104](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/104) [109](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/109) [113](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/113) [120](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/120) [130](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/130) [131](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/131) [135](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/135) [139](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/139) [142](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/142) [156](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/156) [163](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/163) [164](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/164) [165](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/165) [178](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/178) | [103](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/103) [121](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/121) [153](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/153) [179](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/179) | [APP M3](https://drive.google.com/file/d/1T2NWnRs8t-k4GOSsPdfWND1j8yj5lH2j/view?usp=sharing) [APP FINAL PROD](https://drive.google.com/file/d/1T44RcvHAlPjSEPaeZbsNseL_GpFni7s7/view?usp=sharing) |
| Xaidyn Lirazno (xml) | **Created the Flight Search Feature (Search by number, search by airport, search by route) \- very pivotal to the actual function of the project itself. Created the Search page itself that displays this information to the user Created the cache lookup function that allows us to use fewer of our API requests. So if a flight is still relatively new (pulled from database)(under 120s), then that information is served to the user instead of calling the API again Figma Creation of UI/UX design for the pages of the project Created the alert display that shows users when flight data is unavailable, the worker is down, a timeout occurs, etc Created the promotion tool that allowed for file transfer from Dev→qa and qa →prod** | [\#114](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/114)  [\#119](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/119)  [\#146](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/146)  [\#147](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/147)  [\#148](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/148)  [\#149](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/149)  [\#150](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/150)  [\#168](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/168)  [\#100](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/100)  [\#118](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/118)  | [FINAL DELIVERABLE](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/179)  [MVP RULL REQUEST](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/121)  [M3 PULL REQUEST](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/153)  | [MVP Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1BJ25S-sDOpf4jtQor3t8OtoNODo4hXQD)  [Milestone 3 Video \- Google Drive](https://drive.google.com/drive/u/1/folders/10YhZpIGsuJCL2lyEEL2ob7QynHMLNWvh)  [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)  |
| Caitlin Ortiz (cao39) | **Assigned project manager for the team to plan out assignments, remind people of deadlines, and assist as necessary Facilitated every meeting and stored all meeting recordings Sent meeting agendas and created meeting minute issues for team to work on  Created a healthcheck file to check on the rabbit mq service for all 3 vm lanes Built the setup script that is ssh based to set up the mq service for the qa and prod lane for all to connect and route messages to  Created, updated, and maintained the RabbitMQ for all 3 Milestones and the final project Created a new user warning table in the db schema to update user profiles if they get flagged by an admin Built the admin dashboard  Added usernames across reports, violations, and activity logs for an easy to read display Verified end to end logging in production for the rabbit mq Built every file for the US-04 admin feature** | [\#182](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/182) [\#43](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/43) [\#48](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/48) [\#50](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/50) [\#95](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/95) [\#99](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/99) [\#111](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/111) [\#112](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/112) [\#137](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/137) [\#144](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/144) [\#151](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/151) [\#152](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/152) [\#170](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/170) [\#171](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/171) [\#172](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/172) [\#173](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/173) [\#177](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/177) | [FINAL DELIVERABLE](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/179)  [MVP RULL REQUEST](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/121)  [M3 PULL REQUEST](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/153)  | [MVP Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1BJ25S-sDOpf4jtQor3t8OtoNODo4hXQD)  [Milestone 3 Video \- Google Drive](https://drive.google.com/drive/u/1/folders/10YhZpIGsuJCL2lyEEL2ob7QynHMLNWvh)  [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)  |

Contribution notes should connect work to behavior, not only file names.

**Milestone And Demo History**

List the major checkpoints and what was working at each one. If a checkpoint did not apply or was replaced by another course requirement, note that briefly.

| Checkpoint | Required Focus | Working Behavior Demoed | Evidence Link | Follow-Up Needed |
| :---- | :---- | :---- | :---- | :---- |
| Proposal | Approved project direction | OnTheRadar was concept approved: EWR flight tracker \+ community reporting, (US-01–US-05), AeroDataBox as external API, 4-VM architecture | [Proposal](https://docs.google.com/document/d/1jx4oN-L4ZmAsTHRSFy4U3N0kojVeXm6egBuTDpgLU00/edit?usp=sharing) | N/A |
| Milestone 1 | VM infrastructure and logging logic | 4 VMs up and running on Tailscale network; DB VM logging consumer (logs\_consumer) listening on db.logs, writing to MySQL \+ local log file, deadletter routing for malformed messages; broker topology initial setup | [\#25](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/25) [\#57](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/57) | N/A |
| Milestone 2 | Auth and cross-VM Logic | Db side auth\_consumer.php register/login (bcrypt hash, password\_verify, generic invalid-credentials message);  auth\_client.php correlation\_id/reply\_to pattern;  App-side register.php, login.php, logout.php, dashboard.php, working session guard; MQ-only contract verified across App↔Broker↔DB | [\#75](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/75)  [\#79](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/79) [\#80](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/80) | N/A |
| Midterm MVP | Minimal viable product | US-01 full (register/login/profile update)  US-02 search using AeroDataBox US-03 reports CRUD  US-04 admin user/role management  US-05 save/unsave/list flights | [**MVP Worksheet**](https://docs.google.com/document/d/1vHp27VC99WBMHw24rhIDhkcy7VimSMG8S7UfQjy0Lb0/edit?usp=sharing) | N/A |
| Milestone 3 | Promotion Toolset  | Db migrate.php (versioned schema \+ backup)  promote.php (SFTP release-snapshot)  rollback.php | [Repo Promotion Tool](https://github.com/MattToegel/it490-m2026-autopilot-engineers/tree/main/promotion-tool) | N/A |
| Final Demo | Final project behavior | Final Item 1: Secure Account Modifications Final Item 2: Real-Time Flight Search And Status Final Item 3: Flagging Reports And History Final Item 4: Administration User Monitoring Final Item 5: Flight Status Notifications | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)  | N/A |

**GitHub Evidence**

List important GitHub artifacts. Choose evidence that helps the instructor verify planning, implementation, review, bug fixing, documentation, and team coordination.

| Type | Link | Why It Matters |
| :---- | :---- | :---- |
| Issue | [58](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/58) [77](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/77) [142](https://github.com/MattToegel/it490-m2026-autopilot-engineers/issues/142) | Issue 58: This issue is important because it verifies that the team’s separate VMs communicate only through RabbitMQ for centralized logging for M1 Issue 77: Shows that the team verified the complete Milestone 2 authentication workflow across the distributed architecture and confirmed that App-to-backend communication occurred through RabbitMQ rather than direct database access. Issue 142: Demonstrates the complete App VM promotion workflow by moving the same release from Development to QA and then from QA to Production using the custom promotion tool without Git or direct target-VM edits. It also verifies the application service in QA and Production and includes promotion logs, screenshots, and documentation as deployment evidence.  |
| Pull request | [60](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/60) [91](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/91) [153](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/153) [121](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/121) [179](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/179) | PR 60: Closed the team’s Milestone 1 work across APP, DB, RabbitMQ, and API into main, linking the completed setup, centralized logging, and cross-VM communication PR 91: Closed the teams Milestone 2 authentication and authorization work across APP, DB, RabbitMQ, and API. Links the completed setup, registration, login session, validation, and MQ-only communication tasks, included teammate review and showed the work moving through the project board before merge into main. PR 153: It proves that the Milestone 3 work was completed, reviewed, approved, tracked through the project board, and successfully merged into the main branch. PR 121: Represents the team’s completed MVP integration, bringing together the work from the different system components and VM roles into the final working OnTheRadar MVP. Its merge and team approvals provide evidence that the combined MVP functionality was reviewed, accepted, and incorporated into the project. PR 179 : Serves as the final integration point for the entire project, showing that work across the App, RabbitMQ, Database, and API components was combined, reviewed, and accepted into the final main branch.  |
| Commit | [Milestone 1](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/60/commits) [Milestone 2](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/91/commits) [Milestone 3](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/153/changes/d422aa19f79cae879cfbc1b347c5dce530ba27c3) [MVP](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/121/changes/08c07b3eeb90dd47c465058487aefd5544745085) | **Milestone 1:** Shows the implementation of the team’s Milestone 1 infrastructure and  centralized logging work, including the APP, DB, RabbitMQ, and API service setup that established the project’s multi-VM architecture. Milestone 2: Shows the actual code and documentation changes that implemented Milestone 2 authentication, session handling, validation, and cross-VM communication through RabbitMQ. Milestone 3: Shows the actual implementation of the App promotion tool used for Milestone 3, providing direct code evidence for moving application changes between environments through the custom deployment process. MVP: Represents the completed team MVP integration by bringing together the APP, DB, RabbitMQ, and API work into the main branch for the final MVP submission.  |
| Review comment | [Milestone 1](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/60#pullrequestreview-4550335122) [Milestone 2](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/91#pullrequestreview-4603204500) [Milestone 3](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/153#pullrequestreview-4831607410) [MVP](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/121#pullrequestreview-4741129407) [FINAL](https://github.com/MattToegel/it490-m2026-autopilot-engineers/pull/179#pullrequestreview-4902226765) | **Milestone 1**: Shows a member from the team (tad46) has verified the required screenshots and scripts to be closed and merged into main Milestone 2: Shows formal peer review of the Milestone 2 work, with (xml)  confirming that all team members’ proof, scripts, screenshots, and documentation were included before approval and merge. Milestone 3: Shows that a teammate reviewed the Milestone 3 submission and confirmed that all main VM lanes had their promotion tooling in place, the environments were working correctly, and the required promotion and recovery evidence had been included before the work was merged. MVP: Provides peer-review evidence that the team’s integrated MVP was checked and approved before being merged into the main branch, supporting that the final submission was complete and functional. FINAL : Confirms the merge of all final deliverables into main branch  |
| Project board item | [Project Board \- M1](https://github.com/users/MattToegel/projects/48/views/2?filterQuery=milestone%3A%22%5BMilestone+1%5D+Centralized+Logging%22) [Milestone 2](https://github.com/users/MattToegel/projects/48/views/2?filterQuery=milestone%3A%22%5BMilestone+2%5D+Authentication+and+Authorization%22) [Milestone 3](https://github.com/users/MattToegel/projects/48/views/2?filterQuery=milestone%3A%22Milestone+3%22) [MVP](https://github.com/users/MattToegel/projects/48/views/2?filterQuery=milestone%3A%22%5BMVP%5D+Deliverable%22) [FINAL](https://github.com/users/MattToegel/projects/48/views/2?filterQuery=milestone%3A%22Final+Project+Deliverables%22) | **Milestone 1:** Shows how the team used the GitHub Project Board during Milestone 1 to organize, assign, track, and complete the initial APP, DB, RabbitMQ, API, and centralized logging tasks that established the foundation of the project. Milestone 2: Demonstrates team-wide planning and progress tracking for Milestone 2 authentication, session handling, MQ communication, and VM-specific tasks. Milestone 3: **S**hows that all Milestone 3 tasks for environment lanes, promotion tooling, validation, backup, rollback, and recovery were tracked and completed through GitHub, with the milestone reaching 100% completion and all 18 issues closed. MVP: Shows that the team tracked and completed the MVP work through GitHub, with all 20 associated items completed and the MVP milestone reaching 100% completion. FINAL: Shows that the team formally tracked the final deliverable work through GitHub and completed the required tasks across all project domains before the final submission.  |

Do not list every commit. Prefer the most useful issues, pull requests, commits, review comments, project board items, or documentation links.

**Architecture And Service Changes**

Document major architecture or service changes. Focus on changes that affected how the system was built, deployed, connected, secured, or demonstrated.

| Change | Reason | Impact | Evidence |
| :---- | :---- | :---- | :---- |
| Changed database schema \- added flight\_alerts.saved\_flight\_id usage, users.search\_count, users.email\_verified/verification columns | Needed to support notification history linkage, a real per-user search counter, and the email-verification stretch feature | Required new ordered migration files tracked through migrate.php, applied consistently across dev/QA/production | Migration files 002\_add\_search\_count\_to\_users.sql, 003\_add\_email\_verification\_to\_users.sql; confirmed applied via otr\_migrations\_applied table |
| Enforced UTC as the standard time representation across DB, API, and App VMs | MySQL and PHP defaulted to system/local time inconsistently across VMs, causing timestamps to display incorrectly throughout the app | Standardized all stored timestamps as UTC, with explicit UTC→local conversion added at every user facing page | Verified with live "just now" tests on QA and production after the fix |

Examples:

* Added or removed a VM or service  
* Changed frontend/backend communication  
* Changed message queue behavior  
* Changed database schema  
* Added logging, monitoring, failover, or deployment automation  
* Changed API usage or caching strategy

**Data And API Changes**

Document changes to external API usage and stored data.

| Change | Reason | Affected Feature | Evidence |
| :---- | :---- | :---- | :---- |
| No longer using Google Places API | Individual in charge of implementing this API never implemented it  | We no longer have a map that displays the users on the landing page of the EWR airport. Thankfully, there were no routing keys or anything that was created for this yet since the individual did not take initiative  | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)  |
| Added email verification data to the Users system and integrated verification email delivery through the API worker. Integrated the [Resend](https://resend.com/) API for verification email delivery   | Needed to verify newly registered users through an emailed verification code before completing account access | US-01 Registration / Email Verification Stretch Feature  | [FINAL](https://drive.google.com/file/d/1T44RcvHAlPjSEPaeZbsNseL_GpFni7s7/view?usp=sharing) VIDEO |

Include:

* External API fields added or removed  
* Database tables or fields added or changed  
* User-data associations added or changed  
* CRUD behavior added, changed, or removed  
* Validation, authorization, or ownership changes

**Deployment And Server Evidence**

Record deployment and server evidence needed for grading. Include the required services and any environments or supporting services used for the final demo.

| Server, Environment, Or Service | URL Or Host | Purpose | Current Status | Evidence |
| :---- | :---- | :---- | :---- | :---- |
| APP | appdev, appqa, appprod | Hosts the user facing OTR application and App side logic across the Dev, QA, and Production | Running and verified in Production | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)[Milestone 3 Video \- Google Drive](https://drive.google.com/drive/u/1/folders/10YhZpIGsuJCL2lyEEL2ob7QynHMLNWvh)  |
| DB | dbdev, dbqa, dbprodA | MySQL database \+ consumer scripts (auth, flights, alerts, admin, reports, logs) | Running, verified end-to-end on QA and production | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)[Milestone 3 Video \- Google Drive](https://drive.google.com/drive/u/1/folders/10YhZpIGsuJCL2lyEEL2ob7QynHMLNWvh)  |
| RabbitMQ | mqdev, mqprod, mqprod | Works as a central hub for all communication across all VM domains (routing messages with routing keys, placing them in queues, and making sure they reach their destinations) | Running and verified in the production lane | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)[Milestone 3 Video \- Google Drive](https://drive.google.com/drive/u/1/folders/10YhZpIGsuJCL2lyEEL2ob7QynHMLNWvh)  |
| API | apidev, qa-api, production-api | Worker that is in charge of receiving api requests, fetching API information from external APIs, converting the raw data, and sending it back to the APP server to display to users | Currently running on production VM after promotion process of  Dev → qa Qa → prod | [Final Video \- Google Drive](https://drive.google.com/drive/u/1/folders/1t6yR93ljz5GgXXq5ensZKgviTq8KjEuY)[Milestone 3 Video \- Google Drive](https://drive.google.com/drive/u/1/folders/10YhZpIGsuJCL2lyEEL2ob7QynHMLNWvh)  |
| Deployment environment, if separate |  |  |  |  |
| Logging or monitoring, if used |  |  |  |  |

Do not paste real passwords, private keys, tokens, or secret values.

**Bugs Fixed**

List meaningful bugs fixed during the project.

| Bug | Root Cause | Fix | Evidence |
| :---- | :---- | :---- | :---- |
| Alert timestamps showing several hours off | MySQL's time\_zone was set to SYSTEM (local/EDT) instead of UTC across all DB VMs, while the app assumed UTC-stored timestamps | Set default-time-zone \= '+00:00' in mysqld.cnf on each DB VM, restarted MySQL, corrected existing rows with a one-time UPDATE | Verified with a live "just now" test on production after the fix, confirming new alerts timestamp correctly  |
| Cross-lane log contamination (dev activity appearing in QA/prod logs) | Suspected misconfigured RABBITMQ\_HOST in a .env file pointing a dev-lane VM at the wrong environment's broker | Diagnosis identified: fix is correcting the .env value and restarting the affected service (pending confirmation) | Log entries observed from appdev appearing in QA and production log streams |
|  |  |  |  |

Good bug notes include the symptom, what caused it, and how the team verified the fix.

**Known Limitations**

List known limitations honestly.

| Limitation | Impact | Workaround Or Future Fix |
| :---- | :---- | :---- |
| One of our group members was not able to perform up to par, which limited the full potential of our platform. We are currently missing some very important parts of our project: —---------------------- Another limitation would be the very underperforming API that is not very efficient | We are missing the communities tab for our platform (which was a major part of our service) We are missing the feature that allows users to see their flagged comments We are also missing the feature that hides these flagged comments until viewed by an Admin We are also missing the maps on our landing page that belonged to said team member, which made our page look very incomplete —--------------------------- It caused our flight data to be a bit laggy, which made our platform look underperforming as well. Some data types were missing, and some information from the API itself was not responding (after checking the raw data)  | Sadly, we were not able to move around this issue. We communicated with the professor about everything, and this information is documented in our contributions portion of our final deliverables —--------------------------- We were able to mitigate this issue by trying to play around with raw responses and find ways to fill in null responses; however, some missing data could be lost in the process  |

Limitations are not automatically failures. Undocumented limitations are usually worse than clearly explained ones.

**Final Demo Checklist**

Before the final demo, confirm:

* The deployment URL works  
* Required APP, DB, RabbitMQ, and API services are running or documented if intentionally unavailable  
* The external API behavior can be shown  
* User login or user identity behavior works when required  
* User-owned data can be demonstrated  
* CRUD behavior can be demonstrated  
* Logs, diagrams, screenshots, and documentation are available  
* Each team member can explain their contribution  
* Known limitations are documented


**AI Disclosure**

Disclose any meaningful AI help used for the project, change log, code, troubleshooting, documentation, or presentation. Include what AI helped with, what the team changed, and how the result was verified. If no meaningful AI assistance was used, write "No meaningful AI assistance used." 

**No meaningful AI assistance used** 