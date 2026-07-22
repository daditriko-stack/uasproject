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
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

<nav class="navbar glass">
    <a href="<?= base_url('index.php') ?>" class="nav-brand">
        <i class="fa-solid fa-store"></i> WarungKu
    </a>
    
    <div class="nav-links">
        <a href="<?= base_url('index.php') ?>">Beranda</a>
        <a href="<?= base_url('products.php') ?>">Katalog</a>
        <a href="#">Kategori</a>
    </div>

    <div class="nav-actions">
        <button id="theme-toggle" class="btn btn-outline" style="border: none; font-size: 1.25rem; padding: 0.5rem;"><i class="fa-solid fa-moon"></i></button>
        <?php
        $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
        ?>
        <a href="<?= base_url('cart.php') ?>" class="btn btn-outline">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="badge" id="cart-badge"><?= $cartCount ?></span>
        </a>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($_SESSION['role'] === 'admin'): ?>
                <a href="<?= base_url('admin/index.php') ?>" class="btn btn-primary">Dashboard</a>
            <?php else: ?>
                <a href="<?= base_url('profile.php') ?>" class="btn btn-primary"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($_SESSION['name']) ?></a>
            <?php endif; ?>
            <a href="<?= base_url('auth/logout.php') ?>" class="btn btn-outline" style="border-color: var(--danger); color: var(--danger);"><i class="fa-solid fa-right-from-bracket"></i></a>
        <?php else: ?>
            <a href="<?= base_url('auth/login.php') ?>" class="btn btn-primary">Masuk</a>
        <?php endif; ?>
    </div>
</nav>

<main>
    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['is_verified']) && !$_SESSION['is_verified']): ?>
        <div style="background: var(--accent); color: var(--secondary); text-align: center; padding: 0.75rem; font-weight: 500;">
            <i class="fa-solid fa-circle-exclamation"></i> Email Anda belum diverifikasi. Beberapa fitur mungkin dibatasi. 
            <a href="<?= base_url('auth/resend-verification.php') ?>" style="text-decoration: underline; font-weight: 700;">Kirim ulang email verifikasi</a>
        </div>
    <?php endif; ?>
    <!-- Tempat memunculkan flash message dari PHP ke JS Toast -->
    <?php if(isset($_SESSION['flash'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showToast("<?= $_SESSION['flash']['message'] ?>", "<?= $_SESSION['flash']['type'] ?>");
            });
        </script>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
