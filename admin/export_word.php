<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: /uasproject/auth/login.php");
    exit;
}

$type = $_GET['type'] ?? 'products';
$filename = "export_" . $type . "_" . date('Ymd_His') . ".doc";

header("Content-Type: application/vnd.ms-word");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("content-disposition: attachment;filename={$filename}");

echo "<html><head><meta charset='utf-8'></head><body>";
echo "<h2 style='text-align:center;'>Laporan " . ucfirst($type) . " - WarungKu</h2>";

if($type === 'products'){
    $products = $pdo->query("SELECT p.id, p.name, c.name as category, p.price, p.stock FROM products p JOIN categories c ON p.category_id = c.id")->fetchAll();
    echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%; border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Nama Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th></tr>";
    foreach($products as $p){
        echo "<tr>";
        echo "<td>{$p['id']}</td>";
        echo "<td>{$p['name']}</td>";
        echo "<td>{$p['category']}</td>";
        echo "<td>Rp " . number_format($p['price'], 0, ',', '.') . "</td>";
        echo "<td>{$p['stock']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} elseif($type === 'orders') {
    $orders = $pdo->query("SELECT o.id, o.order_date, u.name, o.total_price, o.status FROM orders o JOIN users u ON o.user_id = u.id")->fetchAll();
    echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%; border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Total Harga</th><th>Status</th></tr>";
    foreach($orders as $o){
        echo "<tr>";
        echo "<td>{$o['id']}</td>";
        echo "<td>{$o['order_date']}</td>";
        echo "<td>{$o['name']}</td>";
        echo "<td>Rp " . number_format($o['total_price'], 0, ',', '.') . "</td>";
        echo "<td>" . ucfirst($o['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "</body></html>";
exit;
