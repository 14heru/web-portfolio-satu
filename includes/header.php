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
    <link rel="stylesheet" href="public/css/style.css">
</head>
<body>

    <!-- Header & Navigasi Zen Responsif -->
    <header class="site-header">
        <div class="container site-nav">
            <a href="index.php" class="brand-link" aria-label="Beranda">
                <span class="brand-dot"></span>
                <span>Heru Perdana Saputra</span>
            </a>

            <!-- Tombol Toggle Menu Mobile (Hamburger) -->
            <button type="button" class="nav-toggle-btn" id="nav-toggle" aria-label="Buka Menu Navigasi" aria-expanded="false">
                <span class="nav-toggle-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
            </button>

            <div class="nav-menu-wrapper" id="nav-menu">
                <nav>
                    <ul class="nav-links">
                        <li><a href="index.php#hero" class="nav-link">Beranda</a></li>
                        <li><a href="index.php#projects" class="nav-link">Karya</a></li>
                        <li><a href="index.php#skills" class="nav-link">Keahlian</a></li>
                        <li><a href="index.php#about" class="nav-link">Tentang</a></li>
                        <li><a href="index.php#contact" class="nav-link">Kontak</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main id="main-content">
