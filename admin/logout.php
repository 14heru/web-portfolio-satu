<?php
/**
 * ============================================================================
 * Keluar Sesi Admin (admin/logout.php)
 * ============================================================================
 * Standar Keamanan:
 * - Bersihkan array $_SESSION
 * - Hapus cookie sesi pada browser dengan parameter expire masa lalu
 * - Hancurkan data sesi di server via session_destroy()
 * - Redirect ke login.php dengan flash notification
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Kosongkan seluruh variabel sesi
$_SESSION = [];

// Hapus cookie sesi dari browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Hancurkan data sesi di server
session_destroy();

// Mulai sesi baru bersih untuk membawa pesan flash
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure'   => false,
]);

set_flash('success', 'Anda telah berhasil keluar dengan aman.');
header('Location: login.php');
exit;
