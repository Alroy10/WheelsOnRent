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
    <title>Insurance & Protection - WheelsOnRent</title>
    <link rel="stylesheet" href="insurance_style.css">
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

    <!-- Insurance & Protection Section -->
    <section class="insurance-section">
        <h1>Insurance & Protection</h1>
        <div class="insurance-content">
            <div class="insurance-box">
                <div class="insurance-item">
                    <h2>Standard Insurance Coverage</h2>
                    <p>All our rental vehicles come with basic insurance that includes:</p>
                    <ul>
                        <li>Third-party liability coverage: Covers damage or injury to others caused by your rental.</li>
                        <li>Theft protection: Covers losses due to vehicle theft (with proper police report and key submission).</li>
                        <li>Fire and accidental damage: Basic coverage for fire damage or unforeseen accidents.</li>
                    </ul>
                </div>
                <div class="insurance-item">
                    <h2>Optional Add-ons</h2>
                    <p>You can enhance your protection by choosing from these optional plans:</p>
                    <ul>
                        <li>Zero-Depreciation Cover: No deduction on vehicle parts in case of damage.</li>
                        <li>Personal Accident Insurance: Covers medical expenses for driver and passengers.</li>
                        <li>Roadside Assistance: 24/7 support for breakdowns, flat tires, and emergencies.</li>
                    </ul>
                </div>
                
                <div class="insurance-item">
                    <h2>Customer Responsibility</h2>
                    <ul>
                        <li>Report any damage or accident immediately.</li>
                        <li>Use the vehicle responsibly and follow traffic laws.</li>
                        <li>Ensure the vehicle is returned in good condition to avoid extra charges.</li>
                    </ul>
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