<?php
// secretary/sidebar.php
// Prevent direct access
if (!defined('APP_RUNNING')) {
    die('Direct access not permitted');
}

$fullname = $_SESSION['fullname'] ?? 'Barangay Secretary';
$current = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="/e-barangay/assets/css/secretary_sidebar.css">

<aside class="sidebar">
    <div class="brand">
        <img src="/e-barangay/assets/images/logo.jpg" class="logo" alt="logo">
        <h3>e-Barangay Secretary</h3>
        <small>Brgy. Cantil</small>
    </div>

    <nav class="menu">

        <a href="/e-barangay/secretary/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
            📊 Dashboard
        </a>

        <a href="/e-barangay/secretary/verify_residents.php" class="<?= $current === 'verify_residents.php' ? 'active' : '' ?>">
            ✅ Verify Residents
        </a>

        <a href="/e-barangay/secretary/clearance_requests.php" class="<?= $current === 'clearance_requests.php' ? 'active' : '' ?>">
            📄 Clearance Requests
        </a>

        <a href="/e-barangay/secretary/cases.php" class="<?= $current === 'cases.php' ? 'active' : '' ?>">
            📋 Case Reports
        </a>

        <a href="/e-barangay/secretary/resident_directory.php" class="<?= $active_page === 'resident_directory' ? 'active' : '' ?>">
            📂 Resident Directory
        </a>

        <a href="/e-barangay/public/logout.php" class="logout">
            🚪 Logout
        </a>

    </nav>
</aside>