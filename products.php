<?php
require_once __DIR__ . '/templates/header.php';

$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['q']) ? $_GET['q'] : '';

$query = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if($category_filter) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

if($search) {
    $query .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}

$query .= " ORDER BY p.id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; gap: 2rem;">
        
        <!-- Sidebar Filter -->
        <aside style="width: 250px; flex-shrink: 0;">
            <div style="background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border);">
                <h3 style="margin-bottom: 1rem; color: var(--secondary);">Pencarian</h3>
                <form method="GET" action="">
                    <?php if($category_filter): ?>
                        <input type="hidden" name="category" value="<?= $category_filter ?>">
                    <?php endif; ?>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Cari produk..." style="margin-bottom: 1rem;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Cari</button>
                </form>

                <h3 style="margin: 1.5rem 0 1rem; color: var(--secondary);">Kategori</h3>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <a href="products.php<?= $search ? '?q='.urlencode($search) : '' ?>" style="padding: 0.5rem; border-radius: 8px; <?= !$category_filter ? 'background: var(--primary); color: white;' : 'color: var(--text-main);' ?>">Semua Kategori</a>
                    <?php foreach($categories as $c): ?>
                        <a href="products.php?category=<?= $c['id'] ?><?= $search ? '&q='.urlencode($search) : '' ?>" style="padding: 0.5rem; border-radius: 8px; <?= $category_filter == $c['id'] ? 'background: var(--primary); color: white;' : 'color: var(--text-main);' ?>">
                            <?= htmlspecialchars($c['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>

        <!-- Product List -->
        <div style="flex: 1;">
            <h2 class="section-title">Katalog Produk</h2>
            
            <?php if(empty($products)): ?>
                <div style="text-align: center; padding: 3rem; background: white; border-radius: 16px; border: 1px dashed var(--border);">
                    <i class="fa-solid fa-box-open" style="font-size: 3rem; color: var(--border); margin-bottom: 1rem;"></i>
                    <h3>Tidak ada produk yang ditemukan.</h3>
                </div>
            <?php else: ?>
                <div class="product-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                    <?php foreach($products as $p): ?>
                    <div class="product-card">
                        <div class="product-image" style="padding: 0;">
                            <img src="/uasproject/assets/images/<?= htmlspecialchars($p['image_url'] ?? 'default.jpg') ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover; display: block;" onerror="this.onerror=null; this.parentNode.innerHTML='<i class=\'fa-solid fa-image\'></i>';">
                        </div>
                        <div class="product-info">
                            <div class="product-category"><?= htmlspecialchars($p['category_name']) ?></div>
                            <h3 class="product-title"><?= htmlspecialchars($p['name']) ?></h3>
                            <div class="product-price"><?= formatRupiah($p['price']) ?></div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 0.85rem; color: <?= $p['stock'] < 5 ? 'var(--danger)' : 'var(--text-muted)' ?>;">
                                    Stok: <?= $p['stock'] ?>
                                </span>
                                <form method="POST" action="/uasproject/cart.php" style="margin: 0;">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-outline" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-cart-plus"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
