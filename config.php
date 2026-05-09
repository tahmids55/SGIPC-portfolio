<?php
// config.php - Database Configuration and Initialization (MySQL only)

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─────────────────────────────────────────────
//  MySQL Connection Settings
// ─────────────────────────────────────────────
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'sgipc_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// ─────────────────────────────────────────────
//  Establish MySQL PDO Connection
// ─────────────────────────────────────────────
try {
    // Connect to MySQL server (without a specific DB) to create the DB if needed
    $server_pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $server_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $server_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $server_pdo = null; // Close temp connection

    // Connect to the sgipc_db database
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Initialize all tables and seed default admin
    init_database($pdo);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ─────────────────────────────────────────────
//  Database Initialization: Tables & Seeding
// ─────────────────────────────────────────────
function init_database($pdo) {

    // Ensure local uploads directory exists
    $galleryDir = __DIR__ . '/uploads/gallery';
    if (!file_exists($galleryDir)) {
        mkdir($galleryDir, 0777, true);
    }

    // 1. Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        name         VARCHAR(100) NOT NULL,
        email        VARCHAR(100) UNIQUE NOT NULL,
        password     VARCHAR(255) NOT NULL,
        student_id   VARCHAR(50)  NOT NULL,
        department   VARCHAR(50)  NOT NULL,
        batch        VARCHAR(10)  NOT NULL,
        codeforces_handle VARCHAR(50)  DEFAULT NULL,
        vjudge_handle     VARCHAR(50)  DEFAULT NULL,
        programming_skills TEXT        DEFAULT NULL,
        motivation         TEXT        DEFAULT NULL,
        status       VARCHAR(20)  DEFAULT 'pending',
        role         VARCHAR(20)  DEFAULT 'member',
        designation  VARCHAR(50)  DEFAULT NULL,
        created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 2. Resources table
    $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        title       VARCHAR(150) NOT NULL,
        link        VARCHAR(255) NOT NULL,
        description TEXT         DEFAULT NULL,
        created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 3. Score logs table (contest standings)
    $pdo->exec("CREATE TABLE IF NOT EXISTS score_logs (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        user_id      INT          NOT NULL,
        contest_name VARCHAR(150) NOT NULL,
        points       INT          NOT NULL,
        added_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // 4. Gallery table (photo uploads)
    $pdo->exec("CREATE TABLE IF NOT EXISTS gallery (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        image_path TEXT         NOT NULL,
        caption    VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Seed default administrator account (only if it doesn't already exist)
    $adminEmail = 'admin@sgipc.org';
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $check->execute([$adminEmail]);

    if (!$check->fetch()) {
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = $pdo->prepare("
            INSERT INTO users
                (name, email, password, student_id, department, batch, status, role)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insert->execute([
            'SGIPC Administrator',
            $adminEmail,
            $adminPassword,
            'ADMIN-001',
            'CSE',
            'Admin',
            'approved',
            'admin'
        ]);
    }
}

// ─────────────────────────────────────────────
//  Global Helper Functions
// ─────────────────────────────────────────────

/** Sanitizes output to prevent XSS */
function sanitize($data) {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/** Returns true if a user session is active */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/** Returns true if the active session belongs to an admin */
function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/** Redirects to $url and stops execution */
function redirect($url) {
    header("Location: " . $url);
    exit();
}
?>
