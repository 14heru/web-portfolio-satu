<?php
/**
 * ============================================================================
 * Landing Page Portofolio Utama (index.php)
 * ============================================================================
 * - Integrasi PDO Prepared Statement untuk fetch data karya & keahlian
 * - CSRF Protected Contact Form Handler
 * - Sanitasi input & output anti-XSS via e()
 * - UI Zen Minimalis, Tenang, dan Anti-AI Slop
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

// --------------------------------------------------------------------------
// Form Handler: Pengiriman Pesan Kontak (POST)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // 1. Verifikasi Validitas Token CSRF
    require_csrf_valid();

    // 2. Ambil & Sanitasi Input Bersih
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = [];

    // Validasi Sederhana & Tegas
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Nama wajib diisi dan maksimal 100 karakter.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $errors[] = 'Format alamat email tidak valid.';
    }

    if ($subject === '' || mb_strlen($subject) > 150) {
        $errors[] = 'Subjek pesan wajib diisi dan maksimal 150 karakter.';
    }

    if ($message === '' || mb_strlen($message) < 10) {
        $errors[] = 'Isi pesan wajib diisi minimal 10 karakter.';
    }

    if (empty($errors)) {
        try {
            // Prepared Statement Mutlak: Bebas SQL Injection
            $stmt = $pdo->prepare('
                INSERT INTO `contacts` (`name`, `email`, `subject`, `message`, `is_read`, `created_at`)
                VALUES (:name, :email, :subject, :message, 0, NOW())
            ');

            $stmt->execute([
                ':name'    => $name,
                ':email'   => $email,
                ':subject' => $subject,
                ':message' => $message,
            ]);

            set_flash('success', 'Terima kasih! Pesan Anda telah terkirim dengan aman. Saya akan segera membalasnya.');
        } catch (PDOException $e) {
            error_log('[CONTACT FORM ERROR] ' . $e->getMessage());
            set_flash('error', 'Maaf, terjadi kendala saat menyimpan pesan Anda. Silakan coba lagi nanti.');
        }
    } else {
        set_flash('error', implode('<br>', $errors));
    }

    // Pola PRG (Post-Redirect-Get) untuk mencegah resubmission form saat refresh browser
    header('Location: index.php#contact');
    exit;
}

// --------------------------------------------------------------------------
// Query Data: Proyek Terkurasi (Projects)
// --------------------------------------------------------------------------
try {
    $projectsStmt = $pdo->query('
        SELECT * FROM `projects` 
        ORDER BY `featured` DESC, `sort_order` ASC, `created_at` DESC
    ');
    $projects = $projectsStmt->fetchAll();

    // Ekstrak daftar kategori unik untuk tombol filter
    $projectCategories = [];
    foreach ($projects as $proj) {
        $cat = trim($proj['category']);
        if ($cat !== '' && !in_array($cat, $projectCategories, true)) {
            $projectCategories[] = $cat;
        }
    }
} catch (PDOException $e) {
    error_log('[FETCH PROJECTS ERROR] ' . $e->getMessage());
    $projects = [];
    $projectCategories = [];
}

// --------------------------------------------------------------------------
// Query Data: Keahlian Teknis (Skills)
// --------------------------------------------------------------------------
try {
    $skillsStmt = $pdo->query('
        SELECT * FROM `skills`
        ORDER BY `category` ASC, `sort_order` ASC
    ');
    $allSkills = $skillsStmt->fetchAll();

    // Kelompokkan skill berdasarkan kategori
    $skillsByCategory = [];
    foreach ($allSkills as $skill) {
        $skillsByCategory[$skill['category']][] = $skill;
    }
} catch (PDOException $e) {
    error_log('[FETCH SKILLS ERROR] ' . $e->getMessage());
    $skillsByCategory = [];
}

// Ambil notifikasi pesan flash jika ada
$flash = get_flash();

// Set metadata judul & deskripsi
$pageTitle = 'Heru Perdana Saputra — Web Developer & System Analyst';
$pageDesc = 'Portofolio resmi Heru Perdana Saputra.';

// Load Header Template
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section id="hero" class="hero-section">
    <div class="container">
        <h1 class="hero-heading">
            Heru Perdana Saputra.
        </h1>

        <h2 class="hero-heading">
            JR. Web Developer
        </h2>



        <h3 class="hero-heading">
            Build. Learn. Improve.
        </h3>

        <p class="hero-text">
            Saya membangun, menguji, dan terus mengembangkan solusi digital melalui setiap proyek yang saya kerjakan.
        </p>

        <div class="hero-actions">
            <a href="#projects" class="btn btn-primary">Lihat Karya Terpilih</a>
            <a href="#contact" class="btn btn-secondary">Hubungi</a>
        </div>
    </div>
</section>

<hr class="section-divider">

<!-- Section Projects / Work -->
<section id="projects" class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Koleksi Karya</span>
            <h2 class="section-title">Proyek &amp; Eksplorasi Terpilih</h2>
            <p class="section-desc">
                Setiap karya dibangun dengan perhatian penuh pada kode yang bersih, keamanan data, dan antarmuka tanpa distraksi.
            </p>
        </div>

        <!-- Filter Kategori Client-Side (Vanilla JS) -->
        <?php if (!empty($projects) && !empty($projectCategories)): ?>
            <div class="filter-nav" role="tablist" aria-label="Filter Kategori Proyek">
                <button type="button" class="filter-btn active" data-filter="all">Semua (<?= count($projects); ?>)</button>
                <?php foreach ($projectCategories as $pCat): ?>
                    <button type="button" class="filter-btn" data-filter="<?= e($pCat); ?>">
                        <?= e($pCat); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="projects-grid">
            <?php if (!empty($projects)): ?>
                <?php foreach ($projects as $project): ?>
                    <article class="project-card" data-category="<?= e($project['category']); ?>">
                        <div class="project-image-box">
                            <?php if (!empty($project['image']) && file_exists(__DIR__ . '/' . $project['image'])): ?>
                                <img src="<?= e($project['image']); ?>" alt="<?= e($project['title']); ?>" class="project-image" loading="lazy">
                            <?php else: ?>
                                <div class="project-image-placeholder">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                    </svg>
                                    <span>Tampilan Proyek Minimalis</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="project-body">
                            <div class="project-meta">
                                <span class="project-category"><?= e($project['category']); ?></span>
                                <?php if (!empty($project['featured'])): ?>
                                    <span class="project-badge-featured">Utama</span>
                                <?php endif; ?>
                            </div>

                            <h3 class="project-title">
                                <a href="project-detail.php?slug=<?= e($project['slug']); ?>" style="color: inherit; text-decoration: none;">
                                    <?= e($project['title']); ?>
                                </a>
                            </h3>
                            <p class="project-excerpt"><?= e($project['excerpt']); ?></p>

                            <!-- Tech Stack Tags -->
                            <?php if (!empty($project['tech_stack'])): ?>
                                <div class="project-tech">
                                    <?php
                                    $tags = array_map('trim', explode(',', $project['tech_stack']));
                                    foreach ($tags as $tag):
                                    ?>
                                        <span class="tech-tag"><?= e($tag); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Action Links -->
                            <div class="project-links">
                                <a href="project-detail.php?slug=<?= e($project['slug']); ?>" class="project-link" style="font-weight: 600; color: var(--text-primary);">
                                    Studi Kasus &rarr;
                                </a>

                                <?php if (!empty($project['demo_url'])): ?>
                                    <a href="<?= e($project['demo_url']); ?>" target="_blank" rel="noopener noreferrer" class="project-link">
                                        Demo
                                    </a>
                                <?php endif; ?>

                                <?php if (!empty($project['github_url'])): ?>
                                    <a href="<?= e($project['github_url']); ?>" target="_blank" rel="noopener noreferrer" class="project-link">
                                        GitHub
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback UI Menarik Jika Data Kosong -->
                <div class="empty-box">
                    <h3>Belum Ada Proyek yang Ditampilkan</h3>
                    <p>Proyek baru sedang dalam tahap penyempurnaan kode dan dokumentasi.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<hr class="section-divider">

<!-- Section Skills / Focus (No Fake Progress Bars) -->
<section id="skills" class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Fokus &amp; Kapabilitas</span>
            <h2 class="section-title">Keahlian Rekayasa Software</h2>
            <p class="section-desc">
                Teknologi yang saya gunakan secara mendalam tanpa persentase kemampuan buatan.
            </p>
        </div>

        <div class="skills-container">
            <?php if (!empty($skillsByCategory)): ?>
                <?php foreach ($skillsByCategory as $categoryName => $skills): ?>
                    <div class="skill-category-card">
                        <h3 class="skill-category-title">
                            <?= e($categoryName); ?>
                        </h3>
                        <div class="skill-items">
                            <?php foreach ($skills as $skill): ?>
                                <div class="skill-item">
                                    <div class="skill-name"><?= e($skill['name']); ?></div>
                                    <?php if (!empty($skill['description'])): ?>
                                        <div class="skill-desc"><?= e($skill['description']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-box">
                    <h3>Data Keahlian Belum Terisi</h3>
                    <p>Daftar kompetensi teknis akan segera diperbarui.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<hr class="section-divider">

<!-- Section About & Philosophy -->
<section id="about" class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Filosofi &amp; Prinsip Kerja</span>
            <h2 class="section-title">Menghargai Kejelasan di Atas Kompleksitas</h2>
        </div>

        <div class="about-grid">
            <div class="about-narrative">
                <p>
                    Sebagai pengembang web junior, saya percaya bahwa 
                    perangkat lunak yang hebat tidak lahir dari kerumitan dependensi pihak ketiga yang tidak perlu, 
                    melainkan dari pemahaman mendalam atas protokol, struktur data, dan keamanan.
                </p>
                <p>
                    Dengan memanfaatkan PHP Native terstruktur dan CSS modern, saya mendedikasikan proyek ini untuk mendalami cara kerja aplikasi dari akarnya. Ini adalah bagian dari perjalanan belajar saya dalam mewujudkan aplikasi web yang cepat, aman, dan rapi.
                </p>
            </div>

            <div class="philosophy-card">
                <h3 class="philosophy-title">Nilai Utama yang Saya Pegang</h3>
                <ul class="philosophy-list">
                    <li class="philosophy-item">
                        <span class="philosophy-bullet"></span>
                        <span><strong>Keamanan sebagai Prioritas:</strong> Tidak ada kompromi terhadap validasi input, sanitasi output XSS, dan prepared statements database.</span>
                    </li>
                    <li class="philosophy-item">
                        <span class="philosophy-bullet"></span>
                        <span><strong>Performa Ringan:</strong> Meminimalkan payload aset agar situs dapat dimuat seketika bahkan di koneksi terbatas.</span>
                    </li>
                    <li class="philosophy-item">
                        <span class="philosophy-bullet"></span>
                        <span><strong>Estetika Zen:</strong> Tipografi terbaca nyaman, warna lembut di mata, dan ruang kosong (whitespace) yang lega.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<hr class="section-divider">

<!-- Section Contact -->
<section id="contact" class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Hubungi Saya</span>
            <h2 class="section-title">Mari Bekerja Sama</h2>
            <p class="section-desc">
                Apakah Anda memiliki proyek yang butuh sentuhan kode rapi atau sekadar ingin bertukar pikiran seputar arsitektur web?
            </p>
        </div>

        <div class="contact-grid">
            <!-- Informasi & Media Sosial -->
            <div class="contact-info">
                <h3 class="contact-info-title">Ruang Diskusi Terbuka</h3>
                <p class="contact-info-text">
                    Saya selalu antusias berdiskusi mengenai proyek open source, pengembangan web ramah aksesibilitas, 
                    atau peluang kerja kolaboratif.
                </p>

                <div class="contact-methods">
                    <div class="contact-method-item">
                        <span class="contact-method-label">Lokasi</span>
                        <span class="contact-method-value">Padang, Indonesia (UTC+7 / WIB)</span>
                    </div>
                    <div class="contact-method-item">
                        <span class="contact-method-label">Email Langsung</span>
                        <a href="mailto:heruperdanaaa@gmail.com" class="contact-method-value">heruperdanaaa@gmail.com</a>
                    </div>
                    <div class="contact-method-item">
                        <span class="contact-method-label">GitHub</span>
                        <a href="https://github.com/14heru" target="_blank" rel="noopener noreferrer" class="contact-method-value">github.com/14heru</a>
                    </div>
                </div>
            </div>

            <!-- Form Kontak dengan Proteksi CSRF & Sanitasi -->
            <div class="form-box">
                <!-- Tampilkan Alert Flash Message jika ada -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?= ($flash['type'] === 'success') ? 'success' : 'error'; ?>">
                        <?= $flash['message']; // pesan sudah disanitasi saat pemanggilan ?>
                    </div>
                <?php endif; ?>

                <form action="index.php" method="POST" novalidate>
                    <!-- Proteksi Wajib CSRF Field -->
                    <?= csrf_field(); ?>

                    <div class="form-group">
                        <label for="name" class="form-label">Nama Lengkap</label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Budi Santoso" required>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="nama@domain.com" required>
                    </div>

                    <div class="form-group">
                        <label for="subject" class="form-label">Subjek</label>
                        <input type="text" id="subject" name="subject" class="form-control" placeholder="Tujuan pesan atau nama proyek" required>
                    </div>

                    <div class="form-group">
                        <label for="message" class="form-label">Pesan</label>
                        <textarea id="message" name="message" class="form-control" placeholder="Tuliskan ide atau pesan Anda di sini..." required></textarea>
                    </div>

                    <button type="submit" name="send_message" value="1" class="btn btn-primary" style="width: 100%;">
                        Kirim Pesan
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
// Load Footer Template
require_once __DIR__ . '/includes/footer.php';
