<?php
// send_test_log.php - Test the logger by sending a log message
// Usage: php send_test_log.php [level] [message]

require_once __DIR__ . '/testlogger.php';

$level   = $argv[1] ?? 'info';
$message = $argv[2] ?? 'Test message from DB VM';

$logger = new Logger('db-server');
$logger->publishLog($level, $message);
$logger->close();