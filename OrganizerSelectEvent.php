<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "FYP_BookMyTicket";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Fetch organizer's username from the users table
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $organizer_name = $row['username']; // Set organizer name
} else {
    $organizer_name = 'Organizer'; // Default value if not found
}

// Fetch events created by the logged-in user
$eventsSql = "SELECT id, event_title, event_date FROM events WHERE user_id = ?";
$eventsStmt = $conn->prepare($eventsSql);
$eventsStmt->bind_param("i", $user_id);
$eventsStmt->execute();
$eventsResult = $eventsStmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Events</title>
    <link rel="stylesheet" href="OrganizerSelectEvent.css">
</head>
<body>
<header class="header">
    <div class="navigation">
        <div class="brand">
            <a href="#" class="logo"><i class="fas fa-calendar-alt"></i><b>BookMyEvent</b></a>
        </div>
        <nav class="nav">
            <a href="OrganizerDashboard.php" class="nav-link">Home</a>
            <a href="OrganizerAddEvent.php" class="nav-link">Create Event</a>
            <a href="OrganizerSelectEvent.php" class="nav-link">Edit Event</a>
            <a href="OrganizerPublicity.php" class="nav-link">Publicity</a>
            <a href="OrganizerScanQrcode.php" class="nav-link">Scan QR</a>
            <a href="OrganizerProfile.php" class="nav-link">Profile</a>
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

<h1>Your Events</h1>

<?php if ($eventsResult->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Event Title</th>
                <th>Event Date</th>
                <th>Edit</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $eventsResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['event_title']); ?></td>
                    <td><?php echo htmlspecialchars($row['event_date']); ?></td>
                    <td><a href="OrganizerEditEvent.php?event_id=<?php echo $row['id']; ?>">Edit</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>You have not created any events yet.</p>
<?php endif; ?>

</body>
</html>
