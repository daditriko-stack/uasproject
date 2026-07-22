<?php
// db.php HARUS di-load pertama agar $pdo tersedia sebelum query dijalankan
require_once __DIR__ . '/../config/db.php';

// Hapus proteksi session login agar pengguna yang sedang login tetap bisa membuka link reset kata sandi dari email

$token = $_GET['token'] ?? '';
if (empty($token)) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token reset tidak valid.'];
    header("Location: " . base_url('auth/login.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    // Token tidak ada di database
    require_once __DIR__ . '/../templates/header.php';
    ?>
    <div class="container">
        <div class="auth-container" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">❌</div>
            <h2 style="color: var(--danger);">Link Tidak Valid</h2>
            <p style="color: var(--text-muted); margin: 1rem 0;">
                Link reset kata sandi tidak ditemukan.<br>Mungkin sudah pernah digunakan sebelumnya.
            </p>
            <a href="<?= base_url('auth/forgot-password.php') ?>" class="btn btn-primary" style="display:inline-flex; justify-content:center;">
                <i class="fa-solid fa-rotate-right"></i>&nbsp; Minta Link Reset Baru
            </a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// Cek apakah token masih berlaku
if (strtotime($user['reset_expires']) < time()) {
    require_once __DIR__ . '/../templates/header.php';
    ?>
    <div class="container">
        <div class="auth-container" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
            <h2 style="color: var(--danger);">Link Sudah Kedaluwarsa</h2>
            <p style="color: var(--text-muted); margin: 1rem 0;">
                Link reset kata sandi hanya berlaku selama <strong>1 jam</strong>.<br>
                Silakan minta link baru di bawah ini.
            </p>
            <a href="<?= base_url('auth/forgot-password.php') ?>" class="btn btn-primary" style="display:inline-flex; justify-content:center;">
                <i class="fa-solid fa-rotate-right"></i>&nbsp; Minta Link Reset Baru
            </a>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        if ($stmt->execute([$hashed, $user['id']])) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => '✅ Kata sandi berhasil direset! Silakan masuk dengan kata sandi baru Anda.'];
            header("Location: " . base_url('auth/login.php'));
            exit;
        } else {
            $error = "Gagal mereset kata sandi. Silakan coba lagi.";
        }
    }
}

// Baru load header setelah semua logika PHP selesai
require_once __DIR__ . '/../templates/header.php';
?>

<div class="container">
    <div class="auth-container">
        <h2 style="text-align: center; color: var(--secondary); margin-bottom: 1.5rem;">Reset Kata Sandi</h2>
        
        <?php if(isset($error)): ?>
            <div style="background: var(--danger); color: white; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Kata Sandi Baru</label>
                <input type="password" name="password" class="form-control" required placeholder="Buat password baru">
            </div>
            <div class="form-group">
                <label>Konfirmasi Kata Sandi</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Ulangi password baru">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; margin-top: 1rem;">Simpan Kata Sandi</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
