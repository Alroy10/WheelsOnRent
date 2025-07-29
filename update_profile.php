<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost", "root", "", "wheelsonrent");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'update_profile') {
    $phone = $data['phone'] ?? null;
    $dob = $data['dob'] ?? null;
    $address = $data['address'] ?? null;
    $license_no = $data['license_no'] ?? null;

    // Prepare the update query
    $fields = [];
    $params = [];
    $types = '';

    if ($phone !== null) {
        $fields[] = 'phone = ?';
        $params[] = $phone;
        $types .= 's';
    }
    if ($dob !== null) {
        $fields[] = 'dob = ?';
        $params[] = $dob;
        $types .= 's';
    }
    if ($address !== null) {
        $fields[] = 'address = ?';
        $params[] = $address;
        $types .= 's';
    }
    if ($license_no !== null) {
        $fields[] = 'license_no = ?';
        $params[] = $license_no;
        $types .= 's';
    }

    if (empty($fields)) {
        echo json_encode(['success' => true, 'message' => 'No changes to update']);
        exit();
    }

    $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";
    $params[] = $user_id;
    $types .= 'i';

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit();
    }
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }
} elseif ($action === 'update_profile_picture') {
    $profile_picture = $data['profile_picture'] ?? null;

    if (empty($profile_picture)) {
        echo json_encode(['success' => false, 'message' => 'No image provided']);
        exit();
    }

    // Validate base64 image
    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $profile_picture)) {
        echo json_encode(['success' => false, 'message' => 'Invalid image format']);
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
    $stmt->bind_param("si", $profile_picture, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile picture']);
    }
} elseif ($action === 'remove_profile_picture') {
    $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Profile picture removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile picture']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$stmt->close();
$conn->close();
?>