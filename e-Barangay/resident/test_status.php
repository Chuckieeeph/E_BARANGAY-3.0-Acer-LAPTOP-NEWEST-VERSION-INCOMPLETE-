<?php
// TEST FILE - Check validation status
// Access: /e-barangay/resident/test_status.php

session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = $_SESSION['user_id'];

// Get from session
$session_status = $_SESSION['validation_status'] ?? 'not set';

// Get from database
$stmt = $con->prepare("SELECT validation_status, fullname FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$db_data = $result->fetch_assoc();
$db_status = $db_data['validation_status'] ?? 'not found';
$fullname = $db_data['fullname'] ?? 'Unknown';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Status Check</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        h2 { color: #006A2E; }
        .status { font-size: 24px; font-weight: bold; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .validated { background: #d1fae5; color: #065f46; }
        .pending { background: #fef3c7; color: #92400e; }
        .rejected { background: #fee2e2; color: #991b1b; }
        .unvalidated { background: #f3f4f6; color: #6b7280; }
        button { padding: 10px 20px; background: #006A2E; color: white; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #004a1f; }
    </style>
</head>
<body>

<div class="box">
    <h2>🔍 Validation Status Checker</h2>
    <p><strong>User:</strong> <?= htmlspecialchars($fullname) ?> (ID: <?= $user_id ?>)</p>
    
    <h3>Session Status:</h3>
    <div class="status <?= $session_status ?>">
        <?= ucfirst($session_status) ?>
    </div>
    
    <h3>Database Status:</h3>
    <div class="status <?= $db_status ?>">
        <?= ucfirst($db_status) ?>
    </div>
    
    <?php if ($session_status !== $db_status): ?>
        <div style="background: #fee2e2; padding: 15px; border-radius: 5px; color: #991b1b; margin: 10px 0;">
            <strong>⚠️ MISMATCH DETECTED!</strong><br>
            Session and database don't match. This is why updates aren't showing.
        </div>
        
        <button onclick="location.href='?refresh=1'">🔄 Refresh Status</button>
    <?php else: ?>
        <div style="background: #d1fae5; padding: 15px; border-radius: 5px; color: #065f46; margin: 10px 0;">
            <strong>✅ Status Synchronized!</strong>
        </div>
    <?php endif; ?>
    
    <hr style="margin: 20px 0;">
    
    <h3>Menu Items That Should Show:</h3>
    <?php if ($db_status === 'validated'): ?>
        <ul>
            <li>✅ Dashboard</li>
            <li>✅ Request Clearance</li>
            <li>✅ Report Case</li>
            <li>✅ My Documents</li>
        </ul>
    <?php else: ?>
        <ul>
            <li>✅ Dashboard</li>
            <li>✅ Upload Valid ID</li>
        </ul>
    <?php endif; ?>
    
    <hr style="margin: 20px 0;">
    
    <button onclick="location.href='dashboard.php'">Go to Dashboard</button>
    <button onclick="location.reload()">Reload Page</button>
</div>

<?php
// Handle refresh request
if (isset($_GET['refresh'])) {
    $_SESSION['validation_status'] = $db_status;
    header("Location: test_status.php");
    exit;
}
?>

</body>
</html>