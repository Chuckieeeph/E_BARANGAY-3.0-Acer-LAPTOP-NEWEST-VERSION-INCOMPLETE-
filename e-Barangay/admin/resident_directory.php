<?php
//admin/resident_directory.php - Complete Resident Viewer
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

$active_page = "resident_directory";

// Fetch all residents with complete data
$query = "
    SELECT 
        u.user_id, u.fullname, u.email, u.contact_no, u.address,
        u.gender, u.birthdate, u.civil_status, u.nationality,
        u.validation_status, u.date_registered, u.valid_id,
        rp.age, rp.occupation, rp.years_of_residency, 
        rp.barangay_id_number, rp.profile_completed
    FROM users u
    LEFT JOIN resident_profiles rp ON u.user_id = rp.user_id
    WHERE u.role = 'resident'
    ORDER BY u.date_registered DESC
";

$residents = $con->query($query);

// Get statistics
$total = $con->query("SELECT COUNT(*) as c FROM users WHERE role='resident'")->fetch_assoc()['c'];
$validated = $con->query("SELECT COUNT(*) as c FROM users WHERE role='resident' AND validation_status='validated'")->fetch_assoc()['c'];
$pending = $con->query("SELECT COUNT(*) as c FROM users WHERE role='resident' AND validation_status='pending'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Directory • e-Barangay Admin</title>
    <link rel="stylesheet" href="../assets/css/resident_directory.css">
</head>
<body>

<div class="dashboard-container">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h2>Resident Directory</h2>
                <p class="muted">View and manage all registered residents</p>
            </div>
            <button class="btn btn-secondary" onclick="exportToExcel()">
                📥 Export to Excel
            </button>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-info">
                    <h3><?= $total ?></h3>
                    <p>Total Residents</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon validated">✅</div>
                <div class="stat-info">
                    <h3><?= $validated ?></h3>
                    <p>Validated</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending">⏳</div>
                <div class="stat-info">
                    <h3><?= $pending ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>

        <div class="table-controls">
            <input type="text" id="searchInput" placeholder="🔍 Search by name, email, or barangay ID..." onkeyup="filterTable()">
            <select id="statusFilter" onchange="filterTable()">
                <option value="">All Status</option>
                <option value="validated">✅ Validated</option>
                <option value="pending">⏳ Pending</option>
                <option value="rejected">❌ Rejected</option>
            </select>
        </div>

        <div class="table-container">
            <table class="residents-table" id="residentsTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Age</th>
                        <th>Occupation</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($residents && $residents->num_rows > 0): ?>
                        <?php while($r = $residents->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['fullname']) ?></strong></td>
                            <td><?= htmlspecialchars($r['email']) ?></td>
                            <td><?= htmlspecialchars($r['contact_no'] ?? '—') ?></td>
                            <td><?= $r['age'] ?? '—' ?></td>
                            <td><?= htmlspecialchars($r['occupation'] ?? '—') ?></td>
                            <td>
                                <span class="badge status-<?= $r['validation_status'] ?>">
                                    <?= ucfirst($r['validation_status']) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($r['date_registered'])) ?></td>
                            <td>
                                <button class="btn-view" onclick="viewResident(<?= $r['user_id'] ?>)">
                                    👁️ View
                                </button>
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

<!-- Resident Profile Modal -->
<div id="residentModal" class="modal">
    <div class="modal-content large">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <div id="residentDetails">Loading...</div>
    </div>
</div>

<script>
function viewResident(userId) {
    const modal = document.getElementById('residentModal');
    const details = document.getElementById('residentDetails');
    
    modal.style.display = 'flex';
    details.innerHTML = '<div class="loading">Loading resident information...</div>';
    
    fetch(`get_resident_details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                details.innerHTML = `<div class="error">${data.error}</div>`;
                return;
            }
            
            details.innerHTML = `
                <div class="profile-view">
                    <div class="profile-header">
                        <div class="profile-avatar-large">
                            ${data.fullname.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <h2>${data.fullname}</h2>
                            <p class="subtitle">${data.email}</p>
                            <span class="badge status-${data.validation_status}">
                                ${data.validation_status.toUpperCase()}
                            </span>
                        </div>
                    </div>

                    <div class="profile-sections">
                        <div class="profile-section">
                            <h3>👤 Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Gender</label>
                                    <p>${data.gender || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Birthdate</label>
                                    <p>${data.birthdate || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Age</label>
                                    <p>${data.age || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Civil Status</label>
                                    <p>${data.civil_status || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Blood Type</label>
                                    <p>${data.blood_type || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Height/Weight</label>
                                    <p>${data.height || '—'} cm / ${data.weight || '—'} kg</p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3>🏠 Address</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Complete Address</label>
                                    <p>${data.full_address || data.address || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Years of Residency</label>
                                    <p>${data.years_of_residency || '—'} years</p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3>💼 Employment</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Occupation</label>
                                    <p>${data.occupation || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Employer</label>
                                    <p>${data.employer_name || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Monthly Income</label>
                                    <p>₱${data.monthly_income || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Employment Status</label>
                                    <p>${data.employment_status || '—'}</p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3>👨‍👩‍👧 Family</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Spouse</label>
                                    <p>${data.spouse_name || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Father's Name</label>
                                    <p>${data.father_name || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>Mother's Name</label>
                                    <p>${data.mother_name || '—'}</p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3>🆔 Government IDs</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Barangay ID</label>
                                    <p>${data.barangay_id_number || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>TIN</label>
                                    <p>${data.tin || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>SSS</label>
                                    <p>${data.sss_number || '—'}</p>
                                </div>
                                <div class="info-item">
                                    <label>PhilHealth</label>
                                    <p>${data.philhealth_number || '—'}</p>
                                </div>
                            </div>
                        </div>

                        ${data.valid_id ? `
                        <div class="profile-section">
                            <h3>📄 Uploaded Valid ID</h3>
                            <img src="../public/uploads/valid_ids/${data.valid_id}" 
                                 alt="Valid ID" 
                                 style="max-width: 400px; border-radius: 8px; cursor: pointer;"
                                 onclick="window.open(this.src, '_blank')">
                        </div>
                        ` : ''}
                    </div>

                    <div class="modal-footer">
                        <button class="btn secondary" onclick="closeModal()">Close</button>
                    </div>
                </div>
            `;
        })
        .catch(error => {
            details.innerHTML = `<div class="error">Error loading resident details</div>`;
            console.error('Error:', error);
        });
}

function closeModal() {
    document.getElementById('residentModal').style.display = 'none';
}

function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const table = document.getElementById('residentsTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const name = row.cells[0]?.textContent.toLowerCase() || '';
        const email = row.cells[1]?.textContent.toLowerCase() || '';
        const status = row.cells[5]?.textContent.toLowerCase() || '';

        const matchesSearch = name.includes(searchInput) || email.includes(searchInput);
        const matchesStatus = statusFilter === '' || status.includes(statusFilter);

        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    }
}

function exportToExcel() {
    window.location.href = 'export_residents.php';
}

window.onclick = function(event) {
    const modal = document.getElementById('residentModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<link rel="stylesheet" href="/e-barangay/assets/css/footer.css">
</body>
</html>