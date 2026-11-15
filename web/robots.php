<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "User-agent: *\n";
echo "Disallow: /includes/\n";
echo "Disallow: /send-form.php\n";
echo "\n";
echo 'Sitemap: ' . absolute_url('sitemap.xml') . "\n";
