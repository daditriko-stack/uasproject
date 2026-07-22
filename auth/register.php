<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

if(isset($_SESSION['user_id'])){
    header("Location: " . base_url('index.php'));
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name             = trim($_POST['name']);
    $username         = trim($_POST['username']);
    $email            = trim($_POST['email']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password){
        $error = "Konfirmasi password tidak cocok!";
    } elseif(strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        try {
            // Cek ketersediaan username/email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if($stmt->rowCount() > 0){
                $error = "Username atau Email sudah digunakan!";
            } else {
                $hashed            = password_hash($password, PASSWORD_DEFAULT);
                // Token 32 byte (64 karakter hex) — lebih aman
                $token             = bin2hex(random_bytes(32));
                // Kedaluwarsa 1 jam dari sekarang
                $token_expires_at  = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, username, email, password, role, is_verified, verification_token, token_expires_at)
                     VALUES (?, ?, ?, ?, 'customer', 0, ?, ?)"
                );
                if($stmt->execute([$name, $username, $email, $hashed, $token, $token_expires_at])){
                    // Kirim email verifikasi via helper (Resend API atau log)
                    sendVerificationEmail($email, $name, $token);

                    $_SESSION['flash'] = [
                        'type'    => 'success',
                        'message' => 'Pendaftaran berhasil! Silakan cek email Anda untuk verifikasi akun (berlaku 1 jam).'
                    ];
                    header("Location: " . base_url('auth/login.php'));
                    exit;
                } else {
                    $error = "Terjadi kesalahan saat mendaftar. Coba lagi.";
                }
            }
        } catch (PDOException $e) {
            $error = "Kesalahan sistem: Tidak dapat terhubung ke database.";
            error_log("Register Error: " . $e->getMessage());
        }
    }
}

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="auth-container">
        <h2 style="text-align: center; color: var(--secondary); margin-bottom: 1.5rem;">Daftar WarungKu</h2>
        
        <?php if(isset($error)): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <!-- Google SSO -->
        <script src="https://accounts.google.com/gsi/client" async defer></script>
        <div id="g_id_onload"
             data-client_id="648814225183-m0i3br2tchsafbut2favsm5kmhrr9i8m.apps.googleusercontent.com"
             data-login_uri="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . base_url('auth/sso_callback.php') ?>"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="signup_with" data-shape="rectangular" data-logo_alignment="left" style="display: flex; justify-content: center; margin-bottom: 1.5rem;"></div>

        <div style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem; position: relative;">
            <hr style="border: none; border-top: 1px solid var(--border); position: absolute; width: 100%; top: 50%; z-index: 1;">
            <span style="background: white; padding: 0 10px; position: relative; z-index: 2;">atau</span>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="name" class="form-control" required placeholder="Masukkan nama lengkap">
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Buat username">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required placeholder="Masukkan email aktif">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Buat password">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Ulangi password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Daftar Sekarang</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem;">
            Sudah punya akun? <a href="<?= base_url('auth/login.php') ?>" style="color: var(--primary); font-weight: 600;">Masuk di sini</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
