<?php
$servername = "localhost"; 
$username = "root"; 
$password = "root"; 
$dbname = "FYP_BookMyTicket";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userInput = $conn->real_escape_string($_POST['user_input']); // Can be username or email
    $newPassword = $conn->real_escape_string($_POST['password']);
    $confirmPassword = $conn->real_escape_string($_POST['confirmPassword']);

    // Validate password
    if (strlen($newPassword) < 6 || !preg_match("/[A-Za-z]/", $newPassword) || !preg_match("/[0-9]/", $newPassword)) {
        echo "<script>alert('Password must be at least 6 characters long, contain at least one letter and one number.'); window.location.href='ForgotPassword.html';</script>";
    } else if ($newPassword !== $confirmPassword) {
        echo "<script>alert('Passwords do not match!'); window.location.href='ForgotPassword.html';</script>";
    } else {
        // Determine if input is an email or username
        if (filter_var($userInput, FILTER_VALIDATE_EMAIL)) {
            // It's an email
            $sql = "SELECT * FROM users WHERE email = '$userInput'";
        } else {
            // It's a username
            $sql = "SELECT * FROM users WHERE username = '$userInput'";
        }

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // User exists, update the password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT); // Hash the new password

            if (filter_var($userInput, FILTER_VALIDATE_EMAIL)) {
                // Update password by email
                $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE email = '$userInput'";
            } else {
                // Update password by username
                $updateSql = "UPDATE users SET password = '$hashedPassword' WHERE username = '$userInput'";
            }

            if ($conn->query($updateSql) === TRUE) {
                echo "<script>alert('Password updated successfully. Please check your email for confirmation.'); window.location.href='Login.html';</script>";
            } else {
                echo "<script>alert('Error updating password: " . $conn->error . "'); window.location.href='ForgotPassword.html';</script>";
            }
        } else {
            echo "<script>alert('No account found with this username or email address.'); window.location.href='ForgotPassword.html';</script>";
        }
    }
}

$conn->close();
?>
