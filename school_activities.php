<?php
include('includes/auth.php');
include('db_connection.php');

// Handle form submissions
$message = '';
$messageType = '';

if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_activity':
                $activityName = $_POST['activity_name'] ?? '';
                $activityType = $_POST['activity_type'] ?? '';
                $description = $_POST['description'] ?? '';
                $startDate = $_POST['start_date'] ?? '';
                $endDate = $_POST['end_date'] ?? '';
                $startTime = $_POST['start_time'] ?? '';
                $endTime = $_POST['end_time'] ?? '';
                $location = $_POST['location'] ?? '';
                $organizer = $_POST['organizer'] ?? '';
                $maxParticipants = $_POST['max_participants'] ?? 0;
                $status = $_POST['status'] ?? 'planned';
                $notes = $_POST['notes'] ?? '';
                $medicalTeamAssigned = isset($_POST['medical_team_assigned']) ? 1 : 0;
                $medicalTeamNotes = $_POST['medical_team_notes'] ?? '';
                $medicalEquipmentNeeded = $_POST['medical_equipment_needed'] ?? '';
                $firstAidStation = $_POST['first_aid_station'] ?? '';
                $emergencyContact = $_POST['emergency_contact'] ?? '';
                
                if ($activityName && $activityType && $startDate && $startTime) {
                    $stmt = $conn->prepare("INSERT INTO school_activities (activity_name, activity_type, description, start_date, end_date, start_time, end_time, location, organizer, max_participants, status, notes, medical_team_assigned, medical_team_notes, medical_equipment_needed, first_aid_station, emergency_contact, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssssssssiisssssss", $activityName, $activityType, $description, $startDate, $endDate, $startTime, $endTime, $location, $organizer, $maxParticipants, $status, $notes, $medicalTeamAssigned, $medicalTeamNotes, $medicalEquipmentNeeded, $firstAidStation, $emergencyContact);
                    
                    if ($stmt->execute()) {
                        $message = "Activity added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding activity: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'add_event':
                $eventName = $_POST['event_name'] ?? '';
                $eventType = $_POST['event_type'] ?? '';
                $description = $_POST['description'] ?? '';
                $eventDate = $_POST['event_date'] ?? '';
                $startTime = $_POST['start_time'] ?? '';
                $endTime = $_POST['end_time'] ?? '';
                $location = $_POST['location'] ?? '';
                $organizer = $_POST['organizer'] ?? '';
                $maxParticipants = $_POST['max_participants'] ?? 0;
                $status = $_POST['status'] ?? 'planned';
                $notes = $_POST['notes'] ?? '';
                $medicalTeamAssigned = isset($_POST['medical_team_assigned']) ? 1 : 0;
                $medicalTeamNotes = $_POST['medical_team_notes'] ?? '';
                $medicalEquipmentNeeded = $_POST['medical_equipment_needed'] ?? '';
                $firstAidStation = $_POST['first_aid_station'] ?? '';
                $emergencyContact = $_POST['emergency_contact'] ?? '';
                
                if ($eventName && $eventType && $eventDate && $startTime) {
                    $stmt = $conn->prepare("INSERT INTO school_events (event_name, event_type, description, event_date, start_time, end_time, location, organizer, max_participants, status, notes, medical_team_assigned, medical_team_notes, medical_equipment_needed, first_aid_station, emergency_contact, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssssssssiisssssss", $eventName, $eventType, $description, $eventDate, $startTime, $endTime, $location, $organizer, $maxParticipants, $status, $notes, $medicalTeamAssigned, $medicalTeamNotes, $medicalEquipmentNeeded, $firstAidStation, $emergencyContact);
                    
                    if ($stmt->execute()) {
                        $message = "Event added successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error adding event: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                } else {
                    $message = "Please fill in all required fields.";
                    $messageType = "error";
                }
                break;
                
            case 'update_activity':
                $id = $_POST['activity_id'] ?? '';
                $activityName = $_POST['activity_name'] ?? '';
                $activityType = $_POST['activity_type'] ?? '';
                $description = $_POST['description'] ?? '';
                $startDate = $_POST['start_date'] ?? '';
                $endDate = $_POST['end_date'] ?? '';
                $startTime = $_POST['start_time'] ?? '';
                $endTime = $_POST['end_time'] ?? '';
                $location = $_POST['location'] ?? '';
                $organizer = $_POST['organizer'] ?? '';
                $maxParticipants = $_POST['max_participants'] ?? 0;
                $status = $_POST['status'] ?? 'planned';
                $notes = $_POST['notes'] ?? '';
                
                if ($id && $activityName && $activityType && $startDate && $startTime) {
                    $stmt = $conn->prepare("UPDATE school_activities SET activity_name=?, activity_type=?, description=?, start_date=?, end_date=?, start_time=?, end_time=?, location=?, organizer=?, max_participants=?, status=?, notes=? WHERE id=?");
                    $stmt->bind_param("ssssssssiisi", $activityName, $activityType, $description, $startDate, $endDate, $startTime, $endTime, $location, $organizer, $maxParticipants, $status, $notes, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Activity updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating activity: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                }
                break;
                
            case 'update_event':
                $id = $_POST['event_id'] ?? '';
                $eventName = $_POST['event_name'] ?? '';
                $eventType = $_POST['event_type'] ?? '';
                $description = $_POST['description'] ?? '';
                $eventDate = $_POST['event_date'] ?? '';
                $startTime = $_POST['start_time'] ?? '';
                $endTime = $_POST['end_time'] ?? '';
                $location = $_POST['location'] ?? '';
                $organizer = $_POST['organizer'] ?? '';
                $maxParticipants = $_POST['max_participants'] ?? 0;
                $status = $_POST['status'] ?? 'planned';
                $notes = $_POST['notes'] ?? '';
                
                if ($id && $eventName && $eventType && $eventDate && $startTime) {
                    $stmt = $conn->prepare("UPDATE school_events SET event_name=?, event_type=?, description=?, event_date=?, start_time=?, end_time=?, location=?, organizer=?, max_participants=?, status=?, notes=? WHERE id=?");
                    $stmt->bind_param("ssssssssiisi", $eventName, $eventType, $description, $eventDate, $startTime, $endTime, $location, $organizer, $maxParticipants, $status, $notes, $id);
                    
                    if ($stmt->execute()) {
                        $message = "Event updated successfully!";
                        $messageType = "success";
                    } else {
                        $message = "Error updating event: " . $conn->error;
                        $messageType = "error";
                    }
                    $stmt->close();
                }
                break;
                
                
        }
    }
}

// Get statistics
$stats = [];

// Activity statistics
$result = $conn->query("SELECT COUNT(*) as total FROM school_activities");
$stats['total_activities'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as upcoming FROM school_activities WHERE start_date >= CURDATE() AND status IN ('planned', 'confirmed')");
$stats['upcoming_activities'] = $result->fetch_assoc()['upcoming'];

$result = $conn->query("SELECT COUNT(*) as ongoing FROM school_activities WHERE start_date <= CURDATE() AND end_date >= CURDATE() AND status = 'ongoing'");
$stats['ongoing_activities'] = $result->fetch_assoc()['ongoing'];

// Event statistics
$result = $conn->query("SELECT COUNT(*) as total FROM school_events");
$stats['total_events'] = $result->fetch_assoc()['total'];

$result = $conn->query("SELECT COUNT(*) as upcoming FROM school_events WHERE event_date >= CURDATE() AND status IN ('planned', 'confirmed')");
$stats['upcoming_events'] = $result->fetch_assoc()['upcoming'];

// Get upcoming activities (next 30 days)
$upcomingActivities = $conn->query("
    SELECT * FROM school_activities 
    WHERE start_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
    AND status IN ('planned', 'confirmed', 'ongoing')
    ORDER BY start_date ASC, start_time ASC
    LIMIT 10
");

// Get upcoming events (next 30 days)
$upcomingEvents = $conn->query("
    SELECT * FROM school_events 
    WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
    AND status IN ('planned', 'confirmed')
    ORDER BY event_date ASC, start_time ASC
    LIMIT 10
");

// Get activity for editing
$editActivity = null;
if (isset($_GET['edit_activity'])) {
    $editId = $_GET['edit_activity'];
    $editStmt = $conn->prepare("SELECT * FROM school_activities WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editActivity = $editStmt->get_result()->fetch_assoc();
    $editStmt->close();
}

// Get event for editing
$editEvent = null;
if (isset($_GET['edit_event'])) {
    $editId = $_GET['edit_event'];
    $editStmt = $conn->prepare("SELECT * FROM school_events WHERE id = ?");
    $editStmt->bind_param("i", $editId);
    $editStmt->execute();
    $editEvent = $editStmt->get_result()->fetch_assoc();
    $editStmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>School Activities & Events - KNS Clinical System</title>
    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/clinical-dashboard.css">
    <link rel="stylesheet" href="css/school_activities.css">
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

    <div class="activities-container">
        <div class="content-scrollable">
            <div class="page-header">
                <h1>🎓 School Activities & Events</h1>
                <div style="display: flex; gap: 12px;">
                    <button class="btn-primary" onclick="openModal('addActivityModal')">+ Add Activity</button>
                    <button class="btn-primary" onclick="openModal('addEventModal')">+ Add Event</button>
                </div>
            </div>

        <!-- Quick Event Buttons -->
        <div class="quick-events-section">
            <h2 style="margin-bottom: 16px; color: var(--text-700);">🎉 Quick Event Setup</h2>
            <div class="quick-events-grid">
                <button class="quick-event-btn" onclick="quickAddEvent('Enrollment', 'Ceremony', 'Student enrollment and registration ceremony')">
                    <div class="quick-event-icon">📝</div>
                    <div class="quick-event-title">Enrollment</div>
                    <div class="quick-event-desc">Student registration ceremony</div>
                </button>
                <button class="quick-event-btn" onclick="quickAddEvent('Sports Fest', 'Festival', 'Annual sports festival with competitions and awards')">
                    <div class="quick-event-icon">🏆</div>
                    <div class="quick-event-title">Sports Fest</div>
                    <div class="quick-event-desc">Annual sports festival</div>
                </button>
                <button class="quick-event-btn" onclick="quickAddEvent('ALCU', 'Conference', 'Academic Leadership and Cultural Unity conference')">
                    <div class="quick-event-icon">🎓</div>
                    <div class="quick-event-title">ALCU</div>
                    <div class="quick-event-desc">Academic Leadership Conference</div>
                </button>
                <button class="quick-event-btn" onclick="quickAddEvent('KNS Night', 'Social', 'Knowledge, Networking, and Social night event')">
                    <div class="quick-event-icon">🌙</div>
                    <div class="quick-event-title">KNS Night</div>
                    <div class="quick-event-desc">Knowledge & Networking Night</div>
                </button>
                <button class="quick-event-btn" onclick="quickAddEvent('Valentines Day', 'Social', 'Valentine\'s Day celebration and social event')">
                    <div class="quick-event-icon">💕</div>
                    <div class="quick-event-title">Valentines Day</div>
                    <div class="quick-event-desc">Valentine\'s Day celebration</div>
                </button>
                <button class="quick-event-btn" onclick="quickAddEvent('Graduation', 'Ceremony', 'Graduation ceremony for graduating students')">
                    <div class="quick-event-icon">🎓</div>
                    <div class="quick-event-title">Graduation</div>
                    <div class="quick-event-desc">Graduation ceremony</div>
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card activities">
                <h3>📚 Total Activities</h3>
                <div class="stat-value"><?php echo $stats['total_activities']; ?></div>
                <div class="stat-subtitle">All school activities</div>
            </div>
            <div class="stat-card events">
                <h3>🎉 Total Events</h3>
                <div class="stat-value"><?php echo $stats['total_events']; ?></div>
                <div class="stat-subtitle">All school events</div>
            </div>
            <div class="stat-card upcoming">
                <h3>📅 Upcoming</h3>
                <div class="stat-value"><?php echo $stats['upcoming_activities'] + $stats['upcoming_events']; ?></div>
                <div class="stat-subtitle">Activities & events (30 days)</div>
            </div>
            <div class="stat-card ongoing">
                <h3>🔄 Ongoing</h3>
                <div class="stat-value"><?php echo $stats['ongoing_activities']; ?></div>
                <div class="stat-subtitle">Currently running activities</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Upcoming Activities -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📚 Upcoming Activities</h3>
                    <a href="?view=activities" class="btn-secondary">View All</a>
                </div>
                <div class="upcoming-list">
                    <?php if ($upcomingActivities && $upcomingActivities->num_rows > 0): ?>
                        <?php while ($activity = $upcomingActivities->fetch_assoc()): ?>
                            <div class="upcoming-item">
                                <div class="upcoming-info">
                                    <div class="upcoming-name"><?php echo htmlspecialchars($activity['activity_name']); ?></div>
                                    <div class="upcoming-details">
                                        <?php echo htmlspecialchars($activity['activity_type']); ?> • 
                                        <?php echo htmlspecialchars($activity['location']); ?> • 
                                        <span class="status-<?php echo $activity['status']; ?>"><?php echo ucfirst($activity['status']); ?></span>
                                    </div>
                                </div>
                                <div class="upcoming-date">
                                    <?php echo date('M j', strtotime($activity['start_date'])); ?><br>
                                    <small><?php echo date('g:i A', strtotime($activity['start_time'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: var(--text-500);">
                            No upcoming activities
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">🎉 Upcoming Events</h3>
                    <a href="?view=events" class="btn-secondary">View All</a>
                </div>
                <div class="upcoming-list">
                    <?php if ($upcomingEvents && $upcomingEvents->num_rows > 0): ?>
                        <?php while ($event = $upcomingEvents->fetch_assoc()): ?>
                            <div class="upcoming-item">
                                <div class="upcoming-info">
                                    <div class="upcoming-name"><?php echo htmlspecialchars($event['event_name']); ?></div>
                                    <div class="upcoming-details">
                                        <?php echo htmlspecialchars($event['event_type']); ?> • 
                                        <?php echo htmlspecialchars($event['location']); ?> • 
                                        <span class="status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span>
                                    </div>
                                </div>
                                <div class="upcoming-date">
                                    <?php echo date('M j', strtotime($event['event_date'])); ?><br>
                                    <small><?php echo date('g:i A', strtotime($event['start_time'])); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 20px; color: var(--text-500);">
                            No upcoming events
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Activities Table -->
        <?php if (isset($_GET['view']) && $_GET['view'] === 'activities'): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📚 All Activities</h3>
                <a href="school_activities.php" class="btn-secondary">← Back</a>
            </div>
            <table class="data-table">
                <thead>
                        <tr>
                            <th>Activity Name</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>Location</th>
                            <th>Medical Team</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                </thead>
                <tbody>
                    <?php
                    $allActivities = $conn->query("SELECT * FROM school_activities ORDER BY start_date DESC, start_time DESC");
                    while ($activity = $allActivities->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($activity['activity_name']); ?></td>
                            <td><?php echo htmlspecialchars($activity['activity_type']); ?></td>
                            <td>
                                <?php echo date('M j, Y', strtotime($activity['start_date'])); ?><br>
                                <small><?php echo date('g:i A', strtotime($activity['start_time'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($activity['location']); ?></td>
                            <td>
                                <?php if ($activity['medical_team_assigned']): ?>
                                    <span style="color: #10b981; font-weight: 600;">🏥 Assigned</span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">❌ Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-<?php echo $activity['status']; ?>"><?php echo ucfirst($activity['status']); ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit_activity=<?php echo $activity['id']; ?>" class="btn-secondary">Edit</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Events Table -->
        <?php if (isset($_GET['view']) && $_GET['view'] === 'events'): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🎉 All Events</h3>
                <a href="school_activities.php" class="btn-secondary">← Back</a>
            </div>
            <table class="data-table">
                <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Medical Team</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                </thead>
                <tbody>
                    <?php
                    $allEvents = $conn->query("SELECT * FROM school_events ORDER BY event_date DESC, start_time DESC");
                    while ($event = $allEvents->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                            <td><?php echo htmlspecialchars($event['event_type']); ?></td>
                            <td>
                                <?php echo date('M j, Y', strtotime($event['event_date'])); ?><br>
                                <small><?php echo date('g:i A', strtotime($event['start_time'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                            <td>
                                <?php if ($event['medical_team_assigned']): ?>
                                    <span style="color: #10b981; font-weight: 600;">🏥 Assigned</span>
                                <?php else: ?>
                                    <span style="color: #6b7280;">❌ Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit_event=<?php echo $event['id']; ?>" class="btn-secondary">Edit</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Activity Modal -->
    <div id="addActivityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $editActivity ? 'Edit Activity' : 'Add New Activity'; ?></h2>
                <span class="close" onclick="closeModal('addActivityModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editActivity ? 'update_activity' : 'add_activity'; ?>">
                <?php if ($editActivity): ?>
                    <input type="hidden" name="activity_id" value="<?php echo $editActivity['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="activity_name">Activity Name *</label>
                        <input type="text" id="activity_name" name="activity_name" required value="<?php echo htmlspecialchars($editActivity['activity_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="activity_type">Activity Type *</label>
                        <select id="activity_type" name="activity_type" required>
                            <option value="">Select Type</option>
                            <option value="Academic" <?php echo ($editActivity && $editActivity['activity_type'] === 'Academic') ? 'selected' : ''; ?>>Academic</option>
                            <option value="Sports" <?php echo ($editActivity && $editActivity['activity_type'] === 'Sports') ? 'selected' : ''; ?>>Sports</option>
                            <option value="Cultural" <?php echo ($editActivity && $editActivity['activity_type'] === 'Cultural') ? 'selected' : ''; ?>>Cultural</option>
                            <option value="Community Service" <?php echo ($editActivity && $editActivity['activity_type'] === 'Community Service') ? 'selected' : ''; ?>>Community Service</option>
                            <option value="Workshop" <?php echo ($editActivity && $editActivity['activity_type'] === 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                            <option value="Training" <?php echo ($editActivity && $editActivity['activity_type'] === 'Training') ? 'selected' : ''; ?>>Training</option>
                            <option value="Other" <?php echo ($editActivity && $editActivity['activity_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($editActivity['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date *</label>
                        <input type="date" id="start_date" name="start_date" required value="<?php echo $editActivity['start_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $editActivity['end_date'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_time">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required value="<?php echo $editActivity['start_time'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" value="<?php echo $editActivity['end_time'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($editActivity['location'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="organizer">Organizer</label>
                        <input type="text" id="organizer" name="organizer" value="<?php echo htmlspecialchars($editActivity['organizer'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="max_participants">Max Participants</label>
                        <input type="number" id="max_participants" name="max_participants" min="0" value="<?php echo $editActivity['max_participants'] ?? 0; ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="planned" <?php echo ($editActivity && $editActivity['status'] === 'planned') ? 'selected' : ''; ?>>Planned</option>
                            <option value="confirmed" <?php echo ($editActivity && $editActivity['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="ongoing" <?php echo ($editActivity && $editActivity['status'] === 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo ($editActivity && $editActivity['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($editActivity && $editActivity['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($editActivity['notes'] ?? ''); ?></textarea>
                </div>
                
                <!-- Medical Team Section -->
                <div style="border-top: 2px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
                    <h3 style="color: var(--text-700); margin-bottom: 16px;">🏥 Medical Team Information</h3>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="medical_team_assigned" value="1" <?php echo ($editActivity && $editActivity['medical_team_assigned']) ? 'checked' : ''; ?>>
                            Medical team assigned to this activity
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="medical_team_notes">Medical Team Notes</label>
                        <textarea id="medical_team_notes" name="medical_team_notes" rows="3" placeholder="Special medical requirements, health considerations, etc."><?php echo htmlspecialchars($editActivity['medical_team_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medical_equipment_needed">Medical Equipment Needed</label>
                        <input type="text" id="medical_equipment_needed" name="medical_equipment_needed" placeholder="e.g., First aid kit, AED, stretcher, ice packs" value="<?php echo htmlspecialchars($editActivity['medical_equipment_needed'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_aid_station">First Aid Station Location</label>
                            <input type="text" id="first_aid_station" name="first_aid_station" placeholder="e.g., Main Auditorium - Stage Area" value="<?php echo htmlspecialchars($editActivity['first_aid_station'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="emergency_contact">Emergency Contact</label>
                            <input type="text" id="emergency_contact" name="emergency_contact" placeholder="e.g., Emergency: 911, School Nurse: (555) 123-4567" value="<?php echo htmlspecialchars($editActivity['emergency_contact'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addActivityModal')">Cancel</button>
                    <button type="submit" class="btn-primary"><?php echo $editActivity ? 'Update Activity' : 'Add Activity'; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div id="addEventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><?php echo $editEvent ? 'Edit Event' : 'Add New Event'; ?></h2>
                <span class="close" onclick="closeModal('addEventModal')">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editEvent ? 'update_event' : 'add_event'; ?>">
                <?php if ($editEvent): ?>
                    <input type="hidden" name="event_id" value="<?php echo $editEvent['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_name">Event Name *</label>
                        <input type="text" id="event_name" name="event_name" required value="<?php echo htmlspecialchars($editEvent['event_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="event_type">Event Type *</label>
                        <select id="event_type" name="event_type" required>
                            <option value="">Select Type</option>
                            <option value="Ceremony" <?php echo ($editEvent && $editEvent['event_type'] === 'Ceremony') ? 'selected' : ''; ?>>Ceremony</option>
                            <option value="Festival" <?php echo ($editEvent && $editEvent['event_type'] === 'Festival') ? 'selected' : ''; ?>>Festival</option>
                            <option value="Competition" <?php echo ($editEvent && $editEvent['event_type'] === 'Competition') ? 'selected' : ''; ?>>Competition</option>
                            <option value="Exhibition" <?php echo ($editEvent && $editEvent['event_type'] === 'Exhibition') ? 'selected' : ''; ?>>Exhibition</option>
                            <option value="Conference" <?php echo ($editEvent && $editEvent['event_type'] === 'Conference') ? 'selected' : ''; ?>>Conference</option>
                            <option value="Fundraiser" <?php echo ($editEvent && $editEvent['event_type'] === 'Fundraiser') ? 'selected' : ''; ?>>Fundraiser</option>
                            <option value="Social" <?php echo ($editEvent && $editEvent['event_type'] === 'Social') ? 'selected' : ''; ?>>Social</option>
                            <option value="Other" <?php echo ($editEvent && $editEvent['event_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($editEvent['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="event_date">Event Date *</label>
                        <input type="date" id="event_date" name="event_date" required value="<?php echo $editEvent['event_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="start_time">Start Time *</label>
                        <input type="time" id="start_time" name="start_time" required value="<?php echo $editEvent['start_time'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" value="<?php echo $editEvent['end_time'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($editEvent['location'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="organizer">Organizer</label>
                        <input type="text" id="organizer" name="organizer" value="<?php echo htmlspecialchars($editEvent['organizer'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="max_participants">Max Participants</label>
                        <input type="number" id="max_participants" name="max_participants" min="0" value="<?php echo $editEvent['max_participants'] ?? 0; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="planned" <?php echo ($editEvent && $editEvent['status'] === 'planned') ? 'selected' : ''; ?>>Planned</option>
                            <option value="confirmed" <?php echo ($editEvent && $editEvent['status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo ($editEvent && $editEvent['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo ($editEvent && $editEvent['status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($editEvent['notes'] ?? ''); ?></textarea>
                </div>
                
                <!-- Medical Team Section -->
                <div style="border-top: 2px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
                    <h3 style="color: var(--text-700); margin-bottom: 16px;">🏥 Medical Team Information</h3>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="medical_team_assigned" value="1" <?php echo ($editEvent && $editEvent['medical_team_assigned']) ? 'checked' : ''; ?>>
                            Medical team assigned to this event
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label for="medical_team_notes">Medical Team Notes</label>
                        <textarea id="medical_team_notes" name="medical_team_notes" rows="3" placeholder="Special medical requirements, health considerations, etc."><?php echo htmlspecialchars($editEvent['medical_team_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medical_equipment_needed">Medical Equipment Needed</label>
                        <input type="text" id="medical_equipment_needed" name="medical_equipment_needed" placeholder="e.g., First aid kit, AED, stretcher, ice packs" value="<?php echo htmlspecialchars($editEvent['medical_equipment_needed'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_aid_station">First Aid Station Location</label>
                            <input type="text" id="first_aid_station" name="first_aid_station" placeholder="e.g., Main Auditorium - Stage Area" value="<?php echo htmlspecialchars($editEvent['first_aid_station'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="emergency_contact">Emergency Contact</label>
                            <input type="text" id="emergency_contact" name="emergency_contact" placeholder="e.g., Emergency: 911, School Nurse: (555) 123-4567" value="<?php echo htmlspecialchars($editEvent['emergency_contact'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                    <button type="button" class="btn-secondary" onclick="closeModal('addEventModal')">Cancel</button>
                    <button type="submit" class="btn-primary"><?php echo $editEvent ? 'Update Event' : 'Add Event'; ?></button>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script>
        // Override closeModal for school activities-specific functionality
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            // Clear form if it's add modal
            if (modalId.includes('Modal')) {
                document.querySelector('#' + modalId + ' form').reset();
            }
        }
        
        // Auto-open edit modal if editing
        <?php if ($editActivity): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('addActivityModal');
        });
        <?php endif; ?>
        
        <?php if ($editEvent): ?>
        document.addEventListener('DOMContentLoaded', function() {
            openModal('addEventModal');
        });
        <?php endif; ?>
        
        function quickAddEvent(eventName, eventType, description) {
            // Pre-fill the event form with the selected event details
            document.getElementById('event_name').value = eventName;
            document.getElementById('event_type').value = eventType;
            document.getElementById('description').value = description;
            
            // Set default values
            const today = new Date();
            const nextWeek = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
            
            document.getElementById('event_date').value = nextWeek.toISOString().split('T')[0];
            document.getElementById('start_time').value = '18:00';
            document.getElementById('end_time').value = '21:00';
            document.getElementById('location').value = 'School Auditorium';
            document.getElementById('organizer').value = 'Event Committee';
            document.getElementById('max_participants').value = '200';
            document.getElementById('status').value = 'planned';
            document.getElementById('notes').value = 'Quick event setup - please review and update details as needed.';
            
            // Set medical team defaults
            document.querySelector('input[name="medical_team_assigned"]').checked = true;
            document.getElementById('medical_team_notes').value = 'Medical team required for event safety and health monitoring.';
            document.getElementById('medical_equipment_needed').value = 'First aid kit, emergency contact list';
            document.getElementById('first_aid_station').value = 'School Auditorium - Stage Area';
            document.getElementById('emergency_contact').value = 'Emergency: 911, School Nurse: (555) 123-4567';
            
            // Open the modal
            openModal('addEventModal');
        }
        
        // School activities-specific functionality - common functions are in common.js
    </script>
    
    <!-- Module Highlighting Script -->
    <script src="js/module-highlighting.js"></script>
</body>
</html>