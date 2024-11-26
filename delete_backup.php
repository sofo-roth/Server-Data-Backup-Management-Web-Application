<?php
// Include your database connection
include 'db_connection.php';

session_start(); // Start the session

// Check if the user is logged in by verifying the session variable
if (!isset($_SESSION['email'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

$email = $_SESSION['email']; 

// Replace special characters in email for folder naming
$sanitizedEmail = str_replace(['@', '.'], '_', $email);

// Path to user-specific backup folder
$userBackupDir = "backups/{$sanitizedEmail}/";

// Parse the incoming data
$data = json_decode(file_get_contents('php://input'), true);
$fileName = $data['file'] ?? '';

if (empty($fileName)) {
    echo json_encode(['success' => false, 'message' => 'No file specified']);
    exit;
}

// Full path to the file (accounting for subdirectories)
$filePath = $userBackupDir . $fileName;

// Attempt to delete the file
if (unlink($filePath)) {
    try {
        // Start a database transaction
        $pdo->beginTransaction();
        error_log("File {$fileName} deleted successfully. Proceeding to delete from backup_logs...");

        // Prepare the SQL statement to delete the row from the backup_logs table
        $stmt = $pdo->prepare("DELETE FROM backup_logs WHERE file_name = :file_name AND email = :email");
        $stmt->bindParam(':file_name', $fileName, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);

        // Execute the query
        $executionResult = $stmt->execute();

        // Log the result of the query execution
        if ($executionResult) {
            error_log("Database entry for file {$fileName} deleted successfully.");
        } else {
            error_log("Failed to delete database entry for file {$fileName}. Query not executed.");
        }

        // Check if the query actually deleted any row
        if ($stmt->rowCount() > 0) {
            error_log("Successfully deleted a row from backup_logs for file: {$fileName}");
        } else {
            error_log("No row found to delete in backup_logs for file: {$fileName}");
        }

        // Check if the folder is empty and delete it if empty
        $folderPath = dirname($filePath); // Get the folder containing the file
        if (is_dir_empty($folderPath)) {
            rmdir($folderPath); // Remove the folder if empty
            error_log("Deleted empty folder: " . $folderPath);
        }

        // Commit the transaction after deleting both the file and the log
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'File and log entry deleted successfully']);
    } catch (PDOException $e) {
        // Rollback in case of any database error
        $pdo->rollBack();
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'File deleted, but a database error occurred']);
    }
} else {
    // The file could not be deleted, possibly locked or in use
    error_log("Failed to delete the file at path: " . $filePath);
    echo json_encode(['success' => false, 'message' => 'Failed to delete the file']);
}

// Function to check if a directory is empty
function is_dir_empty($dir) {
    if (!is_readable($dir)) return false;
    return count(scandir($dir)) === 2; // Only "." and ".." exist in an empty folder
}
?>
