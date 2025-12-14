<?php
//includes/auth_check.php - ENHANCED VERSION
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* 100% NO BACK-BUTTON CACHING */
header("Expires: Tue, 01 Jan 2000 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

/* Universal authentication lock */
if (!isset($_SESSION['user_id'])) {
    header("Location: /e-barangay/public/login.php");
    exit;
}

function require_role($role) {
    if ($_SESSION['role'] !== $role) {
        header("Location: /e-barangay/public/login.php");
        exit;
    }
}

/* ✅ CRITICAL FIX: Always refresh session data from database on EVERY page load */
require_once __DIR__ . '/../config/database.php';

$stmt = $con->prepare("SELECT fullname, role, validation_status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Store old validation status to detect changes
    $old_validation = $_SESSION['validation_status'] ?? null;
    
    // Update session with fresh database values
    $_SESSION['fullname'] = $row['fullname'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['validation_status'] = $row['validation_status'];
    
    // ✅ NEW: If validation status changed, force a clean refresh
    // This ensures the user sees the updated permissions immediately
    if ($old_validation !== null && $old_validation !== $row['validation_status']) {
        // Clear any cached validation state
        if (isset($_SESSION['last_validation_check'])) {
            unset($_SESSION['last_validation_check']);
        }
        
        // For residents, redirect to dashboard to show new status
        if ($row['role'] === 'resident') {
            // Only redirect if not already on dashboard (prevent loop)
            $current_page = basename($_SERVER['PHP_SELF']);
            if ($current_page !== 'dashboard.php') {
                // Set a flag to show notification on dashboard
                $_SESSION['validation_changed'] = true;
                $_SESSION['validation_change_from'] = $old_validation;
                $_SESSION['validation_change_to'] = $row['validation_status'];
            }
        }
    }
} else {
    // User no longer exists in database - force logout
    session_destroy();
    header("Location: /e-barangay/public/login.php");
    exit;
}

$stmt->close();

/* ✅ NEW: Validation enforcement for residents */
// This ensures residents can't access protected pages if their status changes
if ($_SESSION['role'] === 'resident') {
    $protected_pages = ['request_clearance.php', 'report_case.php', 'my_clearances.php'];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // If on a protected page but not validated, redirect to dashboard
    if (in_array($current_page, $protected_pages) && $_SESSION['validation_status'] !== 'validated') {
        header("Location: /e-barangay/resident/dashboard.php");
        exit;
    }
}
?>