<?php
//admin/dashboard.php
require_once __DIR__ . '/../includes/error_handler.php';

require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

// Fetch dashboard statistics
$pendingClearances = $con->query("SELECT COUNT(*) as count FROM clearance_requests WHERE status='pending'")->fetch_assoc()['count'];
$pendingVerification = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='pending'")->fetch_assoc()['count'];
$totalResidents = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='validated'")->fetch_assoc()['count'];
$activeCases = $con->query("SELECT COUNT(*) as count FROM cases WHERE status='open'")->fetch_assoc()['count'];

// This month stats
$thisMonth = date('Y-m');
$clearancesThisMonth = $con->query("SELECT COUNT(*) as count FROM clearance_requests WHERE DATE_FORMAT(request_date, '%Y-%m') = '$thisMonth'")->fetch_assoc()['count'];
$usersThisWeek = $con->query("SELECT COUNT(*) as count FROM users WHERE date_registered >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];

// Recent pending clearances (Top 5)
$recentClearances = $con->query("
    SELECT cr.request_id, u.fullname, cr.purpose, cr.request_date, cr.status
    FROM clearance_requests cr
    JOIN users u ON cr.resident_id = u.user_id
    WHERE cr.status = 'pending'
    ORDER BY cr.request_date DESC
    LIMIT 5
");

// Recent pending verifications (Top 5)
$recentVerifications = $con->query("
    SELECT user_id, fullname, email, date_registered
    FROM users
    WHERE role='resident' AND validation_status='pending'
    ORDER BY date_registered DESC
    LIMIT 5
");

// Recent cases (Top 5)
$recentCases = $con->query("
    SELECT c.case_id, u.fullname, c.case_type, c.status, c.case_date
    FROM cases c
    JOIN users u ON c.resident_id = u.user_id
    ORDER BY c.case_date DESC
    LIMIT 5
");

// Chart data - Clearances by month (last 6 months)
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $count = $con->query("SELECT COUNT(*) as count FROM clearance_requests WHERE DATE_FORMAT(request_date, '%Y-%m') = '$month'")->fetch_assoc()['count'];
    $chartData[] = [
        'month' => date('M Y', strtotime("-$i months")),
        'count' => $count
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard • e-Barangay</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h2>Admin Dashboard</h2>
                <p class="muted">Real-time barangay system overview</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-secondary" onclick="window.location.reload()">
                    🔄 Refresh
                </button>
                <button class="btn btn-primary" onclick="location.href='user_management.php'">
                    👥 Manage Users
                </button>
            </div>
        </div>

        <!-- Main Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card clickable" onclick="location.href='clearance_requests.php'">
                <div class="stat-icon pending">📄</div>
                <div class="stat-info">
                    <h3 id="pending-clearances"><?= $pendingClearances ?></h3>
                    <p>Pending Clearances</p>
                </div>
                <?php if ($pendingClearances > 0): ?>
                    <span class="stat-badge"><?= $pendingClearances ?></span>
                <?php endif; ?>
            </div>

            <div class="stat-card clickable" onclick="location.href='verify_residents.php'">
                <div class="stat-icon verify">👤</div>
                <div class="stat-info">
                    <h3 id="pending-verifications"><?= $pendingVerification ?></h3>
                    <p>Pending Verifications</p>
                </div>
                <?php if ($pendingVerification > 0): ?>
                    <span class="stat-badge warning"><?= $pendingVerification ?></span>
                <?php endif; ?>
            </div>

            <div class="stat-card clickable" onclick="location.href='user_management.php'">
                <div class="stat-icon residents">👥</div>
                <div class="stat-info">
                    <h3 id="total-residents"><?= $totalResidents ?></h3>
                    <p>Total Residents</p>
                </div>
            </div>

            <div class="stat-card clickable" onclick="location.href='cases.php'">
                <div class="stat-icon cases">📋</div>
                <div class="stat-info">
                    <h3><?= $activeCases ?></h3>
                    <p>Active Cases</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats Summary -->
        <div class="summary-cards">
            <div class="summary-card">
                <span class="summary-icon">📊</span>
                <div>
                    <h4><?= $clearancesThisMonth ?></h4>
                    <p>Clearances This Month</p>
                </div>
            </div>
            <div class="summary-card">
                <span class="summary-icon">📅</span>
                <div>
                    <h4><?= $usersThisWeek ?></h4>
                    <p>New Users This Week</p>
                </div>
            </div>
            <div class="summary-card">
                <span class="summary-icon">✅</span>
                <div>
                    <h4><?= $totalResidents ?></h4>
                    <p>Validated Residents</p>
                </div>
            </div>
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
                <?php if ($pendingVerification > 0): ?>
                    <span class="action-badge"><?= $pendingVerification ?></span>
                <?php endif; ?>
            </a>
            <a href="clearance_requests.php" class="action-card">
                <span class="action-icon">📄</span>
                <h4>Review Clearances</h4>
                <p>Process clearance requests</p>
                <?php if ($pendingClearances > 0): ?>
                    <span class="action-badge"><?= $pendingClearances ?></span>
                <?php endif; ?>
            </a>
            <a href="cases.php" class="action-card">
                <span class="action-icon">📋</span>
                <h4>Manage Cases</h4>
                <p>View and handle cases</p>
            </a>
            <a href="user_management.php" class="action-card">
                <span class="action-icon">👥</span>
                <h4>User Management</h4>
                <p>Manage system users</p>
            </a>
        </div>

        <!-- Charts and Tables Row -->
        <div class="dashboard-row">
            
            <!-- Chart Section -->
            <div class="chart-section">
                <div class="section-header">
                    <h3>📈 Clearance Requests Trend</h3>
                </div>
                <div class="chart-container">
                    <canvas id="clearanceChart"></canvas>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="activity-section">
                <div class="section-header">
                    <h3>🔔 Recent Activity</h3>
                </div>
                <div class="activity-feed" id="activityFeed">
                    <div class="activity-item">
                        <span class="activity-icon">⏳</span>
                        <div class="activity-content">
                            <p>Loading recent activities...</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pending Items Tables -->
        <div class="tables-row">
            
            <!-- Pending Clearances -->
            <div class="table-section">
                <div class="section-header">
                    <h3>📄 Pending Clearance Requests</h3>
                    <a href="clearance_requests.php" class="view-all">View All →</a>
                </div>
                <div class="mini-table">
                    <?php if ($recentClearances && $recentClearances->num_rows > 0): ?>
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
                                <?php while($row = $recentClearances->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                                    <td><?= htmlspecialchars(substr($row['purpose'], 0, 30)) ?>...</td>
                                    <td><?= date('M d, Y', strtotime($row['request_date'])) ?></td>
                                    <td>
                                        <a href="review_clearance.php?id=<?= $row['request_id'] ?>" class="btn-mini">Review</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state-mini">
                            <span>📭</span>
                            <p>No pending clearances</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Verifications -->
            <div class="table-section">
                <div class="section-header">
                    <h3>👤 Pending Verifications</h3>
                    <a href="verify_residents.php" class="view-all">View All →</a>
                </div>
                <div class="mini-table">
                    <?php if ($recentVerifications && $recentVerifications->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Registered</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $recentVerifications->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['date_registered'])) ?></td>
                                    <td>
                                        <a href="verify_residents.php?id=<?= $row['user_id'] ?>" class="btn-mini">Verify</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state-mini">
                            <span>📭</span>
                            <p>No pending verifications</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Recent Cases -->
        <div class="table-section full-width">
            <div class="section-header">
                <h3>📋 Recent Cases</h3>
                <a href="cases.php" class="view-all">View All →</a>
            </div>
            <div class="mini-table">
                <?php if ($recentCases && $recentCases->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Case ID</th>
                                <th>Resident</th>
                                <th>Case Type</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recentCases->fetch_assoc()): ?>
                            <tr>
                                <td><span class="case-id">#<?= $row['case_id'] ?></span></td>
                                <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                                <td><?= htmlspecialchars($row['case_type']) ?></td>
                                <td><span class="badge status-<?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($row['case_date'])) ?></td>
                                <td>
                                    <a href="view_case.php?id=<?= $row['case_id'] ?>" class="btn-mini">View</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state-mini">
                        <span>📭</span>
                        <p>No recent cases</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>

</div>

<script>
// Chart.js Implementation
const ctx = document.getElementById('clearanceChart').getContext('2d');
const chartData = <?= json_encode($chartData) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.map(d => d.month),
        datasets: [{
            label: 'Clearance Requests',
            data: chartData.map(d => d.count),
            borderColor: '#006A2E',
            backgroundColor: 'rgba(0, 106, 46, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Load Recent Activity
async function loadRecentActivity() {
    try {
        const response = await fetch('get_recent_activity.php');
        const activities = await response.json();
        
        const feed = document.getElementById('activityFeed');
        if (activities.length === 0) {
            feed.innerHTML = '<div class="empty-state-mini"><span>📭</span><p>No recent activity</p></div>';
            return;
        }
        
        feed.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <span class="activity-icon">${activity.icon}</span>
                <div class="activity-content">
                    <p><strong>${activity.user}</strong> ${activity.action}</p>
                    <small>${activity.time}</small>
                </div>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error loading activity:', error);
    }
}

// Auto-refresh every 30 seconds
setInterval(loadRecentActivity, 30000);
loadRecentActivity();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<link rel="stylesheet" href="/e-barangay/assets/css/footer.css">
</body>
</html>