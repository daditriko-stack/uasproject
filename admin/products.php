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
            $cat = (int)$_POST['category_id'];
            $price = (float)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $desc = trim($_POST['description']);
            $image_url = 'default.jpg';
            
            if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
                $dir = __DIR__ . '/../assets/images/';
                if(!is_dir($dir)) mkdir($dir, 0777, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_url = uniqid('prod_') . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $dir . $image_url);
            }
            
            $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $cat, $price, $stock, $desc, $image_url]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil ditambahkan.'];
        } elseif($_POST['action'] === 'edit'){
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $cat = (int)$_POST['category_id'];
            $price = (float)$_POST['price'];
            $stock = (int)$_POST['stock'];
            $desc = trim($_POST['description']);
            
            $image_query = "";
            $params = [$name, $cat, $price, $stock, $desc];
            
            if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK){
                $dir = __DIR__ . '/../assets/images/';
                if(!is_dir($dir)) mkdir($dir, 0777, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $image_url = uniqid('prod_') . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], $dir . $image_url);
                $image_query = ", image_url=?";
                $params[] = $image_url;
            }
            $params[] = $id;
            
            $stmt = $pdo->prepare("UPDATE products SET name=?, category_id=?, price=?, stock=?, description=? $image_query WHERE id=?");
            $stmt->execute($params);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil diubah.'];
        } elseif($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Produk berhasil dihapus.'];
        }
        header("Location: /uasproject/admin/products.php");
        exit;
    }
}

$products = $pdo->query("SELECT p.*, c.name as cat_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();

require_once __DIR__ . '/../templates/header.php';
?>
<!-- html2pdf CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Admin Sidebar -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1.5rem; color: var(--secondary);">Menu Admin</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="/uasproject/admin/index.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-gauge-high" style="width: 25px;"></i> Dashboard</a>
                    <a href="/uasproject/admin/products.php" style="padding: 0.75rem; border-radius: 8px; background: var(--primary); color: white;"><i class="fa-solid fa-box" style="width: 25px;"></i> Produk</a>
                    <a href="/uasproject/admin/categories.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-tags" style="width: 25px;"></i> Kategori</a>
                    <a href="/uasproject/admin/orders.php" style="padding: 0.75rem; border-radius: 8px; color: var(--text-main);"><i class="fa-solid fa-clipboard-list" style="width: 25px;"></i> Pesanan</a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 class="section-title" style="margin-bottom: 0;">Manajemen Produk</h2>
                <div style="display: flex; gap: 0.5rem;">
                    <button onclick="openModal('importExcelModal')" class="btn btn-outline" style="border-color: var(--success); color: var(--success);"><i class="fa-solid fa-file-import"></i> Import</button>
                    <a href="/uasproject/admin/export_excel.php?type=products" class="btn btn-outline" style="border-color: var(--success); color: var(--success);"><i class="fa-solid fa-file-excel"></i> Excel</a>
                    <a href="/uasproject/admin/export_word.php?type=products" class="btn btn-outline" style="border-color: #2563EB; color: #2563EB;"><i class="fa-solid fa-file-word"></i> Word</a>
                    <button onclick="exportToPDF()" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);"><i class="fa-solid fa-file-pdf"></i> PDF</button>
                    <button onclick="openModal('addProductModal')" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Tambah Produk</button>
                </div>
            </div>
            
            <div id="print-area">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <thead style="background: var(--bg-light); border-bottom: 1px solid var(--border);">
                        <tr>
                            <th style="padding: 1rem; text-align: left;">Nama</th>
                            <th style="padding: 1rem; text-align: left;">Kategori</th>
                            <th style="padding: 1rem; text-align: right;">Harga</th>
                            <th style="padding: 1rem; text-align: center;">Stok</th>
                            <th class="no-print" style="padding: 1rem; text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($products as $p): ?>
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="padding: 1rem;"><?= htmlspecialchars($p['cat_name']) ?></td>
                            <td style="padding: 1rem; text-align: right;"><?= formatRupiah($p['price']) ?></td>
                            <td style="padding: 1rem; text-align: center;">
                                <span class="badge" style="background: <?= $p['stock'] < 5 ? 'var(--danger)' : 'var(--success)' ?>;"><?= $p['stock'] ?></span>
                            </td>
                            <td class="no-print" style="padding: 1rem; text-align: center;">
                                <button onclick="editProduct(<?= htmlspecialchars(json_encode($p)) ?>)" class="btn btn-outline" style="border-color: var(--accent); color: var(--accent); padding: 0.25rem 0.5rem;"><i class="fa-solid fa-pen"></i></button>
                                <form method="POST" action="" onsubmit="return confirm('Hapus produk ini?');" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
</div>

<!-- Modal Tambah Produk -->
<div id="addProductModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('addProductModal')">&times;</span>
        <h3 style="margin-bottom: 1.5rem;">Tambah Produk Baru</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Pilih Kategori...</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label>Harga (Rp)</label>
                    <input type="number" name="price" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Stok</label>
                    <input type="number" name="stock" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Foto Produk (Opsional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('addProductModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Produk -->
<div id="editProductModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('editProductModal')">&times;</span>
        <h3 style="margin-bottom: 1.5rem;">Edit Produk</h3>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Nama Produk</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <select name="category_id" id="edit_category_id" class="form-control" required>
                    <option value="">Pilih Kategori...</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; gap: 1rem;">
                <div class="form-group" style="flex: 1;">
                    <label>Harga (Rp)</label>
                    <input type="number" name="price" id="edit_price" class="form-control" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Stok</label>
                    <input type="number" name="stock" id="edit_stock" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Ganti Foto Produk (Opsional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('editProductModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Excel -->
<div id="importExcelModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('importExcelModal')">&times;</span>
        <h3 style="margin-bottom: 1.5rem;">Import Data Produk (CSV)</h3>
        <form method="POST" action="/uasproject/admin/import_excel.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Pilih File CSV</label>
                <input type="file" name="file" class="form-control" accept=".csv" required>
                <small style="color: var(--text-muted); display: block; margin-top: 0.5rem;">Format Kolom: <code>name, category_id, price, stock, description</code></small>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="closeModal('importExcelModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Import Sekarang</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProduct(product) {
    document.getElementById('edit_id').value = product.id;
    document.getElementById('edit_name').value = product.name;
    document.getElementById('edit_category_id').value = product.category_id;
    document.getElementById('edit_price').value = product.price;
    document.getElementById('edit_stock').value = product.stock;
    document.getElementById('edit_description').value = product.description;
    openModal('editProductModal');
}

function exportToPDF() {
    const element = document.getElementById('print-area');
    // Hide action columns for printing
    const noPrintElements = document.querySelectorAll('.no-print');
    noPrintElements.forEach(el => el.style.display = 'none');
    
    html2pdf().from(element).save('daftar_produk.pdf').then(() => {
        // Restore action columns
        noPrintElements.forEach(el => el.style.display = '');
    });
}
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
