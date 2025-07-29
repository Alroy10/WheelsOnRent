<?php
session_start();

// Check if admin is already logged in
if(isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit();
}

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Add your admin credentials here
    if($username === "admin" && $password === "admin123") {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid credentials";
    }
}
?> 

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - WheelsOnRent</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
    />
    <link
      href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
      rel="stylesheet"
    />
    <style>
      body {
        background: linear-gradient(135deg, #1a1a2e 0%, #2a2a4e 100%);
        font-family: "Poppins", sans-serif;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0;
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
      }

      @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
      }

      .login-container {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        padding: 3rem;
        width: 100%;
        max-width: 450px;
        animation: fadeIn 0.5s ease-in-out;
        border: 1px solid rgba(255, 255, 255, 0.2);
      }

      .input-group {
        position: relative;
        margin-bottom: 1.8rem;
      }

      .input-group input {
        width: 100%;
        padding: 1rem 3rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 1rem;
        transition: all 0.3s ease;
        background: rgba(255, 255, 255, 0.9);
      }

      .input-group input:focus {
        border-color: #ffca28;
        box-shadow: 0 0 0 4px rgba(255, 202, 40, 0.1);
        outline: none;
      }

      .sym {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #ffca28;
        font-size: 1.2rem;
        transition: all 0.3s ease;
      }

      .password-toggle {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #666;
        transition: all 0.3s ease;
      }

      .password-toggle:hover {
        color: #ffca28;
      }

      button {
        background: linear-gradient(45deg, #ffca28, #ffb300);
        color: #1a1a2e;
        padding: 1rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        width: 100%;
        box-shadow: 0 4px 15px rgba(255, 202, 40, 0.2);
      }

      button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 202, 40, 0.3);
      }

      button:active {
        transform: translateY(0);
      }

      .error {
        background: rgba(220, 38, 38, 0.1);
        color: #dc2626;
        padding: 1rem;
        border-radius: 12px;
        text-align: center;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(220, 38, 38, 0.2);
        font-size: 0.95rem;
      }
      .top-left-title {
    position: absolute;
    top: 20px;
    left: 20px;
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
}


      h1 {
        color: #1a1a2e;
        font-size: 2rem !important;
        margin-bottom: 2rem !important;
        text-align: center;
        font-weight: 700;
      }

      @media (max-width: 640px) {
        .login-container {
          margin: 1rem;
          padding: 2rem;
        }

        h1 {
          font-size: 1.75rem !important;
        }

        .input-group input {
          padding: 0.875rem 2.5rem;
        }
      }
    </style>
  </head>
  <body>
  <div class="top-left-title">WheelsOnRent</div>
    <div class="login-container">
      <!-- Placeholder logo (replace with actual image path if available) -->
      <!-- <img src="image-9.jpg" alt="WheelsOnRent Logo" class="logo" /> -->
      <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">
        Admin Login
      </h1>
      <?php if(isset($error)): ?>
      <div class="error"><?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST" class="space-y-4">
        <div class="input-group">
          <i class="fas fa-user sym"></i>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Username"
            required
            class="username-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="Username"
          />
        </div>
        <div class="input-group">
          <i class="fas fa-lock sym"></i>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Password"
            required
            class="password-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            aria-label="Password"
          />
          <i
            class="fas fa-eye password-toggle"
            id="togglePassword"
            aria-label="Toggle password visibility"
          ></i>
        </div>
        <button
          type="submit"
          class="w-full bg-blue-900 text-white py-3 rounded-lg hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          Login
        </button>
      </form>
    </div>

    <script>
      const togglePassword = document.getElementById("togglePassword");
      const passwordInput = document.getElementById("password");

      togglePassword.addEventListener("click", () => {
        // When eye is open (fa-eye), show password (type="text")
        // When eye is closed (fa-eye-slash), hide password (type="password")
        const type = togglePassword.classList.contains("fa-eye") ? "password" : "text";
        passwordInput.setAttribute("type", type);
        togglePassword.classList.toggle("fa-eye");
        togglePassword.classList.toggle("fa-eye-slash");
      });
    </script>
  </body>
</html>
