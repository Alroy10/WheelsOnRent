<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

file_put_contents('debug.log', "Session: " . print_r($_SESSION, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', "Input: " . file_get_contents('php://input') . "\n", FILE_APPEND);

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "wheelsonrent");
if ($conn->connect_error) {
    file_put_contents('php_errors.log', "Database connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$message = $data['message'] ?? '';

if (empty($username) || empty($email) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND username = ?");
$stmt->bind_param("ss", $email, $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    $stmt->close();
    $conn->close();
    exit();
}
$user = $result->fetch_assoc();
$user_id = $user['user_id'];
$stmt->close();

$admin_username = "Admin";
$is_admin_message = 1;
$status = 'replied';
$stmt = $conn->prepare("INSERT INTO contacts (user_id, username, email, message, is_admin_message, status) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssis", $user_id, $admin_username, $email, $message, $is_admin_message, $status);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    file_put_contents('php_errors.log', "Insert failed: " . $stmt->error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $stmt->error]);
}
$stmt->close();
$conn->close();
?>