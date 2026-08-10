Milestone 3 - DB Screenshot Captions

Section 1 - Task 1: Environment Lane Inventory and Readiness

	Picture 1: DB Lane Vms Setup and Main Service
	Picture 2: Inventory.json file VM Lanes and Roles

Section 2 - Task 4: Ordered Database Migration Evidence

- Folder: 1 migration runner and the file naming or ordering pattern

	Picture 1: dbdev Migrations Folder showing migrations numbering
	Picture 2: dbdev VM Test Migration File MySQL query
	Picture 3: dbdev database migration runner script

- Folder: 2 migration applied and recorded in QA, followed by a repeated run

	Picture 1: Successful file promotion and database migration from dev to qa
	Picture 2: dev to qa migration retest check
	Picture 3: Successful database migration on dbqa

- Folder: 3 QA check passes, show the same migration or release applied and recorded in production

	Picture 1: Successful file promotion and database promotion from qa to prod

- Folder: 4 database result with the matching migration or release ID

	Picture 1: Evidence of empty prod db on production vm
	Picture 2: Successful database migration to prod db from qa db

Section 3 - Task 1: End-to-End Promotion Demo

- Folder 1: Show one release ID moving through both promotion steps after the QA check passes

	Picture 1: Successful file promotion and database migration from dev to qa
	Picture 2: Successful file promotion and database promotion from qa to prod

- Folder 2: Show concise QA and production readiness checks for the DB core services

	Picture 1: QA DB VM MySQL active, otr_qa schema present with expected tables
	Picture 2: Prod DB VM MySQL active, otr_prod schema present with expected tables

- Folder 3: Show the related promotion log. Reuse earlier evidence and add only the missing end-to-end proof

	Picture 1: Log Output from dbdev promotion tool
	Picture 2: dbqa vm db file successful promotion
	Picture 3: dbproda vm db file successful promotion from qa
	Picture 4: Successful database migration on dbqa
	Picture 5: Successful database migration on dbproda