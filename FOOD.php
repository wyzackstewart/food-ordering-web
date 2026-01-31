<?php
require_once 'config.php';

$conn = getDBConnection();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAll':
        getAllFoods($conn);
        break;
    case 'getCategories':
        getCategories($conn);
        break;
    case 'getById':
        $id = $_GET['id'] ?? 0;
        getFoodById($conn, $id);
        break;
    default:
        jsonResponse(false, 'Invalid action');
}

function getAllFoods($conn) {
    $result = $conn->query("
        SELECT f.*, c.name as category_name 
        FROM foods f 
        JOIN categories c ON f.category_id = c.id 
        ORDER BY f.created_at DESC
    ");
    
    $foods = [];
    while ($row = $result->fetch_assoc()) {
        $foods[] = $row;
    }
    
    jsonResponse(true, '', ['foods' => $foods]);
}

function getCategories($conn) {
    $result = $conn->query("SELECT * FROM categories ORDER BY name");
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    jsonResponse(true, '', ['categories' => $categories]);
}

function getFoodById($conn, $id) {
    $stmt = $conn->prepare("
        SELECT f.*, c.name as category_name 
        FROM foods f 
        JOIN categories c ON f.category_id = c.id 
        WHERE f.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        jsonResponse(false, 'Food item not found');
    }
    
    $food = $result->fetch_assoc();
    jsonResponse(true, '', ['food' => $food]);
}

$conn->close();
?>