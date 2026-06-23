# App Service

Place application-layer code here.

Use feature branches to isolate work. Keep changes linked to an issue and open a PR for review.

## App VM Centralized Logging

This folder contains the App Server VM setup and logging publisher files for the centralized logging milestone.

## Files

- `app_setup.sh` sets up the App Server VM environment.
- `app_log.php` contains the App VM log publisher function.
- `app_demo.php` runs a test log event through RabbitMQ.
- `composer.json` and `composer.lock` define the PHP RabbitMQ dependency.
- `.env.example` shows the required local RabbitMQ connection settings.

## Local Setup

Install PHP dependencies:

```bash
composer install
