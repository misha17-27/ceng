<?php
/*
 * One-time installer. Open in browser:  /admin/install.php?token=YOUR_TOKEN
 * (token = install_token in config.php). Creates tables, seeds data, and the
 * first admin account. DELETE this file afterwards.
 */
declare(strict_types=1);
require __DIR__ . '/lib.php';
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['token'] ?? '') !== (string)cfg('install_token')) {
    http_response_code(403);
    exit("Forbidden. Add ?token=... (the install_token from config.php).");
}
$db = pdo();
$log = fn($m) => print($m . "\n");

$db->exec("CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) UNIQUE NOT NULL,
  pass_hash VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

foreach (['texts','contacts','settings','seo'] as $kvt) {
    $db->exec("CREATE TABLE IF NOT EXISTS `$kvt` (
      k VARCHAR(120) PRIMARY KEY, v TEXT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
// seo needs two columns; recreate shape via separate table
$db->exec("CREATE TABLE IF NOT EXISTS seo_pages (
  slug VARCHAR(160) PRIMARY KEY, title VARCHAR(255), descr TEXT ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS pages (
  slug VARCHAR(160) PRIMARY KEY, title VARCHAR(255), in_menu TINYINT DEFAULT 1, sort INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255), descr TEXT, icon VARCHAR(120), sort INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY, slug VARCHAR(160) UNIQUE, title VARCHAR(255),
  image VARCHAR(255), descr TEXT, sort INT DEFAULT 0, visible TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS partners (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), image VARCHAR(255), url VARCHAR(255), sort INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS submissions (
  id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255), phone VARCHAR(120), email VARCHAR(190),
  message TEXT, ip VARCHAR(64), is_read TINYINT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$log('✓ tables created');

/* seed admin */
$em = (string)cfg('install_admin_email'); $pw = (string)cfg('install_admin_pass');
$st = $db->prepare('SELECT id FROM admins WHERE email=?'); $st->execute([$em]);
if (!$st->fetch()) {
    $db->prepare('INSERT INTO admins (email,pass_hash) VALUES (?,?)')
       ->execute([$em, password_hash($pw, PASSWORD_DEFAULT)]);
    $log("✓ admin created: $em");
} else { $log("• admin already exists: $em"); }

/* seed contacts (from current footer/header) */
$contacts = ['phone'=>'+994 70 230 06 90','phone2'=>'+994 70 8109889',
    'email'=>'info@ceng.az','address'=>'Bakı, Əhməd Rəcəbli'];
$ins = $db->prepare("INSERT IGNORE INTO contacts (k,v) VALUES (?,?)");
foreach ($contacts as $k=>$v) $ins->execute([$k,$v]);

/* seed texts (key site strings) */
$texts = [
 'hero_title'   => 'KEYFİYYƏTLİ TİKİNTİ VƏ MÜHƏNDİSLİK HƏLLƏRİ İLƏ GƏLƏCƏYİNİZİ İNŞA EDİRİK.',
 'about_title'  => 'Şirkət haqqında',
 'footer_slogan'=> 'KEYFİYYƏTLİ TİKİNTİ VƏ MÜHƏNDİSLİK HƏLLƏRİ İLƏ GƏLƏCƏYİNİZİ İNŞA EDİRİK.',
];
$it = $db->prepare("INSERT IGNORE INTO texts (k,v) VALUES (?,?)");
foreach ($texts as $k=>$v) $it->execute([$k,$v]);

/* seed pages */
$pages = [['/','Ana səhifə'],['haqqimizda','Haqqımızda'],['xidmetlerimiz','Xidmətlərimiz'],
          ['layiheler','Layihələr'],['elaqe','Əlaqə']];
$pp = $db->prepare("INSERT IGNORE INTO pages (slug,title,sort) VALUES (?,?,?)");
foreach ($pages as $i=>$p) $pp->execute([$p[0],$p[1],$i]);
$sp = $db->prepare("INSERT IGNORE INTO seo_pages (slug,title,descr) VALUES (?,?,?)");
foreach ($pages as $p) $sp->execute([$p[0],$p[1].' - Ceng.az','']);

/* seed projects (14) */
$projects = [
 ['bine-stadium','Bine Stadium'],['bakcell-arena','Bakcell Arena'],['sr-group-co','SR Group CO'],
 ['wyndham-garden-baku','Wyndham Garden Baku'],['agsu-dairy-factory','Agsu dairy factory'],
 ['grand-park-plaza','Grand Park Plaza'],['intercontinental-hotel-baku','InterContinental Hotel Baku'],
 ['khazar-residence','Khazar Residence'],['mogan-hotel-baku','Mogan Hotel Baku'],
 ['qalaalti-hotel','Qalaalti Hotel'],['savalan-winers','Savalan Winers'],
 ['skywell-showroom','Skywell Showroom'],['socar-midstream-office','SOCAR Midstream Office'],
 ['the-pool-house','The Pool House'],
];
$pr = $db->prepare("INSERT IGNORE INTO projects (slug,title,sort) VALUES (?,?,?)");
foreach ($projects as $i=>$p) $pr->execute([$p[0],$p[1],$i]);
$log('✓ seeded contacts, texts, pages, seo, '.count($projects).' projects');

$log("\nDONE. Now:\n 1) Log in at /admin/  (email + password from config.php)\n 2) DELETE this install.php file.");
