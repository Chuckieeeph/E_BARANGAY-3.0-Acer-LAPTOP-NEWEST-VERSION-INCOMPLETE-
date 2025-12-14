<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

// Fetch latest counts
$data = [];

// Pending clearance requests
$q1 = $con->query("SELECT COUNT(*) AS total FROM clearance_requests WHERE status='pending'");
$data['pending_clearances'] = $q1->fetch_assoc()['total'] ?? 0;

// Residents for verification
$q2 = $con->query("SELECT COUNT(*) AS total FROM users WHERE role='resident' AND validation_status='pending'");
$data['pending_verifications'] = $q2->fetch_assoc()['total'] ?? 0;

// Total validated residents
$q3 = $con->query("SELECT COUNT(*) AS total FROM users WHERE role='resident' AND validation_status='validated'");
$data['total_residents'] = $q3->fetch_assoc()['total'] ?? 0;

header('Content-Type: application/json');
echo json_encode($data);
exit;
?>
