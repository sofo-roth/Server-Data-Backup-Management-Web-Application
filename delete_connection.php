<?php
// Include your database connection
include 'db_connection.php';

session_start(); // Start the session

// Ensure the user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$email = $_SESSION['email']; // Logged-in user's email

// Check if connection ID and password are provided
if (!isset($_POST['connection_id'], $_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Missing connection ID or password.']);
    exit;
}

$connectionId = $_POST['connection_id'];
$password = $_POST['password'];

try {
    // Fetch the user's hashed password from the database
    $stmt = $pdo->prepare("SELECT passwd FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verify password
    if (!$user || !password_verify($password, $user['passwd'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit;
    }

    // If password is correct, delete the connection
    $deleteStmt = $pdo->prepare("DELETE FROM user_db_connections WHERE connection_id = ? AND email = ?");
    $deleteStmt->execute([$connectionId, $email]);

    // Check if any rows were affected (connection was deleted)
    if ($deleteStmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Connection deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Connection not found or already deleted.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
