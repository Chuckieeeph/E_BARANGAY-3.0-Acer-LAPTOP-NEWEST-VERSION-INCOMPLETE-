<?php
//secretary/verify_residents.php - UPDATED with new table
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/auth_check.php';
require_role('secretary');
require_once __DIR__ . '/../config/database.php';

$active_page = "verify_residents";
$message = '';
$messageType = '';

// Handle Approve Action
if (isset($_POST['approve'])) {
    $id = intval($_POST['user_id']);
    
    $con->begin_transaction();
    
    try {
        // Update valid_ids table
        $stmt = $con->prepare("
            UPDATE valid_ids 
            SET validation_status = 'approved', 
                validated_by = ?, 
                validated_at = NOW() 
            WHERE user_id = ? AND is_current = TRUE
        ");
        $stmt->bind_param("ii", $_SESSION['user_id'], $id);
        $stmt->execute();
        
        // Update users table
        $stmt = $con->prepare("UPDATE users SET validation_status = 'validated' WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $con->commit();
        
        $message = "✅ Resident ID approved successfully! Account is now validated.";
        $messageType = "success";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Failed to approve: " . $e->getMessage();
        $messageType = "error";
    }
    
    header("Location: verify_residents.php?success=1");
    exit;
}

// Handle Reject Action with Reason
if (isset($_POST['reject'])) {
    $id = intval($_POST['user_id']);
    $reason = trim($_POST['rejection_reason'] ?? 'No reason provided');
    
    $con->begin_transaction();
    
    try {
        // Update valid_ids table with rejection
        $stmt = $con->prepare("
            UPDATE valid_ids 
            SET validation_status = 'rejected', 
                validated_by = ?, 
                validated_at = NOW(),
                rejection_reason = ?,
                is_current = FALSE
            WHERE user_id = ? AND is_current = TRUE
        ");
        $stmt->bind_param("isi", $_SESSION['user_id'], $reason, $id);
        $stmt->execute();
        
        // Update users table
        $stmt = $con->prepare("UPDATE users SET validation_status = 'rejected', valid_id = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $con->commit();
        
        $message = "⚠️ Resident ID rejected. User must re-upload.";
        $messageType = "warning";
        
    } catch (Exception $e) {
        $con->rollback();
        $message = "❌ Failed to reject: " . $e->getMessage();
        $messageType = "error";
    }
    
    header("Location: verify_residents.php?rejected=1");
    exit;
}

// Check for messages
if (isset($_GET['success'])) {
    $message = "✅ Resident ID approved successfully!";
    $messageType = "success";
} elseif (isset($_GET['rejected'])) {
    $message = "⚠️ Resident ID rejected.";
    $messageType = "warning";
}

// Get statistics
$pendingCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='pending'")->fetch_assoc()['count'];
$validatedCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='validated'")->fetch_assoc()['count'];
$rejectedCount = $con->query("SELECT COUNT(*) as count FROM users WHERE role='resident' AND validation_status='rejected'")->fetch_assoc()['count'];

// Fetch residents with pending IDs
$res = $con->query("
    SELECT 
        u.user_id, 
        u.fullname, 
        u.email, 
        u.validation_status, 
        u.contact_no,
        u.date_registered,
        vid.id as valid_id_record,
        vid.filename,
        vid.upload_date,
        vid.file_type
    FROM users u
    LEFT JOIN valid_ids vid ON u.user_id = vid.user_id AND vid.is_current = TRUE
    WHERE u.role = 'resident'
    ORDER BY 
        CASE u.validation_status 
            WHEN 'pending' THEN 1 
            WHEN 'rejected' THEN 2 
            WHEN 'validated' THEN 3 
            ELSE 4 
        END,
        vid.upload_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Residents • e-Barangay Secretary</title>
    <link rel="stylesheet" href="../assets/css/verify_residents.css">
    <style>
        /* Rejection Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            font-size: 20px;
            color: #dc2626;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .modal-body textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            min-height: 100px;
            resize: vertical;
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .modal-footer button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-confirm-reject {
            background: #dc2626;
            color: white;
        }
        
        .btn-confirm-reject:hover {
            background: #b91c1c;
        }
        
        .btn-cancel {
            background: #e5e7eb;
            color: #374151;
        }
        
        .btn-cancel:hover {
            background: #d1d5db;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h2>Resident ID Verification</h2>
                <p class="muted">Review and approve resident ID submissions</p>
            </div>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                🔄 Refresh
            </button>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $messageType ?>">
                <strong><?= $message ?></strong>
            </div>
        <?php endif; ?>

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
        </div>

        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍 Search..." onkeyup="filterTable()">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="pending" selected>⏳ Pending</option>
                <option value="validated">✅ Validated</option>
                <option value="rejected">❌ Rejected</option>
            </select>
        </div>

        <div class="table-container">
            <table class="residents-table" id="residentsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Valid ID</th>
                        <th>Upload Date</th>
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
                                    <?= ucfirst($r['validation_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($r['filename']): ?>
                                    <button class="btn-view-id" onclick="viewID('<?= htmlspecialchars($r['filename']) ?>', '<?= htmlspecialchars($r['fullname']) ?>')">
                                        📄 View
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">No ID</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $r['upload_date'] ? date('M d, Y', strtotime($r['upload_date'])) : '—' ?></td>
                            <td>
                                <?php if($r['validation_status'] === 'pending' && $r['filename']): ?>
                                    <div class="action-buttons">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?= $r['user_id'] ?>">
                                            <button type="submit" name="approve" class="btn-action approve">
                                                ✅ Approve
                                            </button>
                                        </form>
                                        <button class="btn-action reject" onclick="openRejectModal(<?= $r['user_id'] ?>, '<?= htmlspecialchars($r['fullname']) ?>')">
                                            ❌ Reject
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="status-text"><?= ucfirst($r['validation_status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="no-data">No residents found</td>
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
        <span class="modal-close" onclick="closeIDModal()">&times;</span>
        <h3 id="modalTitle">View Valid ID</h3>
        <div class="modal-body">
            <img id="modalImage" src="" alt="Valid ID" style="max-width: 100%; border-radius: 8px;">
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" class="modal">
    <div class="modal-content">
        <h3 class="modal-header">Reject ID Verification</h3>
        <div class="modal-body">
            <p style="margin-bottom: 15px;">Provide a reason for rejection (will be shown to resident):</p>
            <form id="rejectForm" method="POST">
                <input type="hidden" name="user_id" id="rejectUserId">
                <textarea name="rejection_reason" placeholder="E.g., ID is blurry, expired, or invalid" required></textarea>
                <div class="modal-footer">
                    <button type="submit" name="reject" class="btn-confirm-reject">Reject ID</button>
                    <button type="button" class="btn-cancel" onclick="closeRejectModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewID(filename, fullname) {
    const modal = document.getElementById('idModal');
    const img = document.getElementById('modalImage');
    const title = document.getElementById('modalTitle');
    
    img.src = '/e-barangay/public/uploads/valid_ids/' + filename;
    title.textContent = fullname + ' - Valid ID';
    modal.style.display = 'flex';
}

function closeIDModal() {
    document.getElementById('idModal').style.display = 'none';
}

function openRejectModal(userId, fullname) {
    document.getElementById('rejectUserId').value = userId;
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const table = document.getElementById('residentsTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const name = row.cells[1]?.textContent.toLowerCase() || '';
        const email = row.cells[2]?.textContent.toLowerCase() || '';
        const status = row.cells[4]?.textContent.toLowerCase() || '';

        const matchesSearch = name.includes(searchInput) || email.includes(searchInput);
        const matchesStatus = statusFilter === '' || status.includes(statusFilter);

        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    }
}

window.onload = filterTable;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<link rel="stylesheet" href="/e-barangay/assets/css/footer.css">

</body>
</html>