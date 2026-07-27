<?php
// promotion-tool/app/promote.php
// App-role promoter. SHARED engine; the QA->prod stage and the bulk-release path
// are owned by ns87 (Noaman / Person B). The single-file path is used by Person A (Rosmy).
//
// Moves App config/content from one lane to the next over SSH (scp), with a
// pre-change backup, checksum verification, logging, and safe failure.
//
// USAGE
//   Single approved file (Person A, dev -> qa):
//     php app/promote.php --from development --to qa --role app --file otr.conf
//   Bulk release (Person B, qa -> prod):
//     php app/promote.php --from qa --to production --role app --release rel-2026-07-27-01
//   Add --dry-run to preview without changing anything.

require_once __DIR__ . '/../lib/inventory.php';

// ---- small helpers -----------------------------------------------------------
function fail($msg, $ctx = []) {
    fwrite(STDERR, "ERROR: $msg\n");
    log_event(array_merge(['result' => 'failed', 'error' => $msg], $ctx));
    exit(1);
}

function run_or_fail($cmd, $msg, $ctx = []) {
    $out = []; $code = 0;
    exec($cmd . ' 2>&1', $out, $code);
    if ($code !== 0) {
        fail($msg . ' :: ' . implode(' | ', array_slice($out, -3)), $ctx);
    }
    return $out;
}

function parse_args($argv) {
    $a = [];
    for ($i = 1; $i < count($argv); $i++) {
        if (substr($argv[$i], 0, 2) === '--') {
            $key = substr($argv[$i], 2);
            $a[$key] = ($i + 1 < count($argv) && substr($argv[$i + 1], 0, 2) !== '--') ? $argv[++$i] : true;
        }
    }
    return $a;
}

// ---- parse + validate inputs -------------------------------------------------
$args    = parse_args($argv);
$from    = $args['from'] ?? '';
$to      = $args['to'] ?? '';
$role    = $args['role'] ?? 'app';
$release = $args['release'] ?? null;
$file    = $args['file'] ?? null;
$dryRun  = isset($args['dry-run']);

if ($from === '' || $to === '') fail("Missing --from/--to. Example: --from qa --to production --role app --release rel-...");
if ($role !== 'app') fail("This script promotes the 'app' role only (got '$role').");
if (!$release && !$file) fail("Provide --release <id> (bulk) or --file <name> (single target).");

try { assert_promotion_allowed($from, $to); }          // blocks development->production
catch (Exception $e) { fail($e->getMessage(), compact('from', 'to', 'role', 'release')); }

$inv = load_inventory();
try { $target = resolve_target($inv, $to, $role); }
catch (Exception $e) { fail($e->getMessage(), compact('from', 'to', 'role')); }

// ---- build the file list -----------------------------------------------------
$items      = [];
$releaseDir = __DIR__ . '/releases/' . ($release ?? '');

if ($release) {
    // BULK RELEASE (Person B) - the immutable, already-built package.
    $manifestPath = $releaseDir . '/manifest.json';
    if (!is_file($manifestPath)) fail("Release manifest not found: $manifestPath");
    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (!is_array($manifest) || empty($manifest['files'])) fail("Invalid manifest: $manifestPath");
    if (($manifest['role'] ?? '') !== 'app') fail("Manifest role is not 'app'.");

    // SAME-RELEASE guardrail: to reach production the release must already be tested in qa.
    if ($to === 'production' && !release_reached_lane($release, 'qa')) {
        fail("Release '$release' has not been promoted to qa yet. Promote dev->qa and test it first.",
             compact('from', 'to', 'role', 'release'));
    }
    foreach ($manifest['files'] as $f) {
        $local = $releaseDir . '/' . $f['src'];
        if (!is_file($local)) fail("Release file missing: $local");
        $items[] = ['local' => $local, 'dest' => $f['dest']];
    }
} else {
    // SINGLE TARGET (Person A) - one approved file from the staging area.
    $local = __DIR__ . '/staging/' . $file;
    if (!is_file($local)) fail("Approved file not found in staging: $local");
    $items[] = ['local' => $local, 'dest' => rtrim($target['path'], '/') . '/' . basename($file)];
}

// ---- promotion ---------------------------------------------------------------
$ssh       = 'ssh ' . ssh_opts($target) . ' ' . escapeshellarg($target['user'] . '@' . $target['host']);
$backupTag = ($release ?: pathinfo($file, PATHINFO_FILENAME)) . '_' . date('Ymd_His');
$backupDir = "/home/{$target['user']}/promo-backups/{$backupTag}";

echo "== App promotion ==\n";
echo "From: $from   To: $to   Role: $role   " . ($release ? "Release: $release" : "File: $file") . "\n";
echo "Target: {$target['user']}@{$target['host']}:{$target['path']}\n";
echo "Backup on target: $backupDir\n";
if ($dryRun) echo "(dry-run: nothing will be changed)\n";

$checksums = [];
$affected  = [];

foreach ($items as $it) {
    $local = $it['local'];
    $dest  = $it['dest'];
    $sha   = file_sha256($local);
    $checksums[basename($dest)] = $sha;
    $affected[] = $dest;
    echo "\n-> $dest  (sha256 " . substr($sha, 0, 12) . "...)\n";
    if ($dryRun) continue;

    $tmp = '/tmp/promo_' . basename($dest) . '_' . getmypid();

    // 1) copy the new file to a temp spot on the target (scp over SSH)
    $scp = 'scp ' . ssh_opts($target) . ' ' . escapeshellarg($local) . ' ' .
           escapeshellarg($target['user'] . '@' . $target['host'] . ':' . $tmp);
    run_or_fail($scp, "Transfer failed for $dest",
                compact('from', 'to', 'role', 'release') + ['target' => $dest, 'backup' => $backupDir]);

    // 2) on the target: make the release-linked backup, then install the new file.
    //    sudo is used so root-owned paths (/etc, /var/www) work. Fails safely (set -e).
    $remote =
        "set -e; " .
        "sudo mkdir -p " . escapeshellarg($backupDir) . "; " .
        "if [ -f " . escapeshellarg($dest) . " ]; then sudo cp -a " . escapeshellarg($dest) . " " . escapeshellarg($backupDir . '/') . "; fi; " .
        "sudo mkdir -p " . escapeshellarg(dirname($dest)) . "; " .
        "sudo install -m 0644 " . escapeshellarg($tmp) . " " . escapeshellarg($dest) . "; " .
        "rm -f " . escapeshellarg($tmp);
    run_or_fail("$ssh " . escapeshellarg($remote), "Backup/install failed for $dest",
                compact('from', 'to', 'role', 'release') + ['target' => $dest, 'backup' => $backupDir]);

    // 3) verify by comparing checksums on the target
    $remoteSha = trim((string)shell_exec("$ssh " . escapeshellarg("sha256sum " . escapeshellarg($dest) . " | cut -d' ' -f1")));
    if ($remoteSha !== $sha) {
        fail("Checksum mismatch after transfer for $dest (local $sha / remote $remoteSha)",
             compact('from', 'to', 'role', 'release') + ['target' => $dest, 'backup' => $backupDir]);
    }
    echo "   backed up + installed + verified on target.\n";
}

// 5) reload the App service so the new config takes effect
if (!$dryRun && !empty($target['service'])) {
    $reload = "sudo systemctl reload " . escapeshellarg($target['service']) .
              " || sudo systemctl restart " . escapeshellarg($target['service']);
    run_or_fail("$ssh " . escapeshellarg($reload), "Service reload failed ({$target['service']})",
                compact('from', 'to', 'role', 'release') + ['backup' => $backupDir]);
}

// 6) success log with every required field
log_event([
    'from' => $from, 'to' => $to, 'role' => $role,
    'release' => $release, 'file' => $file,
    'targets' => $affected, 'checksums' => $checksums,
    'backup' => $backupDir, 'operator' => get_current_user(),
    'result' => $dryRun ? 'dry-run-ok' : 'success',
]);

echo "\nDONE: " . ($dryRun ? "dry-run OK" : "$from -> $to promotion complete") . "\n";
