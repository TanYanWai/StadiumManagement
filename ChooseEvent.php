<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection details
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

// Fetch booked events for the logged-in user
$sql = "SELECT e.id, e.event_title, b.seat_number, b.event_date 
        FROM bookings b 
        JOIN events e ON b.event_id = e.id 
        WHERE b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="ChooseEvent.css">
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
                    <a href="UserProfile.php" id="userProfileLink">
                        <?php echo $user_name; ?>
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
    <h1>Your Booked Events</h1>

    <table>
        <tr>
            <th>Event Title</th>
            <th>Seat Number</th>
            <th>Event Date</th>
            <th>Rate Event</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo $row['event_title']; ?></td>
                <td><?php echo $row['seat_number']; ?></td>
                <td><?php echo $row['event_date']; ?></td>
                <td>
                    <a href="RateEvent.php?event_id=<?php echo $row['id']; ?>">Rate</a>
                </td>
            </tr>
        <?php } ?>
    </table>
    <div class="footerbox">
        <p>Malaysia . Penang</p>
    </div>
    <?php
    $stmt->close();
    $conn->close();
    ?>
</body>
</html>
