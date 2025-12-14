<?php
//resident/upload_valid_id.php - REDESIGNED
define('APP_RUNNING', true);

require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role('resident');
require_once __DIR__ . '/../config/database.php';

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'] ?? 'Resident';
$validation = $_SESSION['validation_status'] ?? 'unvalidated';

$success = $error = null;

// Fetch current ID upload history
$history_query = $con->prepare("
    SELECT id, filename, file_type, upload_date, validation_status, rejection_reason 
    FROM valid_ids 
    WHERE user_id = ? 
    ORDER BY upload_date DESC 
    LIMIT 5
");
$history_query->bind_param("i", $user_id);
$history_query->execute();
$history = $history_query->get_result();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['valid_id'])) {
    $file = $_FILES['valid_id'];
    
    // Validation
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 2MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "❌ Upload failed. Please try again.";
    } elseif (!in_array($file['type'], $allowed_types)) {
        $error = "❌ Invalid file type. Please upload JPG, PNG, or PDF only.";
    } elseif ($file['size'] > $max_size) {
        $error = "❌ File too large. Maximum size is 2MB.";
    } else {
        $upload_dir = __DIR__ . '/../public/uploads/valid_ids/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'id_' . $user_id . '_' . time() . '.' . $ext;
        $target = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            // Start transaction
            $con->begin_transaction();
            
            try {
                // Mark previous uploads as not current
                $stmt = $con->prepare("UPDATE valid_ids SET is_current = FALSE WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                // Insert new ID record
                $stmt = $con->prepare("
                    INSERT INTO valid_ids 
                    (user_id, filename, file_type, file_size, validation_status, is_current) 
                    VALUES (?, ?, ?, ?, 'pending', TRUE)
                ");
                $stmt->bind_param("issi", $user_id, $filename, $file['type'], $file['size']);
                $stmt->execute();
                
                // Update users table
                $stmt = $con->prepare("UPDATE users SET valid_id = ?, validation_status = 'pending' WHERE user_id = ?");
                $stmt->bind_param("si", $filename, $user_id);
                $stmt->execute();
                
                $con->commit();
                
                $_SESSION['validation_status'] = 'pending';
                $success = "✅ ID uploaded successfully! Your ID is now pending review.";
                
                // Refresh history
                $history_query->execute();
                $history = $history_query->get_result();
                
            } catch (Exception $e) {
                $con->rollback();
                $error = "❌ Database error. Please try again.";
            }
        } else {
            $error = "❌ Failed to save file. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Valid ID - e-Barangay</title>
    <link rel="stylesheet" href="/e-barangay/assets/css/upload_valid_id.css">
</head>
<body>

    <!-- Top Navigation Bar -->
    <header class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="/e-barangay/resident/dashboard.php" class="back-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Back to Home
                </a>
            </div>
            <div class="nav-right">
                <a href="/e-barangay/resident/profile.php" class="profile-btn" title="My Profile">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                </a>
                <a href="/e-barangay/public/logout.php" class="logout-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="upload-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1>Upload Valid ID</h1>
            <p class="subtitle">Upload a clear photo of your valid government-issued ID</p>
        </div>

        <!-- Alert Messages -->
        <?php if($success): ?>
            <div class="alert success">
                <strong>Success!</strong>
                <p><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert error">
                <strong>Error</strong>
                <p><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <!-- Current Status Card -->
        <div class="status-card">
            <div class="status-header">
                <h3>Current Validation Status</h3>
                <span class="badge status-<?= $validation ?>">
                    <?php if($validation === 'pending'): ?>⏳ Pending<?php endif; ?>
                    <?php if($validation === 'validated'): ?>✅ Validated<?php endif; ?>
                    <?php if($validation === 'rejected'): ?>❌ Rejected<?php endif; ?>
                    <?php if($validation === 'unvalidated'): ?>⚠️ Not Validated<?php endif; ?>
                </span>
            </div>
            <div class="status-body">
                <?php if($validation === 'pending'): ?>
                    <p>Your ID is currently being reviewed by the admin. Please wait for approval.</p>
                <?php elseif($validation === 'validated'): ?>
                    <p>Your account is validated! You have full access to all barangay services.</p>
                <?php elseif($validation === 'rejected'): ?>
                    <p>Your previous ID was rejected. Please upload a new, clear photo of your valid ID.</p>
                <?php else: ?>
                    <p>You need to upload a valid ID to access barangay services.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upload Form -->
        <div class="upload-card">
            <h3>Upload New ID</h3>
            
            <form method="POST" enctype="multipart/form-data" class="upload-form">
                
                <div class="file-upload-area" id="fileUploadArea">
                    <div class="upload-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <p class="upload-text">Click to upload or drag and drop</p>
                    <p class="upload-hint">JPG, PNG, or PDF (Max 2MB)</p>
                    <input type="file" 
                           id="valid_id" 
                           name="valid_id" 
                           accept="image/jpeg,image/jpg,image/png,application/pdf" 
                           required
                           class="file-input">
                </div>

                <div id="filePreview" class="file-preview" style="display: none;">
                    <img id="previewImage" src="" alt="Preview">
                    <p id="fileName" class="file-name"></p>
                    <button type="button" class="btn-remove" onclick="removeFile()">Remove</button>
                </div>

                <div class="requirements">
                    <h4>Accepted IDs:</h4>
                    <ul>
                        <li>✓ Driver's License</li>
                        <li>✓ Passport</li>
                        <li>✓ PhilHealth ID</li>
                        <li>✓ SSS ID</li>
                        <li>✓ Voter's ID</li>
                        <li>✓ UMID</li>
                        <li>✓ Postal ID</li>
                        <li>✓ PRC ID</li>
                    </ul>
                    <p class="note"><strong>Note:</strong> Make sure your ID is clear, readable, and not expired.</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        Upload ID
                    </button>
                    <a href="/e-barangay/resident/dashboard.php" class="btn secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Upload History -->
        <?php if($history && $history->num_rows > 0): ?>
        <div class="history-card">
            <h3>Upload History</h3>
            <div class="history-list">
                <?php while($h = $history->fetch_assoc()): ?>
                <div class="history-item">
                    <div class="history-icon">
                        <?php if($h['file_type'] === 'application/pdf'): ?>
                            📄
                        <?php else: ?>
                            🖼️
                        <?php endif; ?>
                    </div>
                    <div class="history-info">
                        <p class="history-filename"><?= htmlspecialchars($h['filename']) ?></p>
                        <p class="history-date"><?= date('M d, Y g:i A', strtotime($h['upload_date'])) ?></p>
                        <?php if($h['rejection_reason']): ?>
                            <p class="rejection-reason">Reason: <?= htmlspecialchars($h['rejection_reason']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="history-status">
                        <span class="badge status-<?= $h['validation_status'] ?>">
                            <?= ucfirst($h['validation_status']) ?>
                        </span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <script>
        const fileInput = document.getElementById('valid_id');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const filePreview = document.getElementById('filePreview');
        const previewImage = document.getElementById('previewImage');
        const fileName = document.getElementById('fileName');

        // Click to upload
        fileUploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('drag-over');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('drag-over');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('drag-over');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });

        // File selection
        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                fileName.textContent = file.name;
                
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        previewImage.src = e.target.result;
                        previewImage.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    previewImage.style.display = 'none';
                }
                
                fileUploadArea.style.display = 'none';
                filePreview.style.display = 'block';
            }
        }

        function removeFile() {
            fileInput.value = '';
            fileUploadArea.style.display = 'flex';
            filePreview.style.display = 'none';
            previewImage.src = '';
        }
    </script>

</body>
</html>