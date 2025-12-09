<?php
include('includes/auth.php');
include('db_connection.php');
include('includes/archive_functions.php');

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'restore_patient':
                $archiveId = $_POST['archive_id'] ?? '';
                if ($archiveId) {
                    $result = restorePatient($conn, $archiveId, $_SESSION['user_id']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;

        }
    }
}

// Get archive statistics
$stats = getArchiveStats($conn);

// Get archived patients
$archivedPatients = $conn->query("SELECT * FROM archived_patients ORDER BY archived_at DESC LIMIT 50");

// Get archived appointments
$archivedAppointments = $conn->query("SELECT * FROM archived_appointments ORDER BY archived_at DESC LIMIT 50");

// Get archived medical records
$archivedRecords = $conn->query("SELECT * FROM archived_medical_records ORDER BY archived_at DESC LIMIT 50");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Archive - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/archive.css">
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

    <div class="archive-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>📦 Archive Management</h1>
                <div style="color: var(--text-500); font-size: 14px;">
                    Manage archived records and data
                </div>
            </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Archive Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📋 Archived Patients</h3>
                <div class="stat-value"><?php echo $stats['archived_patients']; ?></div>
                <div class="stat-subtitle">Total archived patients</div>
            </div>
            <div class="stat-card">
                <h3>📅 Archived Appointments</h3>
                <div class="stat-value"><?php echo $stats['archived_appointments']; ?></div>
                <div class="stat-subtitle">Total archived appointments</div>
            </div>
            <div class="stat-card">
                <h3>📄 Archived Records</h3>
                <div class="stat-value"><?php echo $stats['archived_records']; ?></div>
                <div class="stat-subtitle">Total archived medical records</div>
            </div>
        </div>

        <!-- Archived Patients -->
        <div class="archive-section">
            <h3>👨‍⚕️ Archived Patients</h3>
            <?php if ($archivedPatients && $archivedPatients->num_rows > 0): ?>
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Archived Date</th>
                            <th>Reason</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($patient = $archivedPatients->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($patient['archived_at'])); ?></td>
                                <td><?php echo htmlspecialchars($patient['archive_reason']); ?></td>
                                <td>
                                    <div class="actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="restore_patient">
                                            <input type="hidden" name="archive_id" value="<?php echo $patient['id']; ?>">
                                            <button type="submit" class="btn-restore" onclick="return confirm('Are you sure you want to restore this patient?')">Restore</button>
                                        </form>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="permanent_delete">
                                            <input type="hidden" name="archive_id" value="<?php echo $patient['id']; ?>">
                                            <input type="hidden" name="table" value="patients">
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No archived patients found.</div>
            <?php endif; ?>
        </div>

        <!-- Archived Appointments -->
        <div class="archive-section">
            <h3>📅 Archived Appointments</h3>
            <?php if ($archivedAppointments && $archivedAppointments->num_rows > 0): ?>
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Archived Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $archivedAppointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($appointment['archived_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No archived appointments found.</div>
            <?php endif; ?>
        </div>

        <!-- Archived Medical Records -->
        <div class="archive-section">
            <h3>📄 Archived Medical Records</h3>
            <?php if ($archivedRecords && $archivedRecords->num_rows > 0): ?>
                <table class="archive-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Record Type</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Archived Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $archivedRecords->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($record['patient_name']); ?></td>
                                <td><?php echo htmlspecialchars($record['record_type']); ?></td>
                                <td><?php echo htmlspecialchars($record['title']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                                <td><?php echo htmlspecialchars($record['doctor_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($record['archived_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">No archived medical records found.</div>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <script>
        // Archive-specific functionality - common functions are in common.js
    </script>

    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>
