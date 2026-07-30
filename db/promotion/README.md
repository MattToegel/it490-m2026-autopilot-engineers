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
├── db_schema.sql            # legacy/reference - NEVER promoted (migrate.php is the real source of truth)
└── promotion/               # <-- this tool
    ├── inventory.json       # shared config: lanes, roles, hosts, paths
    ├── lib/
    │   └── inventory.php    # shared helper: load config, enforce dev->qa->prod, write logs
    ├── migrate.php           # SCHEMA changes: connects to remote MySQL directly, no files move
    ├── promote.php           # CODE changes: SFTPs an explicit allow-list of folders to the target VM
    ├── rollback.php           # DB restore from a specific backup file
    ├── migrations/
    │   └── 001_....sql        # ordered migration files, numbered prefix = run order
    ├── backups/                # local backup + release-snapshot output (gitignore this)
    └── logs/                   # local log output (gitignore this)
```

## What actually gets promoted vs. what never does

`promote.php` copies an **explicit allow-list** only: `admin/`, `auth/`,
`ConsumerManager/` (minus its `consumer_logs/` runtime folder), `flights/`,
`logging/`, `reports/`, `composer.json`, `composer.lock`.

It never promotes:
- **`.env`** — dev's secrets; qa/prod get their own `.env.qa`/`.env.prod` placed manually
- **`promotion/`** — the tool itself; doesn't belong on qa/prod
- **`db_schema.sql`** — legacy/reference only; `migrate.php`'s `migrations/` files are the real schema source of truth
- **`ConsumerManager/consumer_logs/`** — runtime log output, not code


## How it enforces the rules

- **dev -> prod blocked:** `Inventory::assertPromotionAllowed()` only permits
  `dev->qa` and `qa->prod`. Every script should call this before doing
  anything else.
- **No manual path typing:** scripts call `$inv->getTarget('db', 'qa')` etc.
  instead of hardcoding hostnames/paths. Add App/API in the same style.
- **Backup before change:** `migrate.php` runs `mysqldump` and confirms the
  file is non-empty *before* touching the schema. Fails safely if the dump
  doesn't produce a usable file.
- **No double-apply:** a `migrations_applied` table (per lane) is checked
  before each migration file runs.
- **Rollback:** `rollback.php` restores a specific backup file tied to a
  lane; pair it with a screenshot of the row count/state before and after.
- **Logging:** every script writes one JSON line via `Inventory::log()` with
  lane, role, release id, backup file, result, and timestamp — this becomes
  your Section 2 evidence trail.

## Two different jobs for DB: schema vs. code

- **`migrate.php`** changes the remote database *schema*. It never copies
  files — it connects directly to the target lane's MySQL over the network
  (via Tailscale) and runs the ordered `.sql` migration files against it.
- **`promote.php`** moves the actual consumer *code* (`flights_consumer.php`,
  `auth_consumer.php`, etc.) onto the target VM, since that has to physically
  exist there to run. This uses **SFTP** (via the `sftp` CLI), never Git.
  - `--from=dev`: snapshots the current tested `db/` folder on dbdev into
    `backups/db-releases/<release_id>/`, then SFTPs that snapshot to `dbqa`.
  - `--from=qa --release=<id>`: reuses that *exact same* snapshot folder to
    push to `dbprod` — it does not re-copy from the live repo, so QA and
    prod always get identical bits (satisfies "same tested release moves
    forward").
  - Backs up whatever's currently on the target (tar over SSH, pulled back
    locally via SFTP) before overwriting, and restarts the consumer service
    after the copy.

## To extend for App and API

Follow the same pattern as `db/migrate.php`:
1. `$inv->assertPromotionAllowed($from, $to)`
2. `$target = $inv->getTarget('app', $lane)` (or `'api'`)
3. Copy the file(s)/release over `scp`/`rsync` to `$target['app_path']`
4. Restart `$target['service']` and confirm it's healthy
5. `$inv->log([...])`

A **bulk release** just means grouping multiple files/migrations under one
`release_id` and looping over them — the manifest can be as simple as a
`release.json` listing which files belong to that release.

## Secrets

Real DB credentials belong in `.env.dev`, `.env.qa`, `.env.prod` (one per
lane, on the relevant VM, outside Git) — never in `inventory.json`, never
committed, never in logs/screenshots.

## Next steps for the team

1. Everyone agrees on this (or an adjusted) `inventory.json` shape.
2. DB (tad46): harden `migrate.php`/`rollback.php`, wire real ssh transport
   instead of running locally, test against dbdev -> dbqa -> dbprod.
3. App/API owners: build sibling scripts using the same `Inventory` class.
4. Someone builds a thin coordinator CLI (`promote.php app qa --from=dev`)
   that just dispatches to the right role script.
