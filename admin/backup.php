<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . base_url('auth/login.php'));
    exit;
}

$backup_dir = __DIR__ . '/../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'backup') {
        try {
            $tables = [];
            $stmt = $pdo->query("SHOW TABLES");
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }
            
            $sql = "-- Database Backup " . date('Y-m-d H:i:s') . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW CREATE TABLE $table");
                $row = $stmt->fetch(PDO::FETCH_NUM);
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $row[1] . ";\n\n";
                
                $stmt = $pdo->query("SELECT * FROM $table");
                while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $keys = array_keys($data);
                    $values = array_values($data);
                    
                    $keys_str = "`" . implode("`, `", $keys) . "`";
                    $values_str = implode(", ", array_map(function($v) use ($pdo) {
                        return $v === null ? "NULL" : $pdo->quote($v);
                    }, $values));
                    
                    $sql .= "INSERT INTO `$table` ($keys_str) VALUES ($values_str);\n";
                }
                $sql .= "\n";
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            
            $filename = 'backup_' . date('Ymd_His') . '.sql';
            file_put_contents($backup_dir . $filename, $sql);
            $_SESSION['flash'] = ['type' => 'success', 'message' => "Backup database berhasil dibuat: $filename"];
            
        } catch (Exception $e) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "Gagal membuat backup: " . $e->getMessage()];
        }
        header("Location: " . base_url('admin/backup.php'));
        exit;
        
    } elseif ($_POST['action'] === 'delete') {
        $file = basename($_POST['filename']);
        if (file_exists($backup_dir . $file)) {
            unlink($backup_dir . $file);
            $_SESSION['flash'] = ['type' => 'success', 'message' => "File backup $file berhasil dihapus."];
        }
        header("Location: " . base_url('admin/backup.php'));
        exit;
        
    } elseif ($_POST['action'] === 'restore') {
        $file = basename($_POST['filename']);
        $filepath = $backup_dir . $file;
        if (file_exists($filepath)) {
            try {
                $sql = file_get_contents($filepath);
                $pdo->exec($sql);
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Database berhasil dipulihkan dari $file."];
            } catch (Exception $e) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => "Gagal memulihkan database: " . $e->getMessage()];
            }
        }
        header("Location: " . base_url('admin/backup.php'));
        exit;
    }
}

// Download action (GET)
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = $backup_dir . $file;
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// List backup files
$backups = [];
foreach (glob($backup_dir . "*.sql") as $filename) {
    $backups[] = [
        'name' => basename($filename),
        'size' => filesize($filename),
        'time' => filemtime($filename)
    ];
}
usort($backups, function($a, $b) {
    return $b['time'] - $a['time']; // Newest first
});

require_once __DIR__ . '/../templates/header.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Admin Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div style="background: var(--bg-white); padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Menu Admin</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="<?= base_url('admin/index.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="<?= base_url('admin/products.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="<?= base_url('admin/categories.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="<?= base_url('admin/orders.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
                    <a href="<?= base_url('admin/reports.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-chart-line" style="width: 25px;"></i> Laporan</a>
                    <a href="<?= base_url('admin/backup.php') ?>" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-database" style="width: 25px;"></i> Backup</a>
                    <a href="<?= base_url('admin/email_logs.php') ?>" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-envelope" style="width: 25px;"></i> Log Email</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Backup & Restore Database</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="backup">
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-download"></i> Buat Backup Baru</button>
                </form>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; background: var(--bg-white); border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <thead style="background: var(--bg-light); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1rem; text-align: left;">Nama File</th>
                        <th style="padding: 1rem; text-align: left;">Waktu Dibuat</th>
                        <th style="padding: 1rem; text-align: right;">Ukuran</th>
                        <th style="padding: 1rem; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($backups)): ?>
                    <tr><td colspan="4" style="text-align:center; padding: 2rem;">Belum ada file backup.</td></tr>
                    <?php else: ?>
                        <?php foreach($backups as $b): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                            <td style="padding: 1rem;"><?= date('d M Y, H:i:s', $b['time']) ?></td>
                            <td style="padding: 1rem; text-align: right;"><?= number_format($b['size'] / 1024, 2) ?> KB</td>
                            <td style="padding: 1rem; text-align: center; display: flex; gap: 0.5rem; justify-content: center;">
                                <a href="?download=<?= urlencode($b['name']) ?>" class="btn btn-outline" style="padding: 0.25rem 0.5rem; border-color: var(--success); color: var(--success);"><i class="fa-solid fa-file-arrow-down"></i> Unduh</a>
                                
                                <form method="POST" action="" onsubmit="return confirm('PERINGATAN: Memulihkan database akan menimpa data yang ada saat ini. Anda yakin ingin melanjutkan?');">
                                    <input type="hidden" name="action" value="restore">
                                    <input type="hidden" name="filename" value="<?= $b['name'] ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; border-color: var(--accent); color: var(--accent);"><i class="fa-solid fa-clock-rotate-left"></i> Pulihkan</button>
                                </form>
                                
                                <form method="POST" action="" onsubmit="return confirm('Hapus file backup ini?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="filename" value="<?= $b['name'] ?>">
                                    <button type="submit" class="btn btn-outline" style="padding: 0.25rem 0.5rem; border-color: var(--danger); color: var(--danger);"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
