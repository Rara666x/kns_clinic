<?php
include('includes/auth.php');
include('db_connection.php');

// Get patient ID from URL parameter
$patientId = $_GET['patient_id'] ?? '';

if (!$patientId) {
    echo '<div style="text-align: center; padding: 40px; color: red;">Invalid patient ID.</div>';
    exit;
}

// Get patient details
$patientStmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$patientStmt->bind_param("i", $patientId);
$patientStmt->execute();
$patient = $patientStmt->get_result()->fetch_assoc();
$patientStmt->close();

if (!$patient) {
    echo '<div style="text-align: center; padding: 40px; color: red;">Patient not found.</div>';
    exit;
}

// Get patient's medical records count
$recordsStmt = $conn->prepare("SELECT COUNT(*) as count FROM medical_records WHERE patient_id = ?");
$recordsStmt->bind_param("i", $patientId);
$recordsStmt->execute();
$recordsCount = $recordsStmt->get_result()->fetch_assoc()['count'];
$recordsStmt->close();

// Get patient's appointments count
$appointmentsStmt = $conn->prepare("SELECT COUNT(*) as count FROM appointments WHERE patient_id = ?");
$appointmentsStmt->bind_param("i", $patientId);
$appointmentsStmt->execute();
$appointmentsCount = $appointmentsStmt->get_result()->fetch_assoc()['count'];
$appointmentsStmt->close();

// Get recent medical records (last 5)
$recentRecordsStmt = $conn->prepare("SELECT * FROM medical_records WHERE patient_id = ? ORDER BY record_date DESC LIMIT 5");
$recentRecordsStmt->bind_param("i", $patientId);
$recentRecordsStmt->execute();
$recentRecords = $recentRecordsStmt->get_result();
$recentRecordsStmt->close();

// Get recent appointments (last 5)
$recentAppointmentsStmt = $conn->prepare("SELECT * FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC LIMIT 5");
$recentAppointmentsStmt->bind_param("i", $patientId);
$recentAppointmentsStmt->execute();
$recentAppointments = $recentAppointmentsStmt->get_result();
$recentAppointmentsStmt->close();
?>

<div style="max-height: 70vh; overflow-y: auto;">
    <!-- Patient Basic Information -->
    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 16px 0; color: var(--text-900);">👤 Basic Information</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div>
                <strong>Full Name:</strong><br>
                <span style="color: var(--text-700);"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></span>
            </div>
            <div>
                <strong>Email:</strong><br>
                <span style="color: var(--text-700);"><?php echo htmlspecialchars($patient['email']); ?></span>
            </div>
            <div>
                <strong>Phone:</strong><br>
                <span style="color: var(--text-700);"><?php echo htmlspecialchars($patient['phone']); ?></span>
            </div>
            <div>
                <strong>Date of Birth:</strong><br>
                <span style="color: var(--text-700);"><?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?></span>
            </div>
            <div style="grid-column: 1 / -1;">
                <strong>Address:</strong><br>
                <span style="color: var(--text-700);"><?php echo htmlspecialchars($patient['address']); ?></span>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
        <div style="background: #dbeafe; padding: 16px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #1e40af;"><?php echo $recordsCount; ?></div>
            <div style="color: #1e40af; font-weight: 500;">Medical Records</div>
        </div>
        <div style="background: #dcfce7; padding: 16px; border-radius: 8px; text-align: center;">
            <div style="font-size: 24px; font-weight: bold; color: #16a34a;"><?php echo $appointmentsCount; ?></div>
            <div style="color: #16a34a; font-weight: 500;">Appointments</div>
        </div>
    </div>

    <!-- Medical History -->
    <?php if ($patient['medical_history']): ?>
    <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 16px 0; color: var(--text-900);">🏥 Medical History</h3>
        <div style="color: var(--text-700); line-height: 1.6; white-space: pre-wrap;"><?php echo htmlspecialchars($patient['medical_history']); ?></div>
    </div>
    <?php endif; ?>

    <!-- Recent Medical Records -->
    <?php if ($recentRecords && $recentRecords->num_rows > 0): ?>
    <div style="margin-bottom: 20px;">
        <h3 style="margin: 0 0 16px 0; color: var(--text-900);">📄 Recent Medical Records</h3>
        <div style="max-height: 200px; overflow-y: auto;">
            <?php while ($record = $recentRecords->fetch_assoc()): ?>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: between; align-items: start;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: var(--text-900);"><?php echo htmlspecialchars($record['title']); ?></div>
                        <div style="font-size: 14px; color: var(--text-600); margin: 4px 0;"><?php echo htmlspecialchars($record['record_type']); ?></div>
                        <div style="font-size: 12px; color: var(--text-500);"><?php echo date('M j, Y', strtotime($record['record_date'])); ?></div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Appointments -->
    <?php if ($recentAppointments && $recentAppointments->num_rows > 0): ?>
    <div style="margin-bottom: 20px;">
        <h3 style="margin: 0 0 16px 0; color: var(--text-900);">📅 Recent Appointments</h3>
        <div style="max-height: 200px; overflow-y: auto;">
            <?php while ($appointment = $recentAppointments->fetch_assoc()): ?>
            <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px; margin-bottom: 8px;">
                <div style="display: flex; justify-content: between; align-items: start;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: var(--text-900);"><?php echo htmlspecialchars($appointment['appointment_type']); ?></div>
                        <div style="font-size: 14px; color: var(--text-600); margin: 4px 0;">
                            <?php echo date('M j, Y', strtotime($appointment['appointment_date'])); ?> at
                            <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                        </div>
                        <div style="font-size: 12px; color: var(--text-500);">
                            Status: <span style="text-transform: capitalize;"><?php echo htmlspecialchars($appointment['status']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Registration Info -->
    <div style="background: #f3f4f6; padding: 16px; border-radius: 8px; text-align: center; color: var(--text-600); font-size: 14px;">
        Patient registered on <?php echo date('M j, Y \a\t g:i A', strtotime($patient['created_at'])); ?>
    </div>
</div>



