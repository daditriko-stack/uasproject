<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: /uasproject/auth/login.php");
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['action'])){
        if($_POST['action'] === 'add'){
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', $name));
            $icon = trim($_POST['icon']);
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, icon) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $icon]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori berhasil ditambahkan.'];
        } elseif($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Kategori berhasil dihapus.'];
        }
        header("Location: /uasproject/admin/categories.php");
        exit;
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>
<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Admin Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Menu Admin</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="/uasproject/admin/index.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="/uasproject/admin/products.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="/uasproject/admin/categories.php" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="/uasproject/admin/orders.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Manajemen Kategori</h2>
                <button onclick="openModal('addCategoryModal')" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Kategori</button>
            </div>
            
            <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <thead style="background: var(--bg-light); border-bottom: 1px solid var(--border);">
                    <tr>
                        <th style="padding: 1rem; text-align: left;">Icon</th>
                        <th style="padding: 1rem; text-align: left;">Nama Kategori</th>
                        <th style="padding: 1rem; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categories as $c): ?>
                    <tr style="border-bottom: 1px solid var(--border);">
                        <td style="padding: 1rem; font-size: 1.5rem; color: var(--primary);"><i class="fa-solid <?= htmlspecialchars($c['icon']) ?>"></i></td>
                        <td style="padding: 1rem; font-weight: 500;"><?= htmlspecialchars($c['name']) ?></td>
                        <td style="padding: 1rem; text-align: center;">
                            <form method="POST" action="" onsubmit="return confirm('Hapus kategori ini?');" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger); padding: 0.25rem 0.5rem;"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div id="addCategoryModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('addCategoryModal')">&times;</span>
        <h3 style="margin-bottom: 1.5rem;">Tambah Kategori Baru</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Nama Kategori</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Icon (Contoh: fa-box)</label>
                <input type="text" name="icon" class="form-control" value="fa-box" required>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCategoryModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
