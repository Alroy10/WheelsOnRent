<?php
session_start();

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'profile_errors.log');

// Redirect to login if not authenticated
if (!isset($_SESSION['email']) || empty($_SESSION['email'])) {
    error_log("profile.php - Redirecting to login.php due to missing session email");
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Fetch user details
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT user_id, username, email, phone, dob, address, license_no, profile_picture, created_at FROM users WHERE email = ?");
if (!$stmt) {
    error_log("Prepare failed for user query: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    error_log("Execute failed for user query: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    error_log("User not found for email: $email");
    header("Location: login.php?error=user_not_found");
    exit();
}
$user_id = $user['user_id'];
$_SESSION['user_id'] = $user_id; // Ensure user_id is set for update_profile.php

// Fetch user bookings
$stmt = $conn->prepare("SELECT * FROM bookings WHERE email = ? ORDER BY booking_date DESC");
if (!$stmt) {
    error_log("Prepare failed for bookings query: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$stmt->bind_param("s", $email);
if (!$stmt->execute()) {
    error_log("Execute failed for bookings query: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch user messages (user-initiated and admin-initiated for this user)
$sql = "SELECT c.id, c.message, c.created_at, c.is_admin_message, c.username, r.reply_text, r.created_at AS reply_created_at 
        FROM contacts c 
        LEFT JOIN replies r ON c.id = r.contact_id 
        WHERE (c.user_id = ? AND c.is_admin_message = 0) OR (c.user_id = ? AND c.is_admin_message = 1) 
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log("Prepare failed for messages query: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$stmt->bind_param("ii", $user_id, $user_id);
if (!$stmt->execute()) {
    error_log("Execute failed for messages query: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - WheelsOnRent</title>
    <link rel="stylesheet" href="profile-style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="logo">
            <h1>WheelsOnRent</h1>
        </div>
        <nav>
            <ul>
                <li><a href="home.php">Home</a></li>
                <li><a href="home.php#vehicles">Vehicles</a></li>
                <li><a href="home.php#about">About</a></li>
                <li><a href="home.php#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <div class="profile-container">
        <aside class="sidebar">
            <div class="profile-header">
                <div class="profile-picture">
                    <img src="<?php echo htmlspecialchars($user['profile_picture'] ?? 'profile.png'); ?>" alt="Profile Picture" id="profile-img">
                    <label for="profile-upload" class="upload-btn"><i class="fas fa-camera"></i></label>
                    <button class="remove-btn" id="remove-profile-pic"><i class="fas fa-trash"></i></button>
                    <input type="file" id="profile-upload" accept="image/*" style="display: none;">
                </div>
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p>User ID: <?php echo htmlspecialchars($user['user_id']); ?></p>
            </div>
            <nav class="sidebar-nav">
                <a href="#basic-details" class="active"><i class="fas fa-user"></i> Basic Details</a>
                <a href="#booking-history"><i class="fas fa-history"></i> Booking History</a>
                <a href="#messages"><i class="fas fa-envelope"></i> Messages</a>
                <a href="logout.php" class="signout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
            </nav>
        </aside>

        <main class="main-content">
            <section id="basic-details" class="card">
                <h2>Basic Details</h2>
                <div class="details-grid">
                    <div class="detail-item">
                        <label>Full Name</label>
                        <p><?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Email</label>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($user['phone'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Date of Birth</label>
                        <p><?php echo htmlspecialchars($user['dob'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Address</label>
                        <p><?php echo htmlspecialchars($user['address'] ?? 'Not provided'); ?></p>
                    </div>
                    <div class="detail-item">
                        <label>Driving License</label>
                        <p><?php echo htmlspecialchars($user['license_no'] ?? 'Not provided'); ?></p>
                    </div>
                </div>
                <button class="edit-btn">Edit Profile</button>
            </section>

            <section id="booking-history" class="card">
                <h2>Booking History</h2>
                <div class="booking-list">
                    <?php if (empty($bookings)): ?>
                        <p class="no-bookings">No bookings found.</p>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <div class="booking-item">
                                <div class="booking-header">
                                    <h3><?php echo htmlspecialchars($booking['vehicle_name']); ?></h3>
                                    <span class="booking-status <?php echo (new DateTime($booking['dropoff_date']) < new DateTime()) ? 'completed' : 'upcoming'; ?>">
                                        <?php echo (new DateTime($booking['dropoff_date']) < new DateTime()) ? 'Completed' : 'Upcoming'; ?>
                                    </span>
                                </div>
                                <div class="booking-details">
                                    <p><i class="fas fa-car"></i> <?php echo htmlspecialchars($booking['vehicle_type']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['pickup_location']); ?></p>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($booking['pickup_date'])) . ' - ' . date('d M Y', strtotime($booking['dropoff_date'])); ?></p>
                                    <p><i class="fas fa-rupee-sign"></i> <?php echo number_format($booking['total_amount'], 2); ?></p>
                                    <p><i class="fas fa-credit-card"></i> <?php echo htmlspecialchars(ucfirst($booking['payment_method'])); ?></p>
                                    <p><i class="fas fa-truck"></i> Delivery Required: <?php echo $booking['delivery_required'] ? 'Yes' : 'No'; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section id="messages" class="card">
                <h2>Messages</h2>
                <a href="contact.php" class="send-message-btn">Send Message</a>
                <div class="message-list">
                    <?php if (empty($messages)): ?>
                        <p class="no-messages">No messages found.</p>
                    <?php else: ?>
                        <?php 
                        $current_message_id = null;
                        foreach ($messages as $message):
                            if ($current_message_id !== $message['id']):
                                if ($current_message_id !== null): ?>
                                    </div>
                                <?php endif;
                                $current_message_id = $message['id']; ?>
                                <div class="message-thread">
                                    <div class="message-item <?php echo $message['is_admin_message'] ? 'admin-message' : 'user-message'; ?>">
                                        <div class="message-header">
                                            <h3><?php echo $message['is_admin_message'] ? 'Admin' : 'You'; ?></h3>
                                            <span class="message-date"><?php echo date('d M Y, H:i', strtotime($message['created_at'])); ?></span>
                                        </div>
                                        <p><?php echo htmlspecialchars($message['message']); ?></p>
                                    </div>
                            <?php endif; ?>
                            <?php if (!empty($message['reply_text'])): ?>
                                <div class="message-item admin-message">
                                    <div class="message-header">
                                        <h3>Admin</h3>
                                        <span class="message-date"><?php echo date('d M Y, H:i', strtotime($message['reply_created_at'])); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($message['reply_text']); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($current_message_id !== null): ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal" id="edit-modal">
        <div class="modal-content">
            <span class="close-btn">×</span>
            <h2>Edit Profile</h2>
            <form id="edit-form">
                <div class="form-group">
                    <label for="username">Full Name</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" maxlength="10" pattern="[0-9]{10}" placeholder="Enter 10-digit phone number">
                    <span id="phone-error" class="error-message">Only 10 digits are allowed</span>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>">
                    <span id="dob-error" class="error-message">User must be at least 18 years old</span>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="license_no">Driving License</label>
                    <input type="text" id="license_no" name="license_no" value="<?php echo htmlspecialchars($user['license_no'] ?? ''); ?>">
                    <span id="license-error" class="error-message">Only letters and numbers are allowed</span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <button type="button" class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup Modal for Messages -->
    <div class="popup" id="popup-message">
        <div class="popup-content">
            <span class="popup-close-btn">×</span>
            <p id="popup-text"></p>
            <button class="popup-ok-btn">OK</button>
        </div>
    </div>

    <footer>
        <p>© 2025 WheelsOnRent. All rights reserved.</p>
    </footer>

    <script>
        // Ensure popup is hidden on page load
        document.addEventListener('DOMContentLoaded', () => {
            const popup = document.getElementById('popup-message');
            popup.style.display = 'none';
        });

        // Popup handling
        function showPopup(message, isSuccess = true) {
            const popup = document.getElementById('popup-message');
            const popupText = document.getElementById('popup-text');
            const popupContent = document.querySelector('.popup-content');
            
            if (!message || typeof message !== 'string' || message.trim() === '') {
                return;
            }

            popupText.textContent = message.trim();
            popupContent.classList.remove('success', 'error');
            popupContent.classList.add(isSuccess ? 'success' : 'error');
            popup.style.display = 'flex';
        }

        function closePopup() {
            const popup = document.getElementById('popup-message');
            popup.style.display = 'none';
            const popupText = document.getElementById('popup-text');
            popupText.textContent = '';
        }

        document.querySelector('.popup-close-btn').addEventListener('click', closePopup);
        document.querySelector('.popup-ok-btn').addEventListener('click', closePopup);

        // Modal handling
        const editBtn = document.querySelector('.edit-btn');
        const modal = document.getElementById('edit-modal');
        const closeBtn = document.querySelector('.close-btn');
        const cancelBtn = document.querySelector('.cancel-btn');

        editBtn.addEventListener('click', () => {
            modal.style.display = 'flex';
        });

        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        cancelBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Real-time input validation
        const phoneInput = document.getElementById('phone');
        const phoneError = document.getElementById('phone-error');
        phoneInput.addEventListener('input', () => {
            const value = phoneInput.value;
            const digitsOnly = /^[0-9]*$/;
            
            if (!digitsOnly.test(value)) {
                phoneInput.value = value.replace(/[^0-9]/g, '');
                phoneError.style.display = 'block';
                setTimeout(() => phoneError.style.display = 'none', 2000);
            }
            if (value.length > 10) {
                phoneInput.value = value.slice(0, 10);
                phoneError.style.display = 'block';
                setTimeout(() => phoneError.style.display = 'none', 2000);
            }
        });

        const dobInput = document.getElementById('dob');
        const dobError = document.getElementById('dob-error');
        dobInput.addEventListener('change', () => {
            const dobValue = dobInput.value;
            if (dobValue) {
                const dob = new Date(dobValue);
                const today = new Date();
                const age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                const dayDiff = today.getDate() - dob.getDate();

                let adjustedAge = age;
                if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                    adjustedAge--;
                }

                if (adjustedAge < 18) {
                    dobError.style.display = 'block';
                } else {
                    dobError.style.display = 'none';
                }
            } else {
                dobError.style.display = 'none';
            }
        });

        const licenseInput = document.getElementById('license_no');
        const licenseError = document.getElementById('license-error');
        licenseInput.addEventListener('input', () => {
            const value = licenseInput.value;
            const alphanumericOnly = /^[A-Za-z0-9]*$/;
            
            if (!alphanumericOnly.test(value)) {
                licenseInput.value = value.replace(/[^A-Za-z0-9]/g, '');
                licenseError.style.display = 'block';
                setTimeout(() => licenseError.style.display = 'none', 2000);
            }
        });

        // Profile picture upload
        const profileUpload = document.getElementById('profile-upload');
        const profileImg = document.getElementById('profile-img');

        profileUpload.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                if (file.size > 1 * 1024 * 1024) {
                    showPopup('Image size must be less than 1MB.', false);
                    return;
                }
                const reader = new FileReader();
                reader.onload = (e) => {
                    const base64Image = e.target.result;
                    profileImg.src = base64Image;

                    fetch('update_profile.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'update_profile_picture', profile_picture: base64Image })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showPopup('Profile picture uploaded successfully.', true);
                        } else {
                            showPopup(data.message || 'Failed to update profile picture.', false);
                        }
                    })
                    .catch(error => {
                        showPopup('An error occurred while updating profile picture.', false);
                    });
                };
                reader.readAsDataURL(file);
            } else {
                showPopup('Please select a valid image file (e.g., JPG, PNG).', false);
            }
        });

        // Profile picture removal
        document.getElementById('remove-profile-pic').addEventListener('click', function() {
            const popup = document.getElementById('popup-message');
            const popupText = document.getElementById('popup-text');
            const popupContent = document.querySelector('.popup-content');
            
            popupText.textContent = 'Are you sure you want to remove your profile picture?';
            popupContent.classList.remove('success', 'error');
            popup.style.display = 'flex';
            
            const popupBtns = document.createElement('div');
            popupBtns.style.display = 'flex';
            popupBtns.style.gap = '10px';
            popupBtns.style.justifyContent = 'center';
            
            const yesBtn = document.createElement('button');
            yesBtn.textContent = 'Yes';
            yesBtn.className = 'popup-ok-btn';
            
            const noBtn = document.createElement('button');
            noBtn.textContent = 'No';
            noBtn.className = 'popup-ok-btn';
            noBtn.style.backgroundColor = '#666';
            
            popupBtns.appendChild(yesBtn);
            popupBtns.appendChild(noBtn);
            
            const existingBtn = document.querySelector('.popup-ok-btn');
            existingBtn.replaceWith(popupBtns);
            
            noBtn.addEventListener('click', function() {
                closePopup();
                popupBtns.replaceWith(existingBtn);
            });
            
            yesBtn.addEventListener('click', function() {
                fetch('update_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'remove_profile_picture' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        profileImg.src = 'profile.png';
                        popupText.textContent = 'Profile picture removed successfully';
                        popupContent.classList.add('success');
                        popupBtns.replaceWith(existingBtn);
                    } else {
                        popupText.textContent = data.message || 'Error removing profile picture';
                        popupContent.classList.add('error');
                        popupBtns.replaceWith(existingBtn);
                    }
                })
                .catch(error => {
                    popupText.textContent = 'Error removing profile picture: ' + error.message;
                    popupContent.classList.add('error');
                    popupBtns.replaceWith(existingBtn);
                });
            });
        });

        // Edit form submission
        const editForm = document.getElementById('edit-form');
        let isSubmitting = false;

        editForm.addEventListener('submit', (e) => {
            e.preventDefault();

            if (isSubmitting) {
                return;
            }

            isSubmitting = true;

            const phoneValue = phoneInput.value;
            if (phoneValue && !/^[0-9]{10}$/.test(phoneValue)) {
                showPopup('Please enter a valid 10-digit phone number.', false);
                phoneError.style.display = 'block';
                setTimeout(() => phoneError.style.display = 'none', 2000);
                isSubmitting = false;
                return;
            }

            const dobValue = dobInput.value;
            if (dobValue) {
                const dob = new Date(dobValue);
                const today = new Date();
                if (isNaN(dob.getTime())) {
                    showPopup('Please enter a valid date of birth.', false);
                    isSubmitting = false;
                    return;
                }
                if (dob > today) {
                    showPopup('Date of birth cannot be in the future.', false);
                    isSubmitting = false;
                    return;
                }
                const age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                const dayDiff = today.getDate() - dob.getDate();
                let adjustedAge = age;
                if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
                    adjustedAge--;
                }
                if (adjustedAge < 18) {
                    showPopup('User must be at least 18 years old.', false);
                    dobError.style.display = 'block';
                    isSubmitting = false;
                    return;
                }
            }

            const licenseValue = licenseInput.value;
            if (licenseValue && !/^[A-Za-z0-9]*$/.test(licenseValue)) {
                showPopup('Driving license can only contain letters and numbers.', false);
                licenseError.style.display = 'block';
                setTimeout(() => licenseError.style.display = 'none', 2000);
                isSubmitting = false;
                return;
            }

            const formData = {
                action: 'update_profile',
                phone: phoneValue || null,
                dob: dobValue || null,
                address: document.getElementById('address').value || null,
                license_no: licenseValue || null
            };

            fetch('update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showPopup(data.message || 'Profile updated successfully.', true);
                    setTimeout(() => {
                        closePopup();
                        window.location.replace(window.location.pathname);
                    }, 2000);
                } else {
                    showPopup(data.message || 'Failed to update profile.', false);
                }
            })
            .catch(error => {
                showPopup('An error occurred. Please try again.', false);
            })
            .finally(() => {
                isSubmitting = false;
            });
        });

        // Smooth scrolling for sidebar navigation
        document.querySelectorAll('.sidebar-nav a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.sidebar-nav a').forEach(a => a.classList.remove('active'));
                this.classList.add('active');
                document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
            });
        });
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>