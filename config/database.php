<?php
/**
 * ============================================================================
 * Koneksi Basis Data Terpusat (PHP PDO)
 * ============================================================================
 * Arsitektur:
 * - Singleton / Reusable Connection Instance
 * - Strict Exception Mode (ERRMODE_EXCEPTION)
 * - Real Native Prepared Statements (ATTR_EMULATE_PREPARES = false)
 * - UTF8mb4 Full Unicode Character Set
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
            // Mode error: lemparkan exception langsung jika ada kegagalan query
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Default fetch: hanya array asosiatif untuk menghemat alokasi memori
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Mutlak: Matikan emulated prepared statements.
            // Memaksa driver MySQL memisahkan parsing SQL dengan data payload secara native.
            PDO::ATTR_EMULATE_PREPARES   => false,

            // Matikan persistent connection untuk mencegah stale locks di environment dev
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Jangan pernah mengekspos detail kredensial atau traceback ke browser publik!
            error_log('[DB CONNECTION ERROR] ' . $e->getMessage());

            // Tampilkan UI Fallback Minimalis Zen
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
                    <p>Layanan belum dapat terhubung ke basis data <code>web_portfolio_satu</code>. Pastikan modul MySQL di XAMPP telah berjalan dan skema <code>schema.sql</code> telah di-import.</p>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    return $pdo;
}
