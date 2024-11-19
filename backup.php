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
                // Get the last backup timestamp from the backup_logs table
                $stmt = $pdo->prepare("SELECT last_backup FROM backup_logs WHERE email = :email ORDER BY last_backup DESC LIMIT 1");
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
            } elseif ($backupType === 'incremental') {
                // Use the last modified timestamp to back up only modified records
                $stmt = $pdo->prepare("SELECT MAX(last_modified_timestamp) FROM backup_logs WHERE last_modified_timestamp > :lastBackupTimestamp");
                $stmt->execute([':lastBackupTimestamp' => $lastBackupTimestamp]);
                $lastModifiedTimestamp = $stmt->fetchColumn();
                
                if ($lastModifiedTimestamp) {
                    // Run mysqldump with incremental changes based on modified data
                    $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " --single-transaction --quick --lock-tables=false " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No changes found for incremental backup.']);
                    exit;
                }
            } elseif ($backupType === 'differential') {
                // Retrieve the last full backup timestamp for the given email
                $stmt = $pdo->prepare("SELECT last_full_backup FROM backup_logs WHERE email = :email ORDER BY last_backup DESC LIMIT 1");
                $stmt->execute([':email' => $email]);
                $lastFullBackup = $stmt->fetchColumn();
            
                if ($lastFullBackup) {
                    // Format the last full backup timestamp for use in the SQL WHERE clause
                    $lastFullBackupDate = date('Y-m-d H:i:s', strtotime($lastFullBackup));
            
                    // Open the backup file to write the SQL
                    $backupFile = fopen($backupFilePath, 'w');
                    if ($backupFile) {
            
                        // Function to generate SQL INSERT statements for modified rows
                        function writeBackupData($pdo, $table, $lastFullBackupDate, $backupFile) {
                            $stmt = $pdo->prepare("SELECT * FROM $table WHERE last_modified_timestamp > :timestamp");
                            $stmt->execute([':timestamp' => $lastFullBackupDate]);
                            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
                            // Write each modified row as an INSERT statement
                            foreach ($data as $row) {
                                $columns = implode(", ", array_keys($row));
                                $values = implode(", ", array_map(function ($value) {
                                    return "'" . addslashes($value) . "'";
                                }, array_values($row)));
                                $insertSql = "INSERT INTO $table ($columns) VALUES ($values);\n";
                                fwrite($backupFile, $insertSql);
                            }
                        }
            
                        // Dump modified rows for the relevant tables (user_db_connections, users, backup_logs)
                        writeBackupData($pdo, 'user_db_connections', $lastFullBackupDate, $backupFile);
                        writeBackupData($pdo, 'users', $lastFullBackupDate, $backupFile);
                        writeBackupData($pdo, 'backup_logs', $lastFullBackupDate, $backupFile);
            
                        // Close the file after writing all data
                        fclose($backupFile);
            
                        echo json_encode(['success' => true, 'message' => 'Differential backup completed successfully.']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to open backup file.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'No full backup found for differential backup.']);
                }
            } elseif ($backupType === 'snapshot') {
                try {
                    // Start the transaction to ensure consistency
                    $db->beginTransaction(); 
                    
                    // Execute FLUSH TABLES WITH READ LOCK
                    $db->exec("FLUSH TABLES WITH READ LOCK");
            
                    // Create backup file path and command for mysqldump
                    $backupFilePath = '/path/to/backups/' . date('Y-m-d_H-i-s') . '_snapshot.sql';
                    
                    // Snapshot logic - Full backup without table structure and locking
                    $command = "mysqldump -u " . escapeshellarg($username) . " -p" . escapeshellarg($password) . " --single-transaction --quick --no-create-info --lock-tables=false " . escapeshellarg($database) . " > " . escapeshellarg($backupFilePath);
            
                    // Execute the backup command
                    exec($command, $output, $return_var);
            
                    // Check if mysqldump was successful
                    if ($return_var !== 0) {
                        throw new Exception("Snapshot backup failed. Error: " . implode("\n", $output));
                    }
            
                    // Commit the transaction to release the lock
                    $db->exec("UNLOCK TABLES");
            
                    // Success message
                    echo "Snapshot backup completed successfully. Backup file: $backupFilePath";
            
                } catch (Exception $e) {
                    // Rollback if any error occurs
                    $db->exec("ROLLBACK");
                    echo "Error: " . $e->getMessage();
                }
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
