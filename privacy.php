<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");
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
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - WheelsOnRent</title>
    <link rel="stylesheet" href="privacy_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="logo">
            <h1>WheelsOnRent</h1>
        </div>
        <nav>
            <ul>
                <li><a href="home.php" class="nav-link">Home</a></li>
                <li><a href="home.php#vehicles" class="nav-link">Vehicles</a></li>
                <li><a href="home.php#about" class="nav-link">About</a></li>
                <li><a href="home.php#contact" class="nav-link">Contact</a></li>
                <li><a href="book.php" class="nav-btn">Book Now</a></li>
                <li class="profile-dropdown">
                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-icon">
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="login.php">Login</a>
                        <a href="logout.php" class="signout-link">Sign Out</a>
                    </div>
                </li>
            </ul>
            <div class="menu-toggle">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>

    <!-- Privacy Policy Section -->
    <section class="privacy-section">
        <h1>Privacy Policy</h1>
        <div class="privacy-content">
            <div class="privacy-box">
                <div class="privacy-item">
                    <h2>Collection of Personal Information</h2>
                    <p>We collect personal information such as name, email, phone number, and vehicle preferences when users register, make reservations, or communicate with customer service.</p>
                </div>
                <div class="privacy-item">
                    <h2>Purpose of Data Collection</h2>
                    <p>We collect and use this information to process rental transactions, provide customer support, and improve our services.</p>
                </div>
                <div class="privacy-item">
                    <h2>Data Sharing</h2>
                    <p>Your personal information may be shared with third parties such as payment processors, analytics providers, or legal authorities as required by law or to facilitate our services.</p>
                </div>
                <div class="privacy-item">
                    <h2>Data Security Measures</h2>
                    <p>We implement security measures such as encryption, secure servers, and access controls to protect your personal data from unauthorized access or disclosure.</p>
                </div>
                <div class="privacy-item">
                    <h2>User Rights</h2>
                    <p>You have the right to access, correct, or delete your personal information. You may also opt-out of certain types of communications from us.</p>
                </div>
                <div class="privacy-item">
                    <h2>Cookies and Tracking Technologies</h2>
                    <p>Our website uses cookies and other tracking technologies to enhance user experience. You can manage your cookie preferences through your browser settings.</p>
                </div>
                <div class="privacy-item">
                    <h2>Data Retention</h2>
                    <p>We retain user data for as long as necessary to fulfill the purposes outlined in this policy, unless a longer retention period is required or permitted by law.</p>
                </div>
                <div class="privacy-item">
                    <h2>Children's Privacy</h2>
                    <p>Our services are not directed to children under 13. We do not knowingly collect personal information from children, in compliance with laws such as COPPA in the United States.</p>
                </div>
                <div class="privacy-item">
                    <h2>Changes to the Privacy Policy</h2>
                    <p>We may update this privacy policy periodically. Users will be notified of significant changes via email or a notice on our website.</p>
                </div>
                <div class="privacy-item">
                    <h2>Contact Information</h2>
                    <p>If you have questions or concerns about our privacy practices, please contact us at <a href="mailto:info@wheelsonrent.com">info@wheelsonrent.com</a> or call +91 9923 668 188.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <p>© 2025 WheelsOnRent. All rights reserved.</p>
    </footer>

    <script>
        // Smooth scrolling for in-page links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        // Mobile menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const nav = document.querySelector('nav ul');
        menuToggle.addEventListener('click', () => {
            nav.classList.toggle('show');
        });
    </script>
</body>
</html>