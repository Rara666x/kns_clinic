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
                $recordType = $_POST['record_type'] ?? '';
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $diagnosis = $_POST['diagnosis'] ?? '';
                $treatment = $_POST['treatment'] ?? '';
                $medications = $_POST['medications'] ?? '';
                $recordDate = $_POST['record_date'] ?? '';
                
                if ($patientId && $recordType && $title && $recordDate) {
                    $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, record_type, title, description, diagnosis, treatment, medications, record_date, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("isssssss", $patientId, $recordType, $title, $description, $diagnosis, $treatment, $medications, $recordDate);
                    
                    if ($stmt->execute()) {
                        $message = "Medical record added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding medical record: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'edit':
                $id = $_POST['record_id'] ?? '';
                $patientId = $_POST['patient_id'] ?? '';
                $recordType = $_POST['record_type'] ?? '';
                $title = $_POST['title'] ?? '';
                $description = $_POST['description'] ?? '';
                $diagnosis = $_POST['diagnosis'] ?? '';
                $treatment = $_POST['treatment'] ?? '';
                $medications = $_POST['medications'] ?? '';
                $recordDate = $_POST['record_date'] ?? '';
                
                if ($id && $patientId && $recordType && $title && $recordDate) {
                    $stmt = $conn->prepare("UPDATE medical_records SET patient_id=?, record_type=?, title=?, description=?, diagnosis=?, treatment=?, medications=?, record_date=? WHERE id=?");
                    $stmt->bind_param("isssssssi", $patientId, $recordType, $title, $description, $diagnosis, $treatment, $medications, $recordDate, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Medical record updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating medical record: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'archive':
                $id = $_POST['record_id'] ?? '';
                $reason = $_POST['archive_reason'] ?? '';
                $notes = $_POST['archive_notes'] ?? '';
                
                if ($id && $reason) {
                    // Combine reason and notes if notes are provided
                    $fullReason = $reason;
                    if (!empty($notes)) {
                        $fullReason .= " - " . $notes;
                    }
                    
                    $result = archiveMedicalRecord($conn, $id, $fullReason, $_SESSION['user_id']);
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
$typeFilter = $_GET['type'] ?? '';
$patientFilter = $_GET['patient'] ?? '';

$whereConditions = [];
$params = [];
$paramTypes = '';

if ($search) {
    $whereConditions[] = "(p.first_name LIKE ? OR p.last_name LIKE ? OR mr.title LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $paramTypes .= 'sss';
}

if ($typeFilter) {
    $whereConditions[] = "mr.record_type = ?";
    $params[] = $typeFilter;
    $paramTypes .= 's';
}

if ($patientFilter) {
    $whereConditions[] = "mr.patient_id = ?";
    $params[] = $patientFilter;
    $paramTypes .= 'i';
}

$whereClause = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Fetch medical records with patient names
$recordsQuery = "SELECT mr.*, p.first_name, p.last_name 
                FROM medical_records mr 
                JOIN patients p ON mr.patient_id = p.id 
                $whereClause 
                ORDER BY mr.record_date DESC, mr.created_at DESC";

$stmt = $conn->prepare($recordsQuery);
if ($params) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$recordsResult = $stmt->get_result();

// Get record for editing
$editRecord = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editStmt = $conn->prepare("SELECT * FROM medical_records WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editRecord = $editStmt->get_result()->fetch_assoc();
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
    <title>Medical Records - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/records.css">
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

    <div class="records-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>Medical Records</h1>
                <button class="btn-primary" onclick="openModal('addModal')">+ Add Medical Record</button>
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
                    <input type="text" name="search" placeholder="Patient or title..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="filter-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="">All Types</option>
                        <option value="Consultation" <?php echo $typeFilter === 'Consultation' ? 'selected' : ''; ?>>Consultation</option>
                        <option value="Diagnosis" <?php echo $typeFilter === 'Diagnosis' ? 'selected' : ''; ?>>Diagnosis</option>
                        <option value="Treatment" <?php echo $typeFilter === 'Treatment' ? 'selected' : ''; ?>>Treatment</option>
                        <option value="Lab" <?php echo $typeFilter === 'Lab' ? 'selected' : ''; ?>>Lab</option>
                        <option value="Imaging" <?php echo $typeFilter === 'Imaging' ? 'selected' : ''; ?>>Imaging</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Patient</label>
                    <select name="patient">
                        <option value="">All Patients</option>
                        <?php 
                        $patientsForFilter = $conn->query("SELECT id, first_name, last_name FROM patients ORDER BY first_name, last_name");
                        while ($patient = $patientsForFilter->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $patient['id']; ?>" <?php echo $patientFilter == $patient['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
            <?php if ($recordsResult && $recordsResult->num_rows > 0): ?>
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $recordsResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="type-badge type-<?php echo $record['record_type']; ?>">
                                        <?php echo htmlspecialchars($record['record_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="record-preview" title="<?php echo htmlspecialchars($record['title']); ?>">
                                        <?php echo htmlspecialchars($record['title']); ?>
                                    </div>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($record['record_date'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button class="btn-info" onclick="viewPatientProfile(<?php echo $record['patient_id']; ?>)">View Profile</button>
                                        <a href="?edit=<?php echo $record['id']; ?>" class="btn-secondary">Update</a>
                                        <button class="btn-danger" onclick="openArchiveModal(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?>', '<?php echo htmlspecialchars($record['title']); ?>')">Archive</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-records">
                    <h3>No medical records found</h3>
                    <p><?php echo $search || $typeFilter || $patientFilter ? 'Try adjusting your filters.' : 'Add your first medical record to get started.'; ?></p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </div>

    <!-- Add/Edit Medical Record Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $editRecord ? 'Edit Medical Record' : 'Add Medical Record'; ?></h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editRecord ? 'edit' : 'add'; ?>">
                <?php if ($editRecord): ?>
                    <input type="hidden" name="record_id" value="<?php echo $editRecord['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="patient_id">Patient *</label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php while ($patient = $patientsResult->fetch_assoc()): ?>
                            <option value="<?php echo $patient['id']; ?>" 
                                <?php echo ($editRecord && $editRecord['patient_id'] == $patient['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="record_type">Record Type *</label>
                        <select id="record_type" name="record_type" required>
                            <option value="">Select Type</option>
                            <option value="Consultation" <?php echo ($editRecord && $editRecord['record_type'] === 'Consultation') ? 'selected' : ''; ?>>Consultation</option>
                            <option value="Diagnosis" <?php echo ($editRecord && $editRecord['record_type'] === 'Diagnosis') ? 'selected' : ''; ?>>Diagnosis</option>
                            <option value="Treatment" <?php echo ($editRecord && $editRecord['record_type'] === 'Treatment') ? 'selected' : ''; ?>>Treatment</option>
                            <option value="Lab" <?php echo ($editRecord && $editRecord['record_type'] === 'Lab') ? 'selected' : ''; ?>>Lab</option>
                            <option value="Imaging" <?php echo ($editRecord && $editRecord['record_type'] === 'Imaging') ? 'selected' : ''; ?>>Imaging</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="record_date">Record Date *</label>
                        <input type="date" id="record_date" name="record_date" required value="<?php echo $editRecord['record_date'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($editRecord['title'] ?? ''); ?>">
                </div>
                
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($editRecord['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="diagnosis">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis" rows="3"><?php echo htmlspecialchars($editRecord['diagnosis'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="treatment">Treatment</label>
                    <textarea id="treatment" name="treatment" rows="3"><?php echo htmlspecialchars($editRecord['treatment'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="medications">Medications</label>
                    <textarea id="medications" name="medications" rows="2"><?php echo htmlspecialchars($editRecord['medications'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-primary"><?php echo $editRecord ? 'Update Record' : 'Add Record'; ?></button>
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

    <!-- Archive Medical Record Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Archive Medical Record</h2>
                <span class="close" onclick="closeModal('archiveModal')">&times;</span>
            </div>
            <form method="POST" id="archiveForm">
                <input type="hidden" name="action" value="archive">
                <input type="hidden" name="record_id" id="archive_record_id">
                
                <div class="form-group">
                    <label>Patient</label>
                    <input type="text" id="archive_patient_name" readonly style="background-color: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label>Record Title</label>
                    <input type="text" id="archive_record_title" readonly style="background-color: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label for="archive_reason">Reason for Archiving *</label>
                    <select id="archive_reason" name="archive_reason" required>
                        <option value="">Select a reason</option>
                        <option value="Record completed">Record completed</option>
                        <option value="Patient discharged">Patient discharged</option>
                        <option value="Duplicate record">Duplicate record</option>
                        <option value="Data cleanup">Data cleanup</option>
                        <option value="System maintenance">System maintenance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="archive_notes">Additional Notes (Optional)</label>
                    <textarea id="archive_notes" name="archive_notes" rows="3" placeholder="Add any additional details about why this medical record is being archived..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('archiveModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Archive Medical Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Override closeModal for records-specific functionality
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Clear form if it's add modal
            if (modalId === 'addModal' && !<?php echo $editRecord ? 'true' : 'false'; ?>) {
                document.querySelector('#addModal form').reset();
            }
        }
        
        // Auto-open edit modal if editing
        <?php if ($editRecord): ?>
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

        // Archive medical record functionality
        function openArchiveModal(recordId, patientName, recordTitle) {
            document.getElementById('archive_record_id').value = recordId;
            document.getElementById('archive_patient_name').value = patientName;
            document.getElementById('archive_record_title').value = recordTitle;
            document.getElementById('archiveForm').reset();
            document.getElementById('archive_record_id').value = recordId;
            document.getElementById('archive_patient_name').value = patientName;
            document.getElementById('archive_record_title').value = recordTitle;
            openModal('archiveModal');
        }
    </script>
    
    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>