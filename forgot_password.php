<?php
include 'db_connection.php';

// Define the encryption key (make sure to store this securely)
define('ENCRYPTION_KEY', 'your-secure-encryption-key'); // Replace this with your own key

// Function to decrypt the security answer
function decryptData($encryptedData) {
    return openssl_decrypt($encryptedData, 'AES-128-ECB', ENCRYPTION_KEY);
}

// Step 1: Handle the email input
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email']) && !isset($_POST['security_answer'])) {
    $email = trim($_POST['email']);
    $stmt = $pdo->prepare("SELECT security_question FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode(['success' => true, 'security_question' => $user['security_question']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No user found with that email.']);
    }
    exit;
}

// Step 2: Handle security question and password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'], $_POST['security_answer'], $_POST['new_password'])) {
    $email = trim($_POST['email']);
    $security_answer = trim($_POST['security_answer']);
    $new_password = trim($_POST['new_password']);

    // Fetch stored encrypted security answer for the email
    $stmt = $pdo->prepare("SELECT security_answer FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Decrypt the stored security answer
        $decryptedAnswer = decryptData($user['security_answer']);

        // Verify the answer entered by the user
        if ($security_answer === $decryptedAnswer) {
            // Hash the new password and update
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET passwd = ? WHERE email = ?");
            $updateStmt->execute([$hashedPassword, $email]);

            echo json_encode(['success' => true, 'message' => 'Password reset successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect answer to the security question.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No user found with that email.']);
    }
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> <!-- Reuse your styles.css for consistent styling -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery for AJAX -->
</head>
<body>
    <h2 class="title">BUDBs Project</h2>
    
    <div class="wrapper">
        <!-- Step 1: Email input form -->
        <div class="form-container" id="forgotPasswordForm">
            <h1>Forgot Password</h1>
            <div class="input-box">
                <i class='bx bx-envelope'></i>
                <input type="email" id="email" placeholder="Enter your email" required>
            </div>
            <button class="btn" id="submitEmail">Submit</button>
        </div>

        <!-- Step 2: Security Question form -->
        <!-- Step 2: Security Question form -->
        <div class="form-container" id="securityQuestionForm" style="display:none;">
            <h1>Security Question</h1>
            <div class="input-box">
                <label id="securityQuestionLabel"></label>
            </div>
            <div class="input-box">
                <i class='bx bx-question-mark'></i> <!-- Icon should be inside the input box container -->
                <input type="text" id="securityAnswer" placeholder="Answer" required>
            </div>
            <div class="input-box">
                <i class='bx bx-lock-alt'></i>
                <input type="password" id="newPassword" placeholder="Enter new password" required>
            </div>
            <button class="btn" id="resetPassword">Reset Password</button>
        </div>
    </div>

    <script>
        // Step 1: Submit email to get security question
        $('#submitEmail').on('click', function() {
            var email = $('#email').val();
            
            $.ajax({
                type: 'POST',
                url: 'forgot_password.php',
                data: { email: email },
                success: function(response) {
                    response = JSON.parse(response);

                    if (response.success) {
                        $('#securityQuestionLabel').text(response.security_question);
                        $('#forgotPasswordForm').hide();
                        $('#securityQuestionForm').show();
                    } else {
                        alert(response.message);
                    }
                }
            });
        });

        // Step 2: Submit security answer and new password
        $('#resetPassword').on('click', function() {
            var email = $('#email').val();
            var securityAnswer = $('#securityAnswer').val();
            var newPassword = $('#newPassword').val();

            $.ajax({
                type: 'POST',
                url: 'forgot_password.php',
                data: {
                    email: email,
                    security_answer: securityAnswer,
                    new_password: newPassword
                },
                success: function(response) {
                    response = JSON.parse(response);

                    alert(response.message);
                    if (response.success) {
                        window.location.href = 'login.php'; // Redirect to login page
                    }
                }
            });
        });
    </script>
</body>
</html>

