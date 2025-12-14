<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

// Block access if secretary already exists
$q = $con->query("SELECT user_id FROM users WHERE role='secretary' LIMIT 1");
if ($q && $q->num_rows > 0) {
    header("Location: dashboard.php?secretary_exists=1");
    exit;
}

// Continue with original creation logic...
$errors = []; 
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$fullname || !$email || !$password) $errors[] = "All fields are required.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Enter a valid email.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";

    if (empty($errors)) {
        $check = $con->prepare("SELECT user_id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) $errors[] = "Email already exists.";
        $check->close();
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $insert = $con->prepare("INSERT INTO users (role, fullname, email, password) VALUES ('secretary',?,?,?)");
        $insert->bind_param("sss", $fullname, $email, $hash);

        if ($insert->execute()) {
            $success = "Secretary account created successfully.";
        } else {
            $errors[] = "Database error occurred.";
        }
        $insert->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Secretary | e-Barangay</title>
    <link rel="stylesheet" href="/e-barangay/public/assets/css/auth.css">
</head>

<body>

<div class="container">
    <div class="auth-box">

        <img src="/e-barangay/public/assets/images/logo.jpg" class="seal" alt="Barangay Seal">

        <h2>Create Secretary Account</h2>
        <p class="subtext">Barangay Cantil · Roxas, Oriental Mindoro</p>

        <!-- Alerts -->
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?= implode("<br>", $errors); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">

            <div class="input-group">
                <label>Full Name</label>
                <input type="text" name="fullname" placeholder="Juan Dela Cruz" required>
            </div>

            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="secretary@gmail.com" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" minlength="6" required>
            </div>

            <button type="submit" class="btn">Create Secretary</button>

            <p class="redirect">
                <a href="dashboard.php">← Back to Dashboard</a>
            </p>

        </form>

    </div>
</div>

</body>
</html>
