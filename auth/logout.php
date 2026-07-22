<?php
require_once __DIR__ . '/../config/db.php';
session_start();
session_unset();
session_destroy();
session_start();
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Anda telah berhasil keluar.'];
header("Location: " . base_url('auth/login.php'));
exit;
