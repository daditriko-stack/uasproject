<?php
session_start();

$host = 'localhost';
$dbname = 'uas_warungku';
$user = 'root'; // Sesuaikan jika menggunakan user lain di MySQL
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Helper function untuk format rupiah
function formatRupiah($angka) {
    return "Rp " . number_format($angka, 0, ',', '.');
}

// Helper untuk mengambil inisial nama
function getInitials($name) {
    $words = explode(" ", $name);
    $initials = "";
    foreach ($words as $w) {
        $initials .= strtoupper($w[0]);
        if (strlen($initials) >= 2) break;
    }
    return $initials;
}
?>
