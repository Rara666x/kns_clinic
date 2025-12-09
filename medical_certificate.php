<?php
include('includes/auth.php');
include('db_connection.php');

// Create medical_certificates table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS medical_certificates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        certificate_number VARCHAR(50) NOT NULL UNIQUE,
        issue_date DATE NOT NULL,
        diagnosis TEXT NOT NULL,
        days_rest INT NOT NULL DEFAULT 0,
        medicine_prescribed TEXT,
        equipment_used TEXT,
        treatment_provided TEXT,
        additional_notes TEXT,
        status ENUM('draft', 'saved', 'printed') DEFAULT 'draft',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        printed_at TIMESTAMP NULL,
        FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_certificate') {
        $patientId = $_POST['patient_id'] ?? '';
        $issueDate = $_POST['issue_date'] ?? '';
        $diagnosis = $_POST['diagnosis'] ?? '';
        $daysRest = $_POST['days_rest'] ?? 0;
        $medicine = $_POST['medicine'] ?? '';
        $equipment = $_POST['equipment'] ?? '';
        $treatment = $_POST['treatment'] ?? '';
        $notes = $_POST['notes'] ?? '';

        if ($patientId && $issueDate && $diagnosis) {
            // Generate certificate number
            $certificateNumber = 'MC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Check if certificate number already exists
            $checkStmt = $conn->prepare("SELECT id FROM medical_certificates WHERE certificate_number = ?");
            $checkStmt->bind_param("s", $certificateNumber);
            $checkStmt->execute();
            $result = $checkStmt->get_result();

            while ($result->num_rows > 0) {
                $certificateNumber = 'MC-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                $checkStmt->bind_param("s", $certificateNumber);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
            }
            $checkStmt->close();

            // Insert certificate
            $stmt = $conn->prepare("
                INSERT INTO medical_certificates
                (patient_id, certificate_number, issue_date, diagnosis, days_rest, medicine_prescribed, equipment_used, treatment_provided, additional_notes, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'saved')
            ");
            $stmt->bind_param("isssissssi", $patientId, $certificateNumber, $issueDate, $diagnosis, $daysRest, $medicine, $equipment, $treatment, $notes, $_SESSION['user_id']);

            if ($stmt->execute()) {
                $message = "Medical certificate saved successfully! Certificate Number: " . $certificateNumber;
                $messageType = "success";

                // Log activity
                $logDescription = "Created medical certificate: " . $certificateNumber;
                $logIpAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
                $logUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

                $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) VALUES (?, ?, 'create_medical_certificate', ?, ?, ?)");
                $logStmt->bind_param("issss", $_SESSION['user_id'], $_SESSION['username'], $logDescription, $logIpAddress, $logUserAgent);
                $logStmt->execute();
                $logStmt->close();
            } else {
                $message = "Error saving medical certificate: " . $conn->error;
                $messageType = "error";
            }
            $stmt->close();
        } else {
            $message = "Please fill in all required fields (Patient, Issue Date, Diagnosis).";
            $messageType = "error";
        }
    } elseif ($action === 'print_certificate') {
        $certificateId = $_POST['certificate_id'] ?? '';

        if ($certificateId) {
            // Update status to printed and set printed_at timestamp
            $stmt = $conn->prepare("UPDATE medical_certificates SET status = 'printed', printed_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $certificateId);
            $stmt->execute();
            $stmt->close();

            // Log activity
            $logDescription = "Printed medical certificate ID: " . $certificateId;
            $logIpAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $logUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

            $logStmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) VALUES (?, ?, 'print_medical_certificate', ?, ?, ?)");
            $logStmt->bind_param("issss", $_SESSION['user_id'], $_SESSION['username'], $logDescription, $logIpAddress, $logUserAgent);
            $logStmt->execute();
            $logStmt->close();
        }
    }
}

// Fetch patients for the select dropdown
$patients = $conn->query("SELECT id, first_name, last_name, date_of_birth, address FROM patients ORDER BY first_name, last_name");

// Fetch recent certificates for the user
$recentCertificates = $conn->prepare("
    SELECT mc.*, p.first_name, p.last_name
    FROM medical_certificates mc
    JOIN patients p ON mc.patient_id = p.id
    WHERE mc.created_by = ?
    ORDER BY mc.created_at DESC
    LIMIT 5
");
$recentCertificates->bind_param("i", $_SESSION['user_id']);
$recentCertificates->execute();
$recentCertificatesResult = $recentCertificates->get_result();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title>Medical Certificate - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
	<link rel="stylesheet" href="css/clinical-dashboard.css">
	<link rel="stylesheet" href="css/medical_certificate.css">
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

	<div class="medical-certificate-container">
		<div class="content-scrollable">
			<?php if ($message): ?>
			<div class="message <?php echo $messageType; ?>" style="margin-bottom: 16px; padding: 12px 16px; border-radius: 8px; background: <?php echo $messageType === 'success' ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo $messageType === 'success' ? '#065f46' : '#991b1b'; ?>; border: 1px solid <?php echo $messageType === 'success' ? '#a7f3d0' : '#fecaca'; ?>;">
				<?php echo htmlspecialchars($message); ?>
			</div>
			<?php endif; ?>

			<div class="controls-card">
			<h2 style="margin:0 0 12px 0;">📝 Medical Certificate</h2>
			<div class="form-row">
				<div class="form-group">
					<label>Patient</label>
					<select id="patientSelect">
						<option value="">Select patient...</option>
						<?php while ($p = $patients->fetch_assoc()): ?>
							<option value="<?php echo $p['id']; ?>" data-first="<?php echo htmlspecialchars($p['first_name']); ?>" data-last="<?php echo htmlspecialchars($p['last_name']); ?>" data-dob="<?php echo htmlspecialchars($p['date_of_birth']); ?>" data-address="<?php echo htmlspecialchars($p['address']); ?>">
								<?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>
							</option>
						<?php endwhile; ?>
					</select>
				</div>
				<div class="form-group">
					<label>Issue Date</label>
					<input type="date" id="issueDate" value="<?php echo date('Y-m-d'); ?>">
				</div>
			</div>
			<div class="form-row">
				<div class="form-group">
					<label>Diagnosis / Condition</label>
					<input type="text" id="diagnosis" placeholder="e.g., Acute Upper Respiratory Tract Infection">
				</div>
				<div class="form-group">
					<label>Days of Rest</label>
					<input type="number" id="daysRest" min="0" value="3">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label>Medicine Prescribed</label>
					<textarea id="medicine" rows="2" placeholder="e.g., Paracetamol 500mg, 3 times daily for 5 days"></textarea>
				</div>
				<div class="form-group">
					<label>Equipment Used</label>
					<textarea id="equipment" rows="2" placeholder="e.g., Stethoscope, Blood pressure monitor, Thermometer"></textarea>
				</div>
			</div>

			<div class="form-group">
				<label>Treatment Provided (Optional)</label>
				<textarea id="treatment" rows="3" placeholder="Describe the treatment provided to the patient..."></textarea>
			</div>

			<div class="form-group">
				<label>Additional Notes</label>
				<textarea id="notes" rows="3" placeholder="Optional notes to include in the certificate..."></textarea>
			</div>
			<form method="POST" id="certificateForm">
				<input type="hidden" name="action" value="save_certificate">
				<input type="hidden" name="patient_id" id="hiddenPatientId">
				<input type="hidden" name="issue_date" id="hiddenIssueDate">
				<input type="hidden" name="diagnosis" id="hiddenDiagnosis">
				<input type="hidden" name="days_rest" id="hiddenDaysRest">
				<input type="hidden" name="medicine" id="hiddenMedicine">
				<input type="hidden" name="equipment" id="hiddenEquipment">
				<input type="hidden" name="treatment" id="hiddenTreatment">
				<input type="hidden" name="notes" id="hiddenNotes">

				<div class="btn-row">
					<button type="button" class="btn-secondary" onclick="updatePreview()">Update Preview</button>
					<button type="submit" class="btn-primary" id="saveBtn">Save Certificate</button>
					<button type="button" class="btn-secondary" id="printBtn" onclick="printCertificate()" disabled>Print Certificate</button>
				</div>
			</form>
		</div>

		<!-- Recent Certificates Section -->
		<?php if ($recentCertificatesResult->num_rows > 0): ?>
		<div class="controls-card" style="margin-bottom: 16px;">
			<h3 style="margin:0 0 12px 0;">📋 Recent Certificates</h3>
			<div class="certificates-list">
				<?php while ($cert = $recentCertificatesResult->fetch_assoc()): ?>
				<div class="certificate-item" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px; background: #f9fafb;">
					<div>
						<strong><?php echo htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']); ?></strong>
						<span style="color: #6b7280;">- <?php echo htmlspecialchars($cert['certificate_number']); ?></span>
						<br>
						<small style="color: #6b7280;">
							<?php echo date('M d, Y', strtotime($cert['issue_date'])); ?> -
							Status: <span style="color: <?php echo $cert['status'] === 'printed' ? '#059669' : ($cert['status'] === 'saved' ? '#d97706' : '#6b7280'); ?>;">
								<?php echo ucfirst($cert['status']); ?>
							</span>
						</small>
					</div>
					<div>
						<?php if ($cert['status'] === 'saved'): ?>
						<button class="btn-secondary" onclick="loadCertificate(<?php echo $cert['id']; ?>)">Load</button>
						<button class="btn-primary" onclick="printSavedCertificate(<?php echo $cert['id']; ?>)">Print</button>
						<?php elseif ($cert['status'] === 'printed'): ?>
						<button class="btn-secondary" onclick="loadCertificate(<?php echo $cert['id']; ?>)">View</button>
						<button class="btn-primary" onclick="printSavedCertificate(<?php echo $cert['id']; ?>)">Reprint</button>
						<?php endif; ?>
					</div>
				</div>
				<?php endwhile; ?>
			</div>
		</div>
		<?php endif; ?>

		<div class="certificate-card">
			<div class="certificate-sheet" id="certificate">
				<div class="certificate-header">
					<div class="clinic-name">Clinical System Health Center</div>
					<div class="clinic-meta">123 Health Street, MedCity • (+1) 555-0199 • clinic@example.com</div>
				</div>
				<div class="certificate-title">Medical Certificate</div>
				<div class="certificate-body" id="certificateBody">
					<p>Date: <span id="pvIssueDate" class="field">__________</span></p>
					<p>
						To whom it may concern,
					</p>
					<p>
						This is to certify that <strong><span id="pvPatientName">[Patient Name]</span></strong>
						<?php /* age calc client-side */ ?>
						<span id="pvPatientAge">[Age]</span> years of age, residing at
						<span id="pvAddress" class="field">[Address]</span>,
						was examined and treated at our facility for
						<strong><span id="pvDiagnosis">[Diagnosis]</span></strong>.
					</p>
					<p id="pvMedicineRow" style="display:none;">
						<strong>Medicine Prescribed:</strong> <span id="pvMedicine" class="field">—</span>
					</p>
					<p id="pvEquipmentRow" style="display:none;">
						<strong>Equipment Used:</strong> <span id="pvEquipment" class="field">—</span>
					</p>
					<p id="pvTreatmentRow" style="display:none;">
						<strong>Treatment Provided:</strong> <span id="pvTreatment" class="field">—</span>
					</p>
					<p>
						The patient is hereby advised to rest for <strong><span id="pvDaysRest">[0]</span> day(s)</strong>
						starting from the date indicated above, and may resume usual activities thereafter as tolerated.
					</p>
					<p id="pvNotesRow" style="display:none;">
						Additional notes: <span id="pvNotes" class="field">—</span>
					</p>
					<p>
						This certificate is issued upon the patient's request for whatever legal purpose it may serve.
					</p>
					<div class="certificate-footer">
						<div></div>
						<div class="signature-block">
							<div class="signature-line"></div>
						<div class="signature-name">Authorized Clinic Representative</div>
						</div>
					</div>
				</div>
			</div>
			<div class="print-actions">
				<button class="btn-secondary" onclick="window.print()">Print</button>
			</div>
		</div>
		</div>
	</div>

	<script>
		let currentCertificateId = null;
		let isSaved = false;

		function calculateAge(dob) {
			if (!dob) return '';
			const birth = new Date(dob);
			if (isNaN(birth.getTime())) return '';
			const today = new Date();
			let age = today.getFullYear() - birth.getFullYear();
			const m = today.getMonth() - birth.getMonth();
			if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
			return age.toString();
		}

		function updatePreview() {
			const patientSel = document.getElementById('patientSelect');
			const selected = patientSel.options[patientSel.selectedIndex];
			const first = selected ? selected.getAttribute('data-first') : '';
			const last = selected ? selected.getAttribute('data-last') : '';
			const dob = selected ? selected.getAttribute('data-dob') : '';
			const address = selected ? selected.getAttribute('data-address') : '';
			const issueDate = document.getElementById('issueDate').value;
			const diagnosis = document.getElementById('diagnosis').value;
			const daysRest = document.getElementById('daysRest').value || '0';
			const medicine = document.getElementById('medicine').value;
			const equipment = document.getElementById('equipment').value;
			const treatment = document.getElementById('treatment').value;
			const notes = document.getElementById('notes').value;

			// Update hidden form fields
			document.getElementById('hiddenPatientId').value = patientSel.value || '';
			document.getElementById('hiddenIssueDate').value = issueDate;
			document.getElementById('hiddenDiagnosis').value = diagnosis;
			document.getElementById('hiddenDaysRest').value = daysRest;
			document.getElementById('hiddenMedicine').value = medicine;
			document.getElementById('hiddenEquipment').value = equipment;
			document.getElementById('hiddenTreatment').value = treatment;
			document.getElementById('hiddenNotes').value = notes;

			// Update preview
			document.getElementById('pvPatientName').textContent = (first || '') + (last ? (' ' + last) : '');
			document.getElementById('pvPatientAge').textContent = calculateAge(dob) || '';
			document.getElementById('pvAddress').textContent = address || '';
			document.getElementById('pvIssueDate').textContent = issueDate ? new Date(issueDate).toLocaleDateString() : '';
			document.getElementById('pvDiagnosis').textContent = diagnosis || '';
			document.getElementById('pvDaysRest').textContent = daysRest;
			document.getElementById('pvMedicine').textContent = medicine || '';
			document.getElementById('pvEquipment').textContent = equipment || '';
			document.getElementById('pvTreatment').textContent = treatment || '';
			document.getElementById('pvNotes').textContent = notes || '';

			// Show/hide sections based on content
			document.getElementById('pvMedicineRow').style.display = medicine ? 'block' : 'none';
			document.getElementById('pvEquipmentRow').style.display = equipment ? 'block' : 'none';
			document.getElementById('pvTreatmentRow').style.display = treatment ? 'block' : 'none';
			document.getElementById('pvNotesRow').style.display = notes ? 'block' : 'none';

			// Check if form is valid for saving
			checkFormValidity();
		}

		function checkFormValidity() {
			const patientId = document.getElementById('patientSelect').value;
			const issueDate = document.getElementById('issueDate').value;
			const diagnosis = document.getElementById('diagnosis').value;

			const isValid = patientId && issueDate && diagnosis;
			document.getElementById('saveBtn').disabled = !isValid;
		}

		function printCertificate() {
			if (!isSaved) {
				alert('Please save the certificate before printing.');
				return;
			}
			window.print();
		}

		function printSavedCertificate(certificateId) {
			// Create a form to submit the print action
			const form = document.createElement('form');
			form.method = 'POST';
			form.style.display = 'none';

			const actionInput = document.createElement('input');
			actionInput.type = 'hidden';
			actionInput.name = 'action';
			actionInput.value = 'print_certificate';

			const idInput = document.createElement('input');
			idInput.type = 'hidden';
			idInput.name = 'certificate_id';
			idInput.value = certificateId;

			form.appendChild(actionInput);
			form.appendChild(idInput);
			document.body.appendChild(form);
			form.submit();
		}

		function loadCertificate(certificateId) {
			// This would load a saved certificate into the form
			// For now, we'll just show an alert
			alert('Loading certificate functionality will be implemented. Certificate ID: ' + certificateId);
		}

		// Handle form submission
		document.getElementById('certificateForm').addEventListener('submit', function(e) {
			e.preventDefault();

			// Update hidden fields before submission
			updatePreview();

			// Submit the form
			this.submit();
		});

		// Enable print button when certificate is saved
		<?php if ($message && $messageType === 'success'): ?>
		document.getElementById('printBtn').disabled = false;
		isSaved = true;
		<?php endif; ?>

		// Auto-update preview on field changes for convenience
		['patientSelect','issueDate','diagnosis','daysRest','medicine','equipment','treatment','notes'].forEach(id => {
			const el = document.getElementById(id);
			if (el) el.addEventListener('change', updatePreview);
			if (el) el.addEventListener('keyup', updatePreview);
		});

		document.addEventListener('DOMContentLoaded', updatePreview);

		// Medical certificate-specific functionality - common functions are in common.js
    </script>

    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>
