<?php
session_start();
header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$vehicle_name = $data['vehicle_name'] ?? '';
$pickup_date = $data['pickup_date'] ?? '';

// Validate required fields
if (empty($email) || empty($vehicle_name) || empty($pickup_date)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Delete the booking
    $stmt = $conn->prepare("DELETE FROM bookings WHERE email = ? AND vehicle_name = ? AND pickup_date = ?");
    $stmt->bind_param("sss", $email, $vehicle_name, $pickup_date);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting booking");
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>