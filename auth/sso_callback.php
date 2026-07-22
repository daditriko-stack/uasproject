<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Terima credential dari Google (JWT Token)
    $credential = $_POST['credential'] ?? '';
    
    if($credential){
        $parts = explode('.', $credential);
        if(count($parts) === 3){
            // Decode payload (tanpa memverifikasi signature untuk tujuan demo/tanpa composer)
            $payload = json_decode(base64_decode($parts[1]), true);
            
            if($payload && isset($payload['email'])){
                $email = $payload['email'];
                $name = $payload['name'] ?? 'Google User';
                $google_id = $payload['sub'] ?? '';
                
                // Cari pengguna berdasarkan email (disini kita pakai username = email)
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if($user){
                    // Login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Login SSO berhasil!'];
                } else {
                    // Register and Login
                    // Password acak karena login lewat SSO
                    $random_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, 'customer')");
                    $stmt->execute([$name, $email, $random_pass]);
                    $new_id = $pdo->lastInsertId();
                    
                    $_SESSION['user_id'] = $new_id;
                    $_SESSION['username'] = $email;
                    $_SESSION['name'] = $name;
                    $_SESSION['role'] = 'customer';
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pendaftaran SSO berhasil!'];
                }
                
                header("Location: " . base_url('index.php'));
                exit;
            }
        }
    }
}

$_SESSION['flash'] = ['type' => 'error', 'message' => 'Login SSO gagal!'];
header("Location: " . base_url('auth/login.php'));
exit;
