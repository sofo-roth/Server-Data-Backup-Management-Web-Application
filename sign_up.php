<?php
// Include your database connection
include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the request method is POST for handling signup
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data from JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $username = trim($input['username']);
    $email = trim($input['email']);
    $password = trim($input['password']);

    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required!']);
        exit;
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate a web token (random string)
    $webtoken = bin2hex(random_bytes(32)); // 64-character random string

    // Prepare the SQL query to insert a new user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, passwd, webtoken) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword, $webtoken]);

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Sign up successful']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'username') !== false) {
                echo json_encode(['success' => false, 'message' => 'Username already exists']);
            }elseif (strpos($errorMessage, 'email') !== false) {
                echo json_encode(['success' => false, 'message' => 'Email already exists']);
            }
                
        }else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    exit; // End the script after processing the request
}

?>

<!-- HTML form for signup -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery for AJAX -->
</head>
<body>
    <h2 class="title">BUDBs Project</h2>
    <div class="wrapper">
        <form id="signUpForm">
            <h1>Create a new account</h1>
            <div class="input-box">
                <i class='bx bx-user-plus'></i>
                <input type="text" name="username" placeholder="Enter username" required>
            </div>
            <div class="input-box">
                <i class='bx bx-envelope'></i>
                <input type="email" name="email" placeholder="Enter email" required>
            </div>
            <div class="input-box">    
                <i class='bx bx-lock-open'></i>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn">Sign Up</button>
        </form>
    </div>

    <script>
        // Handle form submission with AJAX
        $('#signUpForm').on('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const formData = {
                username: $("input[name='username']").val(),
                email: $("input[name='email']").val(),
                password: $("input[name='password']").val(),
            };

            $.ajax({
                type: 'POST',
                url: 'sign_up.php', // Point to the same file
                contentType: 'application/json', // Send as JSON
                data: JSON.stringify(formData), // Convert the data to JSON
                success: function(response) {
                    // Parse JSON response
                    response = JSON.parse(response);
                    if (response.success) {
                        alert(response.message); // Show success message
                        window.location.href = 'login.php';
                    } else {
                        alert(response.message); // Show error message
                    }
                },
                error: function(error) {
                    console.error(error);
                    alert('Error during sign up'); // Handle any errors
                }
            });
        });
    </script>
</body>
</html>
