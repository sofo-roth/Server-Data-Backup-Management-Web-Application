<?php
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
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="styles.css">
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
            <li class="logged-in">Logged in as:   <?php echo htmlspecialchars($username); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>
    <div class="wrapper">
    <h2>Add a new Database connection</h2>
    <form id="addConnectionForm" method="POST" action="save_connection.php">
        <div class="input-box">
            <label>Connection Name</label>
            <input type="text" name="connection_name" placeholder="Enter connection name" required>
        </div>
        <div class="input-box">
            <label>Host</label>
            <input type="text" name="host" placeholder="Enter host (e.g., localhost)" required>
        </div>
        <div class="input-box">
            <label>Username</label>
            <input type="text" name="db_username" placeholder="Enter DB username" required>
        </div>
        <div class="input-box">
            <label>Password</label>
            <input type="password" name="db_password" placeholder="Enter DB password" required>
        </div>
        <div class="input-box">
            <label>Database Name</label>
            <input type="text" name="db_name" placeholder="Enter database name" required>
        </div>
        <button type="submit" class="btn">Save Connection</button>
    </form>

</div>

<script>
$('#addConnectionForm').on('submit', function(e) {
    e.preventDefault();

    const formData = {
        connection_name: $("input[name='connection_name']").val(),
        host: $("input[name='host']").val(),
        db_username: $("input[name='db_username']").val(),
        db_password: $("input[name='db_password']").val(),
        db_name: $("input[name='db_name']").val(),
    };

    $.ajax({
        type: 'POST',
        url: 'save_connection.php',  // A PHP script to save the connection
        data: formData,
        success: function(response) {
            alert(response.message);
        },
        error: function(error) {
            alert('Error saving connection');
        }
    });
});

</script>

</body>
</html>
