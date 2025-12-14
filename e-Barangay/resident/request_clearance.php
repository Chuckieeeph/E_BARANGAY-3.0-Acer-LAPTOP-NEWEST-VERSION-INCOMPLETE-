<?php
//resident/request_clearance.php
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/auth_check.php';
require_role('resident');
require_once __DIR__ . '/../config/database.php';

$active_page = "request_clearance";

$user_id = $_SESSION['user_id'];
$validation = $_SESSION['validation_status'] ?? 'unvalidated';


$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $purpose = trim($_POST['purpose'] ?? '');
    
    if (empty($purpose)) {
        $message = "Please provide a purpose for the clearance.";
        $messageType = "error";
    } else {
        $stmt = $con->prepare("INSERT INTO clearance_requests (resident_id, purpose, status, request_date) VALUES (?, ?, 'pending', NOW())");
        $stmt->bind_param("is", $user_id, $purpose);
        
        if ($stmt->execute()) {
            $message = "Clearance request submitted successfully! You will be notified once processed.";
            $messageType = "success";
        } else {
            $message = "Failed to submit clearance request. Please try again.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Get user's clearance history
$clearances = $con->query("
    SELECT request_id, purpose, status, request_date, processed_date
    FROM clearance_requests
    WHERE resident_id = $user_id
    ORDER BY request_date DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Clearance • e-Barangay</title>
    <link rel="stylesheet" href="../assets/css/resident_dashboard.css">
</head>
<body>

<div class="dashboard-container">

    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2>Request Barangay Clearance</h2>
                <p class="muted">Submit a new clearance request</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <strong>
                    <?php if ($messageType === 'success'): ?>✅ Success<?php endif; ?>
                    <?php if ($messageType === 'error'): ?>⚠️ Error<?php endif; ?>
                </strong>
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>

        <!-- Request Form -->
        <div class="form-card">
            <h3>📄 New Clearance Request</h3>
            <form method="POST">
                <div class="input-group">
                    <label for="purpose">Purpose of Clearance <span class="required">*</span></label>
                    <textarea id="purpose" name="purpose" rows="5" 
                              placeholder="Example: Employment, School Requirements, Business Permit, etc." 
                              required></textarea>
                    <small class="hint">Please state the specific purpose for requesting this clearance</small>
                </div>
                <button type="submit" class="btn btn-primary">Submit Request</button>
            </form>
        </div>

        <!-- Request History -->
        <div class="table-section">
            <div class="section-header">
                <h3>📋 My Clearance Requests</h3>
            </div>
            <div class="mini-table">
                <?php if ($clearances && $clearances->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Request ID</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th>Processed Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $clearances->fetch_assoc()): ?>
                            <tr>
                                <td><span class="request-id">#<?= $row['request_id'] ?></span></td>
                                <td><?= htmlspecialchars(substr($row['purpose'], 0, 50)) ?>...</td>
                                <td>
                                    <span class="badge status-<?= $row['status'] ?>">
                                        <?php if($row['status'] === 'pending'): ?>⏳<?php endif; ?>
                                        <?php if($row['status'] === 'approved'): ?>✅<?php endif; ?>
                                        <?php if($row['status'] === 'rejected'): ?>❌<?php endif; ?>
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['request_date'])) ?></td>
                                <td><?= $row['processed_date'] ? date('M d, Y', strtotime($row['processed_date'])) : '—' ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state-mini">
                        <span>📭</span>
                        <p>No clearance requests yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<link rel="stylesheet" href="/e-barangay/assets/css/footer.css">

</body>
</html>