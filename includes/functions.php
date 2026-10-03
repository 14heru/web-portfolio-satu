<?php
/**
 * ============================================================================
 * Helper Keamanan, Sanitasi, Session & Validasi File Upload
 * ============================================================================
 * Standar Keamanan:
 * - Anti XSS (Cross-Site Scripting) via e() htmlspecialchars UTF-8
 * - Anti CSRF (Cross-Site Request Forgery) dengan Secure Random Tokens
 * - Secure Session Initialization & Regeneration
 * - Secure Image Upload Validation (MIME-Type, Extension, Random File Renaming)
 */

declare(strict_types=1);

// Mulai session jika belum aktif dengan flag keamanan cookie
if (session_status() === PHP_SESSION_NONE) {
    // Pengaturan Session Cookie yang Aman
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');

    // Deteksi protokol HTTPS secara dinamis
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_start([
        'cookie_httponly' => true,      // Mencegah pencurian cookie via JavaScript (XSS)
        'cookie_samesite' => 'Lax',       // Proteksi CSRF level browser
        'cookie_secure'   => $isHttps,  // Otomatis aktif saat koneksi menggunakan HTTPS
    ]);
}

/**
 * Helper Anti-XSS (HTML Escape)
 * 
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Menghasilkan CSRF Token yang aman dan disimpan di session
 * 
 * @return string Token CSRF
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render elemen HTML <input type="hidden"> berisi CSRF token
 * 
 * @return string Elemen input hidden
 */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Validasi token CSRF dari permintaan POST
 * Menggunakan hash_equals untuk mencegah serangan Timing Attack
 * 
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validasi CSRF wajib: Hentikan eksekusi jika token tidak valid
 * 
 * @return void
 */
function require_csrf_valid(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        die('
            <div style="font-family: -apple-system, sans-serif; text-align: center; padding: 50px;">
                <h2 style="color: #E53E3E;">403 — Permintaan Ditolak (CSRF Token Invalid)</h2>
                <p style="color: #718096;">Sesi Anda mungkin telah kedaluwarsa. Silakan muat ulang halaman formulir dan coba kembali.</p>
                <a href="javascript:history.back()" style="color: #4A5568; text-decoration: underline;">Kembali</a>
            </div>
        ');
    }
}

/**
 * Set flash message ke session (untuk alert sukses/gagal sekali baca)
 * 
 * @param string $type ('success' | 'error' | 'info')
 * @param string $message
 * @return void
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Ambil flash message dari session lalu langsung hapus
 * 
 * @return array|null
 */
function get_flash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Periksa apakah user admin sudah login
 * 
 * @return bool
 */
function is_admin_logged_in(): bool
{
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Wajibkan login admin untuk halaman back-office
 * 
 * @param string $redirectUrl
 * @return void
 */
function require_admin_login(string $redirectUrl = '../admin/login.php'): void
{
    if (!is_admin_logged_in()) {
        header("Location: {$redirectUrl}");
        exit;
    }
}

/**
 * Generate URL Slug dari string judul (misal: "Proyek Keren" -> "proyek-keren")
 * 
 * @param string $title
 * @return string
 */
function slugify(string $title): string
{
    // Ubah ke huruf kecil dan ganti karakter non-alfanumerik dengan strip
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'project-' . bin2hex(random_bytes(3));
}

/**
 * Validasi dan Proses Unggah Gambar Proyek
 * 
 * Memenuhi Spesifikasi:
 * 1. Validasi error upload bawaan PHP
 * 2. Cek ukuran file maksimal (default 2MB)
 * 3. Validasi ekstensi yang diizinkan (jpg, jpeg, png, webp)
 * 4. Validasi MIME-Type sebenarnya menggunakan finfo_file (bukan cuma percaya ekstensi client)
 * 5. Rename acak: bin2hex(random_bytes(8)) + '.' + ekstensi
 * 
 * @param array $fileInput Array $_FILES['input_name']
 * @param string $targetDir Direktori absolut tujuan simpan
 * @return array ['success' => bool, 'filename' => string|null, 'error' => string|null]
 */
function handle_project_image_upload(array $fileInput, string $targetDir): array
{
    // Jika tidak ada file yang dipilih (opsional)
    if (!isset($fileInput['error']) || $fileInput['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'filename' => null, 'error' => null];
    }

    // Tangani error bawaan PHP upload
    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'filename' => null, 'error' => 'Terjadi kesalahan sistem saat mengunggah berkas.'];
    }

    // Batas ukuran 2MB (2 * 1024 * 1024 bytes)
    $maxFileSize = 2 * 1024 * 1024;
    if ($fileInput['size'] > $maxFileSize) {
        return ['success' => false, 'filename' => null, 'error' => 'Ukuran berkas melebihi batas maksimal 2MB.'];
    }

    // Ekstensi yang diizinkan
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $originalName = (string) $fileInput['name'];
    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions, true)) {
        return ['success' => false, 'filename' => null, 'error' => 'Format berkas tidak diizinkan. Hanya menerima JPG, PNG, atau WEBP.'];
    }

    // Validasi MIME-Type sebenarnya dengan finfo_file (Mencegah script PHP disamarkan jadi gambar)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($fileInput['tmp_name']);
    $allowedMimes = [
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png'  => ['png'],
        'image/webp' => ['webp'],
    ];

    if (!array_key_exists($realMime, $allowedMimes)) {
        return ['success' => false, 'filename' => null, 'error' => 'Isi berkas tidak valid sebagai gambar yang aman.'];
    }

    // Standar Penamaan Spesifikasi: bin2hex(random_bytes(8))
    $randomHex = bin2hex(random_bytes(8));
    $newFileName = sprintf('%s.%s', $randomHex, $fileExtension);

    // Pastikan folder target ada
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $destination = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFileName;

    // Pindahkan berkas yang telah divalidasi
    if (!move_uploaded_file($fileInput['tmp_name'], $destination)) {
        return ['success' => false, 'filename' => null, 'error' => 'Gagal memindahkan berkas ke folder penyimpanan server.'];
    }

    return ['success' => true, 'filename' => $newFileName, 'error' => null];
}

/**
 * Mendapatkan Base URL proyek secara otomatis dan dinamis
 * Bekerja mulus di localhost (dengan subfolder misal /web-portfolio-satu)
 * maupun di hosting InfinityFree (root domain/subdomain).
 *
 * @param string $path Path relatif yang ingin disambung (opsional)
 * @return string Full URL atau root-relative URL yang aman
 */
function base_url(string $path = ''): string
{
    static $baseUrl = null;

    if ($baseUrl === null) {
        // Deteksi HTTPS
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $protocol = $isHttps ? 'https://' : 'http://';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Hitung direktori dasar proyek relatif terhadap DOCUMENT_ROOT
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '');
        $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: '');

        $subDir = '';
        if ($docRoot !== '' && str_starts_with($projectRoot, $docRoot)) {
            $subDir = substr($projectRoot, strlen($docRoot));
        }

        $subDir = trim($subDir, '/');
        $baseUrl = $protocol . $host . ($subDir !== '' ? '/' . $subDir : '');
    }

    $path = ltrim($path, '/');
    return $path !== '' ? $baseUrl . '/' . $path : $baseUrl;
}

/**
 * Helper untuk asset URL (CSS, JS, Images, Uploads)
 *
 * @param string $path Path relatif aset dari root proyek
 * @return string Full URL aset
 */
function asset_url(string $path = ''): string
{
    return base_url($path);
}

