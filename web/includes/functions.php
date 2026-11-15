<?php
/**
 * Spoločné pomocné funkcie. Načítava sa cez includes/bootstrap.php.
 */
declare(strict_types=1);

/** Escapovanie výstupu do HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Konfigurácia (includes/config.php), voliteľne konkrétny kľúč cez bodku: config('business.phone'). */
function config(?string $key = null): mixed
{
    static $config = null;
    $config ??= require __DIR__ . '/config.php';
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return null;
        }
        $value = $value[$part];
    }
    return $value;
}

function biz(string $key): string
{
    return (string) config('business.' . $key);
}

/** Verejný koreň webu (priečinok web/) na disku. */
function web_root(): string
{
    return dirname(__DIR__);
}

/** Absolútna adresa webu (bez lomky na konci). */
function base_url(): string
{
    $configured = rtrim((string) config('base_url'), '/');
    if ($configured !== '') {
        return $configured;
    }
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host   = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    $dir    = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function absolute_url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/** Relatívna cesta k súboru v assets/ s verziou podľa času zmeny (kvôli cache). */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = web_root() . '/assets/' . $path;
    $v    = is_file($file) ? '?v=' . filemtime($file) : '';
    return 'assets/' . $path . $v;
}

/**
 * Nájde obrázok podľa názvu bez prípony, napr. find_image('sluzby/lymfodrenaz').
 * Vráti relatívnu cestu alebo null, ak súbor (zatiaľ) neexistuje.
 */
function find_image(string $basename): ?string
{
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        $rel = 'images/' . $basename . '.' . $ext;
        if (is_file(web_root() . '/assets/' . $rel)) {
            return asset($rel);
        }
    }
    return null;
}

/** Rozmery obrázka pre atribúty width/height (zabraňuje posunom layoutu). */
function image_size_attrs(string $relUrl): string
{
    $file = web_root() . '/' . strtok($relUrl, '?');
    $size = @getimagesize($file);
    return $size ? sprintf(' width="%d" height="%d"', $size[0], $size[1]) : '';
}

/**
 * Vykreslí fotografiu, alebo – ak ešte nebola dodaná – elegantné, jasne označené miesto na jej doplnenie.
 */
function photo(string $basename, string $alt, string $placeholderLabel, string $class = '', bool $eager = false): string
{
    $src = find_image($basename);
    if ($src !== null) {
        return sprintf(
            '<img class="%s" src="%s" alt="%s"%s %s decoding="async">',
            e($class),
            e($src),
            e($alt),
            image_size_attrs($src),
            $eager ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"'
        );
    }
    return sprintf(
        '<div class="photo-placeholder %s" role="img" aria-label="%s">%s<span class="photo-placeholder__label">%s</span><span class="photo-placeholder__file">assets/images/%s</span></div>',
        e($class),
        e('Miesto pre fotografiu: ' . $placeholderLabel),
        lotus_svg('photo-placeholder__icon'),
        e($placeholderLabel),
        e(str_ends_with($basename, '/') ? $basename . '…' : $basename . '.jpg')
    );
}

/** Ovládanie carouselu: šípky vľavo/vpravo a zlatý indikátor priebehu (logika v assets/js/script.js). */
function carousel_controls(string $trackId, string $prevLabel, string $nextLabel, string $class = ''): string
{
    $arrow = static fn (string $d): string =>
        '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    return sprintf(
        '<div class="carousel-controls %1$s">'
        . '<button class="carousel-btn" type="button" data-carousel-prev aria-controls="%2$s" aria-label="%3$s">%5$s</button>'
        . '<div class="carousel-progress" aria-hidden="true"><span class="carousel-progress__bar" data-carousel-bar></span></div>'
        . '<button class="carousel-btn" type="button" data-carousel-next aria-controls="%2$s" aria-label="%4$s">%6$s</button>'
        . '</div>',
        e($class), e($trackId), e($prevLabel), e($nextLabel), $arrow('M15 5l-7 7 7 7'), $arrow('M9 5l7 7-7 7')
    );
}

/** Možnosti poľa „Vybraná masáž“ (slug => názov). Rovnaký zoznam používa aj serverová validácia. */
function service_options(): array
{
    $options = [];
    foreach (require __DIR__ . '/services.php' as $slug => $service) {
        $options[$slug] = $service['name'];
    }
    return $options + [
        'permanentka-10' => 'Permanentka na 10 vstupov',
        'permanentka-5'  => 'Permanentka na 5 vstupov',
        'poradte-mi'     => 'Neviem sa rozhodnúť – poraďte mi',
    ];
}

/**
 * Znak značky (logo): lotos s plným stredovým lupeňom a líniou hladiny.
 * Línie preberajú currentColor (zlatá), stredový lupeň farbu --lotus-core (bordová, na tmavom pozadí zlatá).
 * Rovnaký znak je aj v samostatných súboroch assets/images/logo/*.svg.
 */
function lotus_mark_svg(string $class = ''): string
{
    return '<svg class="lotus-mark ' . e($class) . '" aria-hidden="true" focusable="false" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M31 44.5C25 37.5 15.5 34.2 6.5 35.6c4.2 7.6 13.4 10.8 24.5 8.9Z"/>'
        . '<path d="M33 44.5c6-7 15.5-10.3 24.5-8.9-4.2 7.6-13.4 10.8-24.5 8.9Z"/>'
        . '<path d="M32 44.5c-2-11.2 2.6-20.6 13.8-25.6 1.9 11.9-3.8 21.4-13.8 25.6Z"/>'
        . '<path d="M32 44.5c2-11.2-2.6-20.6-13.8-25.6-1.9 11.9 3.8 21.4 13.8 25.6Z"/>'
        . '<path class="lotus-mark__core" d="M32 8.5c8.2 9 9.4 22.6 0 36-9.4-13.4-8.2-27 0-36Z"/>'
        . '<path d="M15 52.5c9.5-3.4 24.5-3.4 34 0"/>'
        . '<path d="M22 57c6-1.6 14-1.6 20 0" stroke-width="1.1" opacity=".6"/>'
        . '</svg>';
}

/**
 * Pečať značky pre úvodnú fotografiu: znak lotosu v dvojitom zlatom kruhu s názvom po obvode.
 * Text je skutočný text (písmo stránky), nie obrázok – zostáva ostrý na každom displeji.
 */
function brand_seal_svg(string $class = ''): string
{
    $name = mb_strtoupper(biz('name'));
    $city = mb_strtoupper(biz('city'));
    return '<svg class="brand-seal ' . e($class) . '" role="img" aria-label="' . e(biz('name') . ' – ' . biz('city')) . '" viewBox="0 0 200 200">'
        . '<defs>'
        . '<path id="seal-top" d="M30 100a70 70 0 0 1 140 0"/>'
        . '<path id="seal-bottom" d="M19 100a81 81 0 0 0 162 0"/>'
        . '</defs>'
        . '<circle cx="100" cy="100" r="97" class="brand-seal__disc"/>'
        . '<circle cx="100" cy="100" r="90" class="brand-seal__ring"/>'
        . '<circle cx="100" cy="100" r="61" class="brand-seal__ring brand-seal__ring--inner"/>'
        . '<text class="brand-seal__name"><textPath href="#seal-top" startOffset="50%" text-anchor="middle">' . e($name) . '</textPath></text>'
        . '<text class="brand-seal__city"><textPath href="#seal-bottom" startOffset="50%" text-anchor="middle">' . e($city) . '</textPath></text>'
        . '<circle cx="19.5" cy="100" r="1.8" class="brand-seal__dot"/><circle cx="180.5" cy="100" r="1.8" class="brand-seal__dot"/>'
        . '<g class="brand-seal__mark" transform="translate(64 60) scale(1.125)" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' . preg_replace('/^<svg[^>]*>|<\/svg>$/', '', lotus_mark_svg()) . '</g>'
        . '</svg>';
}

/** Lotosový symbol (vlastná vektorová podoba loga). */
function lotus_svg(string $class = '', string $title = ''): string
{
    $a11y = $title !== ''
        ? 'role="img" aria-label="' . e($title) . '"'
        : 'aria-hidden="true" focusable="false"';
    return '<svg class="' . e($class) . '" ' . $a11y . ' viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'
        . '<path d="M32 9c7.5 8.5 8.5 22 0 34.5C23.5 31 24.5 17.5 32 9Z"/>'
        . '<path d="M32 43.5c-1.6-11 3-20.5 14.5-25 1.6 12-4.4 21.5-14.5 25Z"/>'
        . '<path d="M32 43.5c1.6-11-3-20.5-14.5-25-1.6 12 4.4 21.5 14.5 25Z"/>'
        . '<path d="M32 43.5C26 35.5 15.5 31.5 5.5 32c4 9 14 13.5 26.5 11.5Z"/>'
        . '<path d="M32 43.5C38 35.5 48.5 31.5 58.5 32c-4 9-14 13.5-26.5 11.5Z"/>'
        . '<path d="M13 51c8-3.2 30-3.2 38 0"/>'
        . '</svg>';
}

/* ---------------------------------------------------------------------------
 * Relácia a CSRF
 * ------------------------------------------------------------------------ */

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('mvs_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

function csrf_token(): string
{
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_valid(?string $token): bool
{
    start_secure_session();
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

/** Podpísaná časová pečiatka načítania formulára (ochrana proti robotom). */
function form_timestamp(): string
{
    $t = (string) time();
    return $t . '.' . hash_hmac('sha256', $t, csrf_token());
}

/** Vráti vek formulára v sekundách alebo null, ak je pečiatka neplatná. */
function form_age(?string $stamp): ?int
{
    if (!is_string($stamp) || !preg_match('/^(\d{10})\.([a-f0-9]{64})$/', $stamp, $m)) {
        return null;
    }
    if (!hash_equals(hash_hmac('sha256', $m[1], csrf_token()), $m[2])) {
        return null;
    }
    return time() - (int) $m[1];
}

/** Jednorazová správa po presmerovaní (vzor Post/Redirect/Get). */
function flash_set(string $key, mixed $value): void
{
    start_secure_session();
    $_SESSION['flash'][$key] = $value;
}

function flash_get(string $key): mixed
{
    start_secure_session();
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

/* ---------------------------------------------------------------------------
 * SMTP
 * ------------------------------------------------------------------------ */

/** Načíta SMTP konfiguráciu z private/smtp-config.php alebo vráti null. */
function smtp_config(): ?array
{
    foreach ((array) config('smtp_config_paths') as $path) {
        if (is_file($path)) {
            $cfg = require $path;
            return is_array($cfg) ? $cfg : null;
        }
    }
    return null;
}

/** Je SMTP kompletne nastavené? */
function smtp_ready(): bool
{
    $cfg = smtp_config();
    if ($cfg === null || empty($cfg['enabled'])) {
        return false;
    }
    foreach (['host', 'port', 'username', 'password', 'from_email', 'to_email'] as $key) {
        if (empty($cfg[$key])) {
            return false;
        }
    }
    return true;
}

/* ---------------------------------------------------------------------------
 * HTTP hlavičky
 * ------------------------------------------------------------------------ */

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()');
    // Ak je nastavená analytika (config 'analytics_id'), povolíme jej domény. Načíta sa aj tak až po súhlase s cookies.
    $ga      = (string) config('analytics_id') !== '';
    $script  = $ga ? " https://www.googletagmanager.com" : '';
    $connect = $ga ? " https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com" : '';
    $img     = $ga ? " https://*.google-analytics.com https://www.googletagmanager.com" : '';
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data:{$img}; style-src 'self' 'unsafe-inline'; script-src 'self'{$script}; connect-src 'self'{$connect}; font-src 'self'; frame-src https://www.google.com https://maps.google.com; form-action 'self'; base-uri 'self'; frame-ancestors 'self'; object-src 'none'");
}
