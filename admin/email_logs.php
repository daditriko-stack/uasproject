<?php
require_once __DIR__ . '/../templates/header.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: " . base_url('auth/login.php'));
    exit;
}

$logFile = __DIR__ . '/../logs/mail.log';
$logs = [];

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    // Split by the separator
    $rawLogs = explode("========================================", $content);
    foreach ($rawLogs as $raw) {
        if (trim($raw) !== "") {
            $logs[] = trim($raw);
        }
    }
    $logs = array_reverse($logs); // Show newest first
}

if (isset($_POST['clear_logs'])) {
    file_put_contents($logFile, '');
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Log email berhasil dihapus!'];
    header("Location: email_logs.php");
    exit;
}
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h2 class="section-title" style="margin-bottom: 0;">Log Email Keluar</h2>
            <p class="text-muted">Simulasi pengiriman email ke pengguna</p>
        </div>
        <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus semua log email?');">
            <button type="submit" name="clear_logs" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);">
                <i class="fa-solid fa-trash"></i> Bersihkan Log
            </button>
        </form>
    </div>

    <?php if(empty($logs)): ?>
        <div style="text-align: center; padding: 3rem; background: var(--bg-white); border-radius: 16px; border: 1px solid var(--border);">
            <i class="fa-solid fa-envelope-open-text" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
            <p style="color: var(--text-muted);">Belum ada email yang terkirim.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; gap: 1rem;">
            <?php foreach($logs as $log): ?>
                <div style="background: var(--bg-white); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--border); font-family: monospace; white-space: pre-wrap; font-size: 0.9rem; color: var(--text-main); overflow-x: auto;">
<?= htmlspecialchars($log) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
