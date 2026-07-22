<?php
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: " . base_url('auth/login.php'));
    exit;
}

// Statistik
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0;
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Recent Orders
$stmt = $pdo->query("SELECT o.id, u.name, o.total_price, o.status, o.order_date FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.order_date DESC LIMIT 5");
$recent_orders = $stmt->fetchAll();

// Chart Data (Last 7 Days Sales)
$chart_dates = [];
$chart_revenues = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_dates[] = date('d M', strtotime($date));
    
    $stmt = $pdo->prepare("SELECT SUM(total_price) FROM orders WHERE DATE(order_date) = ? AND status = 'completed'");
    $stmt->execute([$date]);
    $chart_revenues[] = (float)($stmt->fetchColumn() ?: 0);
}

// Stok kritis
$critical_stock = $pdo->query("SELECT * FROM products WHERE stock < 5")->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Admin Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Menu Admin</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="<?= base_url('admin/index.php') ?>" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="<?= base_url('admin/products.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="<?= base_url('admin/categories.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="<?= base_url('admin/orders.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
                    <a href="<?= base_url('admin/reports.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-chart-line" style="width: 25px;"></i> Laporan</a>
                    <a href="<?= base_url('admin/backup.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-database" style="width: 25px;"></i> Backup</a>
                    <a href="<?= base_url('admin/email_logs.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-envelope" style="width: 25px;"></i> Log Email</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="flex: 1;">
            <h2 class="section-title">Dashboard Admin</h2>
            
            <?php if(!empty($critical_stock)): ?>
                <div style="background: #FEF2F2; border: 1px solid var(--danger); padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem;">
                    <h3 style="color: var(--danger); margin-bottom: 0.5rem;"><i class="fa-solid fa-triangle-exclamation"></i> Peringatan Stok Kritis!</h3>
                    <p>Beberapa produk memiliki stok hampir habis (< 5):</p>
                    <ul style="margin-top: 0.5rem; margin-left: 1.5rem; color: var(--danger);">
                        <?php foreach($critical_stock as $cs): ?>
                            <li><strong><?= htmlspecialchars($cs['name']) ?></strong> (Sisa: <?= $cs['stock'] ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
                <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-left: 4px solid var(--primary);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Total Produk</div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main); margin-top: 0.5rem;"><?= $total_products ?></div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-left: 4px solid var(--accent);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Pesanan Pending</div>
                    <div style="font-size: 2rem; font-weight: 700; color: var(--text-main); margin-top: 0.5rem;"><?= $pending_orders ?></div>
                </div>
                <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); border-left: 4px solid var(--success);">
                    <div style="color: var(--text-muted); font-size: 0.9rem; font-weight: 600; text-transform: uppercase;">Total Pendapatan</div>
                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-top: 0.5rem;"><?= formatRupiah($revenue) ?></div>
                </div>
            </div>

            <!-- Chart Section -->
            <div style="background: var(--bg-white); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--secondary);">Tren Penjualan (7 Hari Terakhir)</h3>
                <canvas id="salesChart" height="100"></canvas>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('salesChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?= json_encode(array_reverse($chart_dates)) ?>,
                            datasets: [{
                                label: 'Pendapatan (Rp)',
                                data: <?= json_encode(array_reverse($chart_revenues)) ?>,
                                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                borderColor: 'rgba(16, 185, 129, 1)',
                                borderWidth: 2,
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
            </script>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
