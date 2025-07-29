<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require_once("db_connect.php");

    $username = trim($_POST["username"]);
    $email = trim(strtolower($_POST["email"]));
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Server-side validation
    if (!preg_match('/^[a-zA-Z]+$/', $username)) {
        $error = "Username must contain only letters (a-z, A-Z).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($_POST["password"]) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if username or email already exists
        $check_sql = "SELECT user_id, username, email FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $check_result = $stmt->get_result();

        if ($check_result->num_rows > 0) {
            $row = $check_result->fetch_assoc();
            $errors = [];
            if ($row['username'] === $username) {
                $errors[] = "Username already exists. Please choose a different username.";
            }
            if ($row['email'] === $email) {
                $errors[] = "Email already exists. Please use a different email.";
            }
            $error = implode("<br>", $errors);
        } else {
            // Insert new user
            $sql = "INSERT INTO users (username, password, email) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $username, $password, $email);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now Sign In.";
            } else 
                if ($conn->errno === 1062) {
                    $error = "Error: Username or email already exists in the database.";
                } else {
                    $error = "Error creating account: " . $conn->error;
                }
            }
        }
        $stmt->close();
        $conn->close();
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Rental - Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="register_style.css">
</head>
<body>
<div class="top-left-title">WheelsOnRent</div>
    <div class="register-container">
        <div class="register-box">
            <h1 class="title">Create Account</h1>
            <div id="success-message" class="<?php echo isset($success) ? 'show' : ''; ?>">
                <?php if (isset($success)) echo htmlspecialchars($success); ?>
            </div>
            <div id="validation-message" class="<?php echo isset($error) ? 'show' : ''; ?>">
                <?php if (isset($error)) echo htmlspecialchars($error); ?>
            </div>
            <form id="register-form" method="POST" action="">
                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your name" required>
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="show-password" name="show-password">
                    <label for="show-password">Show Password</label>
                </div>
                <button type="submit" class="register-btn">Register</button>
            </form>
            <div class="login-option">
                <p>Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script>
        // Show password toggle
        const passwordInput = document.getElementById('password');
        const showPasswordCheckbox = document.getElementById('show-password');
        showPasswordCheckbox.addEventListener('change', function() {
            passwordInput.type = this.checked ? 'text' : 'password';
        });

        // Restrict username input to letters only
        const usernameInput = document.getElementById('username');
        usernameInput.addEventListener('keypress', function(event) {
            const char = String.fromCharCode(event.which || event.keyCode);
            const usernameRegex = /^[a-zA-Z]+$/;
            if (!usernameRegex.test(char)) {
                event.preventDefault();
            }
        });

        // Client-side form validation
        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const username = usernameInput.value.trim();
            const email = document.getElementById('email').value.trim().toLowerCase();
            const password = passwordInput.value;
            const messageElement = document.getElementById('validation-message');
            let errors = [];

            // Username: only letters (a-z, A-Z)
            const usernameRegex = /^[a-zA-Z]+$/;
            if (!usernameRegex.test(username)) {
                errors.push('Username must contain only letters (no numbers or special symbols).');
            }

            // Password: at least 8 characters
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long.');
            }

            // Basic email format validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Please enter a valid email address.');
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