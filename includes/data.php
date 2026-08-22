<?php
/*
 * Editable site data from the admin DB, with safe fallbacks.
 *   t($key, $default)  -> returns DB text for $key, or $default (and registers
 *                         the key with $default so the admin can edit it).
 *   site_contacts()    -> phone/email/address/socials array.
 * If the DB is unavailable, everything falls back to the current values.
 */
function _site_pdo(): ?PDO {
    static $p = null;
    if ($p !== null) return $p ?: null;
    $dbf = $_SERVER['DOCUMENT_ROOT'] . '/admin/db.php';
    if (is_file($dbf)) { try { $p = require $dbf; } catch (Throwable $e) { $p = false; } }
    else $p = false;
    return $p ?: null;
}

function t(string $key, string $default = ''): string {
    static $cache = null;
    $pdo = _site_pdo();
    if ($cache === null) {
        $cache = [];
        if ($pdo) { try { $cache = $pdo->query('SELECT k,v FROM texts')->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Throwable $e) {} }
    }
    if (array_key_exists($key, $cache)) return (string)$cache[$key];
    // register the default so it shows up (and becomes editable) in the admin
    if ($pdo) { try { $pdo->prepare('INSERT IGNORE INTO texts (k,v) VALUES (?,?)')->execute([$key, $default]); } catch (Throwable $e) {} }
    $cache[$key] = $default;
    return $default;
}
function te(string $key, string $default = ''): void { echo htmlspecialchars(t($key, $default), ENT_QUOTES, 'UTF-8'); }

function site_contacts(): array {
    static $c = null;
    if ($c !== null) return $c;
    $c = [
        'phone'=>'+994 70 230 06 90', 'phone2'=>'+994 70 8109889',
        'email'=>'info@ceng.az', 'address'=>'Bakı, Əhməd Rəcəbli', 'hours'=>'',
        'soc1_name'=>'','soc1_url'=>'','soc2_name'=>'','soc2_url'=>'',
        'soc3_name'=>'','soc3_url'=>'','soc4_name'=>'','soc4_url'=>'',
    ];
    $pdo = _site_pdo();
    if ($pdo) { try {
        foreach ($pdo->query('SELECT k,v FROM contacts')->fetchAll(PDO::FETCH_KEY_PAIR) as $k=>$v)
            if (trim((string)$v) !== '') $c[$k] = $v;
    } catch (Throwable $e) {} }
    return $c;
}
function tel_href(string $phone): string { return 'tel:' . preg_replace('/[^0-9+]/', '', $phone); }
function esc_html(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
