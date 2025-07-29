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
    $location = $_POST['location'];
    $trip_start = $_POST['trip-start'];
    $trip_end = $_POST['trip-end'];
    $delivery_pickup = isset($_POST['free-delivery']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO trip_bookings (location, trip_start, trip_end, delivery_pickup) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $location, $trip_start, $trip_end, $delivery_pickup);

    if ($stmt->execute()) {
        header("Location: search-results.php");
        exit();
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <header>
        <div class="logo">
            <h1>WheelsOnRent</h1>
        </div>
        <nav>
            <ul>
                <li><a href="home.php" class="nav-link">Home</a></li>
                <li><a href="home.php#about" class="nav-link">About</a></li>
                <li><a href="home.php#contact" class="nav-link">Contact</a></li>
                <li class="profile-dropdown">
                <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="profile-icon">
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="login.php">Login</a>
                        <a href="logout.php" id="signout" class="signout-link">Sign Out</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>
    <title>Book Now - DriveEasy Luxury Rentals</title>
    <link rel="stylesheet" href="book-style.css">
</head>
<body>
    <div class="container">
        <div class="instructions">
            <p>Experience Hassle-free Vehicle Rentals</p>
            <h1>Book Your Perfect Ride Today</h1>
        </div>

        <section class="booking-section">
            <div id="validation-message"></div>
            
            <form id="booking-form" method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">
                            <i class="fas fa-map-marker-alt"></i> Location
                        </label>
                        <input type="text" id="location" name="location" placeholder="Enter your preferred pickup location" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="trip-start">
                            <i class="fas fa-calendar-alt"></i> Trip Starts
                        </label>
                        <input type="date" id="trip-start" name="trip-start" required>
                    </div>
                    <div class="form-group">
                        <label for="trip-end">
                            <i class="fas fa-calendar-alt"></i> Trip Ends
                        </label>
                        <input type="date" id="trip-end" name="trip-end" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="delivery-note">
                        <div class="checkbox-group">
                            <input type="checkbox" id="free-delivery" name="free-delivery">
                            <label for="free-delivery">
                                <i class="fas fa-truck"></i> Free Doorstep Delivery & Pickup Service
                            </label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="book-btn">
                    <i class="fas fa-search"></i> Find Available Vehicles
                </button>
            </form>
        </section>
    </div>

    <footer>
        <p>© 2025 WheelsOnRent. All rights reserved.</p>
    </footer>

    <script>
        // Set minimum date for date inputs to today
        const dateInputs = document.querySelectorAll('input[type="date"]');
        const today = new Date().toISOString().split('T')[0];
        dateInputs.forEach(input => {
            input.min = today;
        });

        document.getElementById('booking-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const tripStart = document.getElementById('trip-start').value;
            const tripEnd = document.getElementById('trip-end').value;
            const messageElement = document.getElementById('validation-message');

            // Function to show error message
            const showError = (message) => {
                messageElement.textContent = message;
                messageElement.classList.add('show');
                // Scroll to the error message
                messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };

            // Get current date (without time)
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Convert dates to timestamp for accurate comparison
            const startTimestamp = new Date(tripStart).getTime();
            const endTimestamp = new Date(tripEnd).getTime();
            const todayTimestamp = today.getTime();

            // Validate start date is not in the past
            if (startTimestamp < todayTimestamp) {
                showError('Trip Start Date cannot be in the past. Please select today or a future date.');
                return;
            }

            // Check if start and end dates are the same
            if (tripStart === tripEnd) {
                showError('Trip End Date cannot be the same as Start Date. Please select at least one day after.');
                return;
            }

            // Validate end date is after start date
            if (endTimestamp < startTimestamp) {
                showError('Trip End Date must be after the Start Date.');
                return;
            }

            // Clear any existing error message if validation passes
            messageElement.textContent = '';
            messageElement.classList.remove('show');
            
            // If all validations pass, submit the form
            this.submit();
        });
    </script>
</body>
</html>