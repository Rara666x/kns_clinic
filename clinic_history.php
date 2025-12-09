<?php
include('includes/auth.php');
include('db_connection.php');

// Create clinic_history_content table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS clinic_history_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        content TEXT,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    )
");

// Handle form submission for updating clinic history
$message = '';
$messageType = '';

if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_history') {
    $content = $_POST['history_content'] ?? '';
    $userId = $_SESSION['user_id'] ?? null;

    if ($content) {
        // Check if content already exists
        $checkStmt = $conn->prepare("SELECT id FROM clinic_history_content LIMIT 1");
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($existing) {
            // Update existing content
            $stmt = $conn->prepare("UPDATE clinic_history_content SET content = ?, updated_by = ? WHERE id = ?");
            $stmt->bind_param("sii", $content, $userId, $existing['id']);
        } else {
            // Insert new content
            $stmt = $conn->prepare("INSERT INTO clinic_history_content (content, updated_by) VALUES (?, ?)");
            $stmt->bind_param("si", $content, $userId);
        }

        if ($stmt->execute()) {
            $message = "Clinic history updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating clinic history: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = "Please enter some content for the clinic history.";
        $messageType = "error";
    }
}

// Get current clinic history content
$historyStmt = $conn->prepare("SELECT chc.content, chc.updated_at, u.fullName as updated_by_name FROM clinic_history_content chc LEFT JOIN users u ON chc.updated_by = u.id ORDER BY chc.updated_at DESC LIMIT 1");
$historyStmt->execute();
$historyResult = $historyStmt->get_result();
$currentHistory = $historyResult->fetch_assoc();
$historyStmt->close();

// Get clinic history statistics
$stats = [];

// Total patients
$result = $conn->query("SELECT COUNT(*) as total FROM patients");
$stats['total_patients'] = $result->fetch_assoc()['total'];

// Total appointments
$result = $conn->query("SELECT COUNT(*) as total FROM appointments");
$stats['total_appointments'] = $result->fetch_assoc()['total'];

// Total medical records
$result = $conn->query("SELECT COUNT(*) as total FROM medical_records");
$stats['total_records'] = $result->fetch_assoc()['total'];

// Total medicines
$result = $conn->query("SELECT COUNT(*) as total FROM medicines WHERE is_active = 1");
$stats['total_medicines'] = $result->fetch_assoc()['total'];

// Total equipment
$result = $conn->query("SELECT COUNT(*) as total FROM medical_equipment WHERE is_active = 1");
$stats['total_equipment'] = $result->fetch_assoc()['total'];

// Total school activities
$result = $conn->query("SELECT COUNT(*) as total FROM school_activities");
$stats['total_activities'] = $result->fetch_assoc()['total'];

// Total school events
$result = $conn->query("SELECT COUNT(*) as total FROM school_events");
$stats['total_events'] = $result->fetch_assoc()['total'];


// Get system milestones
$milestones = [
    ['date' => '2024-01-15', 'title' => 'Clinical System Launched', 'description' => 'Initial deployment of the clinical management system'],
    ['date' => '2024-02-01', 'title' => 'Patient Management Module', 'description' => 'Patient registration and management features added'],
    ['date' => '2024-02-15', 'title' => 'Appointment Scheduling', 'description' => 'Online appointment booking system implemented'],
    ['date' => '2024-03-01', 'title' => 'Medical Records System', 'description' => 'Digital medical records and history tracking'],
    ['date' => '2024-03-15', 'title' => 'Inventory Management', 'description' => 'Medicine and equipment inventory tracking'],
    ['date' => '2024-04-01', 'title' => 'School Activities Integration', 'description' => 'School events and activities with medical team support'],
    ['date' => '2024-04-15', 'title' => 'Medical Team Integration', 'description' => 'Medical team assignment for all school events'],
];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Clinic History - Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/clinic_history.css">
</head>
<body>
    <header class="topbar">
        <div class="brand">Clinical System</div>
        <nav class="topbar-actions">
            <div class="settings-dropdown">
                <button class="settings-btn" onclick="toggleSettings()">
                    ⚙️ Settings
                </button>
                <div class="settings-menu" id="settingsMenu">
                    <a href="user_manual.php" class="settings-item">
                        <span class="settings-icon">📖</span>
                        <span class="settings-text">User Manual</span>
                    </a>
                    <a href="clinic_history.php" class="settings-item">
                        <span class="settings-icon">🏥</span>
                        <span class="settings-text">History of Clinic of School</span>
                    </a>
                    <a href="activity_log.php" class="settings-item">
                        <span class="settings-icon">📋</span>
                        <span class="settings-text">Activity Log</span>
                    </a>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="users.php" class="settings-item">
                        <span class="settings-icon">👥</span>
                        <span class="settings-text">Manage Users</span>
                    </a>
                    <?php endif; ?>
                    <div class="settings-divider"></div>
                    <a href="logout.php" class="settings-item settings-logout">
                        <span class="settings-icon">🚪</span>
                        <span class="settings-text">Logout</span>
                    </a>
                </div>
            </div>
            <span class="user-chip">
                <?php echo $_SESSION['fullName']; ?>
                <small><?php echo ucfirst($_SESSION['role']); ?></small>
            </span>
        </nav>
    </header>

    <div class="history-container">
        <div class="page-header">
            <h1>🏥 History of Clinic of School</h1>
            <a href="dashboard.php" class="btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>" style="margin-bottom: 20px; padding: 12px 16px; border-radius: 8px; font-weight: 500; <?php echo $messageType === 'success' ? 'background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['total_patients']); ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo number_format($stats['total_appointments']); ?></div>
                <div class="stat-label">Total Appointments</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-value"><?php echo number_format($stats['total_records']); ?></div>
                <div class="stat-label">Medical Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💊</div>
                <div class="stat-value"><?php echo number_format($stats['total_medicines']); ?></div>
                <div class="stat-label">Medicines</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🔧</div>
                <div class="stat-value"><?php echo number_format($stats['total_equipment']); ?></div>
                <div class="stat-label">Equipment</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo number_format($stats['total_activities']); ?></div>
                <div class="stat-label">School Activities</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎉</div>
                <div class="stat-value"><?php echo number_format($stats['total_events']); ?></div>
                <div class="stat-label">School Events</div>
            </div>
        </div>

        <!-- Editable Clinic History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📝 Clinic History</h3>
                <button class="btn-primary" onclick="toggleEditMode()" id="editBtn">Edit History</button>
            </div>
            <div class="history-content">
                <div id="historyDisplay" class="history-text">
                    <?php if ($currentHistory && $currentHistory['content']): ?>
                        <div style="white-space: pre-wrap; line-height: 1.6; color: var(--text-700);">
                            <?php echo htmlspecialchars($currentHistory['content']); ?>
                        </div>
                        <?php if ($currentHistory['updated_by_name']): ?>
                            <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); font-size: 14px; color: var(--text-500);">
                                Last updated by <strong><?php echo htmlspecialchars($currentHistory['updated_by_name']); ?></strong>
                                on <?php echo date('M j, Y \a\t g:i A', strtotime($currentHistory['updated_at'])); ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-500); font-style: italic;">
                            No clinic history content available. Click "Edit History" to add content.
                        </div>
                    <?php endif; ?>
                </div>

                <div id="historyEdit" class="history-edit" style="display: none;">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_history">
                        <textarea name="history_content" id="historyContent" rows="15" style="width: 100%; padding: 16px; border: 1px solid var(--border); border-radius: 8px; font-family: inherit; font-size: 14px; line-height: 1.6; resize: vertical;"><?php echo htmlspecialchars($currentHistory['content'] ?? ''); ?></textarea>
                        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 16px;">
                            <button type="button" class="btn-secondary" onclick="cancelEdit()">Cancel</button>
                            <button type="submit" class="btn-primary">Save History</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- System Milestones -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🚀 System Milestones</h3>
            </div>
            <div class="timeline">
                <?php foreach ($milestones as $milestone): ?>
                <div class="timeline-item">
                    <div class="timeline-icon">⭐</div>
                    <div class="timeline-content">
                        <div class="timeline-date"><?php echo date('M j, Y', strtotime($milestone['date'])); ?></div>
                        <div class="timeline-title"><?php echo htmlspecialchars($milestone['title']); ?></div>
                        <div class="timeline-description"><?php echo htmlspecialchars($milestone['description']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- System Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ℹ️ System Information</h3>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <h4 style="color: var(--text-700); margin-bottom: 8px;">System Version</h4>
                    <p style="color: var(--text-600);">Clinical System v2.0</p>
                </div>
                <div>
                    <h4 style="color: var(--text-700); margin-bottom: 8px;">Last Updated</h4>
                    <p style="color: var(--text-600);"><?php echo date('M j, Y'); ?></p>
                </div>
                <div>
                    <h4 style="color: var(--text-700); margin-bottom: 8px;">Database Status</h4>
                    <p style="color: var(--text-600);">✅ Operational</p>
                </div>
                <div>
                    <h4 style="color: var(--text-700); margin-bottom: 8px;">Total Users</h4>
                    <p style="color: var(--text-600);">
                        <?php
                        $userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
                        echo $userCount;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Clinic history-specific functionality - common functions are in common.js

        // Clinic history edit functionality
        function toggleEditMode() {
            const display = document.getElementById('historyDisplay');
            const edit = document.getElementById('historyEdit');
            const editBtn = document.getElementById('editBtn');

            if (display.style.display === 'none') {
                // Switch to display mode
                display.style.display = 'block';
                edit.style.display = 'none';
                editBtn.textContent = 'Edit History';
            } else {
                // Switch to edit mode
                display.style.display = 'none';
                edit.style.display = 'block';
                editBtn.textContent = 'Cancel Edit';
                document.getElementById('historyContent').focus();
            }
        }

        function cancelEdit() {
            const display = document.getElementById('historyDisplay');
            const edit = document.getElementById('historyEdit');
            const editBtn = document.getElementById('editBtn');

            display.style.display = 'block';
            edit.style.display = 'none';
            editBtn.textContent = 'Edit History';
        }
    </script>
</body>
</html>
