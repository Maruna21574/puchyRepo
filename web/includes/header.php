<?php
/**
 * Spoločná hlavička. Pred include nastavte pole $page:
 *   title, description, path (napr. '' alebo 'ochrana-osobnych-udajov.php'),
 *   is_home (bool), robots (voliteľné, napr. 'noindex').
 */
declare(strict_types=1);

$page = array_merge([
    'title'       => biz('name'),
    'description' => '',
    'path'        => '',
    'is_home'     => false,
    'robots'      => 'index, follow',
    'use_base'    => false, // <base> pre 404 stránku (zachová cesty k assets pri vnorenej URL)
], $page ?? []);

$nav = $page['is_home'] ? '' : 'index.php';
$canonical = absolute_url($page['path']);
$ogImage = find_image('og-image') ? absolute_url(strtok(find_image('og-image'), '?')) : '';

$navItems = [
    'uvod'        => 'Úvod',
    'o-mne'       => 'O mne',
    'masaze'      => 'Masáže',
    'permanentky' => 'Permanentky',
    'galeria'     => 'Galéria',
    'kontakt'     => 'Kontakt',
];
?><!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<?php if ($page['use_base']): ?>
    <base href="<?= e(base_url()) ?>/">
<?php endif; ?>
    <title><?= e($page['title']) ?></title>
    <meta name="description" content="<?= e($page['description']) ?>">
    <meta name="robots" content="<?= e($page['robots']) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="theme-color" content="#6B1E2C">
    <meta name="format-detection" content="telephone=no">

    <!-- Open Graph (Facebook) -->
    <meta property="og:type" content="website">
    <meta property="og:locale" content="sk_SK">
    <meta property="og:site_name" content="<?= e(biz('name')) ?>">
    <meta property="og:title" content="<?= e($page['title']) ?>">
    <meta property="og:description" content="<?= e($page['description']) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($ogImage !== ''): ?>
    <meta property="og:image" content="<?= e($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= e(biz('name') . ' – ' . biz('city') . ', ' . biz('district')) ?>">
    <meta name="twitter:card" content="summary_large_image">
<?php endif; ?>

    <link rel="icon" href="<?= e(asset('icons/favicon.svg')) ?>" type="image/svg+xml">
    <link rel="icon" href="<?= e(asset('icons/favicon-32.png')) ?>" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="<?= e(asset('icons/apple-touch-icon.png')) ?>">

    <link rel="preload" href="assets/fonts/eb-garamond-400-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="assets/fonts/jost-400-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= e(asset('css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
    <script src="<?= e(asset('js/script.js')) ?>" defer></script>
<?php if ($page['is_home']): ?>
    <script type="application/ld+json"><?= json_encode(array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => 'HealthAndBeautyBusiness',
        '@id'         => absolute_url('#prevadzka'),
        'name'        => biz('name'),
        'description' => $page['description'],
        'url'         => base_url() . '/',
        'telephone'   => biz('phone_intl'),
        'email'       => biz('email'),
        'image'       => $ogImage ?: null,
        'address'     => array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => biz('street'),
            'addressLocality' => biz('city'),
            'postalCode'      => biz('postal_code') ?: null,
            'addressCountry'  => biz('country'),
        ]),
        'areaServed'  => biz('city'),
        'sameAs'      => [biz('facebook')],
        'employee'    => ['@type' => 'Person', 'name' => biz('person'), 'jobTitle' => 'Masérka'],
    ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif; ?>
</head>
<body class="<?= $page['is_home'] ? 'is-home' : 'is-subpage' ?>">
<a class="skip-link" href="#obsah">Preskočiť na obsah</a>

<header class="site-header" data-header>
    <div class="container site-header__inner">
        <a class="brand" href="<?= $page['is_home'] ? '#uvod' : 'index.php' ?>" aria-label="<?= e(biz('name')) ?> – úvod">
            <?= lotus_mark_svg('brand__mark') ?>
            <span class="brand__text">
                <span class="brand__name">Masáže</span>
                <span class="brand__sub">v Sásovej</span>
            </span>
        </a>

        <nav class="main-nav" id="hlavne-menu" aria-label="Hlavná navigácia" data-nav>
            <ul class="main-nav__list">
<?php foreach ($navItems as $id => $label): ?>
                <li><a class="main-nav__link" href="<?= e($nav . '#' . $id) ?>" data-nav-link="<?= e($id) ?>"><?= e($label) ?></a></li>
<?php endforeach; ?>
            </ul>
            <div class="main-nav__mobile-extra">
                <a class="btn btn--primary btn--block" href="<?= e($nav) ?>#kontakt">Objednať sa</a>
                <a class="main-nav__phone" href="tel:<?= e(biz('phone_intl')) ?>"><?= e(biz('phone')) ?></a>
            </div>
        </nav>

        <a class="btn btn--primary btn--sm site-header__cta" href="<?= e($nav) ?>#kontakt">Objednať sa</a>

        <button class="nav-toggle" type="button" aria-controls="hlavne-menu" aria-expanded="false" data-nav-toggle>
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
            <span class="visually-hidden" data-nav-toggle-label>Otvoriť menu</span>
        </button>
    </div>
</header>

<main id="obsah">
