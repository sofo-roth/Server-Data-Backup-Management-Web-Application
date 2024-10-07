<?php
session_start(); // Start the session

// Unset all of the session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page with a logout message
header("Location: login.php?message=logout_successful");
exit;
?>
