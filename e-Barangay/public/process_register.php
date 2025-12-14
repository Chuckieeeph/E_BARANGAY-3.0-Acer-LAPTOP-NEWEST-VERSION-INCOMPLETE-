<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$errors = [];

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect and sanitize input
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    // Validate required fields
    if (!$fullname || !$email || !$dob || !$gender || !$address || !$contact || !$password || !$cpassword) {
        $errors[] = "All fields are required.";
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    }

    // Password length
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    // Confirm password
    if ($password !== $cpassword) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    $stmt = $con->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $errors[] = "Email already registered.";
    }
    $stmt->close();

    // Handle profile photo upload (optional)
    $profile_photo = null;
    if (isset($_FILES['profile']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profile']['tmp_name'];
        $fileName = basename($_FILES['profile']['name']);
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif'];

        if (!in_array($fileExt, $allowed)) {
            $errors[] = "Profile photo must be an image (jpg, png, gif).";
        } else {
            $newFileName = uniqid('profile_', true) . "." . $fileExt;
            $targetDir = __DIR__ . '/../assets/uploads/profiles/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            if (move_uploaded_file($fileTmp, $targetDir . $newFileName)) {
                $profile_photo = $newFileName;
            } else {
                $errors[] = "Failed to upload profile photo.";
            }
        }
    }

    // If no errors, insert user
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
    INSERT INTO users (fullname, email, gender, birthdate, civil_status, nationality, address, contact_no, password, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'resident')
");

        $stmt->bind_param("sssss", $fullname, $email, $hash, $contact, $address);

        if ($stmt->execute()) {
            // Optional: store profile photo path in separate column if needed
            if ($profile_photo) {
                $stmt2 = $con->prepare("UPDATE users SET profile_photo = ? WHERE email = ?");
                $stmt2->bind_param("ss", $profile_photo, $email);
                $stmt2->execute();
                $stmt2->close();
            }

            $_SESSION['success'] = "Account created successfully! Waiting for validation.";
            header("Location: register.php");
            exit;
        } else {
            $errors[] = "Database error occurred.";
        }
        $stmt->close();
    }

    // Store errors in session and redirect back
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: register.php");
        exit;
    }
} else {
    // Redirect to register page if accessed directly
    header("Location: register.php");
    exit;
}
