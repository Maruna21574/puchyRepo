<?php
declare(strict_types=1);

// Na produkcii chyby nezobrazujeme návštevníkom, iba logujeme.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Bratislava');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/booking.php';

send_security_headers();
