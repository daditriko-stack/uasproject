<?php
require_once __DIR__ . '/templates/header.php';

// Ambil produk unggulan
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 4");
$featured_products = $stmt->fetchAll();
?>

<div class="hero">
    <h1>Belanja Kebutuhan Harian Lebih Mudah</h1>
    <p>WarungKu menyediakan berbagai macam kebutuhan pokok, segar, dan berkualitas langsung ke rumah Anda.</p>
    <div class="search-bar">
        <input type="text" id="live-search" placeholder="Cari beras, gula, minyak...">
        <button class="btn btn-primary" style="margin-left: -50px; z-index: 2;"><i class="fa-solid fa-search"></i></button>
    </div>
</div>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2 class="section-title" style="margin-bottom: 0;">Kategori Pilihan</h2>
        <a href="/uasproject/products.php" style="color: var(--primary); font-weight: 600;">Lihat Semua <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    
    <div style="display: flex; gap: 1rem; margin-bottom: 3rem; overflow-x: auto; padding-bottom: 1rem;">
        <?php
        $cats = $pdo->query("SELECT * FROM categories")->fetchAll();
        foreach($cats as $cat):
        ?>
        <a href="/uasproject/products.php?category=<?= $cat['id'] ?>" style="min-width: 150px; text-align: center; background: white; padding: 1.5rem; border-radius: 16px; border: 1px solid var(--border); transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-5px)'; this.style.borderColor='var(--primary)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='var(--border)';">
            <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem;"></i>
            <h3 style="font-size: 1rem;"><?= htmlspecialchars($cat['name']) ?></h3>
        </a>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title">Produk Unggulan</h2>
    <div class="product-grid">
        <?php foreach($featured_products as $p): ?>
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
                        Stok: <?= $p['stock'] ?> <?= $p['stock'] < 5 ? '(Sisa Sedikit!)' : '' ?>
                    </span>
                    <form method="POST" action="/uasproject/cart.php" style="margin: 0;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="btn btn-outline" <?= $p['stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-cart-plus"></i> Tambah
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/templates/footer.php'; ?>
