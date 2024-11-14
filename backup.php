<?php
// Include your database connection
include 'db_connection.php';

session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

define('ENCRYPTION_KEY', 'your-secure-encryption-key');

function decryptPassword($encryptedPassword) {
    return openssl_decrypt($encryptedPassword, 'AES-128-ECB', ENCRYPTION_KEY);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $connectionId = $_POST['connection_id'];
    $backupType = $_POST['backup_type'];

    try {
        $stmt = $pdo->prepare("SELECT host, db_username, db_password, db_name, email FROM user_db_connections WHERE connection_id = ?");
        $stmt->execute([$connectionId]);
        $connectionDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($connectionDetails) {
            $host = $connectionDetails['host'];
            $username = $connectionDetails['db_username'];
            $encryptedPassword = $connectionDetails['db_password'];
            $database = $connectionDetails['db_name'];
            $email = $connectionDetails['email']; // Get the user's email
            $password = decryptPassword($encryptedPassword);

            // Generate a unique folder for each user based on their email
            $userFolder = 'backups/' . preg_replace('/[^a-zA-Z0-9]/', '_', $email);  // Replace non-alphanumeric characters to avoid issues
            if (!is_dir($userFolder)) {
                mkdir($userFolder, 0777, true); // Create the folder if it doesn't exist
            }

            // Generate a different backup file name based on the backup type
            $backupFile = $userFolder . "/" . $database . "_" . $backupType . "_backup_" . date("Y-m-d_H-i-s") . ".sql";

            // Backup logic
            if ($backupType === 'full') {
                // Full backup
                $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " " . escapeshellarg($database) . " > " . escapeshellarg($backupFile);
            } elseif ($backupType === 'incremental') {
                // Incremental backup using binary logs (you will need to track binary log positions)
                $lastBinlogPosition = '1234'; // Replace with actual position tracking
                $command = "mysqlbinlog --start-position=" . escapeshellarg($lastBinlogPosition) . " --stop-never --result-file=" . escapeshellarg($backupFile) . " /path/to/mysql-bin.000001";
            } elseif ($backupType === 'differential') {
                // Differential backup logic
                $stmt = $pdo->prepare("SELECT last_full_backup FROM backup_logs WHERE backup_type = 'full' ORDER BY last_backup DESC LIMIT 1");
                $stmt->execute();
                $lastFullBackup = $stmt->fetchColumn();
                
                if ($lastFullBackup) {
                    $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " --where='updated_at > \"" . $lastFullBackup . "\"' " . escapeshellarg($database) . " > " . escapeshellarg($backupFile);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No full backup found for differential backup.']);
                    exit;
                }
            } elseif ($backupType === 'snapshot') {
                // Snapshot backup logic (you can replace this with actual snapshot commands)
                $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " --single-transaction --lock-tables=false " . escapeshellarg($database) . " > " . escapeshellarg($backupFile);
            }

            // Execute the backup command
            exec($command . " 2>&1", $output, $return_var);

            if ($return_var === 0) {
                // Insert a record into the backup_logs table
                $stmt = $pdo->prepare("INSERT INTO backup_logs (backup_type, last_backup, last_full_backup) VALUES (?, NOW(), NOW())");
                    if ($backupType === 'full') {
                        $stmt->execute(['full']);
                    } elseif ($backupType === 'incremental') {
                        $stmt->execute(['incremental']);
                    } elseif ($backupType === 'differential') {
                        $stmt->execute(['differential']);
                    } elseif ($backupType === 'snapshot') {
                        $stmt->execute(['snapshot']);
                    }

                echo json_encode(['success' => true, 'message' => "Backup successful. Backup saved to: " . htmlspecialchars($backupFile)]);
            } else {
                echo json_encode(['success' => false, 'message' => "Error executing mysqldump: " . implode("\n", $output)]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Connection details not found for the given ID."]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Database error: " . $e->getMessage()]);
    }
}
?>
