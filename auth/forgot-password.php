<?php
// forgot-password.php
// db.php harus di-load PERTAMA agar $pdo dan session tersedia
// sebelum ada header() atau output HTML apapun
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . base_url('index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $update = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($update->execute([$token, $expires, $user['id']])) {
                $reset_link = getAppUrl() . base_url('auth/reset-password.php?token=' . $token);
                $subject    = "Permintaan Reset Kata Sandi WarungKu";
                $body       = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;'>
                    <h2 style='color: #10B981; text-align: center;'>Reset Kata Sandi Anda</h2>
                    <p>Halo <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>
                    <p>Kami menerima permintaan untuk mereset kata sandi akun WarungKu Anda. Tautan di bawah berlaku selama <strong>1 jam</strong>:</p>
                    <div style='text-align: center; margin: 2rem 0;'>
                        <a href='$reset_link' style='background: #10B981; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Reset Kata Sandi</a>
                    </div>
                    <p style='font-size: 0.9rem; color: #6b7280;'>Atau salin tautan berikut ke browser Anda:<br>
                    <span style='color: #10B981; word-break: break-all;'>$reset_link</span></p>
                    <p style='font-size: 0.9rem; color: #6b7280;'>Jika Anda tidak meminta ini, abaikan saja email ini.</p>
                    <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 2rem 0;'>
                    <p style='font-size: 0.8rem; color: #9ca3af; text-align: center;'>WarungKu Kelontong Modern</p>
                </div>";
                
                sendEmail($email, $subject, $body);
            }
        }
        
        // Selalu tampilkan pesan sukses (mencegah enumerasi email)
        $success = "Jika email Anda terdaftar, instruksi reset kata sandi telah dikirim ke inbox Anda.";
    } else {
        $error = "Format email tidak valid.";
    }
}

// Load header SETELAH semua logika selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="auth-container">
        <h2 style="text-align: center; color: var(--secondary); margin-bottom: 1.5rem;">Lupa Kata Sandi</h2>
        
        <?php if(isset($error)): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
            <div style="background: var(--success); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <strong>✅ Link terkirim!</strong><br>
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if(!isset($success)): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Email Anda</label>
                <input type="email" name="email" class="form-control" required placeholder="Masukkan email aktif">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
                <i class="fa-solid fa-paper-plane"></i>&nbsp; Kirim Link Reset
            </button>
        </form>
        <?php endif; ?>
        
        <p style="text-align: center; margin-top: 1.5rem;">
            Kembali ke <a href="<?= base_url('auth/login.php') ?>" style="color: var(--primary); font-weight: 600;">Halaman Login</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
