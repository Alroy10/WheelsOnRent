<?php
session_start();

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token.";
    } else {
        require_once("db_connect.php");

        $email = trim(strtolower($_POST["email"]));
        $new_password = $_POST["new-password"];
        $confirm_password = $_POST["confirm-password"];

        // Server-side validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } else {
            // Check if email exists
            $sql = "SELECT user_id FROM users WHERE LOWER(email) = LOWER(?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ? WHERE LOWER(email) = LOWER(?)";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $hashed_password, $email);

                if ($update_stmt->execute()) {
                    $success = "Password reset successful! Redirecting to login page...";
                    error_log("Password reset successful for email: " . $email);
                    header("Refresh: 2; URL=login.php");
                } else {
                    $error = "Failed to reset password. Please try again.";
                    error_log("Password reset failed for email: " . $email);
                }
                $update_stmt->close();
            } else {
                $error = "No account found with this email!";
                error_log("No user found with email: " . $email);
            }
            $stmt->close();
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WheelsOnRent - Reset Password</title>
    <link rel="stylesheet" href="forgotpassword_style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="top-left-title">WheelsOnRent</div>

    <div class="reset-container">
        <div class="reset-box">
            <h1 class="title">Reset Password</h1>
            <div id="success-message" class="<?php echo isset($success) ? 'show' : ''; ?>">
                <?php if (isset($success)) echo htmlspecialchars($success); ?>
            </div>
            <div id="validation-message" class="<?php echo isset($error) ? 'show' : ''; ?>">
                <?php if (isset($error)) echo htmlspecialchars($error); ?>
            </div>
            <form id="reset-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required aria-describedby="email-error">
                </div>
                <div class="input-group">
                    <label for="new-password">New Password</label>
                    <input type="password" id="new-password" name="new-password" placeholder="Enter new password" required aria-describedby="password-error">
                </div>
                <div class="input-group">
                    <label for="confirm-password">Confirm New Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm new password" required aria-describedby="password-error">
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="show-password" name="show-password" aria-label="Show password">
                    <label for="show-password">Show Password</label>
                </div>
                <button type="submit" class="reset-btn">Reset Password</button>
            </form>
            <div class="login-option">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script>
        const newPasswordInput = document.getElementById('new-password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const showPasswordCheckbox = document.getElementById('show-password');
        const emailInput = document.getElementById('email');
        const resetForm = document.getElementById('reset-form');
        const messageElement = document.getElementById('validation-message');

        // Show/hide password
        showPasswordCheckbox.addEventListener('change', function() {
            const type = this.checked ? 'text' : 'password';
            newPasswordInput.type = type;
            confirmPasswordInput.type = type;
        });

        // Client-side validation
        resetForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = emailInput.value.trim().toLowerCase();
            const newPassword = newPasswordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            let errors = [];

            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Please enter a valid email address.');
            }

            // Password length
            if (newPassword.length < 8) {
                errors.push('Password must be at least 8 characters long.');
            }

            // Password match
            if (newPassword !== confirmPassword) {
                errors.push('Passwords do not match.');
            }

            // Display errors or submit
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