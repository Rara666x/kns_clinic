# Clinical System - Project Structure

## Overview
The Clinical System is a modern, React-based web application for managing clinical operations, patient records, and medical inventory. It is designed to work with a PHP/MySQL backend (XAMPP).

## Backend Setup (XAMPP)
1. **Database**: Use `database.sql` to create the `kns_clinic` database in phpMyAdmin.
2. **API**: Move `api.php` to `C:\xampp\htdocs\clinical-api\`.

## Directory Structure

```
/
├── components/           # React components
│   ├── Header.tsx       # Top navigation bar
│   ├── Sidebar.tsx      # Side navigation menu
│   ├── Patients.tsx     # Patient management view
│   ├── Inventory.tsx    # Inventory management view
│   ├── Reports.tsx      # Reporting and analytics view
│   ├── StatBox.tsx      # Dashboard statistics component
│   └── ...              # Modals and other UI components
├── src/
│   └── services/
│       └── apiService.ts # API Connector for PHP Backend
├── api.php              # PHP Backend script (Move to XAMPP htdocs)
├── database.sql         # SQL Schema for 'kns_clinic'
├── App.tsx              # Main application layout and routing logic
├── constants.tsx        # Fallback Mock data
├── index.css            # Global styles and Tailwind directives
├── index.html           # HTML entry point
├── index.tsx            # React entry point
├── types.ts             # TypeScript interface definitions
└── PROJECT_STRUCTURE.md # This documentation file
```

## Tech Stack
- **Frontend Framework**: React 18
- **Language**: TypeScript
- **Styling**: Tailwind CSS
- **Icons**: Lucide React
- **Charts**: Custom SVG / Recharts
- **Backend**: PHP (Vanilla)
- **Database**: MySQL (`kns_clinic`)

## Key Features
- **Dashboard**: Real-time overview of patients and inventory.
- **Patient Management**: Add, edit, archive, and view patient details.
- **Inventory Tracking**: Manage medicines and equipment with stock alerts.
- **Medical Records**: Track consultation history and treatments.
- **Certificates**: Generate and print medical certificates.
- **Responsive Design**: Fully responsive layout for desktop and mobile.
