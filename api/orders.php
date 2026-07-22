<?php
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

function sendResponse($status, $data = null, $message = '') {
    http_response_code($status);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// Authentication
$headers = apache_request_headers();
$api_key = $headers['X-API-Key'] ?? null;
if (!$api_key && isset($headers['Authorization'])) {
    $parts = explode(' ', $headers['Authorization']);
    if (count($parts) === 2 && strtolower($parts[0]) === 'bearer') {
        $api_key = $parts[1];
    }
}

if (!$api_key) {
    sendResponse(401, null, 'Authentication required');
}

$stmt = $pdo->prepare("SELECT id, role FROM users WHERE api_key = ?");
$stmt->execute([$api_key]);
$user = $stmt->fetch();

if (!$user) {
    sendResponse(401, null, 'Invalid API Key');
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    if ($user['role'] === 'admin') {
        // Admin can see all orders
        $stmt = $pdo->query("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
        sendResponse(200, $stmt->fetchAll(), 'Success');
    } else {
        // Customer sees their own orders
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user['id']]);
        sendResponse(200, $stmt->fetchAll(), 'Success');
    }
} elseif ($method === 'POST') {
    // Create new order
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    
    $items = $input['items'] ?? [];
    $address = $input['shipping_address'] ?? '';
    
    if (empty($items) || empty($address)) {
        sendResponse(400, null, 'Missing items or shipping_address');
    }
    
    try {
        $pdo->beginTransaction();
        
        $total_price = 0;
        $order_items = [];
        
        foreach ($items as $item) {
            $product_id = (int)$item['product_id'];
            $qty = (int)$item['quantity'];
            
            $stmt = $pdo->prepare("SELECT price, stock FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $p = $stmt->fetch();
            
            if (!$p || $p['stock'] < $qty) {
                throw new Exception("Product ID $product_id not found or insufficient stock");
            }
            
            $price = $p['price'];
            $total_price += ($price * $qty);
            $order_items[] = ['product_id' => $product_id, 'quantity' => $qty, 'price' => $price];
        }
        
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, status, shipping_address) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$user['id'], $total_price, $address]);
        $order_id = $pdo->lastInsertId();
        
        // Insert items
        foreach ($order_items as $oi) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $oi['product_id'], $oi['quantity'], $oi['price']]);
            
            // Deduct stock
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$oi['quantity'], $oi['product_id']]);
        }
        
        $pdo->commit();
        sendResponse(201, ['order_id' => $order_id], 'Order created successfully');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        sendResponse(400, null, $e->getMessage());
    }
} else {
    sendResponse(405, null, 'Method not allowed');
}
