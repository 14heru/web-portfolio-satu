<?php
/**
 * ============================================================================
 * Panel Kendali Utama Administrator (admin/dashboard.php)
 * ============================================================================
 * Fitur:
 * - Proteksi akses: Wajib autentikasi sesi admin
 * - Manajemen Proyek: Ringkasan daftar, tombol hapus aman dengan CSRF token
 * - Manajemen Pesan Masuk: Baca pesan pengunjung, tandai telah dibaca, hapus
 * - Navigasi ringkas ke Tambah Proyek dan Keluar (Logout)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Login
require_admin_login('login.php');

$pdo = getDB();

// --------------------------------------------------------------------------
// Handler Aksi POST: Hapus Proyek / Tindakan Pesan (Protected with CSRF)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_valid();

    $action = $_POST['action'] ?? '';

    // 1. Aksi Hapus Proyek
    if ($action === 'delete_project') {
        $projectId = (int) ($_POST['project_id'] ?? 0);
        if ($projectId > 0) {
            try {
                // Ambil path gambar proyek jika ada, untuk dihapus dari disk
                $imgStmt = $pdo->prepare('SELECT image FROM `projects` WHERE id = :id');
                $imgStmt->execute([':id' => $projectId]);
                $oldImage = $imgStmt->fetchColumn();

                $delStmt = $pdo->prepare('DELETE FROM `projects` WHERE id = :id');
                $delStmt->execute([':id' => $projectId]);

                // Hapus file gambar dari server jika ada
                if ($oldImage && file_exists(__DIR__ . '/../' . $oldImage)) {
                    @unlink(__DIR__ . '/../' . $oldImage);
                }

                set_flash('success', 'Proyek berhasil dihapus.');
            } catch (PDOException $e) {
                error_log('[DELETE PROJECT ERROR] ' . $e->getMessage());
                set_flash('error', 'Gagal menghapus proyek.');
            }
        }
        header('Location: dashboard.php');
        exit;
    }

    // 2. Aksi Tandai Pesan Telah Dibaca
    if ($action === 'mark_read') {
        $contactId = (int) ($_POST['contact_id'] ?? 0);
        if ($contactId > 0) {
            try {
                $stmt = $pdo->prepare('UPDATE `contacts` SET is_read = 1 WHERE id = :id');
                $stmt->execute([':id' => $contactId]);
                set_flash('success', 'Pesan ditandai sebagai telah dibaca.');
            } catch (PDOException $e) {
                error_log('[MARK READ ERROR] ' . $e->getMessage());
            }
        }
        header('Location: dashboard.php#messages');
        exit;
    }

    // 3. Aksi Hapus Pesan Kontak
    if ($action === 'delete_contact') {
        $contactId = (int) ($_POST['contact_id'] ?? 0);
        if ($contactId > 0) {
            try {
                $stmt = $pdo->prepare('DELETE FROM `contacts` WHERE id = :id');
                $stmt->execute([':id' => $contactId]);
                set_flash('success', 'Pesan berhasil dihapus.');
            } catch (PDOException $e) {
                error_log('[DELETE CONTACT ERROR] ' . $e->getMessage());
                set_flash('error', 'Gagal menghapus pesan.');
            }
        }
        header('Location: dashboard.php#messages');
        exit;
    }
}

// --------------------------------------------------------------------------
// Fetch Data Proyek & Kontak
// --------------------------------------------------------------------------
try {
    $projects = $pdo->query('
        SELECT * FROM `projects` 
        ORDER BY `featured` DESC, `sort_order` ASC, `created_at` DESC
    ')->fetchAll();

    $contacts = $pdo->query('
        SELECT * FROM `contacts` 
        ORDER BY `created_at` DESC
    ')->fetchAll();
    
    // Hitung pesan yang belum dibaca
    $unreadCount = 0;
    foreach ($contacts as $c) {
        if ((int)$c['is_read'] === 0) {
            $unreadCount++;
        }
    }
} catch (PDOException $e) {
    error_log('[DASHBOARD DATA ERROR] ' . $e->getMessage());
    $projects = [];
    $contacts = [];
    $unreadCount = 0;
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin — Heru Perdana Saputra — Web Developer & System Analyst</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .dashboard-layout {
            padding: 36px 0 80px;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            padding-bottom: 24px;
            margin-bottom: 32px;
            border-bottom: 1px solid var(--border-soft);
        }
        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.88rem;
            color: var(--text-secondary);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-md);
            padding: 20px 24px;
        }
        .stat-label {
            font-size: 0.82rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-top: 6px;
            letter-spacing: -0.02em;
        }
        .table-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 48px;
        }
        .table-header {
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-soft);
        }
        .table-title {
            font-size: 1.15rem;
            font-weight: 600;
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
            padding: 14px 20px;
            font-size: 0.9rem;
            border-bottom: 1px solid var(--border-soft);
        }
        th {
            background-color: var(--bg-subtle);
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.8rem;
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
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: var(--radius-sm);
            font-weight: 600;
        }
        .badge-success { background: #EDF7EE; color: #1E4620; }
        .badge-warning { background: #FFF8E1; color: #B78103; }
        .badge-subtle  { background: var(--bg-muted); color: var(--text-secondary); }
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
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
        .msg-text {
            max-width: 320px;
            white-space: pre-wrap;
            line-height: 1.5;
            color: var(--text-secondary);
        }
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <div class="container dashboard-layout">
        <!-- Dashboard Navigation -->
        <header class="dashboard-header">
            <div>
                <a href="../index.php" target="_blank" class="brand-link" style="margin-bottom: 6px;">
                    <span class="brand-dot"></span>
                    <span>Lihat Situs Publik &rarr;</span>
                </a>
                <div class="user-badge">
                    <span>Login sebagai: <strong><?= e($_SESSION['admin_username'] ?? 'Admin'); ?></strong></span>
                </div>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="skills.php" class="btn btn-secondary">Kelola Keahlian</a>
                <a href="project-add.php" class="btn btn-primary">+ Tambah Proyek</a>
                <a href="logout.php" class="btn btn-secondary">Keluar (Logout)</a>
            </div>
        </header>

        <!-- Flash Message -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= ($flash['type'] === 'success') ? 'success' : 'error'; ?>">
                <?= e($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Karya Proyek</div>
                <div class="stat-value"><?= count($projects); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pesan Masuk Total</div>
                <div class="stat-value"><?= count($contacts); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pesan Belum Dibaca</div>
                <div class="stat-value" style="color: <?= ($unreadCount > 0) ? '#D97706' : 'var(--text-primary)'; ?>;">
                    <?= $unreadCount; ?>
                </div>
            </div>
        </div>

        <!-- 1. Tabel Daftar Proyek -->
        <div class="table-card">
            <div class="table-header">
                <h2 class="table-title">Daftar Proyek Portofolio</h2>
                <a href="project-add.php" class="btn btn-secondary btn-sm">+ Buat Proyek</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Judul Proyek</th>
                            <th>Kategori</th>
                            <th>Tech Stack</th>
                            <th>Featured</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($projects)): ?>
                            <?php foreach ($projects as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($p['title']); ?></strong>
                                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 2px;">
                                            Slug: <?= e($p['slug']); ?>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-subtle"><?= e($p['category']); ?></span></td>
                                    <td style="font-size: 0.82rem; color: var(--text-secondary); max-width: 200px;">
                                        <?= e($p['tech_stack']); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['featured'])): ?>
                                            <span class="badge badge-success">Featured</span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted); font-size: 0.8rem;">Biasa</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <!-- Tombol Edit Proyek -->
                                        <a href="project-edit.php?id=<?= (int) $p['id']; ?>" class="btn btn-secondary btn-sm" style="display: inline-block; margin-right: 6px;">
                                            Edit
                                        </a>

                                        <!-- Hapus Proyek dengan Konfirmasi & CSRF Field -->
                                        <form action="dashboard.php" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus proyek ini? Berkas gambar juga akan dibersihkan.');" style="display: inline;">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="project_id" value="<?= (int) $p['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                    Belum ada data proyek. Silakan tambahkan proyek pertama Anda.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. Tabel Pesan Masuk dari Kontak -->
        <div id="messages" class="table-card">
            <div class="table-header">
                <h2 class="table-title">Pesan Pengunjung Masuk (Contacts)</h2>
                <span class="badge <?= ($unreadCount > 0) ? 'badge-warning' : 'badge-subtle'; ?>">
                    <?= $unreadCount; ?> Belum Dibaca
                </span>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Pengirim</th>
                            <th>Subjek &amp; Pesan</th>
                            <th>Waktu Kirim</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($contacts)): ?>
                            <?php foreach ($contacts as $c): ?>
                                <tr>
                                    <td>
                                        <strong><?= e($c['name']); ?></strong>
                                        <div style="font-size: 0.8rem; margin-top: 2px;">
                                            <a href="mailto:<?= e($c['email']); ?>" style="color: var(--text-secondary); text-decoration: underline;">
                                                <?= e($c['email']); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                            <?= e($c['subject']); ?>
                                        </div>
                                        <div class="msg-text"><?= e($c['message']); ?></div>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                                        <?= date('d M Y, H:i', strtotime($c['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$c['is_read'] === 1): ?>
                                            <span class="badge badge-subtle">Dibaca</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Baru</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space: nowrap;">
                                        <?php if ((int)$c['is_read'] === 0): ?>
                                            <form action="dashboard.php" method="POST" style="display: inline;">
                                                <?= csrf_field(); ?>
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="contact_id" value="<?= (int) $c['id']; ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm">Tandai Dibaca</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="dashboard.php" method="POST" onsubmit="return confirm('Hapus pesan ini secara permanen?');" style="display: inline;">
                                            <?= csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_contact">
                                            <input type="hidden" name="contact_id" value="<?= (int) $c['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                    Belum ada pesan masuk dari pengunjung.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
