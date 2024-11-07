<?php
session_start();

// Check if the organizer is logged in
if (!isset($_SESSION['user_id'])) {
    // If the organizer is not logged in, redirect to login page
    header("Location: Login.html");
    exit();
}

$userId = $_SESSION['user_id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "FYP_BookMyTicket";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500); // Internal server error
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// Fetch the username from the users table
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_name = htmlspecialchars($user['username']); // Escape to prevent XSS
} else {
    $user_name = 'Guest'; // Default value if no user is found
}

$stmt->close();

// Fetch events created by the organizer
$sql = "SELECT id, event_title, event_date FROM events WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Create an array to store the event data
$events = array();
while ($row = $result->fetch_assoc()) {
    $events[] = [
        "id" => $row['id'],
        "title" => $row['event_title'],
        "date" => $row['event_date']
    ];
}

$stmt->close();

// If an event is selected, fetch its details
$event = null;
if (isset($_POST['event_id'])) {
    $eventId = $_POST['event_id'];

    // Fetch the selected event's details
    $sql = "SELECT event_title, event_date, event_time_from, event_time_to, event_description, event_category, contact_person, contact_number, email, event_terms FROM events WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
}

$conn->close();

// Prepare event URL if an event is selected
$event_url = isset($event) ? "https://www.bookmyevent.com/event.php?id=" . $eventId : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicize Event</title>
    <link rel="stylesheet" href="OrganizerPublicity.css">
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
                <a href="OrganiserProfile.php" id="userProfileLink">
                        <?php echo $user_name; ?>
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

<div class="form-container">
    <h1>Select an Event to Publicize</h1>

    <form action="" method="POST">
        <label for="event-select">Choose an event:</label>
        <select name="event_id" id="event-select">
            <option value="">--Select an event--</option>
            <?php foreach ($events as $eventItem): ?>
                <option value="<?= $eventItem['id'] ?>" <?= isset($eventId) && $eventItem['id'] == $eventId ? 'selected' : '' ?>>
                    <?= htmlspecialchars($eventItem['title']) ?> (<?= htmlspecialchars($eventItem['date']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Publicize Event</button>
    </form>
</div>


    <?php if ($event): ?>
        <div class="event-container">
        <h2>Publicize Event: <?= htmlspecialchars($event['event_title']) ?></h2>
        <p><strong>Date:</strong> <?= htmlspecialchars($event['event_date']) ?></p>
        <p><strong>Time:</strong> From <?= htmlspecialchars($event['event_time_from']) ?> to <?= htmlspecialchars($event['event_time_to']) ?></p>
        <p><strong>Description:</strong> <?= htmlspecialchars($event['event_description']) ?></p>
        <p><strong>Category:</strong> <?= htmlspecialchars($event['event_category']) ?></p>
        <p><strong>Contact Person:</strong> <?= htmlspecialchars($event['contact_person']) ?></p>
        <p><strong>Contact Number:</strong> <?= htmlspecialchars($event['contact_number']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($event['email']) ?></p>
        <p><strong>Terms:</strong> <?= htmlspecialchars($event['event_terms']) ?></p>

        <div class="share-buttons">
            <!-- Facebook Share Button -->
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($event_url) ?>" target="_blank" class="share-button facebook-share">
                <i class="fab fa-facebook-f"></i> Share on Facebook
            </a>

            <!-- Instagram Share (Copy Link) -->
            <a href="#" onclick="copyEventLink('<?= $event_url ?>')" class="share-button instagram-share">
                <i class="fab fa-instagram"></i> Share on Instagram
            </a>
        </div>
    </div>


        <script>
            // Function to copy the event link to clipboard
            function copyEventLink(link) {
                navigator.clipboard.writeText(link);
                alert('Event link copied to clipboard! You can paste it in your Instagram bio or post.');
            }
        </script>
    <?php endif; ?>

</body>
</html>
