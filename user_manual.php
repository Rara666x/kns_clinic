<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Log page view activity
$action = 'page_view';
$description = 'Viewed User Manual page';
$ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$username = $_SESSION['fullName'] ?? 'Unknown';

$stmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $_SESSION['user_id'], $username, $action, $description, $ip_address, $user_agent);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Manual - Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/user_manual.css">
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

    <div class="manual-container">
        <div class="page-header">
            <h1 class="page-title">📖 Users Manual</h1>
            <p class="page-subtitle">Complete guide to using the Clinical System</p>
            <div class="welcome-badge">Welcome, <?php echo $_SESSION['fullName']; ?>!</div>
        </div>

        <div class="quick-start">
            <h2>🚀 Quick Start Guide</h2>
            <p>Get started with the Clinical System in just a few steps</p>
            <div class="start-buttons">
                <a href="dashboard.php" class="btn-primary">Go to Dashboard</a>
            </div>
        </div>

        <div class="manual-content">
            <div class="manual-section">
                <h2 class="section-title">
                    <span class="section-icon">👥</span>
                    Patient Management
                </h2>
                <ul class="feature-list">
                    <li class="feature-item">
                        <span class="feature-icon">📝</span>
                        <div class="feature-content">
                            <h4>Add New Patients</h4>
                            <p>Register new patients with complete personal and medical information</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">🔍</span>
                        <div class="feature-content">
                            <h4>Search & Filter</h4>
                            <p>Quickly find patients using search filters and advanced options</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">📊</span>
                        <div class="feature-content">
                            <h4>View Records</h4>
                            <p>Access complete medical history and treatment records</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="manual-section">
                <h2 class="section-title">
                    <span class="section-icon">📅</span>
                    Appointments
                </h2>
                <ul class="feature-list">
                    <li class="feature-item">
                        <span class="feature-icon">➕</span>
                        <div class="feature-content">
                            <h4>Schedule Appointments</h4>
                            <p>Book appointments with date, time, and type specifications</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">📋</span>
                        <div class="feature-content">
                            <h4>Manage Schedule</h4>
                            <p>View, edit, and cancel appointments as needed</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">🔔</span>
                        <div class="feature-content">
                            <h4>Notifications</h4>
                            <p>Stay updated with appointment reminders and alerts</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="manual-section">
                <h2 class="section-title">
                    <span class="section-icon">💊</span>
                    Medicine Inventory
                </h2>
                <ul class="feature-list">
                    <li class="feature-item">
                        <span class="feature-icon">📦</span>
                        <div class="feature-content">
                            <h4>Stock Management</h4>
                            <p>Track medicine quantities and manage inventory levels</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">🆕</span>
                        <div class="feature-content">
                            <h4>New Stock Alerts</h4>
                            <p>Get notified when new medicines arrive in stock</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">⚠️</span>
                        <div class="feature-content">
                            <h4>Low Stock Warnings</h4>
                            <p>Receive alerts when medicines are running low</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="manual-section">
                <h2 class="section-title">
                    <span class="section-icon">🎓</span>
                    School Activities
                </h2>
                <ul class="feature-list">
                    <li class="feature-item">
                        <span class="feature-icon">🏃‍♂️</span>
                        <div class="feature-content">
                            <h4>Event Management</h4>
                            <p>Organize and track school events and activities</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">⚕️</span>
                        <div class="feature-content">
                            <h4>Medical Team</h4>
                            <p>Assign medical teams and track health incidents</p>
                        </div>
                    </li>
                    <li class="feature-item">
                        <span class="feature-icon">📊</span>
                        <div class="feature-content">
                            <h4>Quick Actions</h4>
                            <p>Use quick buttons for common events like Sports Fest, Graduation</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>


        <div class="contact-info">
            <h3>📞 Support & Contact</h3>
            <p>Need additional help? Our support team is here to assist you.</p>
            <div class="contact-details">
                <div class="contact-item">
                    <span>📱</span>
                    <span>+639082963574</span>
                </div>
                <div class="contact-item">
                    <span>🕒</span>
                    <span>Mon-Fri 8AM-6PM</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User manual-specific functionality - common functions are in common.js

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
