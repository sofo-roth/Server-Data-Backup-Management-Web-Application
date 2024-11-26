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

// Get the part of the email before the '@' symbol
$emailParts = explode('@', $email);
$username = $emailParts[0]; // Get the first part of the email

// Replace special characters in email for folder naming
$sanitizedEmail = str_replace(['@', '.'], '_', $email);

// Path to user-specific backup folder
$userBackupDir = "backups/{$sanitizedEmail}/";

// Function to scan backup directories and organize files
function getBackupsByConnection($baseDir) {
    $backups = [];
    if (is_dir($baseDir)) {
        $connections = array_filter(scandir($baseDir), function ($item) use ($baseDir) {
            return $item !== '.' && $item !== '..' && is_dir($baseDir . $item);
        });
        foreach ($connections as $connection) {
            $connectionDir = $baseDir . $connection . '/';
            $files = array_filter(scandir($connectionDir), function ($file) use ($connectionDir) {
                return is_file($connectionDir . $file);
            });
            $backups[$connection] = array_values($files);
        }
    }
    return $backups;
}

// Fetch backups for the user
$backups = getBackupsByConnection($userBackupDir);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Backup Files</title>
    <link rel="stylesheet" href="table_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .backup-item {
            display: flex; /* Enable flexbox */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            gap: 10px; /* Space between file name and buttons */
            padding: 10px;
            margin: 5px 0;
            list-style: none; /* Remove default list style */
            text-align: center;
        }

        /* Backup file text */
        .backup-file {
            flex: 1; /* Ensure the file name takes equal space */
            text-align: center; /* Align the file name text */
            font-size: 1rem;
        }

        /* Buttons */
        .button_download, .button_delete {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .button_download {
            background-color: #1d5421;
            color: white;
        }

        .button_download:hover {
            background-color: #3edc05;
        }

        .button_delete {
            background-color: #491f1f;
            color: white;
        }

        .button_delete:hover {
            background-color: #ff5555;
        }
    </style>
</head>
<body>
    <nav class="menu">
        <ul>
            <li><a href="dashboard.php">HOME</a></li>
            <li><a href="new_db_connection.php">New DB connection</a></li>
            <li><a href="my_connections.php">My connections</a></li>
            <li><a href="my_backups.php">My Backup Files</a></li>
            <li><a href="delete_user.php" class="logout-btn">DELETE USER</a></li>
        </ul>
        <ul class="user-info">
            <li class="logged-in">Logged in as: <?php echo htmlspecialchars($username); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="wrapper">
        <div class="backup-container">
            <?php if (!empty($backups)): ?>
                <?php foreach ($backups as $connectionName => $files): ?>
                    <div class="backup-section">
                        <h3><?php echo htmlspecialchars($connectionName); ?></h3>
                        <ul>
                            <?php foreach ($files as $file): ?>
                                <li class="backup-item">
                                    <span class="backup-file"><?php echo htmlspecialchars($file); ?></span>
                                    <button 
                                        class="download-btn button_download" 
                                        data-file="<?php echo htmlspecialchars("{$connectionName}/{$file}"); ?>">Download</button>
                                    <button 
                                        class="delete-btn button_delete" 
                                        data-file="<?php echo htmlspecialchars("{$connectionName}/{$file}"); ?>">Delete</button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No backups found for your connections.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Download backup button handler
        $(document).on('click', '.download-btn', function () {
            const filePath = $(this).data('file');
            window.location.href = 'download_backup.php?file=' + encodeURIComponent(filePath);
        });

        // Delete backup button handler
        $(document).on('click', '.delete-btn', function () {
            const filePath = $(this).data('file');

            Swal.fire({
                title: 'Are you sure?',
                text: "This will permanently delete the backup.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete_backup.php',
                        type: 'POST',
                        data: JSON.stringify({ file: filePath }),
                        contentType: 'application/json',
                        success: function (response) {
                            Swal.fire('Deleted!', response.message, 'success').then(() => location.reload());
                        },
                        error: function (xhr, status, error) {
                            Swal.fire('Error', 'An unexpected error occurred: ' + error, 'error');
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>
