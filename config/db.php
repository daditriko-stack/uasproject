<?php
// Muat .env sebelum semua hal lainnya
require_once __DIR__ . '/env.php';

// Mulai session hanya jika belum berjalan (mencegah error 'headers already sent')
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host   = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost');
$dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'uas_warungku');
$user   = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
$pass   = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Auto-Migration: tambah kolom ke tabel users jika belum ada
    try {
        $columns_needed = [
            'email'              => "VARCHAR(150) DEFAULT NULL UNIQUE",
            'is_verified'        => "TINYINT(1) DEFAULT 0",
            'verification_token' => "VARCHAR(255) DEFAULT NULL",
            'token_expires_at'   => "DATETIME DEFAULT NULL",
            'reset_token'        => "VARCHAR(255) DEFAULT NULL",
            'reset_expires'      => "DATETIME DEFAULT NULL",
            'api_key'            => "VARCHAR(255) DEFAULT NULL"
        ];
        
        $stmt = $pdo->query("SHOW COLUMNS FROM users");
        $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($columns_needed as $col => $definition) {
            if (!in_array($col, $existing_columns)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN $col $definition");
            }
        }
        
        // Auto-Migration: tambah kolom ke tabel orders jika belum ada
        $order_columns_needed = [
            'payment_method' => "ENUM('cash', 'transfer', 'qris') DEFAULT 'cash'",
            'payment_proof'  => "VARCHAR(255) DEFAULT NULL",
            'payment_status' => "ENUM('unpaid', 'paid', 'failed') DEFAULT 'unpaid'"
        ];
        
        $stmt_orders = $pdo->query("SHOW COLUMNS FROM orders");
        $existing_order_columns = $stmt_orders->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($order_columns_needed as $col => $definition) {
            if (!in_array($col, $existing_order_columns)) {
                $pdo->exec("ALTER TABLE orders ADD COLUMN $col $definition");
            }
        }
        
        // Pastikan akun dummy admin & customer default sudah terverifikasi
        $pdo->exec("UPDATE users SET email = 'admin@warungku.com', is_verified = 1 WHERE username = 'admin' AND email IS NULL");
        $pdo->exec("UPDATE users SET email = 'customer1@warungku.com', is_verified = 1 WHERE username = 'customer1' AND email IS NULL");

    } catch (PDOException $ex) {
        // Gagal migrasi — catat ke error_log agar bisa di-debug
        error_log("DB Auto-Migration Error: " . $ex->getMessage());
    }

} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Helper: format angka ke Rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Helper: ambil 2 inisial nama
function getInitials($name) {
    $words    = explode(" ", $name);
    $initials = "";
    foreach ($words as $w) {
        if (!empty($w)) $initials .= strtoupper($w[0]);
        if (strlen($initials) >= 2) break;
    }
    return $initials;
}

// Helper: mendeteksi base URL secara dinamis (berjalan di localhost/uasproject maupun di domain utama hosting)
if (!function_exists('base_url')) {
    function base_url($path = '') {
        $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
        $app_dir  = str_replace('\\', '/', dirname(__DIR__));
        
        $base = '';
        if ($doc_root && strpos($app_dir, $doc_root) === 0) {
            $base = substr($app_dir, strlen($doc_root));
        }
        
        $base = rtrim(str_replace('\\', '/', $base), '/');
        $path = ltrim($path, '/');
        
        return $base . ($path !== '' ? '/' . $path : '');
    }
}
?>
