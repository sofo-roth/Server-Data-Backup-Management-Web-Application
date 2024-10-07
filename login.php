<?php
// Include your database connection
include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start(); // Start a session to manage user login state

$data = json_decode(file_get_contents('php://input'), true);

// Check if the request method is POST for handling login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data from the POST request
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Username and password are required!']);
        exit;
    }

    // Prepare and execute the query to find the user
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password
        if ($user && password_verify($password, $user['passwd'])) {
            // Store user information in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Return success response
            echo json_encode(['success' => true, 'message' => 'Login successful', 'user' => ['id' => $user['id'], 'username' => $user['username']]]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Username or Password']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit; // End the script after processing the request
}

// Render the HTML form if the request method is not POST
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
                <i class='bx bxs-user'></i>
                <input type="text" name="username" placeholder="Enter username" required>
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
                username: $("input[name='username']").val(),
                password: $("input[name='password']").val(),
            };

            $.ajax({
                type: 'POST',
                url: 'login.php', // Point to the same file
                data: formData, // Send form data
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    if (response.success) {
                        alert('Login successful! Redirecting to dashboard...');
                        window.location.href = 'dashboard.php';
                    } else {
                        alert(response.message);
                    }
                },
                error: function(error) {
                    console.error(error);
                    alert('Error logging in');
                }
            });
        });
    </script>

</body>
</html>
