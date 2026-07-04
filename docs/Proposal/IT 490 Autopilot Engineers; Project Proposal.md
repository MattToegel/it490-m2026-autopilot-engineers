 **IT 490 Autopilot Engineers**  
**Project Proposal 2026**  
---

## **Project Summary \- XML**

**\--------------------------------------------------------------------------------------------------------------------**  
**Team Name:** Autopilot Engineers  
**Repository URL:** [MattToegel/it490-m2026-autopilot-engineers](https://github.com/MattToegel/it490-m2026-autopilot-engineers)   
**Proposed Project Name:** OnTheRadar  
**One-Sentence Project Summary:** Our project is a domestic flight tracker and self-reporting interface that allows users to check their flights as well as report any issues or activity within the EWR airport.   
**Target users:** 

- Individuals who plan on taking domestic flights at EWR (Newark International Airport)  
- Family members who would like access to the flight statuses of their loved ones   
- Individuals who need an estimated time of arrival to get to their flight on time   
- People who need to know the waiting times of places and other activities within the airport in order to calculate their time correctly 

**Problem or Need For Project Addresses:**  
	The creation of our project comes from the constant recurring issue of individuals feeling as though they do not have a reliable platform to, one, track their flights, and two, guide them throughout airports. Our platform aims to diminish this issue and provide users with a reliable platform for all their traveling endeavors. Our team is devoted to providing users with a real-time flight tracker that provides them with information regarding flight delays, terminal changes, rerouting statuses, etc. We are also providing a self-guided section to our platform that allows for status reports on stores within the airport, events occurring within the airport, TSA waiting times, etc. All in all, our team's objective is to be reliable, efficient, and attentive to our customers' wants and needs.   
**\--------------------------------------------------------------------------------------------------------------------**

## **Project Objective: \- XML**

**\--------------------------------------------------------------------------------------------------------------------**  
Our platform is designed to provide users with an all-in-one airport tracker and self guide to make traveling easier. Individuals, particularly in modern day traveling, often struggle with finding a real time flight tracker and self guide that provides them with essential knowledge about their trip. Sudden gate changes, terminal changes, flight cancellations, flight delays, and other inconveniences can negatively impact a passenger's trip. On top of these issues, many individuals are impacted by airport services; for example, bathrooms being inaccessible, lack of emergency door access, accidents occurring at the airport, TSA times, etc. Our platform aims to challenge these issues and provide users with a service that can provide this information to them at easy convenience. That way our customers have safe and stress free traveling.   
Some of the primary outcomes of our platform is to provide users with flight tracking information. This information includes, but is not limited to, gate changes, arrival and departure times, delay times, flight cancellations, terminal changes, and flight pivots. Users will be able to go onto our platform and, in just a few clicks, have all the essential information at their fingertips. Our platform will also provide a self guide for passengers to have more information about airport conditions. This self guide will provide users with information about airport accidents, bathroom inaccessibility, estimated TSA times, etc. It will also provide features for other users to provide each other with input about events going on in the airport.   
This platform heavily integrates saved data, user interaction, and system integration in order to work efficiently for our users. Our platform saves data like user logins, recent user searches, user comments on our self guide, etc. We do this in order to personalize and make sure our platform is personalized for each of our users. Our platform heavily uses user interaction in order to provide users with engagement amongst our platform and within themselves. For example, users will have plenty of buttons, filters, text boxes, etc, in order to ensure their portion of interactivity on our platform. Lastly, there are a few system integrations we plan on adding to our platform. For example, we plan on using API integration in order to provide users with real time accurate data about their flights. We also plan on integrating airport operation trackers in order to efficiently and effectively provide users with information about events occurring in the airport itself.   
\--------------------------------------------------------------------------------------------------------------------

## **Core User Stories \- CAO39**

Write user stories in this format:  
\- As a \[user role\], I want \[behavior\] so that \[outcome/value\].  
For each story, add acceptance criteria.  
Copy this block for each story (should have full project coverage):  
Recommended story count for final submission: 5-8 stories.  
Before submitting, make sure the full set of stories covers the required gradable behaviors below. You do not need one story per bullet, but every bullet should be clearly visible in at least one story, acceptance criterion, workflow, or data note:  
\- **Account access:** registration, login, logout, profile update, and password security.  
**\- General user behavior:** what a normal user can create, view, update, save, or delete.  
**\- Admin maintenance behavior:** how an admin manages users, roles, or application data.  
**\- External API behavior:** what outside data is requested and how it affects the user workflow.  
**\- Stored data behavior:** what team-owned data is saved and how it is connected to users.  
**\- Ownership and authorization:** how the system prevents one user from changing another user's data.  
**\- Error or unavailable-service behavior:** what users see when data, validation, or an API request fails.  
**\- Course service evidence:** where APP, DB, RabbitMQ, and API are expected to appear at a high level by MVP or final demo.

**STUDENT RESPONSE START**

**Story ID: US-01**  
**User story:** As a frequent traveler, I want to be able to log in, log out, and create an account  securely so that I can access my dashboard and see my selected flights that I am tracking.

**Acceptance criteria:**  
**\- \[ \] AC1:** Users are able to register successfully with their chosen unique email and password. The user receives a message verifying that their registration was successful.  
**\- \[ \] AC2:** Users are able to log into their respective accounts with valid credentials to view their dashboard. The user will receive an error message if the credentials provided are incorrect.  
**\- \[ \] AC3:** User can review and update their profile and their password  
**\- \[ \] AC4:** Users are able to log out which terminates their current session to make sure that access back into the account is not permitted unless the user re-authenticates themselves by logging in again  
**Final Demo AC**  
**\- \[ \] AC5:** To change passwords, users must first be able to input their current password before updating their password to a new one  
**\- \[ \] AC6:** When users update their profiles and passwords, a confirmation message will appear confirming the changes  
**\- \[ \] AC7:** Information that the user has updated stays regardless if the user is logged in or logged out

**Data needed:**  
**\- User-created data:** Username, encrypted password, email, session token/ID  
**\- External API data:** None

**Service touchpoints (high-level):**  
**\- APP:** Receives profile update, registration, login, and logout requests and confirms the inputted information and its format. Clears the cache and local session tokens when the user logs out. Publishes requests to RabbitMQ and shows the response that the system sends back.   
**\- DB:** Stores profile information and user credentials securely while being able to authenticate login attempts. Revokes session tokens when a logout request is received. Processes user account information and update requests that are sent by RabbitMQ and returns the results.  
**\- RabbitMQ:** Redirects the messages between the database and App and sends back responses using IDs. Receives user login/logout/registration requests from the App to deliver to the database.   
**\- API:** Not used in this story.

**Story ID: US-02**  
**User story:** As a frequent traveler, I want to be able to search and track my flight to stay up to date in case of any delays, cancellations, or gate changes.

**Acceptance criteria:**  
**\- \[ \] AC1:** Users can enter the Newark (EWR) airport or flight number or route to view the current status of their trip.  
**\- \[ \] AC2:** System publishes gate changes and any delays/cancellations when new data on the status of a flight is received or when it reloads  
**\- \[ \] AC3:** The system will post an error message that information is not available at this time if the external API is not available.  
**Final Demo AC**  
**\- \[ \] AC4:** Users are able to refresh to see the most recent flight data without having to search for it again  
**\- \[ \] AC5:** The system allows users to view the  time of when flight data was last updated   
**\- \[ \] AC6:** If the API is facing an issue or an outage, the system notifies the user that any live updates are unavailable at this time and shows the cached data

**Data needed:**  
**\- User-created data:** Previous search history  
**\- External API data:** Flight delay timeframe, real-time flight updates, gate and terminal information, and cancellation notice.

**Service touchpoints (high-level):**  
**\- APP:** Validates user information and publishes a message to the RabbitMQ queue.  
**\- RabbitMQ:** Receives the messages from the APP server and creates a queue that first gets sent to the database server. If information requested is not present,  the queue is then sent to the API Worker, which pulls information from the external API and routes it to the DB server and back to the APP server.                
**\- DB:** Stores the user’s search in their recent search history in their profile   
**\- API:** Requests live flight information such as its status (e.g., on-time, delayed, cancelled) and gate information, and consumes messages from the RabbitMQ

**Story ID: US-03**  
**User story:** As a passenger, I want to post and view reports and comments regarding any situations going on at the airport so that I and others can plan their trip accordingly.  
**Acceptance criteria:**  
**\- \[ \] AC1:** User can review recent reports at Newark airport by other users  
**\- \[ \] AC2:** Users can post a new report with a relevant category such as TSA wait time, bathroom lines, accidents in terminals, etc.  
**\- \[ \] AC3:** Users can only modify their own comments  
**\- \[ \] AC4:** Users are able to remove their own Newark airport reports  
**Final Demo AC**  
**\- \[ \] AC5:** Logged in users can see their flight report history  
**\- \[ \] AC6:** Reports/comments that get flagged are hidden from others until reviewed by the admin  
**\- \[ \] AC7:** Reports/comments that go against community guidelines can be flagged via an admin  
**\- \[ \] AC8:** Users can view reports/comments that they created that got flagged and review the reason  
**Data needed:**  
**\- User-created data:** User ID, category, timestamp of the post, airport, user text comment  
**\- External API data: Google Places API \-** Location, gate/terminal tags, Newark’s Place ID , the metadata to where the report is located 

**Service touchpoints (high-level):**  
**\- APP:** Receives requests to view, modify, and create reports regarding EWR. Validates data provided by users, then publishes requests to RabbitMQ.   
**\- DB:** Stores comments, reports, time of post, and category and links them to the user’s ID. Obtains and updates reports according to requests received through RabbitMQ.   
**\- RabbitMQ:** Obtains reports and update requests from the APP server. Redirects messages between the DB server and APP server which then returns responses regarding report information.  
**\- API:** Uses the Google Places API to convert the location that the user is wanting to report on an airport condition/situation to a digital ID code that can be connected to the report. This will maintain the accuracy of the  location of where a situation or condition is occurring.

**Story ID: US-04**  
**User story:** As an administrator, I want to be able to maintain user accounts and moderate posts to foster a safe environment and remove any inappropriate or harmful content

**Acceptance criteria:**  
**\- \[ \] AC1:** Administrator can see the list of all users that have created accounts and their roles for each one  
**\- \[ \] AC2:** Administrator can remove any harmful or inappropriate comments or flag a warning to a user  
**\- \[ \] AC3:** Administrator can update all roles for users   
**Final Demo AC**  
**\- \[ \] AC4:** Administrators are able to view all user’s history of reports  
**\- \[ \] AC5:** Administrator are able to see all violations and warnings for each user account if they have any  
**\- \[ \] AC6:** Administrators are able to find users by using a user’s email or username  
**\- \[ \] AC7:** Every action conducted by an administrator is added to an activity log. Actions can include role changes, reviewing accounts, and getting rid of reports/comments by users)

**Data needed:**  
**\- User-created data:** user role assignment list, administrator activity logs, violation warnings  
**\- External API data:** None

**Service touchpoints (high-level):**  
**\- APP:** Receives admin requests to display users, roles, delete comments, and send out violation warnings. Sends the admin activity to RabbitMQ in preparation for a queue.  
**\- DB:** Updates and stores user roles, comments, admin activity, violations and removes comments based on the admin adhering to community guidelines and requests obtained through the Rabbit MQ queues.  
**\- RabbitMQ:** Gathers admin requests from the APP server and sends a queue to DB server. Processes the results and sends them back to the APP server.       
**\- API:** Not used in this story.

**Story ID: US-05**  
**User story:** As a passenger, I want to receive automatic alerts for any of my flights that I have saved so that I am notified of any issues of my upcoming trip.

**Acceptance criteria:**  
**\- \[ \] AC1:** Users allowed to track and save their preferred flights to their dashboard screen or watchlist  
**\- \[ \] AC2:** System automatically checks for saved flights, and if a flight is cancelled or delayed by more than 15 minutes, the user will be notified  
**\- \[ \] AC3:** Alerts are specific to each individual user based on the flights that they have saved  
**\- \[ \] AC4:** Users are able to remove their own saved flights from the Newark airport from their own watchlist. Once completed, the system receives the information and updates the dashboard to stop sending notifications for that specific flight.   
**Final Demo AC**  
**\- \[ \] AC5:** Users can see a history of all of their saved flights  
**\- \[ \] AC6:** Alerts to users about their saved flights can include delays, cancellations, terminal/gate changes, and/or updated arrival information  
**\- \[ \] AC7:** Alerts are timestamped when issued to users  
**\- \[ \] AC8:** Users are able to dismiss alerts that they have seen before without affecting the future notifications for any flights that they have saved

**Data needed:**  
**\- User-created data:** Each user’s saved flight(s) list using flight numbers   
**\- External API data:** Real-time flight status feed  

**Service touchpoints (high-level):**  
**\- APP:** Processes requests from users to delete, save, or see their tracked flights. Presents flight notifications and publishes the requests for tracking flights to RabbitMQ.    
**\- DB:** Stores all alerts generated for flight statuses, alert history, and user’s saved flights. Retrieves lists of users that are tracking certain flights via their flight numbers through RabbitMQ.  
**\- RabbitMQ:** Routes any flight updates, tracking requests and alert queues among the APP server, DB server, and API worker.   
**\- API:**  Consumes tracking jobs for flights via RabbitMQ. Requests the most recent status updates from the external API send them to store into the database via queue and then sends updates back to app server through queues.   

**How to answer touchpoints:**  
1\. Keep each touchpoint to 1-2 plain-language sentences.  
2\. Use action words: receives, validates, stores, publishes, consumes, requests, returns.  
3\. If something is not used for this story yet, write \`Not used in this story\`.

**Optional detail (add only if useful now):**  
\- APP detail:  
\- DB detail:  
\- RabbitMQ detail:  
\- API detail:  
\- Notes for later implementation:

STUDENT RESPONSE END  
\--------------------------------------------------------------------------------------------------------------------

## **Core User Workflows \- RMA9**

Use short workflow bullets that map to your user stories.  
STUDENT RESPONSE START

**\- US-01 workflow:** User opens the platform → user registers with a unique email and password or logs in with existing credentials → system validates the account information → user lands on the main dashboard → user can review or update their profile and password.

**\- US-02 workflow:** User enters EWR, a flight number, or a route → system validates the search request → system requests current flight information → user views flight status, gate, terminal, delay, or cancellation information → search is saved to the user’s recent search history.

**\- US-03 workflow:** User opens the EWR airport report section → user views recent reports from other passengers → user creates a categorized report → system saves the report with the user’s ID and timestamp → user can edit, delete, or resolve only their own report \-\> the system will prevent users to modify reports created by other users.

**\- US-04 workflow:** Administrator logs into the admin dashboard → administrator views user accounts, roles, submitted reports, and violation warnings → administrator removes harmful or inappropriate comments if needed → administrator updates user roles if needed → system stores the admin action in the activity logs.

**\- US-05 workflow:** User searches for a flight → user saves the flight to their dashboard or watchlist → system stores the saved flight under that user’s account → user then will view their saved flights → user can also remove their own saved flights → system checks the saved flight status using the external API → if the user chooses to remove a saved flight, the system will delete it that flight from the user’s watchlist and stops displaying the info on their dashboard.

STUDENT RESPONSE END  
\--------------------------------------------------------------------------------------------------------------------

## **External API Data \- CAO39**

This section is **required**. All projects must use **at least one external API**.  
Keep this section high-level. Identify a realistic data source and explain why it matters to the project. Include how the data will be used or stored and what should happen when the API is unavailable. Exact request formats and implementation details can be refined later.  
**STUDENT RESPONSE START**  
**API 1:**   
**\- Name:** [AeroDataBox](https://aerodatabox.com/)  
**\- Documentation link:** [AeroDataBox API Documentation](https://doc.aerodatabox.com/)    
**\- Fields needed:** 

* Flight numbers   
* Airline names  
* Status on flights and delays times if applicable  
* Scheduled and estimated times for departing and arriving flights and airport codes  
* Gate and terminal details

**\- How it is used in user stories:**  
	**\-US-02:** Real-time flight details for incoming and outgoing flights from the Newark Airport will be provided to users. This information will include a flight’s status (e.g., delay, cancelled, on-time) and gate changes that the user selected to track.   
	**\-US-05:** Allows for reviewing saved flights from users and automatically sends out alerts when the tracked flight’s arrival or departure time changes.  
**\- Fetch/cache/storage plan:** With each flight search request or flight status update notification, the app checks for that requested date and flight number from our own database. To reduce the amount of requests, we can implement a 15 minute rule. If the information we have is older than 15 minutes or no information has been posted for longer than 15 minutes, the data is then considered stale. The system would then fetch the most recent flight details (e.g., flight still on time) and send that latest update to our database with a timestamp for the user to see. However, if the latest information is less than 15 minutes, we will not request to fetch data from the AeroDataBox API but use the existing information in our database instead. For users that have saved flights to watch for updates, we can utilize the API’s Webhooks which can instantly send an alert to our server when a flight is no longer on time or available instead of constantly asking the API. Our database can store the user’s flight search history, alert log, and our user’s saved flight lists. In addition,  our database stores all relevant flight data (e.g., airline, flight number, gate/terminal, flight status, flight times) and connects it via a flight number and the date flying with the time of when the data was last updated.   
**\- Failure handling plan:** If for some reason the AeroDataBox API is not available, a message will display to the user stating that flight details are temporarily unavailable at this time. If any requests fail it will be documented to review and any updates that are unavailable won’t show until we are able to get the most recent and accurate information.

**API 2 :**  
**\- Name:** [Google Places API](https://developers.google.com/maps/documentation/places/web-service/overview)  
**\- Documentation link:** [Google Places API Documentation](https://developers.google.com/maps/documentation/places/web-service/overview)    
**\- Fields needed:**

* Airport name (Newark Airport)  
* Airport address  
* Newark’s Place ID  
* Location and/or gate/terminal tags (e.g., subDestinations)  
* Report-location metadata

**\- How used in user stories:**  
	**\-US-03:** Can be used to assist our users to report on conditions and issues going on at the Newark Airport. Since it’s Google Places, we can use it to link reports that users submitted to areas inside the Newark airport.   
**\- Fetch/cache/storage plan:** We can obtain location labels for EWR and display names of terminals and zones (e.g., Terminal D) from the API. Due to Google’s TOS we can only store Newark’s place ID and location tags  into our database not the display names but we can fetch them live when needed. Similar to API 1, we can reduce the amount of requests to the API by storing Newark’s place ID and the location tags locally. While reports from users can be linked to location metadata and timestamps that are stored to reduce the amount of searches to the API.  
**\- Failure handling plan:** If for some reason Google Places API is not available, we can still store reports locally in our DB and reports already created can be viewed. If location data is not available, a message will alert the users that information cannot be retrieved at this time.

STUDENT RESPONSE END  
\-------------------------------------------------------------------------------------------------------------------

## **Stored Data Model \- RMA9**

Keep this section high-level. You can refine names/fields later.  
Describe the project-specific data stored in the team's own database.  
Include expected tables or collections such as:  
\- **Users or user profile data**  
**\- External API records saved locally**  
**\- User-owned records**  
**\- Relationship or association tables**  
**\- Audit, log, status, or history records when relevant**  
STUDENT RESPONSE START  
**Data entity 1:**  
**\- Name:** Users  
**\- Purpose:** Stores account, login, profile, and role information for each user.  
\- Important fields: user\_id, username, email, password\_hash, role, created\_at, updated\_at  
**\- Created by:** User registration  
**\- Updated by:** User profile update or administrator role update

**Data entity 2:**  
**\- Name:** SearchHistory  
**\- Purpose:** Stores each user’s recent flight searches so users can view or repeat previous searches.  
**\- Important fields:** search\_id, user\_id, search\_type, airport\_code, flight\_number, departure\_airport, arrival\_airport, searched\_at  
**\- Created by:** System when a logged-in user searches for a flight  
**\- Updated by:** System when a new search is made

**Data entity 3:**  
**\- Name:** SavedFlights  
**\- Purpose:** Stores flights that users choose to save to their dashboard/watchlist.  
**\- Important fields:** saved\_flight\_id, user\_id, flight\_number, airline, departure\_airport, arrival\_airport, saved\_at  
**\- Created by:** Logged-in user who saves a flight   
**\- Updated by or deleted by:** Logged-in user who saved the flight

**Data entity 4:**  
**\- Name:** AirportReports  
**\- Purpose:** Stores user-submitted reports about EWR airport conditions and links each report to Google Places location data when available.   
**\- Important fields:** report\_id, user\_id, airport\_code, terminal, category, comment\_text, place\_id, location\_tag, terminal\_zone, location\_metadata, location\_cached\_at, report\_status, created\_at, updated\_at, resolved\_at  
**\- Created by:** Logged-in user who submits a report  
**\- Updated by:** Logged-in user who edits their own report or an administrator who reviews, flags, hides, or resolves reports.

**Data entity 5:**  
**\- Name:** CachedFlightData  
**\- Purpose:** Stores recent flight information retrieved from the external flight API so the system can display recent results and reduce repeated API requests.  
**\- Important fields:** cached\_flight\_id, flight\_number, airline, status, gate, terminal, delay\_minutes, cancellation\_status, departure\_time, arrival\_time, last\_updated  
**\- Created by:** API worker/system after a flight API request  
**\- Updated by:** API worker/system when newer flight data is received

**Data entity 6:**  
**\- Name:** FlightAlerts  
**\- Purpose:** Final-demo extension that stores any alerts for saved flights when delays, cancellations, or any important status changes occur.  
**\- Important fields:** alert\_id, user\_id, saved\_flight\_id, flight\_number, alert\_type, alert\_message, is\_read, created\_at  
**\- Created by:** System when a saved flight has an important update  
**\- Updated by:** User when the alert is viewed or marked as read.  
**Data entity 7:**  
**\- Name:** AdminActivityLogs  
**\- Purpose:** Stores records of administrator actions such as role changes, report removals, violation warnings, and moderation decisions.  
**\- Important fields:** log\_id, admin\_user\_id, action\_type, affected\_user\_id, affected\_report\_id, notes, created\_at  
**\- Created by:** System when an administrator performs an action  
**\- Updated by:** System only

STUDENT RESPONSE END  
\------------------------------------------------------------------------------------------------------------------

## **User Data Associations \- TAD46**

Keep this simple: explain who owns what data and who can change it.  
**Include:**

- Which records belong to a specific user  
- Which records are shared across users  
- Which records are admin-only or team-managed  
- How the system prevents one user from changing another user's data

STUDENT RESPONSE START  
**Association 1:**

- **User action:** The user creates an account and logs in  
- **Stored association:** Each user account has a unique “**user\_id**.” Every piece of data that is tied to the user (i.e. search history, saved flights) is tagged with their “**user\_id**” in the database.  
- **Access rule:** Users can only view and edit their own account. Passwords are hashed and never stored in plaintext. Only a logged-in user can change their own profile’s information.

**Association 2:**

- **User action:** The user searches for flight, route, or terminal information  
- **Stored association:** Search history is stored with the “**user\_id**,” so that the user can see their recent searches.  
- **Access rule:** Search history is private for each user so, **User A** can’t see **User B’s** search history. Queries always filter by the logged-in user’s “**user\_id**.”

**Association 3:**

- **User action:** The user views real-time flight status (delays, gates, arrivals)  
- **Stored association:** Flight status data isn’t owned by any user, but comes from an external API and is shared across all users who look up the same flight.  
- **Access rule:** Read-only for all users and nobody can modify flight status data. It only updates from the external API’s information.

**Association 4:**

- **User action:** The user submits an incident report or airport condition report  
- **Stored association:** The report would be saved in the database tagged with the “**user\_id”** of whoever submitted it, a timestamp, the report type, and description.  
- **Access rule:** The users would be able to view and delete their own submitted reports only. They can see other user’s reports and modify their own. The admins have the ability to view all reports across all users and mark them as reviewed or resolved.

**Association 5:**

- **User action:** An admin manages users and monitors system health  
- **Stored association:** Admin accounts have a “**role**” field set to “**admin**” in the users table, separate from regular users.  
- **Access rule:** Admin-only actions such as, deleting accounts, viewing all users, and managing system settings, check that “**role=admin**,” before allowing the action. Regular users cannot access admin functions even if they try to manually call the route.

STUDENT RESPONSE END  
\--------------------------------------------------------------------------------------------------------------------

## **Cross-Domain Feature Ownership \- XML**

Students are expected to work cross-domain. If a feature touches APP, API, RabbitMQ, and DB, the feature owner should be responsible for the full slice.  
Recommended team practice:  
**1**. **Break larger features into smaller linked tasks so teammates can help.**  
**2\. Trade task ownership when useful for learning and workload balance.**  
**3\. Keep one clear owner per feature while allowing collaborators on subtasks.**  
**4\. Keep excessive collaborators on each issue/PR to a minimum (the whole team should not be collectively assigned to every feature).**  
STUDENT RESPONSE START  
**Feature slice ownership plan:**  
**\- User Registration Feature / US- 01:**  
	**\- Primary owner:** Rosmy A \+ Noaman S.  
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
	**\- Planned subtasks for collaboration:**  
**Rosmy or Noaman:** Will publish registration requests that include users desired emails and passwords  
**Tristan:** Will create table that will store user accounts that registered and will ensure that their passwords are hashed  
**Caitlin:** Will create the user registration queue for routing as well as for formatting

**\- User Login and Authentication** (For Regular and Admin) **/ US- 01:**  
	**\- Primary owner: Tristan D.**  
	**\- Cross-domain touchpoints: APP / RabbitMQ / DB**  
	**\- Planned subtasks for collaboration:**  
**Noaman S or Rosmy A:** Will publish login requests that have username and passwords  
**Caitlin O:** Routes the login queue off to the database in order for authentication of user  
**Tristan:** Validates the users input with their registered information on the db table and returns result   
**Xaidyn L**: creates session persistence and authentication protection in for users on the platform

**\- Search Engine Feature / US-02:**  
	**\- Primary owner:** Xaidyn L.   
	**\- Cross-domain touchpoints:** APP / API / RabbitMQ / DB  
**Noaman S or Rosmy A:** Will send search queue requests off to RabbitMQ  
**Caitlin**: Routes queue messages and jobs between App server, API worker, and Database server  
**Xaidyn L:** Gets information about flights from external API  
**Tristan D:** Takes data from API retrieval and stores it in a table  
**\- Recently Viewed / Saved Feature / US- 02:**  
	**\- Primary owner:** Tristan D.   
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
**\- Planned subtasks for collaboration:**  
**Noaman S or Rosmy A:** Will publish saving flights and send jobs  
**Caitlin**: Will route these requests off to the database server  
**Tristan**: Will be storing recently saved and viewed flights in a database table using users id number on the table

**\-Display Feature** (Shows Results of Searched Flights) **/ US-02:**  
	**\- Primary owner:** Caitlin O.   
	**\- Cross-domain touchpoints:** APP / API / RabbitMQ / DB  
	**\- Planned subtasks for collaboration:**  
**Noaman S or Rosmy A:** Will ensure that flight search requests are sent off to RabbitMQ  
**Tristan D:** Will make sure that information retrieved from the external API is correctly stored in tables for future usage  
**Xaidyn L:** Will make sure that the API worker will be able to retrieve requested information, transform the data, and have it sent back to the database server

**\- Terminal Access Feature** (Shows reports of recent users' experience with Terminal Access) **/ US-03:**  
	**\- Primary owner:** Xaidyn L.   
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
	**\- Planned subtasks for collaboration:**  
**Noaman S or Rosmy A:** Will publish report feature requests like (updating, creation, deletion) to the RabbitMQ server  
**Caitlin**: Define queue names and routing on the MQ server  
**Tristan D:** Will store terminal reports in database per user

**\-Accident Report Feature** (Shows Results of Warnings Administered by Airport staff published on the site) **/ US-03:**  
	**\- Primary owner:** Noaman S.  
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
**Rosmy A:** Will send off accident report requests off to the RabbitMQ server  
**Tristan D:** Will save these reports in the database by report id and the user id   
**Caitlin:** Will route these reports queues off to the database server

**\-Bathroom Report Feature** (Shows reports of recent users' experience with bathroom Access) **/ US-03:**  
	**\- Primary owner:** Rosmy A.  
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
	**\- Planned subtasks for collaboration:**  
		**Xaidyn L:** Publish bathroom reports (creation, update, and deletion) to RabbitMQ  
**Tristan D:** Will store all user reports in a table by user id and time, etc.  
**Caitlin:** Route request off to the database

**\-TSA Report Feature** (Shows reports of recent users' experience with TSA waiting times) **/ US- 03:**  
	**\- Primary owner:** Rosmy A.  
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
	**\- Planned subtasks for collaboration:**  
		**Xaidyn L:** Publish TSA reports off to the RabbitMQ server   
**Caitlin:** Will route these TSA reports off to the database server  
**Tristan D:** Will store these reports off in our database by user id 

**\- Administrative Dashboard** (Allows for Administrative overview of user comments on self-guide, review, and deletion of inappropriate content) **/ US-04:**  
	**\- Primary owner:** Tristan D.  
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
	**\- Planned subtasks for collaboration:**  
**Noaman A:** Will publish report actions like approvals, deletions, and flags to RabbitMQ  
**Caitlin:** Will route these requests off to the database server  
**Tristan**: Will update our table accordingly to the information presented in queue message  
**Xaidyn L**: Will ensure that regular users do not have access to the admin portion of the platform

**\- Administrative Dashboard** (Allows for Administrative alerts (e.g., Airport emergencies, downtime maintenance of platform, etc.)to be created to send to users on the platform) / **US- 05:**  
	**\- Primary owner:**  Xaidyn L. \+ Caitlin O.  
	**\- Cross-domain touchpoints:** APP / RabbitMQ / DB  
	**\- Planned subtasks for collaboration:**  
		**Rosmy A:** Publish alert requests off to the RabbitMQ server  
		**Caitlin O:** Routes these requests of to the database server  
**Tristan D:** Will store alert information into the database for future use  
STUDENT RESPONSE END  
\--------------------------------------------------------------------------------------------------------------------

## **Team Members And Responsibilities \- TAD46**

List each team member's expected responsibilities. Responsibilities can overlap, but each person should have visible ownership.  
STUDENT RESPONSE START  
**Team member 1:** 

- **Name:** Caitlin Ortiz  
- **Primary responsibilities:**   
  - Project Management  
  - RabbitMQ broker configuration and maintenance  
- **Services/features touched:**   
  - RabbitMQ Message Broker (VM2),   
  - Job queue setup for flight search requests/events for real time status updates  
  - Queue health monitoring during development.

**Team member 2:** 

- **Name:** Xaidyn Liranzo  
- **Primary responsibilities:**   
  - Integrating with external APIs, creating job processing rules   
  - Dealing with API error responses and rate limits  
  - Translating raw flight information into usable flight data  
- **Services/features touched:**   
  - API worker (VM3)   
  - Calling external flight tracking APIs for real-time information  
  - Calling external maps/reporting APIs for self-user reporting  
  - Sending flight tracking results through RabbitMQ

**Team member 3:** 

- **Name:** Rosmy Antony  
- **Primary responsibilities:**   
  - Frontend UI development  
  - Connecting browser forms to backend job submission  
  - Displaying flight results and status updates to the user  
  - Displaying other users’ reports on Newark airport conditions to the user  
- **Services/features touched:**   
  - Browser UI   
  - APP Server (VM1) 

**Team member 4:** 

- **Name:** Noaman Shahid  
- **Primary responsibilities:**   
  - Management and authentication of user sessions  
  - Displaying search history   
  - Management of the Frontend state   
- **Services/features touched:**   
  - Browser UI   
  - APP Server (VM1)  
  - Auth routes, session handling, publishing user-specific job requests to RabbitMQ

   
**Team member 5:** 

- **Name:** Tristan Duncan  
- **Primary responsibilities:**   
  - Database design, management, and storing user data and search history.   
  - VM network connectivity so that all VMs can reach each other reliably with Tailscale.  
- **Services/features touched:**   
  - DB Server (VM4) MySQL setup and schema  
  - Storing user accounts, user roles, hashed passwords, emails, retrieving flight search history per user, saved flight lists from users, admin logs, and storing/retrieving reports or comments by users  
  - Tailscale VPN configuration across all team VMs

STUDENT RESPONSE END  
\--------------------------------------------------------------------------------------------------------------------

## **MVP Goals \- CAO39**

**The MVP is the minimum useful version of the project that should be ready for the midterm milestone.**  
List the required MVP behaviors and reference story IDs.  
Use plain language for each item. You are describing goals, not implementation steps.  
The MVP should be demo-ready. It should show working behavior, not only plans or screenshots.  
STUDENT RESPONSE START  
**1\. \[Validate User Credentials System \] \-\> Story IDs: US-01 \-\> Required ACs: US-01 AC1, US-01 AC2, US-01 AC3**   
	Users are able to register, login, and maintain their account while ensuring secure access to the system.  
**2\. \[Flight Search System\] \-\> Story IDs: US-02 \-\> Required ACs: US-02 AC1, US-02 AC2, US-02 AC3**  
	Users are able to search for a flight using the AeroDataBox API and review search results, flight status changes (e.g., delays, cancellations, gate changes), and get error notifications if there is no data available.  
**3\. \[Newark Airport Reporting System \] \-\> Story IDs: US-03 \-\> Required ACs: US-03 AC1, US-03 AC2, US-03 AC3, US-03 AC4**   
Users are able to self-report, modify, and view reports from others in the community related to the conditions at Newark airport. Only users that created the report can edit or delete it.   
**4\. \[Administration Community Management \] \-\> Story IDs: US-04 \-\> Required ACs: US-04 AC1, US-04 AC2, US-04 AC3**  
	Users that are assigned as administrators can review and maintain users, their roles, and airport reports that users have created. Administrators are able to flag or delete inappropriate reports/comments that breach the community guidelines (e.g., spam, incorrect reports, or any obscene reports/comments).   
**5\. \[Save a Flight Management System\]  \-\> Story IDs: US-05 \-\> Required ACs: US-05 AC1, US-05 AC4, US-05 AC5**  
	Users are able to save a single flight and delete flights that they can add to their save/watch list on their own dashboard that only they can see. Users are also only able to remove flights from their own save/watch list in order to stop monitoring them.   
STUDENT RESPONSE END  
\--------------------------------------------------------------------------------------------------------------------

## **Final Demo Goals \- XML**

List the additional behavior expected by the final demo and reference story IDs.  
**Final demo items should extend MVP scope, not repeat the same exact goal statements.**  
STUDENT RESPONSE START  
**1\. \[Secure Account Modifications\] \-\> Story IDs: US-01 \-\> Required ACs: US-01 AC4, US-01 AC5, US-01 AC6**  
Users will be able to maintain and manage their account information, profiles, and passwords accordingly. Updates on passwords, statuses on accounts, etc will be improved for users' convenience.   
**2\. \[Real Time Flight Search and Real-Time Status\] \-\> Story IDs: US-02-\> Required ACs: US-02 AC4, US-02 AC5, US-02 AC6**  
Users will also be able to search again or refresh their desired flights in order to receive real time information about said flights. Statuses on flight updates will be displayed to users, as well as alerts when there is downtime due to API issues. 

**3\. \[Flagging Reports and History\] \-\> Story IDs: US-03\> Required ACs: US-03 AC5,US-03 AC6, US-03 AC7, US-03 AC8**  
Users, individually, will be given an indication warning on reports that they make, ranking them as either valid to post or flagged. Other users will not be able to view these comments. But users will be able to see their comment history and refer back in order to see if their comments/reports were marked for any violations. They will also be able to edit their comments/reports and delete them as well.   
	  
**4\.  \[Administration User Monitoring\] \-\> Story IDs: US-04-\> Required ACs: US-04 AC4,US-04 AC5, US-04 AC6, US-04 AC7**  
Admins on our platform will have records of users' accounts, specifically their flagged reports/comments, their account restrictions, etc. Therefore, allowing admins to be able to monitor the behaviors of users over long time intervals. This will allow for respective user account repercussions.  

**5\.  \[ Flight Status Notifications\] \-\> Story IDs: US-05-\> Required ACs: US-05 AC5,US-05 AC6, US-05 AC7, US-05 AC8**  
Users will be able to get notified when impactful events occur to their saved flights; for example, they will be notified about delays, terminal changes, gate changes, arrival times, etc. They will not only be able to view these saved flights, but they will also be able to delete them at their own discretion.   
STUDENT RESPONSE END  
\-----------------------------------------------------------------------------------------------------------------

## **Stretch Features \- TAD46**

Stretch features are optional improvements after the required MVP, milestone, and final demo requirements are stable.  
Stretch features should not replace required work. If a stretch feature threatens the MVP, milestones, or final demo, postpone it.  
**STUDENT RESPONSE START**

**Stretch feature 1:** Add support for more airports other than EWR  
**\- Why it helps:** Adding more airports would expand the app’s usefulness to a wider audience. The users would be able to track flights at many other airports instead of being limited to just Newark. It could make the final app feel more complete.   
**\- Risk/dependency:** The risk depends on the external flight API supporting multiple airports. The UI could also become more complex, and we would need to have some kind of filtering logic to sift through the data of multiple airports, adding work to the frontend and search logic. We would also have limits on API calls, which would exhaust faster with many airports.  
**\- Attempt only after:** This is only applicable if the flight search system works, there are real-time status updates, and the search history system fully works. Also, the MVP would be stable and the 4 VM servers can communicate with each other reliably.

**Stretch feature 2:** Add email verification on top of account registration  
**\- Why it helps:** Adding email verification would give more security and authentication to user accounts. It also helps to confirm that the user owns the email they used to sign up. Preventing fake accounts is standard practice in real applications.  
**\- Risk/dependency:** Adding email verification needs an external email service, which would be a new dependency on top of the flight API. The email job would need to go through the API worker, with RabbitMQ, which would add a new job type and more queue flow. If the email service isn’t configured correctly, it could block the users from registering at all.  
**\- Attempt only after:** This can only be attempted if the user login and authentication features are fully working without email verifying first and if the API worker is stable and efficiently handling the flight data jobs. It can’t be attempted until the MVP goals are met. 

**Stretch feature 3: Add an interactive map of the Newark Airport**  
**\- Why it helps:** An Interactive EWR Map can enable users to locate the terminals, gates, and major amenities of the airport, TSA checkpoints, bathrooms and food, by viewing them visually rather than as text. The interactive map can complement the self-reporting component. Each user report could be attached to the corresponding area of the airport so that “long TSA line” or “Bathroom out of service” reports pinpoint the actual location of where each report applies.   
**\- Risk/dependency:** This will need a mapping source, likely the suggested Google Places API, for capturing EWR location and place data, and possibly adding this with a separate dedicated maps provider if we need more detailed indoor or terminal-level information. Because indoor maps for airports don‘t have consistent availability with general mapping APIs, this may mean that the terminal and gate level detail will be very limited or would need the use of a static map. In terms of the front-end application, adding airport terminal and gate location and the capability to render an interactive map, and have the ability to plot pins and respond to click events on those pins will be more challenging and will create a new software dependency on top of the underlying flight service provider’s API. Also, linking report data to the locations displayed on the maps will also add additional work to the model of Report Data, and the App/DB Flow because we’ll need to have either coordinates or some type of terminal/area reference with each report.  
**\- Attempt only after:** The MVP needs to be stable before this can be attempted like after conducting flight searches, and the functionality to save flights, create, read, update, and delete reports, and the basic features of an administrator works well. There are other requirements. There should be an already functioning Google Places integration to be used in reporting on this.

STUDENT RESPONSE END  
\--------------------------------------------------------------------------------------------------------------------

## **Approval Checklist \- CAO39**

Before submitting the proposal, confirm:  
✔️- **The project objective is specific enough to evaluate**  
✔️**\- Authentication flows (register, login, logout, and profile update) are covered in user stories**  
✔️**\- Password security approach is noted (passwords will not be stored in plaintext)**  
✔️**\- Admin role, role/permission model, and maintenance pages are planned and covered by at least one user story**  
✔️**\- At least one external API is required, documented, and relevant**  
✔️**\- The team-owned database data is described**  
✔️**\- User-data associations are clear**  
✔️**\- Core user stories represent feature-complete scope (recommended 5-8, minimum 5\)**  
✔️**\- Service touchpoints are defined at a useful high level for each story (APP, DB, RabbitMQ, API)**  
✔️**\- MVP and final demo goals are realistic**  
✔️**\- MVP and final demo items reference story IDs and acceptance criteria**  
✔️**\- MVP and final demo goals together cover the core user stories**  
✔️**\- Stretch features are clearly optional**  
✔️**\- Team responsibilities are visible**  
✔️ **\- The approved proposal Markdown is committed to the team repository**  
**\--------------------------------------------------------------------------------------------------------------------**

## **AI Disclosure**

Disclose any meaningful AI help used for this proposal. Include what the AI helped with, what you changed, and how you verified the result. **If you did not use AI, write "No meaningful AI assistance used."**  
STUDENT RESPONSE START  
**AI was utilized to confirm that the data fields we researched from the two chosen external API site guides, AeroDataBox and Google Places, match the fields that other applications similar to ours use. After getting the results, we compared them to the data that both external APIs could provide on each of their sites. After our review, we removed and updated our data fields to reflect what was necessary for this project.**   
STUDENT RESPONSE END