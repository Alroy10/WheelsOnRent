<?php
session_start();
header('Content-Type: application/json');

// Debug: Log session details
error_log("save_booking.php - Session ID: " . session_id());
error_log("save_booking.php - Session email: " . (isset($_SESSION['email']) ? $_SESSION['email'] : 'not set'));

// Database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Connection failed']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Debug: Log entire POST data
error_log("save_booking.php - POST data: " . print_r($data, true));

// Get email from session or POST data
$email = $data['email'] ?? $_SESSION['email'] ?? '';

// Debug log
error_log("save_booking.php - Email from POST: " . ($data['email'] ?? 'not set'));
error_log("save_booking.php - Email from session: " . ($_SESSION['email'] ?? 'not set'));
error_log("save_booking.php - Final email used: " . $email);

// Validate email
if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'User email is required. Please log in.']);
    exit();
}

// Validate required fields
$required_fields = ['full_name', 'phone', 'location', 'pickup_date', 'dropoff_date', 'vehicle_name', 'vehicle_type', 'total_days', 'total_amount', 'payment_method'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing or empty field: $field"]);
        exit();
    }
}

// Get delivery value, default to 0 if not set
$delivery = isset($data['delivery']) ? (int)$data['delivery'] : 0;
error_log("save_booking.php - Delivery value: " . $delivery);

// Start transaction
$conn->begin_transaction();

try {
    // Prepare SQL statement for booking
    $sql = "INSERT INTO bookings (email, full_name, phone, pickup_location, pickup_date, 
            dropoff_date, delivery_required, vehicle_name, vehicle_type, total_days, 
            total_amount, payment_method, booking_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssisssds", 
        $email,
        $data['full_name'],
        $data['phone'],
        $data['location'],
        $data['pickup_date'],
        $data['dropoff_date'],
        $delivery,
        $data['vehicle_name'],
        $data['vehicle_type'],
        $data['total_days'],
        $data['total_amount'],
        $data['payment_method']
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Fetch user_id and username based on email
    $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $user_id = $user['user_id'];
        $username = $user['username'];
    } else {
        // If user not found, log but continue with booking
        error_log("save_booking.php - No user found for email: $email");
        $conn->commit();
        echo json_encode(['success' => true]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Insert default admin message for all bookings
    $default_admin_message = "Thank you for choosing WheelsOnRent! To ensure a smooth and secure rental experience, please remember to carry your valid driving license at the time of vehicle pickup. Safe and happy riding!";
    $admin_username = "Admin";
    $stmt = $conn->prepare("INSERT INTO contacts (user_id, username, email, phone, message, is_admin_message, status, created_at) VALUES (?, ?, ?, ?, ?, 1, 'replied', NOW())");
    $stmt->bind_param("issss", $user_id, $admin_username, $email, $data['phone'], $default_admin_message);
    if (!$stmt->execute()) {
        error_log("save_booking.php - Failed to insert default admin message: " . $stmt->error);
    }
    $stmt->close();

    // Insert delivery-specific admin message if delivery_required is 1
    if ($delivery === 1) {
        $contact_id = "+91-9876543210"; // Static contact ID (replace with dynamic ID if needed)
        $delivery_message = "As you have chosen delivery required, this is our contact ID: $contact_id. Please give us a call. So that we can deliver your vehicle to your desired location.";
        $stmt = $conn->prepare("INSERT INTO contacts (user_id, username, email, phone, message, is_admin_message, status, created_at) VALUES (?, ?, ?, ?, ?, 1, 'replied', NOW())");
        $stmt->bind_param("issss", $user_id, $admin_username, $email, $data['phone'], $delivery_message);
        if (!$stmt->execute()) {
            error_log("save_booking.php - Failed to insert delivery admin message: " . $stmt->error);
        }
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    error_log("save_booking.php - Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>