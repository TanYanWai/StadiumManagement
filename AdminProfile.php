<?php
session_start();

// Check if the organizer is logged in
if (!isset($_SESSION['user_id'])) {
    // If the organizer is not logged in, redirect to login page
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

// Fetch organizer information based on the logged-in user_id
$user_id = $_SESSION['user_id'];
$userSql = "SELECT username, email, phonenumber, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $organizer = $result->fetch_assoc();
    $organizer_name = $organizer['username']; // Assign the username to $organizer_name variable
} else {
    echo "Organizer not found!";
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
    <title>Organizer Profile Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="UserProfile.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100&display=swap" rel="stylesheet">
</head>
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
                <div class="ProfileContainer">
                    <a href="OrganizerProfile.php" id="organizerProfileLink">
                        <?php echo htmlspecialchars($organizer_name); ?>!
                    </a>
                </div>
            <?php else: ?>
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
    <h2>Organizer Profile</h2>

    <div class="profile-details">
        <p><strong>Organizer Name:</strong> <?php echo htmlspecialchars($organizer['username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($organizer['email']); ?></p>
        <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($organizer['phonenumber'] ?? 'N/A'); ?></p>
        <p><strong>Organizer Since:</strong> <?php echo htmlspecialchars($organizer['created_at']); ?></p>
    </div>

    <div class="profile-actions">
        <!-- Manage Events Button -->
        <a href="ManageEvents.php" class="button-edit">Edit Profile</a>

        <!-- Logout Button -->
        <a href="Logout.php" class="button-logout">Logout</a>
    </div>
</div>

<div class="footerbox">
    <p>Malaysia . Penang</p>
</div>

</body>
</html>
