<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$services = require __DIR__ . '/includes/services.php';

// Stav formulára po presmerovaní (Post/Redirect/Get)
$form   = flash_get('contact_form') ?? [];
$old    = $form['old'] ?? [];
$errors = $form['errors'] ?? [];
$csrf   = csrf_token();
$smtpReady = smtp_ready();

// Objednávkový formulár (includes/booking.php): demo potvrdenie po odoslaní bez JavaScriptu, rozsah dátumov, časy
$formDone  = booking_is_demo() && ($form['status'] ?? '') === 'demo';
$dateMin   = booking_today()->format('Y-m-d');
$dateMax   = booking_max_date()->format('Y-m-d');
$timeSlots = booking_all_start_times();

// Predvolená masáž z odkazu "Objednať sa" pri karte (?masaz=slug)
$serviceOptions  = service_options();
$selectedService = (string) ($old['service'] ?? ($_GET['masaz'] ?? ''));
if (!isset($serviceOptions[$selectedService])) {
    $selectedService = '';
}
// Typ objednávky: jednorazová masáž / permanentka (odkaz ?masaz=permanentka-10 otvorí formulár pre permanentku)
$passOptions  = booking_pass_options();
$selectedPass = (string) ($old['pass_type'] ?? '');
if (isset($passOptions[$selectedService])) {
    $selectedPass    = $selectedService;
    $selectedService = '';
}
$orderType = (($old['order_type'] ?? '') === 'pass' || $selectedPass !== '') ? 'pass' : 'single';
$isPass    = $orderType === 'pass';

// Galéria „Nahliadnite k nám“: fotka1, fotka2, fotka3… z assets/images/, potom obrázky
// z assets/images/galeria/ a nakoniec fotka miestnosti (hlavnyPriestor). Zoradené podľa názvu súboru.
// Na Windows glob nerozlišuje veľkosť písmen (.jpg aj .JPG nájde ten istý súbor) – preto deduplikácia.
$imgGlob = static function (string $pattern): array {
    $files = glob(__DIR__ . '/assets/images/' . $pattern . '.{jpg,jpeg,png,webp,jfif,JPG,JPEG,PNG,WEBP,JFIF}', GLOB_BRACE) ?: [];
    $unique = [];
    foreach ($files as $f) {
        $unique[strtolower(str_replace('\\', '/', $f))] ??= $f;
    }
    $files = array_values($unique);
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    return $files;
};
$galleryMain = $imgGlob('fotka*');
// Fotografie, ktoré majú byť v galérii na začiatku (v tomto poradí); ostatné nasledujú v pôvodnom poradí.
$galleryFirst = ['fotka4'];
$galleryRank = static function (string $file) use ($galleryFirst): int {
    $i = array_search(strtolower(pathinfo($file, PATHINFO_FILENAME)), $galleryFirst, true);
    return $i === false ? PHP_INT_MAX : (int) $i;
};
usort($galleryMain, static fn (string $a, string $b): int => $galleryRank($a) <=> $galleryRank($b)); // stabilné triedenie (PHP 8)
$galleryDir  = $imgGlob('galeria/*');
$galleryRoom = $imgGlob('hlavnyPriestor');
$galleryAlts = require __DIR__ . '/includes/galeria.php';
$assetsDir   = str_replace('\\', '/', __DIR__) . '/assets/';
$gallery = array_map(static function (string $file) use ($galleryAlts, $assetsDir): array {
    $file = str_replace('\\', '/', $file);
    $name = pathinfo($file, PATHINFO_FILENAME);
    $alt  = $galleryAlts[basename($file)] ?? (preg_match('/^fotka\d*$/i', $name)
        ? ''
        : trim(preg_replace('/^\d+[-_ ]*/', '', str_replace(['-', '_'], ' ', $name))));
    return [
        'src' => asset(substr($file, strlen($assetsDir))),
        'alt' => $alt !== '' ? mb_strtoupper(mb_substr($alt, 0, 1)) . mb_substr($alt, 1) : 'Fotografia z masážneho štúdia',
    ];
}, array_merge($galleryMain, $galleryDir, $galleryRoom));

// Sekcia „O mne“: fotografie masérky do carouselu (zobrazia sa len existujúce súbory)
$aboutPhotos = array_values(array_filter([
    ['file' => 'ludmila-dolinska', 'alt' => 'Ľudmila Dolinská, masérka'],
    ['file' => 'oMne2',            'alt' => 'Ľudmila Dolinská, masérka v štúdiu Masáže v Sásovej', 'class' => 'arch__img--head'],
], static fn (array $p): bool => find_image($p['file']) !== null));

$gallerySlots = [
    ['label' => 'Fotografia masérky',      'class' => 'gallery__item--tall'],
    ['label' => 'Masážna miestnosť',       'class' => ''],
    ['label' => 'Procedúra',               'class' => ''],
    ['label' => 'Vybavenie a pomôcky',     'class' => 'gallery__item--wide'],
    ['label' => 'Detail procedúry',        'class' => ''],
    ['label' => 'Prostredie štúdia',       'class' => ''],
];

$page = [
    'title'       => 'Masáže v Sásovej | Masáže Banská Bystrica – Ľudmila Dolinská',
    'description' => 'Profesionálne masáže v Banskej Bystrici – Sásovej. Klasická, športová a aroma masáž, lymfodrenáž, bankovanie, maderoterapia a ďalšie. 17 rokov skúseností. Objednajte sa: 0915 139 575.',
    'path'        => '',
    'is_home'     => true,
];

require __DIR__ . '/includes/header.php';
?>

<!-- ============================== HERO ============================== -->
<section class="hero" id="uvod" aria-labelledby="hero-title">
    <div class="container hero__grid">
        <div class="hero__content">
            <p class="eyebrow eyebrow--line">Banská Bystrica – Sásová</p>
            <h1 class="hero__title" id="hero-title">Doprajte svojmu telu oddych, <em>ktorý si zaslúži.</em></h1>
            <p class="hero__lead">Profesionálne masáže v Sásovej. Relax, regenerácia a uvoľnenie v príjemnom prostredí.</p>
            <div class="hero__actions">
                <a class="btn btn--primary" href="#kontakt">Objednať sa</a>
                <a class="btn btn--ghost" href="#masaze">Preskúmať ponuku</a>
            </div>
            <ul class="hero__facts" aria-label="V skratke">
                <li><strong><?= (int) config('business.experience_years') ?></strong> rokov skúseností</li>
                <li><strong>Odborné</strong> zdravotnícke vzdelanie</li>
                <li><strong>11</strong> druhov masáží</li>
            </ul>
        </div>

        <div class="hero__visual hero__visual--logo">
            <?= brand_seal_svg('hero__logo') ?>
        </div>
    </div>
</section>

<!-- ============================== O MNE ============================== -->
<section class="section about" id="o-mne" aria-labelledby="about-title">
    <div class="container about__grid">
<?php $aboutCarousel = count($aboutPhotos) > 1; ?>
        <div class="about__visual reveal"<?= $aboutCarousel ? ' data-carousel role="region" aria-roledescription="carousel" aria-label="Fotografie masérky"' : '' ?>>
            <div class="about__photo">
                <div class="arch arch--portrait">
<?php if (count($aboutPhotos) === 1): ?>
                    <?= photo($aboutPhotos[0]['file'], $aboutPhotos[0]['alt'], $aboutPhotos[0]['alt'], 'arch__img ' . ($aboutPhotos[0]['class'] ?? '')) ?>
<?php elseif ($aboutCarousel): ?>
                    <ul class="about__track" id="about-track" data-carousel-track tabindex="0" aria-label="Fotografie Ľudmily Dolinskej – posúvajte do strán">
<?php foreach ($aboutPhotos as $i => $p): ?>
                        <li class="about__slide" aria-label="<?= $i + 1 ?> z <?= count($aboutPhotos) ?>"><?= photo($p['file'], $p['alt'], $p['alt'], 'arch__img ' . ($p['class'] ?? '')) ?></li>
<?php endforeach; ?>
                    </ul>
<?php else: ?>
                    <?= photo('ludmila-dolinska', 'Ľudmila Dolinská, masérka', 'Portrétna fotografia – Ľudmila Dolinská', 'arch__img') ?>
<?php endif; ?>
                </div>
                <div class="about__years" aria-hidden="true">
                    <span class="about__years-num"><?= (int) config('business.experience_years') ?></span>
                    <span class="about__years-text">rokov<br>skúseností</span>
                </div>
            </div>
<?php if ($aboutCarousel): ?>
            <?= carousel_controls('about-track', 'Predchádzajúca fotografia', 'Ďalšia fotografia', 'about__controls') ?>
<?php endif; ?>
        </div>

        <div class="about__content reveal">
            <p class="eyebrow">O mne</p>
            <h2 class="section-title" id="about-title">Dobrý deň, som <em>Ľudmila Dolinská</em></h2>
            <p class="about__lead">Radi vám čoskoro predstavíme Ľudmilu Dolinskú a&nbsp;priblížime vám jej prácu, skúsenosti a&nbsp;prístup k&nbsp;masážam.</p>
            <p class="about__text">Informácie budú zverejnené neskôr.</p>
        </div>
    </div>
</section>

<!-- ============================== MASÁŽE ============================== -->
<section class="section services" id="masaze" aria-labelledby="services-title">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow">Ponuka</p>
            <h2 class="section-title" id="services-title">Masáže na mieru <em>vášmu telu</em></h2>
            <p class="section-head__lead">Vyberte si z klasických aj špeciálnych techník. Ak si nie ste istí, ktorá masáž je pre vás vhodná, rada vám poradím pri objednaní.</p>
        </header>

        <div class="services__carousel reveal" data-carousel role="region" aria-roledescription="carousel" aria-label="Ponuka masáží">
        <ul class="services__track" id="services-track" data-carousel-track tabindex="0" aria-label="Zoznam masáží – posúvajte do strán">
<?php foreach ($services as $slug => $service):
    // Prednosť má fotka sluzby/<slug>, inak priradená fotka z kľúča 'image', inak zástupný obrázok.
    $image = 'sluzby/' . $slug;
    if (find_image($image) === null && !empty($service['image']) && find_image($service['image']) !== null) {
        $image = $service['image'];
    }
?>
            <li class="service-card">
                <div class="service-card__media">
                    <?= photo($image, $service['name'], $service['name']) ?>
                </div>
                <div class="service-card__body">
                    <h3 class="service-card__title"><?= e($service['name']) ?></h3>
<?php if ($service['duration']): ?>
                    <ul class="service-card__durations" aria-label="Dĺžka trvania">
<?php foreach ($service['duration'] as $d): ?>
                        <li><?= e($d) ?></li>
<?php endforeach; ?>
                    </ul>
<?php endif; ?>
                    <p class="service-card__desc"><?= e($service['desc']) ?></p>
<?php if (!empty($service['prices'])): ?>
                    <dl class="service-card__prices" aria-label="Cena – <?= e($service['name']) ?>">
<?php foreach ($service['prices'] as $price): ?>
                        <div class="service-card__price">
                            <dt><?= e($price['time']) ?><?php if (!empty($price['note'])): ?> <span class="service-card__price-note"><?= e($price['note']) ?></span><?php endif; ?></dt>
                            <dd><?php if (!empty($price['from'])): ?><span class="service-card__price-from">od</span> <?php endif; ?><?= (int) $price['price'] ?>&nbsp;€</dd>
                        </div>
<?php endforeach; ?>
                    </dl>
<?php endif; ?>
                    <a class="link-arrow" href="?masaz=<?= e($slug) ?>#kontakt" data-book="<?= e($slug) ?>">
                        Objednať sa<span class="visually-hidden"> – <?= e($service['name']) ?></span>
                    </a>
                </div>
            </li>
<?php endforeach; ?>
            <li class="service-card service-card--cta">
                <?= lotus_svg('service-card__cta-icon') ?>
                <h3 class="service-card__title">Neviete si vybrať?</h3>
                <p class="service-card__desc">Zavolajte mi alebo napíšte. Spoločne nájdeme masáž, ktorá vám bude najviac vyhovovať.</p>
                <a class="btn btn--light btn--sm" href="tel:<?= e(biz('phone_intl')) ?>"><?= e(biz('phone')) ?></a>
                <a class="service-card__cta-link" href="#kontakt">
                    Objednať sa
                    <svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v15M6 13l6 6 6-6"/></svg>
                </a>
            </li>
        </ul>
        <?= carousel_controls('services-track', 'Predchádzajúce masáže', 'Ďalšie masáže', 'services__controls') ?>
        </div>
        <p class="services__note">Zľavy na masáže 30 – 60 min: <strong>ISIC karta 15 %</strong> · <strong>ZTP karta 10 %</strong> · <strong>karta dôchodcu 20 %</strong></p>
    </div>
</section>

<!-- ============================== PERMANENTKY ============================== -->
<section class="section passes" id="permanentky" aria-labelledby="passes-title">
    <?= lotus_svg('passes__watermark') ?>
    <div class="container">
        <header class="section-head section-head--light reveal">
            <p class="eyebrow">Vernostný program</p>
            <h2 class="section-title" id="passes-title">Permanentky – <em>pravidelný oddych sa oplatí</em></h2>
            <p class="section-head__lead">Masáž funguje najlepšie, keď sa stane súčasťou vášho režimu. S permanentkou si pravidelnú starostlivosť doprajete výhodnejšie – a ak chcete, môžete ju darovať alebo zdieľať s blízkymi.</p>
        </header>

        <div class="passes__grid">
            <article class="pass-card pass-card--featured reveal" aria-labelledby="pass10-title">
                <p class="pass-card__ribbon">Najvýhodnejšie</p>
                <p class="pass-card__count" aria-hidden="true">10</p>
                <h3 class="pass-card__title" id="pass10-title">Permanentka na 10 vstupov</h3>
                <p class="pass-card__discount"><span>14 %</span> zľava</p>
                <ul class="pass-card__list">
                    <li>Najvyššia zľava z ponuky</li>
                    <li>Možnosť zdieľať permanentku s blízkymi</li>
                </ul>
                <a class="btn btn--primary btn--block" href="?masaz=permanentka-10#kontakt" data-book="permanentka-10">Mám záujem</a>
            </article>

            <article class="pass-card reveal" aria-labelledby="pass5-title">
                <p class="pass-card__count" aria-hidden="true">5</p>
                <h3 class="pass-card__title" id="pass5-title">Permanentka na 5 vstupov</h3>
                <p class="pass-card__discount"><span>5 %</span> zľava</p>
                <ul class="pass-card__list">
                    <li>Výhodný začiatok pravidelnej starostlivosti</li>
                </ul>
                <a class="btn btn--outline-light btn--block" href="?masaz=permanentka-5#kontakt" data-book="permanentka-5">Mám záujem</a>
            </article>
        </div>
    </div>
</section>

<!-- ============================== PREČO ============================== -->
<section class="section why" aria-labelledby="why-title">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow">Prečo práve sem</p>
            <h2 class="section-title" id="why-title">Prečo si vybrať <em>Masáže v Sásovej</em></h2>
        </header>

        <ul class="why__grid">
            <li class="why__item reveal">
                <svg class="why__icon" aria-hidden="true" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="24" cy="24" r="17"/><path d="M24 13v11l7 5"/><path d="M24 4v3M24 41v3M4 24h3M41 24h3"/></svg>
                <h3 class="why__title"><?= (int) config('business.experience_years') ?> rokov skúseností</h3>
                <p>Dlhoročná prax a odborné zdravotnícke vzdelanie. Viete, že ste v istých rukách.</p>
            </li>
            <li class="why__item reveal">
                <svg class="why__icon" aria-hidden="true" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M24 41s-14-8.4-14-19a8 8 0 0 1 14-5.3A8 8 0 0 1 38 22c0 10.6-14 19-14 19Z"/><path d="M18 21.5a5 5 0 0 1 4-4"/></svg>
                <h3 class="why__title">Individuálny prístup</h3>
                <p>Techniku, tlak aj dĺžku masáže prispôsobím vašim potrebám a tomu, ako sa práve cítite.</p>
            </li>
            <li class="why__item reveal">
                <svg class="why__icon" aria-hidden="true" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="7" width="14" height="14" rx="3"/><rect x="27" y="7" width="14" height="14" rx="7"/><rect x="7" y="27" width="14" height="14" rx="7"/><rect x="27" y="27" width="14" height="14" rx="3"/></svg>
                <h3 class="why__title">Široká ponuka techník</h3>
                <p>Od klasickej a športovej masáže cez lymfodrenáž a bankovanie až po maderoterapiu.</p>
            </li>
            <li class="why__item reveal">
                <svg class="why__icon" aria-hidden="true" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><path d="M11 37C11 21 21 11 38 10c0 17-10 27-27 27Z"/><path d="M11 37 27 21"/><path d="M19 29h6M23 25v-6"/></svg>
                <h3 class="why__title">Príjemné prostredie</h3>
                <p>Pokojné miesto, kde môžete na chvíľu vypnúť, nadýchnuť sa a venovať sa len sebe.</p>
            </li>
        </ul>
    </div>
</section>

<!-- ============================== GALÉRIA ============================== -->
<section class="section gallery-section" id="galeria" aria-labelledby="gallery-title">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow">Galéria</p>
            <h2 class="section-title" id="gallery-title">Nahliadnite <em>k nám</em></h2>
        </header>

<?php if ($gallery): ?>
        <div class="gallery-carousel reveal" data-carousel role="region" aria-roledescription="carousel" aria-label="Fotografie zo štúdia">
            <ul class="gallery-carousel__track" id="gallery-track" data-carousel-track tabindex="0" aria-label="Fotografie zo štúdia – posúvajte do strán">
<?php foreach ($gallery as $i => $img): ?>
                <li class="gallery-carousel__slide">
                    <a href="<?= e($img['src']) ?>" class="gallery-carousel__link" data-lightbox data-index="<?= $i ?>">
                        <img src="<?= e($img['src']) ?>" alt="<?= e($img['alt']) ?>"<?= image_size_attrs($img['src']) ?> loading="lazy" decoding="async">
                        <span class="gallery-carousel__zoom" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><circle cx="10.5" cy="10.5" r="6" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="M15 15l5 5M10.5 8v5M8 10.5h5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
                        <span class="visually-hidden"> – zväčšiť fotografiu</span>
                    </a>
                </li>
<?php endforeach; ?>
            </ul>
            <?= carousel_controls('gallery-track', 'Predchádzajúce fotografie', 'Ďalšie fotografie', 'gallery-carousel__controls') ?>
        </div>
<?php else: ?>
        <ul class="gallery gallery--placeholders">
<?php foreach ($gallerySlots as $slot): ?>
            <li class="gallery__item <?= e($slot['class']) ?> reveal">
<?php if (!empty($slot['image']) && find_image($slot['image']) !== null): ?>
                <?= photo($slot['image'], $slot['alt'] ?? $slot['label'], $slot['label']) ?>
<?php else: ?>
                <?= photo('galeria/', '', $slot['label']) ?>
<?php endif; ?>
            </li>
<?php endforeach; ?>
        </ul>
        <p class="gallery__hint">Fotografie z prevádzky budú doplnené čoskoro.</p>
<?php endif; ?>
    </div>
</section>

<!-- ============================== KONTAKT ============================== -->
<section class="section contact" id="kontakt" aria-labelledby="contact-title">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow">Kontakt a objednávky</p>
            <h2 class="section-title" id="contact-title">Objednajte sa <em>na masáž</em></h2>
        </header>

        <div class="contact__grid">
            <div class="contact__info reveal">
                <div class="contact__buttons">
                    <a class="contact-btn contact-btn--primary" href="tel:<?= e(biz('phone_intl')) ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h3.5l1.7 4.3-2.2 1.4a11 11 0 0 0 6.3 6.3l1.4-2.2L20 15.5V19a1.5 1.5 0 0 1-1.6 1.5C10.6 20 4 13.4 3.5 5.6A1.5 1.5 0 0 1 5 4Z"/></svg>
                        <span><strong>Zavolať</strong><small><?= e(biz('phone')) ?></small></span>
                    </a>
                    <a class="contact-btn" href="<?= e(biz('whatsapp')) ?>" target="_blank" rel="noopener">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20l1.3-3.9A8.5 8.5 0 1 1 8 19l-4 1Z"/><path d="M9 8.5c0 3.5 2.9 6.5 6.5 6.5l1-1.6-2-1-1 .9a4 4 0 0 1-2.3-2.3l.9-1-1-2L9.5 8"/></svg>
                        <span><strong>WhatsApp</strong><small>Napíšte správu</small></span>
                    </a>
                    <a class="contact-btn" href="mailto:<?= e(biz('email')) ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/></svg>
                        <span><strong>E-mail</strong><small><?= e(biz('email')) ?></small></span>
                    </a>
                    <a class="contact-btn" href="<?= e(biz('facebook')) ?>" target="_blank" rel="noopener">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z"/></svg>
                        <span><strong>Facebook</strong><small>Masáže v Sásovej</small></span>
                    </a>
                </div>

                <dl class="contact__details">
                    <div>
                        <dt>Adresa</dt>
                        <dd>
                            <address><?= e(biz('street')) ?>, <?= e(biz('street_note')) ?><br><?= e(biz('city')) ?> – <?= e(biz('district')) ?></address>
                        </dd>
                    </div>
                    <div>
                        <dt>Kontaktná osoba</dt>
                        <dd><?= e(biz('person')) ?></dd>
                    </div>
                </dl>
            </div>

            <div class="contact__form-wrap reveal">
                <form class="contact-form booking-form" action="send-form.php" method="post" novalidate data-contact-form data-mode="<?= e(booking_mode()) ?>"<?= $formDone ? ' hidden' : '' ?>>
                    <div class="booking-form__head">
                        <h3 class="contact-form__title">Objednávkový formulár</h3>
                    </div>
                    <p class="booking-form__intro">Vyplňte údaje a vyberte si termín v kalendári. Polia označené <span class="req" aria-hidden="true">*</span><span class="visually-hidden">hviezdičkou</span> sú povinné.</p>

                    <div class="form-status" data-form-status aria-live="polite">
<?php if (!empty($form['message']) && !$formDone): ?>
                        <p class="form-notice form-notice--<?= ($form['status'] ?? '') === 'success' ? 'success' : 'error' ?>" tabindex="-1" data-focus-on-load><?= e($form['message']) ?></p>
<?php endif; ?>
                    </div>

                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="form_ts" value="<?= e(form_timestamp()) ?>">
                    <div class="hp-field" aria-hidden="true">
                        <label for="f-website">Nevypĺňajte toto pole</label>
                        <input type="text" id="f-website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <fieldset class="booking-form__group">
                        <legend class="booking-form__legend"><span class="booking-form__step" aria-hidden="true">1</span>Kontaktné údaje</legend>
                        <div class="field<?= isset($errors['name']) ? ' has-error' : '' ?>">
                            <label for="f-name">Meno a priezvisko <span class="req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-name" name="name" required maxlength="100" autocomplete="name" placeholder="napr. Jana Nováková"
                                   value="<?= e($old['name'] ?? '') ?>"<?= isset($errors['name']) ? ' aria-invalid="true" aria-describedby="f-name-err"' : '' ?>>
                            <p class="field__error" id="f-name-err" data-error-for="name"><?= e($errors['name'] ?? '') ?></p>
                        </div>
                        <div class="form-row form-row--2">
                            <div class="field<?= isset($errors['phone']) ? ' has-error' : '' ?>">
                                <label for="f-phone">Telefónne číslo <span class="req" aria-hidden="true">*</span></label>
                                <input type="tel" id="f-phone" name="phone" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="0915 123 456"
                                       value="<?= e($old['phone'] ?? '') ?>"<?= isset($errors['phone']) ? ' aria-invalid="true" aria-describedby="f-phone-err"' : '' ?>>
                                <p class="field__error" id="f-phone-err" data-error-for="phone"><?= e($errors['phone'] ?? '') ?></p>
                            </div>
                            <div class="field<?= isset($errors['email']) ? ' has-error' : '' ?>">
                                <label for="f-email">E-mailová adresa <span class="req" aria-hidden="true">*</span></label>
                                <input type="email" id="f-email" name="email" required maxlength="150" autocomplete="email" inputmode="email" placeholder="meno@gmail.com"
                                       value="<?= e($old['email'] ?? '') ?>"<?= isset($errors['email']) ? ' aria-invalid="true" aria-describedby="f-email-err"' : '' ?>>
                                <p class="field__error" id="f-email-err" data-error-for="email"><?= e($errors['email'] ?? '') ?></p>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="booking-form__group">
                        <legend class="booking-form__legend"><span class="booking-form__step" aria-hidden="true">2</span><span data-order-legend><?= $isPass ? 'Masáž a permanentka' : 'Masáž a termín' ?></span></legend>

                        <fieldset class="order-type" data-order-type>
                            <legend class="order-type__legend">Typ objednávky <span class="req" aria-hidden="true">*</span></legend>
                            <div class="order-type__options">
                                <label class="order-type__option">
                                    <input type="radio" name="order_type" value="single"<?= $isPass ? '' : ' checked' ?>>
                                    <span class="order-type__card">
                                        <span class="order-type__name">Jednorazová masáž</span>
                                        <span class="order-type__desc">Termín si vyberiete v kalendári</span>
                                    </span>
                                </label>
                                <label class="order-type__option">
                                    <input type="radio" name="order_type" value="pass"<?= $isPass ? ' checked' : '' ?>>
                                    <span class="order-type__card">
                                        <span class="order-type__name">Permanentka</span>
                                        <span class="order-type__desc">Termíny dohodneme osobne</span>
                                    </span>
                                </label>
                            </div>
                        </fieldset>

                        <div class="field<?= isset($errors['pass_type']) ? ' has-error' : '' ?>" data-order="pass">
                            <label for="f-pass">Typ permanentky <span class="req" aria-hidden="true">*</span></label>
                            <select id="f-pass" name="pass_type" required<?= isset($errors['pass_type']) ? ' aria-invalid="true" aria-describedby="f-pass-err"' : '' ?>>
                                <option value="">Vyberte permanentku</option>
<?php foreach ($passOptions as $value => $label): ?>
                                <option value="<?= e($value) ?>"<?= $selectedPass === $value ? ' selected' : '' ?>><?= e($label) ?><?= booking_pass_discount($value) ? ' – zľava ' . e(booking_pass_discount($value)) : '' ?></option>
<?php endforeach; ?>
                            </select>
                            <p class="field__error" id="f-pass-err" data-error-for="pass_type"><?= e($errors['pass_type'] ?? '') ?></p>
                        </div>

                        <div class="field<?= isset($errors['service']) ? ' has-error' : '' ?>">
                            <label for="f-service"><span data-service-label><?= $isPass ? 'Masáž, ktorú chcete s permanentkou využívať' : 'Výber masáže' ?></span> <span class="req" aria-hidden="true">*</span></label>
                            <select id="f-service" name="service" required data-service-select<?= isset($errors['service']) ? ' aria-invalid="true" aria-describedby="f-service-err"' : '' ?>>
                                <option value="">Vyberte masáž</option>
<?php foreach (array_diff_key(booking_service_groups(), ['Permanentky' => true]) as $groupLabel => $options): ?>
                                <optgroup label="<?= e($groupLabel) ?>">
<?php foreach ($options as $value => $label): ?>
                                    <option value="<?= e($value) ?>"<?= $selectedService === $value ? ' selected' : '' ?>><?= e($label) ?></option>
<?php endforeach; ?>
                                </optgroup>
<?php endforeach; ?>
                            </select>
<?php $serviceInfo = $selectedService !== '' && !$isPass ? booking_service_info($selectedService) : null; ?>
                            <dl class="service-info" data-service-info aria-live="polite"<?= $serviceInfo ? '' : ' hidden' ?>>
                                <div><dt>Dĺžka</dt><dd data-service-info-length><?= e($serviceInfo['lengths'] ?? '') ?></dd></div>
                                <div><dt>Cena</dt><dd data-service-info-price><?= e($serviceInfo['price'] ?? '') ?></dd></div>
                            </dl>
                            <p class="field__error" id="f-service-err" data-error-for="service"><?= e($errors['service'] ?? '') ?></p>
                        </div>

                        <div class="field<?= isset($errors['duration']) ? ' has-error' : '' ?>" data-duration-field data-order="single">
                            <label for="f-duration">Dĺžka masáže <span class="req" aria-hidden="true">*</span></label>
                            <select id="f-duration" name="duration" data-duration-select data-lengths="<?= e(json_encode(booking_lengths_map(), JSON_UNESCAPED_UNICODE)) ?>"<?= isset($errors['duration']) ? ' aria-invalid="true" aria-describedby="f-duration-err"' : '' ?>>
                                <option value="">Vyberte dĺžku</option>
<?php foreach (booking_service_groups()['Masáže'] ?? [] as $slug => $label): ?>
<?php $lens = booking_service_lengths($slug); if (!$lens) { continue; } ?>
                                <optgroup label="<?= e($label) ?>">
<?php foreach ($lens as $len): ?>
                                    <option value="<?= (int) $len['minutes'] ?>"><?= e($len['label']) ?></option>
<?php endforeach; ?>
                                </optgroup>
<?php endforeach; ?>
                            </select>
                            <p class="field__hint" data-duration-note hidden>Pri tejto voľbe dĺžku masáže dohodneme individuálne – dostupnosť termínu preto nevieme vopred overiť.</p>
                            <p class="field__error" id="f-duration-err" data-error-for="duration"><?= e($errors['duration'] ?? '') ?></p>
                        </div>

                        <div class="field field--calendar<?= isset($errors['date']) || isset($errors['time']) ? ' has-error' : '' ?>" data-order="single">
                            <p class="field__label" id="f-term-label">Termín <span class="req" aria-hidden="true">*</span> <span class="field__label-note">vyberte jeden deň a jeden čas</span></p>

                            <!-- Náhradné polia bez JavaScriptu. S JavaScriptom ich nahradí kalendár (hodnoty sa zapisujú sem). -->
                            <div class="booking-fallback" data-booking-fallback>
                                <p class="booking-fallback__warning" role="note">
                                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 2.5 20h19L12 3Z"/><path d="M12 10v4M12 17h.01"/></svg>
                                    <span><strong>Dostupnosť termínov sa nedá zobraziť</strong>, pretože máte vo svojom prehliadači vypnutý JavaScript.
                                    Zadajte požadovaný deň a čas – <strong>dostupnosť overíme až po odoslaní</strong>. Zoznam časov nižšie neznamená, že sú voľné.
                                    Termín si môžete dohodnúť aj telefonicky na čísle <a href="tel:<?= e(biz('phone_intl')) ?>"><?= e(biz('phone')) ?></a>.</span>
                                </p>
                                <div class="form-row form-row--datetime">
                                    <div>
                                        <label for="f-date">Požadovaný dátum</label>
                                        <input type="date" id="f-date" name="date" required min="<?= e($dateMin) ?>" max="<?= e($dateMax) ?>"
                                               value="<?= e($old['date'] ?? '') ?>"<?= isset($errors['date']) ? ' aria-invalid="true" aria-describedby="f-date-err"' : '' ?>>
                                    </div>
                                    <div>
                                        <label for="f-time">Požadovaný začiatok (neoverený)</label>
                                        <select id="f-time" name="time" required<?= isset($errors['time']) ? ' aria-invalid="true" aria-describedby="f-time-err"' : '' ?>>
                                            <option value="">Vyberte čas</option>
<?php foreach ($timeSlots as $slot): ?>
                                            <option value="<?= e($slot) ?>"<?= ($old['time'] ?? '') === $slot ? ' selected' : '' ?>><?= e($slot) ?></option>
<?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="booking-cal" data-booking-calendar hidden
                                 data-min="<?= e($dateMin) ?>" data-max="<?= e($dateMax) ?>"
                                 data-step="<?= booking_time_step() ?>" data-buffer="<?= booking_buffer_minutes() ?>"
                                 data-tz="Europe/Bratislava"<?= booking_schedule_is_demo() ? " data-demo-schedule" : "" ?> data-server-now="<?= e(booking_now()->format('Y-m-d\TH:i')) ?>"
                                 data-days="<?= e(json_encode((object) booking_calendar_data())) ?>">
                                <div class="booking-cal__month">
                                    <div class="booking-cal__nav">
                                        <button class="booking-cal__arrow" type="button" data-cal-prev aria-label="Predchádzajúci mesiac">
                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                        <p class="booking-cal__title" data-cal-title aria-live="polite"></p>
                                        <button class="booking-cal__arrow" type="button" data-cal-next aria-label="Nasledujúci mesiac">
                                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </button>
                                    </div>
                                    <div class="booking-cal__weekdays" aria-hidden="true">
                                        <span>Po</span><span>Ut</span><span>St</span><span>Št</span><span>Pi</span><span>So</span><span>Ne</span>
                                    </div>
                                    <div class="booking-cal__days" data-cal-days role="group" aria-labelledby="f-term-label"></div>
                                    <ul class="booking-cal__legend" aria-label="Vysvetlivky">
                                        <li data-legend="free"><span class="booking-cal__swatch booking-cal__swatch--free"></span>Voľné</li>
                                        <li data-legend="unknown" hidden><span class="booking-cal__swatch booking-cal__swatch--unknown"></span>Pracovný deň – dostupnosť neoverená</li>
                                        <li data-legend="busy"><span class="booking-cal__swatch booking-cal__swatch--busy"></span>Obsadené<?= booking_is_demo() ? ' (ukážka)' : '' ?></li>
                                        <li><span class="booking-cal__swatch booking-cal__swatch--off"></span>Nepracovný deň / uplynulé</li>
                                    </ul>
                                </div>

                                <div class="booking-cal__times">
                                    <p class="booking-cal__times-title" data-cal-times-title>Dostupné časy</p>
                                    <p class="booking-cal__hours" data-cal-hours hidden></p>
                                    <p class="booking-cal__times-empty" data-cal-times-empty>Najprv vyberte v kalendári deň.</p>
                                    <div class="booking-cal__slots" data-cal-slots role="group" aria-label="Dostupné časy"></div>
                                    <p class="booking-cal__summary" data-cal-summary aria-live="polite" hidden></p>
                                </div>
                            </div>

                            <p class="field__error" id="f-date-err" data-error-for="date"><?= e($errors['date'] ?? '') ?></p>
                            <p class="field__error" id="f-time-err" data-error-for="time"><?= e($errors['time'] ?? '') ?></p>
                        </div>

                        <p class="booking-form__hint booking-form__hint--pass" data-order="pass" role="note">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                            <span><?= e(booking_pass_notice()) ?></span>
                        </p>
                    </fieldset>

                    <fieldset class="booking-form__group booking-form__group--optional">
                        <legend class="booking-form__legend"><span class="booking-form__step" aria-hidden="true">3</span>Poznámka <span class="booking-form__optional">nepovinné</span></legend>
                        <div class="field<?= isset($errors['note']) ? ' has-error' : '' ?>">
                            <label for="f-note">Poznámka pre masérku</label>
                            <textarea id="f-note" name="note" rows="4" maxlength="2000" placeholder="napr. citlivé miesta, preferovaná intenzita, otázky…"<?= isset($errors['note']) ? ' aria-invalid="true" aria-describedby="f-note-err"' : '' ?>><?= e($old['note'] ?? '') ?></textarea>
                            <p class="field__error" id="f-note-err" data-error-for="note"><?= e($errors['note'] ?? '') ?></p>
                        </div>
                    </fieldset>

                    <p class="contact-form__privacy">
<?php if (booking_is_demo()): ?>
                        Toto je ukážka objednávkového formulára – vyplnené údaje sa nikam neodosielajú ani neukladajú.
<?php else: ?>
                        Údaje z formulára použijem iba na dohodnutie termínu a odpoveď na vašu požiadavku.
<?php endif; ?>
                        Viac v časti <a href="ochrana-osobnych-udajov.php">Ochrana osobných údajov</a>.
                    </p>

                    <div class="contact-form__footer">
                        <p class="contact-form__required"><span class="req" aria-hidden="true">*</span> povinné polia</p>
                        <button class="btn btn--primary booking-form__submit" type="submit" data-submit>
                            <span data-submit-label>Odoslať požiadavku</span>
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                        </button>
                    </div>
                </form>

<?php if (booking_is_demo()): ?>
                <div class="booking-done" data-booking-done tabindex="-1" role="status"<?= $formDone ? ' data-focus-on-load' : ' hidden' ?>>
                    <span class="booking-done__icon" aria-hidden="true"><?= lotus_svg() ?></span>
                    <h3 class="booking-done__title">Ďakujeme za váš záujem o naše masážne služby.</h3>
<?php $doneSummary = $formDone ? (array) ($form['summary'] ?? []) : []; ?>
                    <div class="booking-done__summary" data-booking-done-summary<?= !empty($doneSummary['kind']) ? '' : ' hidden' ?>>
                        <p class="booking-done__kind" data-booking-done-kind><?= e((string) ($doneSummary['kind'] ?? '')) ?></p>
                        <ul class="booking-done__lines" data-booking-done-lines>
<?php foreach ((array) ($doneSummary['lines'] ?? []) as $line): ?>
                            <li><?= e((string) $line) ?></li>
<?php endforeach; ?>
                        </ul>
                    </div>
                    <p class="booking-done__text" data-booking-done-text>
                        <?= e(trim(substr(booking_demo_message(), strlen('Ďakujeme za váš záujem o naše masážne služby.')))) ?>
                    </p>
                    <div class="booking-done__actions">
                        <a class="btn btn--primary" href="tel:<?= e(biz('phone_intl')) ?>"><?= e(biz('phone')) ?></a>
                        <a class="btn btn--ghost" href="index.php#kontakt" data-booking-again>Vyplniť formulár znova</a>
                    </div>
                </div>
<?php endif; ?>
            </div>
        </div>

        <div class="map reveal" id="mapa">
            <div class="map__frame" data-map
                 data-src="https://www.google.com/maps?q=<?= rawurlencode(biz('street') . ', ' . biz('city')) ?>&amp;z=16&amp;output=embed">
                <div class="map__consent">
                    <?= lotus_svg('map__icon') ?>
                    <p class="map__address"><strong><?= e(biz('street')) ?></strong><br><?= e(biz('street_note')) ?><br><?= e(biz('city')) ?> – <?= e(biz('district')) ?></p>
                    <p class="map__text">Mapa sa načíta zo služby Google Maps. Po jej zobrazení môže Google spracúvať údaje o vašom zariadení.</p>
                    <div class="map__actions">
                        <button class="btn btn--primary btn--sm" type="button" data-map-load>Zobraziť mapu</button>
                        <a class="btn btn--ghost btn--sm" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode(biz('street') . ', ' . biz('city')) ?>" target="_blank" rel="noopener">Otvoriť v Google Maps</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Lightbox galérie -->
<dialog class="lightbox" data-lightbox-dialog aria-label="Zväčšená fotografia">
    <button class="lightbox__close" type="button" data-lightbox-close aria-label="Zavrieť">×</button>
    <button class="lightbox__nav lightbox__nav--prev" type="button" data-lightbox-prev aria-label="Predchádzajúca fotografia">‹</button>
    <img class="lightbox__img" src="data:," alt="" data-lightbox-img>
    <button class="lightbox__nav lightbox__nav--next" type="button" data-lightbox-next aria-label="Ďalšia fotografia">›</button>
</dialog>

<?php require __DIR__ . '/includes/footer.php'; ?>
