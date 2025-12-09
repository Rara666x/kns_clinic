<?php
include('includes/auth.php');
include('db_connection.php');

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_equipment':
                $equipment_name = $_POST['equipment_name'];
                $equipment_type = $_POST['equipment_type'];
                $model_number = $_POST['model_number'];
                $serial_number = $_POST['serial_number'];
                $manufacturer = $_POST['manufacturer'];
                $purchase_date = $_POST['purchase_date'];
                $warranty_expiry = $_POST['warranty_expiry'];
                $maintenance_due = $_POST['maintenance_due'];
                $status = $_POST['status'];
                $location = $_POST['location'];
                $assigned_to = $_POST['assigned_to'];
                $supplier = $_POST['supplier'];
                $description = $_POST['description'];
                
                $stmt = $conn->prepare("INSERT INTO medical_equipment (equipment_name, equipment_type, model_number, serial_number, manufacturer, purchase_date, warranty_expiry, maintenance_due, status, location, assigned_to, supplier, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssss", $equipment_name, $equipment_type, $model_number, $serial_number, $manufacturer, $purchase_date, $warranty_expiry, $maintenance_due, $status, $location, $assigned_to, $supplier, $description);
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'update_status':
                $equipment_id = $_POST['equipment_id'];
                $new_status = $_POST['new_status'];
                $maintenance_due = $_POST['maintenance_due'];
                
                $stmt = $conn->prepare("UPDATE medical_equipment SET status = ?, maintenance_due = ? WHERE id = ?");
                $stmt->bind_param("ssi", $new_status, $maintenance_due, $equipment_id);
                $stmt->execute();
                $stmt->close();
                break;
        }
    }
}

// Get equipment with filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereClause = "WHERE is_active = 1";
if ($filter === 'operational') {
    $whereClause .= " AND status = 'operational'";
} elseif ($filter === 'maintenance') {
    $whereClause .= " AND status = 'maintenance'";
} elseif ($filter === 'out_of_order') {
    $whereClause .= " AND status = 'out_of_order'";
} elseif ($filter === 'maintenance_due') {
    $whereClause .= " AND maintenance_due <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

if ($search) {
    $whereClause .= " AND (equipment_name LIKE '%$search%' OR manufacturer LIKE '%$search%' OR model_number LIKE '%$search%')";
}

$result = $conn->query("SELECT * FROM medical_equipment $whereClause ORDER BY equipment_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medical Equipment - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/equipment.css">
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

    <div class="equipment-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>🔧 Medical Equipment</h1>
                <button class="btn-primary" onclick="openModal('addEquipmentModal')">Add New Equipment</button>
            </div>

        <div class="filters">
            <form method="GET" style="display: contents;">
                <div class="filter-group">
                    <label>Filter</label>
                    <select name="filter">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Equipment</option>
                        <option value="operational" <?php echo $filter === 'operational' ? 'selected' : ''; ?>>Operational</option>
                        <option value="maintenance" <?php echo $filter === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        <option value="out_of_order" <?php echo $filter === 'out_of_order' ? 'selected' : ''; ?>>Out of Order</option>
                        <option value="maintenance_due" <?php echo $filter === 'maintenance_due' ? 'selected' : ''; ?>>Maintenance Due</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search equipment...">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <div class="equipment-table">
            <div class="table-header">
                <h3>Medical Equipment Inventory</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Equipment Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Maintenance</th>
                        <th>Location</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($equipment = $result->fetch_assoc()): ?>
                        <?php
                        $statusClass = 'status-' . $equipment['status'];
                        
                        $maintenanceClass = '';
                        $maintenanceText = '';
                        if ($equipment['maintenance_due'] < date('Y-m-d')) {
                            $maintenanceClass = 'maintenance-overdue';
                            $maintenanceText = 'Overdue';
                        } elseif ($equipment['maintenance_due'] <= date('Y-m-d', strtotime('+30 days'))) {
                            $maintenanceClass = 'maintenance-warning';
                            $maintenanceText = 'Due Soon';
                        } else {
                            $maintenanceClass = 'maintenance-good';
                            $maintenanceText = 'Good';
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($equipment['equipment_name']); ?></strong><br>
                                <small style="color: var(--text-500);"><?php echo htmlspecialchars($equipment['manufacturer']); ?> - <?php echo htmlspecialchars($equipment['model_number']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($equipment['equipment_type']); ?></td>
                            <td>
                                <span class="status-indicator <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $equipment['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="maintenance-indicator <?php echo $maintenanceClass; ?>">
                                    <?php echo $maintenanceText; ?>
                                </span><br>
                                <small><?php echo date('M j, Y', strtotime($equipment['maintenance_due'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($equipment['location']); ?></td>
                            <td><?php echo htmlspecialchars($equipment['assigned_to']); ?></td>
                            <td>
                                <button class="btn-secondary" onclick="openStatusModal(<?php echo $equipment['id']; ?>, '<?php echo htmlspecialchars($equipment['equipment_name']); ?>', '<?php echo $equipment['status']; ?>', '<?php echo $equipment['maintenance_due']; ?>')">
                                    Update Status
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
                        <label>Equipment Name *</label>
                        <input type="text" name="equipment_name" required>
                    </div>
                    <div class="form-group">
                        <label>Equipment Type *</label>
                        <select name="equipment_type" required>
                            <option value="">Select Type</option>
                            <option value="Diagnostic">Diagnostic</option>
                            <option value="Monitoring">Monitoring</option>
                            <option value="Emergency">Emergency</option>
                            <option value="Imaging">Imaging</option>
                            <option value="Sterilization">Sterilization</option>
                            <option value="Surgical">Surgical</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Model Number</label>
                        <input type="text" name="model_number">
                    </div>
                    <div class="form-group">
                        <label>Serial Number</label>
                        <input type="text" name="serial_number">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input type="text" name="manufacturer">
                    </div>
                    <div class="form-group">
                        <label>Purchase Date</label>
                        <input type="date" name="purchase_date">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Warranty Expiry</label>
                        <input type="date" name="warranty_expiry">
                    </div>
                    <div class="form-group">
                        <label>Maintenance Due *</label>
                        <input type="date" name="maintenance_due" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status *</label>
                        <select name="status" required>
                            <option value="operational">Operational</option>
                            <option value="maintenance">Under Maintenance</option>
                            <option value="out_of_order">Out of Order</option>
                            <option value="retired">Retired</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Assigned To</label>
                        <input type="text" name="assigned_to">
                    </div>
                </div>
                <div class="form-group">
                    <label>Supplier</label>
                    <input type="text" name="supplier">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addEquipmentModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Equipment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="updateStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Equipment Status</h2>
                <span class="close" onclick="closeModal('updateStatusModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="equipment_id" id="status_equipment_id">
                <div class="form-group">
                    <label>Equipment</label>
                    <input type="text" id="status_equipment_name" readonly style="background-color: #f8fafc;">
                </div>
                <div class="form-group">
                    <label>Status *</label>
                    <select name="new_status" id="status_select" required>
                        <option value="operational">Operational</option>
                        <option value="maintenance">Under Maintenance</option>
                        <option value="out_of_order">Out of Order</option>
                        <option value="retired">Retired</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Next Maintenance Due *</label>
                    <input type="date" name="maintenance_due" id="status_maintenance_due" required>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('updateStatusModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Status</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script>
        // Equipment-specific functionality
        function openStatusModal(equipmentId, equipmentName, currentStatus, maintenanceDue) {
            document.getElementById('status_equipment_id').value = equipmentId;
            document.getElementById('status_equipment_name').value = equipmentName;
            document.getElementById('status_select').value = currentStatus;
            document.getElementById('status_maintenance_due').value = maintenanceDue;
            openModal('updateStatusModal');
        }
    </script>
    
    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>