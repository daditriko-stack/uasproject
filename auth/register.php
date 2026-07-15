<?php
require_once __DIR__ . '/../templates/header.php';

if(isset($_SESSION['user_id'])){
    header("Location: /uasproject/index.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password !== $confirm_password){
        $error = "Konfirmasi password tidak cocok!";
    } else {
        // Cek username ketersediaan
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if($stmt->rowCount() > 0){
            $error = "Username sudah digunakan!";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, 'customer')");
            if($stmt->execute([$name, $username, $hashed])){
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pendaftaran berhasil! Silakan masuk.'];
                header("Location: /uasproject/auth/login.php");
                exit;
            } else {
                $error = "Terjadi kesalahan saat mendaftar.";
            }
        }
    }
}
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
             data-client_id="ISI_DENGAN_CLIENT_ID_GOOGLE_ANDA.apps.googleusercontent.com"
             data-login_uri="http://localhost/uasproject/auth/sso_callback.php"
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
                <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Buat password">
            </div>
            <div class="form-group">
                <label>Konfirmasi Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Ulangi password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Daftar</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem;">
            Sudah punya akun? <a href="/uasproject/auth/login.php" style="color: var(--primary); font-weight: 600;">Masuk di sini</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
