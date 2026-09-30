# Web Portfolio & Rekayasa Software — Heru Perdana Saputra

Portofolio web interaktif dan sistem manajemen konten yang dibangun dengan arsitektur PHP Native, MySQL, dan Pure Native CSS dengan estetika visual Zen Minimalist. Projek ini mengintegrasikan showcase karya rekayasa web, analisis sistem, keahlian teknis, serta fitur manajemen admin internal yang aman dan berkinerja tinggi.

---

## 🌟 Fitur Utama

### Sisi Publik (Client-Facing)

- **Desain Zen Minimalist & Anti-AI Slop**: Skema warna hangat membumi (_earthy off-white_ & _slate charcoal_), tipografi _Plus Jakarta Sans_, spasi lapang, tanpa gradien neon berlebihan.
- **100% Fully Responsive**: Fluid typography dengan CSS `clamp()`, layout grid non-squishy (`auto-fit`), dan menu navigasi mobile interaktif (hamburger slide-down).
- **Showcase Proyek Dinamis**: Mengambil data proyek dari MySQL dengan status featured, kategori, dan deskripsi ringkas.
- **Client-Side Category Filter**: Penyaringan kartu proyek instan menggunakan Vanilla JS ringan tanpa perlu memuat ulang halaman (_zero page reload_).
- **Halaman Detail Studi Kasus (`project-detail.php`)**: Menampilkan deskripsi teknis mendalam, mockup visual, tech stack, dan tautan Live Demo serta Source Code.
- **Formulir Kontak Aman**: Dilengkapi validasi multi-lapis, proteksi CSRF token otomatis, dan penyimpanan pesan pengunjung ke database.

### Sisi Administrasi (Admin Back-Office)

- **Autentikasi Terproteksi**: Verifikasi password hash Bcrypt (`PASSWORD_BCRYPT`), mitigasi session fixation (`session_regenerate_id()`), dan cookie berstandar `HttpOnly` serta `SameSite=Lax`.
- **Dashboard Ringkas**: Statistik jumlah karya proyek, pesan masuk, serta indikator pesan baru yang belum dibaca.
- **CRUD Proyek Lengkap**: Tambah, ubah (_edit pre-filled_), dan hapus proyek beserta berkas gambar fisik di server.
- **Validasi Unggah Gambar Ketat**: Verifikasi binary MIME-type via `finfo_file`, pembatasan ukuran maksimal 2MB, whitelist format (`JPG`, `PNG`, `WEBP`), dan rename otomatis acak (`bin2hex(random_bytes(8))`).
- **Manajemen Keahlian (`admin/skills.php`)**: Form terpadu Tambah, Edit, dan Hapus skill/keahlian teknis secara modular.
- **Folder Hardening (`uploads/.htaccess`)**: Proteksi direktori untuk mencegah eksekusi skrip PHP dari dalam folder upload.

---

## 🛡️ Standar Keamanan Sistem

1. **SQL Injection Free**: Seluruh query interaksi database menggunakan **PDO Prepared Statements** dengan `PDO::ATTR_EMULATE_PREPARES = false`.
2. **Cross-Site Scripting (XSS) Prevention**: Sanitasi output terpusat menggunakan helper function `e($string)` yang membungkus `htmlspecialchars` dengan flag `ENT_QUOTES` dan encoding UTF-8.
3. **Cross-Site Request Forgery (CSRF) Protection**: Token acak berbasis `bin2hex(random_bytes(32))` disematkan pada setiap form POST (`csrf_field()`) dan diverifikasi menggunakan `hash_equals()` untuk mencegah timing attacks.
4. **Directory Traversal & Code Execution Defense**: Folder `uploads/` dilindungi berkas `.htaccess` yang menolak eksekusi file script `.php`, `.phtml`, `.cgi`, serta menonaktifkan directory indexing.

---

## 📁 Struktur Direktori

```text
/web-portfolio-satu
├── .gitignore                  # Berkas pengecualian Git
├── LICENSE                     # Lisensi MIT (Heru Perdana Saputra)
├── README.md                   # Dokumentasi teknis proyek
├── schema.sql                  # Skema database & data awal (seed)
├── index.php                   # Halaman publik utama (Landing Page)
├── project-detail.php          # Halaman publik detail studi kasus
├── admin/                      # Panel kendali administrator
│   ├── dashboard.php           # Dashboard & manajemen pesan
│   ├── login.php               # Form otentikasi admin
│   ├── logout.php              # Pembersihan sesi aman
│   ├── project-add.php         # Formulir tambah proyek baru
│   ├── project-edit.php        # Formulir ubah proyek yang ada
│   └── skills.php              # Manajemen keahlian & fokus teknologi
├── config/                     # Konfigurasi sistem
│   ├── database.php            # Koneksi database PDO aktif
│   └── database.example.php    # Template konfigurasi database
├── includes/                   # Komponen modular reusable
│   ├── footer.php              # Template footer & penutup HTML
│   ├── functions.php           # Helper sanitasi, CSRF, sesi & upload
│   └── header.php              # Template header, meta & navbar
├── public/                     # Aset publik statis
│   ├── css/
│   │   └── style.css           # Design system pure CSS native
│   └── js/
│       └── main.js             # Skrip interaktif filter & mobile nav
└── uploads/                    # Direktori penyimpanan berkas unggahan
    ├── .htaccess               # Proteksi larangan eksekusi skrip
    └── projects/               # Subfolder gambar proyek
        └── .gitkeep            # Penjaga struktur direktori Git
```

---

## 🚀 Panduan Instalasi Lokal (Windows & XAMPP)

### 1. Persyaratan Sistem

- Web Server: Apache (via XAMPP)
- Database: MySQL / MariaDB 10.4+
- PHP: Versi 8.0 atau yang lebih baru dengan ekstensi `pdo_mysql` dan `fileinfo` aktif.

### 2. Penyiapan Berkas

Tempatkan folder proyek pada direktori web server lokal Anda:

```bash
C:\xampp\htdocs\web-portfolio-satu
```

### 3. Konfigurasi Basis Data

1. Buka **XAMPP Control Panel**, nyalakan modul **Apache** dan **MySQL**.
2. Buka phpMyAdmin (`http://localhost/phpmyadmin`) atau jalankan perintah CLI:
   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "SOURCE C:/xampp/htdocs/web-portfolio-satu/schema.sql;"
   ```
3. Database `web_portfolio_satu` beserta seluruh tabel (`users`, `projects`, `skills`, `contacts`) dan data seed awal akan otomatis terbuat.

### 4. Konfigurasi Kredensial

Pastikan pengaturan pada `config/database.php` telah sesuai dengan host lokal Anda:

```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'web_portfolio_satu');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 5. Akses Aplikasi

- **Situs Publik**: Buka peramban ke `http://localhost/web-portfolio-satu/`
- **Panel Admin**: Buka ke `http://localhost/web-portfolio-satu/admin/login.php` atau klik tautan diskret `Admin` pada footer.

---

## 📄 Lisensi

Projek ini dilisensikan di bawah naungan **[MIT License](LICENSE)** &copy; 2026 **Heru Perdana Saputra**.
