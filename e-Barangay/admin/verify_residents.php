<?php
//admin/verify_residents.php - Admin version with same functionality
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

$active_page = "verify_residents";
$message = '';
$messageType = '';

// Handle Approve Action
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    
    // Start transaction
    $con->begin_transaction();
    
    try {
        // Update validation status
        $stmt = $con->prepare("UPDATE users SET validation_status='validated' WHERE user_id=? AND role='resident'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Force session update for that specific user
        $updateSession = $con->prepare("UPDATE users SET date_registered = date_registered WHERE user_id = ?");
        $updateSession->bind_param("i", $id);
        $updateSession->execute();
        $updateSession->close();
        
        // Commit transaction
        $con->commit();
        
        $message = "✅ Resident ID approved successfully! Account is now validated.";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Failed to approve resident: " . $e->getMessage();
        $messageType = "error";
    }
    
    // Redirect to prevent resubmission
    header("Location: verify_residents.php?success=1");
    exit;
}

// Handle Reject Action
if (isset($_GET['reject'])) {
    $id = intval($_GET['reject']);
    
    // Start transaction
    $con->begin_transaction();
    
    try {
        // Update to rejected and clear ID
        $stmt = $con->prepare("UPDATE users SET validation_status='rejected', valid_id=NULL WHERE user_id=? AND role='resident'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        
        // Force session refresh
        $updateSession = $con->prepare("UPDATE users SET date_registered = date_registered WHERE user_id = ?");
        $updateSession->bind_param("i", $id);
        $updateSession->execute();
        $updateSession->close();
        
        $con->commit();
        
        $message = "⚠️ Resident ID rejected. User must re-upload valid ID.";
        $messageType = "warning";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Failed to reject resident: " . $e->getMessage();
        $messageType = "error";
    }
    
    // Redirect to prevent resubmission
    header("Location: verify_residents.php?rejected=1");
    exit;
}

// Check for success messages from redirect
if (isset($_GET['success'])) {
    $message = "Resident ID approved successfully! Account is now validated.";
    $messageType = "success";
} elseif (isset($_GET['rejected'])) {
    $message = "⚠️ Resident ID rejected. User must re-upload valid ID.";
    $messageType = "warning";
}

// Get statistics
$pendingCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='pending'")->fetch_assoc()['count'];
$validatedCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='validated'")->fetch_assoc()['count'];
$rejectedCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='rejected'")->fetch_assoc()['count'];
$unvalidatedCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='unvalidated'")->fetch_assoc()['count'];

// Fetch all residents with ordering: pending first
$res = $con->query("
    SELECT user_id, fullname, email, validation_status, valid_id, date_registered, contact_no, address 
    FROM users 
    WHERE role='resident' 
    ORDER BY 
        CASE validation_status 
            WHEN 'pending' THEN 1 
            WHEN 'rejected' THEN 2 
            WHEN 'unvalidated' THEN 3
            WHEN 'validated' THEN 4 
            ELSE 5 
        END,
        date_registered DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Residents • e-Barangay Admin</title>
    <link rel="stylesheet" href="../assets/css/verify_residents.css">
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
                <h2>Resident ID Verification</h2>
                <p class="muted">Review and approve resident ID submissions</p>
            </div>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                🔄 Refresh Data
            </button>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <strong>
                    <?php if ($messageType === 'success'): ?>✅ Success<?php endif; ?>
                    <?php if ($messageType === 'error'): ?>⚠️ Error<?php endif; ?>
                    <?php if ($messageType === 'warning'): ?>⚠️ Notice<?php endif; ?>
                </strong>
                <p><?= htmlspecialchars($message) ?></p>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3><?= $pendingCount ?></h3>
                    <p>Pending Review</p>
                </div>
            </div>

            <div class="stat-card validated">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?= $validatedCount ?></h3>
                    <p>Validated</p>
                </div>
            </div>

            <div class="stat-card rejected">
                <div class="stat-icon">❌</div>
                <div class="stat-info">
                    <h3><?= $rejectedCount ?></h3>
                    <p>Rejected</p>
                </div>
            </div>

            <div class="stat-card unvalidated">
                <div class="stat-icon">⚠️</div>
                <div class="stat-info">
                    <h3><?= $unvalidatedCount ?></h3>
                    <p>Unvalidated</p>
                </div>
            </div>
        </div>

        <!-- Filter Options -->
        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Search by name or email..." onkeyup="filterTable()">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="pending" selected>⏳ Pending</option>
                <option value="validated">✅ Validated</option>
                <option value="rejected">❌ Rejected</option>
                <option value="unvalidated">⚠️ Unvalidated</option>
            </select>
        </div>

        <!-- Residents Table -->
        <div class="table-container">
            <table class="residents-table" id="residentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Valid ID</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($res && $res->num_rows > 0): ?>
                        <?php while($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><span class="user-id">#<?= $r['user_id'] ?></span></td>
                            <td><strong><?= htmlspecialchars($r['fullname']) ?></strong></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['contact_no'] ?? '—') ?></td>
                            <td>
                                <span class="badge status-<?= $r['validation_status'] ?>">
                                    <?php if($r['validation_status'] === 'pending'): ?>⏳<?php endif; ?>
                                    <?php if($r['validation_status'] === 'validated'): ?>✅<?php endif; ?>
                                    <?php if($r['validation_status'] === 'rejected'): ?>❌<?php endif; ?>
                                    <?php if($r['validation_status'] === 'unvalidated'): ?>⚠️<?php endif; ?>
                                    <?= ucfirst($r['validation_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($r['valid_id']): ?>
                                    <button class="btn-view-id" onclick="viewID('<?= htmlspecialchars($r['valid_id']) ?>', '<?= htmlspecialchars($r['fullname']) ?>')">
                                        📄 View ID
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">No ID</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($r['date_registered'])) ?></td>
                            <td>
                                <?php if($r['validation_status'] === 'pending'): ?>
                                    <div class="action-buttons">
                                        <a href="?approve=<?= $r['user_id'] ?>" 
                                           class="btn-action approve" 
                                           onclick="return confirm('✅ Approve this resident\'s ID?\n\nThis will grant them full access to barangay services.')">
                                            ✅ Approve
                                        </a>
                                        <a href="?reject=<?= $r['user_id'] ?>" 
                                           class="btn-action reject" 
                                           onclick="return confirm('❌ Reject this resident\'s ID?\n\nThey will need to re-upload a valid ID.')">
                                            ❌ Reject
                                        </a>
                                    </div>
                                <?php elseif($r['validation_status'] === 'validated'): ?>
                                    <span class="status-text validated">✅ Verified</span>
                                <?php elseif($r['validation_status'] === 'rejected'): ?>
                                    <span class="status-text rejected">❌ Rejected</span>
                                <?php elseif($r['validation_status'] === 'unvalidated'): ?>
                                    <span class="status-text unvalidated">⚠️ Not Verified</span>
                                <?php else: ?>
                                    <span class="status-text">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">
                                <div class="empty-state">
                                    <span class="empty-icon">📭</span>
                                    <p>No residents found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

</div>

<!-- ID Viewer Modal -->
<div id="idModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle">View Valid ID</h3>
        <div class="modal-body">
            <img id="modalImage" src="" alt="Valid ID" style="max-width: 100%; border-radius: 8px;">
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Close</button>
        </div>
    </div>
</div>

<script>
// View ID Modal
function viewID(filename, fullname) {
    const modal = document.getElementById('idModal');
    const img = document.getElementById('modalImage');
    const title = document.getElementById('modalTitle');
    
    img.src = '/e-barangay/public/uploads/valid_ids/' + filename;
    title.textContent = fullname + ' - Valid ID';
    modal.style.display = 'flex';
}

function closeModal() {
    document.getElementById('idModal').style.display = 'none';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('idModal');
    if (event.target === modal) {
        closeModal();
    }
}

// Filter Table Function
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const table = document.getElementById('residentsTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const fullname = row.cells[1]?.textContent.toLowerCase() || '';
        const email = row.cells[2]?.textContent.toLowerCase() || '';
        const status = row.cells[4]?.textContent.toLowerCase() || '';

        const matchesSearch = fullname.includes(searchInput) || email.includes(searchInput);
        const matchesStatus = statusFilter === '' || status.includes(statusFilter);

        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Auto-select "Pending" filter on load
window.onload = function() {
    filterTable();
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<link rel="stylesheet" href="/e-barangay/assets/css/footer.css">

</body>
</html>