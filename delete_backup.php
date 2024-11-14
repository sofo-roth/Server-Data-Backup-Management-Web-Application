<?php
// Start the session if it's not already started
session_start();

// Include the database connection file
require_once 'db_connection.php'; // Adjust path as necessary

// Check if the user is authenticated (optional based on your app's setup)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the file name from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$fileName = $data['file'] ?? '';

if (!$fileName) {
    echo json_encode(['success' => false, 'message' => 'No file specified']);
    exit;
}

// Define the directory where backups are stored
$user_id = $_SESSION['user_id']; // Example user-specific directory
$backupDir = __DIR__ . "/backups/user_$user_id/";

// Full path to the backup file
$filePath = $backupDir . basename($fileName);

// Delete the file if it exists
$fileDeleted = false;
if (file_exists($filePath)) {
    $fileDeleted = unlink($filePath);
}

// Delete the database log entry if the file was successfully deleted
if ($fileDeleted) {
    // Prepare the SQL query to delete the log entry
    $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE file_name = :file_name AND user_id = :user_id");
    $stmt->bindParam(':file_name', $fileName);
    $stmt->bindParam(':user_id', $user_id);
    $logDeleted = $stmt->execute();

    if ($logDeleted) {
        echo json_encode(['success' => true, 'message' => 'File and log entry deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'File deleted, but failed to delete log entry']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete the file or file not found']);
}

exit;
