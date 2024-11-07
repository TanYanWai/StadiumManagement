<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
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
$userSql = "SELECT username, email, phonenumber FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
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
    <title>Home Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="EditProfile.css">
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
            <a href="ContactUs.php" class="nav-link">Contact Us</a>
            <a href="AboutUs.php" class="nav-link">About Us</a>
        </nav>
        <div class="button-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="ProfileContainer">
                    <a href="UserProfile.php" id="userProfileLink">
                        <?php echo htmlspecialchars($user['username']); ?>!
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
    <h2>Edit Profile</h2>

    <div class="profile-details">
        <form action="UpdateProfile.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="phonenumber">Phone Number:</label>
                <input type="text" name="phonenumber" id="phonenumber" value="<?php echo htmlspecialchars($user['phonenumber']); ?>">
            </div>
            <div class="profile-actions">
                <!-- Submit button to save changes -->
                <button type="submit" class="button-save">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div class="footerbox">
    <p>Malaysia . Penang</p>
</div>

</body>
</html>
