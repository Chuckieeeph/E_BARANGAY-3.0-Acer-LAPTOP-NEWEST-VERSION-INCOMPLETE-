<?php
//resident/profile.php - COMPLETE Enhanced Profile
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('resident');
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $con->prepare("
    SELECT u.*, rp.* 
    FROM users u
    LEFT JOIN resident_profiles rp ON u.user_id = rp.user_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// If no profile exists, create one
if (!isset($user['profile_id'])) {
    $con->query("INSERT INTO resident_profiles (user_id) VALUES ($user_id)");
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}

$success_msg = $error_msg = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Personal Info
    $birthplace = trim($_POST['birthplace'] ?? '');
    $height = floatval($_POST['height'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0);
    $blood_type = trim($_POST['blood_type'] ?? '');
    
    // Address
    $house_no = trim($_POST['house_no'] ?? '');
    $street = trim($_POST['street'] ?? '');
    $purok = trim($_POST['purok'] ?? '');
    $years_of_residency = intval($_POST['years_of_residency'] ?? 0);
    
    // Family
    $spouse_name = trim($_POST['spouse_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $mother_maiden_name = trim($_POST['mother_maiden_name'] ?? '');
    
    // Employment
    $occupation = trim($_POST['occupation'] ?? '');
    $employer_name = trim($_POST['employer_name'] ?? '');
    $monthly_income = floatval($_POST['monthly_income'] ?? 0);
    $employment_status = $_POST['employment_status'] ?? 'Unemployed';
    
    // IDs
    $tin = trim($_POST['tin'] ?? '');
    $sss_number = trim($_POST['sss_number'] ?? '');
    $philhealth_number = trim($_POST['philhealth_number'] ?? '');
    $voters_id = trim($_POST['voters_id'] ?? '');
    
    // Emergency Contact
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');
    $emergency_contact_relation = trim($_POST['emergency_contact_relation'] ?? '');
    
    // Calculate age from birthdate
    $age = 0;
    if ($user['birthdate']) {
        $birthdate = new DateTime($user['birthdate']);
        $today = new DateTime();
        $age = $birthdate->diff($today)->y;
    }
    
    // Update profile
    $update_stmt = $con->prepare("
        UPDATE resident_profiles SET
            birthplace = ?, age = ?, height = ?, weight = ?, blood_type = ?,
            house_no = ?, street = ?, purok = ?, years_of_residency = ?,
            spouse_name = ?, father_name = ?, mother_name = ?, mother_maiden_name = ?,
            occupation = ?, employer_name = ?, monthly_income = ?, employment_status = ?,
            tin = ?, sss_number = ?, philhealth_number = ?, voters_id = ?,
            emergency_contact_name = ?, emergency_contact_number = ?, emergency_contact_relation = ?,
            profile_completed = TRUE,
            last_updated = NOW()
        WHERE user_id = ?
    ");
    
    $update_stmt->bind_param(
        "siddssssississdssssssssi",
        $birthplace, $age, $height, $weight, $blood_type,
        $house_no, $street, $purok, $years_of_residency,
        $spouse_name, $father_name, $mother_name, $mother_maiden_name,
        $occupation, $employer_name, $monthly_income, $employment_status,
        $tin, $sss_number, $philhealth_number, $voters_id,
        $emergency_contact_name, $emergency_contact_number, $emergency_contact_relation,
        $user_id
    );
    
    if ($update_stmt->execute()) {
        $success_msg = "Profile updated successfully!";
        // Refresh data
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error_msg = "Failed to update profile.";
    }
}

$validation_status = $_SESSION['validation_status'] ?? 'unvalidated';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - e-Barangay</title>
    <link rel="stylesheet" href="/e-barangay/assets/css/resident_profile_enhanced.css">
</head>
<body>

    <header class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="/e-barangay/resident/dashboard.php" class="back-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to Home
                </a>
            </div>
            <div class="nav-right">
                <a href="/e-barangay/public/logout.php" class="logout-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <main class="profile-container">
        
        <h1 class="page-title">My Complete Profile</h1>
        <p class="page-subtitle">Complete your profile to access all barangay services</p>

        <?php if ($success_msg): ?>
            <div class="alert success"><?= htmlspecialchars($success_msg) ?></div>
        <?php endif; ?>
        
        <?php if ($error_msg): ?>
            <div class="alert error"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <form method="POST" class="profile-form">
            
            <!-- Basic Information (Read-only from registration) -->
            <div class="form-section">
                <h3>👤 Basic Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <p><?= htmlspecialchars($user['fullname']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Gender</label>
                        <p><?= htmlspecialchars($user['gender'] ?? 'Not set') ?></p>
                    </div>
                    <div class="info-item">
                        <label>Birthdate</label>
                        <p><?= $user['birthdate'] ? date('F j, Y', strtotime($user['birthdate'])) : 'Not set' ?></p>
                    </div>
                    <div class="info-item">
                        <label>Civil Status</label>
                        <p><?= htmlspecialchars($user['civil_status'] ?? 'Not set') ?></p>
                    </div>
                    <div class="info-item">
                        <label>Nationality</label>
                        <p><?= htmlspecialchars($user['nationality'] ?? 'Not set') ?></p>
                    </div>
                    <div class="info-item">
                        <label>Validation Status</label>
                        <p>
                            <span class="badge status-<?= $validation_status ?>">
                                <?= ucfirst($validation_status) ?>
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Personal Details (Editable) -->
            <div class="form-section">
                <h3>📋 Personal Details</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>Birthplace</label>
                        <input type="text" name="birthplace" value="<?= htmlspecialchars($user['birthplace'] ?? '') ?>" placeholder="City/Municipality, Province">
                    </div>
                    <div class="input-group">
                        <label>Height (cm)</label>
                        <input type="number" step="0.01" name="height" value="<?= $user['height'] ?? '' ?>" placeholder="e.g., 165">
                    </div>
                    <div class="input-group">
                        <label>Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" value="<?= $user['weight'] ?? '' ?>" placeholder="e.g., 70">
                    </div>
                    <div class="input-group">
                        <label>Blood Type</label>
                        <select name="blood_type">
                            <option value="">Select Blood Type</option>
                            <?php foreach(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $type): ?>
                                <option value="<?= $type ?>" <?= ($user['blood_type'] ?? '') === $type ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Address Details -->
            <div class="form-section">
                <h3>🏠 Address Details</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>House No.</label>
                        <input type="text" name="house_no" value="<?= htmlspecialchars($user['house_no'] ?? '') ?>" placeholder="e.g., 123">
                    </div>
                    <div class="input-group">
                        <label>Street</label>
                        <input type="text" name="street" value="<?= htmlspecialchars($user['street'] ?? '') ?>" placeholder="Street name">
                    </div>
                    <div class="input-group">
                        <label>Purok/Sitio</label>
                        <input type="text" name="purok" value="<?= htmlspecialchars($user['purok'] ?? '') ?>" placeholder="e.g., Purok 1">
                    </div>
                    <div class="input-group">
                        <label>Years of Residency</label>
                        <input type="number" name="years_of_residency" value="<?= $user['years_of_residency'] ?? '' ?>" placeholder="Years living here">
                    </div>
                </div>
            </div>

            <!-- Family Information -->
            <div class="form-section">
                <h3>👨‍👩‍👧 Family Information</h3>
                <?php if ($user['civil_status'] === 'Married'): ?>
                <div class="input-group">
                    <label>Spouse Name</label>
                    <input type="text" name="spouse_name" value="<?= htmlspecialchars($user['spouse_name'] ?? '') ?>" placeholder="Full name of spouse">
                </div>
                <?php endif; ?>
                <div class="input-row">
                    <div class="input-group">
                        <label>Father's Name</label>
                        <input type="text" name="father_name" value="<?= htmlspecialchars($user['father_name'] ?? '') ?>" placeholder="Full name">
                    </div>
                    <div class="input-group">
                        <label>Mother's Name</label>
                        <input type="text" name="mother_name" value="<?= htmlspecialchars($user['mother_name'] ?? '') ?>" placeholder="Full name">
                    </div>
                </div>
                <div class="input-group">
                    <label>Mother's Maiden Name</label>
                    <input type="text" name="mother_maiden_name" value="<?= htmlspecialchars($user['mother_maiden_name'] ?? '') ?>" placeholder="Maiden name">
                </div>
            </div>

            <!-- Employment -->
            <div class="form-section">
                <h3>💼 Employment Information</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>Employment Status</label>
                        <select name="employment_status">
                            <option value="Unemployed" <?= ($user['employment_status'] ?? '') === 'Unemployed' ? 'selected' : '' ?>>Unemployed</option>
                            <option value="Employed" <?= ($user['employment_status'] ?? '') === 'Employed' ? 'selected' : '' ?>>Employed</option>
                            <option value="Self-Employed" <?= ($user['employment_status'] ?? '') === 'Self-Employed' ? 'selected' : '' ?>>Self-Employed</option>
                            <option value="Student" <?= ($user['employment_status'] ?? '') === 'Student' ? 'selected' : '' ?>>Student</option>
                            <option value="Retired" <?= ($user['employment_status'] ?? '') === 'Retired' ? 'selected' : '' ?>>Retired</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Occupation</label>
                        <input type="text" name="occupation" value="<?= htmlspecialchars($user['occupation'] ?? '') ?>" placeholder="Job title/occupation">
                    </div>
                </div>
                <div class="input-row">
                    <div class="input-group">
                        <label>Employer/Business Name</label>
                        <input type="text" name="employer_name" value="<?= htmlspecialchars($user['employer_name'] ?? '') ?>" placeholder="Company/business name">
                    </div>
                    <div class="input-group">
                        <label>Monthly Income (₱)</label>
                        <input type="number" step="0.01" name="monthly_income" value="<?= $user['monthly_income'] ?? '' ?>" placeholder="0.00">
                    </div>
                </div>
            </div>

            <!-- Government IDs -->
            <div class="form-section">
                <h3>🆔 Government ID Numbers</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>TIN</label>
                        <input type="text" name="tin" value="<?= htmlspecialchars($user['tin'] ?? '') ?>" placeholder="XXX-XXX-XXX-XXX">
                    </div>
                    <div class="input-group">
                        <label>SSS Number</label>
                        <input type="text" name="sss_number" value="<?= htmlspecialchars($user['sss_number'] ?? '') ?>" placeholder="XX-XXXXXXX-X">
                    </div>
                </div>
                <div class="input-row">
                    <div class="input-group">
                        <label>PhilHealth Number</label>
                        <input type="text" name="philhealth_number" value="<?= htmlspecialchars($user['philhealth_number'] ?? '') ?>" placeholder="XX-XXXXXXXXX-X">
                    </div>
                    <div class="input-group">
                        <label>Voter's ID</label>
                        <input type="text" name="voters_id" value="<?= htmlspecialchars($user['voters_id'] ?? '') ?>" placeholder="Voter's ID number">
                    </div>
                </div>
            </div>

            <!-- Emergency Contact -->
            <div class="form-section">
                <h3>🚨 Emergency Contact</h3>
                <div class="input-row">
                    <div class="input-group">
                        <label>Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($user['emergency_contact_name'] ?? '') ?>" placeholder="Full name">
                    </div>
                    <div class="input-group">
                        <label>Contact Number</label>
                        <input type="text" name="emergency_contact_number" value="<?= htmlspecialchars($user['emergency_contact_number'] ?? '') ?>" placeholder="09XX-XXX-XXXX">
                    </div>
                    <div class="input-group">
                        <label>Relation</label>
                        <input type="text" name="emergency_contact_relation" value="<?= htmlspecialchars($user['emergency_contact_relation'] ?? '') ?>" placeholder="e.g., Mother, Spouse">
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="update_profile" class="btn primary">Save Changes</button>
                <a href="/e-barangay/resident/dashboard.php" class="btn secondary">Cancel</a>
            </div>

        </form>

    </main>

</body>
</html>