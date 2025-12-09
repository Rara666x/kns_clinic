<?php
include('includes/auth.php');
include('db_connection.php');

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_medicine':
                $medicineName = $_POST['medicine_name'] ?? '';
                $genericName = $_POST['generic_name'] ?? '';
                $medicineType = $_POST['medicine_type'] ?? '';
                $dosageForm = $_POST['dosage_form'] ?? '';
                $strength = $_POST['strength'] ?? '';
                $manufacturer = $_POST['manufacturer'] ?? '';
                $batchNumber = $_POST['batch_number'] ?? '';
                $currentStock = $_POST['current_stock'] ?? 0;
                $minimumStock = $_POST['minimum_stock'] ?? 10;
                $maximumStock = $_POST['maximum_stock'] ?? 1000;
                $expiryDate = $_POST['expiry_date'] ?? '';
                $supplier = $_POST['supplier'] ?? '';
                $storageLocation = $_POST['storage_location'] ?? '';
                $description = $_POST['description'] ?? '';

                if ($medicineName && $medicineType && $dosageForm) {
                    $stmt = $conn->prepare("INSERT INTO medicines (medicine_name, generic_name, medicine_type, dosage_form, strength, manufacturer, batch_number, current_stock, minimum_stock, maximum_stock, expiry_date, supplier, storage_location, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssssssiissss", $medicineName, $genericName, $medicineType, $dosageForm, $strength, $manufacturer, $batchNumber, $currentStock, $minimumStock, $maximumStock, $expiryDate, $supplier, $storageLocation, $description);

                    if ($stmt->execute()) {
                        $message = "Medicine added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding medicine: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;

            case 'add_equipment':
                $equipmentName = $_POST['equipment_name'] ?? '';
                $equipmentType = $_POST['equipment_type'] ?? '';
                $modelNumber = $_POST['model_number'] ?? '';
                $serialNumber = $_POST['serial_number'] ?? '';
                $manufacturer = $_POST['manufacturer'] ?? '';
                $purchaseDate = $_POST['purchase_date'] ?? '';
                $warrantyExpiry = $_POST['warranty_expiry'] ?? '';
                $maintenanceDue = $_POST['maintenance_due'] ?? '';
                $status = $_POST['status'] ?? 'operational';
                $location = $_POST['location'] ?? '';
                $assignedTo = $_POST['assigned_to'] ?? '';
                $supplier = $_POST['supplier'] ?? '';
                $description = $_POST['description'] ?? '';

                if ($equipmentName && $equipmentType) {
                    $stmt = $conn->prepare("INSERT INTO medical_equipment (equipment_name, equipment_type, model_number, serial_number, manufacturer, purchase_date, warranty_expiry, maintenance_due, status, location, assigned_to, supplier, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssssssss", $equipmentName, $equipmentType, $modelNumber, $serialNumber, $manufacturer, $purchaseDate, $warrantyExpiry, $maintenanceDue, $status, $location, $assignedTo, $supplier, $description);

                    if ($stmt->execute()) {
                        $message = "Equipment added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding equipment: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;

            case 'update_stock':
                $medicineId = $_POST['medicine_id'] ?? '';
                $newStock = $_POST['new_stock'] ?? 0;
                $reason = $_POST['reason'] ?? '';

                if ($medicineId && $reason) {
                    // Get current stock
                    $result = $conn->query("SELECT current_stock FROM medicines WHERE id = $medicineId");
                    $currentStock = $result->fetch_assoc()['current_stock'];

                    // Update stock
                    $stmt = $conn->prepare("UPDATE medicines SET current_stock = ? WHERE id = ?");
                    $stmt->bind_param("ii", $newStock, $medicineId);
                    $stmt->execute();
                    $stmt->close();

                    // Log movement
                    $movementType = $newStock > $currentStock ? 'in' : ($newStock < $currentStock ? 'out' : 'adjustment');
                    $quantity = abs($newStock - $currentStock);
                    $stmt = $conn->prepare("INSERT INTO medicine_stock_movements (medicine_id, movement_type, quantity, reason, performed_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiisi", $medicineId, $movementType, $quantity, $reason, $_SESSION['user_id']);
                    $stmt->execute();
                    $stmt->close();

                    $message = "Stock updated successfully!";
                    $messageType = "success";
                }
                break;
        }
    }
}

// Get inventory statistics
$stats = [];

// Medicine statistics
$result = $conn->query("SELECT COUNT(*) as total FROM medicines WHERE is_active = 1");
$stats['total_medicines'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as low_stock FROM medicines WHERE current_stock <= minimum_stock AND is_active = 1");
$stats['low_stock_medicines'] = $result->fetch_assoc()['low_stock'];

$result = $conn->query("SELECT COUNT(*) as expired FROM medicines WHERE expiry_date < CURDATE() AND is_active = 1");
$stats['expired_medicines'] = $result->fetch_assoc()['expired'];

$result = $conn->query("SELECT COUNT(*) as expiring_soon FROM medicines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND is_active = 1");
$stats['expiring_soon_medicines'] = $result->fetch_assoc()['expiring_soon'];

$result = $conn->query("SELECT COUNT(DISTINCT m.id) as new_stock FROM medicines m JOIN medicine_stock_movements msm ON m.id = msm.medicine_id WHERE m.is_active = 1 AND msm.movement_type = 'in' AND msm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_stock_medicines'] = $result->fetch_assoc()['new_stock'];

// Equipment statistics
$result = $conn->query("SELECT COUNT(*) as total FROM medical_equipment WHERE is_active = 1");
$stats['total_equipment'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as operational FROM medical_equipment WHERE status = 'operational' AND is_active = 1");
$stats['operational_equipment'] = $result->fetch_assoc()['operational'];

$result = $conn->query("SELECT COUNT(*) as maintenance_due FROM medical_equipment WHERE maintenance_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND is_active = 1");
$stats['maintenance_due_equipment'] = $result->fetch_assoc()['maintenance_due'];

// Get recent stock movements
$recentMovements = $conn->query("
    SELECT msm.*, m.medicine_name, u.fullName as performed_by_name
    FROM medicine_stock_movements msm
    JOIN medicines m ON msm.medicine_id = m.id
    LEFT JOIN users u ON msm.performed_by = u.id
    ORDER BY msm.created_at DESC
    LIMIT 10
");

// Get low stock medicines
$lowStockMedicines = $conn->query("
    SELECT * FROM medicines
    WHERE current_stock <= minimum_stock AND is_active = 1
    ORDER BY (current_stock / minimum_stock) ASC
    LIMIT 5
");

// Get expiring medicines
$expiringMedicines = $conn->query("
    SELECT * FROM medicines
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND is_active = 1
    ORDER BY expiry_date ASC
    LIMIT 5
");

// Get equipment needing maintenance
$maintenanceEquipment = $conn->query("
    SELECT * FROM medical_equipment
    WHERE maintenance_due <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND is_active = 1
    ORDER BY maintenance_due ASC
    LIMIT 5
");

// Get medicines with new stock (received within last 7 days)
$newStockMedicines = $conn->query("
    SELECT m.*, msm.created_at as stock_received_date
    FROM medicines m
    JOIN medicine_stock_movements msm ON m.id = msm.medicine_id
    WHERE m.is_active = 1
    AND msm.movement_type = 'in'
    AND msm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY msm.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Inventory Management - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/inventory.css">
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

    <div class="inventory-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>🏥 Inventory Management</h1>
                <div style="display: flex; gap: 12px;">
                    <button class="btn-primary" onclick="openModal('addMedicineModal')">+ Add Medicine</button>
                    <button class="btn-primary" onclick="openModal('addEquipmentModal')">+ Add Equipment</button>
                </div>
            </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card medicines">
                <h3>💊 Total Medicines</h3>
                <div class="stat-value"><?php echo $stats['total_medicines']; ?></div>
                <div class="stat-subtitle">Active medicines in inventory</div>
            </div>
            <div class="stat-card equipment">
                <h3>🔧 Total Equipment</h3>
                <div class="stat-value"><?php echo $stats['total_equipment']; ?></div>
                <div class="stat-subtitle">Medical equipment items</div>
            </div>
            <div class="stat-card alerts">
                <h3>⚠️ Low Stock</h3>
                <div class="stat-value"><?php echo $stats['low_stock_medicines']; ?></div>
                <div class="stat-subtitle">Medicines below minimum stock</div>
            </div>
            <div class="stat-card movements">
                <h3>📦 Expiring Soon</h3>
                <div class="stat-value"><?php echo $stats['expiring_soon_medicines']; ?></div>
                <div class="stat-subtitle">Medicines expiring in 30 days</div>
            </div>
            <div class="stat-card" style="border-left: 4px solid #10b981;">
                <h3>🆕 New Stock</h3>
                <div class="stat-value"><?php echo $stats['new_stock_medicines']; ?></div>
                <div class="stat-subtitle">Medicines with new stock (7 days)</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <div class="main-content">
                <!-- Recent Stock Movements -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📦 Recent Stock Movements</h3>
                        <a href="medicines.php" class="btn-secondary">View All</a>
                    </div>
                    <div class="movement-list">
                        <?php if ($recentMovements && $recentMovements->num_rows > 0): ?>
                            <?php while ($movement = $recentMovements->fetch_assoc()): ?>
                                <div class="movement-item">
                                    <div class="movement-info">
                                        <div class="movement-medicine"><?php echo htmlspecialchars($movement['medicine_name']); ?></div>
                                        <div class="movement-details">
                                            <?php echo $movement['quantity']; ?> units - <?php echo htmlspecialchars($movement['reason']); ?> -
                                            <?php echo $movement['performed_by_name'] ?: 'System'; ?> -
                                            <?php echo date('M j, Y H:i', strtotime($movement['created_at'])); ?>
                                        </div>
                                    </div>
                                    <span class="movement-type <?php echo $movement['movement_type']; ?>">
                                        <?php echo ucfirst($movement['movement_type']); ?>
                                    </span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px; color: var(--text-500);">
                                No recent stock movements
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Medicine Inventory Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">💊 Medicine Inventory</h3>
                        <a href="medicines.php" class="btn-secondary">Manage Medicines</a>
                    </div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Medicine Name</th>
                                <th>Type</th>
                                <th>Current Stock</th>
                                <th>Min Stock</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $medicinesResult = $conn->query("
                                SELECT m.*,
                                       (SELECT MAX(msm.created_at)
                                        FROM medicine_stock_movements msm
                                        WHERE msm.medicine_id = m.id
                                        AND msm.movement_type = 'in'
                                        AND msm.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as last_stock_in
                                FROM medicines m
                                WHERE m.is_active = 1
                                ORDER BY m.medicine_name
                                LIMIT 10
                            ");
                            while ($medicine = $medicinesResult->fetch_assoc()):
                                $stockClass = '';
                                if ($medicine['current_stock'] <= $medicine['minimum_stock']) {
                                    $stockClass = 'stock-critical';
                                } elseif ($medicine['current_stock'] <= $medicine['minimum_stock'] * 1.5) {
                                    $stockClass = 'stock-low';
                                } else {
                                    $stockClass = 'stock-adequate';
                                }

                                $expiryClass = '';
                                if ($medicine['expiry_date'] < date('Y-m-d')) {
                                    $expiryClass = 'expiry-expired';
                                } elseif ($medicine['expiry_date'] <= date('Y-m-d', strtotime('+30 days'))) {
                                    $expiryClass = 'expiry-warning';
                                } else {
                                    $expiryClass = 'expiry-good';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="medicine-name-container">
                                            <?php echo htmlspecialchars($medicine['medicine_name']); ?>
                                            <?php if ($medicine['last_stock_in']): ?>
                                                <span class="new-stock-indicator" title="New stock received within the last 7 days">New</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($medicine['medicine_type']); ?></td>
                                    <td class="<?php echo $stockClass; ?>"><?php echo $medicine['current_stock']; ?></td>
                                    <td><?php echo $medicine['minimum_stock']; ?></td>
                                    <td class="<?php echo $expiryClass; ?>">
                                        <?php echo $medicine['expiry_date'] ? date('M j, Y', strtotime($medicine['expiry_date'])) : 'N/A'; ?>
                                    </td>
                                    <td>
                                        <?php if ($medicine['current_stock'] <= $medicine['minimum_stock']): ?>
                                            <span class="stock-critical">Low Stock</span>
                                        <?php elseif ($medicine['expiry_date'] <= date('Y-m-d', strtotime('+30 days'))): ?>
                                            <span class="expiry-warning">Expiring Soon</span>
                                        <?php else: ?>
                                            <span class="stock-adequate">Good</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sidebar">
                <!-- Alerts -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">⚠️ Alerts</h3>
                    </div>
                    <div class="alert-list">
                        <?php if ($lowStockMedicines && $lowStockMedicines->num_rows > 0): ?>
                            <?php while ($medicine = $lowStockMedicines->fetch_assoc()): ?>
                                <div class="alert-item">
                                    <span class="alert-icon">📉</span>
                                    <div class="alert-content">
                                        <div class="alert-title">Low Stock Alert</div>
                                        <div class="alert-desc"><?php echo htmlspecialchars($medicine['medicine_name']); ?> - Only <?php echo $medicine['current_stock']; ?> units left</div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <?php if ($expiringMedicines && $expiringMedicines->num_rows > 0): ?>
                            <?php while ($medicine = $expiringMedicines->fetch_assoc()): ?>
                                <div class="alert-item">
                                    <span class="alert-icon">⏰</span>
                                    <div class="alert-content">
                                        <div class="alert-title">Expiring Soon</div>
                                        <div class="alert-desc"><?php echo htmlspecialchars($medicine['medicine_name']); ?> - Expires <?php echo date('M j, Y', strtotime($medicine['expiry_date'])); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <?php if ($maintenanceEquipment && $maintenanceEquipment->num_rows > 0): ?>
                            <?php while ($equipment = $maintenanceEquipment->fetch_assoc()): ?>
                                <div class="alert-item">
                                    <span class="alert-icon">🔧</span>
                                    <div class="alert-content">
                                        <div class="alert-title">Maintenance Due</div>
                                        <div class="alert-desc"><?php echo htmlspecialchars($equipment['equipment_name']); ?> - Due <?php echo date('M j, Y', strtotime($equipment['maintenance_due'])); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <?php if ($newStockMedicines && $newStockMedicines->num_rows > 0): ?>
                            <?php while ($medicine = $newStockMedicines->fetch_assoc()): ?>
                                <div class="alert-item" style="background-color: #f0fdf4; border-left-color: #10b981;">
                                    <span class="alert-icon">📦</span>
                                    <div class="alert-content">
                                        <div class="alert-title" style="color: #065f46;">New Stock Received</div>
                                        <div class="alert-desc" style="color: #047857;"><?php echo htmlspecialchars($medicine['medicine_name']); ?> - Received <?php echo date('M j, Y', strtotime($medicine['stock_received_date'])); ?></div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>

                        <?php if (!$lowStockMedicines->num_rows && !$expiringMedicines->num_rows && !$maintenanceEquipment->num_rows && !$newStockMedicines->num_rows): ?>
                            <div style="text-align: center; padding: 20px; color: var(--text-500);">
                                ✅ No alerts at this time
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">⚡ Quick Actions</h3>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <a href="medicines.php" class="btn-secondary">Manage Medicines</a>
                        <a href="equipment.php" class="btn-secondary">Manage Equipment</a>
                        <a href="reports.php?report_type=inventory" class="btn-secondary">Inventory Reports</a>
                        <button class="btn-warning" onclick="openModal('stockUpdateModal')">Update Stock</button>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>

    <!-- Add Medicine Modal -->
    <div id="addMedicineModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Medicine</h2>
                <span class="close" onclick="closeModal('addMedicineModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_medicine">

                <div class="form-row">
                    <div class="form-group">
                        <label for="medicine_name">Medicine Name *</label>
                        <input type="text" id="medicine_name" name="medicine_name" required>
                    </div>
                    <div class="form-group">
                        <label for="generic_name">Generic Name</label>
                        <input type="text" id="generic_name" name="generic_name">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="medicine_type">Medicine Type *</label>
                        <select id="medicine_type" name="medicine_type" required>
                            <option value="">Select Type</option>
                            <option value="Analgesic">Analgesic</option>
                            <option value="Antibiotic">Antibiotic</option>
                            <option value="Antihistamine">Antihistamine</option>
                            <option value="Antacid">Antacid</option>
                            <option value="Vitamin">Vitamin</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dosage_form">Dosage Form *</label>
                        <select id="dosage_form" name="dosage_form" required>
                            <option value="">Select Form</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Cream">Cream</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="strength">Strength</label>
                        <input type="text" id="strength" name="strength" placeholder="e.g., 500mg">
                    </div>
                    <div class="form-group">
                        <label for="manufacturer">Manufacturer</label>
                        <input type="text" id="manufacturer" name="manufacturer">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="batch_number">Batch Number</label>
                        <input type="text" id="batch_number" name="batch_number">
                    </div>
                    <div class="form-group">
                        <label for="expiry_date">Expiry Date</label>
                        <input type="date" id="expiry_date" name="expiry_date">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="current_stock">Current Stock</label>
                        <input type="number" id="current_stock" name="current_stock" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label for="minimum_stock">Minimum Stock</label>
                        <input type="number" id="minimum_stock" name="minimum_stock" min="0" value="10">
                    </div>
                </div>

                <div class="form-group">
                    <label for="maximum_stock">Maximum Stock</label>
                    <input type="number" id="maximum_stock" name="maximum_stock" min="0" value="1000">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="supplier">Supplier</label>
                        <input type="text" id="supplier" name="supplier">
                    </div>
                    <div class="form-group">
                        <label for="storage_location">Storage Location</label>
                        <input type="text" id="storage_location" name="storage_location" placeholder="e.g., Shelf A1">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addMedicineModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Medicine</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Equipment Modal -->
    <div id="addEquipmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Equipment</h2>
                <span class="close" onclick="closeModal('addEquipmentModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_equipment">

                <div class="form-row">
                    <div class="form-group">
                        <label for="equipment_name">Equipment Name *</label>
                        <input type="text" id="equipment_name" name="equipment_name" required>
                    </div>
                    <div class="form-group">
                        <label for="equipment_type">Equipment Type *</label>
                        <select id="equipment_type" name="equipment_type" required>
                            <option value="">Select Type</option>
                            <option value="Diagnostic">Diagnostic</option>
                            <option value="Therapeutic">Therapeutic</option>
                            <option value="Monitoring">Monitoring</option>
                            <option value="Surgical">Surgical</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="model_number">Model Number</label>
                        <input type="text" id="model_number" name="model_number">
                    </div>
                    <div class="form-group">
                        <label for="serial_number">Serial Number</label>
                        <input type="text" id="serial_number" name="serial_number">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="manufacturer">Manufacturer</label>
                        <input type="text" id="manufacturer" name="manufacturer">
                    </div>
                    <div class="form-group">
                        <label for="purchase_date">Purchase Date</label>
                        <input type="date" id="purchase_date" name="purchase_date">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="warranty_expiry">Warranty Expiry</label>
                        <input type="date" id="warranty_expiry" name="warranty_expiry">
                    </div>
                    <div class="form-group">
                        <label for="maintenance_due">Maintenance Due</label>
                        <input type="date" id="maintenance_due" name="maintenance_due">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="operational">Operational</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="out_of_order">Out of Order</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" placeholder="e.g., Room 101">
                    </div>
                </div>

                <div class="form-group">
                    <label for="assigned_to">Assigned To</label>
                    <input type="text" id="assigned_to" name="assigned_to" placeholder="e.g., Staff Member">
                </div>

                <div class="form-group">
                    <label for="supplier">Supplier</label>
                    <input type="text" id="supplier" name="supplier">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addEquipmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Update Modal -->
    <div id="stockUpdateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Stock</h2>
                <span class="close" onclick="closeModal('stockUpdateModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_stock">

                <div class="form-group">
                    <label for="medicine_id">Select Medicine</label>
                    <select id="medicine_id" name="medicine_id" required>
                        <option value="">Select Medicine</option>
                        <?php
                        $medicinesForUpdate = $conn->query("SELECT id, medicine_name, current_stock FROM medicines WHERE is_active = 1 ORDER BY medicine_name");
                        while ($medicine = $medicinesForUpdate->fetch_assoc()):
                        ?>
                            <option value="<?php echo $medicine['id']; ?>" data-current="<?php echo $medicine['current_stock']; ?>">
                                <?php echo htmlspecialchars($medicine['medicine_name']); ?> (Current: <?php echo $medicine['current_stock']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="new_stock">New Stock Quantity</label>
                    <input type="number" id="new_stock" name="new_stock" min="0" required>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Update</label>
                    <select id="reason" name="reason" required>
                        <option value="">Select Reason</option>
                        <option value="Stock received">Stock received</option>
                        <option value="Stock used">Stock used</option>
                        <option value="Stock adjustment">Stock adjustment</option>
                        <option value="Damaged/Expired">Damaged/Expired</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('stockUpdateModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Override closeModal for inventory-specific functionality
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Clear form if it's add modal
            if (modalId.includes('Modal')) {
                document.querySelector('#' + modalId + ' form').reset();
            }
        }

        // Update stock input when medicine is selected
        document.getElementById('medicine_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const currentStock = selectedOption.getAttribute('data-current');
            if (currentStock) {
                document.getElementById('new_stock').value = currentStock;
            }
        });
    </script>

    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>
