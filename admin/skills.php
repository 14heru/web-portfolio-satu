<?php
/**
 * ============================================================================
 * Manajemen Keahlian & Fokus Teknologi (admin/skills.php)
 * ============================================================================
 * Fitur & Keamanan:
 * - Proteksi akses sesi: require_admin_login()
 * - CSRF Token Verification pada setiap aksi POST
 * - Dual-mode Form (Tambah & Edit dalam satu halaman)
 * - Prepared Statements PDO mutlak (INSERT, UPDATE, DELETE)
 * - Sanitasi output anti-XSS via e()
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// 1. Wajib Sesi Login Admin
require_admin_login('login.php');

$pdo = getDB();

// 2. Handler Aksi POST (Tambah, Edit, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_valid();

    $action = $_POST['action'] ?? '';

    // Aksi: Simpan Keahlian Baru (INSERT)
    if ($action === 'create_skill') {
        $name        = trim($_POST['name'] ?? '');
        $category    = trim($_POST['category'] ?? 'Backend');
        $description = trim($_POST['description'] ?? '');
        $sort_order  = (int) ($_POST['sort_order'] ?? 0);

        if ($name === '' || mb_strlen($name) > 80) {
            set_flash('error', 'Nama keahlian wajib diisi dan maksimal 80 karakter.');
        } else {
            try {
                $stmt = $pdo->prepare('
                    INSERT INTO `skills` (`name`, `category`, `description`, `sort_order`)
                    VALUES (:name, :category, :description, :sort_order)
                ');
                $stmt->execute([
                    ':name'        => $name,
                    ':category'    => $category,
                    ':description' => ($description !== '') ? $description : null,
                    ':sort_order'  => $sort_order,
                ]);
                set_flash('success', 'Keahlian "' . e($name) . '" berhasil ditambahkan.');
            } catch (PDOException $e) {
                error_log('[SKILL INSERT ERROR] ' . $e->getMessage());
                set_flash('error', 'Gagal menambahkan keahlian ke basis data.');
            }
        }
        header('Location: skills.php');
        exit;
    }

    // Aksi: Perbarui Keahlian (UPDATE)
    if ($action === 'update_skill') {
        $id          = (int) ($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $category    = trim($_POST['category'] ?? 'Backend');
        $description = trim($_POST['description'] ?? '');
        $sort_order  = (int) ($_POST['sort_order'] ?? 0);

        if ($id <= 0 || $name === '' || mb_strlen($name) > 80) {
            set_flash('error', 'Data keahlian tidak valid untuk diperbarui.');
        } else {
            try {
                $stmt = $pdo->prepare('
                    UPDATE `skills`
                    SET `name`        = :name,
                        `category`    = :category,
                        `description` = :description,
                        `sort_order`  = :sort_order
                    WHERE `id` = :id
                ');
                $stmt->execute([
                    ':name'        => $name,
                    ':category'    => $category,
                    ':description' => ($description !== '') ? $description : null,
                    ':sort_order'  => $sort_order,
                    ':id'          => $id,
                ]);
                set_flash('success', 'Keahlian "' . e($name) . '" berhasil diperbarui.');
            } catch (PDOException $e) {
                error_log('[SKILL UPDATE ERROR] ' . $e->getMessage());
                set_flash('error', 'Gagal memperbarui keahlian.');
            }
        }
        header('Location: skills.php');
        exit;
    }

    // Aksi: Hapus Keahlian (DELETE)
    if ($action === 'delete_skill') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM `skills` WHERE `id` = :id');
                $stmt->execute([':id' => $id]);
                set_flash('success', 'Keahlian berhasil dihapus.');
            } catch (PDOException $e) {
                error_log('[SKILL DELETE ERROR] ' . $e->getMessage());
                set_flash('error', 'Gagal menghapus keahlian.');
            }
        }
        header('Location: skills.php');
        exit;
    }
}

// 3. Cek Mode Edit via GET ?edit=X
$editSkill = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    if ($editId > 0) {
        $stmt = $pdo->prepare('SELECT * FROM `skills` WHERE `id` = :id LIMIT 1');
        $stmt->execute([':id' => $editId]);
        $editSkill = $stmt->fetch();
    }
}

// 4. Ambil Seluruh Daftar Keahlian (Query PDO)
try {
    $skillsStmt = $pdo->query('
        SELECT * FROM `skills`
        ORDER BY `category` ASC, `sort_order` ASC, `id` ASC
    ');
    $skills = $skillsStmt->fetchAll();
} catch (PDOException $e) {
    error_log('[FETCH SKILLS ADMIN ERROR] ' . $e->getMessage());
    $skills = [];
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Keahlian — Panel Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .admin-layout {
            padding: 36px 0 80px;
        }
        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
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
        .skills-grid-admin {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 32px;
            align-items: start;
        }
        .card-box {
            background: var(--bg-surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border-soft);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .card-body {
            padding: 24px;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 12px 18px;
            font-size: 0.88rem;
            border-bottom: 1px solid var(--border-soft);
        }
        th {
            background-color: var(--bg-subtle);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        tr:last-child td {
            border-bottom: 0;
        }
        tr:hover td {
            background-color: #FAFAFC;
        }
        .badge {
            display: inline-block;
            font-size: 0.74rem;
            padding: 2px 8px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            background: var(--bg-muted);
            color: var(--text-secondary);
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 0.78rem;
            border-radius: var(--radius-sm);
        }
        .btn-danger {
            background: #FDEDED;
            color: #C53030;
            border: 1px solid #FEB2B2;
        }
        .btn-danger:hover {
            background: #FEB2B2;
            color: #9B2C2C;
        }
        @media (max-width: 860px) {
            .skills-grid-admin {
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
                <span>Manajemen Keahlian &amp; Fokus Teknologi</span>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="dashboard.php" class="btn btn-secondary">&larr; Dashboard</a>
                <a href="logout.php" class="btn btn-secondary">Keluar</a>
            </div>
        </header>

        <?php if ($flash): ?>
            <div class="alert alert-<?= ($flash['type'] === 'success') ? 'success' : 'error'; ?>">
                <?= e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="skills-grid-admin">
            <!-- Kolom Kiri: Tabel Daftar Keahlian -->
            <div class="card-box">
                <div class="card-header">
                    <h2 class="card-title">Daftar Keahlian Aktif</h2>
                    <span class="badge"><?= count($skills); ?> Keahlian</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th>Nama Keahlian</th>
                                <th>Urutan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($skills)): ?>
                                <?php foreach ($skills as $s): ?>
                                    <tr>
                                        <td>
                                            <span class="badge"><?= e($s['category']); ?></span>
                                        </td>
                                        <td>
                                            <strong><?= e($s['name']); ?></strong>
                                            <?php if (!empty($s['description'])): ?>
                                                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 2px;">
                                                    <?= e($s['description']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: var(--text-muted); font-size: 0.82rem;">
                                            <?= (int) $s['sort_order']; ?>
                                        </td>
                                        <td style="white-space: nowrap;">
                                            <a href="skills.php?edit=<?= (int)$s['id']; ?>" class="btn btn-secondary btn-sm" style="display: inline-block; margin-right: 4px;">
                                                Edit
                                            </a>

                                            <form action="skills.php" method="POST" onsubmit="return confirm('Hapus keahlian ini?');" style="display: inline;">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_skill">
                                                <input type="hidden" name="id" value="<?= (int)$s['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                        Belum ada data keahlian.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Kolom Kanan: Form Tambah / Edit Keahlian -->
            <div class="card-box">
                <div class="card-header">
                    <h2 class="card-title">
                        <?= $editSkill ? 'Ubah Keahlian' : 'Tambah Keahlian Baru'; ?>
                    </h2>
                    <?php if ($editSkill): ?>
                        <a href="skills.php" style="font-size: 0.82rem; color: var(--text-secondary); text-decoration: underline;">
                            + Tambah Baru
                        </a>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <form action="skills.php" method="POST" novalidate>
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="<?= $editSkill ? 'update_skill' : 'create_skill'; ?>">

                        <?php if ($editSkill): ?>
                            <input type="hidden" name="id" value="<?= (int)$editSkill['id']; ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label for="name" class="form-label">Nama Keahlian *</label>
                            <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: PHP Native &amp; PDO" required value="<?= e($editSkill['name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="category" class="form-label">Kategori *</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="Backend" <?= (($editSkill['category'] ?? '') === 'Backend') ? 'selected' : ''; ?>>Backend</option>
                                <option value="Frontend" <?= (($editSkill['category'] ?? '') === 'Frontend') ? 'selected' : ''; ?>>Frontend</option>
                                <option value="Database" <?= (($editSkill['category'] ?? '') === 'Database') ? 'selected' : ''; ?>>Database</option>
                                <option value="Tools &amp; Workflow" <?= (($editSkill['category'] ?? '') === 'Tools & Workflow') ? 'selected' : ''; ?>>Tools &amp; Workflow</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="sort_order" class="form-label">Urutan Tampil (Sort Order)</label>
                            <input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= (int) ($editSkill['sort_order'] ?? 0); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Deskripsi Ringkas (Opsional)</label>
                            <textarea id="description" name="description" class="form-control" style="min-height: 90px;" placeholder="Penjelasan singkat ruang lingkup atau kapabilitas..."><?= e($editSkill['description'] ?? ''); ?></textarea>
                        </div>

                        <div style="display: flex; gap: 10px; margin-top: 24px;">
                            <button type="submit" class="btn btn-primary" style="flex: 1;">
                                <?= $editSkill ? 'Simpan Perubahan' : 'Tambahkan Keahlian'; ?>
                            </button>
                            <?php if ($editSkill): ?>
                                <a href="skills.php" class="btn btn-secondary">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
