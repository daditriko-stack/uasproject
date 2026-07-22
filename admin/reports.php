<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . base_url('auth/login.php'));
    exit;
}

// Filter Dates
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch Report Data
$sql = "SELECT o.id, o.order_date, u.name as customer_name, o.total_price, o.status 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        WHERE DATE(o.order_date) >= ? AND DATE(o.order_date) <= ? 
        ORDER BY o.order_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$orders = $stmt->fetchAll();

// Calculate Totals
$total_revenue = 0;
$total_orders = count($orders);
$completed_orders = 0;

foreach ($orders as $order) {
    if ($order['status'] === 'completed') {
        $total_revenue += $order['total_price'];
        $completed_orders++;
    }
}

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Penjualan_' . $start_date . '_sd_' . $end_date . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID Pesanan', 'Tanggal', 'Pelanggan', 'Total Pembayaran', 'Status']);
    
    foreach ($orders as $row) {
        fputcsv($output, [
            $row['id'],
            $row['order_date'],
            $row['customer_name'],
            $row['total_price'],
            $row['status']
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, ['Total Pendapatan Selesai', $total_revenue]);
    fputcsv($output, ['Total Pesanan', $total_orders]);
    fclose($output);
    exit;
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Admin Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;" class="no-print">
            <div style="background: var(--bg-white); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Menu Admin</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="<?= base_url('admin/index.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="<?= base_url('admin/products.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="<?= base_url('admin/categories.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="<?= base_url('admin/orders.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
                    <a href="<?= base_url('admin/reports.php') ?>" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-chart-line" style="width: 25px;"></i> Laporan</a>
                    <a href="<?= base_url('admin/backup.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-database" style="width: 25px;"></i> Backup</a>
                    <a href="<?= base_url('admin/email_logs.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-envelope" style="width: 25px;"></i> Log Email</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="flex: 1;">
            <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Laporan Penjualan</h2>
                <div>
                    <a href="?export=csv&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-outline" style="border-color: var(--success); color: var(--success);"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
                    <button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Cetak PDF</button>
                </div>
            </div>

            <!-- Filter Form -->
            <div class="no-print" style="background: var(--bg-white); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 1.5rem;">
                <form method="GET" action="" style="display: flex; gap: 1rem; align-items: flex-end;">
                    <div style="flex: 1;">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control">
                    </div>
                    <div style="flex: 1;">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
                </form>
            </div>
            
            <!-- Summary Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: var(--bg-white); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Pendapatan (Selesai)</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?= formatRupiah($total_revenue) ?></div>
                </div>
                <div style="background: var(--bg-white); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Total Pesanan</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);"><?= $total_orders ?></div>
                </div>
                <div style="background: var(--bg-white); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem;">Pesanan Selesai</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);"><?= $completed_orders ?></div>
                </div>
            </div>

            <!-- Print Header (Hidden on screen) -->
            <div class="print-only" style="display: none; text-align: center; margin-bottom: 2rem;">
                <h2>Laporan Penjualan WarungKu</h2>
                <p>Periode: <?= $start_date ?> s/d <?= $end_date ?></p>
            </div>
            
            <style>
                @media print {
                    .no-print { display: none !important; }
                    .print-only { display: block !important; }
                    body { background: white; color: black; }
                    .container { margin: 0 !important; width: 100% !important; max-width: 100% !important; }
                }
            </style>

            <table style="width: 100%; border-collapse: collapse; background: var(--bg-white); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <thead style="background: var(--bg-light); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1rem; text-align: left;">ID Pesanan</th>
                        <th style="padding: 1rem; text-align: left;">Tanggal</th>
                        <th style="padding: 1rem; text-align: left;">Pelanggan</th>
                        <th style="padding: 1rem; text-align: right;">Total</th>
                        <th style="padding: 1rem; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem;">Tidak ada data laporan untuk periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach($orders as $o): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;">#<?= $o['id'] ?></td>
                            <td style="padding: 1rem;"><?= date('d M Y, H:i', strtotime($o['order_date'])) ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td style="padding: 1rem; text-align: right;"><?= formatRupiah($o['total_price']) ?></td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php
                                $status_colors = [
                                    'pending' => 'var(--accent)',
                                    'completed' => 'var(--success)',
                                    'cancelled' => 'var(--danger)'
                                ];
                                $color = $status_colors[$o['status']] ?? 'var(--text-muted)';
                                ?>
                                <span class="badge" style="background: <?= $color ?>;"><?= ucfirst($o['status']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
