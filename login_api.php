<?php
// Include your database connection
include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start a session to manage user login state

header('Content-Type: application/json'); // Set content type to JSON

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $input = json_decode(file_get_contents('php://input'), true);

    // Check if json_decode was successful
    if ($input === null) {
        echo json_encode(['success' => false, 'message' => 'Invalid input format']);
        exit;
    }

    $username = trim($input['username'] ?? ''); // Use null coalescing operator
    $password = trim($input['password'] ?? '');

    // Validate inputs
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required!']);
        exit;
    }

    // Prepare and execute the query to find the user
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password
        if ($user && password_verify($password, $user['passwd'])) {
            // Store user information in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Return success response
            echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => ['id' => $user['id'], 'username' => $user['username']]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Username or Password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
