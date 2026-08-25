<?php
/*
 * One-shot migration to the main domain. Run AFTER deploying the repo into
 * public_html:  https://ceng.az/admin/move-to-main.php?token=...
 *
 * What it does (everything reversible):
 *   1. MOVES WordPress leftovers from public_html into /home/<user>/wp_backup_<ts>/
 *   2. Copies the non-git items from the old yeni.ceng.az folder:
 *      admin/config.php and wp-content/uploads/admin/
 *   3. Writes a 301-redirect .htaccess into the old yeni.ceng.az folder
 *   4. Sanity-checks the result (files + DB connection)
 */
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

$PUB  = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$HOME = dirname($PUB);
$YENI = $HOME . '/yeni.ceng.az';

/* token: local config first, then the old folder's config */
$cfg = [];
foreach ([__DIR__ . '/config.php', $YENI . '/admin/config.php'] as $cf) {
    if (is_file($cf)) { $cfg = require $cf; break; }
}
if (!isset($cfg['install_token']) || ($_GET['token'] ?? '') !== (string)$cfg['install_token']) {
    http_response_code(403); exit("Forbidden. Add ?token=...\n");
}
if (basename($PUB) !== 'public_html') {
    exit("This script must run on the MAIN domain (docroot public_html). Current docroot: $PUB\n");
}
$log = fn($m) => print($m . "\n");
$log("public_html: $PUB");
$log("old folder : $YENI " . (is_dir($YENI) ? '(found)' : '(NOT FOUND)'));
$log('');

/* ---------- 1. move WP leftovers into a backup folder ---------- */
$backup = $HOME . '/wp_backup_' . date('Ymd_His');
$wpItems = [
    'wp-admin', 'wp-login.php', 'wp-config.php', 'wp-config-sample.php', 'wp-settings.php',
    'wp-load.php', 'wp-cron.php', 'xmlrpc.php', 'wp-mail.php', 'wp-signup.php', 'wp-activate.php',
    'wp-links-opml.php', 'wp-trackback.php', 'wp-blog-header.php', 'wp-comments-post.php',
    'license.txt', 'readme.html',
];
$moved = 0;
foreach ($wpItems as $item) {
    $p = "$PUB/$item";
    if (file_exists($p)) {
        if (!is_dir($backup) && !mkdir($backup, 0755, true)) { $log("!! cannot create $backup"); break; }
        if (@rename($p, "$backup/$item")) { $log("moved to backup: $item"); $moved++; }
        else $log("!! could not move: $item");
    }
}
$log($moved ? "✓ WordPress leftovers moved to $backup" : '· no WordPress leftovers found (already clean)');
$log('');

/* ---------- 2. bring non-git items from the old folder ---------- */
function rcopy(string $src, string $dst, callable $log): int {
    $n = 0;
    if (is_file($src)) {
        @mkdir(dirname($dst), 0755, true);
        if (!is_file($dst) && copy($src, $dst)) { $n++; }
        return $n;
    }
    if (!is_dir($src)) return 0;
    @mkdir($dst, 0755, true);
    foreach (scandir($src) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $n += rcopy("$src/$f", "$dst/$f", $log);
    }
    return $n;
}
if (is_dir($YENI)) {
    if (!is_file("$PUB/admin/config.php") && is_file("$YENI/admin/config.php")) {
        copy("$YENI/admin/config.php", "$PUB/admin/config.php");
        $log('✓ admin/config.php copied from the old folder');
    } else {
        $log(is_file("$PUB/admin/config.php") ? '· admin/config.php already in place' : '!! admin/config.php not found anywhere — admin will not reach the DB');
    }
    $n = rcopy("$YENI/wp-content/uploads/admin", "$PUB/wp-content/uploads/admin", $log);
    $log("✓ uploads/admin: $n new file(s) copied");
    $n2 = rcopy("$YENI/wp-content/uploads", "$PUB/wp-content/uploads", $log);
    $log("✓ other uploads merged: $n2 new file(s)");
} else {
    $log('!! old folder missing — skipped config/uploads copy');
}
$log('');

/* ---------- 3. permanent redirect on the old subdomain ---------- */
if (is_dir($YENI)) {
    $redir = "RewriteEngine On\nRewriteCond %{HTTP_HOST} ^yeni\\.ceng\\.az$ [NC]\nRewriteRule ^(.*)$ https://ceng.az/\$1 [R=301,L]\n";
    if (@file_put_contents($YENI . '/.htaccess', $redir) !== false) $log('✓ yeni.ceng.az now 301-redirects to ceng.az');
    else $log('!! could not write redirect .htaccess into the old folder');
}
$log('');

/* ---------- 4. sanity checks ---------- */
$checks = [
    'index.php (new site)'   => is_file("$PUB/index.php") && strpos((string)file_get_contents("$PUB/index.php", false, null, 0, 200), 'includes/data.php') !== false,
    'includes/data.php'      => is_file("$PUB/includes/data.php"),
    'includes/project.php'   => is_file("$PUB/includes/project.php"),
    'admin/config.php'       => is_file("$PUB/admin/config.php"),
    'custom.css'             => is_file("$PUB/custom.css"),
    '.htaccess (new)'        => is_file("$PUB/.htaccess") && strpos((string)file_get_contents("$PUB/.htaccess"), 'project-view.php') !== false,
    'wp-login.php gone'      => !file_exists("$PUB/wp-login.php"),
    'wp-admin gone'          => !is_dir("$PUB/wp-admin"),
];
foreach ($checks as $name => $ok) $log(($ok ? 'OK   ' : 'FAIL ') . $name);
try {
    $pdo = require "$PUB/admin/db.php";
    $c = (int)$pdo->query('SELECT COUNT(*) c FROM projects')->fetch()['c'];
    $log("OK   DB connection (projects: $c)");
} catch (Throwable $e) {
    $log('FAIL DB connection: ' . substr($e->getMessage(), 0, 120));
}
$log("\nDONE. Open https://ceng.az and check the site. The WP backup stays in $backup — delete it later when everything is verified.");
