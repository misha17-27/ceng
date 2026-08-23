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
    if (array_key_exists($key, $cache)) {
        $v = (string)$cache[$key];
        return ($v === '' && $default !== '') ? $default : $v;   // empty DB value falls back to default
    }
    // register the default so it shows up (and becomes editable) in the admin
    if ($default !== '' && $pdo) { try { $pdo->prepare('INSERT IGNORE INTO texts (k,v) VALUES (?,?)')->execute([$key, $default]); } catch (Throwable $e) {} }
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

/* ---- projects for grids (home + layiheler) ---- */
function site_projects(int $limit, int $offset = 0): array {
    $pdo = _site_pdo();
    if ($pdo) { try {
        $st = $pdo->prepare("SELECT slug,title,cover FROM projects
            WHERE visible=1 AND (status IS NULL OR status='' OR status='published')
            ORDER BY sort,id LIMIT " . (int)$limit . " OFFSET " . (int)$offset);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) return $rows;
    } catch (Throwable $e) {} }
    return [];
}
function site_projects_count(): int {
    $pdo = _site_pdo();
    if ($pdo) { try {
        return (int)$pdo->query("SELECT COUNT(*) c FROM projects
            WHERE visible=1 AND (status IS NULL OR status='' OR status='published')")->fetch()['c'];
    } catch (Throwable $e) {} }
    return 0;
}

/* ---- partners for the homepage carousel (DB, falls back to the original 16 logos) ---- */
function site_partners(): array {
    $pdo = _site_pdo();
    if ($pdo) { try {
        $rows = $pdo->query("SELECT name,image,url FROM partners
            WHERE (visible IS NULL OR visible=1) AND image<>'' ORDER BY sort,id")->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) return $rows;
    } catch (Throwable $e) {} }
    $fallback = ['/wp-content/uploads/2025/03/Picture18.png','/wp-content/uploads/2025/03/Screenshot_6.png',
        '/wp-content/uploads/2025/03/Picture9.jpg.webp','/wp-content/uploads/2025/03/Picture4.png.webp',
        '/wp-content/uploads/2025/03/Picture5.jpg','/wp-content/uploads/2025/03/Picture6.png.webp',
        '/wp-content/uploads/2025/03/Picture7.png','/wp-content/uploads/2025/03/Picture8.png.webp',
        '/wp-content/uploads/2025/03/Picture10.png.webp','/wp-content/uploads/2025/03/Picture11.png',
        '/wp-content/uploads/2025/03/Picture12.png','/wp-content/uploads/2025/03/Picture13.png',
        '/wp-content/uploads/2025/03/Picture14.png','/wp-content/uploads/2025/03/Picture15.png.webp',
        '/wp-content/uploads/2025/03/Picture16.png','/wp-content/uploads/2025/03/Picture17.png'];
    return array_map(fn($u) => ['name' => '', 'image' => $u, 'url' => ''], $fallback);
}

/* Search-engine visibility switch (admin -> SEO). When closed, every page
   sends X-Robots-Tag so the whole site stays out of the index. */
function apply_search_visibility(): void {
    $pdo = _site_pdo();
    if (!$pdo || headers_sent()) return;
    try {
        $s = $pdo->prepare("SELECT v FROM settings WHERE k='search_visible' LIMIT 1");
        $s->execute(); $r = $s->fetch();
        if ($r && (string)$r['v'] === '0') header('X-Robots-Tag: noindex, nofollow');
    } catch (Throwable $e) {}
}
apply_search_visibility();

/* Per-page SEO (title/description) from the seo_pages table, with fallbacks. */
function page_seo(string $slug, string $defTitle, string $defDescr = ''): array {
    $out = ['title' => $defTitle, 'descr' => $defDescr];
    $pdo = _site_pdo();
    if ($pdo) { try {
        $st = $pdo->prepare('SELECT title, descr FROM seo_pages WHERE slug = ? LIMIT 1');
        $st->execute([$slug]);
        if ($r = $st->fetch()) {
            if (trim((string)$r['title']) !== '') $out['title'] = (string)$r['title'];
            if (trim((string)$r['descr']) !== '') $out['descr'] = (string)$r['descr'];
        }
    } catch (Throwable $e) {} }
    return $out;
}
