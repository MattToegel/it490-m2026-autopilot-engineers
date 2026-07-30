# OnTheRadar — MS3 Promotion Tool (DB piece)

This lives at `db/promotion/` inside the team repo (a subfolder of the
existing `db/` application code), not at the repo root, since Git only
exists in development and this tool only ever runs from dbdev.

## Layout

```
db/                          # existing app code (repo root's db folder)
├── admin/
├── auth/
├── ConsumerManager/
├── flights/
├── logging/
├── reports/
├── vendor/
├── .env                     # dev secrets - NEVER promoted
├── composer.json
├── composer.lock
├── db_schema.sql            # legacy/reference - NEVER promoted (migrate.php's migrations/ are the real source of truth)
└── promotion/                    # <-- this tool
    ├── inventory.json            # shared config: lanes -> roles -> hosts/paths
    ├── lib/
    │   └── inventory.php          # shared functions: loadInventory / validatePromotion / getRoleConfiguration / writePromotionLog
    ├── migrate.php                 # SCHEMA changes: connects to remote MySQL directly, no files move
    ├── promote.php                 # CODE changes: SFTPs an explicit allow-list of folders to the target VM
    ├── rollback.php                 # DB restore from a specific backup file
    ├── migrations/
    │   ├── 000_initial_schema.sql    # baseline - creates tables on an empty qa/prod DB
    │   └── 001_....sql                # ordered migration files, numbered prefix = run order
    ├── backups/                       # local backup + release-snapshot output (gitignored)
    ├── logs/                           # local promotion.log output (gitignored)
    ├── .env.development                # DB_USER / DB_PASS for dev (gitignored)
    ├── .env.qa                          # DB_USER / DB_PASS for qa (gitignored)
    └── .env.production                   # DB_USER / DB_PASS for prod (gitignored)
```

## Lane names

Lanes are `development`, `qa`, `production` (matching the App lane's
convention) — not `dev`/`qa`/`prod`. All command-line usage and
`inventory.json` use the full names.

## Usage

```bash
# development -> qa (new release, auto-generated id)
php promote.php qa --from=development
php migrate.php  qa --from=development

# force a specific release id, e.g. to keep promote.php and migrate.php
# tagged under the same id for one release:
php promote.php qa --from=development --release=my-release-1
php migrate.php  qa --from=development --release=my-release-1

# qa -> production (MUST reuse the release id that was tested in qa)
php promote.php production --from=qa --release=my-release-1
php migrate.php  production --from=qa --release=my-release-1

# restore a lane's DB from a specific backup
php rollback.php qa /home/tad46/it490-m2026-autopilot-engineers/db/promotion/backups/qa-my-release-1.sql
```

## What actually gets promoted vs. what never does

`promote.php` copies an **explicit allow-list** only: `admin/`, `auth/`,
`ConsumerManager/` (minus its `consumer_logs/` runtime folder), `flights/`,
`logging/`, `reports/`, `composer.json`, `composer.lock`.

It never promotes:
- **`.env`** — dev's secrets; qa/prod get their own `.env.qa`/`.env.production` placed manually
- **`promotion/`** — the tool itself; doesn't belong on qa/prod
- **`db_schema.sql`** — legacy/reference only; `migrate.php`'s `migrations/` files are the real schema source of truth
- **`ConsumerManager/consumer_logs/`** — runtime log output, not code

## How it enforces the rules

- **development -> production blocked:** `validatePromotion()` only permits
  `development->qa` and `qa->production`. Every script calls this before
  doing anything else.
- **No manual path typing:** scripts call `getRoleConfiguration($inventory, $lane, 'db')`
  instead of hardcoding hostnames/paths. App/API use the same functions
  with `'app'`/`'api'`.
- **Backup before change:** `migrate.php` runs `mysqldump` and confirms the
  file is non-empty *before* touching the schema; `promote.php` tars up
  whatever's currently on the target and pulls it back locally before
  overwriting. Both fail safely if the backup step doesn't produce a
  usable file.
- **No double-apply:** the `otr_migrations_applied` table (per lane) is
  checked before each migration file runs; already-applied migrations are
  skipped, not re-run.
- **Rollback:** `rollback.php` restores a specific `.sql` backup to a lane;
  pair it with a screenshot of a known table/row state before and after.
  Known limit: rollback does not itself snapshot the *current* state first,
  and it does not re-sync the migrations-tracking table automatically -
  after a rollback, re-run `migrate.php` and manually reconcile
  `otr_migrations_applied` if the restored schema is out of sync with what
  the tracking table says has been applied.
- **Logging:** every script calls `writePromotionLog()`, appending one
  timestamped line to `logs/promotion.log` - this becomes your Section 2
  evidence trail. Pass the same `--release=<id>` to both `promote.php` and
  `migrate.php` to tie their log lines together under one release.

## Two different jobs for DB: schema vs. code

- **`migrate.php`** changes the remote database *schema*. It never copies
  files — it connects directly to the target lane's MySQL over the network
  (via Tailscale) and runs the ordered `.sql` migration files against it.
- **`promote.php`** moves the actual consumer *code* onto the target VM,
  since that has to physically exist there to run. Uses **SFTP** (via the
  `sftp` CLI), never Git.
  - `--from=development`: snapshots the current tested `db/` folder on
    dbdev into `backups/db-releases/<release_id>/`, then SFTPs that
    snapshot to the target.
  - `--from=qa --release=<id>`: reuses that *exact same* snapshot folder to
    push to production — it does not re-copy from the live repo, so qa and
    prod always get identical bits ("same tested release moves forward").
  - Backs up whatever's currently on the target (tar over SSH, pulled back
    locally via SFTP) before overwriting.
  - Deliberately does **not** auto-restart consumers after promoting -
    start/restart them manually via `manage-consumers.sh` when ready to
    test, to keep this script's failure surface smaller.

## To extend for App and API

Follow the same pattern as `db/promotion/migrate.php` / `promote.php`,
using the shared `lib/inventory.php` functions:
1. `loadInventory()` then `validatePromotion($inventory, $from, $to)`
2. `getRoleConfiguration($inventory, $lane, 'app')` (or `'api'`)
3. Copy the file(s)/release over `scp`/`sftp` to the role's `destination_path`
4. `writePromotionLog(...)`

A **bulk release** just means grouping multiple files/migrations under one
`release_id` and looping over them.

## Secrets

Real DB credentials belong in `.env.development`, `.env.qa`,
`.env.production` (one per lane, sitting directly in `promotion/` on
dbdev, gitignored) — never in `inventory.json`, never committed, never in
logs/screenshots. Format:
```
DB_USER=youruser
DB_PASS=yourpassword
```

## Status

- DB: migrate.php / promote.php / rollback.php built and tested end-to-end
  (development -> qa -> production) using a shared release id.
- App / API: build sibling scripts following the pattern above.
