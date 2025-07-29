<?php
session_start();

// Add database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user profile picture if logged in
$profile_picture = "profile.png"; // Default image
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT profile_picture FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        if (!empty($user['profile_picture'])) {
            $profile_picture = $user['profile_picture'];
        }
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $message = $_POST['message'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    // Validate inputs
    if (empty($username) || empty($email) || empty($phone) || empty($message)) {
        echo "<script>alert('Please fill in all required fields.');</script>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid email format.');</script>";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo "<script>alert('Phone number must be exactly 10 digits.');</script>";
    } else {
        $stmt = $conn->prepare("INSERT INTO contacts (user_id, username, email, phone, message, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("issss", $user_id, $username, $email, $phone, $message);

        if ($stmt->execute()) {
            // Send email notification to admin
            $admin_email = "admin@wheelsonrent.com";
            $subject = "New User Message";
            $body = "A new message from {$username} ({$email}) has been received:\n\n{$message}";
            mail($admin_email, $subject, $body);

            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('success-popup').style.display = 'flex';
                });
            </script>";
        } else {
            echo "<script>alert('Error: " . addslashes($stmt->error) . "');</script>";
        }
        $stmt->close();
    }
}

// Fetch user data for pre-filling form if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, email, phone FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - WheelsOnRent</title>
    <link rel="stylesheet" href="contact-style.css">
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
                <li><a href="home.php" class="nav-link">Home</a></li>
                <li><a href="home.php#about" class="nav-link">About</a></li>
                <li class="profile-dropdown">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-icon">
                    <div class="dropdown-content">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="profile.php">Profile</a>
                            <a href="logout.php" id="signout" class="signout-link">Sign Out</a>
                        <?php else: ?>
                            <a href="login.php">Login</a>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <!-- Contact Page -->
    <section class="contact-page">
        <div class="contact-container">
            <!-- Left: Contact Information -->
            <div class="contact-info">
                <h2>Contact Information</h2>
                <p>Say something to start a live chat!</p>
                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <span>+91 9923 668 188</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span> info@wheelsonrent.com</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Orda, Candolim, Calangute, Goa - 403515</span>
                </div>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <!-- Right: Contact Form -->
            <div class="contact-form">
                <form id="contact-form" method="POST" action="">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="username" placeholder="Enter Your Username" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" required>
                        <span id="username_error" style="color: red; font-size: 0.9rem; display: none;">Only letters and spaces are allowed</span>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="email" placeholder="Enter Your Email" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" id="phone" placeholder="Enter Your Number" maxlength="10" value="<?php echo isset($user['phone']) ? htmlspecialchars($user['phone']) : ''; ?>" required>
                            <span id="phone_error" style="color: red; font-size: 0.9rem; display: none;">Only numbers are allowed</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" id="message" placeholder="Write Your Message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="send-btn">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Success Popup -->
    <div id="success-popup" class="popup-overlay" style="display: none;">
        <div class="popup-content">
            <h2>Thank You! 🎉</h2>
            <p>Thank you for contacting us!</p>
            <p>We will get back to you soon.</p>
            <button id="ok-btn" class="ok-button">OK</button>
        </div>
    </div>

    <style>
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .popup-content {
        background: white;
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        max-width: 400px;
        width: 90%;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .popup-content h2 {
        color: #1a1a2e;
        margin-bottom: 15px;
    }

    .popup-content p {
        color: #4a4a4a;
        margin-bottom: 10px;
    }

    .ok-button {
        background: #1a1a2e;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 5px;
        font-size: 16px;
        cursor: pointer;
        margin-top: 20px;
        transition: background 0.3s ease;
    }

    .ok-button:hover {
        background: #2a2a4e;
    }
    </style>

    <script>
    // Real-time input validation for username
    document.getElementById('username').addEventListener('input', function(e) {
        const input = e.target;
        const value = input.value;
        const lettersOnly = /^[A-Za-z\s]*$/;
        
        if (!lettersOnly.test(value)) {
            input.value = value.replace(/[^A-Za-z\s]/g, '');
            document.getElementById('username_error').style.display = 'block';
            setTimeout(() => {
                document.getElementById('username_error').style.display = 'none';
            }, 2000);
        }
    });

    // Real-time input validation for phone number
    document.getElementById('phone').addEventListener('input', function(e) {
        const input = e.target;
        const value = input.value;
        const digitsOnly = /^[0-9]*$/;
        
        if (!digitsOnly.test(value)) {
            input.value = value.replace(/[^0-9]/g, '');
            document.getElementById('phone_error').style.display = 'block';
            setTimeout(() => {
                document.getElementById('phone_error').style.display = 'none';
            }, 2000);
        }
        
        // Enforce 10-digit limit
        if (input.value.length > 10) {
            input.value = input.value.slice(0, 10);
        }
    });

    // Form submission validation
    document.getElementById('contact-form').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;

        // Basic validation to ensure fields are not empty
        if (!username || !email || !phone) {
            alert('Please fill in all required fields.');
            return;
        }

        // Validate email format
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            alert('Invalid email format.');
            return;
        }

        // Validate phone length (10 digits)
        if (phone.length !== 10) {
            document.getElementById('phone_error').style.display = 'block';
            document.getElementById('phone_error').textContent = 'Phone number must be exactly 10 digits';
            setTimeout(() => {
                document.getElementById('phone_error').style.display = 'none';
                document.getElementById('phone_error').textContent = 'Only numbers are allowed';
            }, 2000);
            return;
        }

        // Submit form using fetch API
        const formData = new FormData(this);
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(() => {
            // Show success popup immediately after successful submission
            const popup = document.querySelector('.popup-overlay');
            popup.style.display = 'flex';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while sending your message. Please try again.');
        });
    });

    // Redirect based on login status when OK is clicked in success popup
    document.getElementById('ok-btn').addEventListener('click', function() {
        <?php if (isset($_SESSION['user_id'])): ?>
            window.location.href = 'profile.php#messages';
        <?php else: ?>
            window.location.href = 'home.php';
        <?php endif; ?>
    });
    </script>
    <footer>
        <p>© 2025 WheelsOnRent. All rights reserved.</p>
    </footer>
    <script>
    // Footer scroll visibility
    let lastScrollPosition = 0;
    const footer = document.querySelector('footer');
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset || document.documentElement.scrollTop;
        
        // Show footer when scrolling down and near bottom of page
        const bottomThreshold = document.documentElement.scrollHeight - window.innerHeight - 100;
        
        if (currentScroll > lastScrollPosition && currentScroll > bottomThreshold) {
            footer.style.bottom = '0';
        } else {
            footer.style.bottom = '-100px';
        }
        
        lastScrollPosition = currentScroll;
    });
    </script>
</body>
</html>
<?php
$conn->close();
?>