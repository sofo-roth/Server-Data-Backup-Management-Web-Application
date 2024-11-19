<?php
// Include the database connection file
include 'db_connection.php';  // Make sure the path is correct

session_start(); // Start the session

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in by verifying the session variable
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$email = $_SESSION['email']; 

// Fetch the user's hashed password from the database
$stmt = $pdo->prepare("SELECT passwd, email FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Handle the form submission for confirming password and deletion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the password is set and user hasn't confirmed deletion yet
    if (isset($_POST['password']) && !isset($_POST['confirm_delete'])) {
        $password = $_POST['password'];

        // Check if user exists and password is correct
        if ($user && password_verify($password, $user['passwd'])) {
            // Password is correct, now show confirmation form
            echo json_encode(['success' => true, 'message' => 'Password correct. Please confirm deletion.']);
            exit;
        } else {
            // Invalid password
            echo json_encode(['success' => false, 'message' => 'Invalid password']);
            exit;
        }
    }
    
    // Handle account deletion after confirmation
    if (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'yes' && isset($_POST['password'])) {
        // Begin transaction to ensure both user and their connections are deleted
        $pdo->beginTransaction();

        try {
            // Delete the user's connections from the user_db_connections table
            $deleteConnectionsStmt = $pdo->prepare("DELETE FROM user_db_connections WHERE email = ?");
            $deleteConnectionsStmt->execute([$email]);

            // Delete the user's account from the users table
            $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
            $deleteUserStmt->execute([$email]);

            // Commit the transaction if both queries were successful
            $pdo->commit();

            // Destroy the session and log the user out
            session_destroy();

            // Return success message as JSON
            echo json_encode(['success' => true, 'message' => 'Your Account and Connections Have Been Deleted Successfully']);
            exit; // Ensure no further output
        } catch (Exception $e) {
            // Rollback the transaction if any error occurs
            $pdo->rollBack();

            // Return error message as JSON
            echo json_encode(['success' => false, 'message' => 'An error occurred while deleting your account. Please try again later.']);
            exit;
        }
    } elseif (isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 'no') {
        // Account deletion was canceled
        echo json_encode(['success' => false, 'message' => 'Account deletion was canceled']);
        exit; // Ensure no further output
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
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
            <li class="logged-in">Logged in as: <?php echo htmlspecialchars($user['email']); ?></li>
            <li><a href="logout.php" class="logout-btn">Logout</a></li>
        </ul>
    </nav>

    <div class="wrapper">
        <!-- First Form: Password Verification -->
        <div class="form-container">
            <h2>Delete Your Account</h2>
            <form method="POST" action="" id="delete-form">
                <p>To proceed with deleting your account, please re-enter your password:</p>
                <div class="input-box">
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn">Verify Password</button>
            </form>
        </div>
    </div>

    <!-- This part will be shown after password is verified -->
    <div id="delete-confirm-form" class="form-container" style="display: none;">
        <div class="wrapper">
            <h3>Are you sure you want to delete your account?</h3>
            <form method="POST" action="" id="confirm-delete-form">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($password); ?>"> <!-- Hidden field to pass password -->
                <input type="radio" name="confirm_delete" value="yes" required> Yes, delete my account<br>
                <input type="radio" name="confirm_delete" value="no" required> No, keep my account<br>
                <button type="submit" class="btn">Confirm</button>
            </form>
        </div>
    </div>

    <script>
            const deleteForm = document.getElementById('delete-form');
        deleteForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            const formData = new FormData(deleteForm);

            fetch('delete_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  // Parse the response as JSON
            .then(data => {
                if (data.success) {
                    if (data.message === 'Password correct. Please confirm deletion.') {
                        // Show the confirmation form
                        alert(data.message);  // You can replace this with a modal or another message.
                        
                        // Unhide the confirmation form
                        document.getElementById('delete-confirm-form').style.display = 'block';
                    } else {
                        alert(data.message);  // Display success or cancellation message
                        if (data.success) {
                            // Redirect to login after account deletion
                            window.location.href = 'login.php';  // Redirect after deletion
                        }
                    }
                } else {
                    alert(data.message);  // Display error message
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        });

        const confirmDeleteForm = document.getElementById('confirm-delete-form');
        confirmDeleteForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            const formData = new FormData(confirmDeleteForm);

            fetch('delete_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())  // Parse the response as JSON
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    // Redirect after successful deletion
                    window.location.href = 'login.php';  // Redirect to login page after deletion
                } else {
                    alert(data.message);  // Display cancellation message
                    // Redirect to dashboard after cancellation
                    window.location.href = 'dashboard.php';  // Redirect to dashboard
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again later.');
            });
        });
    </script>
</body>
</html>
