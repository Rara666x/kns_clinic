# KNS Clinical System - Setup Instructions

## Prerequisites
- XAMPP installed and running
- Node.js and npm installed
- A web browser (Chrome, Firefox, Edge, etc.)

## Step 1: Set Up the Database

1. **Start XAMPP Control Panel**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services (click "Start" buttons)

2. **Create the Database**
   - Open your browser and go to: `http://localhost/phpmyadmin`
   - Click on "New" in the left sidebar to create a new database
   - Database name: `kns_clinic`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

3. **Import the Database Schema**
   - Select the `kns_clinic` database from the left sidebar
   - Click on the "Import" tab at the top
   - Click "Choose File" and select `database.sql` from your project folder:
     ```
     C:\xampp\htdocs\kns_clinical\database.sql
   ```
   - Click "Go" at the bottom
   - Wait for the import to complete (you should see a success message)

## Step 2: Install Frontend Dependencies

1. **Open Command Prompt or PowerShell**
   - Navigate to your project directory:
     ```powershell
     cd C:\xampp\htdocs\kns_clinical
     ```

2. **Install npm packages**
   ```powershell
   npm install
   ```
   This will install all required dependencies (React, Vite, TypeScript, etc.)

## Step 3: Run the System

You have **two options** to run the system:

### Option A: React Frontend (Modern SPA - Recommended)

1. **Start the Vite development server**
   ```powershell
   npm run dev
   ```

2. **Access the application**
   - Open your browser and go to: `http://localhost:3000`
   - The React application will load with hot-reload enabled
   - Any changes you make to the code will automatically refresh in the browser

### Option B: PHP Pages (Traditional)

1. **Access via XAMPP**
   - Make sure Apache is running in XAMPP
   - Open your browser and go to: `http://localhost/kns_clinical/login.php`
   - This will show the PHP-based login page

## Step 4: Create Your First User

Since the database is empty, you'll need to create a user account:

1. **Option 1: Via phpMyAdmin (Recommended)**
   - Go to `http://localhost/phpmyadmin`
   - Select `kns_clinic` database
   - Click on `users` table
   - Click "Insert" tab
   - Fill in the form:
     - `username`: (e.g., "admin")
     - `password`: (your desired password - stored as plain text in current setup)
     - `fullName`: (e.g., "Administrator")
     - `email`: (e.g., "admin@example.com")
     - `role`: Select "admin" or "assistant"
     - `is_active`: 1 (checked)
   - Click "Go"

2. **Option 2: Via SQL Query**
   - In phpMyAdmin, click on "SQL" tab
   - Run this query (replace with your details):
     ```sql
     INSERT INTO users (username, password, fullName, email, role) 
     VALUES ('admin', 'yourpassword', 'Administrator', 'admin@example.com', 'admin');
     ```

## Troubleshooting

### Port 3000 Already in Use
If you get an error that port 3000 is already in use:
- Change the port in `vite.config.ts` (line 9) to another port like `3001`
- Or stop the application using port 3000

### Database Connection Error
- Make sure MySQL is running in XAMPP
- Verify the database name in `db_connection.php` is `kns_clinic`
- Check that the database was created successfully in phpMyAdmin

### API Not Working
- Make sure Apache is running in XAMPP
- Verify the API URL in `src/services/apiService.ts` points to `http://localhost/kns_clinical/api.php`
- Check browser console (F12) for any CORS or network errors

### Cannot Access phpMyAdmin
- Make sure Apache and MySQL are both running in XAMPP
- Try accessing `http://localhost/phpmyadmin` directly
- Check XAMPP error logs if issues persist

## Default Access Points

- **React Frontend**: `http://localhost:3000`
- **PHP Login Page**: `http://localhost/kns_clinical/login.php`
- **PHP Dashboard**: `http://localhost/kns_clinical/dashboard.php` (after login)
- **phpMyAdmin**: `http://localhost/phpmyadmin`

## Notes

- The React frontend (Option A) provides a modern single-page application experience
- The PHP pages (Option B) provide traditional server-side rendered pages
- Both can work together - the React app can call PHP API endpoints
- The system will use mock data if the backend API is unavailable (check browser console)

