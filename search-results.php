<?php
session_start();

// Database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the latest booking details
$sql = "SELECT location, trip_start, trip_end, delivery_pickup FROM trip_bookings ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
$bookingDetails = $result->fetch_assoc();

// Get current user's email
$userEmail = $_SESSION['email'] ?? null;

// Fetch vehicles from the database
$vehicles_query = "SELECT * FROM vehicles";
$vehicles = $conn->query($vehicles_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - WheelsOnRent</title>
    <link rel="stylesheet" href="search-results.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        .vehicle-card.booked {
            position: relative;
            opacity: 0.6;
        }
        .vehicle-card.booked::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        .vehicle-card.booked .book-btn {
            pointer-events: none;
            background: #ccc;
            cursor: not-allowed;
        }
        .vehicle-card.booked .availability-badge {
            display: none;
        }
    </style>
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
                <li><a href="home.php#contact" class="nav-link">Contact</a></li>
                <li class="profile-dropdown">
                    <img src="profile.png" alt="Profile" class="profile-icon">
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <a href="login.php">Login</a>
                        <a href="logout.php" id="signout" class="signout-link">Sign Out</a>
                    </div>
                </li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="main-content">
            <section class="search-filters">
                <div class="filter-container">
                    <h3>Your Search Details</h3>
                    <div id="search-details" class="search-summary">
                        <div class="search-detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <label>Location</label>
                                <span id="location-text"><?php echo htmlspecialchars($bookingDetails['location'] ?? 'Not specified'); ?></span>
                            </div>
                        </div>
                        <div class="search-detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <label>From</label>
                                <span id="start-date-text"><?php echo htmlspecialchars($bookingDetails['trip_start'] ?? 'Not specified'); ?></span>
                            </div>
                        </div>
                        <div class="search-detail-item">
                            <i class="fas fa-calendar-check"></i>
                            <div>
                                <label>To</label>
                                <span id="end-date-text"><?php echo htmlspecialchars($bookingDetails['trip_end'] ?? 'Not specified'); ?></span>
                            </div>
                        </div>
                        <div class="search-detail-item">
                            <i class="fas fa-truck"></i>
                            <div>
                                <label>Delivery</label>
                                <span id="delivery-text"><?php echo ($bookingDetails['delivery_pickup'] ?? 0) ? 'Yes' : 'No'; ?></span>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="filter-section">
                        <h3><i class="fas fa-filter"></i> Filter Results</h3>
                        <div class="filter-group">
                            <label>Vehicle Type</label>
                            <select id="vehicle-type" class="custom-select">
                                <option value="All Vehicles">All Vehicles</option>
                                <option value="Cars">Cars</option>
                                <option value="Bikes">Bikes</option>
                                <option value="Scooters">Scooters</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Price Range</label>
                            <select id="price-range" class="custom-select">
                                <option value="All Prices">All Prices</option>
                                <option value="Under ₹1000">Under ₹1000</option>
                                <option value="₹1000 - ₹2000">₹1000 - ₹2000</option>
                                <option value="Above ₹2000">Above ₹2000</option>
                            </select>
                        </div>
                        <div class="sort-options">
                            <label>Sort by:</label>
                            <select id="sort-by" class="custom-select">
                                <option value="Price: Low to High">Price: Low to High</option>
                                <option value="Price: High to Low">Price: High to Low</option>
                            </select>
                        </div>
                        <button id="apply-filter-btn" class="apply-filter-btn">Apply Filters</button>
                    </div>
                </div>
            </section>

            <section class="search-results">
                <div class="results-header">
                    <h2>Available Vehicles</h2>
                </div>
                
                <div class="results-container" id="results-container">
                    <?php while ($vehicle = $vehicles->fetch_assoc()):
                        // Check if the vehicle is booked by the user for the selected dates
                        $isBooked = false;
                        if ($userEmail && isset($bookingDetails['trip_start']) && isset($bookingDetails['trip_end'])) {
                            $stmt = $conn->prepare("
                                SELECT COUNT(*) as booking_count 
                                FROM bookings 
                                WHERE vehicle_name = ? 
                                AND email = ? 
                                AND (
                                    (pickup_date <= ? AND dropoff_date >= ?) 
                                    OR (pickup_date >= ? AND pickup_date <= ?)
                                    OR (dropoff_date >= ? AND dropoff_date <= ?)
                                )
                            ");
                            $stmt->bind_param(
                                "ssssssss",
                                $vehicle['name'],
                                $userEmail,
                                $bookingDetails['trip_end'],
                                $bookingDetails['trip_start'],
                                $bookingDetails['trip_start'],
                                $bookingDetails['trip_end'],
                                $bookingDetails['trip_start'],
                                $bookingDetails['trip_end']
                            );
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $row = $result->fetch_assoc();
                            $isBooked = $row['booking_count'] > 0;
                            $stmt->close();
                        }
                    ?>
                    <div class="vehicle-card <?php echo $isBooked ? 'booked' : ''; ?>" 
                         data-type="<?php echo htmlspecialchars($vehicle['type']); ?>" 
                         data-price="<?php echo htmlspecialchars($vehicle['price_per_day']); ?>">
                        <div class="vehicle-image">
                            <img src="<?php echo htmlspecialchars($vehicle['image_path']); ?>" alt="<?php echo htmlspecialchars($vehicle['name']); ?>">
                            <?php if (!$isBooked): ?>
                                <span class="availability-badge">Available Now</span>
                            <?php endif; ?>
                        </div>
                        <div class="vehicle-details">
                            <div class="vehicle-header">
                                <h3><?php echo htmlspecialchars($vehicle['name']); ?></h3>
                                <span class="vehicle-type"><?php echo htmlspecialchars($vehicle['type']); ?></span>
                            </div>
                            <div class="features">
                                <span><i class="fas fa-user"></i> <?php echo ($vehicle['type'] == 'Bike' || $vehicle['type'] == 'Scooter') ? '2 Seats' : '5 Seats'; ?></span>
                                <span><i class="fas fa-gas-pump"></i> <?php echo htmlspecialchars($vehicle['fuel_type']); ?></span>
                                <?php if ($vehicle['type'] != 'Bike' && $vehicle['type'] != 'Scooter'): ?>
                                    <span><i class="fas fa-cog"></i> <?php echo htmlspecialchars($vehicle['transmission']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="price-section">
                                <div class="price">
                                    <span class="amount">₹<?php echo htmlspecialchars($vehicle['price_per_day']); ?></span>
                                    <span class="period">/day</span>
                                </div>
                                <?php if (isset($_SESSION['user_id']) && !$isBooked): ?>
                                    <button class="book-btn" onclick="handleBooking(<?php echo $vehicle['id']; ?>, '<?php echo addslashes($vehicle['name']); ?>', '<?php echo addslashes($vehicle['type']); ?>', <?php echo $vehicle['price_per_day']; ?>, '<?php echo addslashes($vehicle['image_path']); ?>')">Book Now</button>
                                <?php else: ?>
                                    <button class="book-btn" onclick="showLoginPrompt()">Book Now</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>© 2025 WheelsOnRent. All rights reserved.</p>
    </footer>

    <script>
        // Function to format date
        function formatDate(dateStr) {
            if (!dateStr) return 'Not specified';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        // Initialize filter when page loads
        window.onload = function() {
            initializeFilters(); 
        };

        // Filter and sort functionality
        function initializeFilters() {
            const vehicleTypeSelect = document.getElementById('vehicle-type');
            const priceRangeSelect = document.getElementById('price-range');
            const sortBySelect = document.getElementById('sort-by');
            const applyFilterBtn = document.getElementById('apply-filter-btn');
            const resultsContainer = document.getElementById('results-container');

            // Store the original list of vehicle cards
            const originalVehicleCards = Array.from(document.querySelectorAll('.vehicle-card'));

            function applyFilters() {
                const selectedType = vehicleTypeSelect.value;
                const selectedPriceRange = priceRangeSelect.value;
                const selectedSort = sortBySelect.value;

                // Start with the original list of vehicle cards
                let vehicleCards = [...originalVehicleCards];

                // Step 1: Filter by vehicle type
                const carTypes = ['Hatchback', 'SUV', 'MPV', 'Sedan'];
                vehicleCards = vehicleCards.filter(card => {
                    const vehicleType = card.getAttribute('data-type');
                    if (selectedType === 'All Vehicles') return true;
                    if (selectedType === 'Cars' && carTypes.includes(vehicleType)) return true;
                    if (selectedType === 'Bikes' && vehicleType === 'Bike') return true;
                    if (selectedType === 'Scooters' && vehicleType === 'Scooter') return true;
                    return false;
                });

                // Step 2: Filter by price range
                vehicleCards = vehicleCards.filter(card => {
                    const price = parseFloat(card.getAttribute('data-price'));
                    if (selectedPriceRange === 'All Prices') return true;
                    if (selectedPriceRange === 'Under ₹1000' && price < 1000) return true;
                    if (selectedPriceRange === '₹1000 - ₹2000' && price >= 1000 && price <= 2000) return true;
                    if (selectedPriceRange === 'Above ₹2000' && price > 2000) return true;
                    return false;
                });

                // Step 3: Sort by price
                vehicleCards.sort((a, b) => {
                    const priceA = parseFloat(a.getAttribute('data-price'));
                    const priceB = parseFloat(b.getAttribute('data-price'));
                    if (selectedSort === 'Price: Low to High') {
                        return priceA - priceB;
                    } else {
                        return priceB - priceA;
                    }
                });

                // Step 4: Update the DOM
                // Clear the current results
                resultsContainer.innerHTML = '';

                // Append the filtered and sorted cards
                if (vehicleCards.length === 0) {
                    resultsContainer.innerHTML = '<p>No vehicles match your criteria.</p>';
                } else {
                    vehicleCards.forEach(card => {
                        resultsContainer.appendChild(card);
                    });
                }
            }

            // Add event listener to the Apply Filters button
            applyFilterBtn.addEventListener('click', applyFilters);

            // Apply filters initially to show all vehicles
            applyFilters();
        }

        function handleBooking(vehicleId, vehicleName, vehicleType, vehiclePrice, vehicleImage) {
            window.location.href = `payment.php?id=${vehicleId}&name=${encodeURIComponent(vehicleName)}&type=${encodeURIComponent(vehicleType)}&price=${vehiclePrice}&image=${encodeURIComponent(vehicleImage)}`;
        }

        function showLoginPrompt() {
            document.getElementById('login-prompt').style.display = 'flex';
        }

        function closeLoginPrompt() {
            document.getElementById('login-prompt').style.display = 'none';
        }

        // Add login prompt popup
        document.body.insertAdjacentHTML('beforeend', `
            <div id="login-prompt" class="popup-overlay" style="display: none;">
                <div class="popup-content">
                    <h3>Please Sign In</h3>
                    <p>You need to be logged in to book a vehicle.</p>
                    <div class="popup-buttons">
                        <button onclick="window.location.href='login.php'" class="login-btn">Sign In</button>
                        <button onclick="closeLoginPrompt()" class="cancel-btn">Cancel</button>
                    </div>
                </div>
            </div>
        `);
    </script>
</body>
</html>
<?php
$conn->close();
?>