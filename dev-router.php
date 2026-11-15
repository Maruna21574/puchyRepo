<?php
/**
 * IBA PRE LOKÁLNY VÝVOJ – nenahrávať na hosting.
 *
 * Vstavaný PHP server nečíta .htaccess, preto tento router napodobňuje
 * jeho pravidlá (sitemap.xml, robots.txt, zákaz includes/, 404 stránka).
 *
 * Spustenie (v priečinku masaze-sasova):
 *   php -S localhost:8000 -t web dev-router.php
 */
declare(strict_types=1);

$web  = __DIR__ . '/web';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

if ($path === '/sitemap.xml') { require $web . '/sitemap.php'; return true; }
if ($path === '/robots.txt')  { require $web . '/robots.php';  return true; }

if (str_starts_with($path, '/includes/') || preg_match('~/\.(?!well-known)~', $path)
    || preg_match('~\.(md|log|ini|sql|bak|example|dist|lock|json|txt)$~', $path)) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

$file = realpath($web . $path);
if ($file !== false && str_starts_with($file, realpath($web)) && is_file($file) && preg_match('~\.jfif$~i', $file)) {
    header('Content-Type: image/jpeg'); // vstavaný server príponu .jfif nepozná (na hostingu rieši .htaccess)
    header('Content-Length: ' . filesize($file));
    readfile($file);
    return true;
}
if ($path === '/' || ($file !== false && str_starts_with($file, realpath($web)) && is_file($file))) {
    return false; // obslúži vstavaný server
}

$_SERVER['SCRIPT_NAME'] = '/404.php';
require $web . '/404.php';
return true;
