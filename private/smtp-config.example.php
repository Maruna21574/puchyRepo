<?php
/**
 * VZOR SMTP KONFIGURÁCIE
 *
 * 1. Skopírujte tento súbor ako  private/smtp-config.php  (v rovnakom priečinku).
 * 2. Doplňte údaje e-mailovej schránky (napr. info@vasadomena.sk) z administrácie Websupportu.
 * 3. Nastavte 'enabled' => true.
 *
 * Súbor smtp-config.php NIKDY nedávajte do verejného priečinka web/ ani do gitu.
 *
 * Websupport (overte aktuálne údaje v administrácii → E-maily → Nastavenie klienta):
 *   server: smtp.m1.websupport.sk, port 465 + 'ssl'   (alebo port 587 + 'tls')
 *
 * Dôležité: 'from_email' musí byť schránka na doméne, ktorej SMTP používate
 * (napr. info@masazvsasovej.sk). Gmail adresu ako odosielateľa použiť nemožno –
 * správy by končili v spame alebo by ich server odmietol. Odpovedať návštevníkovi
 * pôjde aj tak priamo – adresa z formulára sa nastaví ako „Reply-To“.
 */
declare(strict_types=1);

return [
    'enabled'    => false,

    'host'       => '',        // napr. 'smtp.m1.websupport.sk'
    'port'       => 465,
    'encryption' => 'ssl',     // 'ssl' (port 465) alebo 'tls' (port 587)
    'username'   => '',        // celá e-mailová adresa schránky
    'password'   => '',        // heslo k schránke

    'from_email' => '',        // rovnaká adresa ako username
    'from_name'  => 'Web – Masáže v Sásovej',
    'to_email'   => 'ludmila.lilus@gmail.com', // kam majú správy z formulára chodiť

    // Cesta ku knižnici PHPMailer (predvolene private/lib/PHPMailer)
    'phpmailer_path' => __DIR__ . '/lib/PHPMailer',
];
