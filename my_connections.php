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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        <h2>Saved Database Connections</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Connection Name</th>
                        <th>Host</th>
                        <th>Username</th>
                        <th>Database Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($connections) > 0): ?>
                        <?php foreach ($connections as $connection): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($connection['connection_name']); ?></td>
                                <td><?php echo htmlspecialchars($connection['host']); ?></td>
                                <td><?php echo htmlspecialchars($connection['db_username']); ?></td>
                                <td><?php echo htmlspecialchars($connection['db_name']); ?></td>
                                <td>
                                    <div class="button-container">  <!-- New container for buttons -->
                                        <form class="backup-form" action="backup.php" method="POST">
                                            <input type="hidden" name="connection_id" value="<?php echo htmlspecialchars($connection['connection_id']); ?>">
                                            <select name="backup_type" required>
                                                <option value="" disabled selected>Select Backup Type</option>
                                                <option value="full">Full Backup</option>
                                                <option value="incremental">Incremental Backup</option>
                                                <option value="differential">Differential Backup</option>
                                                <option value="snapshot">Snapshot Backup</option>
                                            </select>
                                            <button type="submit">Backup</button>
                                        </form>
                                        <!-- Make sure the class matches the event listener and data-connection-id is set correctly -->
                                        <button type="button" class="delete-btn" data-connection-id="<?php echo $connection['connection_id']; ?>">Delete Connection</button> 
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No connections found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Password Prompt Modal -->
    <div id="password-modal" style="display:none;">
        <div class="wrapper">
            <div class="modal-content">
                <h3>Please enter your password to confirm deletion</h3>
                <form id="password-form" method="POST">
                    <div class="input-box">
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>    
                    <button type="submit">Confirm</button>
                    <button type="button" onclick="closePasswordModal()">Cancel</button>
                </form>
                <p id="password-error" style="color:red; display:none;">Incorrect password. Try again.</p>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
    // Event listener for delete button click
    $(document).on("click", ".delete-btn", function() {
        var connectionId = $(this).data('connection-id'); // Use data-connection-id attribute
        $('#password-modal').data('connectionId', connectionId).show(); // Store the connection id in the modal
    });

    // Handle password form submission
    $("#password-form").on("submit", function(event) {
        event.preventDefault(); // Prevent the default form submission

        var password = $("input[name='password']").val();
        var connectionId = $('#password-modal').data('connectionId'); // Get the connection id from modal data

        $.ajax({
            type: "POST",
            url: "delete_connection.php",  // URL for password verification and connection deletion
            data: { password: password, connection_id: connectionId },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: response.message,  // Show the message returned from PHP
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload(); // Optionally reload the page after user confirms
                        }
                    });
                } else {
                    $("#password-error").show(); // Show the error message if password is incorrect
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Cancel button to close the modal
    $("button[type='button']").on("click", function() {
        $('#password-modal').hide();
        $("#password-error").hide(); // Hide the error message
    });

    // Handle the backup form submission and show the result in a SweetAlert popup
    $(".backup-form").on("submit", function(event) {
        event.preventDefault(); // Prevent the default form submission

        var form = $(this);
        var connectionId = form.find("input[name='connection_id']").val();
        var backupType = form.find("select[name='backup_type']").val();

        $.ajax({
            type: "POST",
            url: "backup.php",  // URL for handling backup
            data: { connection_id: connectionId, backup_type: backupType },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Backup Successful!',
                        text: response.message,  // Show the success message returned from PHP
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: response.message,  // Show the error message returned from PHP
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An unexpected error occurred. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});
    </script>
</body>
</html>

