<?php
session_start(); // Start the session

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once("db_connect.php"); // Include database connection

    $email = trim(strtolower($_POST["email"]));
    $password = $_POST["password"];

    // Server-side validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Debug: Log the email being submitted
        error_log("Login attempt with email: " . $email);

        // Query the database for the user by email, case-insensitive
        $sql = "SELECT user_id, username, email, password FROM users WHERE LOWER(email) = LOWER(?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            // Debug: Log the hashed password from the database
            error_log("Hashed password from DB: " . $row["password"]);
            if (password_verify($password, $row["password"])) {
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["username"] = $row["username"];
                $_SESSION["email"] = $row["email"]; // Set email in session
                error_log("login.php - Session email set: " . $row["email"]);
                header("Location: home.php"); // Redirect to homepage after login
                exit();
            } else {
                $error = "Invalid email or password.";
                error_log("Password verification failed for email: " . $email);
            }
        } else {
            $error = "Invalid email or password.";
            error_log("No user found with email: " . $email);
        }

        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WheelsOnRent - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="login_style.css">
</head>
<body>
    <div class="top-left-title">WheelsOnRent</div>

    <section class="hero">
        <div class="hero-backpanel"></div>
        <div class="hero-content">
            <div class="search-section">
                <h1>Login</h1>
                <div id="validation-message" class="<?php echo isset($error) ? 'show' : ''; ?>">
                    <?php if (isset($error)) echo htmlspecialchars($error); ?>
                </div>
                <div class="search-bar">
                    <form id="login-form" method="POST" action="">
                        <div>
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                        <div>
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="checkbox-container">
                            <label for="show-password" class="checkbox-label">
                                <input type="checkbox" id="show-password" name="show-password">
                                Show Password
                            </label>
                            <a href="forgot_password.php" class="forgot-password-link">Forgot Password?</a>
                        </div>
                        <button type="submit" class="search-btn">Login</button>
                        <a href="register.php" class="register-btn">Register</a>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Show password toggle
        const passwordInput = document.getElementById('password');
        const showPasswordCheckbox = document.getElementById('show-password');
        showPasswordCheckbox.addEventListener('change', function () {
            passwordInput.type = this.checked ? 'text' : 'password';
        });

        // Client-side form validation
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const email = document.getElementById('email').value.trim().toLowerCase();
            const password = passwordInput.value;
            const messageElement = document.getElementById('validation-message');
            let errors = [];

            // Basic email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Please enter a valid email address.');
            }

            // Password: at least 8 characters
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long.');
            }

            // Display errors or submit form
            if (errors.length > 0) {
                messageElement.innerHTML = errors.join('<br>');
                messageElement.classList.add('show');
                messageElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                messageElement.innerHTML = '';
                messageElement.classList.remove('show');
                this.submit();
            }
        });
    </script>
</body>
</html>