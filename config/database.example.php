<?php
/**
 * ============================================================================
 * Database Configuration Template (Example)
 * ============================================================================
 * Salin berkas ini menjadi config/database.php pada server/lingkungan baru:
 *   cp config/database.example.php config/database.php
 *
 * Sesuaikan kredensial di bawah ini dengan konfigurasi basis data Anda.
 */

declare(strict_types=1);

// Konfigurasi Kredensial Database
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'web_portfolio_satu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Mendapatkan koneksi tunggal PDO
 * 
 * @return PDO
 * @throws PDOException jika terjadi kegagalan koneksi
 */
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[DB CONNECTION ERROR] ' . $e->getMessage());

            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Layanan Belum Siap</title>
                <style>
                    body {
                        margin: 0;
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        background-color: #F9F9FB;
                        color: #1A1A1E;
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    }
                    .box {
                        max-width: 440px;
                        padding: 32px;
                        background: #FFFFFF;
                        border: 1px solid #E2E8F0;
                        border-radius: 8px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
                    }
                    h1 { font-size: 18px; font-weight: 600; margin: 0 0 12px; color: #2D3748; }
                    p { font-size: 14px; line-height: 1.6; color: #64748B; margin: 0 0 16px; }
                    code { background: #F1F5F9; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
                </style>
            </head>
            <body>
                <div class="box">
                    <h1>Koneksi Basis Data Terputus</h1>
                    <p>Layanan belum dapat terhubung ke basis data. Pastikan konfigurasi kredensial pada berkas <code>config/database.php</code> sudah benar.</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    return $pdo;
}
