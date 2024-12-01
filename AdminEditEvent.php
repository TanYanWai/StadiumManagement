<?php
session_start();

// Check if user is logged in
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

// Fetch user role
$user_id = $_SESSION['user_id'];
$roleSql = "SELECT user_role FROM users WHERE id = ?";
$roleStmt = $conn->prepare($roleSql);
$roleStmt->bind_param("i", $user_id);
$roleStmt->execute();
$roleResult = $roleStmt->get_result();

// Check if user exists and has admin privileges
if ($roleResult->num_rows > 0) {
    $user = $roleResult->fetch_assoc();
    if ($user['user_role'] !== 'admin') {
        header("Location: AccessDenied.html"); // Redirect to an access denied page
        exit();
    }
} else {
    echo "User not found.";
    exit();
}

// Fetch organizer information (admin info here)
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

// Check if an event ID is passed in the URL (for editing or deleting)
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Fetch event details
    $sql = "SELECT * FROM events WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $eventResult = $stmt->get_result();

    if ($eventResult->num_rows == 0) {
        echo "Event not found.";
        exit();
    }

    $event = $eventResult->fetch_assoc();

    // Fetch seat prices for this event
    $seatSql = "SELECT * FROM seat_prices WHERE event_id = ?";
    $seatStmt = $conn->prepare($seatSql);
    $seatStmt->bind_param("i", $event_id);
    $seatStmt->execute();
    $seatResult = $seatStmt->get_result();

    $seats = [];
    while ($row = $seatResult->fetch_assoc()) {
        $seats[] = $row;
    }

    $seatStmt->close();
}

// Handle delete request
if (isset($_POST['delete_event'])) {
    // Delete event and its associated seat prices
    $deleteSeatsSql = "DELETE FROM seat_prices WHERE event_id = ?";
    $deleteSeatsStmt = $conn->prepare($deleteSeatsSql);
    $deleteSeatsStmt->bind_param("i", $event_id);
    $deleteSeatsStmt->execute();

    $deleteEventSql = "DELETE FROM events WHERE id = ?";
    $deleteEventStmt = $conn->prepare($deleteEventSql);
    $deleteEventStmt->bind_param("i", $event_id);
    $deleteEventStmt->execute();

    header("Location: AdminDashboard.php");
    exit();
}

// Handle edit request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['delete_event'])) {
    // Update event details
    $event_title = $_POST['event_title'];
    $event_date = $_POST['event_date'];
    $event_time_from = $_POST['event_time_from'];
    $event_time_to = $_POST['event_time_to'];
    $event_description = $_POST['event_description'];

    $updateEventSql = "UPDATE events SET event_title = ?, event_date = ?, event_time_from = ?, event_time_to = ?, event_description = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateEventSql);
    $updateStmt->bind_param("sssssi", $event_title, $event_date, $event_time_from, $event_time_to, $event_description, $event_id);
    $updateStmt->execute();

    // Update seat prices
    foreach ($_POST['seats'] as $seat_id => $seat_price) {
        $updateSeatSql = "UPDATE seat_prices SET seat_price = ? WHERE id = ?";
        $seatStmt = $conn->prepare($updateSeatSql);
        $seatStmt->bind_param("di", $seat_price, $seat_id);
        $seatStmt->execute();
    }

    header("Location: AdminDashboard.php"); // Redirect after saving
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event</title>
    <link rel="stylesheet" href="OrganizerEditEvent.css">
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
            <div class="ProfileContainer">
                <a href="AdminProfile.php" id="adminProfileLink">
                    <?php echo htmlspecialchars($organizer_name); ?>!
                </a>
            </div>
        </div>
    </div>
</header>

<div class="container">
    <h1>Edit Event</h1>
    <form method="POST">
        <label for="event_title">Event Title:</label>
        <input type="text" id="event_title" name="event_title" value="<?php echo htmlspecialchars($event['event_title']); ?>" required>

        <label for="event_date">Event Date:</label>
        <input type="date" id="event_date" name="event_date" value="<?php echo htmlspecialchars($event['event_date']); ?>" required>

        <label for="event_time_from">Start Time:</label>
        <input type="time" id="event_time_from" name="event_time_from" value="<?php echo htmlspecialchars($event['event_time_from']); ?>" required>

        <label for="event_time_to">End Time:</label>
        <input type="time" id="event_time_to" name="event_time_to" value="<?php echo htmlspecialchars($event['event_time_to']); ?>" required>

        <label for="event_description">Description:</label>
        <textarea id="event_description" name="event_description" required><?php echo htmlspecialchars($event['event_description']); ?></textarea>

        <h2>Seat Prices</h2>
        <?php foreach ($seats as $seat): ?>
            <label for="seat_<?php echo $seat['id']; ?>">Seat Type: <?php echo htmlspecialchars($seat['seat_type']); ?></label>
            <input type="number" id="seat_<?php echo $seat['id']; ?>" name="seats[<?php echo $seat['id']; ?>]" value="<?php echo htmlspecialchars($seat['seat_price']); ?>" step="0.01" required>
        <?php endforeach; ?>

        <input type="submit" value="Save Changes">
        <button type="submit" name="delete_event" onclick="return confirm('Are you sure you want to delete this event?');" style="background-color: red; color: white;">Delete Event</button>
    </form>
</div>
</body>
</html>
