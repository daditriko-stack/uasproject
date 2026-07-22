<?php
require_once __DIR__ . '/config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: " . base_url('auth/login.php'));
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $pdo->prepare("SELECT name, username, email, is_verified, api_key, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_api') {
        $api_key = bin2hex(random_bytes(24));
        $stmt = $pdo->prepare("UPDATE users SET api_key = ? WHERE id = ?");
        $stmt->execute([$api_key, $user_id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'API Key berhasil dibuat.'];
        header("Location: " . base_url('profile.php'));
        exit;
    }
}

require_once __DIR__ . '/templates/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h2 class="section-title">Profil Saya</h2>
        
        <div style="background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; flex-direction: column; md:flex-row;">
            
            <div style="padding: 2rem; border-bottom: 1px solid var(--border);">
                <div style="display: flex; align-items: center; gap: 1.5rem;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: bold;">
                        <?= getInitials($user['name']) ?>
                    </div>
                    <div>
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.25rem;"><?= htmlspecialchars($user['name']) ?></h3>
                        <p style="color: var(--text-muted); margin-bottom: 0.5rem;"><i class="fa-solid fa-user"></i> @<?= htmlspecialchars($user['username']) ?></p>
                        
                        <?php if($user['is_verified']): ?>
                            <span class="badge" style="background: var(--success);"><i class="fa-solid fa-check-circle"></i> Email Diverifikasi</span>
                        <?php else: ?>
                            <span class="badge" style="background: var(--accent); color: var(--text-main);"><i class="fa-solid fa-circle-exclamation"></i> Belum Diverifikasi</span>
                            <a href="<?= base_url('auth/resend-verification.php') ?>" style="font-size: 0.85rem; margin-left: 0.5rem; color: var(--primary); font-weight: 600;">Kirim Ulang</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div style="padding: 2rem;">
                <h4 style="margin-bottom: 1rem; color: var(--secondary);">Informasi Kontak</h4>
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="color: var(--text-muted); font-weight: 500;">Email</div>
                    <div><?= htmlspecialchars($user['email'] ?? 'Belum diatur') ?></div>
                    
                    <div style="color: var(--text-muted); font-weight: 500;">Terdaftar Sejak</div>
                    <div><?= date('d M Y', strtotime($user['created_at'])) ?></div>
                </div>
                
                <hr style="border: none; border-top: 1px solid var(--border); margin: 2rem 0;">
                
                <h4 style="margin-bottom: 1rem; color: var(--secondary);">Pengaturan API</h4>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem;">API Key digunakan untuk mengakses REST API WarungKu (misal dari aplikasi mobile atau aplikasi pihak ketiga).</p>
                
                <?php if($user['api_key']): ?>
                    <div style="background: var(--bg-light); border: 1px dashed var(--border); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-family: monospace; font-size: 1.1rem; color: var(--text-main); word-break: break-all;">
                        <?= htmlspecialchars($user['api_key']) ?>
                    </div>
                <?php else: ?>
                    <div style="background: #FEF2F2; border: 1px dashed var(--danger); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; color: var(--danger);">
                        Anda belum memiliki API Key.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="generate_api">
                    <button type="submit" class="btn btn-outline" style="border-color: var(--primary); color: var(--primary);">
                        <i class="fa-solid fa-key"></i> <?= $user['api_key'] ? 'Regenerate API Key' : 'Buat API Key' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
