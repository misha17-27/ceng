<?php
/*
 * Dynamic route for projects created in the admin panel that have no static
 * folder. .htaccess rewrites unknown one-segment paths here as ?slug=...
 * Unknown slugs bounce to the projects list.
 */
require_once __DIR__ . '/includes/data.php';
$PROJECT_SLUG = strtolower(preg_replace('/[^A-Za-z0-9-]/', '', $_GET['slug'] ?? ''));
$__ok = false;
$__pdo = _site_pdo();
if ($PROJECT_SLUG !== '' && $__pdo) {
    try {
        $__s = $__pdo->prepare('SELECT COUNT(*) c FROM projects WHERE slug = ? AND visible = 1');
        $__s->execute([$PROJECT_SLUG]);
        $__ok = (int)$__s->fetch()['c'] > 0;
    } catch (Throwable $e) {}
}
if (!$__ok) { header('Location: /layiheler/', true, 302); exit; }
require __DIR__ . '/includes/project.php';
