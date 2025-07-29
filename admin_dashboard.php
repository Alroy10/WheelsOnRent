<?php
session_start();

// Suppress PHP errors and log them instead
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

require_once("db_connect.php");

// Check database connection
if ($conn->connect_error) {
    file_put_contents('php_errors.log', "Database connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Fetch pending messages count
$pending_query = "SELECT COUNT(*) as pending_count FROM contacts WHERE status = 'pending'";
$pending_result = $conn->query($pending_query);
$pending_count = $pending_result->fetch_assoc()['pending_count'];

// Fetch messages
$messages_query = "SELECT c.*, r.id AS reply_id, r.reply_text, r.created_at AS reply_created_at 
                  FROM contacts c 
                  LEFT JOIN replies r ON c.id = r.contact_id 
                  ORDER BY c.status = 'pending' DESC, c.created_at DESC";
$messages = $conn->query($messages_query);

// Fetch bookings
$bookings_query = "SELECT *, pickup_date as booking_date FROM bookings ORDER BY pickup_date DESC";
$bookings = $conn->query($bookings_query);

// Fetch vehicles
$vehicles_query = "SELECT * FROM vehicles ORDER BY created_at DESC";
$vehicles = $conn->query($vehicles_query);

// Fetch users
$users_query = "SELECT user_id, username, email, phone, dob, address, license_no, profile_picture, created_at 
                FROM users ORDER BY created_at DESC";
$users = $conn->query($users_query);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        header('Content-Type: application/json');
        switch ($_POST['action']) {
            case 'add_vehicle':
                $vehicle_name = $_POST['vehicle_name'];
                $vehicle_type = $_POST['vehicle_type'];
                $price_per_day = $_POST['price'];
                $seats = $_POST['seats'];
                $fuel_type = $_POST['fuel_type'];
                $transmission = isset($_POST['transmission']) ? $_POST['transmission'] : NULL;

                // Validate vehicle type
                $allowed_types = ['SUV', 'MPV', 'Sedan', 'Hatchback', 'Bike', 'Scooter'];
                if (!in_array($vehicle_type, $allowed_types)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid vehicle type selected.']);
                    exit();
                }

                // Validate seats and fuel type for bikes and scooters
                if (($vehicle_type === 'Bike' || $vehicle_type === 'Scooter') && 
                    ($seats != 2 || $fuel_type !== 'Petrol')) {
                    echo json_encode(['success' => false, 'error' => 'Bikes and Scooters must have 2 seats and Petrol fuel type.']);
                    exit();
                }

                // Validate fuel_type
                if (empty($fuel_type) || !in_array($fuel_type, ['Petrol', 'Diesel'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid fuel type selected.']);
                    exit();
                }

                // Check if vehicle already exists
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM vehicles WHERE name = ? AND type = ? AND seats = ? AND fuel_type = ? AND (transmission = ? OR (transmission IS NULL AND ? IS NULL))");
                $stmt->bind_param("ssssss", $vehicle_name, $vehicle_type, $seats, $fuel_type, $transmission, $transmission);
                $stmt->execute();
                $result = $stmt->get_result();
                $count = $result->fetch_assoc()['count'];
                $stmt->close();

                if ($count > 0) {
                    echo json_encode(['success' => false, 'error' => 'Vehicle already present.']);
                    exit();
                }

                // Handle file upload
                $image_path = '';
                if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == 0) {
                    $upload_dir = 'Uploads/vehicles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $image_name = uniqid() . '_' . basename($_FILES['vehicle_image']['name']);
                    $image_path = $upload_dir . $image_name;
                    if (!move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $image_path)) {
                        $image_path = '';
                    }
                } else {
                    echo json_encode(['success' => false, 'error' => 'Please choose an image for the vehicle.']);
                    exit();
                }

                $stmt = $conn->prepare("INSERT INTO vehicles (name, type, price_per_day, image_path, seats, fuel_type, transmission) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdssss", $vehicle_name, $vehicle_type, $price_per_day, $image_path, $seats, $fuel_type, $transmission);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle added successfully.']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error adding vehicle: ' . $conn->error]);
                }
                $stmt->close();
                exit();

            case 'update_vehicle':
                $id = $_POST['id'];
                $vehicle_name = $_POST['vehicle_name'];
                $vehicle_type = $_POST['vehicle_type'];
                $price_per_day = $_POST['price'];
                $seats = $_POST['seats'];
                $fuel_type = $_POST['fuel_type'];
                $transmission = isset($_POST['transmission']) ? $_POST['transmission'] : NULL;
                $delete_image = isset($_POST['delete_image']) && $_POST['delete_image'] === '1';

                // Validate vehicle type
                $allowed_types = ['SUV', 'MPV', 'Sedan', 'Hatchback', 'Bike', 'Scooter'];
                if (!in_array($vehicle_type, $allowed_types)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid vehicle type selected.']);
                    exit();
                }

                // Validate seats and fuel type for bikes and scooters
                if (($vehicle_type === 'Bike' || $vehicle_type === 'Scooter') && 
                    ($seats != 2 || $fuel_type !== 'Petrol')) {
                    echo json_encode(['success' => false, 'error' => 'Bikes and Scooters must have 2 seats and Petrol fuel type.']);
                    exit();
                }

                // Validate fuel_type
                if (empty($fuel_type) || !in_array($fuel_type, ['Petrol', 'Diesel'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid fuel type selected.']);
                    exit();
                }

                // Handle file upload
                $image_path = $_POST['existing_image'];
                if ($delete_image && empty($_FILES['vehicle_image']['name'])) {
                    if (!empty($image_path) && file_exists($image_path)) {
                        @unlink($image_path);
                    }
                    $image_path = '';
                } elseif (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] == 0) {
                    $upload_dir = 'Uploads/vehicles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $image_name = uniqid() . '_' . basename($_FILES['vehicle_image']['name']);
                    $new_image_path = $upload_dir . $image_name;
                    if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $new_image_path)) {
                        if (!empty($image_path) && file_exists($image_path)) {
                            @unlink($image_path);
                        }
                        $image_path = $new_image_path;
                    }
                }

                if (empty($image_path)) {
                    echo json_encode(['success' => false, 'error' => 'Please choose an image for the vehicle.']);
                    exit();
                }

                $stmt = $conn->prepare("UPDATE vehicles SET name=?, type=?, price_per_day=?, image_path=?, seats=?, fuel_type=?, transmission=? WHERE id=?");
                $stmt->bind_param("ssdssssi", $vehicle_name, $vehicle_type, $price_per_day, $image_path, $seats, $fuel_type, $transmission, $id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Vehicle updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Error updating vehicle: ' . $conn->error]);
                }
                $stmt->close();
                exit();

            case 'delete_vehicle':
                if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid or missing id']);
                    exit();
                }

                $id = (int)$_POST['id'];
                
                $conn->begin_transaction();
                
                try {
                    $stmt = $conn->prepare("SELECT name FROM vehicles WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $vehicle_name = $row['name'];
                    } else {
                        throw new Exception("Vehicle not found");
                    }
                    $stmt->close();

                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE vehicle_name = ? AND dropoff_date >= CURDATE()");
                    $stmt->bind_param("s", $vehicle_name);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->fetch_assoc()['count'] > 0) {
                        throw new Exception("Cannot delete vehicle with active bookings");
                    }
                    $stmt->close();

                    $stmt = $conn->prepare("SELECT image_path FROM vehicles WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $image_path = '';
                    if ($row = $result->fetch_assoc()) {
                        $image_path = $row['image_path'];
                    }
                    $stmt->close();

                    $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows === 0) {
                        throw new Exception("No vehicle found with the given ID");
                    }
                    $stmt->close();

                    if (!empty($image_path) && file_exists($image_path)) {
                        @unlink($image_path);
                    }

                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'Vehicle deleted successfully.']);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                exit();

            case 'delete_reply':
                // Handle both reply deletion and admin message deletion
                $conn->begin_transaction();
                
                try {
                    if (isset($_POST['reply_id']) && is_numeric($_POST['reply_id'])) {
                        // Deleting a reply
                        $reply_id = (int)$_POST['reply_id'];
                        
                        $stmt = $conn->prepare("SELECT contact_id FROM replies WHERE id = ?");
                        $stmt->bind_param("i", $reply_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            $contact_id = $row['contact_id'];
                        } else {
                            throw new Exception("Reply not found");
                        }
                        $stmt->close();

                        $stmt = $conn->prepare("DELETE FROM replies WHERE id = ?");
                        $stmt->bind_param("i", $reply_id);
                        $stmt->execute();
                        
                        if ($stmt->affected_rows === 0) {
                            throw new Exception("No reply found with the given ID");
                        }
                        $stmt->close();

                        // Update contact status if no replies remain
                        $stmt = $conn->prepare("SELECT COUNT(*) as reply_count FROM replies WHERE contact_id = ?");
                        $stmt->bind_param("i", $contact_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $reply_count = $result->fetch_assoc()['reply_count'];
                        $stmt->close();

                        if ($reply_count == 0) {
                            $stmt = $conn->prepare("UPDATE contacts SET status = 'pending' WHERE id = ?");
                            $stmt->bind_param("i", $contact_id);
                            $stmt->execute();
                            $stmt->close();
                        }

                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Reply deleted successfully.']);
                    } elseif (isset($_POST['contact_id']) && is_numeric($_POST['contact_id'])) {
                        // Deleting an admin message
                        $contact_id = (int)$_POST['contact_id'];
                        
                        $stmt = $conn->prepare("SELECT is_admin_message FROM contacts WHERE id = ?");
                        $stmt->bind_param("i", $contact_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($row = $result->fetch_assoc()) {
                            if ($row['is_admin_message'] != 1) {
                                throw new Exception("Cannot delete non-admin message");
                            }
                        } else {
                            throw new Exception("Message not found");
                        }
                        $stmt->close();

                        $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
                        $stmt->bind_param("i", $contact_id);
                        $stmt->execute();
                        
                        if ($stmt->affected_rows === 0) {
                            throw new Exception("No message found with the given ID");
                        }
                        $stmt->close();

                        $conn->commit();
                        echo json_encode(['success' => true, 'message' => 'Admin message deleted successfully.']);
                    } else {
                        throw new Exception("Invalid or missing ID");
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                exit();

            case 'delete_user':
                if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid or missing user ID']);
                    exit();
                }

                $user_id = (int)$_POST['user_id'];
                
                $conn->begin_transaction();
                
                try {
                    // Check for active bookings
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE email = (SELECT email FROM users WHERE user_id = ?) AND dropoff_date >= CURDATE()");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->fetch_assoc()['count'] > 0) {
                        throw new Exception("Cannot delete user with active bookings");
                    }
                    $stmt->close();

                    // Delete user-related data
                    $stmt = $conn->prepare("DELETE FROM contacts WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();

                    // Delete user
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows === 0) {
                        throw new Exception("No user found with the given ID");
                    }
                    $stmt->close();

                    $conn->commit();
                    echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
                } catch (Exception $e) {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WheelsOnRent</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f4f4;
        }

        .completed-status, .active-status {
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: 500;
            display: inline-block;
        }

        .completed-status {
            background-color: #e0e0e0;
            color: #616161;
        }

        .active-status {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .pending {
            background-color: #ffebee;
        }

        .container {
            padding: 20px;
        }
        .header {
            background: #1a1a2e;
            color: white;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
        }
        .logout-btn {
            background: #ff4444;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .tabs {
            display: flex;
            margin: 20px 0;
            gap: 10px;
        }
        .tab-btn {
            padding: 10px 20px;
            background: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .tab-btn.active {
            background: #1a1a2e;
            color: white;
        }
        .tab-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
        .action-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
        }
        .edit-btn {
            background: #4CAF50;
            color: white;
        }
        .delete-btn {
            background: #f44336;
            color: white;
        }
        .reply-btn {
            background: #0288d1;
            color: white;
        }
        .add-vehicle-btn, .send-message-btn {
            background: #1a1a2e;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            overflow: auto;
        }
        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 500px;
            margin: 50px auto;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-content h2 {
            font-size: 2rem;
            color: #1a1a2e;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-size: 1rem;
            color: #1a1a2e;
            font-weight: 500;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #1a1a2e;
            outline: none;
            box-shadow: 0 0 5px rgba(26, 26, 46, 0.5);
        }
        .modal-content button[type="submit"] {
            background: #1a1a2e;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .modal-content button[type="submit"]:hover {
            background: #2e2e4e;
        }
        .modal-content button[type="button"] {
            background: #ff4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-left: 10px;
            font-size: 1rem;
            font-weight: 600;
        }
        .image-input-container {
            position: relative;
            display: flex;
            align-items: center;
        }
        .clear-image-btn {
            background: #ff4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 30px;
            font-size: 16px;
            cursor: pointer;
            position: absolute;
            right: 10px;
            text-align: center;
            line-height: 24px;
        }
        .clear-image-btn:hover {
            background: #cc0000;
        }
        .error-message {
            color: #ff4444;
            margin-bottom: 15px;
            font-size: 14px;
        }
        #add-validation-message, #edit-validation-message, #reply-validation-message, #send-message-validation-message, #result-message {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            border-left: 4px solid #d32f2f;
            display: none;
        }
        #add-validation-message.show, #edit-validation-message.show, #reply-validation-message.show, #send-message-validation-message.show, #result-message.show {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        #result-message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .user-profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>WheelsOnRent </h1>
        <button class="logout-btn" onclick="window.location.href='admin_logout.php'">Logout</button>
    </div>

    <div class="container">
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('vehicles', event)">Vehicles</button>
            <button class="tab-btn" onclick="showTab('bookings', event)">Bookings</button>
            <button class="tab-btn" onclick="showTab('messages', event)">Messages</button>
            <button class="tab-btn" onclick="showTab('users', event)">Users</button>
        </div>

        <!-- Vehicles Tab -->
        <div id="vehicles" class="tab-content">
            <button class="add-vehicle-btn" onclick="showAddVehicleModal()">Add New Vehicle</button>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Vehicle Name</th>
                        <th>Type</th>
                        <th>Seats</th>
                        <th>Fuel Type Allocator
                        <th>Transmission</th>
                        <th>Price/Day</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($vehicle['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($vehicle['name']); ?>"
                                 style="width: 100px; height: auto;">
                        </td>
                        <td><?php echo htmlspecialchars($vehicle['name']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['type']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['seats']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['fuel_type']); ?></td>
                        <td><?php echo htmlspecialchars($vehicle['transmission'] ?? 'N/A'); ?></td>
                        <td>₹<?php echo htmlspecialchars($vehicle['price_per_day']); ?></td>
                        <td>
                            <button class="action-btn edit-btn" 
                                    onclick='editVehicle(<?php echo json_encode($vehicle); ?>)'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="action-btn delete-btn" 
                                    onclick="showConfirmDeleteVehicleModal(<?php echo $vehicle['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Bookings Tab -->
        <div id="bookings" class="tab-content" style="display: none;">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Vehicle</th>
                        <th>Pickup Date</th>
                        <th>Dropoff Date</th>
                        <th>Amount</th>
                        <th>Payment Type</th>
                        <th>Delivery Required</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($booking = $bookings->fetch_assoc()): 
                        $dropoff_date = new DateTime($booking['dropoff_date']);
                        $current_date = new DateTime();
                        $status = $dropoff_date < $current_date ? 'Completed' : 'Active';
                        $status_class = $status === 'Completed' ? 'completed-status' : 'active-status';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['email']); ?></td>
                        <td><?php echo htmlspecialchars($booking['phone']); ?></td>
                        <td><?php echo htmlspecialchars($booking['pickup_location']); ?></td>
                        <td><?php echo htmlspecialchars($booking['vehicle_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['pickup_date']); ?></td>
                        <td><?php echo htmlspecialchars($booking['dropoff_date']); ?></td>
                        <td>₹<?php echo htmlspecialchars($booking['total_amount']); ?></td>
                        <td><?php echo htmlspecialchars($booking['payment_method'] ?? 'Not Specified'); ?></td>
                        <td><?php echo $booking['delivery_required'] ? 'Yes' : 'No'; ?></td>
                        <td><span class="<?php echo $status_class; ?>"><?php echo $status; ?></span></td>
                        <td>
                            <?php if($status === 'Active'): ?>
                            <button class="action-btn delete-btn" 
                                    onclick="showCancelBookingModal('<?php echo htmlspecialchars(addslashes($booking['email'])); ?>', '<?php echo htmlspecialchars(addslashes($booking['vehicle_name'])); ?>', '<?php echo htmlspecialchars(addslashes($booking['pickup_date'])); ?>')">
                                <i class="fas fa-trash"></i> Cancel
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Messages Tab -->
        <div id="messages" class="tab-content" style="display: none;">
            <h3>Messages (<?php echo $pending_count; ?> Pending)</h3>
            <button class="send-message-btn" onclick="showSendMessageModal()">Send Message</button>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Message</th>
                        <th>Reply</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $default_reply = "Thank you for choosing WheelsOnRent! To ensure a smooth and secure rental experience, please remember to carry your valid driving license at the time of vehicle pickup. Safe and happy riding!";
                    while ($message = $messages->fetch_assoc()):
                        $is_default_reply = ($message['reply_text'] === $default_reply);
                    ?>
                    <tr class="<?php echo $message['status'] === 'pending' ? 'pending' : ''; ?>">
                        <td><?php echo htmlspecialchars($message['username']); ?></td>
                        <td><?php echo htmlspecialchars($message['email']); ?></td>
                        <td><?php echo htmlspecialchars($message['message']); ?></td>
                        <td><?php echo htmlspecialchars($message['reply_text'] ?? 'No reply'); ?></td>
                        <td><?php echo htmlspecialchars($message['created_at']); ?></td>
                        <td><?php echo $is_default_reply ? 'Default' : htmlspecialchars($message['status'] === 'pending' ? 'Pending' : 'Replied'); ?></td>
                        <td>
                            <?php if (!$is_default_reply && $message['status'] === 'pending' && $message['is_admin_message'] == 0): ?>
                            <button class="action-btn reply-btn" 
                                    onclick="showReplyModal(<?php echo $message['id']; ?>, '<?php echo htmlspecialchars(addslashes($message['username'])); ?>', '<?php echo htmlspecialchars(addslashes($message['message'])); ?>')">
                                <i class="fas fa-reply"></i> Reply
                            </button>
                            <?php endif; ?>
                            <?php if ($message['reply_text'] && !$is_default_reply): ?>
                            <button class="action-btn delete-btn" 
                                    onclick="showConfirmDeleteReplyModal(<?php echo $message['reply_id']; ?>)">
                                <i class="fas fa-trash"></i> Delete Reply
                            </button>
                            <?php endif; ?>
                            <?php if ($message['is_admin_message'] == 1): ?>
                            <button class="action-btn delete-btn" 
                                    onclick="showConfirmDeleteAdminMessageModal(<?php echo $message['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Users Tab -->
        <div id="users" class="tab-content" style="display: none;">
            <h3>Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>Profile Picture</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date of Birth</th>
                        <th>Address</th>
                        <th>License No</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'profile.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                 class="user-profile-pic">
                        </td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></td>
                        <td><?php echo htmlspecialchars($user['dob'] ?? 'Not provided'); ?></td>
                        <td><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></td>
                        <td><?php echo htmlspecialchars($user['license_no'] ?? 'Not provided'); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <button class="action-btn delete-btn" 
                                    onclick="showConfirmDeleteUserModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Add Vehicle Modal -->
        <div id="addVehicleModal" class="modal">
            <div class="modal-content">
                <h2>Add New Vehicle</h2>
                <div id="add-validation-message"></div>
                <form id="addVehicleForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_vehicle">
                    <div class="form-group">
                        <label>Vehicle Name</label>
                        <input type="text" name="vehicle_name" id="add_vehicle_name" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="vehicle_type" id="vehicle_type" required>
                            <option value="SUV">SUV</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="MPV">MPV</option>
                            <option value="Sedan">Sedan</option>
                            <option value="Bike">Bike</option>
                            <option value="Scooter">Scooter</option>
                        </select>
                    </div>
                    <div class="form-group" id="seats_group">
                        <label>Seats</label>
                        <select name="seats" id="seats" required>
                            <option value="4">4 Seats</option>
                            <option value="5">5 Seats</option>
                            <option value="7">7 Seats</option>
                        </select>
                    </div>
                    <div class="form-group" id="fuel_type_group">
                        <label>Fuel Type</label>
                        <select name="fuel_type" id="fuel_type" required>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                        </select>
                    </div>
                    <div class="form-group" id="transmission_group">
                        <label>Transmission</label>
                        <select name="transmission" id="transmission" required>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price per Day</label>
                        <input type="number" name="price" id="add_price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Image</label>
                        <div class="image-input-container">
                            <input type="file" name="vehicle_image" id="add_vehicle_image" accept="image/*" required>
                            <button type="button" class="clear-image-btn" id="clear_add_image" onclick="clearImage('add_vehicle_image')">X</button>
                        </div>
                    </div>
                    <button type="submit">Add Vehicle</button>
                    <button type="button" onclick="hideAddVehicleModal()">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Edit Vehicle Modal -->
        <div id="editVehicleModal" class="modal">
            <div class="modal-content">
                <h2>Edit Vehicle</h2>
                <div id="edit-validation-message"></div>
                <form id="editVehicleForm" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="update_vehicle">
                    <input type="hidden" name="id" id="edit_vehicle_id">
                    <input type="hidden" name="existing_image" id="edit_existing_image">
                    <input type="hidden" name="delete_image" id="delete_image" value="0">
                    <div class="form-group">
                        <label>Vehicle Name</label>
                        <input type="text" name="vehicle_name" id="edit_vehicle_name" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="vehicle_type" id="edit_vehicle_type" required>
                            <option value="SUV">SUV</option>
                            <option value="Hatchback">Hatchback</option>
                            <option value="MPV">MPV</option>
                            <option value="Sedan">Sedan</option>
                            <option value="Bike">Bike</option>
                            <option value="Scooter">Scooter</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_seats_group">
                        <label>Seats</label>
                        <select name="seats" id="edit_seats" required>
                            <option value="4">4 Seats</option>
                            <option value="5">5 Seats</option>
                            <option value="7">7 Seats</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_fuel_type_group">
                        <label>Fuel Type</label>
                        <select name="fuel_type" id="edit_fuel_type" required>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                        </select>
                    </div>
                    <div class="form-group" id="edit_transmission_group">
                        <label>Transmission</label>
                        <select name="transmission" id="edit_transmission" required>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Price per Day</label>
                        <input type="number" name="price" id="edit_price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Vehicle Image</label>
                        <div class="image-input-container">
                            <input type="file" name="vehicle_image" id="edit_vehicle_image" accept="image/*">
                            <button type="button" class="clear-image-btn" id="clear_edit_image" onclick="clearEditImage()">X</button>
                        </div>
                        <img id="current_image" src="" style="max-width: 100px; display: none; margin-top: 10px;">
                    </div>
                    <button type="submit">Update Vehicle</button>
                    <button type="button" onclick="hideEditVehicleModal()">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Reply Modal -->
        <div id="replyModal" class="modal">
            <div class="modal-content">
                <h2>Reply to Message</h2>
                <div id="reply-validation-message"></div>
                <form id="replyForm">
                    <div class="form-group">
                        <label>User</label>
                        <input type="text" id="reply_username" readonly>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea id="reply_message" readonly></textarea>
                    </div>
                    <div class="form-group">
                        <label>Reply</label>
                        <textarea id="reply_text" required></textarea>
                    </div>
                    <button type="submit">Send Reply</button>
                    <button type="button" onclick="hideReplyModal()">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Send Message Modal -->
        <div id="sendMessageModal" class="modal">
            <div class="modal-content">
                <h2>Send Message</h2>
                <div id="send-message-validation-message"></div>
                <form id="sendMessageForm">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" id="send_username" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="send_email" required>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea id="send_message" required></textarea>
                    </div>
                    <button type="submit">Send Message</button>
                    <button type="button" onclick="hideSendMessageModal()">Cancel</button>
                </form>
            </div>
        </div>

        <!-- Cancel Booking Modal -->
        <div id="cancelBookingModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Cancel Booking</h2>
                <div class="form-group">
                    <p id="cancelBookingMessage"></p>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="confirmCancelBooking()" style="background: #f44336;">Cancel Booking</button>
                    <button type="button" onclick="hideCancelBookingModal()">Close</button>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Vehicle Modal -->
        <div id="confirmDeleteVehicleModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Delete Vehicle</h2>
                <div class="form-group">
                    <p>Are you sure you want to delete this vehicle? This action cannot be undone.</p>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="confirmDeleteVehicle()" style="background: #f44336;">Delete Vehicle</button>
                    <button type="button" onclick="hideConfirmDeleteVehicleModal()">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Reply Modal -->
        <div id="confirmDeleteReplyModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Delete Reply</h2>
                <div class="form-group">
                    <p>Are you sure you want to delete this reply? This action cannot be undone.</p>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="confirmDeleteReply('reply')" style="background: #f44336;">Delete Reply</button>
                    <button type="button" onclick="hideConfirmDeleteReplyModal()">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Confirm Delete Admin Message Modal -->
        <div id="confirmDeleteAdminMessageModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Delete Message</h2>
                <div class="form-group">
                    <p>Are you sure you want to delete this admin message? This action cannot be undone.</p>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="confirmDeleteReply('admin_message')" style="background: #f44336;">Delete Message</button>
                    <button type="button" onclick="hideConfirmDeleteAdminMessageModal()">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Confirm Delete User Modal -->
        <div id="confirmDeleteUserModal" class="modal">
            <div class="modal-content">
                <h2>Confirm Delete User</h2>
                <div class="form-group">
                    <p id="deleteUserMessage"></p>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <button type="button" onclick="confirmDeleteUser()" style="background: #f44336;">Delete User</button>
                    <button type="button" onclick="hideConfirmDeleteUserModal()">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Vehicle Action Result Modal -->
        <div id="vehicleActionResultModal" class="modal">
            <div class="modal-content">
                <h2>Action Result</h2>
                <div id="result-message"></div>
                <div style="text-align: center;">
                    <button type="button" onclick="hideVehicleActionResultModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabId, event) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.style.display = 'none');
            document.getElementById(tabId).style.display = 'block';
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function showAddVehicleModal() {
            const modal = document.getElementById('addVehicleModal');
            modal.style.display = 'block';
            document.getElementById('addVehicleForm').reset();
            document.getElementById('add-validation-message').innerHTML = '';
            document.getElementById('add-validation-message').classList.remove('show');
            updateAddVehicleForm();
        }

        function hideAddVehicleModal() {
            document.getElementById('addVehicleModal').style.display = 'none';
        }

        function showEditVehicleModal() {
            const modal = document.getElementById('editVehicleModal');
            modal.style.display = 'block';
            document.getElementById('edit-validation-message').innerHTML = '';
            document.getElementById('edit-validation-message').classList.remove('show');
        }

        function hideEditVehicleModal() {
            document.getElementById('editVehicleModal').style.display = 'none';
        }

        function showReplyModal(contactId, username, message) {
            const modal = document.getElementById('replyModal');
            document.getElementById('reply_username').value = username;
            document.getElementById('reply_message').value = message;
            document.getElementById('reply_text').value = '';
            document.getElementById('reply-validation-message').innerHTML = '';
            document.getElementById('reply-validation-message').classList.remove('show');
            modal.dataset.contactId = contactId;
            modal.style.display = 'block';
        }

        function hideReplyModal() {
            document.getElementById('replyModal').style.display = 'none';
        }

        function showSendMessageModal() {
            const modal = document.getElementById('sendMessageModal');
            document.getElementById('sendMessageForm').reset();
            document.getElementById('send-message-validation-message').innerHTML = '';
            document.getElementById('send-message-validation-message').classList.remove('show');
            modal.style.display = 'block';
        }

        function hideSendMessageModal() {
            document.getElementById('sendMessageModal').style.display = 'none';
        }

        function showCancelBookingModal(email, vehicleName, pickupDate) {
            const modal = document.getElementById('cancelBookingModal');
            const messageElement = document.getElementById('cancelBookingMessage');
            messageElement.innerHTML = `Are you sure you want to cancel the booking for <strong>${vehicleName}</strong> on <strong>${pickupDate}</strong>?`;
            modal.dataset.email = email;
            modal.dataset.vehicleName = vehicleName;
            modal.dataset.pickupDate = pickupDate;
            modal.style.display = 'block';
        }

        function hideCancelBookingModal() {
            document.getElementById('cancelBookingModal').style.display = 'none';
        }

        function confirmCancelBooking() {
            const modal = document.getElementById('cancelBookingModal');
            const email = modal.dataset.email;
            const vehicleName = modal.dataset.vehicleName;
            const pickupDate = modal.dataset.pickupDate;

            fetch('cancel_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, vehicle_name: vehicleName, pickup_date: pickupDate })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hideCancelBookingModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        }

        function showConfirmDeleteVehicleModal(id) {
            const modal = document.getElementById('confirmDeleteVehicleModal');
            modal.dataset.vehicleId = id;
            modal.style.display = 'block';
        }

        function hideConfirmDeleteVehicleModal() {
            document.getElementById('confirmDeleteVehicleModal').style.display = 'none';
        }

        function confirmDeleteVehicle() {
            const modal = document.getElementById('confirmDeleteVehicleModal');
            const id = modal.dataset.vehicleId;

            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_vehicle&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                hideConfirmDeleteVehicleModal();
                showVehicleActionResultModal(data.success, data.message || data.error);
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                hideConfirmDeleteVehicleModal();
                showVehicleActionResultModal(false, 'Error: ' + error.message);
            });
        }

        function showConfirmDeleteReplyModal(replyId) {
            const modal = document.getElementById('confirmDeleteReplyModal');
            modal.dataset.replyId = replyId;
            modal.style.display = 'block';
        }

        function hideConfirmDeleteReplyModal() {
            document.getElementById('confirmDeleteReplyModal').style.display = 'none';
        }

        function showConfirmDeleteAdminMessageModal(contactId) {
            const modal = document.getElementById('confirmDeleteAdminMessageModal');
            modal.dataset.contactId = contactId;
            modal.style.display = 'block';
        }

        function hideConfirmDeleteAdminMessageModal() {
            document.getElementById('confirmDeleteAdminMessageModal').style.display = 'none';
        }

        function confirmDeleteReply(type) {
            const replyModal = document.getElementById('confirmDeleteReplyModal');
            const adminMessageModal = document.getElementById('confirmDeleteAdminMessageModal');
            let id, body;

            if (type === 'reply') {
                id = replyModal.dataset.replyId;
                body = `action=delete_reply&reply_id=${id}`;
            } else if (type === 'admin_message') {
                id = adminMessageModal.dataset.contactId;
                body = `action=delete_reply&contact_id=${id}`;
            } else {
                showVehicleActionResultModal(false, 'Invalid deletion type');
                return;
            }

            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                if (type === 'reply') {
                    hideConfirmDeleteReplyModal();
                } else {
                    hideConfirmDeleteAdminMessageModal();
                }
                showVehicleActionResultModal(data.success, data.message || data.error);
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                if (type === 'reply') {
                    hideConfirmDeleteReplyModal();
                } else {
                    hideConfirmDeleteAdminMessageModal();
                }
                showVehicleActionResultModal(false, 'Error: ' + error.message);
            });
        }

        function showConfirmDeleteUserModal(userId, username) {
            const modal = document.getElementById('confirmDeleteUserModal');
            const messageElement = document.getElementById('deleteUserMessage');
            messageElement.innerHTML = `Are you sure you want to delete the user <strong>${username}</strong>? This action cannot be undone.`;
            modal.dataset.userId = userId;
            modal.style.display = 'block';
        }

        function hideConfirmDeleteUserModal() {
            document.getElementById('confirmDeleteUserModal').style.display = 'none';
        }

        function confirmDeleteUser() {
            const modal = document.getElementById('confirmDeleteUserModal');
            const userId = modal.dataset.userId;

            fetch('admin_dashboard.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete_user&user_id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                hideConfirmDeleteUserModal();
                showVehicleActionResultModal(data.success, data.message || data.error);
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                hideConfirmDeleteUserModal();
                showVehicleActionResultModal(false, 'Error: ' + error.message);
            });
        }

        function showVehicleActionResultModal(success, message) {
            const modal = document.getElementById('vehicleActionResultModal');
            const messageElement = document.getElementById('result-message');
            messageElement.innerHTML = message;
            messageElement.classList.remove('success');
            if (success) {
                messageElement.classList.add('success');
            }
            messageElement.classList.add('show');
            modal.style.display = 'block';
        }

        function hideVehicleActionResultModal() {
            document.getElementById('vehicleActionResultModal').style.display = 'none';
        }

        function clearImage(inputId) {
            document.getElementById(inputId).value = '';
            document.getElementById(`clear_${inputId}`).style.display = 'none';
        }

        function clearEditImage() {
            document.getElementById('edit_vehicle_image').value = '';
            document.getElementById('clear_edit_image').style.display = 'none';
            document.getElementById('delete_image').value = '1';
            document.getElementById('current_image').style.display = 'none';
        }

        function updateAddVehicleForm() {
            const vehicleType = document.getElementById('vehicle_type').value;
            const seatsGroup = document.getElementById('seats_group');
            const fuelTypeGroup = document.getElementById('fuel_type_group');
            const transmissionGroup = document.getElementById('transmission_group');
            const seatsSelect = document.getElementById('seats');
            const fuelTypeSelect = document.getElementById('fuel_type');
            const transmissionSelect = document.getElementById('transmission');

            if (vehicleType === 'Bike' || vehicleType === 'Scooter') {
                seatsSelect.innerHTML = '<option value="2">2 Seats</option>';
                seatsSelect.value = '2';
                seatsGroup.style.display = 'none';
                fuelTypeSelect.value = 'Petrol';
                fuelTypeGroup.style.display = 'none';
                transmissionGroup.style.display = 'none';
                transmissionSelect.required = false;
            } else {
                seatsSelect.innerHTML = `
                    <option value="4">4 Seats</option>
                    <option value="5">5 Seats</option>
                    <option value="7">7 Seats</option>
                `;
                seatsGroup.style.display = 'block';
                fuelTypeGroup.style.display = 'block';
                transmissionGroup.style.display = 'block';
                transmissionSelect.required = true;
            }
        }

        function updateEditVehicleForm() {
            const vehicleType = document.getElementById('edit_vehicle_type').value;
            const seatsGroup = document.getElementById('edit_seats_group');
            const fuelTypeGroup = document.getElementById('edit_fuel_type_group');
            const transmissionGroup = document.getElementById('edit_transmission_group');
            const seatsSelect = document.getElementById('edit_seats');
            const fuelTypeSelect = document.getElementById('edit_fuel_type');
            const transmissionSelect = document.getElementById('edit_transmission');

            if (vehicleType === 'Bike' || vehicleType === 'Scooter') {
                seatsSelect.innerHTML = '<option value="2">2 Seats</option>';
                seatsSelect.value = '2';
                seatsGroup.style.display = 'none';
                fuelTypeSelect.value = 'Petrol';
                fuelTypeGroup.style.display = 'none';
                transmissionGroup.style.display = 'none';
                transmissionSelect.required = false;
            } else {
                seatsSelect.innerHTML = `
                    <option value="4">4 Seats</option>
                    <option value="5">5 Seats</option>
                    <option value="7">7 Seats</option>
                `;
                seatsGroup.style.display = 'block';
                fuelTypeGroup.style.display = 'block';
                transmissionGroup.style.display = 'block';
                transmissionSelect.required = true;
            }
        }

        document.getElementById('vehicle_type').addEventListener('change', updateAddVehicleForm);
        document.getElementById('edit_vehicle_type').addEventListener('change', updateEditVehicleForm);

        function editVehicle(vehicle) {
            document.getElementById('edit_vehicle_id').value = vehicle.id;
            document.getElementById('edit_vehicle_name').value = vehicle.name;
            document.getElementById('edit_vehicle_type').value = vehicle.type;
            document.getElementById('edit_seats').value = vehicle.seats;
            document.getElementById('edit_fuel_type').value = vehicle.fuel_type;
            document.getElementById('edit_transmission').value = vehicle.transmission || 'Automatic';
            document.getElementById('edit_price').value = vehicle.price_per_day;
            document.getElementById('edit_existing_image').value = vehicle.image_path;
            document.getElementById('current_image').src = vehicle.image_path;
            document.getElementById('current_image').style.display = vehicle.image_path ? 'block' : 'none';
            document.getElementById('delete_image').value = '0';
            document.getElementById('edit_vehicle_image').value = '';
            document.getElementById('clear_edit_image').style.display = vehicle.image_path ? 'block' : 'none';
            updateEditVehicleForm();
            showEditVehicleModal();
        }

        document.getElementById('addVehicleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const validationMessage = document.getElementById('add-validation-message');

            fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hideAddVehicleModal();
                    showVehicleActionResultModal(true, data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    validationMessage.innerHTML = data.error;
                    validationMessage.classList.add('show');
                }
            })
            .catch(error => {
                validationMessage.innerHTML = 'Error: ' + error.message;
                validationMessage.classList.add('show');
            });
        });

        document.getElementById('editVehicleForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const validationMessage = document.getElementById('edit-validation-message');

            fetch('admin_dashboard.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    hideEditVehicleModal();
                    showVehicleActionResultModal(true, data.message);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    validationMessage.innerHTML = data.error;
                    validationMessage.classList.add('show');
                }
            })
            .catch(error => {
                validationMessage.innerHTML = 'Error: ' + error.message;
                validationMessage.classList.add('show');
            });
        });

        document.getElementById('add_vehicle_image').addEventListener('change', function() {
            document.getElementById('clear_add_image').style.display = this.files.length ? 'block' : 'none';
        });

        document.getElementById('edit_vehicle_image').addEventListener('change', function() {
            document.getElementById('clear_edit_image').style.display = this.files.length ? 'block' : 'none';
            document.getElementById('delete_image').value = this.files.length ? '0' : document.getElementById('delete_image').value;
        });

        document.getElementById('replyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const contactId = document.getElementById('replyModal').dataset.contactId;
            const replyText = document.getElementById('reply_text').value.trim();
            const validationMessage = document.getElementById('reply-validation-message');

            if (!replyText) {
                validationMessage.innerHTML = 'Reply text is required.';
                validationMessage.classList.add('show');
                return;
            }
            
            if (/[<>'";]/.test(replyText)) {
                validationMessage.innerHTML = 'Reply contains invalid characters.';
                validationMessage.classList.add('show');
                return;
            }

            fetch('save_reply.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ contact_id: contactId, reply_text: replyText })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        hideReplyModal();
                        location.reload();
                    } else {
                        validationMessage.innerHTML = data.message || 'Failed to send reply.';
                        validationMessage.classList.add('show');
                    }
                } catch (e) {
                    console.error('JSON Parse Error:', e, 'Raw Response:', text);
                    validationMessage.innerHTML = 'Invalid server response. Please try again.';
                    validationMessage.classList.add('show');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                validationMessage.innerHTML = 'An error occurred while sending reply: ' + error.message;
                validationMessage.classList.add('show');
            });
        });

        document.getElementById('sendMessageForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const username = document.getElementById('send_username').value.trim();
            const email = document.getElementById('send_email').value.trim();
            const message = document.getElementById('send_message').value.trim();
            const validationMessage = document.getElementById('send-message-validation-message');

            if (!username || !email || !message) {
                validationMessage.innerHTML = 'All fields are required.';
                validationMessage.classList.add('show');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                validationMessage.innerHTML = 'Invalid email format.';
                validationMessage.classList.add('show');
                return;
            }

            if (/[<>'";]/.test(message)) {
                validationMessage.innerHTML = 'Message contains invalid characters.';
                validationMessage.classList.add('show');
                return;
            }

            fetch('send_admin_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, email, message })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    hideSendMessageModal();
                    showVehicleActionResultModal(true, 'Message sent successfully.');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    validationMessage.innerHTML = data.message || 'Failed to send message.';
                    validationMessage.classList.add('show');
                }
            })
            .catch(error => {
                validationMessage.innerHTML = 'An error occurred while sending message: ' + error.message;
                validationMessage.classList.add('show');
            });
        });

        // Initialize form on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateAddVehicleForm();
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>