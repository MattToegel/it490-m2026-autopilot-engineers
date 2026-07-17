<?php
// db-worker.php — Database worker (runs on ns87-db)
// IT490 MVP | ns87
// Handles: auth (register/login) + reports (US-03: create/get/update/delete)
// This is the ONLY VM that ever touches the database.

require_once __DIR__ . '/db-worker/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// ── Database config ───────────────────────────────────────────────────────────
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'it490_auth');
define('DB_USER', 'it490user');
define('DB_PASS', 'it490pass');

// ── RabbitMQ config ───────────────────────────────────────────────────────────
define('MQ_HOST',  '127.0.0.1');
define('MQ_PORT',  5672);
define('MQ_USER',  'guest');
define('MQ_PASS',  'guest');
define('MQ_VHOST', '/');

// ── Queue names ───────────────────────────────────────────────────────────────
define('QUEUE_AUTH_REQUEST',    'auth_request');
define('QUEUE_AUTH_RESPONSE',   'auth_response');
define('QUEUE_REPORT_REQUEST',  'report_request');
define('QUEUE_REPORT_RESPONSE', 'report_response');

echo "[ns87-db] DB Worker starting...\n";

// ── Connect to MySQL ──────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "[ns87-db] MySQL connected.\n";
} catch (PDOException $e) {
    echo "[ns87-db] MySQL connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// ── Connect to RabbitMQ ───────────────────────────────────────────────────────
try {
    $connection = new AMQPStreamConnection(MQ_HOST, MQ_PORT, MQ_USER, MQ_PASS, MQ_VHOST);
    $channel    = $connection->channel();
    $channel->queue_declare(QUEUE_AUTH_REQUEST,    false, true, false, false);
    $channel->queue_declare(QUEUE_AUTH_RESPONSE,   false, true, false, false);
    $channel->queue_declare(QUEUE_REPORT_REQUEST,  false, true, false, false);
    $channel->queue_declare(QUEUE_REPORT_RESPONSE, false, true, false, false);
    echo "[ns87-db] RabbitMQ connected. Queues ready.\n";
} catch (Exception $e) {
    echo "[ns87-db] RabbitMQ connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// ═════════════════════════════════════════════════════════════════════════════
// AUTH HANDLER — register and login (original code, login now returns user_id)
// ═════════════════════════════════════════════════════════════════════════════
$authCallback = function (AMQPMessage $msg) use ($channel, $pdo) {
    $data   = json_decode($msg->body, true);
    $action = $data['action'] ?? '';
    $email  = $data['email']  ?? '';
    $corrId = $data['correlation_id'] ?? '';

    echo "[ns87-db] AUTH action: $action for: $email\n";
    $response = ['status' => 'error', 'correlation_id' => $corrId];

    if ($action === 'register') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $response['status'] = 'duplicate';
            error_log("[ns87-db] Duplicate registration: $email");
        } else {
            $hash   = password_hash($data['password'], PASSWORD_BCRYPT);
            $insert = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
            $insert->execute([$email, $hash]);
            $response['status'] = 'success';
            echo "[ns87-db] Registered: $email\n";
        }

    } elseif ($action === 'login') {
        $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            error_log("[ns87-db] Login fail - not found: $email");
            $response['status'] = 'fail';
            $response['reason'] = 'email_not_found';
        } elseif (!password_verify($data['password'], $row['password_hash'])) {
            error_log("[ns87-db] Login fail - wrong password: $email");
            $response['status'] = 'fail';
            $response['reason'] = 'wrong_password';
        } else {
            $response['status']  = 'success';
            $response['user_id'] = $row['id'];
            $response['email']   = $email;
            echo "[ns87-db] Login OK: $email (id={$row['id']})\n";
        }
    } else {
        error_log("[ns87-db] Unknown auth action: $action");
    }

    $reply = new AMQPMessage(
        json_encode($response),
        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, 'correlation_id' => $corrId]
    );
    $channel->basic_publish($reply, '', QUEUE_AUTH_RESPONSE);
    $channel->basic_ack($msg->getDeliveryTag());
    echo "[ns87-db] Auth response sent: " . $response['status'] . "\n";
};

// ═════════════════════════════════════════════════════════════════════════════
// REPORT HANDLER — US-03 (view / create / edit / delete reports)
// ═════════════════════════════════════════════════════════════════════════════
$reportCallback = function (AMQPMessage $msg) use ($channel, $pdo) {
    $data     = json_decode($msg->body, true);
    $action   = $data['action']   ?? '';
    $userId   = $data['user_id']  ?? null;
    $corrId   = $data['correlation_id'] ?? '';

    echo "[ns87-db] REPORT action: $action by user_id: $userId\n";
    $response = ['status' => 'error', 'correlation_id' => $corrId];

    // ── AC1: Get all recent reports ───────────────────────────────────────────
    if ($action === 'report.get_all') {
        $stmt = $pdo->prepare(
            'SELECT r.id, r.user_id, r.category, r.content, r.location,
                    r.created_at, r.updated_at, u.email AS author_email
             FROM reports r
             JOIN users u ON r.user_id = u.id
             ORDER BY r.created_at DESC
             LIMIT 50'
        );
        $stmt->execute();
        $response['status']  = 'success';
        $response['reports'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "[ns87-db] Fetched " . count($response['reports']) . " reports.\n";

    // ── AC2: Create a new report ──────────────────────────────────────────────
    } elseif ($action === 'report.create') {
        $category = trim($data['category'] ?? '');
        $content  = trim($data['content']  ?? '');
        $location = trim($data['location'] ?? 'Newark Liberty International Airport');

        if (empty($category) || empty($content) || empty($userId)) {
            $response['message'] = 'Category, content, and user_id are required.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO reports (user_id, category, content, location) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$userId, $category, $content, $location]);
            $response['status']    = 'success';
            $response['report_id'] = $pdo->lastInsertId();
            echo "[ns87-db] Report created: id={$response['report_id']} by user=$userId\n";
        }

    // ── AC3: Update a report — owner only ────────────────────────────────────
    } elseif ($action === 'report.update') {
        $reportId = $data['report_id'] ?? null;
        $category = trim($data['category'] ?? '');
        $content  = trim($data['content']  ?? '');

        $check = $pdo->prepare('SELECT user_id FROM reports WHERE id = ?');
        $check->execute([$reportId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $response['message'] = 'Report not found.';
        } elseif ((int)$row['user_id'] !== (int)$userId) {
            $response['status']  = 'forbidden';
            $response['message'] = 'You can only edit your own reports.';
            error_log("[ns87-db] Forbidden edit: user=$userId on report=$reportId");
        } elseif (empty($category) || empty($content)) {
            $response['message'] = 'Category and content cannot be empty.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE reports SET category = ?, content = ? WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([$category, $content, $reportId, $userId]);
            $response['status'] = 'success';
            echo "[ns87-db] Report updated: id=$reportId by user=$userId\n";
        }

    // ── AC4: Delete a report — owner only ────────────────────────────────────
    } elseif ($action === 'report.delete') {
        $reportId = $data['report_id'] ?? null;

        $check = $pdo->prepare('SELECT user_id FROM reports WHERE id = ?');
        $check->execute([$reportId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $response['message'] = 'Report not found.';
        } elseif ((int)$row['user_id'] !== (int)$userId) {
            $response['status']  = 'forbidden';
            $response['message'] = 'You can only delete your own reports.';
            error_log("[ns87-db] Forbidden delete: user=$userId on report=$reportId");
        } else {
            $stmt = $pdo->prepare('DELETE FROM reports WHERE id = ? AND user_id = ?');
            $stmt->execute([$reportId, $userId]);
            $response['status'] = 'success';
            echo "[ns87-db] Report deleted: id=$reportId by user=$userId\n";
        }

    // ── Get one report (for pre-filling the edit form) ────────────────────────
    } elseif ($action === 'report.get_one') {
        $reportId = $data['report_id'] ?? null;
        $stmt = $pdo->prepare(
            'SELECT r.id, r.user_id, r.category, r.content, r.location,
                    r.created_at, r.updated_at, u.email AS author_email
             FROM reports r
             JOIN users u ON r.user_id = u.id
             WHERE r.id = ?'
        );
        $stmt->execute([$reportId]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($report) {
            $response['status'] = 'success';
            $response['report'] = $report;
        } else {
            $response['message'] = 'Report not found.';
        }

    } else {
        error_log("[ns87-db] Unknown report action: $action");
        $response['message'] = 'Unknown action.';
    }

    $reply = new AMQPMessage(
        json_encode($response),
        ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, 'correlation_id' => $corrId]
    );
    $channel->basic_publish($reply, '', QUEUE_REPORT_RESPONSE);
    $channel->basic_ack($msg->getDeliveryTag());
    echo "[ns87-db] Report response sent: " . $response['status'] . "\n";
};

// ═════════════════════════════════════════════════════════════════════════════
// START LISTENING on both queues
// ═════════════════════════════════════════════════════════════════════════════
$channel->basic_qos(null, 1, null);
$channel->basic_consume(QUEUE_AUTH_REQUEST,   '', false, false, false, false, $authCallback);
$channel->basic_consume(QUEUE_REPORT_REQUEST, '', false, false, false, false, $reportCallback);

echo "[ns87-db] Worker ready. Listening on: auth_request + report_request\n";

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
