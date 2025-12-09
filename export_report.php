<?php
include('includes/auth.php');
include('db_connection.php');

// Get parameters
$exportType = $_GET['type'] ?? 'pdf';
$reportType = $_GET['report_type'] ?? 'monthly';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get report data (similar to reports.php)
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

// Appointments in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$stats['appointments_range'] = $result->fetch_assoc()['total'];
$stmt->close();

// Medical records in date range
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM medical_records WHERE record_date BETWEEN ? AND ?");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$stats['records_range'] = $result->fetch_assoc()['total'];
$stmt->close();

// Get completed appointments count
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE appointment_date BETWEEN ? AND ? AND status = 'completed'");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$result = $stmt->get_result();
$completedAppointments = $result->fetch_assoc()['total'];
$stmt->close();

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


if ($exportType === 'pdf') {
    // Simple HTML to PDF conversion (you might want to use a proper PDF library like TCPDF or FPDF)
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title><?php echo ucfirst($reportType); ?> Clinical Report</title>
        <link rel="stylesheet" href="css/common.css">
        <link rel="stylesheet" href="css/export_report.css">
    </head>
    <body>
        <a href="reports.php?report_type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="back-button">← Back to Reports</a>
        
        <div class="header">
            <h1>Clinical System - <?php echo ucfirst($reportType); ?> Report</h1>
            <p>Report Period: <?php echo date('M j, Y', strtotime($startDate)); ?> - <?php echo date('M j, Y', strtotime($endDate)); ?></p>
            <p>Generated on: <?php echo date('M j, Y H:i:s'); ?></p>
        </div>

        <div class="section">
            <h3>Key Statistics</h3>
            <table class="stats-table">
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Total Patients</td>
                    <td><?php echo number_format($stats['total_patients']); ?></td>
                </tr>
                <tr>
                    <td>New Patients (Period)</td>
                    <td><?php echo number_format($stats['new_patients_period']); ?></td>
                </tr>
                <tr>
                    <td>Appointments (Period)</td>
                    <td><?php echo number_format($stats['appointments_range']); ?></td>
                </tr>
                <tr>
                    <td>Medical Records (Period)</td>
                    <td><?php echo number_format($stats['records_range']); ?></td>
                </tr>
                <tr>
                    <td>Completion Rate</td>
                    <td><?php echo $stats['appointments_range'] > 0 ? round(($completedAppointments / $stats['appointments_range']) * 100, 1) : 0; ?>%</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h3>Appointment Status Breakdown</h3>
            <table class="stats-table">
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
                <?php 
                $totalAppointments = array_sum($appointmentStatuses);
                foreach ($appointmentStatuses as $status => $count): 
                    $percentage = $totalAppointments > 0 ? round(($count / $totalAppointments) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo ucfirst(str_replace('_', ' ', $status)); ?></td>
                    <td><?php echo $count; ?></td>
                    <td><?php echo $percentage; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section">
            <h3>Medical Record Types</h3>
            <table class="stats-table">
                <tr>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
                <?php 
                $totalRecords = array_sum($recordTypes);
                foreach ($recordTypes as $type => $count): 
                    $percentage = $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo $type; ?></td>
                    <td><?php echo $count; ?></td>
                    <td><?php echo $percentage; ?>%</td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>


        <script>
            // Auto-print when page loads
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
} elseif ($exportType === 'excel') {
    // CSV export (Excel compatible)
    $filename = 'clinical_report_' . $reportType . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Write header
    fputcsv($output, ['Clinical System - ' . ucfirst($reportType) . ' Report']);
    fputcsv($output, ['Report Period: ' . date('M j, Y', strtotime($startDate)) . ' - ' . date('M j, Y', strtotime($endDate))]);
    fputcsv($output, ['Generated on: ' . date('M j, Y H:i:s')]);
    fputcsv($output, []);
    
    // Key Statistics
    fputcsv($output, ['Key Statistics']);
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Patients', $stats['total_patients']]);
    fputcsv($output, ['New Patients (Period)', $stats['new_patients_period']]);
    fputcsv($output, ['Appointments (Period)', $stats['appointments_range']]);
    fputcsv($output, ['Medical Records (Period)', $stats['records_range']]);
    fputcsv($output, ['Completion Rate', ($stats['appointments_range'] > 0 ? round(($completedAppointments / $stats['appointments_range']) * 100, 1) : 0) . '%']);
    fputcsv($output, []);
    
    // Appointment Status Breakdown
    fputcsv($output, ['Appointment Status Breakdown']);
    fputcsv($output, ['Status', 'Count', 'Percentage']);
    $totalAppointments = array_sum($appointmentStatuses);
    foreach ($appointmentStatuses as $status => $count) {
        $percentage = $totalAppointments > 0 ? round(($count / $totalAppointments) * 100, 1) : 0;
        fputcsv($output, [ucfirst(str_replace('_', ' ', $status)), $count, $percentage . '%']);
    }
    fputcsv($output, []);
    
    // Medical Record Types
    fputcsv($output, ['Medical Record Types']);
    fputcsv($output, ['Type', 'Count', 'Percentage']);
    $totalRecords = array_sum($recordTypes);
    foreach ($recordTypes as $type => $count) {
        $percentage = $totalRecords > 0 ? round(($count / $totalRecords) * 100, 1) : 0;
        fputcsv($output, [$type, $count, $percentage . '%']);
    }
    fputcsv($output, []);
    
    
    fclose($output);
}
?>