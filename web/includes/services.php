<?php
/**
 * Ponuka masáží. Texty neobsahujú zdravotné ani liečebné tvrdenia.
 *
 * 'prices' = ceny prepísané z cenníka (assets/images/cennik.jpg, platný od 1. 3. 2025).
 *   ['time' => dĺžka, 'price' => suma v €, 'from' => true pri „od“, 'note' => upresnenie (nepovinné)]
 *   Služba bez kľúča 'prices' sa v cenníku nenachádza jednoznačne – cena sa nezobrazí.
 *
 * 'lengths' = dĺžky masáže v minútach, ktoré si zákazník vyberá v objednávkovom formulári
 *   (podľa údajov na stránke: štítky dĺžky na karte a riadky cenníka). Pri viacerých
 *   variantoch rovnakej masáže: [minúty, 'upresnenie']. Kalendár podľa dĺžky blokuje celý čas.
 *   Pozn.: Športová masáž – štítok „30 – 50 min“ a cenník (30 / 60 min) sa líšia; použitý cenník.
 *
 * Fotka karty: ak existuje assets/images/sluzby/<kľúč>.jpg (.webp/.jpeg/.png), použije sa automaticky.
 * Voliteľný kľúč 'image' = cesta k inej fotke v assets/images/ bez prípony (napr. 'sija').
 * Ak fotka neexistuje, zobrazí sa elegantné miesto na jej doplnenie.
 */
declare(strict_types=1);

return [
    'klasicka-masaz' => [
        'image'    => 'klasickaMasaz',
        'name'     => 'Klasická masáž',
        'duration' => ['40 min', '50 min', '60 min'],
        'lengths'  => [40, 50, 60],
        'desc'     => 'Overená masáž celého tela alebo vybraných partií. Tlak a tempo prispôsobím tomu, čo je vám príjemné.',
    ],
    'chrbat-a-sija' => [
        'image'    => 'sija',
        'name'     => 'Chrbát a šija',
        'duration' => ['40 min'],
        'lengths'  => [40, 50],
        'desc'     => 'Cielená masáž chrbta, ramien a šije – obľúbená voľba pri dlhom sedení za počítačom alebo za volantom.',
        'prices'   => [
            ['time' => '40 min', 'price' => 33, 'from' => true],
            ['time' => '50 min', 'price' => 36],
        ],
    ],
    'sportova-masaz' => [
        'image'    => 'sportovaMasaz',
        'name'     => 'Športová masáž',
        'duration' => ['30 – 50 min'],
        'lengths'  => [30, 60],
        'desc'     => 'Dynamickejšia masáž pre aktívnych ľudí, pred športovým výkonom alebo po ňom. Intenzitu dohodneme vopred.',
        'prices'   => [
            ['time' => '30 min', 'price' => 20, 'from' => true],
            ['time' => '60 min', 'price' => 35],
        ],
    ],
    'bankovanie' => [
        'image'    => 'bankovanie',
        'name'     => 'Bankovanie',
        'duration' => [],
        'lengths'  => [20, 40],
        'desc'     => 'Tradičná technika, pri ktorej sa na pokožku prikladajú masážne banky vytvárajúce jemný podtlak.',
        'prices'   => [
            ['time' => '20 min', 'price' => 14, 'from' => true],
            ['time' => '40 min', 'price' => 24],
        ],
    ],
    'reflexna-masaz-chodidla' => [
        'image'    => 'reflexnaMasazChodidla',
        'name'     => 'Reflexná masáž chodidla',
        'duration' => [],
        'lengths'  => [30, 40],
        'desc'     => 'Sústredená práca s chodidlami prstami a dlaňami. Pokojná procedúra, pri ktorej si oddýchnete celým telom.',
        'prices'   => [
            ['time' => '30 min', 'price' => 23, 'from' => true],
            ['time' => '40 min', 'price' => 27],
        ],
    ],
    'medova-detox-masaz' => [
        'image'    => 'medovaMasaz',
        'name'     => 'Medová detox masáž',
        'duration' => [],
        'lengths'  => [20, 30],
        'desc'     => 'Masáž s použitím medu a charakteristickou technikou prikladania a odliepania dlaní od pokožky.',
        'prices'   => [
            ['time' => '20 min', 'price' => 18, 'from' => true],
            ['time' => '30 min', 'price' => 27],
        ],
    ],
    'makke-techniky' => [
        'image'    => 'makkeTechniky',
        'name'     => 'Mäkké techniky',
        'duration' => [],
        'lengths'  => [30, 60],
        'desc'     => 'Jemné techniky práce s mäkkými tkanivami – pomalé, citlivé a vhodné aj pre tých, ktorí nemajú radi silný tlak.',
        'prices'   => [
            ['time' => '30 min', 'price' => 23, 'from' => true],
            ['time' => '60 min', 'price' => 41],
        ],
    ],
    'lymfodrenaz' => [
        'image'    => 'lymfodrenaz',
        'name'     => 'Lymfodrenáž',
        'duration' => [],
        'lengths'  => [[40, 'ruky, krk spredu'], [50, 'nohy'], [90, 'ruky, krk, nohy obojstranne']],
        'desc'     => 'Veľmi jemná masážna technika s pomalými, rytmickými pohybmi a ľahkým tlakom. Príjemne upokojujúca.',
        'prices'   => [
            ['time' => '40 min', 'price' => 24, 'from' => true, 'note' => 'ruky, krk spredu'],
            ['time' => '50 min', 'price' => 34, 'from' => true, 'note' => 'nohy'],
            ['time' => '90 min', 'price' => 48, 'from' => true, 'note' => 'ruky, krk, nohy obojstranne'],
        ],
    ],
    'maderoterapia' => [
        'image'    => 'maderoterapia',
        'name'     => 'Maderoterapia',
        'duration' => [],
        'lengths'  => [60],
        'desc'     => 'Masáž pomocou špeciálne tvarovaných drevených nástrojov, ktoré dopĺňajú prácu rúk.',
        'prices'   => [
            ['time' => '60 min', 'price' => 40],
        ],
    ],
    'aroma-celotelova-masaz' => [
        'image'    => 'AromaMasaz',
        'name'     => 'Aroma celotelová masáž',
        'duration' => [],
        'lengths'  => [30, 60],
        'desc'     => 'Celotelová masáž s vonnými olejmi. Spomalenie, vôňa a čas len pre seba.',
        'prices'   => [
            ['time' => '30 min', 'price' => 21, 'from' => true],
            ['time' => '60 min', 'price' => 37],
        ],
    ],
    'masazne-techniky' => [
        'image'    => 'masazneTechniky',
        'name'     => 'Masážne techniky',
        'duration' => ['60 min', '70 min', '80 min'],
        'lengths'  => [60, 70, 80],
        'desc'     => 'Dlhšia procedúra kombinujúca viacero techník podľa vašich potrieb a toho, ako sa práve cítite.',
    ],
];
