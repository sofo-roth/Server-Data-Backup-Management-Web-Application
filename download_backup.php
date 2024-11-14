<?php
// Include your database connection
include 'db_connection.php';
session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Validate and get the file name from the query parameter
if (isset($_GET['file'])) {
    $file = $_GET['file'];
    $userFolder = 'backups/' . preg_replace('/[^a-zA-Z0-9]/', '_', $_SESSION['email']);
    $filePath = $userFolder . '/' . $file;

    // Check if the file exists
    if (file_exists($filePath)) {
        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file specified for download.']);
}
?>
