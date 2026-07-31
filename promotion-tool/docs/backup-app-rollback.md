# App Backup and Rollback Procedure

> **Owner:** Rosmy Antony (rma9)
>
> **Role:** App VM – Backup and rollback documentation for Task #7.

## Purpose

This document describes the manual backup and rollback procedure used for the App VM before promoting application files. A release-linked backup is created before modifying an application file so that the previous version can be restored if needed.

## Backup

Release ID:
`rel-2026-07-27-01`

Example commands:

```bash
mkdir -p "$BACKUP_DIR"
sudo cp "$TARGET" "$BACKUP_DIR/rollback-demo.txt"

printf "release_id=%s\nbackup_id=%s\ntarget=%s\n" \
"$RELEASE_ID" "$BACKUP_ID" "$TARGET" \
> "$BACKUP_DIR/backup-info.txt"

sha256sum "$TARGET" "$BACKUP_DIR/rollback-demo.txt"
```

## Rollback

Restore the application file from the release-linked backup.

```bash
sudo cp "$BACKUP_DIR/rollback-demo.txt" "$TARGET"
```

## Verification

Verify that the restored file matches the backup.

```bash
sha256sum "$TARGET" "$BACKUP_DIR/rollback-demo.txt"

cmp -s "$TARGET" "$BACKUP_DIR/rollback-demo.txt"
```

A successful rollback is confirmed when the checksums match and the restored file is identical to the release-linked backup.
