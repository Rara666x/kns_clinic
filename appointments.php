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
            case 'add':
                $patientId = $_POST['patient_id'] ?? '';
                $appointmentDate = $_POST['appointment_date'] ?? '';
                $appointmentTime = $_POST['appointment_time'] ?? '';
                $appointmentType = $_POST['appointment_type'] ?? '';
                $notes = $_POST['notes'] ?? '';
                
                if ($patientId && $appointmentDate && $appointmentTime && $appointmentType) {
                    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, appointment_type, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("issss", $patientId, $appointmentDate, $appointmentTime, $appointmentType, $notes);
                    
                    if ($stmt->execute()) {
                        $message = "Appointment scheduled successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error scheduling appointment: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'edit':
                $id = $_POST['appointment_id'] ?? '';
                $patientId = $_POST['patient_id'] ?? '';
                $appointmentDate = $_POST['appointment_date'] ?? '';
                $appointmentTime = $_POST['appointment_time'] ?? '';
                $appointmentType = $_POST['appointment_type'] ?? '';
                $status = $_POST['status'] ?? '';
                $notes = $_POST['notes'] ?? '';
                
                if ($id && $patientId && $appointmentDate && $appointmentTime && $appointmentType) {
                    $stmt = $conn->prepare("UPDATE appointments SET patient_id=?, appointment_date=?, appointment_time=?, appointment_type=?, status=?, notes=? WHERE id=?");
                    $stmt->bind_param("isssssi", $patientId, $appointmentDate, $appointmentTime, $appointmentType, $status, $notes, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Appointment updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating appointment: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'archive':
                $id = $_POST['appointment_id'] ?? '';
                $reason = $_POST['archive_reason'] ?? '';
                $notes = $_POST['archive_notes'] ?? '';
                
                if ($id && $reason) {
                    // Combine reason and notes if notes are provided
                    $fullReason = $reason;
                    if (!empty($notes)) {
                        $fullReason .= " - " . $notes;
                    }
                    
                    $result = archiveAppointment($conn, $id, $fullReason, $_SESSION['user_id']);
                    if ($result['success']) {
                        $message = $result['message'];
                        $messageType = "success";
                    } else {
                        $message = $result['message'];
                        $messageType = "error";
                    }
                } else {
                    $message = "Please provide a reason for archiving.";
                    $messageType = "error";
                }
                break;
        }
    }
}

// Get search term and filter
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

$whereConditions = [];
$params = [];
$paramTypes = '';

if ($search) {
    $whereConditions[] = "(p.first_name LIKE ? OR p.last_name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $paramTypes .= 'ss';
}

if ($statusFilter) {
    $whereConditions[] = "a.status = ?";
    $params[] = $statusFilter;
    $paramTypes .= 's';
}

if ($dateFilter) {
    $whereConditions[] = "a.appointment_date = ?";
    $params[] = $dateFilter;
    $paramTypes .= 's';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Fetch appointments with patient names
$appointmentsQuery = "SELECT a.*, p.first_name, p.last_name 
                     FROM appointments a 
                     JOIN patients p ON a.patient_id = p.id 
                     $whereClause 
                     ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($appointmentsQuery);
if ($params) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$appointmentsResult = $stmt->get_result();

// Get appointment for editing
$editAppointment = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editStmt = $conn->prepare("SELECT * FROM appointments WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editAppointment = $editStmt->get_result()->fetch_assoc();
    $editStmt->close();
}

// Get all patients for dropdown
$patientsQuery = "SELECT id, first_name, last_name FROM patients ORDER BY first_name, last_name";
$patientsResult = $conn->query($patientsQuery);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Appointments - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/appointments.css">
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

    <div class="appointments-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>Appointment Management</h1>
                <button class="btn-primary" onclick="openModal('addModal')">+ Schedule Appointment</button>
            </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" style="display: contents;">
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Patient name..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Status</option>
                        <option value="scheduled" <?php echo $statusFilter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="no_show" <?php echo $statusFilter === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
            <?php if ($appointmentsResult && $appointmentsResult->num_rows > 0): ?>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($appointment = $appointmentsResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></strong>
                                </td>
                                <td>
                                    <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?><br>
                                    <small><?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $appointment['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-info" onclick="viewPatientProfile(<?php echo $appointment['patient_id']; ?>)">View Profile</button>
                                        <a href="?edit=<?php echo $appointment['id']; ?>" class="btn-secondary">Update</a>
                                        <button class="btn-danger" onclick="openArchiveModal(<?php echo $appointment['id']; ?>, '<?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>')">Archive</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-appointments">
                    <h3>No appointments found</h3>
                    <p><?php echo $search || $statusFilter || $dateFilter ? 'Try adjusting your filters.' : 'Schedule your first appointment to get started.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <!-- Add/Edit Appointment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $editAppointment ? 'Edit Appointment' : 'Schedule New Appointment'; ?></h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editAppointment ? 'edit' : 'add'; ?>">
                <?php if ($editAppointment): ?>
                    <input type="hidden" name="appointment_id" value="<?php echo $editAppointment['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="patient_id">Patient *</label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php while ($patient = $patientsResult->fetch_assoc()): ?>
                            <option value="<?php echo $patient['id']; ?>" 
                                <?php echo ($editAppointment && $editAppointment['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="appointment_type">Appointment Type *</label>
                    <select id="appointment_type" name="appointment_type" required>
                        <option value="">Select Type</option>
                        <option value="Consultation" <?php echo ($editAppointment && $editAppointment['appointment_type'] === 'Consultation') ? 'selected' : ''; ?>>Consultation</option>
                        <option value="Follow-up" <?php echo ($editAppointment && $editAppointment['appointment_type'] === 'Follow-up') ? 'selected' : ''; ?>>Follow-up</option>
                        <option value="Check-up" <?php echo ($editAppointment && $editAppointment['appointment_type'] === 'Check-up') ? 'selected' : ''; ?>>Check-up</option>
                        <option value="Emergency" <?php echo ($editAppointment && $editAppointment['appointment_type'] === 'Emergency') ? 'selected' : ''; ?>>Emergency</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Date *</label>
                        <input type="date" id="appointment_date" name="appointment_date" required value="<?php echo $editAppointment['appointment_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="appointment_time">Time *</label>
                        <input type="time" id="appointment_time" name="appointment_time" required value="<?php echo $editAppointment['appointment_time'] ?? ''; ?>">
                    </div>
                </div>
                
                <?php if ($editAppointment): ?>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="scheduled" <?php echo $editAppointment['status'] === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                        <option value="completed" <?php echo $editAppointment['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $editAppointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="no_show" <?php echo $editAppointment['status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($editAppointment['notes'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-primary"><?php echo $editAppointment ? 'Update Appointment' : 'Schedule Appointment'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Patient Profile Modal -->
    <div id="patientProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Patient Profile</h2>
                <span class="close" onclick="closeModal('patientProfileModal')">&times;</span>
            </div>
            <div id="patientProfileContent">
                <!-- Patient details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Archive Appointment Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Archive Appointment</h2>
                <span class="close" onclick="closeModal('archiveModal')">&times;</span>
            </div>
            <form method="POST" id="archiveForm">
                <input type="hidden" name="action" value="archive">
                <input type="hidden" name="appointment_id" id="archive_appointment_id">
                
                <div class="form-group">
                    <label>Patient</label>
                    <input type="text" id="archive_patient_name" readonly style="background-color: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label for="archive_reason">Reason for Archiving *</label>
                    <select id="archive_reason" name="archive_reason" required>
                        <option value="">Select a reason</option>
                        <option value="Appointment completed">Appointment completed</option>
                        <option value="Patient cancelled">Patient cancelled</option>
                        <option value="No show">No show</option>
                        <option value="Rescheduled">Rescheduled</option>
                        <option value="System cleanup">System cleanup</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="archive_notes">Additional Notes (Optional)</label>
                    <textarea id="archive_notes" name="archive_notes" rows="3" placeholder="Add any additional details about why this appointment is being archived..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('archiveModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Archive Appointment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Override closeModal for appointments-specific functionality
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Clear form if it's add modal
            if (modalId === 'addModal' && !<?php echo $editAppointment ? 'true' : 'false'; ?>) {
                document.querySelector('#addModal form').reset();
            }
        }
        
        // Auto-open edit modal if editing
        <?php if ($editAppointment): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('addModal');
        });
        <?php endif; ?>

        // Patient profile functionality
        function viewPatientProfile(patientId) {
            // Show loading state
            document.getElementById('patientProfileContent').innerHTML = '<div style="text-align: center; padding: 40px;">Loading patient profile...</div>';
            openModal('patientProfileModal');
            
            // Fetch patient data via AJAX
            fetch(`get_patient_profile.php?patient_id=${patientId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('patientProfileContent').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('patientProfileContent').innerHTML = '<div style="text-align: center; padding: 40px; color: red;">Error loading patient profile. Please try again.</div>';
                });
        }

        // Archive appointment functionality
        function openArchiveModal(appointmentId, patientName) {
            document.getElementById('archive_appointment_id').value = appointmentId;
            document.getElementById('archive_patient_name').value = patientName;
            document.getElementById('archiveForm').reset();
            document.getElementById('archive_appointment_id').value = appointmentId;
            document.getElementById('archive_patient_name').value = patientName;
            openModal('archiveModal');
        }
    </script>
    
    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>