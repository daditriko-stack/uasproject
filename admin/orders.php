<?php
require_once __DIR__ . '/../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: " . base_url('auth/login.php'));
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['action']) && $_POST['action'] === 'status'){
        $id = (int)$_POST['id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        
        if ($status === 'completed') {
            $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?")->execute([$id]);
        }
        
        // Send email notification to user
        require_once __DIR__ . '/../config/mailer.php';
        $stmt = $pdo->prepare("SELECT u.email, u.name, o.id FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
        $stmt->execute([$id]);
        $order_data = $stmt->fetch();
        
        if ($order_data && !empty($order_data['email'])) {
            $status_labels = [
                'pending' => 'Menunggu Pembayaran / Diproses',
                'completed' => 'Selesai',
                'cancelled' => 'Dibatalkan'
            ];
            $status_text = $status_labels[$status] ?? $status;
            
            $subject = "Pembaruan Status Pesanan #" . $order_data['id'];
            $body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;'>
                <h2 style='color: #10B981; text-align: center;'>Status Pesanan Diperbarui</h2>
                <p>Halo <strong>" . htmlspecialchars($order_data['name']) . "</strong>,</p>
                <p>Status pesanan Anda dengan nomor <strong>#" . $order_data['id'] . "</strong> telah diperbarui menjadi:</p>
                <div style='text-align: center; margin: 1.5rem 0; padding: 1rem; background: #f3f4f6; border-radius: 8px;'>
                    <strong style='font-size: 1.2rem; color: #064E3B;'>" . strtoupper($status_text) . "</strong>
                </div>
                <p>Terima kasih telah berbelanja di WarungKu.</p>
                <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 2rem 0;'>
                <p style='font-size: 0.8rem; color: #9ca3af; text-align: center;'>WarungKu Kelontong Modern</p>
            </div>
            ";
            sendEmail($order_data['email'], $subject, $body);
        }

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status pesanan diperbarui dan email notifikasi dikirim.'];
        header("Location: " . base_url('admin/orders.php'));
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
                    <a href="<?= base_url('admin/index.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="<?= base_url('admin/products.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="<?= base_url('admin/categories.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="<?= base_url('admin/orders.php') ?>" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
                    <a href="<?= base_url('admin/reports.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-chart-line" style="width: 25px;"></i> Laporan</a>
                    <a href="<?= base_url('admin/backup.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-database" style="width: 25px;"></i> Backup</a>
                    <a href="<?= base_url('admin/email_logs.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-envelope" style="width: 25px;"></i> Log Email</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Daftar Pesanan</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="<?= base_url('admin/export_excel.php?type=orders') ?>" class="btn btn-outline" style="border-color: var(--success); color: var(--success);"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a href="<?= base_url('admin/export_word.php?type=orders') ?>" class="btn btn-outline" style="border-color: #2563EB; color: #2563EB;"><i class="fa-solid fa-file-word"></i> Word</a>
                </div>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <thead style="background: var(--bg-light); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1rem; text-align: left;">ID/Waktu</th>
                        <th style="padding: 1rem; text-align: left;">Pelanggan</th>
                        <th style="padding: 1rem; text-align: left;">Pembayaran</th>
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
                        <td style="padding: 1rem;">
                            <div style="font-size: 0.9rem;">
                                <strong>Metode:</strong> <?= strtoupper($o['payment_method']) ?><br>
                                <strong>Status:</strong> <span style="color: <?= $o['payment_status'] == 'paid' ? 'var(--success)' : 'var(--danger)' ?>"><?= ucfirst($o['payment_status']) ?></span>
                                <?php if($o['payment_proof']): ?>
                                    <br><a href="<?= base_url('assets/img/payments/' . htmlspecialchars($o['payment_proof'])) ?>" target="_blank" style="color: var(--primary); font-size: 0.8rem; text-decoration: underline;">Lihat Bukti</a>
                                <?php endif; ?>
                            </div>
                        </td>
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
