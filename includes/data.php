<?php
/*
 * Loads editable site data (contacts + socials) from the admin DB, with
 * fallbacks to the current values. Used by header.php / footer.php so the
 * admin "Контакты и соцсети" section drives what the site shows.
 */
function site_contacts(): array {
    static $c = null;
    if ($c !== null) return $c;
    $c = [
        'phone'   => '+994 70 230 06 90',
        'phone2'  => '+994 70 8109889',
        'email'   => 'info@ceng.az',
        'address' => 'Bakı, Əhməd Rəcəbli',
        'hours'   => '',
        'soc1_name'=>'','soc1_url'=>'','soc2_name'=>'','soc2_url'=>'',
        'soc3_name'=>'','soc3_url'=>'','soc4_name'=>'','soc4_url'=>'',
    ];
    $dbf = $_SERVER['DOCUMENT_ROOT'] . '/admin/db.php';
    if (is_file($dbf)) {
        try {
            $pdo  = require $dbf;
            $rows = $pdo->query('SELECT k,v FROM contacts')->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($rows as $k => $v) { if (trim((string)$v) !== '') $c[$k] = $v; }
        } catch (Throwable $e) { /* keep fallbacks */ }
    }
    return $c;
}
function tel_href(string $phone): string { return 'tel:' . preg_replace('/[^0-9+]/', '', $phone); }
function esc_html(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
