<?php
/**
 * Objednávkový formulár – spoločné pravidlá pre stránku (index.php) aj server (send-form.php).
 *
 * Režim sa nastavuje v includes/config.php → 'booking' → 'mode':
 *   'demo'  = UKÁŽKA. Formulár sa iba overí a zobrazí sa potvrdenie, že išlo o ukážku.
 *             Nič sa neodosiela, neukladá ani nezapisuje do logov (ani do session).
 *   'email' = (po schválení klientkou) požiadavka sa odošle e-mailom cez SMTP
 *             – vyžaduje vyplnený a zapnutý private/smtp-config.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/booking-demo.php';

function booking_mode(): string
{
    return config('booking.mode') === 'email' ? 'email' : 'demo';
}

function booking_is_demo(): bool
{
    return booking_mode() === 'demo';
}

/** Text potvrdenia v demonštračnom režime (zadaný klientom – nemeniť bez dohody). */
function booking_demo_message(): string
{
    return 'Ďakujeme za váš záujem o naše masážne služby. Toto je demonštračná verzia objednávkového formulára. '
        . 'Vaša požiadavka nebola reálne odoslaná ani rezervovaná. V prípade záujmu nás môžete kontaktovať telefonicky na čísle '
        . biz('phone') . '.';
}

/** Dnešný dátum v časovom pásme prevádzky. */
function booking_today(): DateTimeImmutable
{
    return new DateTimeImmutable('today', new DateTimeZone('Europe/Bratislava'));
}

/** Najneskorší dátum, ktorý sa dá vybrať. */
function booking_max_date(): DateTimeImmutable
{
    $days = max(1, (int) (config('booking.max_days_ahead') ?? 90));
    return booking_today()->modify('+' . $days . ' days');
}

/* ---------------------------------------------------------------------------
 * Pracovný čas (includes/config.php → 'booking'). Aktuálne hodnoty sú DEMONŠTRAČNÉ.
 * Všetky časy sa počítajú v minútach od polnoci, v pásme Europe/Bratislava.
 * ------------------------------------------------------------------------- */

/** Sú pracovné dni a hodiny iba ukážkové (nie skutočné hodiny masérky)? */
function booking_schedule_is_demo(): bool
{
    return config('booking.schedule_is_demo') !== false;
}

/** Krok výberu začiatku masáže v minútach. */
function booking_time_step(): int
{
    return max(5, (int) (config('booking.time_step') ?? 15));
}

/** Prestávka medzi dvoma klientmi v minútach. */
function booking_buffer_minutes(): int
{
    return max(0, (int) (config('booking.buffer_minutes') ?? 0));
}

/** „HH:MM“ → minúty od polnoci (null pri neplatnom zápise). */
function booking_to_minutes(string $time): ?int
{
    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $m)) {
        return null;
    }
    return (int) $m[1] * 60 + (int) $m[2];
}

/** Minúty od polnoci → „HH:MM“. */
function booking_from_minutes(int $minutes): string
{
    $minutes = max(0, min($minutes, 24 * 60 - 1));
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

/** Čas „HH:MM“ posunutý o daný počet minút (v rámci dňa). */
function booking_add_minutes(string $time, int $minutes): string
{
    return booking_from_minutes((booking_to_minutes($time) ?? 0) + $minutes);
}

/** Pracuje sa v daný deň? (pracovné dni v týždni, bez 'closed_dates') */
function booking_is_working_day(DateTimeImmutable $day): bool
{
    $days   = array_map('intval', (array) (config('booking.working_days') ?? []));
    $closed = (array) (config('booking.closed_dates') ?? []);
    return in_array((int) $day->format('N'), $days, true) && !in_array($day->format('Y-m-d'), $closed, true);
}

/** Pracovný čas pre deň v týždni (1 = pondelok … 7 = nedeľa) ako [od, do] v minútach, bez prestávok. */
function booking_day_hours(int $weekday): ?array
{
    $hours = (array) (config('booking.working_hours') ?? []);
    $hours = $hours['by_day'][$weekday] ?? $hours;
    $from  = booking_to_minutes((string) ($hours['from'] ?? ''));
    $to    = booking_to_minutes((string) ($hours['to'] ?? ''));
    return $from !== null && $to !== null && $to > $from ? [$from, $to] : null;
}

/**
 * Pracovný čas dňa po odpočítaní prestávok: zoznam úsekov [od, do] v minútach.
 * Prázdny zoznam = v tento deň sa nepracuje.
 * @return list<array{0: int, 1: int}>
 */
function booking_open_intervals(DateTimeImmutable $day): array
{
    $hours = booking_is_working_day($day) ? booking_day_hours((int) $day->format('N')) : null;
    if (!$hours) {
        return [];
    }

    $intervals = [$hours];
    foreach ((array) (config('booking.breaks') ?? []) as $break) {
        $bFrom = booking_to_minutes((string) ($break['from'] ?? ''));
        $bTo   = booking_to_minutes((string) ($break['to'] ?? ''));
        if ($bFrom === null || $bTo === null || $bTo <= $bFrom) {
            continue;
        }
        $next = [];
        foreach ($intervals as [$a, $b]) {
            if ($bTo <= $a || $bFrom >= $b) {
                $next[] = [$a, $b];
                continue;
            }
            if ($bFrom > $a) { $next[] = [$a, $bFrom]; }
            if ($bTo < $b)   { $next[] = [$bTo, $b]; }
        }
        $intervals = $next;
    }
    return $intervals;
}

/** Aktuálny čas v časovom pásme prevádzky (Europe/Bratislava). */
function booking_now(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Europe/Bratislava'));
}

/** Uplynul už začiatok $start (minúty) v daný deň podľa slovenského času? */
function booking_is_past(DateTimeImmutable $day, int $start): bool
{
    $today = booking_today()->format('Y-m-d');
    $date  = $day->format('Y-m-d');
    if ($date !== $today) {
        return $date < $today;
    }
    $now = booking_now();
    return $start <= (int) $now->format('G') * 60 + (int) $now->format('i');
}

/* ---------------------------------------------------------------------------
 * ROZHRANIE PRE EVIDENCIU REZERVÁCIÍ
 * ------------------------------------------------------------------------- */

/**
 * Rezervácie v daný deň: začiatok „HH:MM“ + dĺžka v minútach (bez prestávky medzi klientmi).
 *
 * JEDINÉ MIESTO, odkiaľ kalendár aj server berú obsadenosť.
 * Teraz: UKÁŽKOVÉ dáta z includes/booking-demo.php (nie skutočné rezervácie).
 * Po schválení: nahradiť načítaním skutočných rezervácií v rovnakom tvare,
 * napr. z databázy alebo z kalendára masérky. Nič iné sa meniť nemusí.
 *
 * @return list<array{start: string, minutes: int}>
 */
function booking_reservations(DateTimeImmutable $day): array
{
    return booking_demo_reservations($day);
}

/**
 * Obsadené úseky dňa [od, do] v minútach (bez prestávky – tá sa pripočíta pri overovaní).
 * @return list<array{0: int, 1: int}>
 */
function booking_busy_intervals(DateTimeImmutable $day): array
{
    $busy = [];
    foreach (booking_reservations($day) as $r) {
        $start = booking_to_minutes((string) ($r['start'] ?? ''));
        $len   = (int) ($r['minutes'] ?? 0);
        if ($start !== null && $len > 0) {
            $busy[] = [$start, $start + $len];
        }
    }
    usort($busy, static fn (array $a, array $b): int => $a[0] <=> $b[0]);
    return $busy;
}

/* ---------------------------------------------------------------------------
 * Výpočet voľných začiatkov
 * ------------------------------------------------------------------------- */

/**
 * Možné začiatky masáže v daný deň.
 *
 * $minutes = dĺžka masáže → masáž sa musí celá zmestiť do jedného úseku pracovného času
 *            a medzi ňou a každou rezerváciou musí zostať prestávka 'buffer_minutes'.
 * $minutes = null → dĺžka nie je známa (permanentka, „poraďte mi“): vráti iba začiatky
 *            v pracovnom čase; obsadenosť sa NEOVERUJE (termín treba potvrdiť individuálne).
 *
 * Začiatok, ktorý už podľa slovenského času uplynul, sa nikdy nevráti.
 * @return list<string>
 */
function booking_start_times(DateTimeImmutable $day, ?int $minutes): array
{
    $step   = booking_time_step();
    $buffer = booking_buffer_minutes();
    $busy   = $minutes !== null ? booking_busy_intervals($day) : [];
    $need   = $minutes ?? $step;
    $out    = [];

    foreach (booking_open_intervals($day) as [$from, $to]) {
        for ($s = $from; $s + $need <= $to; $s += $step) {
            if (booking_is_past($day, $s)) {
                continue;
            }
            foreach ($busy as [$bFrom, $bTo]) {
                if ($s < $bTo + $buffer && $bFrom < $s + $need + $buffer) {
                    continue 2; // prekrytie s rezerváciou (vrátane prestávky)
                }
            }
            $out[] = booking_from_minutes($s);
        }
    }
    return $out;
}

/** Dá sa v daný deň začať masáž o $start? ($minutes = null → neznáma dĺžka, iba pracovný čas) */
function booking_slot_fits(DateTimeImmutable $day, string $start, ?int $minutes): bool
{
    return in_array($start, booking_start_times($day, $minutes), true);
}

/**
 * Všetky začiatky, ktoré pracovný čas v niektorý deň v týždni pripúšťa (zoznam pre formulár bez JavaScriptu).
 * Nejde o voľné termíny – dostupnosť overí server po odoslaní.
 * @return list<string>
 */
function booking_all_start_times(): array
{
    $step  = booking_time_step();
    $times = [];
    foreach (array_map('intval', (array) (config('booking.working_days') ?? [])) as $weekday) {
        if ($hours = booking_day_hours($weekday)) {
            for ($s = $hours[0]; $s + $step <= $hours[1]; $s += $step) {
                $times[booking_from_minutes($s)] = true;
            }
        }
    }
    $times = array_keys($times);
    sort($times);
    return $times;
}

/**
 * Údaje pre kalendár na stránke (vkladajú sa ako JSON, bez sieťových požiadaviek):
 * 'RRRR-MM-DD' => ['open' => úseky pracovného času, 'busy' => obsadené úseky]. Nepracovné dni chýbajú.
 * @return array<string, array{open: list<array{0:int,1:int}>, busy: list<array{0:int,1:int}>}>
 */
function booking_calendar_data(): array
{
    $map = [];
    $end = booking_max_date();
    for ($day = booking_today(); $day <= $end; $day = $day->modify('+1 day')) {
        $open = booking_open_intervals($day);
        if ($open) {
            $map[$day->format('Y-m-d')] = ['open' => $open, 'busy' => booking_busy_intervals($day)];
        }
    }
    return $map;
}

/** Pracovný čas dňa ako text, napr. „9:00 – 12:00, 12:30 – 18:00“. */
function booking_intervals_text(array $intervals): string
{
    return implode(', ', array_map(
        static fn (array $i): string => ltrim(booking_from_minutes($i[0]), '0') . ' – ' . ltrim(booking_from_minutes($i[1]), '0'),
        $intervals
    ));
}

/** Pracovný čas ako krátky text, napr. „Po – Št 9:00 – 18:00 · Pi 9:00 – 14:00 · prestávka 12:00 – 12:30“. */
function booking_schedule_text(): string
{
    $names = [1 => 'Po', 2 => 'Ut', 3 => 'St', 4 => 'Št', 5 => 'Pi', 6 => 'So', 7 => 'Ne'];
    $days  = array_map('intval', (array) (config('booking.working_days') ?? []));
    sort($days);
    $groups = [];
    foreach ($days as $d) {
        $hours = booking_day_hours($d);
        if (!$hours || !isset($names[$d])) {
            continue;
        }
        $text = booking_intervals_text([$hours]);
        $last = count($groups) - 1;
        if ($last >= 0 && $groups[$last]['text'] === $text && $groups[$last]['to'] === $d - 1) {
            $groups[$last]['to'] = $d;
        } else {
            $groups[] = ['from' => $d, 'to' => $d, 'text' => $text];
        }
    }
    $parts = array_map(static fn (array $g): string => $names[$g['from']]
        . ($g['to'] !== $g['from'] ? ' – ' . $names[$g['to']] : '') . ' ' . $g['text'], $groups);
    $breaks = [];
    foreach ((array) (config('booking.breaks') ?? []) as $b) {
        $f = booking_to_minutes((string) ($b['from'] ?? ''));
        $t = booking_to_minutes((string) ($b['to'] ?? ''));
        if ($f !== null && $t !== null && $t > $f) {
            $breaks[] = [$f, $t];
        }
    }
    if ($breaks) {
        $parts[] = 'prestávka ' . booking_intervals_text($breaks);
    }
    return implode(' · ', $parts);
}

/* ---------------------------------------------------------------------------
 * Dĺžky a ceny služieb
 * ------------------------------------------------------------------------- */

/**
 * Dĺžky, ktoré sa dajú pri službe vybrať (services.php → 'lengths'), s cenou z cenníka ('prices').
 * Permanentky a „poraďte mi“ dĺžku nemajú – dohodne sa individuálne.
 * @return list<array{minutes: int, note: string, price: ?string, label: string}>
 */
function booking_service_lengths(string $slug): array
{
    static $services = null;
    $services ??= require __DIR__ . '/services.php';
    $prices = $services[$slug]['prices'] ?? [];
    $out = [];
    foreach ($services[$slug]['lengths'] ?? [] as $len) {
        [$minutes, $note] = is_array($len) ? [(int) $len[0], (string) ($len[1] ?? '')] : [(int) $len, ''];
        $price = null;
        foreach ($prices as $p) {
            if ((string) $p['time'] === $minutes . ' min' && (string) ($p['note'] ?? '') === $note) {
                $price = (!empty($p['from']) ? 'od ' : '') . (int) $p['price'] . ' €';
                break;
            }
        }
        $out[] = [
            'minutes' => $minutes,
            'note'    => $note,
            'price'   => $price,
            'label'   => $minutes . ' min' . ($price !== null ? ' · ' . $price : '') . ($note !== '' ? ' – ' . $note : ''),
        ];
    }
    return $out;
}

/**
 * Krátka informácia o službe pri výbere vo formulári (dĺžka a cena), bez vymyslených údajov.
 * @return array{lengths: string, price: string, known: bool}
 */
function booking_service_info(string $slug): array
{
    $lengths = booking_service_lengths($slug);
    if ($lengths) {
        $prices = array_filter(array_map(
            static fn (array $l): ?string => $l['price'] !== null ? $l['price'] . ' (' . $l['minutes'] . ' min)' : null,
            $lengths
        ));
        return [
            'lengths' => implode(' / ', array_unique(array_map(static fn (array $l): string => (string) $l['minutes'], $lengths))) . ' min',
            'price'   => $prices ? implode(', ', $prices) : 'zatiaľ neuvedená v cenníku',
            'known'   => true,
        ];
    }
    // Permanentky a „poraďte mi“ – údaje zhodné so sekciou Permanentky na stránke
    $passes = ['permanentka-10' => '14 %', 'permanentka-5' => '5 %'];
    return [
        'lengths' => 'dohodneme individuálne',
        'price'   => isset($passes[$slug])
            ? 'podľa zvolenej masáže, zľava ' . $passes[$slug]
            : 'podľa masáže, ktorú spolu vyberieme',
        'known'   => false,
    ];
}

/** Dĺžky a informácie všetkých služieb pre JavaScript. */
function booking_lengths_map(): array
{
    $map = [];
    foreach (array_keys(service_options()) as $slug) {
        $map[$slug] = [
            'l' => array_map(static fn (array $l): array => [$l['minutes'], $l['label'], $l['price']], booking_service_lengths($slug)),
            'i' => booking_service_info($slug),
        ];
    }
    return $map;
}

/* ---------------------------------------------------------------------------
 * Typ objednávky: jednorazová masáž (s termínom v kalendári) alebo permanentka
 * (bez termínu – podrobnosti a termíny sa dohodnú osobne).
 * ------------------------------------------------------------------------- */

/** Permanentky ponúkané na stránke (sekcia Permanentky): kľúč => názov. */
function booking_pass_options(): array
{
    return array_filter(service_options(), static fn (string $k): bool => str_starts_with($k, 'permanentka'), ARRAY_FILTER_USE_KEY);
}

/** Zľava permanentky – údaje zhodné so sekciou Permanentky v index.php. */
function booking_pass_discount(string $pass): ?string
{
    return ['permanentka-10' => '14 %', 'permanentka-5' => '5 %'][$pass] ?? null;
}

/** Masáže (a „poraďte mi“) na výber vo formulári – bez permanentiek. */
function booking_massage_options(): array
{
    return array_diff_key(service_options(), booking_pass_options());
}

/** Text informácie pri objednávke permanentky (zadaný klientom). */
function booking_pass_notice(): string
{
    return 'Pri objednávke permanentky vás budeme kontaktovať a dohodneme si podrobnosti a termíny osobne.';
}

/**
 * Zhrnutie požiadavky pre demonštračné potvrdenie – bez osobných údajov
 * (iba typ objednávky, masáž, permanentka a termín).
 */
function booking_request_summary(array $data): array
{
    $services = service_options();
    $massage  = $services[$data['service'] ?? ''] ?? '';
    if (($data['order_type'] ?? '') === 'pass') {
        return [
            'kind'  => 'Požiadavka na permanentku',
            'lines' => array_values(array_filter([
                $services[$data['pass_type'] ?? ''] ?? '',
                $massage !== '' ? 'Masáž: ' . $massage : '',
                'Termíny by sme dohodli osobne.',
            ])),
        ];
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($data['date'] ?? ''));
    $days = ['nedeľa', 'pondelok', 'utorok', 'streda', 'štvrtok', 'piatok', 'sobota'];
    return [
        'kind'  => 'Požiadavka na jednorazovú masáž',
        'lines' => array_values(array_filter([
            $massage . (($data['duration'] ?? '') !== '' ? ', ' . $data['duration'] . ' min' : ''),
            $date ? 'Termín: ' . $days[(int) $date->format('w')] . ' ' . $date->format('j. n. Y') . ' o ' . ltrim((string) $data['time'], '0') : '',
        ])),
    ];
}

/** Ponuka masáží pre výber vo formulári, rozdelená do skupín (názvy zo services.php). */
function booking_service_groups(): array
{
    $groups = ['Masáže' => [], 'Permanentky' => [], 'Iné' => []];
    $massages = require __DIR__ . '/services.php';
    foreach (service_options() as $value => $label) {
        $group = isset($massages[$value]) ? 'Masáže' : (str_starts_with($value, 'permanentka') ? 'Permanentky' : 'Iné');
        $groups[$group][$value] = $label;
    }
    return array_filter($groups);
}

/** Overí dátum vo formáte RRRR-MM-DD. Vráti chybovú hlášku alebo null. */
function booking_date_error(string $value, string $emptyMessage = ''): ?string
{
    if ($value === '') {
        return $emptyMessage !== '' ? $emptyMessage : null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('Europe/Bratislava'));
    if (!$date || $date->format('Y-m-d') !== $value) {
        return 'Zadajte platný dátum.';
    }
    if ($date < booking_today()) {
        return 'Dátum nemôže byť v minulosti. Vyberte dnešný alebo neskorší dátum.';
    }
    if ($date > booking_max_date()) {
        return 'Vyberte dátum najneskôr do ' . booking_max_date()->format('j. n. Y') . '.';
    }
    return null;
}

/**
 * Očistí a overí odoslané údaje.
 * @return array{0: array<string,string>, 1: array<string,string>} [údaje, chyby]
 */
function booking_validate(array $input): array
{
    $line = static function (mixed $v, int $max): string {
        $v = is_string($v) ? $v : '';
        $v = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $v) ?? '';
        return mb_substr(trim(preg_replace('/\s+/u', ' ', $v) ?? ''), 0, $max);
    };
    $text = static function (mixed $v, int $max): string {
        $v = is_string($v) ? str_replace(["\r\n", "\r"], "\n", $v) : '';
        $v = preg_replace('/[\x00-\x09\x0B-\x1F\x7F]+/u', '', $v) ?? '';
        return mb_substr(trim($v), 0, $max);
    };

    $data = [
        'name'     => $line($input['name'] ?? '', 100),
        'phone'    => $line($input['phone'] ?? '', 20),
        'email'    => $line($input['email'] ?? '', 150),
        'order_type' => ($input['order_type'] ?? '') === 'pass' ? 'pass' : 'single',
        'pass_type'  => $line($input['pass_type'] ?? '', 50),
        'service'  => $line($input['service'] ?? '', 50),
        'date'     => $line($input['date'] ?? '', 10),
        'time'     => $line($input['time'] ?? '', 5),
        'duration' => $line($input['duration'] ?? '', 3),
        'note'     => $text($input['note'] ?? '', 2000),
    ];

    $errors = [];

    if (mb_strlen($data['name']) < 3) {
        $errors['name'] = 'Zadajte, prosím, meno a priezvisko.';
    } elseif (!preg_match("/^[\p{L}][\p{L} .'\-]*$/u", $data['name'])) {
        $errors['name'] = 'Meno môže obsahovať iba písmená, medzery a pomlčky.';
    }

    $digits = preg_replace('/\D/', '', $data['phone']) ?? '';
    if ($data['phone'] === '') {
        $errors['phone'] = 'Zadajte, prosím, telefónne číslo.';
    } elseif (!preg_match('/^\+?[0-9 ()\/\-]+$/', $data['phone']) || strlen($digits) < 9 || strlen($digits) > 15) {
        $errors['phone'] = 'Zadajte platné telefónne číslo, napr. 0915 123 456 alebo +421 915 123 456.';
    }

    if ($data['email'] === '') {
        $errors['email'] = 'Zadajte, prosím, e-mailovú adresu.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)
        || !preg_match('/@[^@\s.]+(\.[^@\s.]+)*\.[^@\s.]{2,}$/', $data['email'])) {
        $errors['email'] = 'Zadajte platnú e-mailovú adresu, napr. meno@gmail.com.';
    }

    // Staršie odkazy (?masaz=permanentka-10) → objednávka permanentky
    if (isset(booking_pass_options()[$data['service']])) {
        $data['order_type'] = 'pass';
        $data['pass_type']  = $data['pass_type'] !== '' ? $data['pass_type'] : $data['service'];
        $data['service']    = '';
    }
    $isPass = $data['order_type'] === 'pass';

    if ($data['service'] === '') {
        $errors['service'] = $isPass ? 'Vyberte, prosím, masáž, ktorú chcete s permanentkou využívať.' : 'Vyberte, prosím, masáž.';
    } elseif (!isset(booking_massage_options()[$data['service']])) {
        $errors['service'] = 'Vyberte masáž zo zoznamu.';
    }

    if ($isPass) {
        // Permanentka: typ je povinný; dĺžka, dátum a čas sa NEPOSIELAJÚ ani neoverujú (dohodnú sa osobne).
        if ($data['pass_type'] === '') {
            $errors['pass_type'] = 'Vyberte, prosím, typ permanentky.';
        } elseif (!isset(booking_pass_options()[$data['pass_type']])) {
            $errors['pass_type'] = 'Vyberte permanentku zo zoznamu.';
        }
        $data['duration'] = $data['date'] = $data['time'] = '';
        if (preg_match_all('~(https?://|www\.)~i', $data['note']) > (int) (config('form.max_links') ?? 2)) {
            $errors['note'] = 'Poznámka obsahuje príliš veľa odkazov.';
        }
        return [$data, $errors];
    }
    $data['pass_type'] = '';

    // Dĺžka masáže – povinná pri službách, ktoré majú dĺžky (permanentky / „poraďte mi“ ju nemajú → null = neznáma)
    $minutes = null;
    if (!isset($errors['service'])) {
        $allowed = array_column(booking_service_lengths($data['service']), 'minutes');
        if ($allowed) {
            if ($data['duration'] === '') {
                $errors['duration'] = 'Vyberte, prosím, dĺžku masáže.';
            } elseif (!ctype_digit($data['duration']) || !in_array((int) $data['duration'], $allowed, true)) {
                $errors['duration'] = 'Vyberte dĺžku zo zoznamu pre zvolenú masáž.';
            } else {
                $minutes = (int) $data['duration'];
            }
        } else {
            $data['duration'] = '';
        }
    }

    if ($err = booking_date_error($data['date'], 'Vyberte, prosím, dátum v kalendári.')) {
        $errors['date'] = $err;
    }
    if ($data['time'] === '') {
        $errors['time'] = 'Vyberte, prosím, čas.';
    } elseif (!in_array($data['time'], booking_all_start_times(), true)) {
        $errors['time'] = 'Vyberte čas zo zoznamu.';
    } elseif (!isset($errors['date']) && !isset($errors['duration']) && !isset($errors['service'])) {
        $day   = DateTimeImmutable::createFromFormat('!Y-m-d', $data['date'], new DateTimeZone('Europe/Bratislava'));
        $start = (int) booking_to_minutes($data['time']);
        if (!booking_open_intervals($day)) {
            $errors['date'] = 'V tento deň sa nepracuje. Vyberte, prosím, iný deň.';
        } elseif (booking_is_past($day, $start)) {
            $errors['time'] = 'Tento čas už podľa slovenského času uplynul. Vyberte, prosím, neskorší čas.';
        } elseif (!booking_slot_fits($day, $data['time'], $minutes)) {
            $errors['time'] = $minutes !== null
                ? 'Tento termín nie je pre masáž s dĺžkou ' . $minutes . ' min voľný (vrátane prestávky medzi klientmi a pracovného času). Vyberte, prosím, iný čas alebo deň.'
                : 'Tento čas je mimo pracovného času. Vyberte, prosím, iný čas.';
        }
    }

    if (preg_match_all('~(https?://|www\.)~i', $data['note']) > (int) (config('form.max_links') ?? 2)) {
        $errors['note'] = 'Poznámka obsahuje príliš veľa odkazov.';
    }

    return [$data, $errors];
}
