import { LucideIcon } from 'lucide-react';

export interface NavItem {
  id: string;
  label: string;
  description: string;
  icon: LucideIcon;
  active?: boolean;
}

export interface StatItem {
  label: string;
  value: number | string;
  color?: string;
  subtext?: string;
}

export interface AlertData {
  id: string;
  type: 'danger' | 'warning' | 'info';
  message: string;
}

export interface ActivityLog {
  id: string;
  user: string;
  action: string;
  time: string;
}

export interface Patient {
  id: string;
  studentId: string;
  firstName: string;
  lastName: string;
  age: number;
  yearLevel: string;
  course: string;
  registrationDate: string;
  // Extended fields
  email?: string;
  phone?: string;
  dateOfBirth?: string;
  address?: string;
  medicalHistory?: string;
}

export interface Certificate {
  id: string;
  patientId: string;
  patientName: string;
  studentId: string;
  date: string;
  diagnosis: string;
  daysOfRest: string;
  medicine: string;
  equipment: string;
  treatment: string;
}

export interface VitalSigns {
  bp: string;
  hr: string;
  rr: string;
  temp: string;
  height: string;
  weight: string;
  o2Sat: string;
  bmi: string;
}

export interface MedicalRecord {
    id: string;
    patient: string;
    studentId?: string; // Added to link easily
    type: string;
    title: string;
    date: string;
    diagnosis?: string;
    treatment?: string;
    medicine?: string;
    vitalSigns?: VitalSigns;
}

export interface TransferRecord {
    id: string;
    patientId: string;
    patientName: string;
    transferTo: string;
    reason: string;
    date: string;
    course?: string;
    yearLevel?: string;
}

export interface Medicine {
  id: number;
  name: string;
  genericName: string;
  type: string;
  stock: number;
  minStock: number;
  expiryDate: string;
  expiryStatus: string;
  location: string;
}

export interface Equipment {
  id: number;
  name: string;
  model: string;
  type: string;
  status: string;
  maintenanceStatus: string;
  maintenanceDate: string;
  location: string;
  assignedTo: string;
}