<?php
/**
 * Verejná (necitlivá) konfigurácia webu.
 *
 * Citlivé údaje (SMTP heslo a pod.) sem NEPATRIA – sú v súbore
 * private/smtp-config.php mimo verejného adresára web/.
 */
declare(strict_types=1);

return [
    // Adresa webu bez lomky na konci, napr. 'https://www.masazvsasovej.sk'.
    // Ak je prázdne, adresa sa zistí automaticky z požiadavky (vhodné na lokálny vývoj).
    // PRED SPUSTENÍM doplňte skutočnú doménu (kvôli canonical URL, sitemap a Open Graph).
    'base_url' => '',

    'business' => [
        'name'        => 'Masáže v Sásovej',
        'person'      => 'Ľudmila Dolinská',
        'phone'       => '0915 139 575',
        'phone_intl'  => '+421915139575',
        'whatsapp'    => 'https://wa.me/421915139575',
        'email'       => 'ludmila.lilus@gmail.com',
        'street'      => 'Rudohorská 31',
        'street_note' => 'areál DraculaGym, 2. poschodie',
        'city'        => 'Banská Bystrica',
        'district'    => 'Sásová',
        'postal_code' => '', // DOPLNIŤ po overení s klientkou
        'country'     => 'SK',
        'facebook'    => 'https://www.facebook.com/masazvsasovej.sk/',
        'experience_years' => 17,
    ],

    // Cesty, kde sa hľadá súbor s SMTP údajmi (prvý existujúci sa použije).
    // Na Websupporte je verejný priečinok "web/", priečinok "private/" leží vedľa neho.
    // Záložná možnosť: web/private/ (chránený súborom .htaccess), ak hosting nedovolí priečinok mimo web/.
    'smtp_config_paths' => [
        __DIR__ . '/../../private/smtp-config.php',
        __DIR__ . '/../private/smtp-config.php',
    ],

    // Ochrana formulára
    // Analytika (napr. Google Analytics 4, tvar 'G-XXXXXXXXXX'). Prázdne = žiadna analytika.
    // Načíta sa AŽ po súhlase návštevníka s analytickými cookies (cookie lišta).
    'analytics_id' => '',

    // Objednávkový formulár (pravidlá v includes/booking.php)
    'booking' => [
        // 'demo'  = UKÁŽKA pre klientku: nič sa neodosiela ani neukladá, zobrazí sa iba informačné potvrdenie.
        // 'email' = po schválení: odoslanie požiadavky e-mailom cez SMTP (vyžaduje private/smtp-config.php).
        'mode'           => 'demo',
        'max_days_ahead' => 90,      // ako ďaleko dopredu sa dá zvoliť dátum (kalendár)
        'time_step'      => 15,      // krok výberu začiatku masáže v minútach (15 = 9:00, 9:15, 9:30 …)

        // =====================================================================
        //  DEMONŠTRAČNÝ PRACOVNÝ ČAS – NIE SÚ TO SKUTOČNÉ HODINY MASÉRKY.
        //  Hodnoty slúžia iba na ukážku fungovania kalendára. Po dohode
        //  s klientkou ich prepíšte a 'schedule_is_demo' nastavte na false.
        // =====================================================================
        'schedule_is_demo' => true,

        // Pracovné dni: 1 = pondelok … 7 = nedeľa.                         (DEMO)
        'working_days'   => [1, 2, 3, 4, 5],

        // Pracovný čas: masáž môže začať najskôr 'from' a musí skončiť      (DEMO)
        // najneskôr o 'to'. 'by_day' = iný čas pre konkrétny deň v týždni.
        'working_hours'  => [
            'from'   => '09:00',
            'to'     => '18:00',
            'by_day' => [
                5 => ['from' => '09:00', 'to' => '14:00'], // piatok kratšie
            ],
        ],

        // Prestávky počas dňa (napr. obed) – v tomto čase masáž neprebieha. (DEMO)
        'breaks'         => [
            ['from' => '12:00', 'to' => '12:30'],
        ],

        // Prestávka medzi dvoma klientmi v minútach (príprava, vetranie).   (DEMO)
        'buffer_minutes' => 10,

        // Dni, keď sa nepracuje (dovolenka, sviatky), tvar 'RRRR-MM-DD'.    (DEMO – prázdne)
        'closed_dates'   => [],
    ],

    'form' => [
        'min_fill_seconds'  => 4,     // rýchlejšie odoslanie = pravdepodobne robot
        'max_age_seconds'   => 7200,  // formulár starší ako 2 hod. treba načítať znova
        'rate_limit_seconds'=> 60,    // minimálny odstup medzi dvoma odoslaniami z jednej relácie
        'max_links'         => 2,     // viac odkazov v správe = spam
    ],
];
