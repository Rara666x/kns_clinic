-- =====================================================
-- CLINICAL SYSTEM DATABASE - COMPLETE SETUP
-- =====================================================
-- This script creates all tables and sample data for the Clinical System
-- Run this script after reformatting your PC to restore the database
-- 
-- Features included:
-- - User management system
-- - Patient management with student information
-- - Appointment scheduling
-- - Medical records management
-- - Medicine inventory management
-- - Medical equipment management
-- - School activities and events
-- - Archive system
-- - Activity logging
-- =====================================================

-- Drop existing tables if they exist (in reverse order to handle foreign keys)
DROP TABLE IF EXISTS event_medical_attendance;
DROP TABLE IF EXISTS event_medical_incidents;
DROP TABLE IF EXISTS school_events;
DROP TABLE IF EXISTS school_activities;
DROP TABLE IF EXISTS equipment_maintenance_log;
DROP TABLE IF EXISTS medicine_stock_movements;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS archive_log;
DROP TABLE IF EXISTS archived_medical_records;
DROP TABLE IF EXISTS archived_appointments;
DROP TABLE IF EXISTS archived_patients;
DROP TABLE IF EXISTS medical_equipment;
DROP TABLE IF EXISTS medicines;
DROP TABLE IF EXISTS medical_records;
DROP TABLE IF EXISTS appointments;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS users;

-- =====================================================
-- 1. USERS TABLE (Create first - no dependencies)
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullName VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'assistant') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 2. PATIENTS TABLE (Student-focused)
-- =====================================================
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    date_of_birth DATE,
    address TEXT,
    medical_history TEXT,
    year_level ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NULL,
    course ENUM('BSCS', 'BSED', 'BEED', 'BSBA', 'BSHM') NULL,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 3. APPOINTMENTS TABLE
-- =====================================================
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_type VARCHAR(50) NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- =====================================================
-- 4. MEDICAL RECORDS TABLE
-- =====================================================
CREATE TABLE medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    record_type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    diagnosis TEXT,
    treatment TEXT,
    medications TEXT,
    record_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- =====================================================
-- 5. MEDICINES TABLE
-- =====================================================
CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(200) NOT NULL,
    generic_name VARCHAR(200),
    medicine_type VARCHAR(100) NOT NULL,
    dosage_form VARCHAR(50) NOT NULL,
    strength VARCHAR(100),
    manufacturer VARCHAR(200),
    batch_number VARCHAR(100),
    current_stock INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 10,
    maximum_stock INT NOT NULL DEFAULT 1000,
    expiry_date DATE,
    supplier VARCHAR(200),
    storage_location VARCHAR(100),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 6. MEDICAL EQUIPMENT TABLE
-- =====================================================
CREATE TABLE medical_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(200) NOT NULL,
    equipment_type VARCHAR(100) NOT NULL,
    model_number VARCHAR(100),
    serial_number VARCHAR(100),
    manufacturer VARCHAR(200),
    purchase_date DATE,
    warranty_expiry DATE,
    maintenance_due DATE,
    status ENUM('operational', 'maintenance', 'out_of_order', 'retired') DEFAULT 'operational',
    location VARCHAR(100),
    assigned_to VARCHAR(200),
    supplier VARCHAR(200),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 7. MEDICINE STOCK MOVEMENTS TABLE
-- =====================================================
CREATE TABLE medicine_stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reason VARCHAR(255),
    reference_number VARCHAR(100),
    performed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- =====================================================
-- 8. EQUIPMENT MAINTENANCE LOG TABLE
-- =====================================================
CREATE TABLE equipment_maintenance_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    maintenance_type ENUM('routine', 'repair', 'calibration', 'inspection') NOT NULL,
    maintenance_date DATE NOT NULL,
    next_maintenance_date DATE,
    technician VARCHAR(200),
    description TEXT,
    status ENUM('completed', 'pending', 'cancelled') DEFAULT 'completed',
    performed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES medical_equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id)
);

-- =====================================================
-- 9. SCHOOL ACTIVITIES TABLE
-- =====================================================
CREATE TABLE school_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_name VARCHAR(255) NOT NULL,
    activity_type ENUM('Academic', 'Sports', 'Cultural', 'Community Service', 'Workshop', 'Training', 'Other') NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE,
    start_time TIME NOT NULL,
    end_time TIME,
    location VARCHAR(255),
    organizer VARCHAR(255),
    max_participants INT DEFAULT 0,
    status ENUM('planned', 'confirmed', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
    notes TEXT,
    medical_team_assigned BOOLEAN DEFAULT FALSE,
    medical_team_notes TEXT,
    medical_equipment_needed TEXT,
    first_aid_station VARCHAR(255),
    emergency_contact VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 10. SCHOOL EVENTS TABLE
-- =====================================================
CREATE TABLE school_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_name VARCHAR(255) NOT NULL,
    event_type ENUM('Ceremony', 'Festival', 'Competition', 'Exhibition', 'Conference', 'Fundraiser', 'Social', 'Other') NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    location VARCHAR(255),
    organizer VARCHAR(255),
    max_participants INT DEFAULT 0,
    status ENUM('planned', 'confirmed', 'completed', 'cancelled') DEFAULT 'planned',
    notes TEXT,
    medical_team_assigned BOOLEAN DEFAULT FALSE,
    medical_team_notes TEXT,
    medical_equipment_needed TEXT,
    first_aid_station VARCHAR(255),
    emergency_contact VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 11. EVENT MEDICAL INCIDENTS TABLE
-- =====================================================
CREATE TABLE event_medical_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT,
    event_type ENUM('activity', 'event') NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME NOT NULL,
    patient_name VARCHAR(255),
    patient_id INT,
    incident_type ENUM('injury', 'illness', 'allergic_reaction', 'heat_exhaustion', 'dehydration', 'other') NOT NULL,
    severity ENUM('minor', 'moderate', 'severe', 'critical') NOT NULL,
    description TEXT,
    treatment_provided TEXT,
    medication_given TEXT,
    medical_staff_present VARCHAR(255),
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL
);

-- =====================================================
-- 12. EVENT MEDICAL ATTENDANCE TABLE
-- =====================================================
CREATE TABLE event_medical_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT,
    event_type ENUM('activity', 'event') NOT NULL,
    medical_staff_id INT,
    staff_name VARCHAR(255),
    role ENUM('doctor', 'nurse', 'paramedic', 'first_aid', 'medical_supervisor') NOT NULL,
    shift_start TIME,
    shift_end TIME,
    equipment_assigned TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medical_staff_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 13. ARCHIVE TABLES
-- =====================================================

-- Archived Patients
CREATE TABLE archived_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    student_id VARCHAR(20) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    address TEXT,
    medical_history TEXT,
    year_level ENUM('1st Year', '2nd Year', '3rd Year', '4th Year') NULL,
    course ENUM('BSCS', 'BSED', 'BEED', 'BSBA', 'BSHM') NULL,
    photo_path VARCHAR(255) NULL,
    created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    archive_reason VARCHAR(255),
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Archived Appointments
CREATE TABLE archived_appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    patient_id INT,
    patient_name VARCHAR(200),
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    appointment_type VARCHAR(50) NOT NULL,
    status ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    archive_reason VARCHAR(255),
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Archived Medical Records
CREATE TABLE archived_medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    patient_id INT,
    patient_name VARCHAR(200),
    record_type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    diagnosis TEXT,
    treatment TEXT,
    medications TEXT,
    record_date DATE NOT NULL,
    created_at TIMESTAMP,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by INT,
    archive_reason VARCHAR(255),
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 14. ARCHIVE LOG TABLE
-- =====================================================
CREATE TABLE archive_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('archive', 'restore', 'permanent_delete') NOT NULL,
    archived_by INT,
    reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 15. ACTIVITY LOGS TABLE
-- =====================================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(255),
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- =====================================================
CREATE INDEX idx_patients_email ON patients(email);
CREATE INDEX idx_patients_student_id ON patients(student_id);
CREATE INDEX idx_appointments_patient_id ON appointments(patient_id);
CREATE INDEX idx_appointments_date ON appointments(appointment_date);
CREATE INDEX idx_medical_records_patient_id ON medical_records(patient_id);
CREATE INDEX idx_medical_records_date ON medical_records(record_date);
CREATE INDEX idx_medicines_name ON medicines(medicine_name);
CREATE INDEX idx_medicines_type ON medicines(medicine_type);
CREATE INDEX idx_medicines_expiry ON medicines(expiry_date);
CREATE INDEX idx_equipment_name ON medical_equipment(equipment_name);
CREATE INDEX idx_equipment_type ON medical_equipment(equipment_type);
CREATE INDEX idx_equipment_status ON medical_equipment(status);
CREATE INDEX idx_archived_patients_original_id ON archived_patients(original_id);
CREATE INDEX idx_archived_appointments_original_id ON archived_appointments(original_id);
CREATE INDEX idx_archived_medical_records_original_id ON archived_medical_records(original_id);
CREATE INDEX idx_archive_log_table_record ON archive_log(table_name, record_id);
CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

-- =====================================================
-- INSERT SAMPLE DATA
-- =====================================================

-- Insert sample users
INSERT INTO users (username, password, fullName, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@clinical.com', 'admin'),
('assistant1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Assistant', 'jane@clinical.com', 'assistant'),
('assistant2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Assistant', 'bob@clinical.com', 'assistant');

-- Insert sample patients (students)
INSERT INTO patients (student_id, first_name, last_name, email, phone, date_of_birth, address, medical_history, year_level, course) VALUES
('2024-001', 'John', 'Doe', 'john.doe@email.com', '555-0123', '2000-03-15', '123 Main St, Anytown, USA', 'No known allergies. Previous appendectomy in 2010.', '3rd Year', 'BSCS'),
('2024-002', 'Jane', 'Smith', 'jane.smith@email.com', '555-0456', '2001-07-22', '456 Oak Ave, Somewhere, USA', 'Allergic to penicillin. Diabetes type 2.', '2nd Year', 'BSED'),
('2024-003', 'Michael', 'Johnson', 'michael.j@email.com', '555-0789', '1999-11-08', '789 Pine Rd, Elsewhere, USA', 'Hypertension. Regular medication.', '4th Year', 'BSBA'),
('2024-004', 'Sarah', 'Williams', 'sarah.w@email.com', '555-0321', '2002-05-14', '321 Elm St, Nowhere, USA', 'No significant medical history.', '1st Year', 'BEED'),
('2024-005', 'Emily', 'Brown', 'emily.brown@email.com', '555-0654', '2000-12-03', '654 Maple Dr, Anywhere, USA', 'Seasonal allergies. Regular checkups.', '3rd Year', 'BSHM'),
('2024-006', 'David', 'Wilson', 'david.wilson@email.com', '555-0987', '1999-06-18', '987 Cedar Ln, Somewhere, USA', 'Previous knee surgery. Active lifestyle.', '4th Year', 'BSCS');

-- Insert sample appointments
INSERT INTO appointments (patient_id, appointment_date, appointment_time, appointment_type, status, notes) VALUES
(1, '2024-01-15', '09:00:00', 'Consultation', 'scheduled', 'Regular checkup'),
(2, '2024-01-16', '14:30:00', 'Follow-up', 'scheduled', 'Diabetes management'),
(3, '2024-01-17', '10:15:00', 'Consultation', 'completed', 'Blood pressure check'),
(4, '2024-01-18', '11:00:00', 'Consultation', 'scheduled', 'New student consultation'),
(5, '2024-01-19', '15:30:00', 'Follow-up', 'scheduled', 'Allergy follow-up'),
(6, '2024-01-20', '08:45:00', 'Check-up', 'completed', 'Post-surgery checkup');

-- Insert sample medical records
INSERT INTO medical_records (patient_id, record_type, title, description, diagnosis, treatment, medications, record_date) VALUES
(1, 'Consultation', 'Annual Physical Exam', 'Complete physical examination including vital signs, heart, lungs, and abdomen.', 'Healthy', 'Continue current lifestyle, annual follow-up recommended', 'None', '2024-01-10'),
(2, 'Consultation', 'Diabetes Management', 'Regular diabetes checkup and blood sugar monitoring.', 'Type 2 Diabetes', 'Continue metformin, dietary modifications, regular exercise', 'Metformin 500mg twice daily', '2024-01-12'),
(3, 'Consultation', 'Hypertension Follow-up', 'Blood pressure monitoring and medication adjustment.', 'Hypertension', 'Lifestyle modifications, medication adjustment', 'Lisinopril 10mg daily', '2024-01-14'),
(4, 'Consultation', 'Initial Consultation', 'New student intake and initial assessment.', 'General health assessment', 'Baseline established, follow-up in 3 months', 'None', '2024-01-15'),
(5, 'Consultation', 'Allergy Assessment', 'Comprehensive allergy testing and evaluation.', 'Seasonal Allergies', 'Avoidance strategies, antihistamines as needed', 'Loratadine 10mg daily during allergy season', '2024-01-16'),
(6, 'Consultation', 'Post-Surgical Follow-up', 'Evaluation of knee surgery recovery progress.', 'Post-surgical recovery', 'Physical therapy, gradual return to activities', 'Ibuprofen 400mg as needed for pain', '2024-01-17');

-- Insert sample medicines
INSERT INTO medicines (medicine_name, generic_name, medicine_type, dosage_form, strength, manufacturer, batch_number, current_stock, minimum_stock, maximum_stock, expiry_date, supplier, storage_location, description) VALUES
('Paracetamol 500mg', 'Acetaminophen', 'Analgesic', 'Tablet', '500mg', 'PharmaCorp', 'PC2024001', 150, 20, 500, '2025-12-31', 'MedSupply Ltd', 'Shelf A1', 'Pain relief and fever reducer'),
('Amoxicillin 250mg', 'Amoxicillin', 'Antibiotic', 'Capsule', '250mg', 'AntibioPharm', 'AB2024002', 75, 15, 300, '2025-08-15', 'PharmaDist', 'Shelf B2', 'Broad spectrum antibiotic'),
('Ibuprofen 400mg', 'Ibuprofen', 'NSAID', 'Tablet', '400mg', 'PainRelief Inc', 'PR2024003', 200, 25, 400, '2026-03-20', 'MedSupply Ltd', 'Shelf A3', 'Anti-inflammatory and pain relief'),
('Insulin Glargine', 'Insulin Glargine', 'Hormone', 'Vial', '100 units/ml', 'DiabCare', 'DC2024004', 12, 5, 50, '2024-06-30', 'Specialty Meds', 'Refrigerator', 'Long-acting insulin'),
('Salbutamol Inhaler', 'Salbutamol', 'Bronchodilator', 'Inhaler', '100mcg', 'RespiraCorp', 'RC2024005', 8, 3, 30, '2025-11-10', 'RespSupply', 'Shelf C1', 'Bronchodilator for asthma'),
('Metformin 500mg', 'Metformin', 'Antidiabetic', 'Tablet', '500mg', 'DiabCare', 'DC2024006', 120, 20, 400, '2025-09-25', 'PharmaDist', 'Shelf B1', 'Type 2 diabetes medication'),
('Aspirin 75mg', 'Acetylsalicylic Acid', 'Antiplatelet', 'Tablet', '75mg', 'CardioPharm', 'CP2024007', 300, 50, 600, '2026-01-15', 'MedSupply Ltd', 'Shelf A2', 'Low dose aspirin for heart protection'),
('Lisinopril 10mg', 'Lisinopril', 'ACE Inhibitor', 'Tablet', '10mg', 'CardioPharm', 'CP2024008', 90, 15, 250, '2025-07-18', 'PharmaDist', 'Shelf B3', 'Blood pressure medication');

-- Insert sample medical equipment
INSERT INTO medical_equipment (equipment_name, equipment_type, model_number, serial_number, manufacturer, purchase_date, warranty_expiry, maintenance_due, status, location, assigned_to, supplier, description) VALUES
('Digital Blood Pressure Monitor', 'Diagnostic', 'BP-2000', 'BP2000-001', 'MedTech Solutions', '2023-01-15', '2026-01-15', '2024-07-15', 'operational', 'Consultation Room 1', 'Dr. Sarah Johnson', 'MedEquip Ltd', 'Automatic digital blood pressure monitor'),
('Stethoscope', 'Diagnostic', 'ST-500', 'ST500-045', 'CardioCare', '2023-03-20', '2025-03-20', '2024-09-20', 'operational', 'Consultation Room 1', 'Dr. Sarah Johnson', 'MedEquip Ltd', 'High-quality acoustic stethoscope'),
('Digital Thermometer', 'Diagnostic', 'TEMP-100', 'TEMP100-078', 'TempTech', '2023-02-10', '2025-02-10', '2024-08-10', 'operational', 'Consultation Room 2', 'Dr. Michael Brown', 'MedEquip Ltd', 'Infrared digital thermometer'),
('Pulse Oximeter', 'Monitoring', 'OXI-300', 'OXI300-012', 'OxyMed', '2023-04-05', '2026-04-05', '2024-10-05', 'operational', 'Consultation Room 2', 'Dr. Michael Brown', 'MedEquip Ltd', 'Finger pulse oximeter'),
('ECG Machine', 'Diagnostic', 'ECG-5000', 'ECG5000-003', 'CardioTech', '2022-11-30', '2025-11-30', '2024-05-30', 'operational', 'Diagnostic Room', 'Dr. Emily Davis', 'MedEquip Ltd', '12-lead ECG machine'),
('Autoclave', 'Sterilization', 'AUTO-200', 'AUTO200-007', 'SterilCorp', '2022-08-15', '2025-08-15', '2024-02-15', 'operational', 'Sterilization Room', 'Nurse Jane', 'MedEquip Ltd', 'Steam sterilizer for instruments'),
('Defibrillator', 'Emergency', 'DEF-1000', 'DEF1000-002', 'LifeSave', '2023-06-20', '2026-06-20', '2024-12-20', 'operational', 'Emergency Room', 'Emergency Team', 'MedEquip Ltd', 'Automated external defibrillator'),
('X-Ray Machine', 'Imaging', 'XR-3000', 'XR3000-001', 'ImagingTech', '2021-12-10', '2024-12-10', '2024-06-10', 'maintenance', 'X-Ray Room', 'Radiology Tech', 'MedEquip Ltd', 'Digital X-ray imaging system');

-- Insert sample school activities
INSERT INTO school_activities (activity_name, activity_type, description, start_date, end_date, start_time, end_time, location, organizer, max_participants, status, notes, medical_team_assigned, medical_team_notes, medical_equipment_needed, first_aid_station, emergency_contact) VALUES
('Science Fair 2024', 'Academic', 'Annual science fair showcasing student projects and experiments', '2024-03-15', '2024-03-15', '09:00:00', '15:00:00', 'Main Auditorium', 'Science Department', 200, 'confirmed', 'Registration required', TRUE, 'Medical team on standby for chemical exposure incidents', 'First aid kit, eye wash station, emergency contact list', 'Main Auditorium - Stage Area', 'Emergency: 911, School Nurse: (555) 123-4567'),
('Basketball Tournament', 'Sports', 'Inter-class basketball competition', '2024-03-20', '2024-03-22', '14:00:00', '17:00:00', 'School Gymnasium', 'Sports Committee', 50, 'planned', 'Teams of 5 players each', TRUE, 'Sports medicine team required for injury management', 'Ice packs, bandages, stretcher, AED', 'Gymnasium - Side Entrance', 'Emergency: 911, Sports Medicine: (555) 234-5678'),
('Cultural Dance Workshop', 'Cultural', 'Traditional dance workshop for students', '2024-03-25', '2024-03-25', '10:00:00', '12:00:00', 'Dance Studio', 'Cultural Club', 30, 'confirmed', 'Bring comfortable clothes', TRUE, 'Basic first aid for dance-related injuries', 'First aid kit, ice packs', 'Dance Studio - Main Floor', 'Emergency: 911, School Nurse: (555) 123-4567'),
('Community Cleanup Drive', 'Community Service', 'Environmental awareness and cleanup activity', '2024-04-01', '2024-04-01', '08:00:00', '12:00:00', 'Local Park', 'Environmental Club', 100, 'planned', 'Gloves and bags provided', TRUE, 'Medical supervision for outdoor activity', 'First aid kit, sunscreen, water station', 'Local Park - Main Pavilion', 'Emergency: 911, Park Ranger: (555) 345-6789'),
('Coding Bootcamp', 'Workshop', 'Introduction to programming for beginners', '2024-04-05', '2024-04-07', '09:00:00', '16:00:00', 'Computer Lab', 'IT Department', 25, 'confirmed', 'Laptops provided', TRUE, 'Eye strain and ergonomic injury prevention', 'Eye drops, ergonomic supports', 'Computer Lab - Front Desk', 'Emergency: 911, School Nurse: (555) 123-4567');

-- Insert sample school events
INSERT INTO school_events (event_name, event_type, description, event_date, start_time, end_time, location, organizer, max_participants, status, notes, medical_team_assigned, medical_team_notes, medical_equipment_needed, first_aid_station, emergency_contact) VALUES
('Annual Sports Day', 'Ceremony', 'School sports day with various competitions and awards', '2024-03-10', '08:00:00', '17:00:00', 'School Ground', 'Sports Department', 500, 'completed', 'All students participated', TRUE, 'Full medical team deployed for sports injuries', 'AED, stretchers, ice packs, bandages, medical tent', 'School Ground - Medical Tent', 'Emergency: 911, Sports Medicine: (555) 234-5678'),
('Spring Festival', 'Festival', 'Cultural festival celebrating spring season', '2024-03-18', '10:00:00', '18:00:00', 'School Campus', 'Cultural Committee', 300, 'confirmed', 'Food stalls and performances', TRUE, 'Medical supervision for food allergies and heat exposure', 'First aid kit, epinephrine pens, water stations', 'School Campus - Main Stage', 'Emergency: 911, School Nurse: (555) 123-4567'),
('Math Olympiad', 'Competition', 'Mathematics competition for all grades', '2024-03-28', '09:00:00', '12:00:00', 'Main Hall', 'Mathematics Department', 150, 'planned', 'Registration deadline: March 20', TRUE, 'Basic medical support for stress-related incidents', 'First aid kit, stress relief items', 'Main Hall - Front Desk', 'Emergency: 911, School Nurse: (555) 123-4567'),
('Art Exhibition', 'Exhibition', 'Student artwork display and competition', '2024-04-02', '14:00:00', '18:00:00', 'Art Gallery', 'Art Department', 200, 'confirmed', 'Awards ceremony at 5 PM', TRUE, 'Medical support for art material exposure', 'First aid kit, eye wash station', 'Art Gallery - Main Entrance', 'Emergency: 911, School Nurse: (555) 123-4567'),
('Parent-Teacher Conference', 'Conference', 'Semi-annual parent-teacher meeting', '2024-04-08', '09:00:00', '15:00:00', 'Various Classrooms', 'Administration', 400, 'planned', 'Appointment scheduling available', TRUE, 'Medical team on standby for parent health issues', 'First aid kit, emergency contact list', 'Main Office - Reception', 'Emergency: 911, School Nurse: (555) 123-4567'),
('Charity Fundraiser', 'Fundraiser', 'Fundraising event for local orphanage', '2024-04-12', '16:00:00', '20:00:00', 'School Auditorium', 'Student Council', 250, 'confirmed', 'Tickets: $10 per person', TRUE, 'Medical support for large gathering', 'First aid kit, emergency exits marked', 'School Auditorium - Stage Area', 'Emergency: 911, School Nurse: (555) 123-4567'),
('Graduation Ceremony', 'Ceremony', 'Class of 2024 graduation ceremony', '2024-05-15', '18:00:00', '21:00:00', 'Main Auditorium', 'Graduation Committee', 600, 'planned', 'Formal attire required', TRUE, 'Full medical team for large formal event', 'AED, first aid kit, emergency protocols', 'Main Auditorium - Back Stage', 'Emergency: 911, School Nurse: (555) 123-4567');

-- =====================================================
-- DISPLAY SUCCESS MESSAGE
-- =====================================================
SELECT 'Clinical System Database Setup Complete!' as Status,
       'All tables created successfully with comprehensive sample data' as Message,
       'Ready for use after PC reformat' as Note;

-- =====================================================
-- DATABASE STRUCTURE SUMMARY
-- =====================================================
-- 
-- Core Tables:
-- 1. users - User management (admin/assistant roles)
-- 2. patients - Student patient records with academic info
-- 3. appointments - Appointment scheduling
-- 4. medical_records - Medical history and consultations
-- 5. medicines - Medicine inventory management
-- 6. medical_equipment - Equipment tracking and maintenance
-- 7. medicine_stock_movements - Stock tracking
-- 8. equipment_maintenance_log - Maintenance records
-- 9. school_activities - School activity management
-- 10. school_events - School event management
-- 11. event_medical_incidents - Medical incidents during events
-- 12. event_medical_attendance - Medical staff attendance
-- 
-- Archive System:
-- 13. archived_patients - Archived patient records
-- 14. archived_appointments - Archived appointments
-- 15. archived_medical_records - Archived medical records
-- 16. archive_log - Archive activity tracking
-- 
-- Logging:
-- 17. activity_logs - System activity tracking
-- 
-- Features:
-- - Complete student-focused patient management
-- - Comprehensive appointment scheduling
-- - Medical records with diagnosis and treatment
-- - Medicine inventory with stock tracking
-- - Medical equipment management
-- - School activities and events with medical support
-- - Archive system for data retention
-- - Activity logging for audit trails
-- - Optimized with proper indexes
-- 
-- Default Login Credentials:
-- Admin: admin / password
-- Assistant: assistant1 / password
-- Assistant: assistant2 / password
-- 
-- =====================================================
