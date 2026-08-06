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

$log("\nDONE. Delete this file. Open /admin/?section=projects");
