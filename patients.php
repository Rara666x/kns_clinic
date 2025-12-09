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
                $studentId = $_POST['student_id'] ?? '';
                $firstName = $_POST['first_name'] ?? '';
                $lastName = $_POST['last_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $dateOfBirth = $_POST['date_of_birth'] ?? '';
                $address = $_POST['address'] ?? '';
                $medicalHistory = $_POST['medical_history'] ?? '';
                $yearLevel = $_POST['year_level'] ?? '';
                $course = $_POST['course'] ?? '';
                
                // Handle photo upload
                $photoPath = null;
                if (isset($_FILES['patient_photo']) && $_FILES['patient_photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/patient_photos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileExtension = strtolower(pathinfo($_FILES['patient_photo']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $fileName = $studentId . '_' . time() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['patient_photo']['tmp_name'], $uploadPath)) {
                            $photoPath = $uploadPath;
                        }
                    }
                }
                
                if ($studentId && $firstName && $lastName && $email) {
                    $stmt = $conn->prepare("INSERT INTO patients (student_id, first_name, last_name, email, phone, date_of_birth, address, medical_history, year_level, course, photo_path, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("sssssssssss", $studentId, $firstName, $lastName, $email, $phone, $dateOfBirth, $address, $medicalHistory, $yearLevel, $course, $photoPath);
                    
                    if ($stmt->execute()) {
                        $message = "Patient added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding patient: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'edit':
                $id = $_POST['patient_id'] ?? '';
                $studentId = $_POST['student_id'] ?? '';
                $firstName = $_POST['first_name'] ?? '';
                $lastName = $_POST['last_name'] ?? '';
                $email = $_POST['email'] ?? '';
                $phone = $_POST['phone'] ?? '';
                $dateOfBirth = $_POST['date_of_birth'] ?? '';
                $address = $_POST['address'] ?? '';
                $medicalHistory = $_POST['medical_history'] ?? '';
                $yearLevel = $_POST['year_level'] ?? '';
                $course = $_POST['course'] ?? '';
                
                // Handle photo upload
                $photoPath = null;
                if (isset($_FILES['patient_photo']) && $_FILES['patient_photo']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'uploads/patient_photos/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    
                    $fileExtension = strtolower(pathinfo($_FILES['patient_photo']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $fileName = $studentId . '_' . time() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['patient_photo']['tmp_name'], $uploadPath)) {
                            $photoPath = $uploadPath;
                        }
                    }
                } else {
                    // Keep existing photo if no new photo uploaded
                    $existingStmt = $conn->prepare("SELECT photo_path FROM patients WHERE id = ?");
                    $existingStmt->bind_param("i", $id);
                    $existingStmt->execute();
                    $existingResult = $existingStmt->get_result()->fetch_assoc();
                    $photoPath = $existingResult['photo_path'] ?? null;
                    $existingStmt->close();
                }
                
                if ($id && $studentId && $firstName && $lastName && $email) {
                    $stmt = $conn->prepare("UPDATE patients SET student_id=?, first_name=?, last_name=?, email=?, phone=?, date_of_birth=?, address=?, medical_history=?, year_level=?, course=?, photo_path=? WHERE id=?");
                    $stmt->bind_param("sssssssssssi", $studentId, $firstName, $lastName, $email, $phone, $dateOfBirth, $address, $medicalHistory, $yearLevel, $course, $photoPath, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Patient updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating patient: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'archive':
                $id = $_POST['patient_id'] ?? '';
                $reason = $_POST['archive_reason'] ?? '';
                $notes = $_POST['archive_notes'] ?? '';
                
                if ($id && $reason) {
                    // Combine reason and notes if notes are provided
                    $fullReason = $reason;
                    if (!empty($notes)) {
                        $fullReason .= " - " . $notes;
                    }
                    
                    $result = archivePatient($conn, $id, $fullReason, $_SESSION['user_id']);
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

// Get search term
$search = $_GET['search'] ?? '';
$searchCondition = $search ? "WHERE student_id LIKE '%$search%' OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%'" : '';

// Fetch patients
$patientsQuery = "SELECT * FROM patients $searchCondition ORDER BY created_at DESC";
$patientsResult = $conn->query($patientsQuery);

// Get patient for editing
$editPatient = null;
if (isset($_GET['edit'])) {
    $editId = $_GET['edit'];
    $editStmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editPatient = $editStmt->get_result()->fetch_assoc();
    $editStmt->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Patients - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/patients.css">
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

    <div class="patients-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>Patient Management</h1>
                <button class="btn-primary" onclick="openModal('addModal')">+ Add New Patient</button>
            </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="search-bar">
            <form method="GET" style="display: flex; gap: 12px; width: 100%;">
                <input type="text" name="search" class="search-input" placeholder="Search by Student ID or name..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="patients.php" class="btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); width: 100%;">
            <?php if ($patientsResult && $patientsResult->num_rows > 0): ?>
                <table class="patients-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Age</th>
                            <th>Year Level</th>
                            <th>Course</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($patient = $patientsResult->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="student-id-badge"><?php echo htmlspecialchars($patient['student_id'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['first_name'] ?? 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['last_name'] ?? 'N/A'); ?></strong>
                                </td>
                                <td>
                                    <span class="age-badge">
                                        <?php 
                                        if ($patient['date_of_birth']) {
                                            $birthDate = new DateTime($patient['date_of_birth']);
                                            $today = new DateTime();
                                            $age = $today->diff($birthDate)->y;
                                            echo $age . ' years';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="year-badge"><?php echo htmlspecialchars($patient['year_level'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="course-badge"><?php echo htmlspecialchars($patient['course'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="registration-date">
                                        <?php echo date('M j, Y', strtotime($patient['created_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <button onclick="viewPatientDetails(<?php echo htmlspecialchars(json_encode($patient)); ?>)" class="btn-primary">View More</button>
                                        <a href="?edit=<?php echo $patient['id']; ?>" class="btn-secondary">Update</a>
                                        <button class="btn-danger" onclick="openArchiveModal(<?php echo $patient['id']; ?>, '<?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>', '<?php echo htmlspecialchars($patient['student_id']); ?>')">Archive</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-patients">
                    <h3>No patients found</h3>
                    <p><?php echo $search ? 'Try adjusting your search terms.' : 'Add your first patient to get started.'; ?></p>
                </div>
            <?php endif; ?>
            </div>
        </div>
        </div>
    </div>

    <!-- Add/Edit Patient Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $editPatient ? 'Edit Patient' : 'Add New Patient'; ?></h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editPatient ? 'edit' : 'add'; ?>">
                <?php if ($editPatient): ?>
                    <input type="hidden" name="patient_id" value="<?php echo $editPatient['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="student_id">Student ID *</label>
                    <input type="text" id="student_id" name="student_id" required value="<?php echo htmlspecialchars($editPatient['student_id'] ?? ''); ?>" placeholder="e.g., 2024-001">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($editPatient['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($editPatient['last_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($editPatient['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($editPatient['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $editPatient['date_of_birth'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="year_level">Year Level</label>
                        <select id="year_level" name="year_level">
                            <option value="">Select Year Level</option>
                            <option value="1st Year" <?php echo ($editPatient['year_level'] ?? '') === '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2nd Year" <?php echo ($editPatient['year_level'] ?? '') === '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3rd Year" <?php echo ($editPatient['year_level'] ?? '') === '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4th Year" <?php echo ($editPatient['year_level'] ?? '') === '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="course">Course</label>
                    <select id="course" name="course">
                        <option value="">Select Course</option>
                        <option value="BSCS" <?php echo ($editPatient['course'] ?? '') === 'BSCS' ? 'selected' : ''; ?>>BSCS - Bachelor of Science in Computer Science</option>
                        <option value="BSED" <?php echo ($editPatient['course'] ?? '') === 'BSED' ? 'selected' : ''; ?>>BSED - Bachelor of Science in Education</option>
                        <option value="BEED" <?php echo ($editPatient['course'] ?? '') === 'BEED' ? 'selected' : ''; ?>>BEED - Bachelor of Elementary Education</option>
                        <option value="BSBA" <?php echo ($editPatient['course'] ?? '') === 'BSBA' ? 'selected' : ''; ?>>BSBA - Bachelor of Science in Business Administration</option>
                        <option value="BSHM" <?php echo ($editPatient['course'] ?? '') === 'BSHM' ? 'selected' : ''; ?>>BSHM - Bachelor of Science in Hospitality Management</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="patient_photo">Patient Photo</label>
                    <div class="photo-upload-container">
                        <div class="photo-preview" id="photoPreview">
                            <?php if (isset($editPatient['photo_path']) && $editPatient['photo_path']): ?>
                                <img src="<?php echo htmlspecialchars($editPatient['photo_path']); ?>" alt="Current Photo" class="current-photo">
                            <?php else: ?>
                                <div class="no-photo">
                                    <span class="no-photo-icon">📷</span>
                                    <span class="no-photo-text">No photo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="photo-upload-options">
                            <input type="file" id="patient_photo" name="patient_photo" accept="image/*" capture="user" onchange="previewPhoto(this)">
                            <div class="upload-buttons">
                                <button type="button" class="btn-secondary" onclick="openFileUpload()">
                                    📁 Choose File
                                </button>
                                <button type="button" class="btn-secondary" onclick="openCamera()">
                                    📷 Take Photo
                                </button>
                            </div>
                            <small class="upload-help">Supported formats: JPG, PNG, GIF. Max size: 5MB</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($editPatient['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="medical_history">Medical History</label>
                    <textarea id="medical_history" name="medical_history" rows="4"><?php echo htmlspecialchars($editPatient['medical_history'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn-primary"><?php echo $editPatient ? 'Update Patient' : 'Add Patient'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Patient Details Modal -->
    <div id="patientDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Patient Details</h2>
                <span class="close" onclick="closeModal('patientDetailsModal')">&times;</span>
            </div>
            <div id="patientDetailsContent">
                <!-- Patient details will be populated here -->
            </div>
        </div>
    </div>

    <!-- Archive Patient Modal -->
    <div id="archiveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Archive Patient</h2>
                <span class="close" onclick="closeModal('archiveModal')">&times;</span>
            </div>
            <form method="POST" id="archiveForm">
                <input type="hidden" name="action" value="archive">
                <input type="hidden" name="patient_id" id="archive_patient_id">
                
                <div class="form-group">
                    <label>Patient Name</label>
                    <input type="text" id="archive_patient_name" readonly style="background-color: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label>Student ID</label>
                    <input type="text" id="archive_student_id" readonly style="background-color: #f8f9fa;">
                </div>
                
                <div class="form-group">
                    <label for="archive_reason">Reason for Archiving *</label>
                    <select id="archive_reason" name="archive_reason" required>
                        <option value="">Select a reason</option>
                        <option value="Patient graduated">Patient graduated</option>
                        <option value="Patient transferred">Patient transferred</option>
                        <option value="Patient withdrew">Patient withdrew</option>
                        <option value="Duplicate record">Duplicate record</option>
                        <option value="Data cleanup">Data cleanup</option>
                        <option value="System maintenance">System maintenance</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="archive_notes">Additional Notes (Optional)</label>
                    <textarea id="archive_notes" name="archive_notes" rows="3" placeholder="Add any additional details about why this patient is being archived..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal('archiveModal')">Cancel</button>
                    <button type="submit" class="btn-danger">Archive Patient</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Override openModal for patients-specific functionality
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
            
            // Initialize photo upload functionality when opening add/edit modal
            if (modalId === 'addModal') {
                setTimeout(initializePhotoUpload, 100);
            }
        }
        
        // Override closeModal for patients-specific functionality
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Clear form if it's add modal
            if (modalId === 'addModal' && !<?php echo $editPatient ? 'true' : 'false'; ?>) {
                document.querySelector('#addModal form').reset();
            }
        }
        
        // Archive patient functionality
        function openArchiveModal(patientId, patientName, studentId) {
            document.getElementById('archive_patient_id').value = patientId;
            document.getElementById('archive_patient_name').value = patientName;
            document.getElementById('archive_student_id').value = studentId;
            document.getElementById('archiveForm').reset();
            document.getElementById('archive_patient_id').value = patientId;
            document.getElementById('archive_patient_name').value = patientName;
            document.getElementById('archive_student_id').value = studentId;
            openModal('archiveModal');
        }
        
        function viewPatientDetails(patient) {
            const content = document.getElementById('patientDetailsContent');
            
            // Format date of birth
            const dateOfBirth = patient.date_of_birth ? 
                new Date(patient.date_of_birth).toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                }) : 'Not provided';
            
            // Calculate age if date of birth is available
            const age = patient.date_of_birth ? 
                Math.floor((new Date() - new Date(patient.date_of_birth)) / (365.25 * 24 * 60 * 60 * 1000)) : 'N/A';
            
            // Format created date
            const createdDate = new Date(patient.created_at).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            content.innerHTML = `
                <div class="patient-header">
                    <div class="patient-photo-section">
                        ${patient.photo_path ? 
                            `<img src="${patient.photo_path}" alt="Patient Photo" class="patient-photo">` : 
                            `<div class="no-patient-photo">📷</div>`
                        }
                    </div>
                    <h3>${patient.first_name} ${patient.last_name}</h3>
                    <div class="patient-id">Student ID: ${patient.student_id || 'N/A'}</div>
                </div>
                
                <div class="patient-details-grid">
                    <div class="patient-details-section">
                        <h4>📋 Personal Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Student ID:</span>
                            <span class="detail-value">${patient.student_id || '<span class="empty">Not provided</span>'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Full Name:</span>
                            <span class="detail-value">${patient.first_name} ${patient.last_name}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value">${patient.email || '<span class="empty">Not provided</span>'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value">${patient.phone || '<span class="empty">Not provided</span>'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Date of Birth:</span>
                            <span class="detail-value">${dateOfBirth}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Age:</span>
                            <span class="detail-value">${age} years old</span>
                        </div>
                    </div>
                    
                    <div class="patient-details-section">
                        <h4>🎓 Academic Information</h4>
                        <div class="detail-item">
                            <span class="detail-label">Year Level:</span>
                            <span class="detail-value">${patient.year_level || '<span class="empty">Not specified</span>'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Course:</span>
                            <span class="detail-value">${patient.course || '<span class="empty">Not specified</span>'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Registration Date:</span>
                            <span class="detail-value">${createdDate}</span>
                        </div>
                    </div>
                </div>
                
                <div class="patient-details-section">
                    <h4>🏠 Address Information</h4>
                    <div class="detail-item">
                        <span class="detail-label">Address:</span>
                        <span class="detail-value">${patient.address || '<span class="empty">Not provided</span>'}</span>
                    </div>
                </div>
                
                <div class="patient-details-section">
                    <h4>🏥 Medical Information</h4>
                    <div class="detail-item">
                        <span class="detail-label">Medical History:</span>
                        <span class="detail-value">${patient.medical_history || '<span class="empty">No medical history recorded</span>'}</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: center; margin-top: 24px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('patientDetailsModal')">Close</button>
                    <a href="?edit=${patient.id}" class="btn-primary" style="text-decoration: none; text-align: center;">Edit Patient</a>
                </div>
            `;
            
            openModal('patientDetailsModal');
        }
        
        function previewPhoto(input) {
            const preview = document.getElementById('photoPreview');
            const file = input.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Photo Preview" class="current-photo">`;
                };
                reader.readAsDataURL(file);
            }
        }
        
        function openCamera() {
            const input = document.getElementById('patient_photo');
            const button = event.target;
            
            // Show loading state
            const originalText = button.innerHTML;
            button.innerHTML = '📷 Opening Camera...';
            button.disabled = true;
            
            // Check if device supports camera
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                // Set capture attribute to force camera usage
                input.setAttribute('capture', 'user');
                input.setAttribute('accept', 'image/*');
                
                // Clear any existing event listeners
                input.removeEventListener('change', handleCameraCapture);
                
                // Add new event listener
                input.addEventListener('change', handleCameraCapture);
                
                // Trigger file input click
                input.click();
                
                // Reset button after a short delay
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 1000);
                
            } else {
                // Fallback for devices without camera support
                button.innerHTML = originalText;
                button.disabled = false;
                alert('Camera not available on this device. Please use "Choose File" to upload a photo.');
                input.removeAttribute('capture');
                input.click();
            }
        }
        
        function handleCameraCapture(e) {
            if (e.target.files && e.target.files[0]) {
                previewPhoto(e.target);
                
                // Show success message
                const button = document.querySelector('button[onclick="openCamera()"]');
                if (button) {
                    const originalText = button.innerHTML;
                    button.innerHTML = '✅ Photo Captured!';
                    setTimeout(() => {
                        button.innerHTML = originalText;
                    }, 2000);
                }
            }
        }
        
        function openFileUpload() {
            const input = document.getElementById('patient_photo');
            input.removeAttribute('capture');
            input.setAttribute('accept', 'image/*');
            input.click();
        }
        
        // Check camera availability on page load
        function checkCameraAvailability() {
            const cameraButton = document.querySelector('button[onclick="openCamera()"]');
            if (cameraButton) {
                if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                    // Camera is available
                    cameraButton.title = 'Take a photo using your device camera';
                } else {
                    // Camera not available
                    cameraButton.style.opacity = '0.6';
                    cameraButton.title = 'Camera not available on this device';
                }
            }
        }
        
        // Initialize camera check when modal opens
        function initializePhotoUpload() {
            checkCameraAvailability();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Auto-open edit modal if editing
        <?php if ($editPatient): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('addModal');
        });
        <?php endif; ?>
        
        // Patients-specific functionality - common functions are in common.js

    </script>
    
    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>
