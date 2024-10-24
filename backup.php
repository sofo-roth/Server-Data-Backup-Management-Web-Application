<?php
// Include your database connection
include 'db_connection.php';

session_start(); // Start the session

// Check if the user is logged in by verifying the session variable
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

// Define the encryption key (the same key used during encryption)
define('ENCRYPTION_KEY', 'your-secure-encryption-key');

// Decryption function
function decryptPassword($encryptedPassword) {
    return openssl_decrypt($encryptedPassword, 'AES-128-ECB', ENCRYPTION_KEY);
}

// Handle the backup request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $connectionId = $_POST['connection_id'];
    $backupType = $_POST['backup_type'];

    // Fetch the connection details from the database
    try {
        $stmt = $pdo->prepare("SELECT host, db_username, db_password, db_name FROM user_db_connections WHERE connection_id = ?");
        $stmt->execute([$connectionId]);
        $connectionDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($connectionDetails) {
            $host = $connectionDetails['host'];
            $username = $connectionDetails['db_username'];
            $encryptedPassword = $connectionDetails['db_password'];
            $database = $connectionDetails['db_name'];

            // Decrypt the password before using it
            $password = decryptPassword($encryptedPassword);

            // Prepare the mysqldump command
            $backupFile = $database . "_backup_" . date("Y-m-d_H-i-s") . ".sql";
            $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($database) . " > " . escapeshellarg($backupFile);

            // Execute the command and capture the output and return code
            exec($command . " 2>&1", $output, $return_var);

            // Return JSON response based on the execution result
            if ($return_var === 0) {
                echo json_encode(['success' => true, 'message' => "Backup successful. Backup saved to: " . htmlspecialchars($backupFile)]);
            } else {
                echo json_encode(['success' => false, 'message' => "Error executing mysqldump: " . implode("\n", $output)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Connection details not found for the given ID."]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Error fetching connection details: " . $e->getMessage()]);
    }
}
?>
