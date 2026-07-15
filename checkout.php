<?php
require_once __DIR__ . '/config/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Silakan masuk terlebih dahulu untuk checkout.'];
    header("Location: /uasproject/auth/login.php");
    exit;
}

if(empty($_SESSION['cart'])){
    header("Location: /uasproject/cart.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $address = trim($_POST['address']);
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
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address) VALUES (?, ?, 'pending', ?)");
            $stmt->execute([$_SESSION['user_id'], $total_price, $address]);
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
            unset($_SESSION['cart']);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pesanan berhasil dibuat!'];
            header("Location: /uasproject/index.php");
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
