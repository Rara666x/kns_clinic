import React, { useState, useEffect } from 'react';
import Header from './components/Header';
import Sidebar from './components/Sidebar';
import PatientAnalytics from './components/PatientAnalytics';
import StatBox from './components/StatBox';
import Alerts from './components/Alerts';
import Patients from './components/Patients';
import Archive from './components/Archive';
import AuditTrail from './components/AuditTrail';
import MedicalCertificate from './components/MedicalCertificate';
import MedicalRecords from './components/MedicalRecords';
import Inventory from './components/Inventory';
import Reports from './components/Reports';
import TransferPatient from './components/TransferPatient';
import UserManual from './components/UserManual';
import Toast from './components/Toast';
import Login from './components/Login';
import { Pill, Stethoscope, X } from 'lucide-react';
import { 
  ACTIVITIES, 
  MOCK_PATIENTS, 
  MOCK_MEDICINES, 
  MOCK_EQUIPMENTS, 
  MOCK_RECORDS 
} from './constants';
import { Patient, Certificate, MedicalRecord, TransferRecord, Medicine, Equipment } from './types';
import { api } from './src/services/apiService';

const App: React.FC = () => {
  // Auth State
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  const [activeTab, setActiveTab] = useState('dashboard');
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);
  const [notification, setNotification] = useState<{message: string, type: 'success' | 'error' | 'warning'} | null>(null);
  
  // -- REAL DATA STATES --
  const [patients, setPatients] = useState<Patient[]>([]);
  const [medicalRecords, setMedicalRecords] = useState<MedicalRecord[]>([]);
  const [medicines, setMedicines] = useState<Medicine[]>([]);
  const [equipments, setEquipments] = useState<Equipment[]>([]);
  
  const [archivedPatients, setArchivedPatients] = useState<Patient[]>([]);
  const [archivedTransferRecords, setArchivedTransferRecords] = useState<TransferRecord[]>([]);
  
  const [certificates, setCertificates] = useState<Certificate[]>([]);
  const [transferRecords, setTransferRecords] = useState<TransferRecord[]>([]);

  // -- INITIAL DATA FETCHING --
  useEffect(() => {
    if (isAuthenticated) {
        loadSystemData(false); // Initial load only when authenticated
    }
  }, [isAuthenticated]);

  const loadSystemData = async (silent = false) => {
    try {
        // The API service is now mocked to return data directly
        const [pats, meds, equips, recs] = await Promise.all([
            api.getPatients(),
            api.getMedicines(),
            api.getEquipments(),
            api.getMedicalRecords()
        ]);
        
        setPatients(pats);
        setMedicines(meds);
        setEquipments(equips);
        setMedicalRecords(recs);

        if(!silent) {
            setNotification({ message: "System Loaded", type: "success" });
        }
    } catch (error) {
        console.warn("Error loading data", error);
        // Fallback to constants if even the mocked service fails
        setPatients(prev => prev.length === 0 ? MOCK_PATIENTS : prev);
        setMedicines(prev => prev.length === 0 ? MOCK_MEDICINES : prev);
        setEquipments(prev => prev.length === 0 ? MOCK_EQUIPMENTS : prev);
        setMedicalRecords(prev => prev.length === 0 ? MOCK_RECORDS : prev);
    }
  };

  const handleNavigate = (id: string) => {
    setActiveTab(id);
    setIsMobileMenuOpen(false);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // --- PATIENT ARCHIVE LOGIC ---
  const handleArchivePatient = async (patientToArchive: Patient) => {
    try {
        await api.archivePatient(patientToArchive.id);
        setPatients(prev => prev.filter(p => p.id !== patientToArchive.id));
        setArchivedPatients(prev => [...prev, patientToArchive]); // In memory move for now
        setNotification({ message: "Patient archived successfully.", type: "success" });
    } catch (e) {
        setNotification({ message: "Error archiving patient.", type: "error" });
    }
  };

  const handleRestorePatient = (patientToRestore: Patient) => {
      // 1. Remove from Archive
      setArchivedPatients(prev => prev.filter(p => p.id !== patientToRestore.id));
      // 2. Add back to Patients list
      setPatients(prev => [patientToRestore, ...prev]);
      
      setNotification({ message: "Patient restored successfully.", type: "success" });
  };

  // --- TRANSFER RECORD ARCHIVE LOGIC ---
  const handleArchiveTransferRecord = (r: TransferRecord) => {
      setTransferRecords(prev => prev.filter(x => x.id !== r.id));
      setArchivedTransferRecords(prev => [...prev, r]);
      setNotification({ message: "Transfer record archived.", type: "success" });
  };

  const handleRestoreTransferRecord = (r: TransferRecord) => {
      setArchivedTransferRecords(prev => prev.filter(x => x.id !== r.id));
      setTransferRecords(prev => [r, ...prev]);
      setNotification({ message: "Transfer record restored.", type: "success" });
  };

  const handleAddPatient = async (newPatient: Patient, initialRecord?: MedicalRecord, medicineUsage?: { id: number, quantity: number }) => {
      try {
          // 1. Save Patient
          const res = await api.addPatient(newPatient);
          const patientDbId = res?.id || res?.success?.id || null;
          if(patientDbId) {
              newPatient.id = patientDbId.toString();
          }
          
          setPatients(prev => [newPatient, ...prev]);
          let message = "Patient added successfully.";

          // 2. Save Initial Record (if patient was saved successfully)
          if (initialRecord && patientDbId) {
              // Update record with actual patient database ID
              const recordWithPatientId = { ...initialRecord, patientId: patientDbId };
              await api.addMedicalRecord(recordWithPatientId);
              setMedicalRecords(prev => [recordWithPatientId, ...prev]);
              message = "Patient and initial record added.";
          }

          // 3. Update Inventory
          if (medicineUsage) {
              const med = medicines.find(m => m.id === medicineUsage.id);
              if (med) {
                  const newStock = Math.max(0, med.stock - medicineUsage.quantity);
                  await api.updateMedicineStock(medicineUsage.id, newStock);
                  setMedicines(prev => prev.map(m => m.id === medicineUsage.id ? {...m, stock: newStock} : m));
                  message += " Inventory updated.";
              }
          }

          setNotification({ message: message, type: "success" });
      } catch (e) {
          console.error(e);
          setNotification({ message: "Error saving patient data.", type: "error" });
      }
  };

  const handleAddMedicalRecord = async (record: MedicalRecord) => {
      try {
        await api.addMedicalRecord(record);
        setMedicalRecords(prev => [record, ...prev]);
        setNotification({ message: "New medical record saved.", type: "success" });
      } catch (e) {
        setNotification({ message: "Error saving record.", type: "error" });
      }
  };

  // Header Actions
  const handleManageUsers = () => {
    setNotification({ message: "User Management module coming soon.", type: "warning" });
  };

  const handleManual = () => {
     setActiveTab('manual');
     setIsMobileMenuOpen(false);
  };

  const handleLogout = () => {
     setIsAuthenticated(false);
     setNotification({ message: "Logged out successfully.", type: "success" });
  };

  const handleLogin = () => {
    setIsAuthenticated(true);
    // Data will load via useEffect
  };

  // Placeholders for features not yet fully API-connected in this snippet
  const handleUpdatePatient = (p: Patient) => { 
      setNotification({ message: "Update feature coming in next version.", type: "warning" });
  };
  
  const handleSaveCertificate = (c: Certificate) => setCertificates(prev => [c, ...prev]);
  const handleAddTransferRecord = (r: TransferRecord) => setTransferRecords(prev => [r, ...prev]);

  // Calculate stats dynamically
  const totalMedicines = medicines.length;
  const expiredMedicines = medicines.filter(m => m.expiryStatus === 'EXPIRED').length;
  const lowStockMedicines = medicines.filter(m => m.stock <= m.minStock && m.stock > 0).length;
  const expiringSoonMedicines = medicines.filter(m => m.expiryStatus === 'EXPIRING SOON').length;

  const totalEquipments = equipments.length;
  const operationalEquipments = equipments.filter(e => e.status === 'OPERATIONAL').length;
  const maintenanceEquipments = equipments.filter(e => e.status === 'MAINTENANCE').length;

  // Render Login if not authenticated
  if (!isAuthenticated) {
      return (
        <>
            {notification && (
                <Toast 
                    message={notification.message} 
                    type={notification.type} 
                    onClose={() => setNotification(null)} 
                />
            )}
            <Login onLogin={handleLogin} />
        </>
      );
  }

  return (
    <div className="min-h-screen flex flex-col bg-[#f0f4f8]">
      <Header 
        onMenuClick={() => setIsMobileMenuOpen(true)} 
        onManageUsers={handleManageUsers}
        onManual={handleManual}
        onLogout={handleLogout}
      />

      {notification && (
        <Toast 
            message={notification.message} 
            type={notification.type} 
            onClose={() => setNotification(null)} 
        />
      )}

      {isMobileMenuOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity" onClick={() => setIsMobileMenuOpen(false)} />
          <div className="absolute top-0 left-0 h-full w-4/5 max-w-[300px] bg-[#f0f4f8] shadow-2xl p-4 transition-transform duration-300 transform translate-x-0">
            <div className="flex justify-between items-center mb-6 px-2">
              <span className="font-bold text-lg text-blue-900">Menu</span>
              <button onClick={() => setIsMobileMenuOpen(false)} className="p-1 rounded-full hover:bg-gray-200 text-gray-500">
                <X size={24} />
              </button>
            </div>
            <Sidebar activeTab={activeTab} onNavigate={handleNavigate} />
          </div>
        </div>
      )}

      <div className="flex-1 p-4 lg:p-6">
        <div className="max-w-[1920px] mx-auto h-full relative">
          <div className="w-full lg:w-[calc(100%-300px)] min-h-0 pb-10">
            {activeTab === 'dashboard' ? (
              <>
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 md:p-8 text-center mb-6">
                    <h1 className="text-xl md:text-2xl font-bold text-blue-600 mb-1">Welcome back, System Administrator.</h1>
                    <p className="text-gray-500 text-sm">
                        Overview of clinical operations for today.
                    </p>
                </div>

                <div className="animate-in fade-in slide-in-from-bottom-6 duration-500 delay-100">
                    <PatientAnalytics patients={patients} />
                </div>

                <div className="flex flex-col md:flex-row gap-6 mb-6 animate-in fade-in slide-in-from-bottom-8 duration-500 delay-200">
                    <StatBox 
                        title="Medicine Stock"
                        icon={Pill}
                        iconColor="text-red-500"
                        items={[
                            { label: 'Total Medicines', value: totalMedicines },
                            { label: 'Low Stock', value: lowStockMedicines },
                            { label: 'Expired', value: expiredMedicines, alert: true },
                            { label: 'Expiring Soon', value: expiringSoonMedicines, warning: true },
                        ]}
                    />
                    <StatBox 
                        title="Medical Equipment"
                        icon={Stethoscope}
                        iconColor="text-gray-400"
                        items={[
                            { label: 'Total Equipment', value: totalEquipments },
                            { label: 'Operational', value: operationalEquipments, success: true },
                            { label: 'Maintenance', value: maintenanceEquipments, warning: true },
                            { label: 'Out of Order', value: 0 },
                        ]}
                    />
                </div>

                <div className="animate-in fade-in slide-in-from-bottom-10 duration-500 delay-300">
                    <Alerts medicines={medicines} equipments={equipments} />
                </div>
              </>
            ) : (
              <div className="h-full animate-in fade-in zoom-in-95 duration-300">
                {activeTab === 'patients' && (
                    <Patients 
                        patients={patients}
                        medicines={medicines}
                        records={medicalRecords}
                        onArchive={handleArchivePatient} 
                        onUpdate={handleUpdatePatient}
                        onAddPatient={handleAddPatient}
                    />
                )}
                {activeTab === 'records' && (
                    <MedicalRecords 
                        patients={patients} 
                        records={medicalRecords}
                        onAddRecord={handleAddMedicalRecord}
                    />
                )}
                {activeTab === 'certificates' && (
                    <MedicalCertificate 
                        patients={patients} 
                        onSave={handleSaveCertificate}
                    />
                )}
                {activeTab === 'transfer' && (
                    <TransferPatient 
                        patients={patients} 
                        records={transferRecords}
                        onTransfer={handleAddTransferRecord}
                        onArchive={handleArchiveTransferRecord}
                    />
                )}
                {activeTab === 'inventory' && (
                    <Inventory 
                        medicines={medicines}
                        equipments={equipments}
                        onUpdateMedicines={setMedicines}
                        onUpdateEquipments={setEquipments}
                    />
                )}
                {activeTab === 'reports' && (
                    <Reports 
                        certificates={certificates} 
                        medicalRecords={medicalRecords}
                        transferRecords={transferRecords}
                        medicines={medicines}
                        equipments={equipments}
                    />
                )}
                {activeTab === 'archive' && (
                    <Archive 
                        patients={archivedPatients} 
                        transferRecords={archivedTransferRecords}
                        onRestore={handleRestorePatient} 
                        onRestoreTransfer={handleRestoreTransferRecord}
                    />
                )}
                {activeTab === 'audit-trail' && (
                    <AuditTrail activities={ACTIVITIES} />
                )}
                {activeTab === 'manual' && (
                    <UserManual onBack={() => setActiveTab('dashboard')} />
                )}
              </div>
            )}
          </div>
          <div className="hidden lg:block fixed top-[84px] right-6 w-[280px] h-[calc(100vh-100px)] overflow-y-auto pb-6 scrollbar-hide">
            <Sidebar activeTab={activeTab} onNavigate={handleNavigate} />
          </div>
        </div>
      </div>
    </div>
  );
};

export default App;