<?php
// Simple PHP mail handler replacing the WordPress/Elementor Pro form backend.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: /elaqe/'); exit; }

// Cloudflare Turnstile (captcha) check, if configured in admin -> Безопасность.
$tsfile = __DIR__ . '/admin/turnstile.php';
if (is_file($tsfile)) {
    require_once $tsfile;
    if (!turnstile_verify($_POST['cf-turnstile-response'] ?? '')) { header('Location: /elaqe/?captcha=1#contact'); exit; }
}

// Honeypot: the hidden "website" field is invisible to humans; bots fill it.
// Pretend success so the bot learns nothing.
if (trim((string)($_POST['website'] ?? '')) !== '') { header('Location: /elaqe/?sent=1#contact'); exit; }

$f     = isset($_POST['form_fields']) && is_array($_POST['form_fields']) ? $_POST['form_fields'] : array();
$clean = function ($v, int $max) { return mb_substr(str_replace(array("\r", "\n"), ' ', trim((string)$v)), 0, $max); };
$name  = $clean($f['email']         ?? '', 200);   // "Ad" field (original key)
$phone = $clean($f['field_6f7b0a2'] ?? '', 60);
$email = $clean($f['field_ded525d'] ?? '', 190);
$msg   = mb_substr(trim((string)($f['field_e389c4e'] ?? '')), 0, 5000);

// Save the submission to the database (admin panel "Zayavki"), if configured.
$dbfile = __DIR__ . '/admin/db.php';
if (is_file($dbfile)) {
    try {
        $pdo = require $dbfile;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        // Rate limit: max 5 submissions per hour from one IP.
        $rl = $pdo->prepare('SELECT COUNT(*) c FROM submissions WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
        $rl->execute([$ip]);
        if ((int)$rl->fetch()['c'] >= 5) { header('Location: /elaqe/?captcha=1#contact'); exit; }
        $st = $pdo->prepare('INSERT INTO submissions (name, phone, email, message, ip, created_at) VALUES (?,?,?,?,?,NOW())');
        $st->execute([$name, $phone, $email, $msg, $ip]);
    } catch (Throwable $e) { /* ignore, still send mail */ }
}

// Recipient from admin -> SEO ("Почта для заявок"); SMTP when configured, mail() otherwise.
$to = 'info@ceng.az';
$sent = false;
$libfile = __DIR__ . '/admin/lib.php';
if (is_file($libfile)) {
    try {
        require_once $libfile;
        $ne = kv_get('settings', 'notify_email');
        if ($ne !== '' && filter_var($ne, FILTER_VALIDATE_EMAIL)) $to = $ne;
        $plain = "Ad: $name\nTelefon: $phone\nEmail: $email\nMesaj:\n$msg\n";
        if (kv_get('settings', 'smtp_host') !== '') {
            $err = '';
            $sent = smtp_send($to, 'Yeni muraciet - ceng.az', $plain, $err);
        }
    } catch (Throwable $e) { /* fall back to mail() below */ }
}
if (!$sent) {
    $subject = '=?UTF-8?B?' . base64_encode('Yeni muraciet - ceng.az') . '?=';
    $body    = "Ad: $name\nTelefon: $phone\nEmail: $email\nMesaj:\n$msg\n";
    $headers = "From: website@ceng.az\r\n";
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) { $headers .= "Reply-To: $email\r\n"; }
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($to, $subject, $body, $headers);
}

header('Location: /elaqe/?sent=1#contact');
exit;
