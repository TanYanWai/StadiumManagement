<?php
session_start();

// Check if the user is already logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: UserHome.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "FYP_BookMyTicket";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = array();

// Fetch the organizer's name if the user is logged in
$organizer_name = ""; // Initialize the variable to avoid warnings
$userId = $_SESSION['user_id'];
$userSql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $organizer = $result->fetch_assoc();
    $organizer_name = $organizer['username']; // Assign the username to $organizer_name variable
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $phonenumber = $_POST['phonenumber'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($phonenumber)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert into users table with the role of organiser
        $sql = "INSERT INTO users (username, email, phonenumber, password, user_role) VALUES (?, ?, ?, ?, 'organiser')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $username, $email, $phonenumber, $hashed_password);

        if ($stmt->execute()) {
            // Display a success message or redirect to another page if desired
            echo "<script>alert('Registration successful!');</script>";
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organiser Registration</title>
    <link rel="stylesheet" href="organizerRegistration.css">
</head>
<body>
<header class="header">
    <div class="navigation">
        <div class="brand">
            <a href="#" class="logo"><i class="fas fa-calendar-alt"></i><b>BookMyEvent</b></a>
        </div>
        <nav class="nav">
            <a href="AdminDashboard.php" class="nav-link">Home</a>
            <a href="AdminAddEvent.php" class="nav-link">Create Event</a>
            <a href="AdminSelectEvent.php" class="nav-link">Edit Event</a>
            <a href="OrganizerRegistration.php" class="nav-link">Create account</a>
            <a href="AdminScanQRCode.php" class="nav-link">Scan QR</a>
            <a href="AdminProfile.php" class="nav-link">Profile</a>
        </nav>
        <div class="button-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- If the organizer is logged in, display the username as a link to the Profile page -->
                <div class="ProfileContainer">
                    <a href="OrganizerProfile.php" id="organizerProfileLink">
                        <?php echo htmlspecialchars($organizer_name); ?>!
                    </a>
                </div>
            <?php else: ?>
                <!-- If the user is not logged in, show the login and sign-up buttons -->
                <div class="UserLoginButton">
                    <a href="Login.html" id="Login" class="buttonLog">Login</a>
                </div>
                <div class="UserSignupButton">
                    <a href="SignUp.html" id="SignUp" class="buttonLog">Sign Up</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
    <div class="registration-container">
        <h1>Register as an Organiser</h1>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="organiserRegistration.php" method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>

            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>

            <label for="phonenumber">Phone Number</label>
            <input type="text" name="phonenumber" id="phonenumber" required>

            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <input type="submit" value="Register">
        </form>
    </div>
</body>
</html>
