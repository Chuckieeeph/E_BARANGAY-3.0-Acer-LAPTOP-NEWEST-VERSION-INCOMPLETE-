<?php
//resident/dashboard.php - UPDATED to always show buttons
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('resident');
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];

// ✅ Always fetch fresh validation status
$stmt = $con->prepare("SELECT validation_status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $_SESSION['validation_status'] = $row['validation_status'];
}

$fullname = $_SESSION['fullname'] ?? 'Resident';
$validation = $_SESSION['validation_status'] ?? 'unvalidated';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Portal - e-Barangay</title>
    <link rel="stylesheet" href="/e-barangay/assets/css/resident_landing.css">
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <img src="/e-barangay/assets/images/logo.jpg" alt="Logo" class="nav-logo">
                <span class="nav-title">e-Barangay Portal</span>
            </div>
            <div class="nav-right">
                <a href="/e-barangay/resident/profile.php" class="profile-btn" title="My Profile">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
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

    <!-- Main Content -->
    <main class="landing-container">
        
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome, <span class="highlight"><?= htmlspecialchars($fullname); ?></span>!</h1>
            <p class="subtitle">What would you like to do today?</p>
        </div>

        <!-- Action Cards - ALWAYS SHOW -->
        <div class="action-grid">
            
            <!-- Request Clearance Card -->
            <a href="#" onclick="checkValidation('clearance'); return false;" class="action-card clearance-card">
                <div class="card-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                </div>
                <h2>Request Clearance</h2>
                <p>Apply for barangay clearance certificate</p>
                <div class="card-arrow">→</div>
            </a>

            <!-- Report Case Card -->
            <a href="#" onclick="checkValidation('case'); return false;" class="action-card case-card">
                <div class="card-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="18" x2="12" y2="12"></line>
                        <line x1="12" y1="8" x2="12" y2="8"></line>
                    </svg>
                </div>
                <h2>Report a Case</h2>
                <p>File an incident or complaint report</p>
                <div class="card-arrow">→</div>
            </a>

        </div>

        <!-- Quick Info Section -->
        <?php if ($validation === 'validated'): ?>
        <div class="info-section">
            <div class="info-card">
                <span class="info-icon">✅</span>
                <span>Account Validated</span>
            </div>
            <div class="info-card">
                <span class="info-icon">📋</span>
                <a href="/e-barangay/resident/my_clearances.php">View My Documents</a>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="landing-footer">
        <p>&copy; <?= date('Y') ?> e-Barangay System - Brgy. Cantil</p>
    </footer>

    <script>
        // Check validation status before allowing access
        const validationStatus = '<?= $validation ?>';
        
        function checkValidation(type) {
            if (validationStatus !== 'validated') {
                // Show custom alert
                const message = validationStatus === 'pending' 
                    ? '⏳ Your account is pending validation. Please wait for admin approval.'
                    : validationStatus === 'rejected'
                    ? '❌ Your ID was rejected. Please upload a new valid ID to access this service.'
                    : '⚠️ Your account is not validated. Please upload a valid ID to access this service.';
                
                if (confirm(message + '\n\nWould you like to upload your valid ID now?')) {
                    window.location.href = '/e-barangay/resident/upload_valid_id.php';
                }
                return false;
            }
            
            // If validated, allow access
            if (type === 'clearance') {
                window.location.href = '/e-barangay/resident/request_clearance.php';
            } else if (type === 'case') {
                window.location.href = '/e-barangay/resident/report_case.php';
            }
        }
    </script>

</body>
</html>