<?php
/**
 * ============================================================================
 * Kelola Tambah Proyek Portofolio (admin/project-add.php)
 * ============================================================================
 * Standar Keamanan:
 * - Proteksi Auth Sesi Admin via require_admin_login()
 * - CSRF Token Verification via require_csrf_valid()
 * - Sanitasi Input String & Validasi URL
 * - Secure Image Upload Validation (finfo MIME, Ext, Size, Uniq Random Hex)
 * - PDO Prepared Statements untuk Insert
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Login Admin
require_admin_login('login.php');

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Token CSRF
    require_csrf_valid();

    // 2. Ambil & Sanitasi Nilai Input
    $title       = trim($_POST['title'] ?? '');
    $category    = trim($_POST['category'] ?? 'Web Application');
    $excerpt     = trim($_POST['excerpt'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $tech_stack  = trim($_POST['tech_stack'] ?? '');
    $demo_url    = trim($_POST['demo_url'] ?? '');
    $github_url  = trim($_POST['github_url'] ?? '');
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $sort_order  = (int) ($_POST['sort_order'] ?? 0);

    $errors = [];

    // Validasi Wajib
    if ($title === '' || mb_strlen($title) > 150) {
        $errors[] = 'Judul proyek wajib diisi dan maksimal 150 karakter.';
    }

    if ($excerpt === '' || mb_strlen($excerpt) > 255) {
        $errors[] = 'Ringkasan singkat (excerpt) wajib diisi dan maksimal 255 karakter.';
    }

    if ($description === '') {
        $errors[] = 'Deskripsi lengkap proyek wajib diisi.';
    }

    if ($tech_stack === '') {
        $errors[] = 'Tech stack wajib diisi (pisahkan dengan koma).';
    }

    // Validasi Format URL (jika diisi)
    if ($demo_url !== '' && !filter_var($demo_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Format tautan Live Demo tidak valid (harus diawali http:// atau https://).';
    }

    if ($github_url !== '' && !filter_var($github_url, FILTER_VALIDATE_URL)) {
        $errors[] = 'Format tautan GitHub tidak valid (harus diawali http:// atau https://).';
    }

    // 3. Proses Unggah Gambar Aman
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $targetDir = __DIR__ . '/../uploads/projects';
        $uploadResult = handle_project_image_upload($_FILES['image'], $targetDir);

        if (!$uploadResult['success']) {
            $errors[] = $uploadResult['error'];
        } else {
            // Path relatif yang disimpan ke database (untuk dipakai tag <img>)
            $imagePath = 'uploads/projects/' . $uploadResult['filename'];
        }
    }

    // 4. Jika Tidak Ada Error, Simpan ke Database
    if (empty($errors)) {
        // Buat slug unik
        $baseSlug = slugify($title);
        $slug = $baseSlug;
        $counter = 1;

        // Cek duplikasi slug
        $slugStmt = $pdo->prepare('SELECT COUNT(*) FROM `projects` WHERE `slug` = :slug');
        while (true) {
            $slugStmt->execute([':slug' => $slug]);
            if ($slugStmt->fetchColumn() == 0) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        try {
            $stmt = $pdo->prepare('
                INSERT INTO `projects` 
                (`title`, `slug`, `category`, `excerpt`, `description`, `tech_stack`, `image`, `demo_url`, `github_url`, `featured`, `sort_order`, `created_at`)
                VALUES 
                (:title, :slug, :category, :excerpt, :description, :tech_stack, :image, :demo_url, :github_url, :featured, :sort_order, NOW())
            ');

            $stmt->execute([
                ':title'       => $title,
                ':slug'        => $slug,
                ':category'    => $category,
                ':excerpt'     => $excerpt,
                ':description' => $description,
                ':tech_stack'  => $tech_stack,
                ':image'       => $imagePath,
                ':demo_url'    => ($demo_url !== '') ? $demo_url : null,
                ':github_url'  => ($github_url !== '') ? $github_url : null,
                ':featured'    => $featured,
                ':sort_order'  => $sort_order,
            ]);

            set_flash('success', 'Proyek baru "' . e($title) . '" berhasil ditambahkan.');
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            error_log('[PROJECT INSERT ERROR] ' . $e->getMessage());
            set_flash('error', 'Gagal menyimpan proyek ke basis data.');
        }
    } else {
        set_flash('error', implode('<br>', $errors));
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Proyek Baru — Panel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .admin-layout {
            padding: 40px 0 80px;
        }
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 24px;
            margin-bottom: 32px;
            border-bottom: 1px solid var(--border-soft);
        }
        .admin-nav-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-lg);
            padding: 36px;
            max-width: 800px;
            margin: 0 auto;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-hint {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.92rem;
            color: var(--text-primary);
        }
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="container admin-layout">
        <header class="admin-nav">
            <div class="admin-nav-title">
                <span class="brand-dot"></span>
                <span>Tambah Proyek Baru</span>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-secondary">&larr; Kembali ke Dashboard</a>
            </div>
        </header>

        <div class="form-card">
            <?php if ($flash): ?>
                <div class="alert alert-<?= ($flash['type'] === 'success') ? 'success' : 'error'; ?>">
                    <?= $flash['message']; ?>
                </div>
            <?php endif; ?>

            <form action="project-add.php" method="POST" enctype="multipart/form-data" novalidate>
                <!-- CSRF Token Field Wajib -->
                <?= csrf_field(); ?>

                <div class="form-group">
                    <label for="title" class="form-label">Judul Proyek *</label>
                    <input type="text" id="title" name="title" class="form-control" placeholder="Contoh: ZenDesk — Customer Ticket Engine" required value="<?= e($_POST['title'] ?? ''); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category" class="form-label">Kategori *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="Web Application" <?= (($_POST['category'] ?? '') === 'Web Application') ? 'selected' : ''; ?>>Web Application</option>
                            <option value="Internal Tool" <?= (($_POST['category'] ?? '') === 'Internal Tool') ? 'selected' : ''; ?>>Internal Tool</option>
                            <option value="Open Source" <?= (($_POST['category'] ?? '') === 'Open Source') ? 'selected' : ''; ?>>Open Source</option>
                            <option value="API & Microservice" <?= (($_POST['category'] ?? '') === 'API & Microservice') ? 'selected' : ''; ?>>API &amp; Microservice</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort_order" class="form-label">Urutan Tampil (Sort Order)</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= e($_POST['sort_order'] ?? '0'); ?>">
                        <span class="form-hint">Nilai angka lebih kecil tampil lebih dulu</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="excerpt" class="form-label">Ringkasan Singkat (Excerpt) *</label>
                    <input type="text" id="excerpt" name="excerpt" class="form-control" placeholder="Satu atau dua kalimat penjelas inti proyek..." required value="<?= e($_POST['excerpt'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Deskripsi Lengkap *</label>
                    <textarea id="description" name="description" class="form-control" style="min-height: 140px;" placeholder="Jelaskan arsitektur teknis, tantangan pemecahan masalah, atau fitur utama..." required><?= e($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="tech_stack" class="form-label">Tech Stack (Dipisahkan Koma) *</label>
                    <input type="text" id="tech_stack" name="tech_stack" class="form-control" placeholder="Contoh: PHP Native, MySQL, CSS Grid, Vanilla JS" required value="<?= e($_POST['tech_stack'] ?? ''); ?>">
                    <span class="form-hint">Format: PHP 8, MySQL, CSS Variables (otomatis dipisah menjadi badge)</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="demo_url" class="form-label">Tautan Live Demo</label>
                        <input type="url" id="demo_url" name="demo_url" class="form-control" placeholder="https://demo.domain.com" value="<?= e($_POST['demo_url'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="github_url" class="form-label">Tautan GitHub / Source Code</label>
                        <input type="url" id="github_url" name="github_url" class="form-control" placeholder="https://github.com/username/repo" value="<?= e($_POST['github_url'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="image" class="form-label">Unggah Gambar Mockup/Screenshot (Opsional)</label>
                    <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <span class="form-hint">Format: JPG, PNG, WEBP. Ukuran maks 2MB. Di-rename acak aman secara otomatis.</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="1" <?= (!empty($_POST['featured'])) ? 'checked' : ''; ?>>
                        <span>Jadikan sebagai <strong>Proyek Unggulan (Featured)</strong> di halaman depan</span>
                    </label>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 32px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Simpan &amp; Publikasikan Proyek
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
