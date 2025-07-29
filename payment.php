<?php
session_start();

// Debug: Log session details
error_log("payment.php - Session ID: " . session_id());
error_log("payment.php - Session email: " . (isset($_SESSION['email']) ? $_SESSION['email'] : 'not set'));

// Redirect to login if user is not authenticated
if (!isset($_SESSION['email']) || empty($_SESSION['email'])) {
    error_log("payment.php - Redirecting to login.php due to missing session email");
    header("Location: login.php");
    exit();
}

$userEmail = $_SESSION['email'];

// Database connection
$conn = new mysqli("localhost", "root", "", "wheelsonrent");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user profile details to validate required fields
$required_fields_complete = true;
$missing_fields = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT username, email, phone, dob, address, license_no, profile_picture FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($user = $result->fetch_assoc()) {
        // Check required fields (profile_picture is optional)
        if (empty($user['username'])) $missing_fields[] = 'Full Name';
        if (empty($user['email'])) $missing_fields[] = 'Email';
        if (empty($user['phone'])) $missing_fields[] = 'Phone Number';
        if (empty($user['dob'])) $missing_fields[] = 'Date of Birth';
        if (empty($user['address'])) $missing_fields[] = 'Address';
        if (empty($user['license_no'])) $missing_fields[] = 'Driving License';
        $required_fields_complete = empty($missing_fields);
        // Set profile picture
        $profile_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : "profile.png";
    } else {
        $required_fields_complete = false;
        $missing_fields[] = 'User profile not found';
    }
    $stmt->close();
} else {
    $required_fields_complete = false;
    $missing_fields[] = 'User ID not set';
    $profile_picture = "profile.png";
}

// Get the latest booking details
$sql = "SELECT location, trip_start, trip_end, delivery_pickup FROM trip_bookings ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);
$bookingDetails = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - WheelsOnRent</title>
    <link rel="stylesheet" href="payment-style.css">
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

<main>
    <div class="payment-container">
        <section class="user-details">
            <h2>Your Booking Details</h2>
            <form id="booking-form">
                <div class="form-group">
                    <label for="full-name">Full Name</label>
                    <input type="text" id="full-name" name="full-name" placeholder="Enter your full name" required>
                    <div id="full-name-error-message" style="color: red; margin-bottom: 10px; display: none;">
                        Full name must contain only letters and spaces
                    </div>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="Enter your phone number" pattern="[0-9]{10}" required>
                    <div id="phone-error-message" style="color: red; margin-bottom: 10px; display: none;">
                        Phone number must be exactly 10 digits
                    </div>
                </div>
                <div class="form-group">
                    <label for="location">Pickup Location</label>
                    <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($bookingDetails['location']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="pickup-date">Pickup Date</label>
                    <input type="date" id="pickup-date" name="pickup-date" value="<?php echo htmlspecialchars($bookingDetails['trip_start']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="dropoff-date">Drop-off Date</label>
                    <input type="date" id="dropoff-date" name="dropoff-date" value="<?php echo htmlspecialchars($bookingDetails['trip_end']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="delivery">Delivery Required</label>
                    <input type="text" id="delivery" value="<?php echo $bookingDetails['delivery_pickup'] ? 'Yes' : 'No'; ?>" readonly>
                </div>
            </form>
        </section>

        <section class="vehicle-summary">
            <h2>Vehicle Summary</h2>
            <div class="vehicle-info">
                <img id="vehicle-image" src="" alt="Vehicle Image">
                <div class="details">
                    <h3 id="vehicle-name"></h3>
                    <p><strong>Type:</strong> <span id="vehicle-type"></span></p>
                    <p><strong>Price:</strong> ₹<span id="vehicle-price"></span>/day</p>
                    <p><strong>Total Days:</strong> <span id="total-days"></span></p>
                    <p><strong>Total Amount:</strong> ₹<span id="total-amount"></span></p>
                </div>
            </div>
        </section>

        <section class="payment-methods">
            <h2>Payment Methods</h2>
            <div id="card-error-message" style="color: red; margin-bottom: 10px; display: none;">
                Please enter a valid 16-digit card number
            </div>
            <div id="card-name-error-message" style="color: red; margin-bottom: 10px; display: none;">
                Name on card must contain only letters and spaces
            </div>
            <div id="expiry-error-message" style="color: red; margin-bottom: 10px; display: none;">
                Please enter a valid month (01-12) or card has expired
            </div>
            <div id="cvv-error-message" style="color: red; margin-bottom: 10px; display: none;">
                Please enter a valid 3-digit CVV
            </div>
            <div class="payment-options">
                <div class="payment-option">
                    <input type="radio" id="credit-card" name="payment-method" value="credit-card" checked>
                    <label for="credit-card">
                        <i class="fas fa-credit-card"></i> Credit/Debit Card
                    </label>
                </div>
                <div class="payment-option">
                    <input type="radio" id="upi" name="payment-method" value="upi">
                    <label for="upi">
                        <i class="fas fa-mobile-alt"></i> UPI
                    </label>
                </div>
            </div>
            <div id="payment-details">
                <div id="credit-card-details" class="payment-details-form">
                    <div class="form-group">
                        <label for="card-number">Card Number</label>
                        <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    </div>
                    <div class="form-group">
                        <label for="card-name">Name on Card</label>
                        <input type="text" id="card-name" placeholder="John Doe" required>
                    </div>
                    <div class="form-group inline-group">
                        <div>
                            <label for="expiry-date">Expiry Date</label>
                            <input type="text" id="expiry-date" placeholder="MM/YY" maxlength="5" required>
                        </div>
                        <div>
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" placeholder="123" maxlength="3" required>
                        </div>
                    </div>
                </div>
                <div id="upi-details" class="payment-details-form" style="display: none;">
                    <div class="form-group">
                        <label for="upi-id">UPI ID</label>
                        <input type="text" id="upi-id" placeholder="example@upi" required>
                    </div>
                </div>
            </div>
            <button id="pay-now-btn" class="pay-btn">Pay Now</button>
        </section>
    </div>
</main>

<footer>
    <p>© 2025 WheelsOnRent. All rights reserved.</p>
</footer>

<div id="success-popup" class="popup-overlay" style="display: none;">
    <div class="popup-content">
        <h2>🎉 Booking Confirmed!</h2>
        <p>Your ride has been booked successfully.</p>
        <p>Thanks for choosing WheelsOnRent.</p>
        <p>Stay Safe, Drive Safe!</p>
        <button id="ok-btn" class="ok-button">OK</button>
    </div>
</div>

<script>
window.onload = function() {
    // Add full name validation (letters and spaces only)
    const fullNameInput = document.getElementById('full-name');
    const fullNameErrorMessage = document.getElementById('full-name-error-message');
    fullNameInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        if (this.value.length > 0 && !/^[a-zA-Z\s]+$/.test(this.value)) {
            fullNameErrorMessage.style.display = 'block';
        } else {
            fullNameErrorMessage.style.display = 'none';
        }
    });

    // Add phone number validation (digits only, exactly 10)
    const phoneInput = document.getElementById('phone');
    const phoneErrorMessage = document.getElementById('phone-error-message');
    phoneInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 0 && this.value.length !== 10) {
            phoneErrorMessage.style.display = 'block';
        } else {
            phoneErrorMessage.style.display = 'none';
        }
    });

    // Add card number validation and formatting
    const cardNumberInput = document.getElementById('card-number');
    const cardErrorMessage = document.getElementById('card-error-message');
    const cardNameInput = document.getElementById('card-name');
    const cardNameErrorMessage = document.getElementById('card-name-error-message');
    const expiryDateInput = document.getElementById('expiry-date');
    const expiryErrorMessage = document.getElementById('expiry-error-message');
    const cvvInput = document.getElementById('cvv');
    const cvvErrorMessage = document.getElementById('cvv-error-message');

    cardNumberInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        let formattedValue = '';
        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 4 === 0) {
                formattedValue += ' ';
            }
            formattedValue += value[i];
        }
        this.value = formattedValue;
        const digitCount = value.length;
        if (digitCount > 0 && digitCount !== 16) {
            cardErrorMessage.style.display = 'block';
        } else {
            cardErrorMessage.style.display = 'none';
        }
    });

    cardNameInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        if (this.value.length > 0 && !/^[a-zA-Z\s]+$/.test(this.value)) {
            cardNameErrorMessage.style.display = 'block';
        } else {
            cardNameErrorMessage.style.display = 'none';
        }
    });

    // Validate expiry date
    function isValidExpiryDate(value) {
        if (!/^\d{2}\/\d{2}$/.test(value)) {
            return false;
        }
        const [month, year] = value.split('/').map(Number);
        if (month < 1 || month > 12) {
            return false;
        }
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear() % 100; // Get last two digits of year
        const currentMonth = currentDate.getMonth() + 1; // Months are 0-based in JS
        const expiryYear = parseInt(year, 10);
        const expiryMonth = parseInt(month, 10);
        // Check if expiry date is before current date
        if (expiryYear < currentYear || (expiryYear === currentYear && expiryMonth < currentMonth)) {
            return false;
        }
        return true;
    }

    expiryDateInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length >= 2) {
            const month = parseInt(value.substring(0, 2));
            if (month > 12 || month === 0) {
                this.value = '';
                expiryErrorMessage.textContent = 'Please enter a valid month (01-12)';
                expiryErrorMessage.style.display = 'block';
            } else {
                if (value.length > 2) {
                    this.value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                if (value.length === 4) {
                    if (!isValidExpiryDate(this.value)) {
                        expiryErrorMessage.textContent = 'Card has expired';
                        expiryErrorMessage.style.display = 'block';
                    } else {
                        expiryErrorMessage.style.display = 'none';
                    }
                } else {
                    expiryErrorMessage.style.display = 'none';
                }
            }
        } else {
            this.value = value;
            expiryErrorMessage.style.display = 'none';
        }
    });

    cvvInput.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 0 && !/^\d{3}$/.test(this.value)) {
            cvvErrorMessage.style.display = 'block';
        } else {
            cvvErrorMessage.style.display = 'none';
        }
    });

    // Set vehicle details from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const vehicleName = urlParams.get('name');
    const vehicleType = urlParams.get('type');
    const vehiclePrice = parseInt(urlParams.get('price'));
    const vehicleImage = urlParams.get('image');

    document.getElementById('vehicle-name').textContent = vehicleName || 'Unknown Vehicle';
    document.getElementById('vehicle-type').textContent = vehicleType || 'N/A';
    document.getElementById('vehicle-price').textContent = vehiclePrice || '0';
    document.getElementById('vehicle-image').src = vehicleImage || 'https://via.placeholder.com/150x100';

    // Calculate total days and amount
    const pickupDate = new Date(document.getElementById('pickup-date').value);
    const dropoffDate = new Date(document.getElementById('dropoff-date').value);
    
    if (pickupDate && dropoffDate && dropoffDate > pickupDate) {
        const diffTime = dropoffDate - pickupDate;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        document.getElementById('total-days').textContent = diffDays;
        document.getElementById('total-amount').textContent = (vehiclePrice * diffDays).toFixed(2);
    }

    // Payment method toggle
    const paymentOptions = document.querySelectorAll('input[name="payment-method"]');
    const creditCardDetails = document.getElementById('credit-card-details');
    const upiDetails = document.getElementById('upi-details');

    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            creditCardDetails.style.display = this.value === 'credit-card' ? 'block' : 'none';
            upiDetails.style.display = this.value === 'upi' ? 'block' : 'none';
            cardErrorMessage.style.display = 'none';
            cardNameErrorMessage.style.display = 'none';
            expiryErrorMessage.style.display = 'none';
            cvvErrorMessage.style.display = 'none';
        });
    });

    // Pay Now button handler
    const payNowBtn = document.getElementById('pay-now-btn');
    payNowBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const form = document.getElementById('booking-form');
        const selectedPaymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
        
        // Validate full name
        const fullName = fullNameInput.value.trim();
        if (!fullName || !/^[a-zA-Z\s]+$/.test(fullName)) {
            fullNameErrorMessage.style.display = 'block';
            showPopup('Error', 'Please enter a valid full name (letters and spaces only).');
            return;
        } else {
            fullNameErrorMessage.style.display = 'none';
        }

        // Validate phone number
        const phone = phoneInput.value;
        if (!phone || !/^\d{10}$/.test(phone)) {
            phoneErrorMessage.style.display = 'block';
            showPopup('Error', 'Please enter a valid 10-digit phone number.');
            return;
        } else {
            phoneErrorMessage.style.display = 'none';
        }

        // Validate payment details
        let paymentValid = false;
        
        if (selectedPaymentMethod === 'credit-card') {
            const cardNumber = document.getElementById('card-number').value;
            const cardName = document.getElementById('card-name').value;
            const expiryDate = document.getElementById('expiry-date').value;
            const cvv = document.getElementById('cvv').value;
            
            if (cardNumber && cardName && expiryDate && cvv) {
                const cleanedCardNumber = cardNumber.replace(/\s/g, '');
                if (cleanedCardNumber.length !== 16) {
                    cardErrorMessage.style.display = 'block';
                    showPopup('Error', 'Please enter a valid 16-digit card number.');
                    return;
                }
                if (!/^[a-zA-Z\s]+$/.test(cardName)) {
                    cardNameErrorMessage.style.display = 'block';
                    showPopup('Error', 'Name on card must contain only letters and spaces.');
                    return;
                }
                if (!isValidExpiryDate(expiryDate)) {
                    expiryErrorMessage.textContent = !/^\d{2}\/\d{2}$/.test(expiryDate) ? 
                        'Please enter a valid month (01-12)' : 'Card has expired';
                    expiryErrorMessage.style.display = 'block';
                    showPopup('Error', expiryErrorMessage.textContent);
                    return;
                }
                if (!/^\d{3}$/.test(cvv)) {
                    cvvErrorMessage.style.display = 'block';
                    showPopup('Error', 'Please enter a valid 3-digit CVV.');
                    return;
                }
                paymentValid = true;
            } else {
                showPopup('Error', 'Please fill in all card details.');
                return;
            }
        } else if (selectedPaymentMethod === 'upi') {
            const upiId = document.getElementById('upi-id').value;
            
            if (upiId) {
                if (!/^[\w\.\-]+@[\w\-]+$/.test(upiId)) {
                    showPopup('Error', 'Please enter a valid UPI ID.');
                    return;
                }
                paymentValid = true;
            } else {
                showPopup('Error', 'Please enter UPI ID.');
                return;
            }
        }

        if (!form.checkValidity()) {
            showPopup('Error', 'Please fill in all required booking details.');
            return;
        }

        // Check if required profile fields are complete
        if (!<?php echo json_encode($required_fields_complete); ?>) {
            const missingFields = <?php echo json_encode($missing_fields); ?>;
            const message = 'Please complete your profile details before booking. Missing fields: ' + missingFields.join(', ') + '.';
            showPopup('Incomplete Profile', message, () => {
                window.location.href = 'profile.php';
            });
            return;
        }

        if (paymentValid) {
            // Collect form data
            const formData = {
                email: '<?php echo isset($_SESSION["email"]) ? addslashes($_SESSION["email"]) : ""; ?>',
                full_name: document.getElementById('full-name').value,
                phone: document.getElementById('phone').value,
                location: document.getElementById('location').value,
                pickup_date: document.getElementById('pickup-date').value,
                dropoff_date: document.getElementById('dropoff-date').value,
                delivery: document.getElementById('delivery').value === 'Yes' ? 1 : 0,
                vehicle_name: document.getElementById('vehicle-name').textContent,
                vehicle_type: document.getElementById('vehicle-type').textContent,
                total_days: document.getElementById('total-days').textContent,
                total_amount: document.getElementById('total-amount').textContent,
                payment_method: selectedPaymentMethod === 'credit-card' ? 'Credit/Debit Card' : selectedPaymentMethod
            };

            // Debug: Log formData to check email and delivery
            console.log('Form Data:', formData);
            if (!formData.email) {
                showPopup('Error', 'Session email is missing. Please log in again.');
                return;
            }

            // Send data to server
            fetch('save_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const successPopup = document.getElementById('success-popup');
                    successPopup.style.display = 'flex';
                    document.getElementById('ok-btn').addEventListener('click', function() {
                        setTimeout(() => {
                            window.location.href = 'home.php';
                        }, 3000);
                    });
                } else {
                    showPopup('Error', data.message || 'Something went wrong. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showPopup('Error', 'Something went wrong. Please try again.');
            });
        }
    });
}

// Popup function for errors and profile validation
function showPopup(title, message, onClose = null) {
    const popup = document.createElement('div');
    popup.className = 'popup-overlay';
    popup.innerHTML = `
        <div class="popup-content">
            <h2>${title}</h2>
            <p>${message}</p>
            <button class="ok-button">OK</button>
        </div>
    `;
    document.body.appendChild(popup);
    const okButton = popup.querySelector('.ok-button');
    okButton.addEventListener('click', () => {
        popup.remove();
        if (onClose) onClose();
    });
}
</script>
</body>
</html>
<?php
$conn->close();
?>