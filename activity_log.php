<?php
include('includes/auth.php');
include('db_connection.php');

// Create activity_logs table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        username VARCHAR(255),
        action VARCHAR(255) NOT NULL,
        description TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
");

// Handle logout logging
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $userId = $_SESSION['userId'] ?? null;
    $username = $_SESSION['username'] ?? 'Unknown';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) VALUES (?, ?, 'logout', 'User logged out', ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $ipAddress, $userAgent);
    $stmt->execute();
    $stmt->close();
}

// Log current page view
$userId = $_SESSION['userId'] ?? null;
$username = $_SESSION['username'] ?? 'Unknown';
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

$stmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) VALUES (?, ?, 'view_activity_log', 'Viewed activity log page', ?, ?)");
$stmt->bind_param("isss", $userId, $username, $ipAddress, $userAgent);
$stmt->execute();
$stmt->close();

// Get filter parameters
$dateFilter = $_GET['date'] ?? '';
$userFilter = $_GET['user'] ?? '';
$actionFilter = $_GET['action'] ?? '';

// Build query with filters
$whereConditions = [];
$params = [];
$paramTypes = '';

if ($dateFilter) {
    $whereConditions[] = "DATE(created_at) = ?";
    $params[] = $dateFilter;
    $paramTypes .= 's';
}

if ($userFilter) {
    $whereConditions[] = "username LIKE ?";
    $params[] = "%$userFilter%";
    $paramTypes .= 's';
}

if ($actionFilter) {
    $whereConditions[] = "action LIKE ?";
    $params[] = "%$actionFilter%";
    $paramTypes .= 's';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get activity logs
$query = "SELECT * FROM activity_logs $whereClause ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($query);
if ($params) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$activityLogs = $stmt->get_result();

// Get unique users for filter
$users = $conn->query("SELECT DISTINCT username FROM activity_logs ORDER BY username");

// Get unique actions for filter
$actions = $conn->query("SELECT DISTINCT action FROM activity_logs ORDER BY action");

// Get statistics
$stats = [];

// Total log entries
$result = $conn->query("SELECT COUNT(*) as total FROM activity_logs");
$stats['total_logs'] = $result->fetch_assoc()['total'];

// Today's activities
$result = $conn->query("SELECT COUNT(*) as today FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$stats['today_logs'] = $result->fetch_assoc()['today'];

// Unique users today
$result = $conn->query("SELECT COUNT(DISTINCT username) as users_today FROM activity_logs WHERE DATE(created_at) = CURDATE()");
$stats['users_today'] = $result->fetch_assoc()['users_today'];

// Most active user
$result = $conn->query("SELECT username, COUNT(*) as activity_count FROM activity_logs GROUP BY username ORDER BY activity_count DESC LIMIT 1");
$mostActive = $result->fetch_assoc();
$stats['most_active_user'] = $mostActive['username'] ?? 'N/A';
$stats['most_active_count'] = $mostActive['activity_count'] ?? 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Log - Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/activity_log.css">
</head>
<body>
    <header class="topbar">
        <div class="brand">Clinical System</div>
        <nav class="topbar-actions">
            <a href="dashboard.php" class="btn-dashboard">← Dashboard</a>
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

    <div class="log-container">
        <div class="page-header">
            <h1>📋 Activity Log</h1>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo number_format($stats['total_logs']); ?></div>
                <div class="stat-label">Total Log Entries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo number_format($stats['today_logs']); ?></div>
                <div class="stat-label">Today's Activities</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['users_today']); ?></div>
                <div class="stat-label">Active Users Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-value"><?php echo htmlspecialchars($stats['most_active_user']); ?></div>
                <div class="stat-label">Most Active User (<?php echo $stats['most_active_count']; ?> activities)</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" style="display: contents;">
                <div class="filters-row">
                    <div class="filter-group">
                        <label>Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                    </div>
                    <div class="filter-group">
                        <label>User</label>
                        <select name="user">
                            <option value="">All Users</option>
                            <?php while ($user = $users->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($user['username']); ?>" 
                                        <?php echo $userFilter === $user['username'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Action</label>
                        <select name="action">
                            <option value="">All Actions</option>
                            <?php while ($action = $actions->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($action['action']); ?>" 
                                        <?php echo $actionFilter === $action['action'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($action['action']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn-primary">Filter</button>
                        <a href="activity_log.php" class="btn-secondary" style="margin-top: 8px; text-align: center;">Clear</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Activity Log Table -->
        <div class="log-table">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($activityLogs && $activityLogs->num_rows > 0): ?>
                            <?php while ($log = $activityLogs->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo strtoupper(substr($log['username'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div style="font-weight: 600;"><?php echo htmlspecialchars($log['username']); ?></div>
                                                <div class="ip-info">ID: <?php echo $log['user_id'] ?? 'N/A'; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="action-badge action-<?php echo $log['action']; ?>">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($log['description']); ?></td>
                                    <td>
                                        <div class="ip-info"><?php echo htmlspecialchars($log['ip_address']); ?></div>
                                    </td>
                                    <td>
                                        <div><?php echo date('M j, Y', strtotime($log['created_at'])); ?></div>
                                        <div class="ip-info"><?php echo date('g:i:s A', strtotime($log['created_at'])); ?></div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">
                                    No activity logs found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Activity log-specific functionality - common functions are in common.js
    </script>
</body>
</html>