<?php
session_start();
require_once __DIR__ . '/../includes/error_handler.php';

require_once __DIR__ . '/../config/database.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $contact_no = trim($_POST['contact_no'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $civil_status = $_POST['civil_status'] ?? '';
    $nationality = trim($_POST['nationality'] ?? '');
    $terms = isset($_POST['terms']);

    // Validation
    if (!$fullname || !$email || !$password || !$confirm_password) {
        $err = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $err = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm_password) {
        $err = "Passwords do not match.";
    } elseif (!$terms) {
        $err = "You must agree to the Terms and Conditions.";
    } else {
        // Check if email exists
        $stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $err = "Email already registered.";
        } else {
            // Insert new user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $con->prepare("
                INSERT INTO users (fullname, email, password, role, contact_no, address, gender, birthdate, civil_status, nationality, validation_status) 
                VALUES (?, ?, ?, 'resident', ?, ?, ?, ?, ?, ?, 'unvalidated')
            ");
            $stmt->bind_param("sssssssss", $fullname, $email, $hashed, $contact_no, $address, $gender, $birthdate, $civil_status, $nationality);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                // Optional: Redirect after 2 seconds
                header("refresh:2;url=login.php");
            } else {
                $err = "Registration failed. Please try again.";
            }
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
<title>Register • e-BARANGAY</title>
<link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>

<div class="container">
    
    <!-- Branding Section -->
    <div class="branding-section">
        <div class="logo-wrapper">
            <img src="../assets/images/logo.jpg" class="seal" alt="Barangay Logo">
        </div>
        <h1 class="brgy-name">Brgy. Cantil</h1>
        <p class="location">Roxas, Oriental Mindoro</p>
        <h2 class="system-name">Resident Registration</h2>
        <p class="tagline">Create your account to access barangay services</p>
    </div>

    <!-- Registration Form -->
    <div class="register-section">
        <div class="register-box">
            <h2>Create Account</h2>
            <p class="welcome-text">Register as a resident of Barangay Cantil</p>

            <?php if ($err): ?>
                <div class="alert error">
                    <strong>⚠️ Error</strong>
                    <p><?= htmlspecialchars($err) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert success">
                    <strong>✅ Success</strong>
                    <p><?= htmlspecialchars($success) ?></p>
                    <p><small>Redirecting to login page...</small></p>
                </div>
            <?php endif; ?>

            <form method="POST" id="registerForm" autocomplete="off">
                
                <!-- Personal Information -->
                <div class="form-section">
                    <h3>📋 Personal Information</h3>
                    
                    <div class="input-group">
                        <label for="fullname">Full Name <span class="required">*</span></label>
                        <input type="text" id="fullname" name="fullname" 
                               placeholder="Enter your full name" 
                               value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" 
                               autocomplete="off"
                               required>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender" autocomplete="off">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label for="birthdate">Birthdate</label>
                            <input type="date" id="birthdate" name="birthdate" 
                                   value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>"
                                   autocomplete="off">
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="civil_status">Civil Status</label>
                            <select id="civil_status" name="civil_status" autocomplete="off">
                                <option value="">Select Status</option>
                                <option value="Single" <?= ($_POST['civil_status'] ?? '') === 'Single' ? 'selected' : '' ?>>Single</option>
                                <option value="Married" <?= ($_POST['civil_status'] ?? '') === 'Married' ? 'selected' : '' ?>>Married</option>
                                <option value="Widowed" <?= ($_POST['civil_status'] ?? '') === 'Widowed' ? 'selected' : '' ?>>Widowed</option>
                                <option value="Separated" <?= ($_POST['civil_status'] ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
                            </select>
                        </div>

                        <div class="input-group">
                            <label for="nationality">Nationality</label>
                            <input type="text" id="nationality" name="nationality" 
                                   placeholder="e.g., Filipino" 
                                   value="<?= htmlspecialchars($_POST['nationality'] ?? '') ?>"
                                   autocomplete="off">
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <h3>📞 Contact Information</h3>
                    
                    <div class="input-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" 
                               placeholder="Enter your email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               autocomplete="off"
                               required>
                    </div>

                    <div class="input-group">
                        <label for="contact_no">Contact Number</label>
                        <input type="text" id="contact_no" name="contact_no" 
                               placeholder="e.g., 09123456789" 
                               value="<?= htmlspecialchars($_POST['contact_no'] ?? '') ?>"
                               autocomplete="off">
                    </div>

                    <div class="input-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" 
                                  placeholder="Enter your complete address"
                                  autocomplete="off"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- Account Security -->
                <div class="form-section">
                    <h3>🔒 Account Security</h3>
                    
                    <div class="input-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" 
                               placeholder="At least 6 characters" 
                               autocomplete="new-password"
                               required>
                        <small class="hint">Password must be at least 6 characters long</small>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Re-enter your password" 
                               autocomplete="new-password"
                               required>
                    </div>
                </div>

                <!-- Terms and Conditions -->
                <div class="checkbox-group">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">
                        I agree to the <a href="#" id="termsLink">Terms and Conditions</a> <span class="required">*</span>
                    </label>
                </div>

                <button type="submit" class="btn-register">Create Account</button>
                
                <div class="login-section">
                    <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Include Terms Modal -->
<?php include __DIR__ . '/../includes/terms_modal.php'; ?>

<script>
// Clear form on page load to prevent autofill
window.addEventListener('load', function() {
    if (performance.navigation.type === 1) {
        // Page was refreshed - don't clear
    } else {
        // Fresh page load - clear all fields except on error
        <?php if (!$err && !$success): ?>
        document.getElementById('registerForm').reset();
        <?php endif; ?>
    }
});

// Password match validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const terms = document.getElementById('terms').checked;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('⚠️ Passwords do not match!');
        return false;
    }
    
    if (!terms) {
        e.preventDefault();
        alert('⚠️ You must agree to the Terms and Conditions!');
        return false;
    }
});

// Real-time password match indicator
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (confirmPassword && password !== confirmPassword) {
        this.style.borderColor = '#dc2626';
    } else if (confirmPassword && password === confirmPassword) {
        this.style.borderColor = '#10b981';
    } else {
        this.style.borderColor = '#e0e0e0';
    }
});

// Disable browser autofill/autocomplete
document.querySelectorAll('input, select, textarea').forEach(function(element) {
    element.setAttribute('autocomplete', 'off');
});
</script>

</body>
</html>