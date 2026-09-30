<?php
/**
 * ============================================================================
 * Kelola Ubah / Edit Proyek Portofolio (admin/project-edit.php)
 * ============================================================================
 * Standar Keamanan:
 * - Proteksi Auth Sesi Admin via require_admin_login()
 * - CSRF Token Verification via require_csrf_valid()
 * - PDO Prepared Statements untuk Select & Update
 * - Validasi MIME-Type gambar aman, hapus gambar lama via unlink() jika diganti
 * - Sanitasi output anti-XSS via e()
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Login Admin
require_admin_login('login.php');

$pdo = getDB();

// Ambil ID proyek dari query string ?id=X
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    set_flash('error', 'ID proyek tidak valid.');
    header('Location: dashboard.php');
    exit;
}

// Ambil data proyek saat ini
$stmt = $pdo->prepare('SELECT * FROM `projects` WHERE `id` = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$project = $stmt->fetch();

if (!$project) {
    set_flash('error', 'Proyek tidak ditemukan.');
    header('Location: dashboard.php');
    exit;
}

// Proses formulir pembaruan proyek (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Token CSRF Wajib
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

    // 3. Tangani Unggah Gambar Baru (jika ada file baru yang diunggah)
    $imagePath = $project['image']; // Tetap gunakan gambar lama secara default

    if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $targetDir = __DIR__ . '/../uploads/projects';
        $uploadResult = handle_project_image_upload($_FILES['image'], $targetDir);

        if (!$uploadResult['success']) {
            $errors[] = $uploadResult['error'];
        } else {
            // Hapus gambar lama jika ada dan berkasnya benar-benar ada di disk
            if (!empty($project['image'])) {
                $oldFile = __DIR__ . '/../' . $project['image'];
                if (file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }

            // Simpan path relatif gambar baru
            $imagePath = 'uploads/projects/' . $uploadResult['filename'];
        }
    }

    // 4. Update Database jika tidak ada error
    if (empty($errors)) {
        // Buat slug baru jika judul berubah
        $slug = $project['slug'];
        if ($title !== $project['title']) {
            $baseSlug = slugify($title);
            $slug = $baseSlug;
            $counter = 1;

            $slugStmt = $pdo->prepare('SELECT COUNT(*) FROM `projects` WHERE `slug` = :slug AND `id` != :id');
            while (true) {
                $slugStmt->execute([':slug' => $slug, ':id' => $id]);
                if ((int)$slugStmt->fetchColumn() === 0) {
                    break;
                }
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        try {
            $updateStmt = $pdo->prepare('
                UPDATE `projects` 
                SET `title`       = :title,
                    `slug`        = :slug,
                    `category`    = :category,
                    `excerpt`     = :excerpt,
                    `description` = :description,
                    `tech_stack`  = :tech_stack,
                    `image`       = :image,
                    `demo_url`    = :demo_url,
                    `github_url`  = :github_url,
                    `featured`    = :featured,
                    `sort_order`  = :sort_order,
                    `updated_at`  = NOW()
                WHERE `id` = :id
            ');

            $updateStmt->execute([
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
                ':id'          => $id,
            ]);

            set_flash('success', 'Perubahan pada proyek "' . e($title) . '" berhasil disimpan.');
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $e) {
            error_log('[PROJECT UPDATE ERROR] ' . $e->getMessage());
            set_flash('error', 'Gagal memperbarui proyek di basis data.');
        }
    } else {
        set_flash('error', implode('<br>', $errors));
        // Sinkronisasi data form kembali jika ada error validasi
        $project['title']       = $title;
        $project['category']    = $category;
        $project['excerpt']     = $excerpt;
        $project['description'] = $description;
        $project['tech_stack']  = $tech_stack;
        $project['demo_url']    = $demo_url;
        $project['github_url']  = $github_url;
        $project['featured']    = $featured;
        $project['sort_order']  = $sort_order;
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Proyek: <?= e($project['title']); ?> — Panel Admin</title>
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
        .current-image-preview {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
            padding: 12px;
            background: var(--bg-subtle);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-sm);
        }
        .current-image-thumb {
            width: 80px;
            height: 54px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border-soft);
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
                <span>Edit Proyek: <?= e($project['title']); ?></span>
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

            <form action="project-edit.php?id=<?= (int)$project['id']; ?>" method="POST" enctype="multipart/form-data" novalidate>
                <!-- CSRF Token Field Wajib -->
                <?= csrf_field(); ?>

                <div class="form-group">
                    <label for="title" class="form-label">Judul Proyek *</label>
                    <input type="text" id="title" name="title" class="form-control" required value="<?= e($project['title']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category" class="form-label">Kategori *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="Web Application" <?= ($project['category'] === 'Web Application') ? 'selected' : ''; ?>>Web Application</option>
                            <option value="Internal Tool" <?= ($project['category'] === 'Internal Tool') ? 'selected' : ''; ?>>Internal Tool</option>
                            <option value="Open Source" <?= ($project['category'] === 'Open Source') ? 'selected' : ''; ?>>Open Source</option>
                            <option value="API & Microservice" <?= ($project['category'] === 'API & Microservice') ? 'selected' : ''; ?>>API &amp; Microservice</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sort_order" class="form-label">Urutan Tampil (Sort Order)</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= (int) $project['sort_order']; ?>">
                        <span class="form-hint">Nilai angka lebih kecil tampil lebih dulu</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="excerpt" class="form-label">Ringkasan Singkat (Excerpt) *</label>
                    <input type="text" id="excerpt" name="excerpt" class="form-control" required value="<?= e($project['excerpt']); ?>">
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Deskripsi Lengkap *</label>
                    <textarea id="description" name="description" class="form-control" style="min-height: 140px;" required><?= e($project['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="tech_stack" class="form-label">Tech Stack (Dipisahkan Koma) *</label>
                    <input type="text" id="tech_stack" name="tech_stack" class="form-control" required value="<?= e($project['tech_stack']); ?>">
                    <span class="form-hint">Format: PHP 8, MySQL, CSS Variables</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="demo_url" class="form-label">Tautan Live Demo</label>
                        <input type="url" id="demo_url" name="demo_url" class="form-control" placeholder="https://demo.domain.com" value="<?= e($project['demo_url'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="github_url" class="form-label">Tautan GitHub / Source Code</label>
                        <input type="url" id="github_url" name="github_url" class="form-control" placeholder="https://github.com/username/repo" value="<?= e($project['github_url'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="image" class="form-label">Gambar Proyek</label>
                    
                    <?php if (!empty($project['image']) && file_exists(__DIR__ . '/../' . $project['image'])): ?>
                        <div class="current-image-preview">
                            <img src="../<?= e($project['image']); ?>" alt="Gambar saat ini" class="current-image-thumb">
                            <div>
                                <div style="font-size: 0.85rem; font-weight: 500;">Gambar Saat Ini Terpasang</div>
                                <div style="font-size: 0.76rem; color: var(--text-muted); font-family: var(--font-mono);"><?= e($project['image']); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <input type="file" id="image" name="image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    <span class="form-hint">Unggah gambar baru jika ingin mengganti gambar lama (Maksimal 2MB, JPG/PNG/WEBP).</span>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" value="1" <?= (!empty($project['featured'])) ? 'checked' : ''; ?>>
                        <span>Jadikan sebagai <strong>Proyek Unggulan (Featured)</strong> di halaman depan</span>
                    </label>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 32px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Simpan Perubahan
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
