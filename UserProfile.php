<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to login page
    header("Location: Login.html");
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

// Fetch user information based on the logged-in user_id
$user_id = $_SESSION['user_id'];
$userSql = "SELECT username, email, phonenumber, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_name = $user['username']; // Assign the username to $user_name variable
} else {
    echo "User not found!";
    exit();
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="UserProfile.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100&display=swap" rel="stylesheet">
</head>
<header class="header">
    <div class="navigation">
        <div class="brand">
            <a href="#" class="logo"><i class="fas fa-heartbeat"></i><b>BookMyTicket</b></a>
        </div>
        <div class="search-container">
            <input type="text" class="search-input" placeholder="Search...">
            <button class="search-button"><i class="fas fa-search"></i></button>
        </div>
        <nav class="nav">
            <a href="UserHome.php" class="nav-link">Home</a>
            <a href="UserEvent.html" class="nav-link">Events</a>
            <a href="Message.html" class="nav-link">Learn More</a>
            <a href="output_message.php" class="nav-link">About Us</a>
        </nav>
        <div class="button-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- If the user is logged in, display the username as a link to the Profile page -->
                <div class="ProfileContainer">
                    <a href="UserProfile.php" id="userProfileLink">
                        <?php echo htmlspecialchars($user_name); ?>!
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

<body>

<div class="profile-container">
    <h2>User Profile</h2>

    <div class="profile-details">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['phonenumber'] ?? 'N/A'); ?></p>
        <p><strong>Member Since:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
    </div>

    <div class="profile-actions">
        <!-- Edit Profile Button -->
        <a href="EditProfile.php" class="button-edit">Edit Profile</a>

        <!-- Logout Button -->
        <a href="Logout.php" class="button-logout">Logout</a>
    </div>
</div>

<div class="footerbox">
    <p>Malaysia . Penang</p>
</div>

</body>
</html>
