<?php
/**
 * ============================================================================
 * Halaman Detail Publik Proyek (project-detail.php)
 * ============================================================================
 * Fitur & Keamanan:
 * - Menerima parameter ?slug=...
 * - Query PDO Prepared Statement untuk fetch proyek berdasarkan slug unik
 * - Error UI 404 minimalis jika slug tidak ditemukan
 * - Sanitasi output anti-XSS via e()
 * - Desain Zen: Gambar penuh, badge stack, deskripsi teknis luas, tombol Live & Code
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

// Ambil slug dari query string
$slug = trim($_GET['slug'] ?? '');

$project = null;

if ($slug !== '') {
    try {
        $stmt = $pdo->prepare('SELECT * FROM `projects` WHERE `slug` = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $project = $stmt->fetch();
    } catch (PDOException $e) {
        error_log('[FETCH PROJECT DETAIL ERROR] ' . $e->getMessage());
    }
}

// Set Judul Halaman Sesuai Proyek
if ($project) {
    $pageTitle = $project['title'] . ' — Studi Kasus & Rekayasa';
    $pageDesc  = $project['excerpt'];
} else {
    $pageTitle = 'Proyek Tidak Ditemukan — 404';
    $pageDesc  = 'Halaman portofolio yang Anda cari tidak ditemukan.';
}

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<div class="container project-detail-layout">

    <?php if ($project): ?>
        <!-- Tombol Kembali -->
        <div class="detail-back-nav">
            <a href="index.php#projects" class="detail-back-link">
                &larr; Kembali ke Daftar Karya
            </a>
        </div>

        <!-- Header Proyek -->
        <header class="detail-header">
            <div class="detail-meta">
                <span class="project-category"><?= e($project['category']); ?></span>
                <?php if (!empty($project['featured'])): ?>
                    <span class="project-badge-featured">Utama</span>
                <?php endif; ?>
                <span style="font-size: 0.8rem; color: var(--text-muted);">
                    &bull; <?= date('M Y', strtotime($project['created_at'])); ?>
                </span>
            </div>

            <h1 class="detail-title"><?= e($project['title']); ?></h1>
            <p class="detail-excerpt"><?= e($project['excerpt']); ?></p>
        </header>

        <!-- Mockup Gambar Penuh -->
        <div class="detail-image-box">
            <?php if (!empty($project['image']) && file_exists(__DIR__ . '/' . $project['image'])): ?>
                <img src="<?= e($project['image']); ?>" alt="<?= e($project['title']); ?>" class="detail-image">
            <?php else: ?>
                <div class="project-image-placeholder" style="height: 360px;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                        <polyline points="21 15 16 10 5 21"></polyline>
                    </svg>
                    <span>Tidak ada mockup visual untuk studi kasus ini</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Konten Utama & Sidebar Spesifikasi -->
        <div class="detail-content-grid">
            <!-- Deskripsi & Studi Kasus Rekayasa -->
            <div class="detail-main-text">
                <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--text-primary); margin-bottom: 16px;">
                    Deskripsi &amp; Arsitektur Teknis
                </h3>
                <?= nl2br(e($project['description'])); ?>
            </div>

            <!-- Sidebar Informasi Teknis & Tautan -->
            <aside class="detail-sidebar">
                <div class="detail-sidebar-item">
                    <span class="detail-sidebar-label">Teknologi &amp; Stack</span>
                    <div class="project-tech" style="margin-bottom: 0;">
                        <?php 
                        $tags = array_map('trim', explode(',', $project['tech_stack']));
                        foreach ($tags as $tag): 
                        ?>
                            <span class="tech-tag"><?= e($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="detail-sidebar-item">
                    <span class="detail-sidebar-label">Kategori Karya</span>
                    <span style="font-size: 0.94rem; color: var(--text-primary); font-weight: 500;">
                        <?= e($project['category']); ?>
                    </span>
                </div>

                <div class="detail-sidebar-item">
                    <span class="detail-sidebar-label">Aksi &amp; Tautan Eksternal</span>
                    <div class="detail-sidebar-actions">
                        <?php if (!empty($project['demo_url'])): ?>
                            <a href="<?= e($project['demo_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="width: 100%;">
                                Live Preview &rarr;
                            </a>
                        <?php endif; ?>

                        <?php if (!empty($project['github_url'])): ?>
                            <a href="<?= e($project['github_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary" style="width: 100%;">
                                Source Code &rarr;
                            </a>
                        <?php endif; ?>

                        <?php if (empty($project['demo_url']) && empty($project['github_url'])): ?>
                            <span style="font-size: 0.85rem; color: var(--text-muted);">
                                Tautan publik tidak tersedia (Proyek Internal / Privat).
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>
        </div>

    <?php else: ?>
        <!-- 404 Fallback State -->
        <div class="empty-box" style="padding: 96px 24px;">
            <span class="section-tag">Status 404</span>
            <h2 style="font-size: 1.6rem; font-weight: 600; margin: 8px 0 12px;">Proyek Tidak Ditemukan</h2>
            <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 28px;">
                Karya portofolio dengan alamat ini mungkin telah dipindahkan atau dihapus dari sistem.
            </p>
            <a href="index.php#projects" class="btn btn-primary">
                &larr; Kembali ke Halaman Utama
            </a>
        </div>
    <?php endif; ?>

</div>

<?php
require_once __DIR__ . '/includes/footer.php';
