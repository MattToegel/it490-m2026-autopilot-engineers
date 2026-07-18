# US-03 — Newark Airport Reporting System (Noaman Shahid, ns87)

Report CRUD (view / create / edit / delete) for Newark (EWR) airport reports,
integrated into the team system: shared RabbitMQ transport, shared session,
the team database schema, and the shared stylesheet.

## Files (App side)
- `app/reports/report_client.php` — `sendReportRequest()`: sends `report.*` requests
  over the shared `app.requests` topic exchange with a reply_to queue + correlation_id
  (mirrors `auth_client.php`).
- `app/reports/reports.php` — community list of recent reports (AC1).
- `app/reports/report_create.php` — create a report with a category (AC2).
- `app/reports/report_edit.php` — edit own report only (AC3).
- `app/reports/report_delete.php` — delete own report only (AC4).

## How it connects to the rest of the system
- **Transport:** `sendReportRequest('report.list' | 'report.create' | 'report.update' |
  'report.delete' | 'report.get_one', $payload)` — same RPC pattern the auth pages use.
- **Session:** each page requires `app/auth/auth_protect.php` and reads `$_SESSION['user_id']`.
- **Fields:** `category`, `comment_text`, `terminal`, `airport_code` (EWR), `report_id`.
- **Styling:** shared `/public/auth_styles.css` (no per-page inline styles).

## Ownership (AC3 / AC4)
Edit and delete are restricted to the report's owner. The App checks ownership before
showing the edit form, and the DB consumer (`reports_consumer.php`) checks it again on
`report.update` / `report.delete`, returning `forbidden` on a `user_id` mismatch — so the
rule holds even if the App layer is bypassed.

## DB side
Report persistence and the reports table live on the DB VM in `reports_consumer.php`
(owned by the DB VM member). This module is the App-side half that talks to it.

## Status
App pages converted to the team transport / session / schema / styling, ready for
integration testing against `reports_consumer.php`.
