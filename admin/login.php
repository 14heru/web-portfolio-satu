<?php
/**
 * ============================================================================
 * Halaman Otentikasi Masuk Admin (admin/login.php)
 * ============================================================================
 * Standar Keamanan:
 * - Session Hijacking Prevention: session_regenerate_id(true) setelah sukses login
 * - CSRF Token Verification via verify_csrf_token()
 * - Password Verification via password_verify() (Bcrypt / Argon2id)
 * - Safe Flash Message untuk pesan error
 * - Sanitasi output anti-XSS via e()
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika admin sudah login, langsung alihkan ke dashboard
if (is_admin_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

// Proses formulir login (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validasi Token CSRF Wajib
    require_csrf_valid();

    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        set_flash('error', 'Silakan masukkan nama pengguna dan kata sandi.');
        header('Location: login.php');
        exit;
    }

    $pdo = getDB();

    try {
        // Query user dengan Prepared Statement
        $stmt = $pdo->prepare('
            SELECT id, username, email, password 
            FROM `users` 
            WHERE username = :username 
            LIMIT 1
        ');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // Verifikasi password hash (Bcrypt / Argon2id)
        if ($user && password_verify($password, $user['password'])) {
            // Mitigasi Session Fixation: Regenerasi Session ID
            session_regenerate_id(true);

            // Simpan status sesi login
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = (int) $user['id'];
            $_SESSION['admin_username']  = $user['username'];
            $_SESSION['admin_email']     = $user['email'];

            set_flash('success', 'Selamat datang kembali, ' . e($user['username']) . '!');
            header('Location: dashboard.php');
            exit;
        } else {
            // Pesan error umum (Generic error message) untuk mencegah username enumeration
            set_flash('error', 'Kombinasi nama pengguna atau kata sandi tidak sesuai.');
            header('Location: login.php');
            exit;
        }
    } catch (PDOException $e) {
        error_log('[LOGIN ERROR] ' . $e->getMessage());
        set_flash('error', 'Terjadi kesalahan sistem saat memproses login.');
        header('Location: login.php');
        exit;
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Admin — Portfolio Zen</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../public/css/style.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
            background-color: var(--bg-subtle);
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-surface);
            border: 1px solid var(--border-soft);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.02);
        }
        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-primary);
            letter-spacing: -0.02em;
            margin-top: 8px;
        }
        .login-header p {
            font-size: 0.88rem;
            color: var(--text-secondary);
            margin-top: 4px;
        }
        .back-link {
            display: inline-block;
            margin-top: 24px;
            text-align: center;
            width: 100%;
            font-size: 0.86rem;
            color: var(--text-secondary);
        }
        .back-link:hover {
            color: var(--text-primary);
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <span class="brand-dot" style="margin: 0 auto 12px; width: 10px; height: 10px;"></span>
                <h1>Panel Administrasi</h1>
                <p>Masuk untuk mengelola karya &amp; pesan masuk</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?= ($flash['type'] === 'success') ? 'success' : 'error'; ?>">
                    <?= e($flash['message']); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" novalidate>
                <!-- CSRF Protection Field -->
                <?= csrf_field(); ?>

                <div class="form-group">
                    <label for="username" class="form-label">Nama Pengguna (Username)</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Nama pengguna" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Kata Sandi</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                    Masuk ke Dashboard
                </button>
            </form>

            <a href="../index.php" class="back-link">&larr; Kembali ke Beranda Publik</a>
        </div>
    </div>

</body>
</html>
