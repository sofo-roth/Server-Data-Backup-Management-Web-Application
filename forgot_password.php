<?php
include 'db_connection.php';
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $reset_token = bin2hex(random_bytes(50));  // Generate a secure token
        $token_expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));  // 1 hour expiry

        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE email = ?");
        $stmt->execute([$reset_token, $token_expiry, $email]);

        // Send email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com';
            $mail->Password = 'your_email_password';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('your_email@example.com', 'Your Site');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset';
            $mail->Body    = "Click <a href='http://localhost/reset_password.php?token=$reset_token'>here</a> to reset your password.";

            $mail->send();
            echo "Password reset email sent!";
        } catch (Exception $e) {
            echo "Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "No user found with that email.";
    }
}
?>

<!-- Forgot Password Form -->
<form method="POST" action="forgot_password.php">
    <input type="email" name="email" required placeholder="Enter your email">
    <button type="submit">Submit</button>
</form>
