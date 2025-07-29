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
    <title>Terms & Conditions - WheelsOnRent</title>
    <link rel="stylesheet" href="terms_style.css">
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

    <!-- Terms & Conditions Section -->
    <section class="terms-section">
        <h1>Terms & Conditions</h1>
        <div class="terms-content">
            <div class="terms-box">
                <div class="terms-item">
                    <h2>Vehicle Condition</h2>
                    <p>The vehicle should be provided in a proper and safe condition. The renter agrees to return the vehicle in the same condition, excluding normal wear and tear.</p>
                </div>
                <div class="terms-item">
                    <h2>Return Policy</h2>
                    <p>The vehicle must be returned on the agreed date and place. If the vehicle is not returned on time, a fee equal to one-third of the daily rate will be charged for each hour of delay.</p>
                </div>
                <div class="terms-item">
                    <h2>Cancellation Policies</h2>
                    <p>User cannot directly cancel the booking. He/she can send a message(through contact) regarding cancellation process. The cancellation process must be done a day prior to delivery of vehicle.</p>
                </div>
                <div class="terms-item">
                    <h2>Insurance and Liability</h2>
                    <p>Refer Insurance and Protection selection for detailed info.</p>
                </div>
                <div class="terms-item">
                    <h2>Fuel Policy</h2>
                    <p>The fuel policy is Full to Full, which means the vehicle must be returned with a full tank.</p>
                </div>
                <div class="terms-item">
                    <h2>Grace Period</h2>
                    <p>A grace period is provided for returning the vehicle, after which penalties will apply for late returns.</p>
                </div>
                <div class="terms-item">
                    <h2>Early Return</h2>
                    <p>If the renter needs to return the vehicle early, they should be aware that refunds are unlikely.</p>
                </div>
                <div class="terms-item">
                    <h2>Driver Information</h2>
                    <p>Renters must be at least 18 years old and hold a valid driver's license. Additional drivers must meet the same requirements.</p>
                </div>
                <div class="terms-item">
                    <h2>Required Documents</h2>
                    <p>Renters must provide a valid driver's license to collect the rental vehicle.</p>
                </div>
                <div class="terms-item">
                    <h2>Taxes</h2>
                    <p>Renters are responsible for paying applicable taxes, including GST, road tax, and local taxes, depending on the rental location.</p>
                </div>
                <div class="terms-item">
                    <h2>Vehicle Usage</h2>
                    <p>The vehicle must not be driven in prohibited areas or used for illegal activities, such as driving the wrong way down a street or smoking in the vehicle.</p>
                </div>
                <div class="terms-item">
                    <h2>Insurance Options</h2>
                    <p>Available insurance options include coverage for accidents, breakdowns, and theft. Renters can select their preferred option during booking.</p>
                </div>
                <div class="terms-item">
                    <h2>Vehicle Damage</h2>
                    <p>If the vehicle is damaged, stolen, or involved in an accident, the renter must immediately notify the police and the rental company.</p>
                </div>
                <div class="terms-item">
                    <h2>Contract Termination</h2>
                    <p>The rental company may terminate the contract early if the renter violates the terms, such as using the vehicle for illegal purposes or failing to maintain its condition.</p>
                </div>
                <div class="terms-item">
                    <h2>Customer Responsibilities</h2>
                    <p>Renters are responsible for maintaining the vehicle's condition, adhering to driving rules, and ensuring the vehicle is used appropriately during the rental period.</p>
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