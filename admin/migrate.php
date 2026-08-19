<?php
/*
 * Idempotent DB migration. Run once after a deploy that changes the schema:
 *   /admin/migrate.php?token=YOUR_TOKEN
 * Safe to re-run. DO NOT delete it from the repository (leave it — it is
 * protected by the token). You may delete it only from public_html copy.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['token'] ?? '') !== (string)cfg('install_token')) { http_response_code(403); exit('Forbidden. Add ?token=...'); }
$db = pdo();
$log = fn($m) => print($m . "\n");

function col_exists(PDO $db, string $t, string $c): bool {
    $s = $db->prepare("SELECT COUNT(*) c FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?");
    $s->execute([$t, $c]); return (int)$s->fetch()['c'] > 0;
}
function add_col(PDO $db, callable $log, string $t, string $c, string $d): void {
    if (!col_exists($db, $t, $c)) { $db->exec("ALTER TABLE `$t` ADD COLUMN $c $d"); $log("  + $t.$c"); }
    else $log("  · $t.$c ok");
}

$log('projects columns:');
add_col($db, $log, 'projects', 'video', 'VARCHAR(500) NULL');

/* re-seed covers + SEO if still empty (safe) */
$covers = [
 'bine-stadium'=>'/wp-content/uploads/2025/04/img_22141_slide2.jpg','bakcell-arena'=>'/wp-content/uploads/2025/04/160634_bn12v7ezit.jpg',
 'sr-group-co'=>'/wp-content/uploads/2025/04/srgroup.jpg','wyndham-garden-baku'=>'/wp-content/uploads/2025/04/Screenshot_5.webp',
 'agsu-dairy-factory'=>'/wp-content/uploads/2025/04/0003.jpg','grand-park-plaza'=>'/wp-content/uploads/2025/04/Picture19.jpg',
 'mogan-hotel-baku'=>'/wp-content/uploads/2025/04/getlstd-property-pho.jpg','socar-midstream-office'=>'/wp-content/uploads/2025/04/bp-xezer-centre.jpg',
 'intercontinental-hotel-baku'=>'/wp-content/uploads/2025/04/intercontinental-baku-7096431446-2x1-1.webp','qalaalti-hotel'=>'/wp-content/uploads/2025/04/1563191831.2.jpg',
 'khazar-residence'=>'/wp-content/uploads/2025/04/3893-1724920571cfqF6.jpg','savalan-winers'=>'/wp-content/uploads/2025/04/main_factory_img.webp',
 'skywell-showroom'=>'/wp-content/uploads/2025/04/363004021_17898058946839303_1293916128108342644_n.jpg','the-pool-house'=>'/wp-content/uploads/2025/03/204119242.webp',
];
$uc = $db->prepare("UPDATE projects SET cover=? WHERE slug=? AND (cover IS NULL OR cover='')");
foreach ($covers as $s=>$img) $uc->execute([$img,$s]);
$log('✓ covers ensured');

$log("\nDONE. Re-runnable. Do NOT delete from the repo (token-protected).");
