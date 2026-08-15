<?php
/*
 * Schema upgrade for the rich Projects module (categories, gallery, content,
 * custom fields, per-project SEO). Run once:  /admin/migrate.php?token=YOUR_TOKEN
 * Safe to re-run. DELETE afterwards.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';
header('Content-Type: text/plain; charset=utf-8');
if (($_GET['token'] ?? '') !== (string)cfg('install_token')) { http_response_code(403); exit('Forbidden.'); }
$db = pdo();
$log = fn($m) => print($m . "\n");

function col_exists(PDO $db, string $table, string $col): bool {
    $st = $db->prepare("SELECT COUNT(*) c FROM information_schema.columns
        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $st->execute([$table,$col]); return (int)$st->fetch()['c'] > 0;
}
function add_col(PDO $db, callable $log, string $table, string $col, string $def): void {
    if (!col_exists($db,$table,$col)) { $db->exec("ALTER TABLE `$table` ADD COLUMN $col $def"); $log("  + $table.$col"); }
    else $log("  · $table.$col exists");
}

$db->exec("CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120), sort INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$ins = $db->prepare("INSERT INTO categories (name,sort) SELECT ?,? FROM DUAL
  WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = ?)");
foreach (['Yaşayış'=>0,'İnteryer'=>1,'Kommersiya'=>2,'İnfrastruktur'=>3] as $n=>$s) $ins->execute([$n,$s,$n]);
$log('✓ categories ready');

$log('projects columns:');
add_col($db,$log,'projects','category_id','INT NULL');
add_col($db,$log,'projects','year','VARCHAR(20) NULL');
add_col($db,$log,'projects','location','VARCHAR(255) NULL');
add_col($db,$log,'projects','area','VARCHAR(50) NULL');
add_col($db,$log,'projects','client','VARCHAR(255) NULL');
add_col($db,$log,'projects','cover','VARCHAR(255) NULL');
add_col($db,$log,'projects','gallery','LONGTEXT NULL');
add_col($db,$log,'projects','content','LONGTEXT NULL');
add_col($db,$log,'projects','scope','TEXT NULL');
add_col($db,$log,'projects','seo_title','VARCHAR(255) NULL');
add_col($db,$log,'projects','seo_desc','TEXT NULL');
add_col($db,$log,'projects','robots',"VARCHAR(40) DEFAULT 'index,follow'");
add_col($db,$log,'projects','canonical','VARCHAR(255) NULL');
add_col($db,$log,'projects','status',"VARCHAR(20) DEFAULT 'published'");

$log('services columns:');
add_col($db,$log,'services','slug','VARCHAR(160) NULL');
add_col($db,$log,'services','code','VARCHAR(20) NULL');
add_col($db,$log,'services','short','TEXT NULL');
add_col($db,$log,'services','points','TEXT NULL');

$log('pages columns:');
add_col($db,$log,'pages','hero_title','TEXT NULL');
add_col($db,$log,'pages','blue_short','TEXT NULL');
add_col($db,$log,'pages','blue_text','TEXT NULL');
add_col($db,$log,'seo_pages','robots',"VARCHAR(40) DEFAULT 'index,follow'");
add_col($db,$log,'seo_pages','canonical','VARCHAR(255) NULL');

$ic = $db->prepare("INSERT IGNORE INTO contacts (k,v) VALUES (?,?)");
foreach (['hours'=>'B.e — Cümə, 09:00 – 18:00','map'=>'',
          'soc1_name'=>'Facebook','soc1_url'=>'#','soc2_name'=>'Instagram','soc2_url'=>'#',
          'soc3_name'=>'LinkedIn','soc3_url'=>'#','soc4_name'=>'','soc4_url'=>''] as $k=>$v) $ic->execute([$k,$v]);
$it = $db->prepare("INSERT IGNORE INTO texts (k,v) VALUES (?,?)");
foreach (['site_short'=>'CENG','site_full'=>'Caspian Engineering Group',
          'site_slogan'=>'Keyfiyyətli tikinti və mühəndislik həlləri','blue_short'=>'','blue_text'=>''] as $k=>$v) $it->execute([$k,$v]);
$log('✓ services/pages/seo columns + contacts/texts seeds');

$log('admins columns (users/roles):');
add_col($db,$log,'admins','name','VARCHAR(120) NULL');
add_col($db,$log,'admins','role',"VARCHAR(20) DEFAULT 'admin'");
add_col($db,$log,'admins','active','TINYINT DEFAULT 1');
add_col($db,$log,'admins','last_login','DATETIME NULL');
$db->exec("UPDATE admins SET role='admin' WHERE role IS NULL OR role=''");
$db->exec("UPDATE admins SET active=1 WHERE active IS NULL");

$is = $db->prepare("INSERT IGNORE INTO settings (k,v) VALUES (?,?)");
foreach (['smtp_host'=>'','smtp_port'=>'587','smtp_user'=>'','smtp_pass'=>'',
          'smtp_from'=>'info@ceng.az','smtp_from_name'=>'Ceng.az','smtp_secure'=>'tls',
          'turnstile_site'=>'','turnstile_secret'=>''] as $k=>$v) $is->execute([$k,$v]);
$log('✓ admins roles + SMTP + Turnstile settings');

$covers = [
    'bine-stadium' => '/wp-content/uploads/2025/04/img_22141_slide2.jpg',
    'bakcell-arena' => '/wp-content/uploads/2025/04/160634_bn12v7ezit.jpg',
    'sr-group-co' => '/wp-content/uploads/2025/04/srgroup.jpg',
    'wyndham-garden-baku' => '/wp-content/uploads/2025/04/Screenshot_5.webp',
    'agsu-dairy-factory' => '/wp-content/uploads/2025/04/0003.jpg',
    'grand-park-plaza' => '/wp-content/uploads/2025/04/Picture19.jpg',
    'mogan-hotel-baku' => '/wp-content/uploads/2025/04/getlstd-property-pho.jpg',
    'socar-midstream-office' => '/wp-content/uploads/2025/04/bp-xezer-centre.jpg',
    'intercontinental-hotel-baku' => '/wp-content/uploads/2025/04/intercontinental-baku-7096431446-2x1-1.webp',
    'qalaalti-hotel' => '/wp-content/uploads/2025/04/1563191831.2.jpg',
    'khazar-residence' => '/wp-content/uploads/2025/04/3893-1724920571cfqF6.jpg',
    'savalan-winers' => '/wp-content/uploads/2025/04/main_factory_img.webp',
    'skywell-showroom' => '/wp-content/uploads/2025/04/363004021_17898058946839303_1293916128108342644_n.jpg',
    'the-pool-house' => '/wp-content/uploads/2025/03/204119242.webp',
];
$uc = $db->prepare("UPDATE projects SET cover=? WHERE slug=? AND (cover IS NULL OR cover='')");
foreach ($covers as $slug=>$img) $uc->execute([$img,$slug]);
$log('✓ project covers seeded (14)');

// SEO title + description per project (Azerbaijani, tailored to CENG services)
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
$log('✓ project SEO seeded (14)');

$log("\nDONE. Delete this file. Open /admin/?section=projects");
