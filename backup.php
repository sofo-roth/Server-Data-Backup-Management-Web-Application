<?php
// Include your database connection
include 'db_connection.php';

session_start(); // Start the session

date_default_timezone_set('Europe/Athens'); // Replace with your local timezone

// Check if the user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

define('ENCRYPTION_KEY', 'your-secure-encryption-key');

// Function to decrypt the encrypted password
function decryptPassword($encryptedPassword) {
    return openssl_decrypt($encryptedPassword, 'AES-128-ECB', ENCRYPTION_KEY);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $connectionId = $_POST['connection_id'];
    $backupType = $_POST['backup_type'];

    try {
        // Fetch connection details from the database
        $stmt = $pdo->prepare("SELECT host, db_username, db_password, db_name, connection_name, email FROM user_db_connections WHERE connection_id = ?");
        $stmt->execute([$connectionId]);
        $connectionDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($connectionDetails) {
            $host = $connectionDetails['host'];
            $username = $connectionDetails['db_username'];
            $encryptedPassword = $connectionDetails['db_password'];
            $database = $connectionDetails['db_name'];
            $connectionName = $connectionDetails['connection_name'];
            $email = $connectionDetails['email'];
            $password = decryptPassword($encryptedPassword);

            // Generate a unique user folder based on email
            $userFolder = 'backups/' . preg_replace('/[^a-zA-Z0-9]/', '_', $email);
            if (!is_dir($userFolder)) {
                mkdir($userFolder, 0777, true);
            }

            // Generate a unique folder for the connection inside the user folder
            $connectionFolder = $userFolder . '/' . preg_replace('/[^a-zA-Z0-9]/', '_', $connectionName);
            if (!is_dir($connectionFolder)) {
                mkdir($connectionFolder, 0777, true);
            }

            // Generate a standardized backup file name with timestamp
            $timestamp = date("Y-m-d_H-i-s"); // Include date and time
            $backupFileName = $database . "_" . $backupType . "_backup_" . $timestamp . ".sql";
            $backupFilePath = $connectionFolder . "/" . $backupFileName;

            // Backup logic
            $command = '';
            if ($backupType === 'full') {
                $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
            } elseif ($backupType === 'incremental') {
                echo json_encode(['success' => false, 'message' => 'Incremental backups require binary logging to be enabled.']);
                exit;
            } elseif ($backupType === 'differential') {
                echo json_encode(['success' => false, 'message' => 'Differential backups logic is yet to be implemented.']);
                exit;
            } elseif ($backupType === 'snapshot') {
                echo json_encode(['success' => false, 'message' => 'Snapshot backups logic is yet to be implemented.']);
                exit;
            }

            // Execute the backup command
            exec($command . " 2>&1", $output, $return_var);

            if ($return_var === 0) {
                // Insert a log entry into the backup_logs table
                $stmt = $pdo->prepare("INSERT INTO backup_logs (file_name, email, backup_type, last_backup) VALUES (:file_name, :email, :backup_type, NOW())");
                $stmt->execute([
                    ':file_name' => $backupFileName,
                    ':email' => $email,
                    ':backup_type' => $backupType
                ]);

                echo json_encode(['success' => true, 'message' => "Backup successful. Backup saved to: " . htmlspecialchars($backupFilePath)]);
            } else {
                echo json_encode(['success' => false, 'message' => "Error executing backup command: " . implode("\n", $output)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Connection details not found for the given ID."]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    }
}

exit;
