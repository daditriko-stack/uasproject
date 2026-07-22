<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: " . base_url('auth/login.php'));
    exit;
}

$type = $_GET['type'] ?? 'products';
$filename = "export_" . $type . "_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for UTF-8 Excel support

if($type === 'products'){
    fputcsv($output, ['ID', 'Nama Produk', 'Kategori', 'Harga', 'Stok', 'Deskripsi']);
    $products = $pdo->query("SELECT p.id, p.name, c.name as category, p.price, p.stock, p.description FROM products p JOIN categories c ON p.category_id = c.id")->fetchAll();
    foreach($products as $p){
        fputcsv($output, $p);
    }
} elseif($type === 'orders') {
    fputcsv($output, ['ID Pesanan', 'Tanggal', 'Pelanggan', 'Total Harga', 'Status']);
    $orders = $pdo->query("SELECT o.id, o.order_date, u.name, o.total_price, o.status FROM orders o JOIN users u ON o.user_id = u.id")->fetchAll();
    foreach($orders as $o){
        fputcsv($output, $o);
    }
}

fclose($output);
exit;
