<?php
// resident/sidebar.php
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

// IMPORTANT: Always fetch fresh validation status from database
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $con->prepare("SELECT validation_status FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['validation_status'] = $row['validation_status'];
    }
    $stmt->close();
}

$fullname = $_SESSION['fullname'] ?? 'Resident';
$validation = $_SESSION['validation_status'] ?? 'unvalidated';
$current = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="/e-barangay/assets/css/resident_sidebar.css">

<aside class="sidebar">
    <div class="brand">
        <img src="/e-barangay/assets/images/logo.jpg" class="logo" alt="logo">
        <h3>e-Barangay Resident</h3>
        <small>Brgy. Cantil</small>
    </div>

    <nav class="menu">

        <a href="/e-barangay/resident/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
            📊 Dashboard
        </a>

        <?php if ($validation === 'validated'): ?>
            <!-- Validated Users Menu -->
            <a href="/e-barangay/resident/request_clearance.php" class="<?= $current === 'request_clearance.php' ? 'active' : '' ?>">
                📄 Request Clearance
            </a>
            <a href="/e-barangay/resident/report_case.php" class="<?= $current === 'report_case.php' ? 'active' : '' ?>">
                📋 Report Case
            </a>
            <a href="/e-barangay/resident/my_clearances.php" class="<?= $current === 'my_clearances.php' ? 'active' : '' ?>">
                📁 My Documents
            </a>
        <?php else: ?>
            <!-- Unvalidated Users Menu -->
            <a href="/e-barangay/resident/upload_valid_id.php" class="<?= $current === 'upload_valid_id.php' ? 'active' : '' ?>">
                🆔 Upload Valid ID
            </a>
        <?php endif; ?>

        <a href="/e-barangay/public/logout.php" class="logout">
            🚪 Logout
        </a>

    </nav>
</aside>

<!-- Debug info (remove after testing) -->
<!-- Current validation status: <?= $validation ?> -->