<?php
require_once __DIR__ . '/config/db.php';
session_start();

if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $action = $_POST['action'] ?? '';
    
    if($action === 'add'){
        $product_id = (int)$_POST['product_id'];
        $qty = 1;
        
        // Cek produk
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if($product && $product['stock'] > 0){
            if(isset($_SESSION['cart'][$product_id])){
                if($_SESSION['cart'][$product_id]['quantity'] < $product['stock']){
                    $_SESSION['cart'][$product_id]['quantity'] += $qty;
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Jumlah produk diperbarui di keranjang.'];
                } else {
                    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Stok produk tidak mencukupi!'];
                }
            } else {
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'quantity' => $qty,
                    'stock' => $product['stock']
                ];
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil ditambahkan ke keranjang.'];
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Produk tidak ditemukan atau stok habis.'];
        }
        
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    if($action === 'remove'){
        $product_id = (int)$_POST['product_id'];
        if(isset($_SESSION['cart'][$product_id])){
            unset($_SESSION['cart'][$product_id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk dihapus dari keranjang.'];
        }
        header("Location: /uasproject/cart.php");
        exit;
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h2 class="section-title">Keranjang Belanja</h2>
    
    <?php if(empty($_SESSION['cart'])): ?>
        <div style="text-align: center; padding: 4rem; background: white; border-radius: 16px; border: 1px solid var(--border);">
            <i class="fa-solid fa-cart-arrow-down" style="font-size: 4rem; color: var(--border); margin-bottom: 1rem;"></i>
            <h3>Keranjang Anda kosong</h3>
            <p style="color: var(--text-muted); margin-bottom: 2rem;">Ayo mulai berbelanja kebutuhan Anda.</p>
            <a href="/uasproject/products.php" class="btn btn-primary">Lihat Katalog</a>
        </div>
    <?php else: ?>
        <div style="display: flex; gap: 2rem;">
            <div style="flex: 2;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <thead style="background: var(--bg-light); border-bottom: 1px solid var(--border);">
                        <tr>
                            <th style="padding: 1rem; text-align: left;">Produk</th>
                            <th style="padding: 1rem; text-align: center;">Harga</th>
                            <th style="padding: 1rem; text-align: center;">Jumlah</th>
                            <th style="padding: 1rem; text-align: right;">Total</th>
                            <th style="padding: 1rem; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_all = 0;
                        foreach($_SESSION['cart'] as $id => $item): 
                            $subtotal = $item['price'] * $item['quantity'];
                            $total_all += $subtotal;
                        ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><?= htmlspecialchars($item['name']) ?></td>
                            <td style="padding: 1rem; text-align: center;"><?= formatRupiah($item['price']) ?></td>
                            <td style="padding: 1rem; text-align: center;"><?= $item['quantity'] ?></td>
                            <td style="padding: 1rem; text-align: right; font-weight: 600;"><?= formatRupiah($subtotal) ?></td>
                            <td style="padding: 1rem; text-align: center;">
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger); padding: 0.25rem 0.5rem;"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="flex: 1;">
                <div style="background: white; padding: 2rem; border-radius: 16px; border: 1px solid var(--border); position: sticky; top: 100px;">
                    <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">Ringkasan Belanja</h3>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1.5rem; font-size: 1.25rem; font-weight: 700; color: var(--secondary);">
                        <span>Total:</span>
                        <span><?= formatRupiah($total_all) ?></span>
                    </div>
                    <a href="/uasproject/checkout.php" class="btn btn-primary" style="width: 100%; justify-content: center; font-size: 1.1rem; padding: 1rem;">Lanjut ke Pembayaran</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
