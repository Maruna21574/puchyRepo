<?php
declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
http_response_code(404);

$page = [
    'title'       => 'Stránka sa nenašla | Masáže v Sásovej',
    'description' => 'Požadovaná stránka neexistuje.',
    'path'        => '404.php',
    'is_home'     => false,
    'robots'      => 'noindex',
    'use_base'    => true,
];

require __DIR__ . '/includes/header.php';
?>

<section class="section legal legal--center">
    <div class="container container--narrow">
        <?= lotus_svg('legal__icon') ?>
        <h1 class="section-title">Táto stránka sa nenašla</h1>
        <p>Možno bola presunutá alebo je adresa nesprávna.</p>
        <p><a class="btn btn--primary" href="index.php">Späť na úvod</a></p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
