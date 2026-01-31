<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    jsonResponse(false, 'Please enter username and password');
}

$conn = getDBConnection();

// Check user credentials
$stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
$stmt->bind_param("ss", $username, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse(false, 'Invalid username or password');
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['password'])) {
    jsonResponse(false, 'Invalid username or password');
}

// Set session variables
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['role'] = $user['role'];
$_SESSION['logged_in'] = true;

jsonResponse(true, 'Login successful', [
    'id' => $user['id'],
    'username' => $user['username'],
    'email' => $user['email'],
    'role' => $user['role']
]);

$stmt->close();
$conn->close();
?>