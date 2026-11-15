<?php
/**
 * Spracovanie objednávkového formulára.
 *
 * Režim (includes/config.php → 'booking' → 'mode'):
 *   'demo'  – UKÁŽKA: údaje sa iba overia a hneď zahodia. Nič sa neodosiela, neukladá
 *             (ani do session) ani nezapisuje do logov. Pri zapnutom JavaScripte
 *             sa sem formulár v demo režime vôbec neodosiela – overí sa priamo v prehliadači.
 *   'email' – po schválení: odoslanie e-mailom cez SMTP (private/smtp-config.php).
 *             Úspech sa hlási IBA vtedy, keď PHPMailer správu skutočne odoslal.
 *
 * - Funguje aj bez JavaScriptu (Post/Redirect/Get späť na index.php#kontakt).
 * - Pri odoslaní cez JavaScript (hlavička X-Requested-With: fetch) vracia JSON.
 */
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';

use PHPMailer\PHPMailer\Exception as MailException;
use PHPMailer\PHPMailer\PHPMailer;

$isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';

/** Odpoveď klientovi – JSON alebo presmerovanie späť na formulár. */
function respond(bool $isAjax, int $status, string $state, string $message, array $errors = [], array $old = [], array $summary = []): never
{
    if ($isAjax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['ok' => in_array($state, ['success', 'demo'], true), 'demo' => $state === 'demo', 'message' => $message, 'errors' => (object) $errors, 'summary' => $summary ?: null], JSON_UNESCAPED_UNICODE);
        exit;
    }
    flash_set('contact_form', [
        'status'  => $state,
        'message' => $message,
        'errors'  => $errors,
        // Zhrnutie pre demo potvrdenie: iba typ objednávky, masáž a termín – žiadne osobné údaje.
        'summary' => $state === 'demo' ? $summary : [],
        // V demo režime sa vyplnené údaje neukladajú ani dočasne (session je súbor na serveri).
        'old'     => ($state === 'success' || $state === 'demo' || booking_is_demo()) ? [] : $old,
    ]);
    header('Location: index.php#kontakt', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php#kontakt', true, 303);
    exit;
}

start_secure_session();

$phoneFallback = ' Prosím, zavolajte na ' . biz('phone') . '.';

/* ---------- 1. Bezpečnostné kontroly ---------- */

if (!csrf_valid($_POST['csrf_token'] ?? null)) {
    respond($isAjax, 419, 'error', 'Platnosť formulára vypršala. Obnovte, prosím, stránku a skúste to znova.');
}

// Honeypot – skryté pole vypĺňajú iba roboty. Tvárime sa neutrálne, nič neodosielame.
if (trim((string) ($_POST['website'] ?? '')) !== '') {
    respond($isAjax, 400, 'error', 'Správu sa nepodarilo spracovať.' . $phoneFallback);
}

$formCfg = (array) config('form');
$age = form_age($_POST['form_ts'] ?? null);
if ($age === null || $age > (int) $formCfg['max_age_seconds']) {
    respond($isAjax, 419, 'error', 'Platnosť formulára vypršala. Obnovte, prosím, stránku a skúste to znova.');
}
if ($age < (int) $formCfg['min_fill_seconds']) {
    respond($isAjax, 429, 'error', 'Formulár bol odoslaný príliš rýchlo. Počkajte chvíľu a skúste to znova.');
}

$lastSent = (int) ($_SESSION['last_form_sent'] ?? 0);
if ($lastSent > 0 && time() - $lastSent < (int) $formCfg['rate_limit_seconds']) {
    respond($isAjax, 429, 'error', 'Správu ste už odoslali. Ďalšiu môžete poslať o chvíľu.');
}

/* ---------- 2. Očistenie a validácia vstupov (includes/booking.php) ---------- */

[$data, $errors] = booking_validate($_POST);
$serviceOptions  = service_options();

if ($errors) {
    respond($isAjax, 422, 'error', 'Skontrolujte, prosím, zvýraznené polia.', $errors, $data);
}

/* ---------- 3. Demonštračný režim – nič sa neodosiela ani neukladá ---------- */

if (booking_is_demo()) {
    $summary = booking_request_summary($data);
    unset($data, $_POST);
    respond($isAjax, 200, 'demo', booking_demo_message(), [], [], $summary);
}

// PO SCHVÁLENÍ (rozšírenie): sem doplniť uloženie požiadavky (napr. databáza na Websuporte)
// a vygenerovanie odkazu, ktorým masérka termín potvrdí. Zatiaľ zámerne neimplementované.

/* ---------- 4. Odoslanie e-mailu ---------- */

if (!smtp_ready()) {
    // Žiadne falošné potvrdenie – formulár jasne povie, že ešte nie je aktívny.
    respond($isAjax, 503, 'error', 'Online formulár zatiaľ nie je aktívny, správa nebola odoslaná.' . $phoneFallback, [], $data);
}

$smtp = smtp_config();
$lib  = (string) ($smtp['phpmailer_path'] ?? (__DIR__ . '/../private/lib/PHPMailer'));
foreach (['Exception', 'PHPMailer', 'SMTP'] as $class) {
    $file = rtrim($lib, '/\\') . '/' . $class . '.php';
    if (!is_file($file)) {
        error_log('[kontaktny formular] Chýba knižnica PHPMailer: ' . $file);
        respond($isAjax, 500, 'error', 'Správu sa nepodarilo odoslať pre technickú chybu.' . $phoneFallback, [], $data);
    }
    require_once $file;
}

$serviceLabel = $serviceOptions[$data['service']];

$fmtDate = static fn (string $d): string => $d !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d', $d)->format('j. n. Y') : '';
$rows = [
    'Typ objednávky'     => $data['order_type'] === 'pass' ? 'PERMANENTKA' : 'Jednorazová masáž',
    'Meno a priezvisko'  => $data['name'],
    'Telefón'            => $data['phone'],
    'E-mail'             => $data['email'],
    'Vybraná masáž'      => $serviceLabel,
];
if ($data['order_type'] === 'pass') {
    $rows['Permanentka'] = $serviceOptions[$data['pass_type']];
    $rows['Termíny']     = 'dohodnúť osobne – zákazníka kontaktovať';
} else {
    $rows['Preferovaný termín'] = $fmtDate($data['date']) . ' o ' . $data['time'];
    $rows['Dĺžka masáže']       = $data['duration'] !== '' ? $data['duration'] . ' min' : 'dohodne sa individuálne (dostupnosť termínu NEOVERENÁ – potvrdiť telefonicky)';
}

$html  = '<div style="font-family:Arial,sans-serif;font-size:15px;color:#3A2621">';
$html .= '<h2 style="color:#6B1E2C;font-weight:normal">Nová správa z webu ' . e(biz('name')) . '</h2>';
$html .= '<table cellpadding="6" style="border-collapse:collapse">';
foreach ($rows as $label => $value) {
    $html .= '<tr><td style="color:#7a6359">' . e($label) . '</td><td><strong>' . e($value) . '</strong></td></tr>';
}
$html .= '</table>';
$html .= '<p style="color:#7a6359;margin-top:18px">Poznámka pre masérku:</p>';
$html .= '<p>' . nl2br(e($data['note'] ?: '—')) . '</p>';
$html .= '</div>';

$text = "Nová správa z webu " . biz('name') . "\n\n";
foreach ($rows as $label => $value) {
    $text .= $label . ': ' . $value . "\n";
}
$text .= "\nPoznámka pre masérku:\n" . ($data['note'] ?: '—') . "\n";

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = (string) $smtp['host'];
    $mail->Port       = (int) $smtp['port'];
    $mail->SMTPAuth   = true;
    $mail->Username   = (string) $smtp['username'];
    $mail->Password   = (string) $smtp['password'];
    $mail->SMTPSecure = ($smtp['encryption'] ?? 'ssl') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
    $mail->Timeout    = 15;
    $mail->CharSet    = PHPMailer::CHARSET_UTF8;

    $mail->setFrom((string) $smtp['from_email'], (string) ($smtp['from_name'] ?? biz('name')));
    $mail->addAddress((string) $smtp['to_email']);
    $mail->addReplyTo($data['email'], $data['name']);

    $mail->isHTML(true);
    $mail->Subject = 'Požiadavka na termín: ' . $data['name'] . ' – ' . $serviceLabel;
    $mail->Body    = $html;
    $mail->AltBody = $text;

    $mail->send();
} catch (MailException $ex) {
    // Detail chyby iba do logu servera (bez osobných údajov), nie návštevníkovi.
    error_log('[kontaktny formular] SMTP chyba: ' . $mail->ErrorInfo);
    respond($isAjax, 502, 'error', 'Správu sa nepodarilo odoslať.' . $phoneFallback, [], $data);
}

$_SESSION['last_form_sent'] = time();

// Požiadavka bola odoslaná masérke – termín ešte NIE JE potvrdený.
respond($isAjax, 200, 'success', 'Ďakujem, vaša požiadavka bola odoslaná. Termín vám potvrdím telefonicky alebo e-mailom.');
