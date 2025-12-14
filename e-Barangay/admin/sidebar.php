<?php
// admin/sidebar.php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') return;

require_once __DIR__ . '/../config/database.php';

// Check for existing secretary
$existingSecretary = null;
$q = $con->query("SELECT user_id, fullname, email, contact_no, date_registered FROM users WHERE role='secretary' LIMIT 1");
if ($q && $q->num_rows > 0) {
    $existingSecretary = $q->fetch_assoc();
}

$current = basename($_SERVER['PHP_SELF']);
?>
<link rel="stylesheet" href="/e-barangay/assets/css/admin_sidebar.css">

<aside class="sidebar">
    <div class="brand">
        <img src="/e-barangay/assets/images/logo.jpg" class="logo" alt="logo">
        <h3>e-Barangay Admin</h3>
        <small>Brgy. Cantil</small>
    </div>

    <nav class="menu">

        <a href="/e-barangay/admin/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
            📊 Dashboard
        </a>

        <a href="/e-barangay/admin/user_management.php" class="<?= $current === 'user_management.php' ? 'active' : '' ?>">
            👥 User Management
        </a>

        <a href="/e-barangay/admin/resident_directory.php" class="<?= $current === 'resident_directory' ? 'active' : '' ?>">
            📂 Resident Directory
        </a>

        <a href="/e-barangay/admin/certifications.php" class="<?= $current === 'certifications.php' ? 'active' : '' ?>">
            📄 Certifications
        </a>

        <a href="/e-barangay/admin/audit_trail.php" class="<?= $current === 'audit_trail.php' ? 'active' : '' ?>">
            📝 Audit Trail
        </a>

        <a href="#" id="addSecretaryBtn">
            ➕ Add Secretary
        </a>

        <a href="/e-barangay/public/logout.php" class="logout">
            🚪 Logout
        </a>

    </nav>
</aside>

<!-- Secretary Modal -->
<div id="secretaryModal" class="secretary-modal">
    <div class="secretary-modal-content">
        <span class="secretary-modal-close">&times;</span>

        <?php if ($existingSecretary): ?>
            <h2>Existing Secretary</h2>
            <div class="secretary-info">
                <p><strong>Name:</strong> <?= htmlspecialchars($existingSecretary['fullname']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($existingSecretary['email']) ?></p>
                <p><strong>Contact:</strong> <?= htmlspecialchars($existingSecretary['contact_no'] ?: 'N/A') ?></p>
                <p><strong>Date Created:</strong> <?= $existingSecretary['date_registered'] ?></p>
            </div>

            <div class="alert info">
                <strong>ℹ️ Note:</strong>
                A secretary already exists. Delete or demote the existing one to create a new secretary.
            </div>

            <a class="btn" href="/e-barangay/admin/view_secretary.php?user_id=<?= $existingSecretary['user_id'] ?>">
                View Secretary Profile
            </a>

        <?php else: ?>
            <h2>No Secretary Found</h2>
            <p>No secretary account exists in the system. You can create one now.</p>

            <a class="btn btn-primary" href="/e-barangay/admin/create_secretary.php">
                Create Secretary
            </a>
        <?php endif; ?>
    </div>
</div>

<script>
// Secretary Modal Handler
(function() {
    const modal = document.getElementById('secretaryModal');
    const btn = document.getElementById('addSecretaryBtn');
    const closeBtn = document.querySelector('.secretary-modal-close');

    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.style.display = 'flex';
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }

    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
})();
</script>