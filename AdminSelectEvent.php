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
$sql = "SELECT username, user_role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $organizer_name = $row['username']; // Set organizer name
    $user_role = $row['user_role']; // Get user role for further checks if necessary
} else {
    $organizer_name = 'Organizer'; // Default value if not found
}

// Fetch all events (for admin)
$eventsSql = "SELECT id, event_title, event_date FROM events"; // Remove user_id filter to fetch all events
$eventsStmt = $conn->prepare($eventsSql);
$eventsStmt->execute();
$eventsResult = $eventsStmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Events</title>
    <link rel="stylesheet" href="OrganizerSelectEvent.css">
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

<h1>All Events</h1>

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
                    <td><a href="AdminEditEvent.php?event_id=<?php echo $row['id']; ?>">Edit</a></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No events available at this time.</p>
<?php endif; ?>

</body>
</html>
