<?php
session_start(); // Start session to manage user login state
include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the request method is POST for handling login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data from the POST request
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Email and password are required!']);
        exit;
    }

    // Prepare and execute the query to find the user by email
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password
        if ($user && password_verify($password, $user['passwd'])) {
            // Store user information in session
            $_SESSION['user_id'] = $user['email']; // Store email instead of a non-existing id
            $_SESSION['email'] = $user['email'];

            // Return success response
            echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => ['email' => $user['email']]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Email or Password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit; // End the script after processing the request
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery for AJAX -->
</head>
<body>

    <h2 class="title">BUDBs Project</h2>
    <div class="wrapper">
        <form id="loginForm" method="POST">
            <h1>Login</h1>
            <div class="input-box">
                <i class='bx bxs-envelope'></i>
                <input type="email" name="email" placeholder="Enter email" required>
            </div>
            <div class="input-box">
                <i class='bx bxs-lock-alt'></i>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn">Login</button>
            <div class="register-link">
                <p>Don't have an account? <a href="sign_up.php">Sign up here.</a></p>
            </div>
        </form>
    </div>

    <script>
        // Handle form submission with AJAX
        $('#loginForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = {
                email: $("input[name='email']").val(),
                password: $("input[name='password']").val(),
            };

            $.ajax({
    type: 'POST',
    url: 'login.php', // Ensure this points to your login PHP file
    data: formData, // Send serialized form data
    dataType: 'json', // Expect JSON response
    success: function(response) {
        console.log('Response:', response); // Log the entire response
        if (response.success) {
            alert('Login successful! Redirecting to dashboard...');
            window.location.href = 'dashboard.php'; // Ensure this is the correct path
        } else {
            alert(response.message); // Display error message
        }
    },
    error: function(jqXHR, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown); // Log detailed error
        alert('Error logging in: ' + textStatus);
    }
});

        });
    </script>

</body>
</html>
