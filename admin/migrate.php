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

// Per-project texts (descr / content / scope), Azerbaijani. Fills ONLY empty fields.
$texts = [
 'bine-stadium' => [
   'Binə qəsəbəsindəki stadionun tikintisi və mühəndislik sistemləri üzrə kompleks işlər.',
   '<p>Binə Stadionu layihəsində CENG layihələndirmədən icraya qədər bütün mərhələlərdə iştirak edib. Tribunalar, texniki otaqlar və meydança infrastrukturu üzrə tikinti işləri yüksək keyfiyyət standartları ilə yerinə yetirilib.</p><p>Mühəndislik sistemləri — işıqlandırma, drenaj, elektrik təchizatı və zəif cərəyan xətləri vahid layihə çərçivəsində qurulub. Obyekt idman qurumlarının tələblərinə tam uyğun təhvil verilib.</p>',
   "Layihələndirmə və texniki sənədlər\nTribuna və meydança konstruksiyaları\nDrenaj və suvarma sistemləri\nİşıqlandırma və elektrik təchizatı\nTexniki otaqların qurulması\nƏrazinin abadlaşdırılması"],
 'bakcell-arena' => [
   'Bakcell Arena stadionunda tikinti, quraşdırma və mühəndislik sistemləri üzrə işlər.',
   '<p>Bakcell Arena layihəsində CENG tikinti-quraşdırma işlərini və mühəndislik sistemlərinin qurulmasını həyata keçirib. İşlər beynəlxalq idman infrastrukturu tələbləri nəzərə alınmaqla icra olunub.</p><p>Elektrik təchizatı, havalandırma və texniki sahələrin təchizatı üzrə kompleks həllər tətbiq edilib, obyekt vaxtında və keyfiyyətlə təhvil verilib.</p>',
   "Tikinti-quraşdırma işləri\nElektrik təchizatı sistemləri\nHavalandırma və iqlim həlləri\nTexniki sahələrin təchizatı\nYekun tamamlama işləri"],
 'sr-group-co' => [
   'SR Group CO üçün ofis və istehsalat sahələrində kompleks mühəndislik həlləri.',
   '<p>SR Group CO layihəsi çərçivəsində CENG layihələndirmə, tikinti və mühəndislik sistemlərinin quraşdırılmasını kompleks şəkildə təqdim edib.</p><p>İstilik, havalandırma, elektrik və zəif cərəyan sistemləri müasir standartlara uyğun qurulub, sahələr istismara tam hazır vəziyyətdə təhvil verilib.</p>',
   "Layihələndirmə işləri\nİstilik və havalandırma sistemləri\nElektrik və zəif cərəyan xətləri\nDaxili tamamlama işləri\nSatışdan sonrakı texniki dəstək"],
 'wyndham-garden-baku' => [
   'Wyndham Garden Baku otelində mühəndislik sistemləri və tikinti işləri.',
   '<p>Beynəlxalq otel şəbəkəsinə daxil olan Wyndham Garden Baku layihəsində CENG mühəndislik sistemlərinin layihələndirilməsi və quraşdırılmasını icra edib.</p><p>Otel standartlarına uyğun istilik, havalandırma, su təchizatı və elektrik sistemləri qurulub; işlər brendin texniki tələblərinə tam cavab verməklə təhvil verilib.</p>',
   "Mühəndislik sistemlərinin layihələndirilməsi\nİstilik və havalandırma (HVAC)\nSu təchizatı və kanalizasiya\nElektrik təchizatı sistemləri\nOtaqların tamamlama işləri"],
 'agsu-dairy-factory' => [
   'Ağsu süd zavodunda sənaye avadanlığının təchizatı və mühəndislik işləri.',
   '<p>Ağsu süd zavodu layihəsində CENG sənaye avadanlığının təchizatı, quraşdırılması və mühəndislik sistemlərinin qurulmasını həyata keçirib.</p><p>İstehsalat sahələri qida sənayesinin gigiyena və texnoloji tələblərinə uyğun təchiz olunub; soyutma, su və elektrik sistemləri vahid həll kimi icra edilib.</p>',
   "Sənaye avadanlığının təchizatı\nTexnoloji xətlərin quraşdırılması\nSoyutma sistemləri\nSu təchizatı və elektrik xətləri\nİstehsalat sahələrinin tamamlanması"],
 'grand-park-plaza' => [
   'Grand Park Plaza obyektində tikinti və mühəndislik sistemləri üzrə kompleks işlər.',
   '<p>Grand Park Plaza layihəsində CENG tikinti işlərini və bina mühəndislik sistemlərinin quraşdırılmasını kompleks şəkildə yerinə yetirib.</p><p>Havalandırma, istilik, elektrik və zəif cərəyan sistemləri müasir biznes mərkəzi tələblərinə uyğun qurulub və istismara verilib.</p>',
   "Tikinti-quraşdırma işləri\nHavalandırma və istilik sistemləri\nElektrik təchizatı\nZəif cərəyan sistemləri\nÜmumi sahələrin tamamlanması"],
 'mogan-hotel-baku' => [
   'Mogan Hotel Baku layihəsində mühəndislik və tamamlama işləri.',
   '<p>Mogan Hotel Baku layihəsində CENG mühəndislik sistemlərinin quraşdırılması və tamamlama işlərini icra edib.</p><p>Otel sahələri üzrə istilik, havalandırma, elektrik və su təchizatı sistemləri qonaqlama sektorunun standartlarına uyğun qurulub.</p>',
   "Mühəndislik sistemlərinin quraşdırılması\nİstilik və havalandırma\nElektrik təchizatı\nSu təchizatı sistemləri\nDaxili tamamlama işləri"],
 'socar-midstream-office' => [
   'SOCAR Midstream ofisində mühəndislik sistemləri və ofis mühitinin qurulması.',
   '<p>SOCAR Midstream ofis layihəsində CENG mühəndislik sistemlərinin layihələndirilməsi və quraşdırılmasını, habelə ofis sahələrinin tamamlama işlərini həyata keçirib.</p><p>İşlər korporativ təhlükəsizlik və keyfiyyət tələblərinə tam uyğun şəkildə, iş prosesini dayandırmadan icra olunub.</p>',
   "Layihələndirmə və razılaşdırma\nHavalandırma və iqlim sistemləri\nElektrik və zəif cərəyan xətləri\nOfis sahələrinin tamamlanması\nTexniki sənədlərin təhvili"],
 'intercontinental-hotel-baku' => [
   'InterContinental Hotel Baku layihəsində mühəndislik sistemləri üzrə işlər.',
   '<p>InterContinental Hotel Baku layihəsində CENG mühəndislik sistemlərinin quraşdırılması üzrə işləri beynəlxalq otel şəbəkəsinin standartlarına uyğun icra edib.</p><p>İstilik, havalandırma, su və elektrik təchizatı sistemləri yüksək etibarlılıq tələbləri ilə qurulub və sınaqdan keçirilərək təhvil verilib.</p>',
   "HVAC sistemlərinin quraşdırılması\nElektrik təchizatı\nSu təchizatı və kanalizasiya\nAvtomatlaşdırma və nəzarət\nSınaq və təhvil işləri"],
 'qalaalti-hotel' => [
   'Qalaaltı Hotel layihəsində tikinti və mühəndislik sistemləri üzrə kompleks işlər.',
   '<p>Dağ ətəyində yerləşən Qalaaltı Hotel layihəsində CENG tikinti və mühəndislik işlərini kompleks şəkildə həyata keçirib.</p><p>Otel və sağlamlıq mərkəzi sahələri üzrə istilik, havalandırma, su təchizatı və elektrik sistemləri relyefin xüsusiyyətləri nəzərə alınmaqla qurulub.</p>',
   "Tikinti-quraşdırma işləri\nİstilik və havalandırma sistemləri\nSu təchizatı və kanalizasiya\nElektrik təchizatı\nƏrazinin mühəndis infrastrukturu"],
 'khazar-residence' => [
   'Khazar Residence yaşayış kompleksində mühəndislik sistemləri və tamamlama işləri.',
   '<p>Khazar Residence yaşayış kompleksi layihəsində CENG mühəndislik sistemlərinin quraşdırılması və tamamlama işlərini yerinə yetirib.</p><p>Mənzil və ümumi sahələr üzrə istilik, havalandırma, elektrik və su təchizatı sistemləri sakinlərin rahatlığı üçün müasir standartlarla qurulub.</p>',
   "Mühəndislik sistemlərinin quraşdırılması\nİstilik və havalandırma\nElektrik təchizatı\nSu təchizatı sistemləri\nÜmumi sahələrin tamamlanması"],
 'savalan-winers' => [
   'Savalan şərab zavodunda sənaye avadanlığı və mühəndislik sistemləri üzrə işlər.',
   '<p>Savalan şərab istehsalı müəssisəsində CENG sənaye avadanlığının təchizatı və mühəndislik sistemlərinin quraşdırılmasını həyata keçirib.</p><p>İstehsalat prosesinin tələblərinə uyğun soyutma, su və elektrik sistemləri qurulub, texnoloji sahələr istismara hazır vəziyyətdə təhvil verilib.</p>',
   "Sənaye avadanlığının təchizatı\nSoyutma sistemləri\nSu təchizatı xətləri\nElektrik təchizatı\nTexnoloji sahələrin tamamlanması"],
 'skywell-showroom' => [
   'Skywell avtomobil mərkəzində tikinti və mühəndislik həlləri.',
   '<p>Skywell avtomobil mərkəzi layihəsində CENG tikinti işlərini və mühəndislik sistemlərinin quraşdırılmasını icra edib.</p><p>Nümayiş zalı və servis sahələri üzrə işıqlandırma, havalandırma və elektrik təchizatı brend standartlarına uyğun qurulub.</p>',
   "Tikinti-quraşdırma işləri\nİşıqlandırma sistemləri\nHavalandırma və iqlim həlləri\nElektrik təchizatı\nServis sahələrinin təchizatı"],
 'the-pool-house' => [
   'The Pool House layihəsində hovuz infrastrukturu və mühəndislik sistemləri.',
   '<p>The Pool House layihəsində CENG hovuz infrastrukturunun qurulması və mühəndislik sistemlərinin quraşdırılmasını həyata keçirib.</p><p>Su hazırlığı, filtrasiya, istilik və havalandırma sistemləri istirahət obyektinin tələblərinə uyğun icra olunub.</p>',
   "Hovuz infrastrukturunun qurulması\nSu hazırlığı və filtrasiya\nİstilik və havalandırma\nElektrik təchizatı\nTamamlama işləri"],
];
// "blank" = empty after stripping tags/whitespace (rich editors leave <br>/<p></p> junk)
$blank = fn($s) => trim(strip_tags((string)$s)) === '' && strpos((string)$s, '<img') === false;
$sel  = $db->prepare("SELECT descr, content, scope FROM projects WHERE slug = ?");
$updD = $db->prepare("UPDATE projects SET descr = ? WHERE slug = ?");
$updC = $db->prepare("UPDATE projects SET content = ? WHERE slug = ?");
$updS = $db->prepare("UPDATE projects SET scope = ? WHERE slug = ?");
$n = 0;
foreach ($texts as $slug => $v) {
    $sel->execute([$slug]); $cur = $sel->fetch();
    if (!$cur) continue;
    if ($blank($cur['descr']))   { $updD->execute([$v[0], $slug]); $n++; }
    if ($blank($cur['content'])) { $updC->execute([$v[1], $slug]); $n++; }
    if ($blank($cur['scope']))   { $updS->execute([$v[2], $slug]); $n++; }
}
$log("✓ project texts ensured (fields filled: $n)");

$log("\nDONE. Re-runnable. Do NOT delete from the repo (token-protected).");
