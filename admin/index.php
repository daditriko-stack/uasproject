<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: /uasproject/auth/login.php");
    exit;
}

require_once __DIR__ . '/../templates/header.php';

// Statistik
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_price) FROM orders WHERE status = 'completed'")->fetchColumn() ?: 0;
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Stok kritis
$critical_stock = $pdo->query("SELECT * FROM products WHERE stock < 5")->fetchAll();

?>
<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Admin Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Menu Admin</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="/uasproject/admin/index.php" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="/uasproject/admin/products.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="/uasproject/admin/categories.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="/uasproject/admin/orders.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
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
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
