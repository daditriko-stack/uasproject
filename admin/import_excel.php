<?php
require_once __DIR__ . '/../config/db.php';
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: " . base_url('auth/login.php'));
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])){
    $file = $_FILES['file'];
    
    if($file['error'] === UPLOAD_ERR_OK){
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if(strtolower($ext) === 'csv'){
            $handle = fopen($file['tmp_name'], "r");
            
            // Skip header line if exists
            $header = fgetcsv($handle);
            
            $count = 0;
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock, description) VALUES (?, ?, ?, ?, ?)");
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if(count($data) >= 5) {
                        // asumsi: name, category_id, price, stock, description
                        $stmt->execute([$data[0], (int)$data[1], (float)$data[2], (int)$data[3], $data[4]]);
                        $count++;
                    }
                }
                $pdo->commit();
                $_SESSION['flash'] = ['type' => 'success', 'message' => "Berhasil mengimpor $count produk."];
            } catch(Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal mengimpor: ' . $e->getMessage()];
            }
            fclose($handle);
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Format file tidak didukung. Harap unggah CSV.'];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Gagal mengunggah file.'];
    }
}

header("Location: " . base_url('admin/products.php'));
exit;
