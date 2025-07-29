<?php
session_start();
header('Content-Type: application/json');

// Suppress PHP errors and log them
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");
if ($conn->connect_error) {
    file_put_contents('php_errors.log', "Database connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$contact_id = $data['contact_id'] ?? null;
$reply_text = $data['reply_text'] ?? null;

// Validate inputs
if (empty($contact_id) || !is_numeric($contact_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid or missing contact ID']);
    exit();
}
if (empty($reply_text)) {
    echo json_encode(['success' => false, 'message' => 'Reply text is required']);
    exit();
}
if (preg_match('/[<>\'";]/', $reply_text)) {
    echo json_encode(['success' => false, 'message' => 'Reply contains invalid characters']);
    exit();
}

// Verify contact exists
$stmt = $conn->prepare("SELECT id, email FROM contacts WHERE id = ?");
$stmt->bind_param("i", $contact_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Contact not found']);
    $stmt->close();
    $conn->close();
    exit();
}
$contact = $result->fetch_assoc();
$user_email = $contact['email'];
$stmt->close();

// Start transaction
$conn->begin_transaction();

try {
    // Insert reply
    $stmt = $conn->prepare("INSERT INTO replies (contact_id, reply_text, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("is", $contact_id, $reply_text);
    if (!$stmt->execute()) {
        throw new Exception('Failed to save reply: ' . $stmt->error);
    }
    $stmt->close();

    // Update contact status
    $stmt = $conn->prepare("UPDATE contacts SET status = 'replied' WHERE id = ?");
    $stmt->bind_param("i", $contact_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update contact status: ' . $stmt->error);
    }
    $stmt->close();

    // Send email notification to user
    $subject = "WheelsOnRent: New Reply to Your Message";
    $body = "Dear User,\n\nWe have responded to your message:\n\nReply: {$reply_text}\n\nView it in your profile: https://wheelsonrent.com/profile.php#messages\n\nThank you,\nWheelsOnRent Team";
    if (!mail($user_email, $subject, $body)) {
        file_put_contents('php_errors.log', "Failed to send email to: $user_email\n", FILE_APPEND);
        // Note: Not throwing an exception here to avoid rolling back the transaction
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    file_put_contents('php_errors.log', "Error in save_reply.php: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $t) {
    $conn->rollback();
    file_put_contents('php_errors.log', "Unexpected error in save_reply.php: " . $t->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'An unexpected error occurred']);
}

$conn->close();
?>