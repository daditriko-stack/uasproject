<?php
// File: auth/resend-verification.php
// Halaman mandiri (tidak perlu login) untuk meminta kirim ulang email verifikasi.
// User cukup masukkan email mereka.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

// Redirect jika sudah login dan sudah terverifikasi
if(isset($_SESSION['user_id']) && isset($_SESSION['is_verified']) && $_SESSION['is_verified']){
    header("Location: " . base_url('index.php'));
    exit;
}

$success = false;
$error   = '';

// Isi email dari URL (?email=...) untuk mempermudah pengisian form otomatis
$prefillEmail = htmlspecialchars($_GET['email'] ?? '');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = trim($_POST['email'] ?? '');

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Format email tidak valid.";
    } else {
        // Selalu tampilkan pesan sukses untuk mencegah enumerasi email
        $stmt = $pdo->prepare("SELECT id, name, email, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if($user && !$user['is_verified']){
            // Buat token baru dengan waktu kedaluwarsa baru (+1 jam)
            $newToken      = bin2hex(random_bytes(32));
            $newExpiry     = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare(
                "UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?"
            );
            $stmt->execute([$newToken, $newExpiry, $user['id']]);

            // Kirim email verifikasi baru
            sendVerificationEmail($user['email'], $user['name'], $newToken);
        }

        // Pesan sukses generik (tidak bocorkan apakah email terdaftar atau tidak)
        $success = true;
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="auth-container">
        <h2 style="text-align: center; color: var(--secondary); margin-bottom: 0.5rem;">
            <i class="fa-solid fa-envelope-circle-check" style="color: var(--primary);"></i>
            Kirim Ulang Verifikasi
        </h2>
        <p style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem; font-size: 0.95rem;">
            Masukkan alamat email yang Anda daftarkan dan kami akan mengirimkan link verifikasi baru.
        </p>

        <?php if($success): ?>
            <div style="background: var(--success); color: white; padding: 1.25rem; border-radius: 10px; text-align: center; margin-bottom: 1.5rem;">
                <strong>✅ Link verifikasi telah dikirim!</strong><br>
                Jika email Anda terdaftar dan belum diverifikasi, silakan cek kotak masuk Anda (termasuk folder Spam).
                <br><small>Link berlaku selama 1 jam.</small>
            </div>
        <?php elseif($error): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if(!$success): ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>Alamat Email Terdaftar</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= $prefillEmail ?>"
                       placeholder="Masukkan email Anda">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">
                <i class="fa-solid fa-paper-plane"></i>&nbsp; Kirim Ulang Link Verifikasi
            </button>
        </form>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 1.5rem;">
            Kembali ke <a href="<?= base_url('auth/login.php') ?>" style="color: var(--primary); font-weight: 600;">Halaman Login</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
