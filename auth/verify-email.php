<?php
// File: auth/verify-email.php
// Mengecek token dari URL, memvalidasi kedaluwarsa, dan mengaktifkan akun.
require_once __DIR__ . '/../config/db.php';

$token = trim($_GET['token'] ?? '');

if(empty($token)){
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token verifikasi tidak valid atau kosong.'];
    header("Location: " . base_url('auth/login.php'));
    exit;
}

// Cari user berdasarkan token
$stmt = $pdo->prepare("SELECT id, name, email, is_verified, token_expires_at FROM users WHERE verification_token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch();

if(!$user){
    // Token tidak ditemukan di database
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Token verifikasi tidak valid. Pastikan Anda menggunakan link terbaru dari email Anda.'];
    header("Location: " . base_url('auth/login.php'));
    exit;
}

if($user['is_verified']){
    // Sudah terverifikasi sebelumnya
    $_SESSION['flash'] = ['type' => 'info', 'message' => 'Akun Anda sudah terverifikasi sebelumnya. Silakan masuk.'];
    header("Location: " . base_url('auth/login.php'));
    exit;
}

// Cek apakah token masih berlaku
$tokenExpiry = $user['token_expires_at'];
if($tokenExpiry && strtotime($tokenExpiry) < time()){
    // Token kedaluwarsa
    require_once __DIR__ . '/../templates/header.php';
    ?>
    <div class="container">
        <div class="auth-container" style="text-align: center;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⏳</div>
            <h2 style="color: var(--danger); margin-bottom: 1rem;">Link Verifikasi Kedaluwarsa</h2>
            <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                Link verifikasi Anda sudah tidak berlaku (kedaluwarsa setelah 1 jam).<br>
                Silakan minta link verifikasi baru di bawah ini.
            </p>
            <a href="<?= base_url('auth/resend-verification.php?email=' . urlencode($user['email'])) ?>"
               class="btn btn-primary" style="display: inline-flex; justify-content: center;">
                <i class="fa-solid fa-rotate-right"></i>&nbsp; Kirim Ulang Email Verifikasi
            </a>
            <p style="margin-top: 1.5rem;">
                <a href="<?= base_url('auth/login.php') ?>" style="color: var(--primary);">Kembali ke Login</a>
            </p>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../templates/footer.php';
    exit;
}

// Token valid & belum kedaluwarsa — aktifkan akun
$stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = ?");
if($stmt->execute([$user['id']])){
    $_SESSION['flash'] = ['type' => 'success', 'message' => '✅ Email Anda berhasil diverifikasi! Silakan masuk dengan akun Anda.'];
} else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal memverifikasi email. Silakan coba lagi nanti.'];
}

header("Location: " . base_url('auth/login.php'));
exit;
