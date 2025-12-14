<?php
//secretary/get_resident_details.php - AJAX endpoint for resident data
header('Content-Type: application/json');

define('APP_RUNNING', true);
require_once __DIR__ . '/../includes/auth_check.php';
require_role('secretary');
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No user ID provided']);
    exit;
}

$user_id = intval($_GET['id']);

// Fetch complete resident data
$stmt = $con->prepare("
    SELECT 
        u.*,
        rp.*,
        CONCAT(
            COALESCE(rp.house_no, ''), ' ',
            COALESCE(rp.street, ''), ', ',
            COALESCE(rp.purok, ''), ', ',
            'Brgy. Cantil, Roxas, Oriental Mindoro'
        ) as full_address
    FROM users u
    LEFT JOIN resident_profiles rp ON u.user_id = rp.user_id
    WHERE u.user_id = ? AND u.role = 'resident'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Resident not found']);
    exit;
}

$resident = $result->fetch_assoc();

// Format birthdate
if ($resident['birthdate']) {
    $resident['birthdate'] = date('F j, Y', strtotime($resident['birthdate']));
}

// Format monetary values
if ($resident['monthly_income']) {
    $resident['monthly_income'] = number_format($resident['monthly_income'], 2);
}

echo json_encode($resident);
?>