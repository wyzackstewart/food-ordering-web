<?php
include ". ./config/config.php";

$conn = getDBConnection();
echo "DB CONNECTED OK";
exit();

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get and validate input
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    jsonResponse(false, 'All fields are required');
}

if ($password !== $confirm_password) {
    jsonResponse(false, 'Passwords do not match');
}

if (strlen($password) < 6) {
    jsonResponse(false, 'Password must be at least 6 characters');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address');
}

$conn = getDBConnection();

// Check if username or email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    jsonResponse(false, 'Username or email already exists');
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $email, $hashed_password);

if ($stmt->execute()) {
    jsonResponse(true, 'Registration successful. Please login.');
} else {
    jsonResponse(false, 'Registration failed: ' . $conn->error);
}

$stmt->close();
$conn->close();
?>