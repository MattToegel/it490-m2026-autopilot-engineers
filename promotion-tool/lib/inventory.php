<?php
// promotion-tool/lib/inventory.php
// SHARED team helper (drafted by ns87 / Noaman; review by Person A / Rosmy).
// - Loads inventory.json (no secrets there)
// - Resolves lane+role -> full server + path details, so operators never type paths
// - Enforces the promotion guardrails (development->qa, qa->production only)
// - Writes the shared promotion log

function promo_root() {
    return dirname(__DIR__); // the promotion-tool/ folder
}

function load_inventory() {
    $path = promo_root() . '/inventory.json';
    if (!is_file($path)) {
        throw new RuntimeException("inventory.json not found at $path");
    }
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) {
        throw new RuntimeException("inventory.json is not valid JSON");
    }
    return $data;
}

function valid_lanes() { return ['development', 'qa', 'production']; }
function valid_roles() { return ['app', 'db', 'mq', 'api']; }

// GUARDRAIL: only development->qa and qa->production are allowed.
// This blocks an accidental direct development->production promotion.
function assert_promotion_allowed($from, $to) {
    $allowed = [ 'development' => 'qa', 'qa' => 'production' ];
    if (!isset($allowed[$from]) || $allowed[$from] !== $to) {
        throw new RuntimeException(
            "BLOCKED: promotion '$from' -> '$to' is not permitted. " .
            "Allowed: development -> qa, then qa -> production."
        );
    }
}

// Resolve the full server + path details for a lane/role from the inventory.
function resolve_target($inv, $lane, $role) {
    if (!in_array($lane, valid_lanes(), true)) throw new RuntimeException("Unknown lane: $lane");
    if (!in_array($role, valid_roles(), true)) throw new RuntimeException("Unknown role: $role");
    if (!isset($inv['lanes'][$lane][$role])) throw new RuntimeException("No inventory entry for $lane/$role");
    $t = $inv['lanes'][$lane][$role];
    foreach (['host', 'user', 'path'] as $k) {
        if (empty($t[$k])) throw new RuntimeException("Inventory $lane/$role is missing '$k'");
    }
    if (strpos((string)$t['host'], 'TODO-') === 0) {
        throw new RuntimeException("Inventory $lane/$role host is still a placeholder ({$t['host']}). Set the real host first.");
    }
    return $t + ['port' => 22, 'service' => '', 'ssh_key' => ''];
}

// Append one structured line to the shared promotion log.
function log_event(array $record) {
    $dir = promo_root() . '/logs';
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    $record['timestamp'] = date('c'); // ISO-8601 with timezone
    file_put_contents(
        $dir . '/promotion.log',
        json_encode($record, JSON_UNESCAPED_SLASHES) . "\n",
        FILE_APPEND
    );
}

// Confirm a release already reached a lane successfully (used to enforce
// "the SAME QA-tested release moves to production").
function release_reached_lane($releaseId, $lane) {
    $path = promo_root() . '/logs/promotion.log';
    if (!is_file($path)) return false;
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $rec = json_decode($line, true);
        if (is_array($rec)
            && ($rec['release'] ?? '') === $releaseId
            && ($rec['to'] ?? '') === $lane
            && ($rec['result'] ?? '') === 'success') {
            return true;
        }
    }
    return false;
}

function file_sha256($path) { return is_file($path) ? hash_file('sha256', $path) : null; }

function expand_home($p) {
    if ($p !== '' && $p[0] === '~') {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: '';
        return $home . substr($p, 1);
    }
    return $p;
}

// Build the shared ssh/scp option string for a resolved target.
function ssh_opts($t) {
    $key = !empty($t['ssh_key']) ? '-i ' . escapeshellarg(expand_home($t['ssh_key'])) . ' ' : '';
    return $key . '-o StrictHostKeyChecking=accept-new -p ' . intval($t['port']);
}
