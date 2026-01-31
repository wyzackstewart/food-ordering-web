<?php
require_once 'config.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    jsonResponse(false, 'Unauthorized access');
}

$conn = getDBConnection();
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'getAll':
        getAllOrders($conn);
        break;
    case 'updateStatus':
        if ($method === 'PUT') {
            updateOrderStatus($conn);
        }
        break;
    default:
        jsonResponse(false, 'Invalid action');
}

function getAllOrders($conn) {
    $result = $conn->query("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        // Get order items for each order
        $stmt = $conn->prepare("
            SELECT oi.*, f.name as food_name 
            FROM order_items oi 
            JOIN foods f ON oi.food_id = f.id 
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $itemsResult = $stmt->get_result();
        
        $items = [];
        while ($item = $itemsResult->fetch_assoc()) {
            $items[] = $item;
        }
        
        $row['items'] = $items;
        $orders[] = $row;
        $stmt->close();
    }
    
    jsonResponse(true, '', ['orders' => $orders]);
}

function updateOrderStatus($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['orderId']) || !isset($data['status'])) {
        jsonResponse(false, 'Missing required data');
    }
    
    $orderId = intval($data['orderId']);
    $status = trim($data['status']);
    
    // Validate status
    $validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        jsonResponse(false, 'Invalid status');
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $orderId);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Order status updated');
    } else {
        jsonResponse(false, 'Failed to update order status: ' . $conn->error);
    }
    
    $stmt->close();
}

$conn->close();
?>