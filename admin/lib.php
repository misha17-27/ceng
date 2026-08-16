<?php
/* Shared admin helpers: PDO, auth, CSRF, layout. */
declare(strict_types=1);

function pdo(): PDO {
    static $p = null;
    if (!$p) { $p = require __DIR__ . '/db.php'; }
    return $p;
}
function cfg(string $k, $default = null) {
    static $c = null;
    if ($c === null) { $c = is_file(__DIR__.'/config.php') ? require __DIR__.'/config.php' : []; }
    return $c[$k] ?? $default;
}
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect(string $to): void { header('Location: ' . $to); exit; }

function boot_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax',
            'secure'=>(($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')]);
        session_start();
    }
}
function current_admin(): ?array { boot_session(); return $_SESSION['admin'] ?? null; }
function require_login(): array {
    $a = current_admin();
    if (!$a) redirect('index.php?section=login');
    return $a;
}
function attempt_login(string $email, string $pass): bool {
    $st = pdo()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
    $st->execute([trim($email)]);
    $u = $st->fetch();
    if ($u && (int)($u['active'] ?? 1) === 1 && password_verify($pass, $u['pass_hash'])) {
        boot_session();
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id'=>(int)$u['id'], 'email'=>$u['email'],
                              'name'=>$u['name'] ?? '', 'role'=>$u['role'] ?? 'admin'];
        try { pdo()->prepare('UPDATE admins SET last_login=NOW() WHERE id=?')->execute([(int)$u['id']]); } catch (Throwable $e) {}
        return true;
    }
    return false;
}
function is_admin(): bool { $a = current_admin(); return ($a['role'] ?? 'admin') === 'admin'; }
function require_admin(): void { require_login(); if (!is_admin()) { flash('Недостаточно прав (нужна роль Администратор).','err'); redirect('index.php?section=overview'); } }
function logout(): void { boot_session(); $_SESSION = []; session_destroy(); }

/* CSRF */
function csrf_token(): string {
    boot_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function csrf_check(): void {
    boot_session();
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) {
            http_response_code(400); exit('Bad CSRF token. Reload the page.');
        }
    }
}
/* Flash */
function flash(string $msg, string $type='ok'): void { boot_session(); $_SESSION['flash'][] = [$type,$msg]; }
function flash_render(): string {
    boot_session(); $out='';
    foreach ($_SESSION['flash'] ?? [] as [$t,$m]) {
        $bg = $t==='err' ? '#b91c1c' : '#011640';
        $out .= '<div style="background:'.$bg.';color:#fff;padding:11px 16px;border-radius:8px;margin-bottom:14px;font-weight:600">'.e($m).'</div>';
    }
    $_SESSION['flash'] = [];
    return $out;
}

/* small settings/text/contact key-value helpers */
function kv_get(string $table, string $key, $default=''): string {
    $st = pdo()->prepare("SELECT v FROM `$table` WHERE k = ? LIMIT 1");
    $st->execute([$key]); $r = $st->fetch();
    return $r ? (string)$r['v'] : (string)$default;
}
function kv_set(string $table, string $key, string $val): void {
    $st = pdo()->prepare("INSERT INTO `$table` (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v = VALUES(v)");
    $st->execute([$key,$val]);
}

/* Minimal SMTP sender (STARTTLS/SSL). Returns true on success, sets &$err on failure.
   Falls back is handled by caller (use mail() when smtp_host empty). */
function smtp_send(string $to, string $subject, string $body, string &$err = ''): bool {
    $host = kv_get('settings','smtp_host');
    if ($host === '') { $err = 'SMTP-сервер не заполнен'; return false; }
    $port   = (int)(kv_get('settings','smtp_port') ?: 587);
    $user   = kv_get('settings','smtp_user');
    $pass   = kv_get('settings','smtp_pass');
    $secure = kv_get('settings','smtp_secure') ?: 'tls';
    $from   = kv_get('settings','smtp_from') ?: $user;
    $fname  = kv_get('settings','smtp_from_name') ?: 'Ceng.az';
    $target = $secure === 'ssl' ? "ssl://$host" : $host;
    $fp = @fsockopen($target, $port, $eno, $estr, 15);
    if (!$fp) { $err = "Подключение не удалось: $estr ($eno)"; return false; }
    stream_set_timeout($fp, 15);
    $get = function() use ($fp) { $d=''; while (($l=fgets($fp,515))!==false) { $d.=$l; if (strlen($l)<4 || $l[3]===' ') break; } return $d; };
    $cmd = function($c) use ($fp,$get) { fwrite($fp, $c."\r\n"); return $get(); };
    $ok  = fn($r,$code)=> str_starts_with(trim($r), $code);
    $get();
    $cmd("EHLO ceng.az");
    if ($secure === 'tls') {
        if (!$ok($cmd("STARTTLS"),'220') || !stream_socket_enable_crypto($fp,true,STREAM_CRYPTO_METHOD_TLS_CLIENT)) { $err='STARTTLS не удалось'; fclose($fp); return false; }
        $cmd("EHLO ceng.az");
    }
    if ($user !== '') {
        $cmd("AUTH LOGIN"); $cmd(base64_encode($user));
        if (!$ok($cmd(base64_encode($pass)),'235')) { $err='Авторизация SMTP не прошла (проверь логин/пароль)'; fclose($fp); return false; }
    }
    $cmd("MAIL FROM:<$from>");
    $cmd("RCPT TO:<$to>");
    if (!$ok($cmd("DATA"),'354')) { $err='Сервер не принял DATA'; fclose($fp); return false; }
    $hdr = "From: =?UTF-8?B?".base64_encode($fname)."?= <$from>\r\nTo: <$to>\r\n"
         . "Subject: =?UTF-8?B?".base64_encode($subject)."?=\r\nMIME-Version: 1.0\r\n"
         . "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
    $r = $cmd($hdr . str_replace("\r\n.", "\r\n..", $body) . "\r\n.");
    $cmd("QUIT"); fclose($fp);
    if (!$ok($r,'250')) { $err='Письмо не отправлено: '.trim($r); return false; }
    return true;
}

const SECTIONS = [
    'overview'    => ['Обзор', '▤'],
    'texts'       => ['Тексты сайта', '¶'],
    'pages'       => ['Страницы', '❐'],
    'services'    => ['Услуги', '✦'],
    'projects'    => ['Проекты', '◧'],
    'partners'    => ['Партнёры', '⬡'],
    'images'      => ['Изображения', '❏'],
    'contacts'    => ['Контакты и соцсети', '☏'],
    'submissions' => ['Заявки с сайта', '✉'],
    'seo'         => ['SEO', '☌'],
    'smtp'        => ['Почта (SMTP)', '✉'],
    'security'    => ['Безопасность', '⚿'],
    'users'       => ['Пользователи', '☺'],
    'profile'     => ['Мой профиль', '☺'],
];
const GROUPS = [
    'ОСНОВНОЕ'  => ['overview'],
    'КОНТЕНТ'   => ['texts','pages','services','projects','partners','images','contacts','submissions'],
    'НАСТРОЙКИ' => ['seo','smtp','security','users','profile'],
];
const ADMIN_ONLY = ['users','smtp','security'];

function layout_top(string $active, string $title): void {
    $admin = current_admin();
    echo '<!doctype html><html lang="ru"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1"><title>'.e($title).' — CENG admin</title>';
    echo '<style>'.admin_css().'</style></head><body><div class="wrap">';
    echo '<aside class="side"><div class="brand"><img src="/admin/logo.png" alt="CENG" style="max-height:38px;max-width:180px;filter:brightness(0) invert(1)"></div><nav>';
    foreach (GROUPS as $glabel => $keys) {
        echo '<div class="navgroup">'.e($glabel).'</div>';
        foreach ($keys as $key) {
            if (in_array($key, ADMIN_ONLY, true) && !is_admin()) continue;
            [$label,$ic] = SECTIONS[$key];
            $cls = $key === $active ? ' class="on"' : '';
            echo '<a'.$cls.' href="index.php?section='.$key.'"><i>'.$ic.'</i>'.e($label).'</a>';
        }
    }
    echo '</nav><div class="who">Вы вошли как<br><b>'.e($admin['name'] ?: ($admin['email'] ?? '')).'</b></div></aside>';
    echo '<main><header class="bar"><h1>'.e($title).'</h1><div>';
    echo '<a class="btn ghost" href="/" target="_blank">Открыть сайт</a> ';
    echo '<a class="btn" href="index.php?section=logout">Выйти</a></div></header><div class="body">';
    echo flash_render();
}
function layout_bottom(): void { echo '</div></main></div></body></html>'; }

function admin_css(): string {
    return <<<CSS
*{box-sizing:border-box}body{margin:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f4f6f6;color:#12211d}
a{text-decoration:none}.wrap{display:flex;min-height:100vh}
.side{width:250px;background:#011640;color:#cfe3dd;display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
.brand{display:flex;gap:10px;align-items:center;padding:20px 18px;font-size:12px;line-height:1.15;color:#fff;border-bottom:1px solid #0c2450}
.brand .logo{background:#1b4b8f;color:#fff;width:34px;height:34px;border-radius:8px;display:grid;place-items:center;font-weight:800}
.side nav{display:flex;flex-direction:column;padding:6px 0 14px;flex:1;overflow:auto}
.navgroup{color:#6f83a3;font-size:11px;letter-spacing:.08em;padding:15px 20px 5px;font-weight:700}
.side nav a{color:#b9c6dd;padding:12px 20px;font-size:14px;display:flex;gap:12px;align-items:center}
.side nav a i{width:18px;font-style:normal;opacity:.8}
.side nav a:hover{background:#0b2a54;color:#fff}
.side nav a.on{background:#1b4b8f;color:#fff;font-weight:700}
.who{padding:16px 20px;font-size:12px;color:#8296b5;border-top:1px solid #0c2450}
main{flex:1;min-width:0}
.bar{display:flex;justify-content:space-between;align-items:center;padding:18px 28px;background:#fff;border-bottom:1px solid #e3e8e7;position:sticky;top:0;z-index:5}
.bar h1{margin:0;font-size:22px}
.body{padding:26px 28px;max-width:1100px}
.btn{display:inline-block;background:#011640;color:#fff;border:0;padding:10px 18px;border-radius:24px;font-weight:700;cursor:pointer;font-size:14px}
.btn:hover{background:#0a2a5c}.btn.ghost{background:transparent;color:#011640;border:1.5px solid #011640}
.btn.sm{padding:6px 12px;font-size:13px;border-radius:8px}.btn.red{background:#b91c1c}.btn.red:hover{background:#991717}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:22px}
.card{background:#fff;border:1px solid #e6ebea;border-radius:14px;padding:20px 22px}
.card .n{font-size:34px;font-weight:800;color:#011640}.card .l{color:#5b6f6a;font-size:13px;text-transform:uppercase;letter-spacing:.03em}
.panel{background:#fff;border:1px solid #e6ebea;border-radius:14px;padding:22px;margin-bottom:20px}
table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:11px 12px;border-bottom:1px solid #eef2f1;font-size:14px;vertical-align:top}
th{color:#5b6f6a;font-size:12px;text-transform:uppercase;letter-spacing:.03em}
label{display:block;font-weight:600;margin:14px 0 6px;font-size:14px}
input[type=text],input[type=email],input[type=password],input[type=number],textarea,select{width:100%;padding:11px 13px;border:1px solid #cfd8d6;border-radius:9px;font-size:14px;font-family:inherit}
textarea{min-height:90px}.row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.muted{color:#5b6f6a;font-size:13px}.right{text-align:right}
.login{min-height:100vh;display:grid;place-items:center;background:#011640}
.login .box{background:#fff;padding:38px 34px;border-radius:18px;width:360px;max-width:92vw;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.login h2{margin:6px 0 2px}.login .sub{color:#5b6f6a;margin-bottom:18px}
@media(max-width:820px){.side{width:64px}.side .brand b,.side nav a span,.who{display:none}.side nav a{justify-content:center;padding:14px 0}.row{grid-template-columns:1fr}}
CSS;
}
