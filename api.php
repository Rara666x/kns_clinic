<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include('db_connection.php');

// Check database connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Get the endpoint from query string
$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Get JSON input for POST/PUT requests
$input = json_decode(file_get_contents('php://input'), true);

// Enable error logging for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Route to appropriate handler
switch ($endpoint) {
    case 'patients':
        handlePatients($conn, $method, $input, $_GET);
        break;
    case 'medicines':
        handleMedicines($conn, $method, $input, $_GET);
        break;
    case 'equipments':
        handleEquipments($conn, $method, $input, $_GET);
        break;
    case 'records':
        handleRecords($conn, $method, $input, $_GET);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

// =====================================================
// PATIENTS HANDLER
// =====================================================
function handlePatients($conn, $method, $input, $query) {
    switch ($method) {
        case 'GET':
            // Get all patients
            $result = $conn->query("SELECT * FROM patients ORDER BY created_at DESC");
            $patients = [];
            while ($row = $result->fetch_assoc()) {
                $patients[] = $row;
            }
            echo json_encode($patients);
            break;
            
        case 'POST':
            // Add new patient
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }
            
            $studentId = $input['student_id'] ?? '';
            $firstName = $input['first_name'] ?? '';
            $lastName = $input['last_name'] ?? '';
            $email = $input['email'] ?? '';
            $phone = $input['phone'] ?? '';
            $dateOfBirth = $input['date_of_birth'] ?? null;
            $address = $input['address'] ?? '';
            $medicalHistory = $input['medical_history'] ?? '';
            $yearLevel = $input['year_level'] ?? null;
            $course = $input['course'] ?? null;
            $photoPath = $input['photo_path'] ?? null;
            
            if (!$studentId || !$firstName || !$lastName) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields: student_id, first_name, last_name']);
                return;
            }
            
            // Generate email if not provided
            if (empty($email)) {
                $email = strtolower($firstName . '.' . $lastName . '@student.kns.edu');
            }
            
            $stmt = $conn->prepare("INSERT INTO patients (student_id, first_name, last_name, email, phone, date_of_birth, address, medical_history, year_level, course, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssssss", $studentId, $firstName, $lastName, $email, $phone, $dateOfBirth, $address, $medicalHistory, $yearLevel, $course, $photoPath);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Patient added successfully']);
            } else {
                http_response_code(500);
                $errorMsg = 'Failed to add patient: ' . $conn->error;
                error_log("API Error: " . $errorMsg);
                echo json_encode(['error' => $errorMsg]);
            }
            $stmt->close();
            break;
            
        case 'DELETE':
            // Archive patient (soft delete - move to archived_patients)
            $id = $query['id'] ?? '';
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                echo json_encode(['error' => 'Valid Patient ID required']);
                return;
            }
            
            // Get patient data (using prepared statement)
            $stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Patient not found']);
                $stmt->close();
                return;
            }
            
            $patient = $result->fetch_assoc();
            $stmt->close();
            
            // Insert into archived_patients
            $stmt = $conn->prepare("INSERT INTO archived_patients (original_id, student_id, first_name, last_name, email, phone, date_of_birth, address, medical_history, year_level, course, photo_path, created_at, archive_reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $archiveReason = 'Archived via API';
            $stmt->bind_param("issssssssssss", $patient['id'], $patient['student_id'], $patient['first_name'], $patient['last_name'], $patient['email'], $patient['phone'], $patient['date_of_birth'], $patient['address'], $patient['medical_history'], $patient['year_level'], $patient['course'], $patient['photo_path'], $patient['created_at'], $archiveReason);
            $stmt->execute();
            $stmt->close();
            
            // Delete from patients table (using prepared statement)
            $stmt = $conn->prepare("DELETE FROM patients WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Patient archived successfully']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

// =====================================================
// MEDICINES HANDLER
// =====================================================
function handleMedicines($conn, $method, $input, $query) {
    switch ($method) {
        case 'GET':
            // Get all medicines
            $result = $conn->query("SELECT * FROM medicines WHERE is_active = 1 ORDER BY medicine_name ASC");
            $medicines = [];
            while ($row = $result->fetch_assoc()) {
                $medicines[] = $row;
            }
            echo json_encode($medicines);
            break;
            
        case 'POST':
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }
            
            // Check if it's an update_stock action
            if (isset($input['action']) && $input['action'] === 'update_stock') {
                $id = $input['id'] ?? 0;
                $stock = $input['stock'] ?? 0;
                
                if (!$id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Medicine ID required']);
                    return;
                }
                
                // Get current stock (using prepared statement)
                $stmt = $conn->prepare("SELECT current_stock FROM medicines WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Medicine not found']);
                    $stmt->close();
                    return;
                }
                
                $currentStock = $result->fetch_assoc()['current_stock'];
                $stmt->close();
                
                // Update stock
                $stmt = $conn->prepare("UPDATE medicines SET current_stock = ? WHERE id = ?");
                $stmt->bind_param("ii", $stock, $id);
                $stmt->execute();
                $stmt->close();
                
                // Log movement
                $movementType = $stock > $currentStock ? 'in' : ($stock < $currentStock ? 'out' : 'adjustment');
                $quantity = abs($stock - $currentStock);
                if ($quantity > 0) {
                    $stmt = $conn->prepare("INSERT INTO medicine_stock_movements (medicine_id, movement_type, quantity, reason, notes) VALUES (?, ?, ?, ?, ?)");
                    $reason = 'Stock updated via API';
                    $notes = "Updated from $currentStock to $stock";
                    $stmt->bind_param("iiiss", $id, $movementType, $quantity, $reason, $notes);
                    $stmt->execute();
                    $stmt->close();
                }
                
                echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
            } else {
                // Add new medicine
                $medicineName = $input['medicine_name'] ?? '';
                $genericName = $input['generic_name'] ?? '';
                $medicineType = $input['medicine_type'] ?? '';
                $dosageForm = $input['dosage_form'] ?? '';
                $strength = $input['strength'] ?? '';
                $manufacturer = $input['manufacturer'] ?? '';
                $batchNumber = $input['batch_number'] ?? '';
                $currentStock = $input['current_stock'] ?? 0;
                $minimumStock = $input['minimum_stock'] ?? 10;
                $maximumStock = $input['maximum_stock'] ?? 1000;
                $expiryDate = $input['expiry_date'] ?? null;
                $supplier = $input['supplier'] ?? '';
                $storageLocation = $input['storage_location'] ?? '';
                $description = $input['description'] ?? '';
                
                if (!$medicineName || !$medicineType || !$dosageForm) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    return;
                }
                
                $stmt = $conn->prepare("INSERT INTO medicines (medicine_name, generic_name, medicine_type, dosage_form, strength, manufacturer, batch_number, current_stock, minimum_stock, maximum_stock, expiry_date, supplier, storage_location, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssiiissss", $medicineName, $genericName, $medicineType, $dosageForm, $strength, $manufacturer, $batchNumber, $currentStock, $minimumStock, $maximumStock, $expiryDate, $supplier, $storageLocation, $description);
                
                if ($stmt->execute()) {
                    $newId = $conn->insert_id;
                    echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Medicine added successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to add medicine: ' . $conn->error]);
                }
                $stmt->close();
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

// =====================================================
// EQUIPMENTS HANDLER
// =====================================================
function handleEquipments($conn, $method, $input, $query) {
    switch ($method) {
        case 'GET':
            // Get all equipment
            $result = $conn->query("SELECT * FROM medical_equipment WHERE is_active = 1 ORDER BY equipment_name ASC");
            $equipments = [];
            while ($row = $result->fetch_assoc()) {
                $equipments[] = $row;
            }
            echo json_encode($equipments);
            break;
            
        case 'POST':
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }
            
            // Check if it's an update_status action
            if (isset($input['action']) && $input['action'] === 'update_status') {
                $id = $input['id'] ?? 0;
                $status = $input['status'] ?? '';
                
                if (!$id || !$status) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Equipment ID and status required']);
                    return;
                }
                
                $stmt = $conn->prepare("UPDATE medical_equipment SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'Equipment status updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update equipment: ' . $conn->error]);
                }
                $stmt->close();
            } else {
                // Add new equipment
                $equipmentName = $input['equipment_name'] ?? '';
                $equipmentType = $input['equipment_type'] ?? '';
                $modelNumber = $input['model_number'] ?? '';
                $serialNumber = $input['serial_number'] ?? '';
                $manufacturer = $input['manufacturer'] ?? '';
                $purchaseDate = $input['purchase_date'] ?? null;
                $warrantyExpiry = $input['warranty_expiry'] ?? null;
                $maintenanceDue = $input['maintenance_due'] ?? null;
                $status = $input['status'] ?? 'operational';
                $location = $input['location'] ?? '';
                $assignedTo = $input['assigned_to'] ?? '';
                $supplier = $input['supplier'] ?? '';
                $description = $input['description'] ?? '';
                
                if (!$equipmentName || !$equipmentType) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Missing required fields']);
                    return;
                }
                
                $stmt = $conn->prepare("INSERT INTO medical_equipment (equipment_name, equipment_type, model_number, serial_number, manufacturer, purchase_date, warranty_expiry, maintenance_due, status, location, assigned_to, supplier, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssss", $equipmentName, $equipmentType, $modelNumber, $serialNumber, $manufacturer, $purchaseDate, $warrantyExpiry, $maintenanceDue, $status, $location, $assignedTo, $supplier, $description);
                
                if ($stmt->execute()) {
                    $newId = $conn->insert_id;
                    echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Equipment added successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to add equipment: ' . $conn->error]);
                }
                $stmt->close();
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

// =====================================================
// MEDICAL RECORDS HANDLER
// =====================================================
function handleRecords($conn, $method, $input, $query) {
    switch ($method) {
        case 'GET':
            // Get all medical records
            $result = $conn->query("SELECT * FROM medical_records ORDER BY record_date DESC, created_at DESC");
            $records = [];
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            echo json_encode($records);
            break;
            
        case 'POST':
            // Add new medical record
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid input']);
                return;
            }
            
            $patientId = $input['patient_id'] ?? 0;
            $recordType = $input['record_type'] ?? '';
            $title = $input['title'] ?? '';
            $description = $input['description'] ?? '';
            $diagnosis = $input['diagnosis'] ?? '';
            $treatment = $input['treatment'] ?? '';
            $medications = $input['medications'] ?? '';
            $recordDate = $input['record_date'] ?? '';
            
            if (!$patientId || !$recordType || !$title || !$recordDate) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            $stmt = $conn->prepare("INSERT INTO medical_records (patient_id, record_type, title, description, diagnosis, treatment, medications, record_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $patientId, $recordType, $title, $description, $diagnosis, $treatment, $medications, $recordDate);
            
            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Medical record added successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to add medical record: ' . $conn->error]);
            }
            $stmt->close();
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

$conn->close();
?>

