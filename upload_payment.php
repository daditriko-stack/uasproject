<?php
require_once __DIR__ . '/config/db.php';
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: " . base_url('auth/login.php'));
    exit;
}

$order_id = $_GET['order_id'] ?? null;
if(!$order_id){
    header("Location: " . base_url('index.php'));
    exit;
}

// Fetch order details
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if(!$order){
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Pesanan tidak ditemukan.'];
    header("Location: " . base_url('index.php'));
    exit;
}

if ($order['payment_method'] === 'cash' || $order['payment_status'] === 'paid') {
    header("Location: " . base_url('profile.php'));
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])){
    $file = $_FILES['payment_proof'];
    
    // Validate file
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if(!in_array($file['type'], $allowed_types)){
        $error = "Format file harus JPG, JPEG, atau PNG.";
    } elseif($file['size'] > $max_size){
        $error = "Ukuran file maksimal 2MB.";
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = "proof_" . $order_id . "_" . time() . "." . $ext;
        $upload_dir = __DIR__ . '/assets/img/payments/';
        
        if(!is_dir($upload_dir)){
            mkdir($upload_dir, 0777, true);
        }
        
        if(move_uploaded_file($file['tmp_name'], $upload_dir . $filename)){
            $stmt = $pdo->prepare("UPDATE orders SET payment_proof = ? WHERE id = ?");
            if($stmt->execute([$filename, $order_id])){
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bukti pembayaran berhasil diunggah. Menunggu konfirmasi admin.'];
                header("Location: " . base_url('profile.php'));
                exit;
            } else {
                $error = "Gagal menyimpan ke database.";
            }
        } else {
            $error = "Gagal mengunggah file.";
        }
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 2rem; border-radius: 16px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
        <h2 style="color: var(--secondary); margin-bottom: 1.5rem; text-align: center;">Upload Bukti Pembayaran</h2>
        
        <?php if(isset($error)): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <div style="background: #f3f4f6; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
            <h4 style="margin-bottom: 1rem; font-weight: 600;">Detail Pesanan #<?= htmlspecialchars($order['id']) ?></h4>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span>Total Tagihan:</span>
                <span style="font-weight: bold; color: var(--primary);"><?= formatRupiah($order['total_price']) ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span>Metode Pembayaran:</span>
                <span style="font-weight: 500; text-transform: uppercase;"><?= htmlspecialchars($order['payment_method']) ?></span>
            </div>
        </div>
        
        <?php if($order['payment_method'] === 'qris'): ?>
        <div style="text-align: center; margin-bottom: 2rem;">
            <p style="margin-bottom: 1rem; font-weight: 500;">Silakan scan QRIS di bawah ini dengan aplikasi e-wallet atau m-banking Anda:</p>
            <div style="background: white; padding: 1rem; display: inline-block; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <img src="<?= base_url('assets/images/qris.jpg') ?>" alt="Scan QRIS WarungKu" style="width: 240px; height: 240px; object-fit: contain; border-radius: 8px;">
            </div>
            <p style="margin-top: 0.75rem; font-size: 0.85rem; color: var(--text-muted);">Pastikan nominal transfer sesuai dengan total tagihan pesanan Anda.</p>
        </div>
        <?php elseif($order['payment_method'] === 'transfer'): ?>
        <div style="background: #e0f2fe; color: #0284c7; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <p style="margin-bottom: 0.5rem; font-weight: 600;">Transfer Bank ke Rekening Berikut:</p>
            <p style="font-size: 1.1rem; font-weight: bold; margin-bottom: 0.2rem;">Bank BCA: 1234567890</p>
            <p style="margin-bottom: 0;">Atas Nama: PT WarungKu Sejahtera</p>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label>Pilih Bukti Pembayaran (JPG/PNG, Max 2MB)</label>
                <input type="file" name="payment_proof" class="form-control" required accept="image/jpeg,image/png,image/jpg" style="padding: 0.5rem;">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Upload Bukti</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
