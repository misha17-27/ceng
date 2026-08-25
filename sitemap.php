<?php
/* Dynamic sitemap: 5 main pages + all visible projects from the DB.
   Served as /sitemap.xml via an .htaccess rewrite. */
require_once __DIR__ . '/includes/data.php';
header('Content-Type: application/xml; charset=utf-8');
$host = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'ceng.az');

$urls = [
    ['/', '1.0'],
    ['/layiheler/', '0.9'],
    ['/haqqimizda/', '0.8'],
    ['/xidmetlerimiz/', '0.8'],
    ['/elaqe/', '0.7'],
];
foreach (site_projects(1000) as $p) $urls[] = ['/' . $p['slug'] . '/', '0.7'];

echo '<' . '?xml version="1.0" encoding="UTF-8"?' . ">\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as [$u, $prio]) {
    echo "  <url><loc>" . htmlspecialchars($host . $u, ENT_QUOTES) . "</loc><priority>$prio</priority></url>\n";
}
echo "</urlset>\n";
