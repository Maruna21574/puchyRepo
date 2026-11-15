<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

$page = [
    'title'       => 'Ochrana osobných údajov | Masáže v Sásovej',
    'description' => 'Informácie o spracúvaní osobných údajov na webe Masáže v Sásovej.',
    'path'        => 'ochrana-osobnych-udajov.php',
    'is_home'     => false,
];

require __DIR__ . '/includes/header.php';
?>

<article class="section legal">
    <div class="container container--narrow">
        <p class="eyebrow">Informácie pre návštevníkov</p>
        <h1 class="section-title">Ochrana osobných údajov</h1>

        <!--
            POZOR: Tento text je PRACOVNÝ NÁVRH, nie právne overený dokument.
            Pred zverejnením ho musí skontrolovať a doplniť klientka alebo právnik.
            Miesta označené [DOPLNIŤ] treba vyplniť skutočnými údajmi.
        -->
        <p class="form-notice form-notice--info">
            Tento dokument sa pripravuje. Úplné znenie doplníme po právnej kontrole.
        </p>

        <h2>Kto spracúva vaše údaje</h2>
        <p>
            Prevádzkovateľ: <?= e(biz('person')) ?>, <?= e(biz('name')) ?><br>
            Sídlo / miesto podnikania: [DOPLNIŤ]<br>
            IČO: [DOPLNIŤ]<br>
            Kontakt: <a href="mailto:<?= e(biz('email')) ?>"><?= e(biz('email')) ?></a>, <a href="tel:<?= e(biz('phone_intl')) ?>"><?= e(biz('phone')) ?></a>
        </p>

        <h2>Kontaktný formulár</h2>
        <p>
            Ak nám napíšete cez kontaktný formulár, údaje, ktoré vyplníte (meno, telefón, e-mail, vybraná masáž,
            preferovaný termín a text správy), sa odošlú e-mailom prevádzkovateľke. Použijú sa iba na odpoveď
            na vašu správu a na dohodnutie termínu.
        </p>
        <p>
            Web údaje z formulára neukladá do žiadnej databázy. Ako dlho sa uchovávajú prijaté e-maily: [DOPLNIŤ].
        </p>
        <p>Právny základ spracúvania: [DOPLNIŤ po právnej konzultácii].</p>

        <h2 id="cookies">Cookies</h2>
        <p>
            Nevyhnutné cookies sú vždy aktívne: <code>mvs_session</code> slúži na bezpečné odoslanie kontaktného
            formulára a po zatvorení prehliadača zaniká, <code>mvs_consent</code> si na 6 mesiacov pamätá vašu voľbu cookies.
        </p>
<?php if (trim((string) config('analytics_id')) !== ''): ?>
        <p>
            Analytické cookies sa použijú len vtedy, ak s nimi vyjadríte súhlas v cookie lište. Marketingové cookies web nepoužíva.
            Svoju voľbu môžete kedykoľvek zmeniť cez odkaz „Nastavenia cookies“ v pätičke stránky.
        </p>
<?php else: ?>
        <p>
            Analytické, reklamné ani iné nepovinné cookies web nepoužíva. Prehľad cookies nájdete aj cez odkaz
            „Nastavenia cookies“ v pätičke stránky.
        </p>
<?php endif; ?>

        <h2>Mapa Google</h2>
        <p>
            Mapa s polohou prevádzky sa načíta zo služby Google Maps až po kliknutí na tlačidlo
            „Zobraziť mapu“. Dovtedy sa do služieb Google neodosielajú žiadne údaje.
        </p>

        <h2>Vaše práva</h2>
        <p>[DOPLNIŤ – práva dotknutej osoby a kontakt na Úrad na ochranu osobných údajov SR po právnej kontrole.]</p>

        <p><a class="link-arrow" href="index.php">Späť na úvod</a></p>
    </div>
</article>

<?php require __DIR__ . '/includes/footer.php'; ?>
