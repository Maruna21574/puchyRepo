<?php
/**
 * Popisy fotografií v galérii (alt text pre Google a čítačky obrazovky).
 * Kľúč = názov súboru (v assets/images/galeria/ alebo fotka1.jpg, fotka2.jpg… v assets/images/),
 * hodnota = popis so správnou diakritikou.
 * Ak fotka popis nemá, vytvorí sa automaticky z názvu súboru.
 */
declare(strict_types=1);

return [
    'fotka4.jpg'         => 'Usmiata žena v štúdiu Masáže v Sásovej, v pozadí osvedčenia o absolvovaných kurzoch',
    'fotka1.jpg'       => 'Ľudmila Dolinská počas masáže nohy v štúdiu',
    'fotka2.jfif'        => 'Drevené nástroje na maderoterapiu a masážne oleje',
    'fotka3.jfif'        => 'Masážna miestnosť počas procedúry',
    'hlavnyPriestor.jpg' => 'Masážna miestnosť v štúdiu Masáže v Sásovej',
    // '01-masazna-miestnost.jpg' => 'Masážna miestnosť v štúdiu Masáže v Sásovej',
    // '02-drevene-nastroje.jpg'  => 'Drevené nástroje na maderoterapiu',
];
