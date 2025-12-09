<?php
include('includes/auth.php');

// Check if user is admin
if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

include('db_connection.php');

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $fullName = $_POST['fullName'] ?? '';
                $email = $_POST['email'] ?? '';
                $role = $_POST['role'] ?? '';

                if ($username && $password && $fullName && $email && $role) {
                    // Check if username or email already exists
                    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $checkStmt->bind_param("ss", $username, $email);
                    $checkStmt->execute();
                    $existingUser = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();

                    if ($existingUser) {
                        $message = "Username or email already exists.";
                        $messageType = "error";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO users (username, password, fullName, email, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt->bind_param("sssss", $username, $password, $fullName, $email, $role);

                        if ($stmt->execute()) {
                            $message = "User added successfully!";
                            $messageType = "success";
                        } else {
                            $message = "Error adding user: " . $conn->error;
                            $messageType = "error";
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;

            case 'edit':
                $id = $_POST['user_id'] ?? '';
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $fullName = $_POST['fullName'] ?? '';
                $email = $_POST['email'] ?? '';
                $role = $_POST['role'] ?? '';
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if ($id && $username && $fullName && $email && $role) {
                    // Check if username or email already exists (excluding current user)
                    $checkStmt = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
                    $checkStmt->bind_param("ssi", $username, $email, $id);
                    $checkStmt->execute();
                    $existingUser = $checkStmt->get_result()->fetch_assoc();
                    $checkStmt->close();

                    if ($existingUser) {
                        $message = "Username or email already exists.";
                        $messageType = "error";
                    } else {
                        if ($password) {
                            // Update with new password
                            $stmt = $conn->prepare("UPDATE users SET username=?, password=?, fullName=?, email=?, role=?, is_active=? WHERE id=?");
                            $stmt->bind_param("sssssii", $username, $password, $fullName, $email, $role, $isActive, $id);
                        } else {
                            // Update without changing password
                            $stmt = $conn->prepare("UPDATE users SET username=?, fullName=?, email=?, role=?, is_active=? WHERE id=?");
                            $stmt->bind_param("ssssii", $username, $fullName, $email, $role, $isActive, $id);
                        }

                        if ($stmt->execute()) {
                            $message = "User updated successfully!";
                            $messageType = "success";
                        } else {
                            $message = "Error updating user: " . $conn->error;
                            $messageType = "error";
                        }
                        $stmt->close();
                    }
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;

            case 'delete':
                $id = $_POST['user_id'] ?? '';
                if ($id) {
                    // Prevent deleting own account
                    if ($id == $_SESSION['user_id']) {
                        $message = "You cannot delete your own account.";
                        $messageType = "error";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
                        $stmt->bind_param("i", $id);

                        if ($stmt->execute()) {
                            $message = "User deleted successfully!";
                            $messageType = "success";
                        } else {
                            $message = "Error deleting user: " . $conn->error;
                            $messageType = "error";
                        }
                        $stmt->close();
                    }
                }
                break;
        }
    }
}

// Get search term
$search = $_GET['search'] ?? '';
$searchCondition = $search ? "WHERE username LIKE '%$search%' OR fullName LIKE '%$search%' OR email LIKE '%$search%'" : '';

// Fetch users
$usersQuery = "SELECT * FROM users $searchCondition ORDER BY created_at DESC";
$usersResult = $conn->query($usersQuery);

// Get user for editing
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editUser = $editStmt->get_result()->fetch_assoc();
    $editStmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/users.css">
    <script src="js/common.js"></script>
</head>
<body>
    <header class="topbar">
        <div class="datetime-display">
            <div class="current-date" id="currentDate"></div>
            <div class="current-time" id="currentTime"></div>
        </div>
        <div class="brand">KNS Clinical System</div>
        <nav class="topbar-actions">
            <span class="user-chip">
                <?php echo $_SESSION['fullName']; ?>
                <small><?php echo ucfirst($_SESSION['role']); ?></small>
            </span>
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
        </nav>
    </header>

    <!-- Right-aligned modules row (outside the container) -->
    <section class="modules-right">
        <div class="modules-container">
            <div class="modules-row">
            <a class="card" href="dashboard.php">
                <div class="card-icon">🏠</div>
                <div class="card-title">Dashboard</div>
                <div class="card-desc">Main dashboard with overview and quick access.</div>
            </a>
            <a class="card" href="patients.php">
                <div class="card-icon">👨‍⚕️</div>
                <div class="card-title">Patients</div>
                <div class="card-desc">Register, view, and manage patient profiles.</div>
            </a>
            <a class="card" href="appointments.php">
                <div class="card-icon">📅</div>
                <div class="card-title">Appointments</div>
                <div class="card-desc">Schedule and track clinic appointments.</div>
            </a>
            <a class="card" href="records.php">
                <div class="card-icon">📄</div>
                <div class="card-title">Medical Records</div>
                <div class="card-desc">Access treatment history and clinical notes.</div>
            </a>
            <a class="card" href="medical_certificate.php">
                <div class="card-icon">📝</div>
                <div class="card-title">Medical Certificate</div>
                <div class="card-desc">Create, edit, and print patient certificates.</div>
            </a>
            <a class="card" href="inventory.php">
                <div class="card-icon">🏥</div>
                <div class="card-title">Inventory</div>
                <div class="card-desc">Manage medicines, equipment, and stock levels.</div>
            </a>
            <a class="card" href="school_activities.php">
                <div class="card-icon">🎓</div>
                <div class="card-title">School Activities & Events</div>
                <div class="card-desc">Manage school activities, events, and schedules.</div>
            </a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
            <a class="card" href="reports.php">
                <div class="card-icon">📊</div>
                <div class="card-title">Reports</div>
                <div class="card-desc">Operational and clinical reporting.</div>
            </a>
            <a class="card" href="archive.php">
                <div class="card-icon">📦</div>
                <div class="card-title">Archive</div>
                <div class="card-desc">Manage archived records and data.</div>
            </a>
            <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="users-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>Manage Users</h1>
                <button class="btn-primary" onclick="openModal('addModal')">+ Add New User</button>
            </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" style="display: flex; gap: 12px; width: 100%;">
                <input type="text" name="search" class="search-input" placeholder="Search users by name, username, or email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
            <?php if ($usersResult && $usersResult->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $usersResult->fetch_assoc()): ?>
                            <tr class="<?php echo $user['id'] == $_SESSION['user_id'] ? 'current-user' : ''; ?>">
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                        <small style="color: var(--brand-600);">(You)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($user['fullName']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?edit=<?php echo $user['id']; ?>" class="btn-secondary">Update</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-users">
                    <h3>No users found</h3>
                    <p><?php echo $search ? 'Try adjusting your search terms.' : 'Add your first user to get started.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $editUser ? 'Edit User' : 'Add New User'; ?></h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editUser ? 'edit' : 'add'; ?>">
                <?php if ($editUser): ?>
                    <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($editUser['email'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="fullName">Full Name *</label>
                        <input type="text" id="fullName" name="fullName" required value="<?php echo htmlspecialchars($editUser['fullName'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="role">Role *</label>
                        <select id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="admin" <?php echo ($editUser && $editUser['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="assistant" <?php echo ($editUser && $editUser['role'] === 'assistant') ? 'selected' : ''; ?>>Assistant</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password <?php echo $editUser ? '(leave blank to keep current)' : '*'; ?></label>
                    <input type="password" id="password" name="password" <?php echo $editUser ? '' : 'required'; ?>>
                </div>

                <?php if ($editUser): ?>
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" id="is_active" name="is_active" <?php echo $editUser['is_active'] ? 'checked' : ''; ?>>
                        <label for="is_active">Active User</label>
                    </div>
                </div>
                <?php endif; ?>

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-primary"><?php echo $editUser ? 'Update User' : 'Add User'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Users-specific functionality
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Clear form if it's add modal
            if (modalId === 'addModal' && !<?php echo $editUser ? 'true' : 'false'; ?>) {
                document.querySelector('#addModal form').reset();
            }
        }

        // Auto-open edit modal if editing
        <?php if ($editUser): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('addModal');
        });
        <?php endif; ?>
    </script>

    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>
