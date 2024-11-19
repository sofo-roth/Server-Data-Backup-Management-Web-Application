<?php
// Include your database connection
require_once 'db_connection.php';

// Start the session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Parse JSON input
$data = json_decode(file_get_contents('php://input'), true);
$fileName = $data['file'] ?? '';

if (empty($fileName)) {
    echo json_encode(['success' => false, 'message' => 'No file specified']);
    exit;
}

// User-specific backup directory
$email = $_SESSION['email'];
$safeEmail = str_replace(['@', '.'], '_', $email); // Ensure compatibility with file system
$backupDir = __DIR__ . "/backups/{$safeEmail}/"; // Use sanitized email for directory

// Full path to the file
$filePath = $backupDir . basename($fileName);

// Debugging logs
error_log("Backup Directory: " . $backupDir);
error_log("Full file path: " . $filePath);

// Check if file exists
if (!file_exists($filePath)) {
    error_log("File not found at path: " . $filePath);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// Attempt to delete the file
if (unlink($filePath)) {
    try {
        // Delete the log entry from the database
        $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE file_name = :file_name AND email = :email");
        $stmt->bindParam(':file_name', $fileName, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'File and log entry deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'File deleted, but failed to delete log entry']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'File deleted, but a database error occurred']);
    }
} else {
    error_log("Failed to delete the file at path: " . $filePath);
    echo json_encode(['success' => false, 'message' => 'Failed to delete the file']);
}

exit;
