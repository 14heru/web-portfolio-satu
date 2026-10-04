<?php
/**
 * ============================================================================
 * Template Header Publik
 * ============================================================================
 */
declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'Portfolio & Craft — Minimalist Works';
}
if (!isset($pageDesc)) {
    $pageDesc = 'Portofolio rekayasa web bersih, performa tinggi, dan berestetika tenang.';
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    
    <title><?= e($pageTitle); ?></title>
    <meta name="description" content="<?= e($pageDesc); ?>">
    
    <!-- Google Fonts: Plus Jakarta Sans (Clean, Modern, Humanist) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Pure Native Stylesheet (Anti-AI Slop) -->
    <link rel="stylesheet" href="<?= e(asset_url('public/css/style.css') . '?v=' . (string) filemtime(__DIR__ . '/../public/css/style.css')); ?>">
</head>
<body>
    <div class="floating-nav-wrapper">
        <nav class="pill-nav" aria-label="Navigasi utama">
            <a class="pill-nav-item active" href="<?= e(base_url('index.php#hero')); ?>" aria-current="page">Beranda</a>
            <a class="pill-nav-item" href="<?= e(base_url('index.php#projects')); ?>">Project</a>
            <a class="pill-nav-item" href="<?= e(base_url('index.php#skills')); ?>">Keahlian</a>
            <a class="pill-nav-item" href="<?= e(base_url('index.php#about')); ?>">Tentang</a>
            <a class="pill-nav-item" href="<?= e(base_url('index.php#contact')); ?>">Kontak</a>
        </nav>
    </div>
