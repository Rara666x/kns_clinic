<?php
include('includes/auth.php');
include('db_connection.php');

// Get report type and date range
$reportType = $_GET['report_type'] ?? 'monthly';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Set date ranges based on report type
switch ($reportType) {
    case 'weekly':
        $startDate = $startDate ?: date('Y-m-d', strtotime('monday this week'));
        $endDate = $endDate ?: date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'monthly':
        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-t');
        break;
    case 'quarterly':
        $currentQuarter = ceil(date('n') / 3);
        $startDate = $startDate ?: date('Y-' . (($currentQuarter - 1) * 3 + 1) . '-01');
        $endDate = $endDate ?: date('Y-' . ($currentQuarter * 3) . '-t');
        break;
    case 'annually':
        $startDate = $startDate ?: date('Y-01-01');
        $endDate = $endDate ?: date('Y-12-31');
        break;
    default:
        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-d');
}

// Get report statistics
$stats = [];

// Total patients
$result = $conn->query("SELECT COUNT(*) as total FROM patients");
$stats['total_patients'] = $result->fetch_assoc()['total'];

// New patients in current period
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM patients WHERE created_at BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$stats['new_patients_period'] = $result->fetch_assoc()['total'];
$stmt->close();

// Previous period comparison
$prevStartDate = '';
$prevEndDate = '';
switch ($reportType) {
    case 'weekly':
        $prevStartDate = date('Y-m-d', strtotime('monday last week'));
        $prevEndDate = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'monthly':
        $prevStartDate = date('Y-m-01', strtotime('first day of last month'));
        $prevEndDate = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'quarterly':
        $currentQuarter = ceil(date('n') / 3);
        $prevQuarter = $currentQuarter - 1;
        if ($prevQuarter == 0) {
            $prevQuarter = 4;
            $prevYear = date('Y') - 1;
        } else {
            $prevYear = date('Y');
        }
        $prevStartDate = $prevYear . '-' . (($prevQuarter - 1) * 3 + 1) . '-01';
        $prevEndDate = $prevYear . '-' . ($prevQuarter * 3) . '-t';
        break;
    case 'annually':
        $prevYear = date('Y') - 1;
        $prevStartDate = $prevYear . '-01-01';
        $prevEndDate = $prevYear . '-12-31';
        break;
}

// New patients in previous period
if ($prevStartDate && $prevEndDate) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM patients WHERE created_at BETWEEN ? AND ?");
    $stmt->bind_param("ss", $prevStartDate, $prevEndDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $prevNewPatients = $result->fetch_assoc()['total'];
    $stats['new_patients_change'] = $stats['new_patients_period'] - $prevNewPatients;
    $stats['new_patients_change_percent'] = $prevNewPatients > 0 ? round(($stats['new_patients_change'] / $prevNewPatients) * 100, 1) : 0;
    $stmt->close();
}

// Total appointments
$result = $conn->query("SELECT COUNT(*) as total FROM appointments");
$stats['total_appointments'] = $result->fetch_assoc()['total'];

// Appointments in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$stats['appointments_range'] = $result->fetch_assoc()['total'];
$stmt->close();

// Appointments in previous period for comparison
if ($prevStartDate && $prevEndDate) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN ? AND ?");
    $stmt->bind_param("ss", $prevStartDate, $prevEndDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $prevAppointments = $result->fetch_assoc()['total'];
    $stats['appointments_change'] = $stats['appointments_range'] - $prevAppointments;
    $stats['appointments_change_percent'] = $prevAppointments > 0 ? round(($stats['appointments_change'] / $prevAppointments) * 100, 1) : 0;
    $stmt->close();
}


// Appointment status breakdown
$stmt = $conn->prepare("SELECT status, COUNT(*) as count FROM appointments WHERE appointment_date BETWEEN ? AND ? GROUP BY status");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$appointmentStatuses = [];
while ($row = $result->fetch_assoc()) {
    $appointmentStatuses[$row['status']] = $row['count'];
}
$stmt->close();

// Calculate completed appointments
$completedAppointments = isset($appointmentStatuses['completed']) ? $appointmentStatuses['completed'] : 0;

// Medical records in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM medical_records WHERE record_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$stats['records_range'] = $result->fetch_assoc()['total'];
$stmt->close();

// Record type breakdown
$stmt = $conn->prepare("SELECT record_type, COUNT(*) as count FROM medical_records WHERE record_date BETWEEN ? AND ? GROUP BY record_type");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$recordTypes = [];
while ($row = $result->fetch_assoc()) {
    $recordTypes[$row['record_type']] = $row['count'];
}
$stmt->close();

// Recent appointments
$stmt = $conn->prepare("SELECT a.appointment_date, a.appointment_time, a.status, p.first_name, p.last_name FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.appointment_date BETWEEN ? AND ? ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 10");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$recentAppointments = $stmt->get_result();
$stmt->close();

// Recent medical records
$stmt = $conn->prepare("SELECT mr.record_type, mr.title, mr.record_date, p.first_name, p.last_name FROM medical_records mr JOIN patients p ON mr.patient_id = p.id WHERE mr.record_date BETWEEN ? AND ? ORDER BY mr.record_date DESC LIMIT 10");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$recentRecords = $stmt->get_result();
$stmt->close();

// Medical certificates statistics
$result = $conn->query("SELECT COUNT(*) as total FROM medical_certificates");
$stats['total_certificates'] = $result->fetch_assoc()['total'];

// Certificates in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM medical_certificates WHERE issue_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$stats['certificates_range'] = $result->fetch_assoc()['total'];
$stmt->close();

// Printed certificates in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM medical_certificates WHERE issue_date BETWEEN ? AND ? AND status = 'printed'");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$printedCertificates = $result->fetch_assoc()['total'];
$stmt->close();

// Recent medical certificates
$stmt = $conn->prepare("SELECT mc.certificate_number, mc.issue_date, mc.status, p.first_name, p.last_name FROM medical_certificates mc JOIN patients p ON mc.patient_id = p.id WHERE mc.issue_date BETWEEN ? AND ? ORDER BY mc.issue_date DESC LIMIT 10");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$recentCertificates = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Reports - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/reports.css">
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

    <div class="reports-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>Clinical Reports</h1>
                <div style="color: var(--text-500); font-size: 14px;">
                    Report Period: <?php echo date('M j, Y', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?>
                    <br><strong><?php echo ucfirst($reportType); ?> Report</strong>
                </div>
            </div>

        <!-- Report Type Buttons -->
        <div class="report-type-buttons">
            <a href="?report_type=weekly" class="report-btn <?php echo $reportType == 'weekly' ? 'active' : ''; ?>">
                📅 Weekly Report
            </a>
            <a href="?report_type=monthly" class="report-btn <?php echo $reportType == 'monthly' ? 'active' : ''; ?>">
                📊 Monthly Report
            </a>
            <a href="?report_type=quarterly" class="report-btn <?php echo $reportType == 'quarterly' ? 'active' : ''; ?>">
                📈 Quarterly Report
            </a>
            <a href="?report_type=annually" class="report-btn <?php echo $reportType == 'annually' ? 'active' : ''; ?>">
                🏆 Annual Report
            </a>
        </div>

        <div class="date-filters">
            <form method="GET" style="display: contents;">
                <input type="hidden" name="report_type" value="<?php echo $reportType; ?>">
                <div class="filter-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                <div class="filter-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Update Reports</button>
                </div>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="value"><?php echo number_format($stats['total_patients']); ?></div>
                <div class="subtitle">All time</div>
            </div>
            <div class="stat-card">
                <h3>New Patients</h3>
                <div class="value"><?php echo number_format($stats['new_patients_period']); ?></div>
                <div class="subtitle">
                    <?php echo ucfirst($reportType); ?> period
                    <?php if (isset($stats['new_patients_change'])): ?>
                        <span class="trend <?php echo $stats['new_patients_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['new_patients_change'] >= 0 ? '↗' : '↘'; ?> 
                            <?php echo abs($stats['new_patients_change_percent']); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Appointments</h3>
                <div class="value"><?php echo number_format($stats['appointments_range']); ?></div>
                <div class="subtitle">
                    <?php echo ucfirst($reportType); ?> period
                    <?php if (isset($stats['appointments_change'])): ?>
                        <span class="trend <?php echo $stats['appointments_change'] >= 0 ? 'positive' : 'negative'; ?>">
                            <?php echo $stats['appointments_change'] >= 0 ? '↗' : '↘'; ?> 
                            <?php echo abs($stats['appointments_change_percent']); ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Medical Records</h3>
                <div class="value"><?php echo number_format($stats['records_range']); ?></div>
                <div class="subtitle"><?php echo ucfirst($reportType); ?> period</div>
            </div>
            <div class="stat-card">
                <h3>Completion Rate</h3>
                <div class="value">
                    <?php 
                    $completionRate = $stats['appointments_range'] > 0 ? 
                        round(($completedAppointments / $stats['appointments_range']) * 100, 1) : 0;
                    echo $completionRate; 
                    ?>%
                </div>
                <div class="subtitle">Appointments completed</div>
            </div>
            <div class="stat-card">
                <h3>Medical Certificates</h3>
                <div class="value"><?php echo number_format($stats['certificates_range']); ?></div>
                <div class="subtitle"><?php echo ucfirst($reportType); ?> period</div>
            </div>
            <div class="stat-card">
                <h3>Printed Certificates</h3>
                <div class="value"><?php echo number_format($printedCertificates); ?></div>
                <div class="subtitle">Certificates printed</div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="export-section">
            <h3>Export Report</h3>
            <div class="export-buttons">
                <a href="export_report.php?type=pdf&report_type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="export-btn pdf">
                    📄 Export as PDF
                </a>
                <a href="export_report.php?type=excel&report_type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="export-btn excel">
                    📊 Export as Excel
                </a>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3>Appointment Status Breakdown</h3>
                <?php if (!empty($appointmentStatuses)): ?>
                    <div class="chart-container">
                        <?php 
                        $totalAppointments = array_sum($appointmentStatuses);
                        foreach ($appointmentStatuses as $status => $count): 
                            $percentage = $totalAppointments > 0 ? round(($count / $totalAppointments) * 100, 1) : 0;
                        ?>
                            <div class="chart-item">
                                <div class="chart-bar">
                                    <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php 
                                        echo $status == 'completed' ? '#10b981' : 
                                            ($status == 'scheduled' ? '#3b82f6' : 
                                            ($status == 'cancelled' ? '#ef4444' : '#f59e0b')); 
                                    ?>"></div>
                                </div>
                                <div class="chart-info">
                                    <span class="chart-label"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                    <span class="chart-value"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">No appointments in selected period</div>
                <?php endif; ?>
            </div>
            
            <div class="chart-card">
                <h3>Medical Record Types</h3>
                <?php if (!empty($recordTypes)): ?>
                    <div class="chart-container">
                        <?php 
                        $totalRecords = array_sum($recordTypes);
                        foreach ($recordTypes as $type => $count): 
                            $percentage = $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0;
                        ?>
                            <div class="chart-item">
                                <div class="chart-bar">
                                    <div class="chart-bar-fill" style="width: <?php echo $percentage; ?>%; background-color: <?php 
                                        echo $type == 'Consultation' ? '#3b82f6' : 
                                            ($type == 'Diagnosis' ? '#f59e0b' : 
                                            ($type == 'Treatment' ? '#10b981' : 
                                            ($type == 'Lab' ? '#8b5cf6' : '#ec4899'))); 
                                    ?>"></div>
                                </div>
                                <div class="chart-info">
                                    <span class="chart-label"><?php echo $type; ?></span>
                                    <span class="chart-value"><?php echo $count; ?> (<?php echo $percentage; ?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">No medical records in selected period</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Data Tables -->
        <div class="tables-grid">
            <div class="table-card">
                <h3>Recent Appointments</h3>
                <div class="table-content">
                    <?php if ($recentAppointments->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($appointment = $recentAppointments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                                        
                                        <td><?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">No appointments in selected period</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <h3>Recent Medical Records</h3>
                <div class="table-content">
                    <?php if ($recentRecords->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($record = $recentRecords->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                        <td>
                                            <span class="type-badge type-<?php echo $record['record_type']; ?>">
                                                <?php echo $record['record_type']; ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($record['title']); ?>">
                                            <?php echo htmlspecialchars($record['title']); ?>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">No medical records in selected period</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-card">
                <h3>Recent Medical Certificates</h3>
                <div class="table-content">
                    <?php if ($recentCertificates->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Certificate Number</th>
                                    <th>Issue Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($certificate = $recentCertificates->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($certificate['first_name'] . ' ' . $certificate['last_name']); ?></td>
                                        <td>
                                            <span class="certificate-number">
                                                <?php echo htmlspecialchars($certificate['certificate_number']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($certificate['issue_date'])); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $certificate['status']; ?>">
                                                <?php echo ucfirst($certificate['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-data">No medical certificates in selected period</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </div>

    <script>
        // Reports-specific functionality - common functions are in common.js
    </script>
    
    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>