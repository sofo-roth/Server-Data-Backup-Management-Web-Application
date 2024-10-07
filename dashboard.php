<?php
session_start(); // Start the session

// Check if the user is logged in by verifying the session variable
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit;
}

$username = $_SESSION['username']; // Get the username from the session
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
            <li><a href="home.php">Home</a></li>
            <li><a href="news.php">News</a></li>
            <li><a href="contact.php">Contact</a></li>
        </ul>
        <ul class="user-info">
            <li class="logged-in">Logged in as:   <?php echo htmlspecialchars($username); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>
</body>
</html>
