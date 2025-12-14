<?php
//admin/user_management.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

// Fetch all users + residents data
$query = "
SELECT 
    user_id,
    fullname,
    email,
    role,
    contact_no,
    address,
    gender,
    birthdate,
    civil_status,
    nationality,
    validation_status,
    date_registered,
    valid_id
FROM users
ORDER BY 
    CASE 
        WHEN role = 'admin' THEN 1
        WHEN role = 'secretary' THEN 2
        ELSE 3
    END,
    date_registered ASC
";


$result = $con->query($query);

// Create array to store users for sequential numbering
$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get counts by role
$adminCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='admin'")->fetch_assoc()['count'];
$secretaryCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='secretary'")->fetch_assoc()['count'];
$residentCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident'")->fetch_assoc()['count'];
$pendingCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='pending'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management • e-Barangay Admin</title>
    <link rel="stylesheet" href="../assets/css/user_management.css">
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
                <h2>User Management</h2>
                <p class="muted">Manage all system users and their information</p>
            </div>
            <button class="btn btn-primary" onclick="window.location.reload()">
                🔄 Refresh Data
            </button>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon admin">👤</div>
                <div class="stat-info">
                    <h3><?= $adminCount ?></h3>
                    <p>Administrators</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon secretary">📝</div>
                <div class="stat-info">
                    <h3><?= $secretaryCount ?></h3>
                    <p>Secretaries</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon resident">👥</div>
                <div class="stat-info">
                    <h3><?= $residentCount ?></h3>
                    <p>Residents</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon pending">⏳</div>
                <div class="stat-info">
                    <h3><?= $pendingCount ?></h3>
                    <p>Pending Validation</p>
                </div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="table-controls">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Search by name, email, or role..." onkeyup="filterTable()">
            </div>
            <div class="filter-box">
                <select id="roleFilter" onchange="filterTable()">
                    <option value="">🔘 All Roles</option>
                    <option value="admin" data-color="admin">👑 Admin</option>
                    <option value="secretary" data-color="secretary">📝 Secretary</option>
                    <option value="resident" data-color="resident">👤 Resident</option>
                </select>
                <select id="statusFilter" onchange="filterTable()">
                    <option value="">🔘 All Status</option>
                    <option value="validated" data-color="validated">✅ Validated</option>
                    <option value="pending" data-color="pending">⏳ Pending</option>
                    <option value="rejected" data-color="rejected">❌ Rejected</option>
                    <option value="authorized" data-color="authorized">🔐 Authorized</option>
                </select>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table class="users-table" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Gender</th>
                        <th>Birthdate</th>
                        <th>Civil Status</th>
                        <th>Nationality</th>
                        <th>Contact No.</th>
                        <th>Address</th>
                        <th>Validation</th>
                        <th>Valid ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php 
                        $displayNumber = 1; // Sequential display number
                        foreach ($users as $row): 
                        ?>
                        <tr data-user-id="<?= $row['user_id']; ?>">
                            <td>
                                <span class="user-id role-<?= $row['role']; ?>">
                                    #<?= $displayNumber; ?>
                                </span>
                            </td>
                            <td><strong><?= htmlspecialchars($row['fullname']); ?></strong></td>
                            <td><?= htmlspecialchars($row['email']); ?></td>
                            <td>
                                <span class="badge role-<?= $row['role']; ?>">
                                    <?= ucfirst($row['role']); ?>
                                </span>
                            </td>
                            <td><?= $row['gender'] ?? '—'; ?></td>
                            <td><?= $row['birthdate'] ?? '—'; ?></td>
                            <td><?= $row['civil_status'] ?? '—'; ?></td>
                            <td><?= $row['nationality'] ?? '—'; ?></td>
                            <td><?= htmlspecialchars($row['contact_no'] ?? '—'); ?></td>
                            <td class="address-cell"><?= htmlspecialchars($row['address'] ?? '—'); ?></td>
                            <td>
                                <?php if ($row['role'] === 'admin' || $row['role'] === 'secretary'): ?>
                                    <span class="badge status-authorized">
                                        Authorized
                                    </span>
                                <?php else: ?>
                                    <span class="badge status-<?= $row['validation_status']; ?>">
                                        <?= ucfirst($row['validation_status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['valid_id']): ?>
                                    <a href="../uploads/valid_ids/<?= htmlspecialchars($row['valid_id']); ?>" 
                                       target="_blank" 
                                       class="btn-view-id" 
                                       title="View ID">
                                        📄 View
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action view" onclick="viewUser(<?= $row['user_id']; ?>)" title="View Details">
                                        👁️
                                    </button>
                                    <?php if ($row['role'] !== 'admin'): ?>
                                        <button class="btn-action edit" onclick="editUser(<?= $row['user_id']; ?>)" title="Edit User">
                                            ✏️
                                        </button>
                                        <button class="btn-action delete" onclick="deleteUser(<?= $row['user_id']; ?>)" title="Delete User">
                                            🗑️
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action disabled" disabled title="Admin cannot be modified">
                                            🔒
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php 
                        $displayNumber++;
                        endforeach; 
                        ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="13" class="no-data">
                                <div class="empty-state">
                                    <span class="empty-icon">📭</span>
                                    <p>No users found in the system</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</div>

<script>
// Search and Filter Function
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const fullname = row.cells[1]?.textContent.toLowerCase() || '';
        const email = row.cells[2]?.textContent.toLowerCase() || '';
        const role = row.cells[3]?.textContent.toLowerCase() || '';
        const status = row.cells[10]?.textContent.toLowerCase() || '';

        const matchesSearch = fullname.includes(searchInput) || 
                            email.includes(searchInput) || 
                            role.includes(searchInput);
        const matchesRole = roleFilter === '' || role.includes(roleFilter);
        const matchesStatus = statusFilter === '' || status.includes(statusFilter);

        if (matchesSearch && matchesRole && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// View User Details
function viewUser(userId) {
    alert('View user details for ID: ' + userId);
    // TODO: Implement view user modal
}

// Edit User
function editUser(userId) {
    alert('Edit user with ID: ' + userId);
    // TODO: Implement edit user modal or redirect
}

// Delete User
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        // TODO: Implement delete functionality
        alert('Delete user with ID: ' + userId);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<link rel="stylesheet" href="/e-barangay/assets/css/footer.css">

</body>
</html>