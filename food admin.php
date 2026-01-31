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
    case 'create':
        if ($method === 'POST') {
            createFood($conn);
        } else {
            jsonResponse(false, 'Invalid method for create');
        }
        break;
    case 'update':
        if ($method === 'PUT') {
            $id = $_GET['id'] ?? 0;
            updateFood($conn, $id);
        } else {
            jsonResponse(false, 'Invalid method for update');
        }
        break;
    case 'delete':
        if ($method === 'DELETE') {
            $id = $_GET['id'] ?? 0;
            deleteFood($conn, $id);
        } else {
            jsonResponse(false, 'Invalid method for delete');
        }
        break;
    default:
        jsonResponse(false, 'Invalid action');
}

function createFood($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validation
    if (!$data || empty($data['name']) || empty($data['price']) || empty($data['category_id'])) {
        jsonResponse(false, 'Missing required fields');
    }
    
    $name = trim($data['name']);
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price']);
    $category_id = intval($data['category_id']);
    $image_url = trim($data['image_url'] ?? '');
    
    $stmt = $conn->prepare("INSERT INTO foods (name, description, price, category_id, image_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdis", $name, $description, $price, $category_id, $image_url);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Food item created successfully', ['id' => $conn->insert_id]);
    } else {
        jsonResponse(false, 'Failed to create food item: ' . $conn->error);
    }
    
    $stmt->close();
}

function updateFood($conn, $id) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['name']) || empty($data['price']) || empty($data['category_id'])) {
        jsonResponse(false, 'Missing required fields');
    }
    
    $name = trim($data['name']);
    $description = trim($data['description'] ?? '');
    $price = floatval($data['price']);
    $category_id = intval($data['category_id']);
    $image_url = trim($data['image_url'] ?? '');
    
    $stmt = $conn->prepare("UPDATE foods SET name = ?, description = ?, price = ?, category_id = ?, image_url = ? WHERE id = ?");
    $stmt->bind_param("ssdisi", $name, $description, $price, $category_id, $image_url, $id);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Food item updated successfully');
    } else {
        jsonResponse(false, 'Failed to update food item: ' . $conn->error);
    }
    
    $stmt->close();
}

function deleteFood($conn, $id) {
    // Check if food exists in any orders
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE food_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        jsonResponse(false, 'Cannot delete food item that exists in orders');
    }
    
    // Delete the food item
    $stmt = $conn->prepare("DELETE FROM foods WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        jsonResponse(true, 'Food item deleted successfully');
    } else {
        jsonResponse(false, 'Failed to delete food item: ' . $conn->error);
    }
    
    $stmt->close();
}

$conn->close();
?>