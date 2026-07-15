<?php
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WarungKu - Toko Kelontong Modern</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/uasproject/assets/css/style.css">
</head>
<body>

<nav class="navbar glass">
    <a href="/uasproject/index.php" class="nav-brand">
        <i class="fa-solid fa-store"></i> WarungKu
    </a>
    
    <div class="nav-links">
        <a href="/uasproject/index.php">Beranda</a>
        <a href="/uasproject/products.php">Katalog</a>
        <a href="#">Kategori</a>
    </div>

    <div class="nav-actions">
        <?php
        $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
        ?>
        <a href="/uasproject/cart.php" class="btn btn-outline">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="badge" id="cart-badge"><?= $cartCount ?></span>
        </a>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="/uasproject/admin/index.php" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="/uasproject/profile.php" class="btn btn-primary"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['name']) ?></a>
            <?php endif; ?>
            <a href="/uasproject/auth/logout.php" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i></a>
        <?php else: ?>
            <a href="/uasproject/auth/login.php" class="btn btn-primary">Masuk</a>
        <?php endif; ?>
    </div>
</nav>

<main>
    <!-- Tempat memunculkan flash message dari PHP ke JS Toast -->
    <?php if(isset($_SESSION['flash'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?= $_SESSION['flash']['message'] ?>", "<?= $_SESSION['flash']['type'] ?>");
            });
        </script>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
