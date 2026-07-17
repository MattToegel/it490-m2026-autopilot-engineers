# US-03 — Newark Airport Reporting System (Noaman Shahid)

Report CRUD (view / create / edit / delete) with database-enforced ownership.
Flow: App -> RabbitMQ (cluster rabbit@ns87-mq) -> DB worker -> RabbitMQ -> App.

## Files
- app/reports/reports.php         - list recent reports (AC1)
- app/reports/report_create.php   - create a report with a category (AC2)
- app/reports/report_edit.php     - edit own report only (AC3)
- app/reports/report_delete.php   - delete own report only (AC4)
- app/reports/mq_helper.php       - RabbitMQ send/receive helper
- app/reports/config.php          - queue names + MQ settings
- db/reports/db-worker.php        - DB consumer: report.get_all/create/update/delete/get_one
- db/reports/reports_schema.sql   - reports table definition
- app/reports/tests/test_forbidden.php        - proves DB refuses a non-owner EDIT (AC3)
- app/reports/tests/test_forbidden_delete.php - proves DB refuses a non-owner DELETE (AC4)

## Ownership (AC3 / AC4)
Enforced in the database: report.update and report.delete compare the report's
user_id to the requester's user_id and return "forbidden" on mismatch, so the
rule holds even if the app layer is bypassed. Verified by the test_forbidden scripts.

## Integration note (follow-up)
This module currently uses its own MQ helper and consumer (action-based dispatch).
Planned follow-up: merge into the team's auth_client.php / auth_consumer.php
routing-key pattern and wire the existing reports_drawer.js UI (coordinating with tad46).
