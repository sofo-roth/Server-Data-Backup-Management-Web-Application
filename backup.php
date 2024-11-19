<?php
// Include your database connection
include 'db_connection.php';

session_start(); // Start the session

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
        $stmt = $pdo->prepare("SELECT host, db_username, db_password, db_name, email FROM user_db_connections WHERE connection_id = ?");
        $stmt->execute([$connectionId]);
        $connectionDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($connectionDetails) {
            $host = $connectionDetails['host'];
            $username = $connectionDetails['db_username'];
            $encryptedPassword = $connectionDetails['db_password'];
            $database = $connectionDetails['db_name'];
            $email = $connectionDetails['email'];
            $password = decryptPassword($encryptedPassword);

            // Generate a unique user folder based on email
            $userFolder = 'backups/' . preg_replace('/[^a-zA-Z0-9]/', '_', $email);
            if (!is_dir($userFolder)) {
                mkdir($userFolder, 0777, true);
            }

            // Generate a standardized backup file name
            $backupFileName = $database . "_" . $backupType . "_backup_" . date("Y-m-d_H-i-s") . ".sql";
            $backupFilePath = $userFolder . "/" . $backupFileName;

            // Fetch the last full backup timestamp dynamically for incremental backups
            $lastBackupTimestamp = null;
            if ($backupType === 'incremental') {
                $stmt = $pdo->prepare("SELECT last_full_backup FROM backup_logs WHERE email = :email ORDER BY last_backup DESC LIMIT 1");
                $stmt->execute([':email' => $email]);
                $lastBackupTimestamp = $stmt->fetchColumn();
                
                if (!$lastBackupTimestamp) {
                    echo json_encode(['success' => false, 'message' => 'No full backup found for incremental backup.']);
                    exit;
                }
            }

            // Backup logic
            $command = '';
            if ($backupType === 'full') {
                $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
            } elseif ($backupType === 'incremental' && $lastBackupTimestamp) {
                $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " --where='updated_at > \"" . $lastBackupTimestamp . "\"' " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
            } elseif ($backupType === 'differential') {
                $stmt = $pdo->prepare("SELECT last_backup FROM backup_logs WHERE backup_type = 'full' ORDER BY last_backup DESC LIMIT 1");
                $stmt->execute();
                $lastFullBackup = $stmt->fetchColumn();
                if ($lastFullBackup) {
                    $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " --where='updated_at > \"" . $lastFullBackup . "\"' " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No full backup found for differential backup.']);
                    exit;
                }
            } elseif ($backupType === 'snapshot') {
                // Snapshot logic, can be just a full backup with some other considerations
                $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " --single-transaction --quick --lock-tables=false " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
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

                // If it's a full backup, update the last_full_backup field
                if ($backupType === 'full') {
                    $stmtUpdate = $pdo->prepare("UPDATE backup_logs SET last_full_backup = NOW() WHERE email = :email AND backup_type = 'full' ORDER BY last_backup DESC LIMIT 1");
                    $stmtUpdate->execute([':email' => $email]);
                }

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
