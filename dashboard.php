<?php
include('includes/auth.php');
include('db_connection.php');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/common.js"></script>
    <script src="js/module-highlighting.js"></script>
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

    <div class="dashboard-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>Welcome back, <?php echo $_SESSION['fullName']; ?>.</h1>
                <p class="muted">Please choose a module to continue.</p>
            </div>

        <?php if ($_SESSION['role'] == 'admin'): ?>
        <!-- Inventory Overview Section -->
        <section class="dashboard-top-section">
            <!-- Real-time Activity Feed -->
            <div class="activity-feed">
                <div class="activity-header">
                    <h3>🔄 Real-time Activity</h3>
                </div>
                <div class="activity-list" id="activity-list">
                    <div class="activity-item">
                        <span class="activity-icon">⏳</span>
                        <span class="activity-text">Loading recent activities...</span>
                        <span class="activity-time">Just now</span>
                    </div>
                </div>
            </div>
            
            <!-- Inventory Overview -->
            <div class="inventory-overview">
            <h2>🏥 Inventory Overview</h2>
            
            <!-- Inventory Left Placeholder -->
            <div class="inventory-left-placeholder">
                <div class="placeholder-content">
                    <div class="placeholder-icon">📊</div>
                    <div class="placeholder-text">Inventory Analytics</div>
                    <div class="placeholder-desc">Detailed inventory insights and trends</div>
                </div>
            </div>
            
            <div class="inventory-grid">
                <!-- Medicine Stock Status -->
                <div class="inventory-card medicines">
                    <div class="inventory-header">
                        <h3>💊 Medicine Stock</h3>
                        <a href="medicines.php" class="view-all-btn">View All</a>
                    </div>
                    <div class="inventory-stats">
                        <?php
                        // Get medicine statistics
                        $result = $conn->query("SELECT COUNT(*) as total FROM medicines WHERE is_active = 1");
                        $totalMedicines = $result->fetch_assoc()['total'];
                        
                        // Low stock medicines
                        $result = $conn->query("SELECT COUNT(*) as low_stock FROM medicines WHERE current_stock <= minimum_stock AND is_active = 1");
                        $lowStockMedicines = $result->fetch_assoc()['low_stock'];
                        
                        // Expired medicines
                        $result = $conn->query("SELECT COUNT(*) as expired FROM medicines WHERE expiry_date < CURDATE() AND is_active = 1");
                        $expiredMedicines = $result->fetch_assoc()['expired'];
                        
                        // Expiring soon (within 30 days)
                        $result = $conn->query("SELECT COUNT(*) as expiring_soon FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND is_active = 1");
                        $expiringSoonMedicines = $result->fetch_assoc()['expiring_soon'];
                        ?>
                        <div class="stat-item">
                            <span class="stat-label">Total Medicines</span>
                            <span class="stat-value"><?php echo $totalMedicines; ?></span>
                        </div>
                        <div class="stat-item <?php echo $lowStockMedicines > 0 ? 'alert' : ''; ?>">
                            <span class="stat-label">Low Stock</span>
                            <span class="stat-value"><?php echo $lowStockMedicines; ?></span>
                        </div>
                        <div class="stat-item <?php echo $expiredMedicines > 0 ? 'critical' : ''; ?>">
                            <span class="stat-label">Expired</span>
                            <span class="stat-value"><?php echo $expiredMedicines; ?></span>
                        </div>
                        <div class="stat-item <?php echo $expiringSoonMedicines > 0 ? 'warning' : ''; ?>">
                            <span class="stat-label">Expiring Soon</span>
                            <span class="stat-value"><?php echo $expiringSoonMedicines; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Medical Equipment Status -->
                <div class="inventory-card equipment">
                    <div class="inventory-header">
                        <h3>🔧 Medical Equipment</h3>
                        <a href="equipment.php" class="view-all-btn">View All</a>
                    </div>
                    <div class="inventory-stats">
                        <?php
                        // Get equipment statistics
                        $result = $conn->query("SELECT COUNT(*) as total FROM medical_equipment WHERE is_active = 1");
                        $totalEquipment = $result->fetch_assoc()['total'];
                        
                        // Operational equipment
                        $result = $conn->query("SELECT COUNT(*) as operational FROM medical_equipment WHERE status = 'operational' AND is_active = 1");
                        $operationalEquipment = $result->fetch_assoc()['operational'];
                        
                        // Equipment needing maintenance
                        $result = $conn->query("SELECT COUNT(*) as maintenance_due FROM medical_equipment WHERE maintenance_due <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND is_active = 1");
                        $maintenanceDueEquipment = $result->fetch_assoc()['maintenance_due'];
                        
                        // Out of order equipment
                        $result = $conn->query("SELECT COUNT(*) as out_of_order FROM medical_equipment WHERE status = 'out_of_order' AND is_active = 1");
                        $outOfOrderEquipment = $result->fetch_assoc()['out_of_order'];
                        ?>
                        <div class="stat-item">
                            <span class="stat-label">Total Equipment</span>
                            <span class="stat-value"><?php echo $totalEquipment; ?></span>
                        </div>
                        <div class="stat-item success">
                            <span class="stat-label">Operational</span>
                            <span class="stat-value"><?php echo $operationalEquipment; ?></span>
                        </div>
                        <div class="stat-item <?php echo $maintenanceDueEquipment > 0 ? 'warning' : ''; ?>">
                            <span class="stat-label">Maintenance Due</span>
                            <span class="stat-value"><?php echo $maintenanceDueEquipment; ?></span>
                        </div>
                        <div class="stat-item <?php echo $outOfOrderEquipment > 0 ? 'critical' : ''; ?>">
                            <span class="stat-label">Out of Order</span>
                            <span class="stat-value"><?php echo $outOfOrderEquipment; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Alerts -->
            <div class="inventory-alerts">
                <?php
                // Show alerts for critical issues
                $alerts = [];
                
                // Medicine alerts
                if ($expiredMedicines > 0) {
                    $alerts[] = [
                        'type' => 'critical',
                        'icon' => '⚠️',
                        'message' => $expiredMedicines . ' medicine(s) have expired and need immediate attention'
                    ];
                }
                
                if ($expiringSoonMedicines > 0) {
                    $alerts[] = [
                        'type' => 'warning',
                        'icon' => '⏰',
                        'message' => $expiringSoonMedicines . ' medicine(s) are expiring within 30 days'
                    ];
                }
                
                if ($lowStockMedicines > 0) {
                    $alerts[] = [
                        'type' => 'warning',
                        'icon' => '📦',
                        'message' => $lowStockMedicines . ' medicine(s) are running low on stock'
                    ];
                }

                // Equipment alerts
                if ($outOfOrderEquipment > 0) {
                    $alerts[] = [
                        'type' => 'critical',
                        'icon' => '🔧',
                        'message' => $outOfOrderEquipment . ' equipment item(s) are out of order'
                    ];
                }
                
                if ($maintenanceDueEquipment > 0) {
                    $alerts[] = [
                        'type' => 'warning',
                        'icon' => '🔧',
                        'message' => $maintenanceDueEquipment . ' equipment item(s) need maintenance'
                    ];
                }

                // Display alerts
                if (!empty($alerts)) {
                    echo '<div class="alerts-section">';
                    echo '<h2>🚨 Quick Alerts</h2>';
                    echo '<div class="alerts-container">';
                    foreach ($alerts as $alert) {
                        echo '<div class="alert-item ' . $alert['type'] . '">';
                        echo '<span class="alert-icon">' . $alert['icon'] . '</span>';
                        echo '<span class="alert-message">' . $alert['message'] . '</span>';
                        echo '</div>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        </section>
        <?php else: ?>
        <!-- For non-admin users, show only activity feed -->
        <section class="activity-feed">
            <div class="activity-header">
                <h3>🔄 Real-time Activity</h3>
            </div>
            <div class="activity-list" id="activity-list">
                <div class="activity-item">
                    <span class="activity-icon">⏳</span>
                    <span class="activity-text">Loading recent activities...</span>
                    <span class="activity-time">Just now</span>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Dashboard Bottom Section: Quick Reports -->
        <section class="dashboard-bottom-section">
            <!-- Quick Reports Summary -->
            <div class="quick-reports">
                <h2>📊 Quick Reports</h2>
                <div class="quick-reports-grid">
                    <a href="reports.php?report_type=weekly" class="quick-report-card" data-period="weekly">
                        <div class="quick-report-icon">📅</div>
                        <div class="quick-report-title">Weekly Report</div>
                        <div class="quick-report-desc">This week's activity summary</div>
                        <div class="quick-report-stats">
                            <div class="stat-item">
                                <span class="stat-label">Appointments</span>
                                <span class="stat-value" id="weekly-appointments">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="weekly-appointments-bar"></div></div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">New Patients</span>
                                <span class="stat-value" id="weekly-patients">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="weekly-patients-bar"></div></div>
                            </div>
                        </div>
                        <div class="quick-report-trend" id="weekly-trend">
                            <span class="trend-icon">📈</span>
                            <span class="trend-text">Loading...</span>
                        </div>
                    </a>
                    <a href="reports.php?report_type=monthly" class="quick-report-card" data-period="monthly">
                        <div class="quick-report-icon">📊</div>
                        <div class="quick-report-title">Monthly Report</div>
                        <div class="quick-report-desc">Current month overview</div>
                        <div class="quick-report-stats">
                            <div class="stat-item">
                                <span class="stat-label">Appointments</span>
                                <span class="stat-value" id="monthly-appointments">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="monthly-appointments-bar"></div></div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">New Patients</span>
                                <span class="stat-value" id="monthly-patients">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="monthly-patients-bar"></div></div>
                            </div>
                        </div>
                        <div class="quick-report-trend" id="monthly-trend">
                            <span class="trend-icon">📈</span>
                            <span class="trend-text">Loading...</span>
                        </div>
                    </a>
                    <a href="reports.php?report_type=quarterly" class="quick-report-card" data-period="quarterly">
                        <div class="quick-report-icon">📈</div>
                        <div class="quick-report-title">Quarterly Report</div>
                        <div class="quick-report-desc">Quarter performance analysis</div>
                        <div class="quick-report-stats">
                            <div class="stat-item">
                                <span class="stat-label">Appointments</span>
                                <span class="stat-value" id="quarterly-appointments">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="quarterly-appointments-bar"></div></div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">New Patients</span>
                                <span class="stat-value" id="quarterly-patients">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="quarterly-patients-bar"></div></div>
                            </div>
                        </div>
                        <div class="quick-report-trend" id="quarterly-trend">
                            <span class="trend-icon">📈</span>
                            <span class="trend-text">Loading...</span>
                        </div>
                    </a>
                    <a href="reports.php?report_type=annually" class="quick-report-card" data-period="annually">
                        <div class="quick-report-icon">🏆</div>
                        <div class="quick-report-title">Annual Report</div>
                        <div class="quick-report-desc">Year-end comprehensive report</div>
                        <div class="quick-report-stats">
                            <div class="stat-item">
                                <span class="stat-label">Appointments</span>
                                <span class="stat-value" id="annually-appointments">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="annually-appointments-bar"></div></div>
                            </div>
                            <div class="stat-item">
                                <span class="stat-label">New Patients</span>
                                <span class="stat-value" id="annually-patients">-</span>
                                <div class="mini-bar"><div class="mini-bar-fill" id="annually-patients-bar"></div></div>
                            </div>
                        </div>
                        <div class="quick-report-trend" id="annually-trend">
                            <span class="trend-icon">📈</span>
                            <span class="trend-text">Loading...</span>
                        </div>
                    </a>
                </div>
            </div>
        </section>
        </div>
    </div>

    <script>
        // Dashboard-specific functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Update date and time
            updateDateTime();
            setInterval(updateDateTime, 1000);

            // Load dashboard statistics
            loadDashboardStats();

            // Load recent activities
            loadRecentActivities();
        });

        function loadDashboardStats() {
            fetch('api/dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Update quick report statistics
                    updateQuickReportStats('weekly', data.weekly);
                    updateQuickReportStats('monthly', data.monthly);
                    updateQuickReportStats('quarterly', data.quarterly);
                    updateQuickReportStats('annually', data.annually);
                })
                .catch(error => {
                    console.error('Error loading dashboard stats:', error);
                });
        }

        function updateQuickReportStats(period, stats) {
            if (stats) {
                // Update appointment counts
                const appointmentsElement = document.getElementById(period + '-appointments');
                if (appointmentsElement) {
                    appointmentsElement.textContent = stats.appointments || 0;
                }

                // Update patient counts
                const patientsElement = document.getElementById(period + '-patients');
                if (patientsElement) {
                    patientsElement.textContent = stats.patients || 0;
                }

                // Update trend
                const trendElement = document.getElementById(period + '-trend');
                if (trendElement && stats.trend) {
                    const trendText = trendElement.querySelector('.trend-text');
                    if (trendText) {
                        trendText.textContent = stats.trend.text || 'No change';
                    }
                }
            }
        }

        function loadRecentActivities() {
            fetch('api/recent_activities.php')
                .then(response => response.json())
                .then(data => {
                    const activityList = document.getElementById('activity-list');
                    if (!activityList) return;

                    if (!data || data.length === 0) {
                    activityList.innerHTML = `
                        <div class="activity-item">
                            <span class="activity-icon">ℹ️</span>
                            <span class="activity-text">No recent activities found</span>
                            <span class="activity-time">-</span>
                        </div>
                    `;
                    return;
                }

                    activityList.innerHTML = data.map(activity => `
                        <div class="activity-item">
                        <span class="activity-icon">${activity.icon || '📋'}</span>
                            <span class="activity-text">${activity.message}</span>
                            <span class="activity-time">${formatTimeAgo(activity.timestamp)}</span>
                    </div>
                `).join('');
                })
                .catch(error => {
                    console.error('Error loading recent activities:', error);
                    const activityList = document.getElementById('activity-list');
                    if (activityList) {
                        activityList.innerHTML = `
                            <div class="activity-item">
                                <span class="activity-icon">⚠️</span>
                                <span class="activity-text">Error loading activities</span>
                                <span class="activity-time">-</span>
                            </div>
                        `;
                    }
                });
        }

        function formatTimeAgo(timestamp) {
            const now = new Date();
            const activityTime = new Date(timestamp);
            const diffInSeconds = Math.floor((now - activityTime) / 1000);

            if (diffInSeconds < 60) {
                return 'Just now';
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60);
                return `${minutes}m ago`;
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600);
                return `${hours}h ago`;
            } else {
                const days = Math.floor(diffInSeconds / 86400);
                return `${days}d ago`;
            }
        }
    </script>
</body>
</html>