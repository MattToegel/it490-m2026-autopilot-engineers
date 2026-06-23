# DB VM

This folder has all the scripts and configuration for the Database VM in the IT490 centralized logging system. The DB VM runs MySQL locally and acts as the consumer for log messages flowing through RabbitMQ.

## Role

The DB VM is responsible for:

- Listening on the `db.logs` queue for incoming log events
- Validating each message has the required fields
- Inserting valid messages into the MySQL `logs` table
- Routing malformed or unprocessable messages to the deadletter exchange
- Maintaining a local mirrored log file (`db_listener.log`) for traceability

## Files

| File | Purpose |
|---|---|
| `setup-db.sh` | Installs MySQL, PHP, Composer, dependencies, and configures the database and RabbitMQ config |
| `db_schema.sql` | SQL schema for the `logs` table, applied automatically by setup |
| `db_consumer.php` | Long-running consumer script that processes messages from `db.logs` |
| `send_log.php` | CLI tool for publishing a test log message to the system |
| `testlogger.php` | Standalone test harness for the logging interface |
| `db-info.sh` | Helper script that prints all databases, users, tables, and the `logs` table structure |
| `composer.json` | Defines PHP dependencies (`php-amqplib`) |
| `testRabbitMQ.ini` | Local RabbitMQ connection config (not committed - generated from `.env`) |
| `db_listener.log` | Local mirrored log file (not committed - created at runtime) |

## Setup

1. Make sure the repo-root `.env` file exists with these values

2. Run the setup script:

   ```bash
   bash setup-db.sh
   ```

   The script is safe to run multiple times - it skips anything already installed or configured.

3. Confirm everything is in place:

   ```bash
   bash db-info.sh
   ```

## Running the Consumer

Start the consumer so it can listen for incoming log messages:

```bash
php db_consumer.php
```

The consumer runs until manually stopped. Each message it processes is logged in `db_listener.log` and inserted into the MySQL `logs` table.

## Sending a Test Log

To test the pipeline end-to-end, run:

```bash
php send_log.php
```

This publishes a sample message to RabbitMQ. The running consumer should pick it up, log it locally, and store it in MySQL.

## Local Mirrored Log

`db_listener.log` is appended to every time the consumer processes a message, including successful inserts and DLQ routings. Sample lines:

```
[2026-06-22 16:01:00] [app-server] [INFO] User tad46 logged in
[2026-06-22 16:01:30] [DLQ] Routed malformed message: {"level":"info","message":"missing fields"}
[2026-06-22 16:02:15] [api-worker] [ERROR] AviationStack returned 500
```

## Notes

- The DB VM never connects to other VMs directly. All inter-VM communication happens through RabbitMQ.
- Both `.env` and `testRabbitMQ.ini` contain credentials and are excluded from version control.
- The local log file is also gitignored to keep the repo clean.
