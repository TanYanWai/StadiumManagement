<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to login page
    header("Location: Login.html");
    exit(); // Stop further execution
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

// Fetch the username based on the stored user ID
$user_id = $_SESSION['user_id'];
$userSql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_name = htmlspecialchars($user['username']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['event_title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="AboutUs.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100&display=swap" rel="stylesheet">
</head>
<body>
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
                <a href="ContactUs.php" class="nav-link">Contact Us</a>
                <a href="AboutUs.php" class="nav-link">About Us</a>
            </nav>
            <div class="button-container">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="ProfileContainer">
                        <a href="UserProfile.php" id="userProfileLink"><?php echo $user_name; ?></a>
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
    
    <main class="container">
        <section class="about-section">
            <h2>Welcome to Stadium Ticket Booking!</h2>
            <p>
                At Stadium Ticket Booking, we believe in providing the best experience for sports and entertainment enthusiasts. Whether it's a thrilling football match, an electrifying concert, or a spectacular performance, we aim to bring you closer to the action with easy and accessible ticket booking.
            </p>
            <h3>Our Mission</h3>
            <p>
                Our mission is to ensure that every fan has the opportunity to experience live events in stadiums across the country. We strive to make ticket purchasing hassle-free, transparent, and enjoyable for all.
            </p>
            <h3>Our Values</h3>
            <ul>
                <li>Customer Satisfaction: Your happiness is our priority.</li>
                <li>Integrity: We believe in honesty and transparency.</li>
                <li>Innovation: We constantly evolve to enhance your experience.</li>
                <li>Accessibility: Everyone should have the chance to enjoy live events.</li>
            </ul>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 Stadium Ticket Booking. All rights reserved.</p>
    </footer>
</body>
</html>
