<?php
// config.php — Shared configuration for App VM
// IT490 MVP | ns87

define('MQ_HOST',  '127.0.0.1');
define('MQ_PORT',  5672);
define('MQ_USER',  'guest');
define('MQ_PASS',  'guest');
define('MQ_VHOST', '/');

define('QUEUE_AUTH_REQUEST',    'auth_request');
define('QUEUE_AUTH_RESPONSE',   'auth_response');
define('QUEUE_REPORT_REQUEST',  'report_request');
define('QUEUE_REPORT_RESPONSE', 'report_response');
