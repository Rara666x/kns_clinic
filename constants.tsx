import { 
  LayoutDashboard, 
  Users, 
  FileText, 
  ScrollText, 
  Package, 
  BarChart2, 
  Archive,
  History,
  ArrowRightLeft
} from 'lucide-react';
import { NavItem, AlertData, ActivityLog, Patient, MedicalRecord, Medicine, Equipment } from './types';

export const NAV_ITEMS: NavItem[] = [
  { 
    id: 'dashboard', 
    label: 'Dashboard', 
    description: 'Main dashboard with overview and quick access.', 
    icon: LayoutDashboard,
  },
  { 
    id: 'patients', 
    label: 'Patients', 
    description: 'Register, view, and manage patient profiles.', 
    icon: Users 
  },
  { 
    id: 'records', 
    label: 'Medical Records', 
    description: 'Access treatment history and clinical notes.', 
    icon: FileText 
  },
  { 
    id: 'certificates', 
    label: 'Medical Certificate', 
    description: 'Create, edit, and print patient certificates.', 
    icon: ScrollText 
  },
  { 
    id: 'transfer', 
    label: 'Transfer Patient', 
    description: 'Manage patient transfers and referrals.', 
    icon: ArrowRightLeft 
  },
  { 
    id: 'inventory', 
    label: 'Inventory', 
    description: 'Manage medicines, equipment, and stock levels.', 
    icon: Package 
  },
  { 
    id: 'reports', 
    label: 'Reports', 
    description: 'Operational and clinical reporting.', 
    icon: BarChart2 
  },
  { 
    id: 'audit-trail', 
    label: 'Audit Trail', 
    description: 'Track system activities and user logs.', 
    icon: History 
  },
  { 
    id: 'archive', 
    label: 'Archive', 
    description: 'Manage archived records and data.', 
    icon: Archive 
  },
];

export const ALERTS: AlertData[] = [
  {
    id: '1',
    type: 'danger',
    message: '5 medicine(s) have expired and need immediate attention'
  },
  {
    id: '2',
    type: 'warning',
    message: '1 medicine(s) are expiring within 30 days'
  },
  {
    id: '3',
    type: 'info',
    message: '8 equipment item(s) need maintenance'
  }
];

export const ACTIVITIES: ActivityLog[] = [
  {
    id: '1',
    user: 'admin@clinical.com',
    action: 'User logged in successfully',
    time: 'Just now'
  }
];

// Helper to generate dynamic dates relative to today
const getRelativeDate = (daysOffset: number) => {
  const date = new Date();
  date.setDate(date.getDate() - daysOffset);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

export const MOCK_PATIENTS: Patient[] = [
  { 
    id: '1', 
    studentId: '2024-001', 
    firstName: 'John', 
    lastName: 'Doe', 
    age: 25, 
    yearLevel: '3rd Year', 
    course: 'BSCS', 
    // Registered today (Current Month Data)
    registrationDate: getRelativeDate(0),
    email: 'john.doe@email.com',
    phone: '555-0123',
    dateOfBirth: 'March 15, 2000',
    address: '123 Main St, Anytown, USA',
    medicalHistory: 'No known allergies. Previous appendectomy in 2010.'
  },
  { 
    id: '2', 
    studentId: '2024-002', 
    firstName: 'Jane', 
    lastName: 'Smith', 
    age: 24, 
    yearLevel: '2nd Year', 
    course: 'BSED', 
    // Registered 2 days ago (Current Month Data)
    registrationDate: getRelativeDate(2),
    email: 'jane.smith@email.com',
    phone: '555-0124',
    dateOfBirth: 'July 22, 2001',
    address: '456 Oak Ave, Somecity, USA',
    medicalHistory: 'Asthma (managed with inhaler).'
  },
  { 
    id: '3', 
    studentId: '2024-003', 
    firstName: 'Michael', 
    lastName: 'Johnson', 
    age: 26, 
    yearLevel: '4th Year', 
    course: 'BSBA', 
    // Registered 5 days ago (Current Month Data)
    registrationDate: getRelativeDate(5),
    email: 'm.johnson@email.com',
    phone: '555-0125',
    dateOfBirth: 'November 10, 1999',
    address: '789 Pine Rd, Othertown, USA',
    medicalHistory: 'None.'
  },
  { 
    id: '4', 
    studentId: '2024-004', 
    firstName: 'Sarah', 
    lastName: 'Williams', 
    age: 23, 
    yearLevel: '1st Year', 
    course: 'BEED', 
    // Registered ~1 month ago (Previous Month Data)
    registrationDate: getRelativeDate(35),
    email: 'sarah.w@email.com',
    phone: '555-0126',
    dateOfBirth: 'January 05, 2002',
    address: '321 Elm St, Smallville, USA',
    medicalHistory: 'Allergic to penicillin.'
  },
  { 
    id: '5', 
    studentId: '2024-005', 
    firstName: 'Emily', 
    lastName: 'Brown', 
    age: 24, 
    yearLevel: '3rd Year', 
    course: 'BSHM', 
    // Registered ~2 months ago
    registrationDate: getRelativeDate(65),
    email: 'emily.b@email.com',
    phone: '555-0127',
    dateOfBirth: 'September 18, 2001',
    address: '654 Maple Dr, Bigcity, USA',
    medicalHistory: 'None.'
  },
  { 
    id: '6', 
    studentId: '2024-006', 
    firstName: 'David', 
    lastName: 'Wilson', 
    age: 26, 
    yearLevel: '4th Year', 
    course: 'BSCS', 
    // Registered ~3 months ago
    registrationDate: getRelativeDate(95),
    email: 'david.wilson@email.com',
    phone: '555-0128',
    dateOfBirth: 'February 28, 1999',
    address: '987 Cedar Ln, Midtown, USA',
    medicalHistory: 'Seasonal allergies.'
  },
];

export const MOCK_RECORDS: MedicalRecord[] = [
    { id: '1', patient: 'David Wilson', studentId: '2024-006', type: 'CONSULTATION', title: 'Post-Surgical Follow-up', date: getRelativeDate(5), diagnosis: 'Recovery going well', treatment: 'Continue physiotherapy' },
    { id: '2', patient: 'Emily Brown', studentId: '2024-005', type: 'CONSULTATION', title: 'Allergy Assessment', date: getRelativeDate(10), diagnosis: 'Seasonal Allergies', medicine: 'Antihistamines' },
    { id: '3', patient: 'Sarah Williams', studentId: '2024-004', type: 'CONSULTATION', title: 'Initial Consultation', date: getRelativeDate(15), diagnosis: 'Common Cold', treatment: 'Rest and fluids' },
    { id: '4', patient: 'Michael Johnson', studentId: '2024-003', type: 'CONSULTATION', title: 'Hypertension Follow-up', date: getRelativeDate(20), diagnosis: 'Hypertension', medicine: 'Lisinopril' },
    { id: '5', patient: 'Jane Smith', studentId: '2024-002', type: 'CONSULTATION', title: 'Diabetes Management', date: getRelativeDate(25), diagnosis: 'Type 2 Diabetes', medicine: 'Metformin' },
    { id: '6', patient: 'John Doe', studentId: '2024-001', type: 'CONSULTATION', title: 'Annual Physical Exam', date: getRelativeDate(30), diagnosis: 'Healthy' },
];

export const MOCK_MEDICINES: Medicine[] = [
  { 
    id: 1, 
    name: 'Paracetamol 500mg', 
    genericName: 'Acetaminophen', 
    type: 'Analgesic', 
    stock: 150, 
    minStock: 20, 
    expiryDate: 'Dec 31, 2025', 
    expiryStatus: 'EXPIRING SOON', 
    location: 'Shelf A1' 
  },
  { 
    id: 2, 
    name: 'Biogesic', 
    genericName: 'Paracetamol', 
    type: 'Analgesic', 
    stock: 200, 
    minStock: 30, 
    expiryDate: 'Oct 15, 2026', 
    expiryStatus: 'GOOD', 
    location: 'Shelf A1' 
  },
  { 
    id: 3, 
    name: 'Amoxicillin', 
    genericName: 'Amoxicillin', 
    type: 'Antibiotic', 
    stock: 75, 
    minStock: 15, 
    expiryDate: 'Aug 15, 2025', 
    expiryStatus: 'EXPIRED', 
    location: 'Shelf B2' 
  },
  { 
    id: 4, 
    name: 'Mefenamic Acid', 
    genericName: 'Mefenamic Acid', 
    type: 'NSAID', 
    stock: 100, 
    minStock: 20, 
    expiryDate: 'Nov 20, 2025', 
    expiryStatus: 'GOOD', 
    location: 'Shelf A2' 
  },
  { 
    id: 5, 
    name: 'Aspirin 75mg', 
    genericName: 'Acetylsalicylic Acid', 
    type: 'Antiplatelet', 
    stock: 300, 
    minStock: 50, 
    expiryDate: 'Jan 15, 2026', 
    expiryStatus: 'GOOD', 
    location: 'Shelf A2' 
  },
  { 
    id: 6, 
    name: 'Ibuprofen 400mg', 
    genericName: 'Ibuprofen', 
    type: 'NSAID', 
    stock: 200, 
    minStock: 25, 
    expiryDate: 'Mar 20, 2026', 
    expiryStatus: 'GOOD', 
    location: 'Shelf A3' 
  },
  { 
    id: 7, 
    name: 'Insulin Glargine', 
    genericName: 'Insulin Glargine', 
    type: 'Hormone', 
    stock: 12, 
    minStock: 5, 
    expiryDate: 'Jun 30, 2024', 
    expiryStatus: 'EXPIRED', 
    location: 'Refrigerator' 
  },
  { 
    id: 8, 
    name: 'Lisinopril 10mg', 
    genericName: 'Lisinopril', 
    type: 'ACE Inhibitor', 
    stock: 90, 
    minStock: 15, 
    expiryDate: 'Jul 18, 2025', 
    expiryStatus: 'EXPIRED', 
    location: 'Shelf B3' 
  },
  { 
    id: 9, 
    name: 'Metformin 500mg', 
    genericName: 'Metformin', 
    type: 'Antidiabetic', 
    stock: 120, 
    minStock: 20, 
    expiryDate: 'Sep 25, 2025', 
    expiryStatus: 'EXPIRED', 
    location: 'Shelf B1' 
  }
];

export const MOCK_EQUIPMENTS: Equipment[] = [
  {
      id: 1,
      name: 'Digital Thermometer',
      model: 'TempTech - TEMP-100',
      type: 'Diagnostic',
      status: 'OPERATIONAL',
      maintenanceStatus: 'OVERDUE',
      maintenanceDate: 'Aug 10, 2024',
      location: 'Consultation Room 2',
      assignedTo: 'Dr. Michael Brown'
  },
  {
      id: 2,
      name: 'Blood Pressure Monitor',
      model: 'MedTech Solutions - BP-2000',
      type: 'Diagnostic',
      status: 'OPERATIONAL',
      maintenanceStatus: 'OVERDUE',
      maintenanceDate: 'Jul 15, 2024',
      location: 'Consultation Room 1',
      assignedTo: 'Dr. Sarah Johnson'
  },
  {
      id: 3,
      name: 'Stethoscope',
      model: 'CardioCare - ST-500',
      type: 'Diagnostic',
      status: 'OPERATIONAL',
      maintenanceStatus: 'OVERDUE',
      maintenanceDate: 'Sep 20, 2024',
      location: 'Consultation Room 1',
      assignedTo: 'Dr. Sarah Johnson'
  },
  {
      id: 4,
      name: 'Pulse Oximeter',
      model: 'OxyMed - OXI-300',
      type: 'Monitoring',
      status: 'OPERATIONAL',
      maintenanceStatus: 'OVERDUE',
      maintenanceDate: 'Oct 5, 2024',
      location: 'Consultation Room 2',
      assignedTo: 'Dr. Michael Brown'
  },
  {
      id: 5,
      name: 'Autoclave',
      model: 'SterilCorp - AUTO-200',
      type: 'Sterilization',
      status: 'OPERATIONAL',
      maintenanceStatus: 'OVERDUE',
      maintenanceDate: 'Feb 15, 2024',
      location: 'Sterilization Room',
      assignedTo: 'Nurse Jane'
  },
  {
      id: 6,
      name: 'Defibrillator',
      model: 'LifeSave - DEF-1000',
      type: 'Emergency',
      status: 'OPERATIONAL',
      maintenanceStatus: 'OVERDUE',
      maintenanceDate: 'Dec 20, 2024',
      location: 'Emergency Room',
      assignedTo: 'Emergency Team'
  },
  {
      id: 7,
      name: 'ECG Machine',
      model: 'CardioTech - ECG-5000',
      type: 'Diagnostic',
      status: 'OPERATIONAL',
      maintenanceStatus: 'OVERDUE',
      maintenanceDate: 'May 30, 2024',
      location: 'Diagnostic Room',
      assignedTo: 'Dr. Emily Davis'
  }
];