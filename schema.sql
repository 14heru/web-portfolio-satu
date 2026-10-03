-- ==========================================================
-- SKEMA BASIS DATA: web_portfolio_satu
-- Standar: Normalized, UTF8mb4, InnoDB, Production-Ready
-- ==========================================================

-- CREATE DATABASE IF NOT EXISTS `web_portfolio_satu`
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;

-- USE `web_portfolio_satu`;

-- ----------------------------------------------------------
-- 1. TABEL USERS (Autentikasi Administrator)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- Kompatibel Bcrypt / Argon2id
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. TABEL PROJECTS (Koleksi Portfolio Proyek)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL UNIQUE,
  `category` VARCHAR(60) NOT NULL DEFAULT 'Web Application',
  `excerpt` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `tech_stack` VARCHAR(255) NOT NULL, -- Contoh: "PHP Native, MySQL, Vanilla JS, CSS Grid"
  `image` VARCHAR(255) DEFAULT NULL,   -- Path relatif file: uploads/projects/hash.ext
  `demo_url` VARCHAR(255) DEFAULT NULL,
  `github_url` VARCHAR(255) DEFAULT NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_projects_slug` (`slug`),
  INDEX `idx_projects_sort` (`sort_order`, `created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. TABEL SKILLS (Keahlian & Fokus Teknologi — No Fake Progress Bars)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `skills`;
CREATE TABLE `skills` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(80) NOT NULL,
  `category` VARCHAR(50) NOT NULL, -- Backend, Frontend, Database, Tools & Workflow
  `description` VARCHAR(255) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  INDEX `idx_skills_category` (`category`, `sort_order` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. TABEL CONTACTS (Pesan Pengunjung Masuk)
-- ----------------------------------------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_contacts_created` (`created_at` DESC),
  INDEX `idx_contacts_is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED DATA AWAL (Initial Setup & Dummy Projects)
-- ==========================================================

-- Admin Default:
-- Username : Heruperdana
INSERT INTO `users` (`id`, `username`, `email`, `password`)
VALUES (
  1,
  'Heruperdana',
  'admin@portfolio.local',
  '$2y$10$yXyTDJkngt1rhfFjPo5tneHbKloAfkJI28AJxIAqtGLZ/LZO4HCiW'
);

-- Skills Default (Pendekatan Clean & Pragmatis)
INSERT INTO `skills` (`name`, `category`, `description`, `sort_order`) VALUES
('PHP Native & Modern PHP', 'Backend', 'Object-Oriented, PDO Prepared Statements, Session Security, RESTful endpoints.', 1),
('MySQL & Database Design', 'Backend', 'Skema relasional, indexing performa, integritas data referensial.', 2),
('Semantic HTML5 & Pure CSS', 'Frontend', 'Modern CSS Grid, Flexbox, Custom Properties, Responsive tanpa library gendut.', 3),
('Vanilla JavaScript (ES6+)', 'Frontend', 'DOM Manipulation, Fetch API asynchronous, Micro-interactions yang ringan.', 4),
('Git & Version Control', 'Tools & Workflow', 'Branching strategy, semantic commits, dan manajemen repositori.', 5),
('Web Security Basics', 'Tools & Workflow', 'Mitigasi XSS, CSRF protection, secure file upload validation, hash passwords.', 6);

-- Proyek Sampel (Zen Aesthetic)
INSERT INTO `projects` (`title`, `slug`, `category`, `excerpt`, `description`, `tech_stack`, `image`, `demo_url`, `github_url`, `featured`, `sort_order`) VALUES
(
  'Komorebi - Focused Editorial Platform',
  'komorebi-focused-editorial-platform',
  'Web Application',
  'Platform publikasi artikel bebas distraksi yang mengutamakan tipografi proporsional dan arsitektur backend hemat memori.',
  'Komorebi dirancang untuk mereka yang merindukan web yang tenang. Tidak ada iklan intrusif, tidak ada pop-up newsletter yang mengganggu, dan tidak ada aset berat yang memperlambat browser. Dibangun murni menggunakan arsitektur PHP Native terstruktur dan CSS native variables.',
  'PHP Native, MySQL, CSS Grid, Vanilla JS',
  NULL,
  'https://demo.example.com',
  'https://github.com/example/komorebi',
  1,
  1
),
(
  'Monolit - Minimalist Inventory Core',
  'monolit-minimalist-inventory-core',
  'Internal Tool',
  'Sistem manajemen inventaris internal berkecepatan tinggi dengan validasi transaksi data yang ketat.',
  'Sistem yang dikembangkan untuk menangani pencatatan stok gudang dengan efisiensi tinggi. Memanfaatkan PDO transaction locks untuk mencegah race conditions saat pembaruan jumlah barang secara simultan.',
  'PHP 8, MySQL PDO, Secure Session, CSS Flexbox',
  NULL,
  NULL,
  'https://github.com/example/monolit',
  1,
  2
);
