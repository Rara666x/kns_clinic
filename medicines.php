<?php
include('includes/auth.php');
include('db_connection.php');

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_medicine':
                $medicine_name = $_POST['medicine_name'];
                $generic_name = $_POST['generic_name'];
                $medicine_type = $_POST['medicine_type'];
                $dosage_form = $_POST['dosage_form'];
                $strength = $_POST['strength'];
                $manufacturer = $_POST['manufacturer'];
                $batch_number = $_POST['batch_number'];
                $current_stock = $_POST['current_stock'];
                $minimum_stock = $_POST['minimum_stock'];
                $maximum_stock = $_POST['maximum_stock'];
                $expiry_date = $_POST['expiry_date'];
                $supplier = $_POST['supplier'];
                $storage_location = $_POST['storage_location'];
                $description = $_POST['description'];
                
                $stmt = $conn->prepare("INSERT INTO medicines (medicine_name, generic_name, medicine_type, dosage_form, strength, manufacturer, batch_number, current_stock, minimum_stock, maximum_stock, expiry_date, supplier, storage_location, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssiiissss", $medicine_name, $generic_name, $medicine_type, $dosage_form, $strength, $manufacturer, $batch_number, $current_stock, $minimum_stock, $maximum_stock, $expiry_date, $supplier, $storage_location, $description);
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'update_stock':
                $medicine_id = $_POST['medicine_id'];
                $new_stock = $_POST['new_stock'];
                $reason = $_POST['reason'];
                
                // Get current stock
                $result = $conn->query("SELECT current_stock FROM medicines WHERE id = $medicine_id");
                $current_stock = $result->fetch_assoc()['current_stock'];
                
                // Update stock
                $stmt = $conn->prepare("UPDATE medicines SET current_stock = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_stock, $medicine_id);
                $stmt->execute();
                $stmt->close();
                
                // Log movement
                $movement_type = $new_stock > $current_stock ? 'in' : 'out';
                $quantity = abs($new_stock - $current_stock);
                $stmt = $conn->prepare("INSERT INTO medicine_stock_movements (medicine_id, movement_type, quantity, reason, performed_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisi", $medicine_id, $movement_type, $quantity, $reason, $_SESSION['user_id']);
                $stmt->execute();
                $stmt->close();
                break;
        }
    }
}

// Get medicines with filters
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';

$whereClause = "WHERE is_active = 1";
if ($filter === 'low_stock') {
    $whereClause .= " AND current_stock <= minimum_stock";
} elseif ($filter === 'expired') {
    $whereClause .= " AND expiry_date < CURDATE()";
} elseif ($filter === 'expiring_soon') {
    $whereClause .= " AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

if ($search) {
    $whereClause .= " AND (medicine_name LIKE '%$search%' OR generic_name LIKE '%$search%' OR manufacturer LIKE '%$search%')";
}

$result = $conn->query("SELECT * FROM medicines $whereClause ORDER BY medicine_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Medicine Inventory - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/medicines.css">
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

    <div class="medicines-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>💊 Medicine Inventory</h1>
                <button class="btn-primary" onclick="openModal('addMedicineModal')">Add New Medicine</button>
            </div>

        <div class="filters">
            <form method="GET" style="display: contents;">
                <div class="filter-group">
                    <label>Filter</label>
                    <select name="filter">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Medicines</option>
                        <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="expired" <?php echo $filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="expiring_soon" <?php echo $filter === 'expiring_soon' ? 'selected' : ''; ?>>Expiring Soon</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search medicines...">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <div class="medicines-table">
            <div class="table-header">
                <h3>Medicine Inventory</h3>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Type</th>
                        <th>Stock</th>
                        <th>Expiry Date</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($medicine = $result->fetch_assoc()): ?>
                        <?php
                        $stockClass = '';
                        if ($medicine['current_stock'] <= $medicine['minimum_stock']) {
                            $stockClass = 'stock-critical';
                        } elseif ($medicine['current_stock'] <= $medicine['minimum_stock'] * 1.5) {
                            $stockClass = 'stock-low';
                        } else {
                            $stockClass = 'stock-adequate';
                        }
                        
                        $expiryClass = '';
                        $expiryText = '';
                        if ($medicine['expiry_date'] < date('Y-m-d')) {
                            $expiryClass = 'expiry-expired';
                            $expiryText = 'Expired';
                        } elseif ($medicine['expiry_date'] <= date('Y-m-d', strtotime('+30 days'))) {
                            $expiryClass = 'expiry-warning';
                            $expiryText = 'Expiring Soon';
                        } else {
                            $expiryClass = 'expiry-good';
                            $expiryText = 'Good';
                        }
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($medicine['medicine_name']); ?></strong><br>
                                <small style="color: var(--text-500);"><?php echo htmlspecialchars($medicine['generic_name']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($medicine['medicine_type']); ?></td>
                            <td>
                                <span class="stock-indicator <?php echo $stockClass; ?>">
                                    <?php echo $medicine['current_stock']; ?> / <?php echo $medicine['minimum_stock']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="expiry-indicator <?php echo $expiryClass; ?>">
                                    <?php echo $expiryText; ?>
                                </span><br>
                                <small><?php echo date('M j, Y', strtotime($medicine['expiry_date'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($medicine['storage_location']); ?></td>
                            <td>
                                <button class="btn-secondary" onclick="openStockModal(<?php echo $medicine['id']; ?>, '<?php echo htmlspecialchars($medicine['medicine_name']); ?>', <?php echo $medicine['current_stock']; ?>)">
                                    Update Stock
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
                        <label>Medicine Name *</label>
                        <input type="text" name="medicine_name" required>
                    </div>
                    <div class="form-group">
                        <label>Generic Name</label>
                        <input type="text" name="generic_name">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Medicine Type *</label>
                        <select name="medicine_type" required>
                            <option value="">Select Type</option>
                            <option value="Analgesic">Analgesic</option>
                            <option value="Antibiotic">Antibiotic</option>
                            <option value="Antidiabetic">Antidiabetic</option>
                            <option value="Antihypertensive">Antihypertensive</option>
                            <option value="Bronchodilator">Bronchodilator</option>
                            <option value="Hormone">Hormone</option>
                            <option value="NSAID">NSAID</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dosage Form *</label>
                        <select name="dosage_form" required>
                            <option value="">Select Form</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Inhaler">Inhaler</option>
                            <option value="Cream">Cream</option>
                            <option value="Ointment">Ointment</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Strength</label>
                        <input type="text" name="strength" placeholder="e.g., 500mg">
                    </div>
                    <div class="form-group">
                        <label>Manufacturer</label>
                        <input type="text" name="manufacturer">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Batch Number</label>
                        <input type="text" name="batch_number">
                    </div>
                    <div class="form-group">
                        <label>Expiry Date *</label>
                        <input type="date" name="expiry_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Current Stock *</label>
                        <input type="number" name="current_stock" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Minimum Stock *</label>
                        <input type="number" name="minimum_stock" min="0" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Maximum Stock</label>
                        <input type="number" name="maximum_stock" min="0">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Supplier</label>
                        <input type="text" name="supplier">
                    </div>
                    <div class="form-group">
                        <label>Storage Location</label>
                        <input type="text" name="storage_location">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addMedicineModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Add Medicine</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Update Stock Modal -->
    <div id="updateStockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Update Stock</h2>
                <span class="close" onclick="closeModal('updateStockModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="medicine_id" id="stock_medicine_id">
                <div class="form-group">
                    <label>Medicine</label>
                    <input type="text" id="stock_medicine_name" readonly style="background-color: #f8fafc;">
                </div>
                <div class="form-group">
                    <label>New Stock Quantity *</label>
                    <input type="number" name="new_stock" id="stock_quantity" min="0" required>
                </div>
                <div class="form-group">
                    <label>Reason for Change *</label>
                    <select name="reason" required>
                        <option value="">Select Reason</option>
                        <option value="Purchase">New Purchase</option>
                        <option value="Usage">Used in Treatment</option>
                        <option value="Adjustment">Stock Adjustment</option>
                        <option value="Return">Return from Patient</option>
                        <option value="Expired">Expired Stock</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('updateStockModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Update Stock</button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script>
        // Medicines-specific functionality
        function openStockModal(medicineId, medicineName, currentStock) {
            document.getElementById('stock_medicine_id').value = medicineId;
            document.getElementById('stock_medicine_name').value = medicineName;
            document.getElementById('stock_quantity').value = currentStock;
            openModal('updateStockModal');
        }
    </script>
    
    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>
