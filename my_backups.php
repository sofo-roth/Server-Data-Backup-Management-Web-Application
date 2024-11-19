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

try {
    // Prepare and execute the SQL query to fetch connections
    $stmt = $pdo->prepare("SELECT connection_id, connection_name, host, db_username, db_name FROM user_db_connections WHERE email = ?");
    $stmt->execute([$email]);
    $connections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching connections: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Saved Connections</title>
    <link rel="stylesheet" href="table_styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <nav class="menu">
        <ul>
            <li><a href="dashboard.php">HOME</a></li>
            <li><a href="new_db_connection.php">New DB connection</a></li>
            <li><a href="my_connections.php">My connections</a></li>
            <li><a href="my_backups.php">My Backup Files</a></li>
            <li><a href="delete_user.php" class="logout-btn">DELETE USER</a></li> <!-- Added Delete User -->
        </ul>
        <ul class="user-info">
            <li class="logged-in">Logged in as: <?php echo htmlspecialchars($username); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>
    <div class="wrapper">
        <h2>Your Backup Files</h2>

        <table id="backupTable">
            <thead>
                <tr>
                    <th>Backup File Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Backup files will be populated here -->
            </tbody>
        </table>

<!-- SweetAlert2 CDN -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Fetch backup files using AJAX
            fetch('list_backups.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tableBody = document.querySelector('#backupTable tbody');
                        data.backupFiles.forEach(file => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${file}</td>
                                <td>
                                    <!-- Download button without the href, just a button -->
                                    <button class="button_download" data-file="${file}">Download</button>
                                    
                                    <!-- Delete button -->
                                    <button class="button_delete" data-file="${file}">Delete</button>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });

                        // Add event listeners to the download buttons
                        document.querySelectorAll('.button_download').forEach(button => {
                            button.addEventListener('click', (e) => {
                                const fileToDownload = e.target.getAttribute('data-file');
                                downloadBackupFile(fileToDownload);
                            });
                        });

                        // Add event listeners to the delete buttons
                        document.querySelectorAll('.button_delete').forEach(button => {
                            button.addEventListener('click', (e) => {
                                const fileToDelete = e.target.getAttribute('data-file');
                                deleteBackupFile(fileToDelete);
                            });
                        });
                    } else {
                        alert(data.message || 'Failed to fetch backup files.');
                    }
                })
                .catch(error => alert('Error fetching backup files: ' + error));

            // Function to handle downloading a backup file
            function downloadBackupFile(file) {
                const link = document.createElement('a');
                link.href = 'download_backup.php?file=' + file;
                link.download = file;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            // Function to delete a backup file
            function deleteBackupFile(file) {
                if (confirm('Are you sure you want to delete this backup?')) {
                    fetch('delete_backup.php', {
                    method: 'POST',
                    body: JSON.stringify({ file }),
                    headers: { 'Content-Type': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Deleted!', data.message, 'success');
                        const row = document.querySelector(`button[data-file="${file}"]`).closest('tr');
                        row.remove();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete the file', 'error');
                    }
                })
                .catch(error => Swal.fire('Error', 'An unexpected error occurred: ' + error, 'error'));
                }
            }
        </script>
    </div>
</body>
</html>
