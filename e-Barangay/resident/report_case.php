<?php
//resident/report_case.php
define('APP_RUNNING', true);
require_once __DIR__ . '/../includes/error_handler.php';

require_once __DIR__ . '/../includes/auth_check.php';
require_role('resident');
require_once __DIR__ . '/../config/database.php';

$active_page = "report_case";

$user_id = $_SESSION['user_id'];
$validation = $_SESSION['validation_status'] ?? 'unvalidated';


$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $case_type = trim($_POST['case_type'] ?? '');
    $case_title = trim($_POST['case_title'] ?? '');
    $case_description = trim($_POST['case_description'] ?? '');
    $case_date = $_POST['case_date'] ?? date('Y-m-d');
    
    if (empty($case_type) || empty($case_title) || empty($case_description)) {
        $message = "Please fill in all required fields.";
        $messageType = "error";
    } else {
        $stmt = $con->prepare("INSERT INTO cases (resident_id, case_type, case_title, case_description, case_date, status) VALUES (?, ?, ?, ?, ?, 'open')");
        $stmt->bind_param("issss", $user_id, $case_type, $case_title, $case_description, $case_date);
        
        if ($stmt->execute()) {
            $message = "Case reported successfully! The barangay will review your report.";
            $messageType = "success";
        } else {
            $message = "Failed to submit case report. Please try again.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Get user's case history
$cases = $con->query("
    SELECT case_id, case_type, case_title, case_description, case_date, status
    FROM cases
    WHERE resident_id = $user_id
    ORDER BY case_date DESC
    LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Case • e-Barangay</title>
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
                <h2>Report Case/Incident</h2>
                <p class="muted">Submit a case or incident report to the barangay</p>
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

        <!-- Report Form -->
        <div class="form-card">
            <h3>📋 New Case Report</h3>
            <form method="POST">
                <div class="input-group">
                    <label for="case_type">Case Type <span class="required">*</span></label>
                    <select id="case_type" name="case_type" required>
                        <option value="">Select Case Type</option>
                        <option value="Noise Complaint">Noise Complaint</option>
                        <option value="Dispute">Dispute/Conflict</option>
                        <option value="Theft">Theft</option>
                        <option value="Vandalism">Vandalism</option>
                        <option value="Public Disturbance">Public Disturbance</option>
                        <option value="Property Issue">Property Issue</option>
                        <option value="Safety Concern">Safety Concern</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="case_title">Case Title <span class="required">*</span></label>
                    <input type="text" id="case_title" name="case_title" 
                           placeholder="Brief title for the case" 
                           required>
                </div>

                <div class="input-group">
                    <label for="case_description">Case Description <span class="required">*</span></label>
                    <textarea id="case_description" name="case_description" rows="6" 
                              placeholder="Provide detailed information about the incident or case..." 
                              required></textarea>
                    <small class="hint">Include date, time, location, and any relevant details</small>
                </div>

                <div class="input-group">
                    <label for="case_date">Incident Date</label>
                    <input type="date" id="case_date" name="case_date" 
                           value="<?= date('Y-m-d') ?>" 
                           max="<?= date('Y-m-d') ?>">
                </div>

                <button type="submit" class="btn btn-primary">Submit Report</button>
            </form>
        </div>

        <!-- Case History -->
        <div class="table-section">
            <div class="section-header">
                <h3>📋 My Reported Cases</h3>
            </div>
            <div class="mini-table">
                <?php if ($cases && $cases->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Type</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Date Reported</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $cases->fetch_assoc()): ?>
                            <tr>
                                <td><span class="case-id">#<?= $row['case_id'] ?></span></td>
                                <td><?= htmlspecialchars($row['case_type']) ?></td>
                                <td><?= htmlspecialchars(substr($row['case_title'], 0, 40)) ?>...</td>
                                <td>
                                    <span class="badge status-<?= $row['status'] ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($row['case_date'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state-mini">
                        <span>📭</span>
                        <p>No cases reported yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>