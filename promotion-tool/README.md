# promotion-tool (IT490 MS3 — Autopilot Engineers, ns87)

Small SSH-based tool that promotes approved changes **development → QA → production**
across the App / DB / MQ / API lanes. No Git is used to place changes in QA or prod.

## Layout
```
promotion-tool/
├── inventory.json                 # SHARED - all lanes/roles/hosts/paths (NO secrets)
├── lib/inventory.php              # SHARED - loads config, blocks dev->prod, writes log
├── app/
│   ├── promote.php                # App promoter (single file + bulk release, over SSH)
│   ├── config-templates/          # App-role config templates (ns87 / Person B)
│   │   ├── app.env.template        #   RabbitMQ keys, placeholders only
│   │   └── apache-otr.conf         #   Apache vhost for the app
│   └── releases/
│       └── rel-2026-07-27-01/     # a bulk release (immutable package)
│           ├── manifest.json       #   what's in the release + where it goes
│           └── files/              #   the exact files promoted
├── backups/ (gitignored)          # local copies of backups if pulled back
└── logs/    (gitignored)          # promotion.log (one JSON line per event)
```

## Ownership
- **Person A (Rosmy, rma9):** App code files, single-file promotion, dev → QA logic.
- **Person B (Noaman, ns87):** App **config templates**, **bulk release** promotion,
  **QA → production** logic. (This README, `config-templates/`, `releases/`, and the
  QA→prod + bulk path in `promote.php` are Person B's.)

## Guardrails
- Only `development → qa` and `qa → production` are allowed. A direct
  `development → production` is **blocked** by `assert_promotion_allowed()`.
- A bulk release can only reach **production** if the log shows it already reached **qa**
  (the same tested release moves forward — it is never rebuilt from dev or GitHub).
- Every change is **backed up on the target first**, then copied, then **checksum-verified**.
- Any invalid lane, missing target, failed backup, failed transfer, or checksum
  mismatch **stops with an error** and writes a `result: failed` log line.

## Usage
Single approved file (Person A, dev → qa):
```
php app/promote.php --from development --to qa --role app --file otr.conf
```
Bulk release (Person B, qa → prod):
```
php app/promote.php --from qa --to production --role app --release rel-2026-07-27-01
```
Preview only (no changes):
```
php app/promote.php --from qa --to production --role app --release rel-2026-07-27-01 --dry-run
```

## Secrets
Real secrets live **only** in each lane's own `app/.env` (outside Git, never promoted).
The templates here carry placeholders (`__SET_PER_LANE__`) so no secret ever leaves dev.

## Setup notes
- Fill the `TODO-...` hosts in `inventory.json` once the QA and production VMs exist.
- The operator (or lane admin) places the SSH keys referenced by `ssh_key` outside Git.
- Service reload uses `sudo systemctl reload <service>`; the promo user needs
  passwordless sudo for that one command on each target.
