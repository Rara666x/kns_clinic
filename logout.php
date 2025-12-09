<?php
session_start();
include('db_connection.php');

// Log logout activity before destroying session
if (isset($_SESSION['userId'])) {
    $userId = $_SESSION['userId'];
    $username = $_SESSION['username'] ?? 'Unknown';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, username, action, description, ip_address, user_agent) VALUES (?, ?, 'logout', 'User logged out', ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $ipAddress, $userAgent);
    $stmt->execute();
    $stmt->close();
}

session_unset();
session_destroy();
header("Location: login.php");
exit;
?>
