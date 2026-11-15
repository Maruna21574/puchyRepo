<?php
declare(strict_types=1);
$nav = !empty($page['is_home']) ? '' : 'index.php';
?>
</main>

<footer class="site-footer">
    <div class="container site-footer__grid">
        <div class="site-footer__brand">
            <a class="brand brand--light" href="<?= e($nav) ?>#uvod">
                <?= lotus_mark_svg('brand__mark') ?>
                <span class="brand__text">
                    <span class="brand__name">Masáže</span>
                    <span class="brand__sub">v Sásovej</span>
                </span>
            </a>
            <p class="site-footer__claim">Relax, regenerácia a uvoľnenie v príjemnom prostredí v Banskej Bystrici – Sásovej.</p>
        </div>

        <div>
            <h2 class="site-footer__title">Kontakt</h2>
            <ul class="site-footer__list">
                <li><a href="tel:<?= e(biz('phone_intl')) ?>"><?= e(biz('phone')) ?></a></li>
                <li><a href="mailto:<?= e(biz('email')) ?>"><?= e(biz('email')) ?></a></li>
                <li><a href="<?= e(biz('facebook')) ?>" target="_blank" rel="noopener">Facebook</a></li>
            </ul>
        </div>

        <div>
            <h2 class="site-footer__title">Adresa</h2>
            <address class="site-footer__address">
                <?= e(biz('street')) ?><br>
                <?= e(biz('street_note')) ?><br>
                <?= e(biz('city')) ?> – <?= e(biz('district')) ?>
            </address>
        </div>

        <nav aria-label="Navigácia v pätičke">
            <h2 class="site-footer__title">Navigácia</h2>
            <ul class="site-footer__list site-footer__list--cols">
                <li><a href="<?= e($nav) ?>#o-mne">O mne</a></li>
                <li><a href="<?= e($nav) ?>#masaze">Masáže</a></li>
                <li><a href="<?= e($nav) ?>#permanentky">Permanentky</a></li>
                <li><a href="<?= e($nav) ?>#galeria">Galéria</a></li>
                <li><a href="<?= e($nav) ?>#kontakt">Kontakt</a></li>
            </ul>
        </nav>
    </div>

    <div class="container site-footer__bottom">
        <p>© <?= date('Y') ?> <?= e(biz('name')) ?> · <?= e(biz('person')) ?></p>
        <p class="site-footer__links">
            <a href="ochrana-osobnych-udajov.php">Ochrana osobných údajov</a>
            <button class="site-footer__link-btn" type="button" data-cookie-open>Nastavenia cookies</button>
        </p>
    </div>
</footer>

<div class="mobile-bar" data-mobile-bar>
    <a class="mobile-bar__btn" href="tel:<?= e(biz('phone_intl')) ?>">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h3.5l1.7 4.3-2.2 1.4a11 11 0 0 0 6.3 6.3l1.4-2.2L20 15.5V19a1.5 1.5 0 0 1-1.6 1.5C10.6 20 4 13.4 3.5 5.6A1.5 1.5 0 0 1 5 4Z"/></svg>
        Zavolať
    </a>
    <a class="mobile-bar__btn mobile-bar__btn--primary" href="<?= e($nav) ?>#kontakt">Objednať sa</a>
</div>

<?php
// Nepovinné cookies existujú iba vtedy, keď je v configu nastavená analytika ('analytics_id').
// Bez nej lišta iba informuje o nevyhnutných cookies – nepredstiera súhlas s niečím, čo web nepoužíva.
$cookieOptional = trim((string) config('analytics_id')) !== '';
?>
<!-- Cookie lišta: zobrazí sa pri prvej návšteve (riadi assets/js/script.js). Analytika sa načíta až po súhlase. -->
<section class="cookie-bar" data-cookie-bar data-optional="<?= $cookieOptional ? '1' : '0' ?>" data-analytics-id="<?= e((string) config('analytics_id')) ?>" role="dialog" aria-modal="false" aria-labelledby="cookie-title" aria-describedby="cookie-text" hidden>
    <div class="cookie-bar__inner">
        <div class="cookie-bar__intro">
            <h2 class="cookie-bar__title" id="cookie-title"><?= lotus_svg('cookie-bar__icon') ?>Súbory cookies</h2>
            <p class="cookie-bar__text" id="cookie-text">
<?php if ($cookieOptional): ?>
                Na správne fungovanie webu používame nevyhnutné cookies. Analytické cookies nám pomáhajú zlepšovať stránku –
                použijeme ich len s vaším súhlasom.
<?php else: ?>
                Tento web používa iba nevyhnutné cookies potrebné na jeho fungovanie. Analytické ani reklamné cookies nepoužívame.
<?php endif; ?>
                <a href="ochrana-osobnych-udajov.php#cookies">Viac informácií</a>
            </p>
        </div>

        <div class="cookie-bar__settings" id="cookie-settings" data-cookie-settings hidden>
            <div class="cookie-option">
                <div class="cookie-option__text">
                    <p class="cookie-option__name">Nevyhnutné</p>
                    <p class="cookie-option__desc">Zabezpečujú základné fungovanie webu, napríklad bezpečné odoslanie formulára a zapamätanie vašej voľby cookies.</p>
                </div>
                <label class="cookie-switch">
                    <input type="checkbox" checked disabled>
                    <span class="cookie-switch__track" aria-hidden="true"></span>
                    <span class="cookie-switch__label">Vždy aktívne</span>
                </label>
            </div>
<?php if ($cookieOptional): ?>
            <div class="cookie-option">
                <div class="cookie-option__text">
                    <p class="cookie-option__name">Analytické</p>
                    <p class="cookie-option__desc">Anonymné štatistiky návštevnosti, vďaka ktorým vieme, ktoré časti webu sú užitočné.</p>
                </div>
                <label class="cookie-switch">
                    <input type="checkbox" data-cookie-analytics>
                    <span class="cookie-switch__track" aria-hidden="true"></span>
                    <span class="cookie-switch__label">Povoliť</span>
                </label>
            </div>
<?php else: ?>
            <p class="cookie-option__desc">Analytické, reklamné ani iné nepovinné cookies web nepoužíva.</p>
<?php endif; ?>
        </div>

        <div class="cookie-bar__actions">
            <button class="btn btn--ghost btn--sm" type="button" data-cookie-toggle aria-expanded="false" aria-controls="cookie-settings">Nastavenia</button>
<?php if ($cookieOptional): ?>
            <button class="btn btn--ghost btn--sm" type="button" data-cookie-reject>Odmietnuť nepovinné</button>
            <button class="btn btn--primary btn--sm" type="button" data-cookie-save hidden>Uložiť výber</button>
            <button class="btn btn--primary btn--sm" type="button" data-cookie-accept>Prijať všetky</button>
<?php else: ?>
            <button class="btn btn--primary btn--sm" type="button" data-cookie-reject>Rozumiem</button>
<?php endif; ?>
        </div>
    </div>
</section>

</body>
</html>
