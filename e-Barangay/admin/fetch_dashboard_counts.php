<?php
require_once __DIR__ . '/../config/database.php';

// Get pending clearance requests
$pendingQuery = "SELECT COUNT(*) AS total FROM clearance_requests WHERE status = 'Pending'";
$pendingResult = $conn->query($pendingQuery);
$pendingCount = $pendingResult ? $pendingResult->fetch_assoc()['total'] : 0;

// Get residents for verification
$verifyQuery = "SELECT COUNT(*) AS total FROM residents WHERE status = 'For Verification'";
$verifyResult = $conn->query($verifyQuery);
$verifyCount = $verifyResult ? $verifyResult->fetch_assoc()['total'] : 0;

// Get total registered residents
$residentsQuery = "SELECT COUNT(*) AS total FROM residents";
$residentsResult = $conn->query($residentsQuery);
$residentsCount = $residentsResult ? $residentsResult->fetch_assoc()['total'] : 0;

echo json_encode([
    'pending-clearances' => (int)$pendingCount,
    'verification' => (int)$verifyCount,
    'total-residents' => (int)$residentsCount
]);
