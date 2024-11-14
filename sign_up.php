<?php
// Include your database connection
include 'db_connection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the encryption key (make sure to store this securely)
define('ENCRYPTION_KEY', 'your-secure-encryption-key'); // Replace this with your own key

// Function to encrypt data
function encryptData($data) {
    return openssl_encrypt($data, 'AES-128-ECB', ENCRYPTION_KEY);
}

// Check if the request method is POST for handling signup
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data from JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email']);
    $password = trim($input['password']);
    $security_question = trim($input['security_question']);
    $security_answer = trim($input['security_answer']);

    // Validate inputs
    if (empty($email) || empty($password) || empty($security_question) || empty($security_answer)) {
        echo json_encode(['success' => false, 'message' => 'Email, password, security question, and answer are required!']);
        exit;
    }

    // Encrypt the security answer before storing it
    $encryptedSecurityAnswer = encryptData($security_answer);

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Generate a web token (random string)
    $webtoken = bin2hex(random_bytes(32)); // 64-character random string

    // Prepare the SQL query to insert a new user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (email, passwd, webtoken, security_question, security_answer) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$email, $hashedPassword, $webtoken, $security_question, $encryptedSecurityAnswer]);

        // Return success response
        echo json_encode(['success' => true, 'message' => 'Sign up successful']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Duplicate entry error code
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    exit; // End the script after processing the request
}
?>



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
                <i class='bx bx-envelope'></i>
                <input type="email" name="email" placeholder="Enter email" required>
            </div>
            <div class="input-box">    
                <i class='bx bx-lock-open'></i>
                <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <div class="select-box">
                <select name="security_question" required>
                    <option value="" disabled selected>Select a Security Question</option>
                    <option value="What was your first pet's name?">What was your first pet's name?</option>
                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                    <option value="What is the name of the town where you were born?">What is the name of the town where you were born?</option>
                </select>
            </div>
            <div class="input-box">    
                <i class='bx bx-lock-alt'></i>
                <input type="text" name="security_answer" placeholder="Enter answer to your security question" required>
            </div>
            <button type="submit" class="btn">Sign Up</button>
        </form>
    </div>

    <script>
    // Handle form submission with AJAX
    $('#signUpForm').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const formData = {
            email: $("input[name='email']").val(),
            password: $("input[name='password']").val(),
            security_question: $("select[name='security_question']").val(),
            security_answer: $("input[name='security_answer']").val(),
        };

        $.ajax({
            type: 'POST',
            url: 'sign_up.php', // Point to the same file
            contentType: 'application/json', // Send as JSON
            data: JSON.stringify(formData), // Convert the data to JSON
            success: function(response) {
                if (typeof response === 'string') {
                    response = JSON.parse(response); // Parse JSON response if it's a string
                }
                if (response.success) {
                    alert(response.message); // Show success message
                    window.location.href = 'login.php'; // Redirect to login page
                } else {
                    alert(response.message); // Show error message
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error(jqXHR, textStatus, errorThrown);
                alert('Error during sign up: ' + textStatus); // Handle any errors
            }
        });
    });
    </script>

</body>
</html>


