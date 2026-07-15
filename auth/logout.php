<?php
session_start();
session_unset();
session_destroy();
session_start();
$_SESSION['flash'] = ['type' => 'success', 'message' => 'Anda telah berhasil keluar.'];
header("Location: /uasproject/auth/login.php");
exit;
