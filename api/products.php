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

$user = null;
if ($api_key) {
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE api_key = ?");
    $stmt->execute([$api_key]);
    $user = $stmt->fetch();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product) {
            $product['qr_code'] = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode(base_url('products.php?id=' . $product['id']));
            sendResponse(200, $product, 'Success');
        } else {
            sendResponse(404, null, 'Product not found');
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
        sendResponse(200, $stmt->fetchAll(), 'Success');
    }
}

// Write Operations require Admin Auth
if (!$user || $user['role'] !== 'admin') {
    sendResponse(401, null, 'Unauthorized or Admin access required');
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

if ($method === 'POST') {
    $name = $input['name'] ?? '';
    $cat = (int)($input['category_id'] ?? 0);
    $price = (float)($input['price'] ?? 0);
    $stock = (int)($input['stock'] ?? 0);
    $desc = $input['description'] ?? '';
    
    if (!$name || !$cat || $price <= 0) {
        sendResponse(400, null, 'Missing required fields (name, category_id, price)');
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (name, category_id, price, stock, description) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$name, $cat, $price, $stock, $desc])) {
        sendResponse(201, ['id' => $pdo->lastInsertId()], 'Product created successfully');
    } else {
        sendResponse(500, null, 'Failed to create product');
    }
} elseif ($method === 'PUT') {
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if (!$id) sendResponse(400, null, 'Missing product ID');
    
    $name = $input['name'] ?? null;
    $price = isset($input['price']) ? (float)$input['price'] : null;
    $stock = isset($input['stock']) ? (int)$input['stock'] : null;
    
    // Build dynamic query
    $updates = [];
    $params = [];
    if ($name) { $updates[] = "name = ?"; $params[] = $name; }
    if ($price !== null) { $updates[] = "price = ?"; $params[] = $price; }
    if ($stock !== null) { $updates[] = "stock = ?"; $params[] = $stock; }
    
    if (empty($updates)) sendResponse(400, null, 'No fields to update');
    
    $params[] = $id;
    $sql = "UPDATE products SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($params)) {
        sendResponse(200, null, 'Product updated successfully');
    } else {
        sendResponse(500, null, 'Failed to update product');
    }
} elseif ($method === 'DELETE') {
    $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
    if (!$id) sendResponse(400, null, 'Missing product ID');
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    if ($stmt->execute([$id])) {
        sendResponse(200, null, 'Product deleted successfully');
    } else {
        sendResponse(500, null, 'Failed to delete product');
    }
} else {
    sendResponse(405, null, 'Method not allowed');
}
