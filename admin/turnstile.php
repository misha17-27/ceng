<?php
/*
 * Cloudflare Turnstile helpers, shared by the site contact form
 * (contact-handler.php) and the admin login. Keys live in the `settings`
 * table (admin -> Безопасность). If keys are empty, forms work as before.
 */
function _ts_pdo() {
    static $p = null;
    if ($p !== null) return $p ?: null;
    try { $p = require __DIR__ . '/db.php'; } catch (Throwable $e) { $p = false; }
    return $p ?: null;
}
function ts_setting(string $k): string {
    $p = _ts_pdo(); if (!$p) return '';
    try { $s = $p->prepare('SELECT v FROM settings WHERE k=? LIMIT 1'); $s->execute([$k]); $r = $s->fetch(); return $r ? (string)$r['v'] : ''; }
    catch (Throwable $e) { return ''; }
}
function turnstile_site(): string   { return ts_setting('turnstile_site'); }
function turnstile_secret(): string { return ts_setting('turnstile_secret'); }

/* Render the widget (script + box). No-op if no site key configured. */
function turnstile_widget(): void {
    $site = turnstile_site();
    if ($site === '') return;
    echo '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    echo '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($site, ENT_QUOTES) . '" style="margin:10px 0"></div>';
}

/* Verify the token. Returns true if not configured (so forms keep working). */
function turnstile_verify(string $token): bool {
    $secret = turnstile_secret();
    if ($secret === '') return true;
    if ($token === '') return false;
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST', 'timeout' => 10,
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]),
    ]]);
    $r = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if ($r === false) return false;
    $d = json_decode($r, true);
    return !empty($d['success']);
}
