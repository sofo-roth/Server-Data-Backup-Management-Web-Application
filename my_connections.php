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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $(".backup-form").on("submit", function(event) {
        event.preventDefault(); // Prevent the default form submission

        var formData = $(this).serialize(); // Serialize the form data

        $.ajax({
            type: "POST",
            url: "backup.php",
            data: formData,
            dataType: "json",
            success: function(response) {
                console.log(response); // Debugging: check the full response object
                if (response.success) {
                    alert(response.message); // Display success message
                } else {
                    alert("Error: " + response.message); // Display error message
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error: ", status, error); // Log any AJAX errors
                console.error("Response Text:", xhr.responseText); // Display the response text
                alert("An unexpected error occurred: " + xhr.responseText); // Show full error details
            }
        });
    });
});
</script>
</body>
</html>
