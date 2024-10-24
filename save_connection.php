<?php
session_start(); // Start session to get the logged-in user ID

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in first']);
    exit;
}

include 'db_connection.php'; // Include the database connection

// Define the encryption key (ideally, store this securely outside the script)
define('ENCRYPTION_KEY', 'your-secure-encryption-key');

// Function to encrypt the password
function encryptPassword($plainPassword) {
    $encryptedPassword = openssl_encrypt($plainPassword, 'AES-128-ECB', ENCRYPTION_KEY);
    return $encryptedPassword;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the data from the form submission
    $user_id = $_SESSION['email']; // Get the logged-in user's ID from the session
    $connection_name = trim($_POST['connection_name']);
    $host = trim($_POST['host']);
    $db_username = trim($_POST['db_username']);
    $db_password = trim($_POST['db_password']);
    $db_name = trim($_POST['db_name']);

    // Validate inputs
    if (empty($connection_name) || empty($host) || empty($db_username) || empty($db_password) || empty($db_name)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    // Encrypt the database password before saving it
    $encryptedPassword = encryptPassword($db_password);

    // Generate a web token (64-character random string)
    $webtoken = bin2hex(random_bytes(32));

    // Prepare and execute the SQL query to insert the connection
    try {
        $stmt = $pdo->prepare("INSERT INTO user_db_connections (email, connection_name, host, db_username, db_password, db_name, webtoken) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $connection_name, $host, $db_username, $encryptedPassword, $db_name, $webtoken]);

        // Return a success message
        echo json_encode(['success' => true, 'message' => 'Database connection saved successfully', 'webtoken' => $webtoken]);
        $_SESSION['success_message'] = 'Database connection saved successfully';
        header("Location: my_connections.php");
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
?>
