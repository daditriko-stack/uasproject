<?php
require_once __DIR__ . '/config/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Silakan masuk terlebih dahulu untuk checkout.'];
    header("Location: " . base_url('auth/login.php'));
    exit;
}

if(empty($_SESSION['cart'])){
    header("Location: " . base_url('cart.php'));
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $address = trim($_POST['address']);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    if(empty($address)){
        $error = "Alamat pengiriman harus diisi.";
    } else {
        try {
            $pdo->beginTransaction();
            
            $total_price = 0;
            foreach($_SESSION['cart'] as $item){
                $total_price += $item['price'] * $item['quantity'];
            }
            
            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address, payment_method, payment_status) VALUES (?, ?, 'pending', ?, ?, 'unpaid')");
            $stmt->execute([$_SESSION['user_id'], $total_price, $address, $payment_method]);
            $order_id = $pdo->lastInsertId();
            
            // Insert order items & Update Stock
            foreach($_SESSION['cart'] as $id => $item){
                $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $id, $item['quantity'], $item['price']]);
                
                // Kurangi stok
                $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $id]);
            }
            
            $pdo->commit();
            
            // Generate Invoice Content & Check Critical Stock
            require_once __DIR__ . '/config/mailer.php';
            
            // Get user email
            $stmt = $pdo->prepare("SELECT email, name FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $u = $stmt->fetch();
            
            if ($u && !empty($u['email'])) {
                $invoice_body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;'>
                    <h2 style='color: #10B981; text-align: center;'>Invoice Pesanan #$order_id</h2>
                    <p>Halo <strong>" . htmlspecialchars($u['name']) . "</strong>,</p>
                    <p>Terima kasih telah berbelanja di WarungKu. Berikut adalah ringkasan pesanan Anda:</p>
                    <table style='width: 100%; border-collapse: collapse; margin-top: 1rem;'>
                        <tr style='background: #f3f4f6;'>
                            <th style='padding: 10px; text-align: left; border: 1px solid #e5e7eb;'>Item</th>
                            <th style='padding: 10px; text-align: right; border: 1px solid #e5e7eb;'>Total</th>
                        </tr>";
                        
                foreach($_SESSION['cart'] as $item){
                    $invoice_body .= "<tr>
                        <td style='padding: 10px; border: 1px solid #e5e7eb;'>" . htmlspecialchars($item['name']) . " (x" . $item['quantity'] . ")</td>
                        <td style='padding: 10px; text-align: right; border: 1px solid #e5e7eb;'>" . formatRupiah($item['price'] * $item['quantity']) . "</td>
                    </tr>";
                }
                
                $invoice_body .= "
                        <tr>
                            <th style='padding: 10px; text-align: right; border: 1px solid #e5e7eb;'>Total Keseluruhan</th>
                            <th style='padding: 10px; text-align: right; border: 1px solid #e5e7eb; color: #10B981;'>" . formatRupiah($total_price) . "</th>
                        </tr>
                    </table>
                    <p style='margin-top: 1.5rem;'><strong>Alamat Pengiriman:</strong><br>" . nl2br(htmlspecialchars($address)) . "</p>
                    <p style='font-size: 0.9rem; color: #6b7280; margin-top: 2rem;'>Pesanan Anda akan segera diproses oleh admin kami.</p>
                </div>";
                
                sendEmail($u['email'], "Invoice Pesanan WarungKu #$order_id", $invoice_body);
            }
            
            // Check Critical Stock and alert admin
            $critical = [];
            foreach($_SESSION['cart'] as $id => $item) {
                $stmt = $pdo->prepare("SELECT name, stock FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $p = $stmt->fetch();
                if ($p && $p['stock'] < 5) {
                    $critical[] = "{$p['name']} (Sisa: {$p['stock']})";
                }
            }
            if (!empty($critical)) {
                $admin_email = 'admin@warungku.com';
                $admin_body = "<h3>Peringatan Stok Kritis!</h3><ul>";
                foreach($critical as $c) {
                    $admin_body .= "<li>$c</li>";
                }
                $admin_body .= "</ul>";
                sendEmail($admin_email, "Peringatan Stok Kritis - WarungKu", $admin_body);
            }
            
            unset($_SESSION['cart']);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pesanan berhasil dibuat! Invoice telah dikirim ke email Anda.'];
            
            if ($payment_method !== 'cash') {
                header("Location: " . base_url('upload_payment.php?order_id=' . $order_id));
            } else {
                header("Location: " . base_url('index.php'));
            }
            exit;
            
        } catch(Exception $e) {
            $pdo->rollBack();
            $error = "Terjadi kesalahan saat memproses pesanan: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <h2 style="color: var(--secondary); margin-bottom: 1.5rem; text-align: center;">Checkout</h2>
        
        <?php if(isset($error)): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Penerima</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($_SESSION['name']) ?>" disabled>
            </div>
            <div class="form-group">
                <label>Alamat Pengiriman Lengkap</label>
                <textarea name="address" class="form-control" rows="4" required placeholder="Masukkan alamat lengkap (Jalan, RT/RW, Kelurahan, Kecamatan, Kota, Kode Pos)"></textarea>
            </div>
            
            <div class="form-group">
                <label>Metode Pembayaran</label>
                <select name="payment_method" class="form-control" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; margin-top: 0.5rem;">
                    <option value="cash">Cash (COD) - Bayar di Tempat</option>
                    <option value="transfer">Transfer Bank</option>
                    <option value="qris">QRIS</option>
                </select>
            </div>
            
            <div style="margin: 2rem 0; border-top: 1px solid var(--border); padding-top: 1rem;">
                <h4 style="margin-bottom: 1rem;">Ringkasan Pesanan</h4>
                <?php 
                $total_all = 0;
                foreach($_SESSION['cart'] as $item): 
                    $total_all += $item['price'] * $item['quantity'];
                ?>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</span>
                    <span><?= formatRupiah($item['price'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>
                <div style="display: flex; justify-content: space-between; margin-top: 1rem; font-weight: 700; font-size: 1.2rem; border-top: 1px dashed var(--border); padding-top: 1rem;">
                    <span>Total Bayar:</span>
                    <span style="color: var(--primary);"><?= formatRupiah($total_all) ?></span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 1rem;">Buat Pesanan Sekarang</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
