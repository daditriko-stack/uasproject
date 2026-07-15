<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: /uasproject/auth/login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['action']) && $_POST['action'] === 'status'){
        $id = (int)$_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status pesanan diperbarui.'];
        header("Location: /uasproject/admin/orders.php");
        exit;
    }
}

$orders = $pdo->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC")->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Admin Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Menu Admin</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="/uasproject/admin/index.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="/uasproject/admin/products.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="/uasproject/admin/categories.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="/uasproject/admin/orders.php" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Daftar Pesanan</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="/uasproject/admin/export_excel.php?type=orders" class="btn btn-outline" style="border-color: var(--success); color: var(--success);"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a href="/uasproject/admin/export_word.php?type=orders" class="btn btn-outline" style="border-color: #2563EB; color: #2563EB;"><i class="fa-solid fa-file-word"></i> Word</a>
                </div>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <thead style="background: var(--bg-light); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1rem; text-align: left;">ID/Waktu</th>
                        <th style="padding: 1rem; text-align: left;">Pelanggan</th>
                        <th style="padding: 1rem; text-align: right;">Total</th>
                        <th style="padding: 1rem; text-align: center;">Status</th>
                        <th style="padding: 1rem; text-align: center;">Ubah Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $o): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem;">
                            <strong>#<?= $o['id'] ?></strong><br>
                            <span style="font-size: 0.8rem; color: var(--text-muted);"><?= date('d/m/Y H:i', strtotime($o['order_date'])) ?></span>
                        </td>
                        <td style="padding: 1rem;"><?= htmlspecialchars($o['customer_name']) ?></td>
                        <td style="padding: 1rem; text-align: right; font-weight: 600;"><?= formatRupiah($o['total_price']) ?></td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php 
                            $bg = $o['status'] == 'pending' ? 'var(--accent)' : ($o['status'] == 'completed' ? 'var(--success)' : 'var(--danger)');
                            ?>
                            <span class="badge" style="background: <?= $bg ?>; padding: 0.5rem 1rem;"><?= ucfirst($o['status']) ?></span>
                        </td>
                        <td style="padding: 1rem; text-align: center;">
                            <form method="POST" action="" style="display: flex; gap: 0.5rem; justify-content: center;">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="<?= $o['id'] ?>">
                                <select name="status" class="form-control" style="padding: 0.25rem; width: auto;" onchange="this.form.submit()">
                                    <option value="pending" <?= $o['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="completed" <?= $o['status'] == 'completed' ? 'selected' : '' ?>>Selesai</option>
                                    <option value="cancelled" <?= $o['status'] == 'cancelled' ? 'selected' : '' ?>>Batal</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
