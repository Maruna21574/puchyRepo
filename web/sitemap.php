<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/xml; charset=utf-8');

$pages = [
    ['path' => '',                            'file' => 'index.php',                   'priority' => '1.0'],
    ['path' => 'ochrana-osobnych-udajov.php', 'file' => 'ochrana-osobnych-udajov.php', 'priority' => '0.3'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $p): ?>
    <url>
        <loc><?= e(absolute_url($p['path'])) ?></loc>
        <lastmod><?= date('Y-m-d', (int) filemtime(__DIR__ . '/' . $p['file'])) ?></lastmod>
        <priority><?= $p['priority'] ?></priority>
    </url>
<?php endforeach; ?>
</urlset>
