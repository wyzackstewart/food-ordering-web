<?php
require_once 'config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'Please login to place an order');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
    jsonResponse(false, 'Invalid order data');
}

$conn = getDBConnection();
$conn->begin_transaction();

try {
    $userId = $_SESSION['user_id'];
    $total = $data['total'];
    
    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)");
    $stmt->bind_param("id", $userId, $total);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create order: " . $conn->error);
    }
    
    $orderId = $conn->insert_id;
    
    // Insert order items
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, food_id, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($data['items'] as $item) {
        if (!isset($item['id'], $item['quantity'], $item['price'])) {
            throw new Exception("Invalid item data");
        }
        
        $stmt->bind_param("iiid", $orderId, $item['id'], $item['quantity'], $item['price']);
        if (!$stmt->execute()) {
            throw new Exception("Failed to add item to order: " . $conn->error);
        }
    }
    
    $conn->commit();
    jsonResponse(true, 'Order placed successfully! Order ID: ' . $orderId, ['order_id' => $orderId]);
    
} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Error placing order: ' . $e->getMessage());
}

$stmt->close();
$conn->close();
?>