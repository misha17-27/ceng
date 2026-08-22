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

// SEO title + description per project (Azerbaijani, tailored to CENG services); only fills empties
$seo = [
 'bine-stadium' => ['Bine Stadium — mühəndislik və tikinti həlləri | CENG', 'Bine Stadium layihəsində CENG layihələndirmə, mühəndislik sistemləri və tikinti işlərini yüksək keyfiyyətlə həyata keçirib.'],
 'bakcell-arena' => ['Bakcell Arena — tikinti və mühəndislik | CENG', 'Bakcell Arena obyektində CENG layihələndirmə, avadanlıq təchizatı və tikinti işlərini peşəkar səviyyədə icra edib.'],
 'sr-group-co' => ['SR Group CO — mühəndislik həlləri | CENG', 'SR Group CO layihəsi üzrə CENG mühəndislik, layihələndirmə və tikinti xidmətlərini kompleks şəkildə təqdim edib.'],
 'wyndham-garden-baku' => ['Wyndham Garden Baku — otel tikintisi | CENG', 'Wyndham Garden Baku otelində CENG layihələndirmə, mühəndislik sistemləri və tikinti işlərini yüksək standartlarla yerinə yetirib.'],
 'agsu-dairy-factory' => ['Ağsu Süd Zavodu — sənaye tikintisi | CENG', 'Ağsu süd zavodu layihəsində CENG sənaye avadanlığının təchizatı, mühəndislik və tikinti işlərini icra edib.'],
 'grand-park-plaza' => ['Grand Park Plaza — tikinti və mühəndislik | CENG', 'Grand Park Plaza obyektində CENG layihələndirmə, mühəndislik sistemləri və tikinti xidmətlərini təqdim edib.'],
 'mogan-hotel-baku' => ['Mogan Hotel Baku — otel layihəsi | CENG', 'Mogan Hotel Baku layihəsində CENG layihələndirmə, avadanlıq təchizatı və tikinti işlərini peşəkarlıqla həyata keçirib.'],
 'socar-midstream-office' => ['SOCAR Midstream Office — mühəndislik | CENG', 'SOCAR Midstream ofisi üzrə CENG mühəndislik sistemləri, layihələndirmə və tikinti işlərini yüksək keyfiyyətlə icra edib.'],
 'intercontinental-hotel-baku' => ['InterContinental Baku — otel tikintisi | CENG', 'InterContinental Hotel Baku layihəsində CENG layihələndirmə, mühəndislik və tikinti həllərini kompleks təqdim edib.'],
 'qalaalti-hotel' => ['Qalaaltı Hotel — tikinti və mühəndislik | CENG', 'Qalaaltı Hotel layihəsində CENG layihələndirmə, avadanlıq təchizatı və tikinti işlərini peşəkar səviyyədə yerinə yetirib.'],
 'khazar-residence' => ['Khazar Residence — yaşayış kompleksi | CENG', 'Khazar Residence layihəsində CENG mühəndislik sistemləri, layihələndirmə və tikinti işlərini yüksək keyfiyyətlə icra edib.'],
 'savalan-winers' => ['Savalan Winery — sənaye mühəndisliyi | CENG', 'Savalan şərab zavodu layihəsində CENG avadanlıq təchizatı, mühəndislik və tikinti işlərini həyata keçirib.'],
 'skywell-showroom' => ['Skywell Showroom — tikinti həlləri | CENG', 'Skywell showroom layihəsində CENG layihələndirmə, mühəndislik və tikinti xidmətlərini peşəkarlıqla təqdim edib.'],
 'the-pool-house' => ['The Pool House — mühəndislik və tikinti | CENG', 'The Pool House layihəsində CENG layihələndirmə, mühəndislik sistemləri və tikinti işlərini yüksək standartlarla icra edib.'],
];
$us = $db->prepare("UPDATE projects SET seo_title=?, seo_desc=? WHERE slug=? AND (seo_title IS NULL OR seo_title='')");
foreach ($seo as $slug=>$v) $us->execute([$v[0],$v[1],$slug]);
$log('✓ project SEO ensured (14)');

$log("\nDONE. Re-runnable. Do NOT delete from the repo (token-protected).");
