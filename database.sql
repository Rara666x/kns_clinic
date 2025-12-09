-- =====================================================
-- KNS CLINICAL SYSTEM DATABASE - FRESH SETUP
-- =====================================================
-- This script creates all tables for the KNS Clinical System
-- Database: kns_clinic
-- Run this script in phpMyAdmin to set up a fresh database
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

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS kns_clinic;
USE kns_clinic;

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
-- DATABASE SETUP COMPLETE
-- =====================================================
-- 
-- All tables have been created successfully.
-- The database is ready for use with empty tables.
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
-- =====================================================
