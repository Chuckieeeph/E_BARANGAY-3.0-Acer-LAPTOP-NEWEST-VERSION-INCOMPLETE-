<?php
//login.php
session_start();
require_once __DIR__ . '/../includes/error_handler.php';

require_once __DIR__ . '/../config/database.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $err = "Email and password required.";
    } else {
        $stmt = $con->prepare("SELECT user_id, role, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $role, $fullname, $hash);
            $stmt->fetch();
            if (password_verify($password, $hash)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = $role;
                $_SESSION['fullname'] = $fullname;

                if ($role === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } elseif ($role === 'secretary') {
                    header("Location: ../secretary/dashboard.php");
                } else {
                    header("Location: ../resident/dashboard.php");
                }
                exit;
            } else {
                $err = "Incorrect email or password.";
            }
        } else {
            $err = "Incorrect email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login • e-BARANGAY</title>
<link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="container">
    <div class="branding-section">
        <div class="logo-wrapper">
            <img src="../assets/images/logo.jpg" class="seal" alt="Barangay Logo">
        </div>
        <h1 class="brgy-name">Brgy. Cantil</h1>
        <p class="location">Roxas, Oriental Mindoro</p>
        <h2 class="system-name">Clearance and Case Management System</h2>
    </div>

    <div class="login-section">
        <div class="login-box">
            <h2>Login</h2>
            <p class="welcome-text">Welcome back! Please login to your account.</p>

            <?php if ($err): ?>
                <div class="error">
                    <strong>⚠️ Error</strong>
                    <p><?= htmlspecialchars($err) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required autofocus>
                </div>

                <div class="input-group password-group">
    <label for="password">Password</label>
    <div class="password-wrapper">
        <input type="password" id="password" name="password" placeholder="Enter your password" required>
        <span id="togglePassword" class="toggle-password" style="display:none; cursor:pointer;">
        </span>
    </div>
</div>


                <button type="submit" class="btn-login">Login</button>
                
                <div class="register-section">
                    <p>Don't have an account? <a href="register.php" class="register-link">Create new account</a></p>
                    <p class="terms-link-text"><a href="#" id="termsLink">Terms and Conditions</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const passwordInput = document.getElementById('password');
const togglePassword = document.getElementById('togglePassword');
const eyeClosed = document.getElementById('eyeClosed');
const eyeOpen = document.getElementById('eyeOpen');

// Show/hide icon depending on input content
passwordInput.addEventListener('input', () => {
    togglePassword.style.display = passwordInput.value.length > 0 ? 'block' : 'none';
});

// Toggle visibility
togglePassword.addEventListener('click', () => {
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeClosed.style.display = 'none';
        eyeOpen.style.display = 'block';
    } else {
        passwordInput.type = 'password';
        eyeClosed.style.display = 'block';
        eyeOpen.style.display = 'none';
    }
});
</script>

<!-- Include Terms Modal -->
<?php include __DIR__ . '/../includes/terms_modal.php'; ?>

</body>
</html>