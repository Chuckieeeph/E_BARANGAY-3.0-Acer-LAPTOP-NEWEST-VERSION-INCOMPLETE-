<?php
//secretary/dashboard.php
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/error_handler.php';

require_once __DIR__ . '/../includes/auth_check.php';
require_role('secretary');
require_once __DIR__ . '/../config/database.php';

$active_page = "dashboard";

// Dashboard counts
$pending_clearances = $con->query("SELECT COUNT(*) AS total FROM clearance_requests WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$pending_verifications = $con->query("SELECT COUNT(*) AS total FROM users WHERE role='resident' AND validation_status='pending'")->fetch_assoc()['total'] ?? 0;
$pending_cases = $con->query("SELECT COUNT(*) AS total FROM cases WHERE status='open'")->fetch_assoc()['total'] ?? 0;

// Recent pending verifications
$residents = $con->query("
    SELECT user_id, fullname, valid_id, validation_status, date_registered
    FROM users 
    WHERE role='resident' AND validation_status='pending'
    ORDER BY date_registered DESC
    LIMIT 5
");

// Pending clearance requests
$clearances = $con->query("
    SELECT cr.request_id, u.fullname, cr.purpose, cr.request_date
    FROM clearance_requests cr
    JOIN users u ON cr.resident_id = u.user_id
    WHERE cr.status = 'pending'
    ORDER BY cr.request_date DESC
    LIMIT 5
");

// Recent open cases
$cases = $con->query("
    SELECT c.case_id, u.fullname, c.case_type, c.case_date
    FROM cases c
    JOIN users u ON c.resident_id = u.user_id
    WHERE c.status = 'open'
    ORDER BY c.case_date DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard • e-Barangay</title>
    <link rel="stylesheet" href="../assets/css/secretary_dashboard.css">
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
                <h2>Secretary Dashboard</h2>
                <p class="muted">Manage barangay services and resident requests</p>
            </div>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                🔄 Refresh Data
            </button>
        </div>

        <!-- Statistics Cards (Clickable) -->
        <div class="stats-grid">
            
            <a href="clearance_requests.php" class="stat-card clickable">
                <div class="stat-icon pending">📄</div>
                <div class="stat-info">
                    <h3><?= $pending_clearances ?></h3>
                    <p>Pending Clearance Requests</p>
                </div>
                <?php if ($pending_clearances > 0): ?>
                    <span class="stat-badge"><?= $pending_clearances ?></span>
                <?php endif; ?>
            </a>

            <a href="verify_residents.php" class="stat-card clickable">
                <div class="stat-icon verify">👤</div>
                <div class="stat-info">
                    <h3><?= $pending_verifications ?></h3>
                    <p>Residents for Verification</p>
                </div>
                <?php if ($pending_verifications > 0): ?>
                    <span class="stat-badge warning"><?= $pending_verifications ?></span>
                <?php endif; ?>
            </a>

            <a href="cases.php" class="stat-card clickable">
                <div class="stat-icon cases">📋</div>
                <div class="stat-info">
                    <h3><?= $pending_cases ?></h3>
                    <p>Pending Case Reports</p>
                </div>
                <?php if ($pending_cases > 0): ?>
                    <span class="stat-badge danger"><?= $pending_cases ?></span>
                <?php endif; ?>
            </a>

        </div>

        <!-- Quick Actions -->
        <div class="section-header">
            <h3>⚡ Quick Actions</h3>
        </div>
        <div class="quick-actions">
            <a href="verify_residents.php" class="action-card">
                <span class="action-icon">✅</span>
                <h4>Verify Residents</h4>
                <p>Review pending ID validations</p>
                <?php if ($pending_verifications > 0): ?>
                    <span class="action-badge"><?= $pending_verifications ?></span>
                <?php endif; ?>
            </a>
            <a href="clearance_requests.php" class="action-card">
                <span class="action-icon">📄</span>
                <h4>Process Clearances</h4>
                <p>Review clearance requests</p>
                <?php if ($pending_clearances > 0): ?>
                    <span class="action-badge"><?= $pending_clearances ?></span>
                <?php endif; ?>
            </a>
            <a href="cases.php" class="action-card">
                <span class="action-icon">📋</span>
                <h4>Manage Cases</h4>
                <p>Handle case reports</p>
                <?php if ($pending_cases > 0): ?>
                    <span class="action-badge"><?= $pending_cases ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Pending Items Section -->
        <div class="dashboard-row">

            <!-- Pending Verifications -->
            <div class="card-section">
                <div class="section-header">
                    <h3>👤 Residents Pending Verification</h3>
                    <a href="verify_residents.php" class="view-all">View All →</a>
                </div>
                <div class="mini-table">
                    <?php if ($residents && $residents->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Valid ID</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($r = $residents->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($r['fullname']) ?></strong></td>
                                    <td>
                                        <?php if($r['valid_id']): ?>
                                            <a href="../public/uploads/valid_ids/<?= htmlspecialchars($r['valid_id']) ?>" 
                                               target="_blank" class="btn-view">View ID</a>
                                        <?php else: ?>
                                            <span class="text-muted">No ID</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($r['date_registered'])) ?></td>
                                    <td>
                                        <a href="verify_residents.php" class="btn-mini">Review</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state-mini">
                            <span>✅</span>
                            <p>No pending verifications</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Clearances -->
            <div class="card-section">
                <div class="section-header">
                    <h3>📄 Pending Clearance Requests</h3>
                    <a href="clearance_requests.php" class="view-all">View All →</a>
                </div>
                <div class="mini-table">
                    <?php if ($clearances && $clearances->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Resident</th>
                                    <th>Purpose</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($c = $clearances->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['fullname']) ?></strong></td>
                                    <td><?= htmlspecialchars(substr($c['purpose'], 0, 30)) ?>...</td>
                                    <td><?= date('M d, Y', strtotime($c['request_date'])) ?></td>
                                    <td>
                                        <a href="clearance_requests.php" class="btn-mini">Process</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state-mini">
                            <span>📭</span>
                            <p>No pending clearance requests</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Recent Cases -->
        <div class="card-section full-width">
            <div class="section-header">
                <h3>📋 Recent Case Reports</h3>
                <a href="cases.php" class="view-all">View All →</a>
            </div>
            <div class="mini-table">
                <?php if ($cases && $cases->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Resident</th>
                                <th>Case Type</th>
                                <th>Date Reported</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($case = $cases->fetch_assoc()): ?>
                            <tr>
                                <td><span class="case-id">#<?= $case['case_id'] ?></span></td>
                                <td><strong><?= htmlspecialchars($case['fullname']) ?></strong></td>
                                <td><?= htmlspecialchars($case['case_type']) ?></td>
                                <td><?= date('M d, Y', strtotime($case['case_date'])) ?></td>
                                <td>
                                    <a href="cases.php" class="btn-mini">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state-mini">
                        <span>📭</span>
                        <p>No recent case reports</p>
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