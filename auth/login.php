<?php
require_once __DIR__ . '/../templates/header.php';

if(isset($_SESSION['user_id'])){
    header("Location: /uasproject/index.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if($user && password_verify($password, $user['password'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Selamat datang kembali, ' . htmlspecialchars($user['name'])];
        
        if($user['role'] === 'admin'){
            header("Location: /uasproject/admin/index.php");
        } else {
            header("Location: /uasproject/index.php");
        }
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<div class="container">
    <div class="auth-container">
        <h2 style="text-align: center; color: var(--secondary); margin-bottom: 1.5rem;">Masuk ke WarungKu</h2>
        
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
        <div class="g_id_signin" data-type="standard" data-size="large" data-theme="outline" data-text="sign_in_with" data-shape="rectangular" data-logo_alignment="left" style="display: flex; justify-content: center; margin-bottom: 1.5rem;"></div>

        <div style="text-align: center; color: var(--text-muted); margin-bottom: 1.5rem; position: relative;">
            <hr style="border: none; border-top: 1px solid var(--border); position: absolute; width: 100%; top: 50%; z-index: 1;">
            <span style="background: white; padding: 0 10px; position: relative; z-index: 2;">atau</span>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required placeholder="Masukkan username">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Masukkan password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Masuk</button>
        </form>
        <p style="text-align: center; margin-top: 1.5rem;">
            Belum punya akun? <a href="/uasproject/auth/register.php" style="color: var(--primary); font-weight: 600;">Daftar Sekarang</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
