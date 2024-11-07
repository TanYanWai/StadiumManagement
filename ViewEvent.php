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

// Get the event ID from the URL
if (isset($_GET['id'])) {
    $event_id = intval($_GET['id']); // Ensure the ID is an integer

    // Fetch the event details based on the event ID
    $eventSql = "
        SELECT e.id, e.event_title, e.event_time_from, e.event_time_to, 
               e.event_description, e.event_category, e.contact_person, 
               e.contact_number, e.email, e.event_terms, ei.event_poster, ei.image_path
        FROM events e
        LEFT JOIN event_images ei ON e.id = ei.event_id 
        WHERE e.id = ?";

    $stmt = $conn->prepare($eventSql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $eventResult = $stmt->get_result();

    if ($eventResult->num_rows > 0) {
        $event = $eventResult->fetch_assoc();
    } else {
        die("Event not found.");
    }

    // Fetch all event images associated with the event ID
    $imagesSql = "SELECT image_path FROM event_images WHERE event_id = ?";
    $stmt = $conn->prepare($imagesSql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $imagesResult = $stmt->get_result();

    // Store all image paths in an array
    $event_images = [];
    while ($row = $imagesResult->fetch_assoc()) {
        $event_images[] = $row['image_path'];
    }

    // Fetch available event dates associated with the event ID from event_dates table
    $datesSql = "SELECT event_date FROM event_dates WHERE event_id = ?";
    $stmt = $conn->prepare($datesSql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $datesResult = $stmt->get_result();

    // Store all event dates in an array
    $event_dates = [];
    while ($row = $datesResult->fetch_assoc()) {
        $event_dates[] = $row['event_date'];
    }
} else {
    die("No event ID specified.");
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
    <link rel="stylesheet" href="ViewEvent.css">
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
            <a href="ChooseEvent.php" class="nav-link">Rate Events</a>
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

<main>
    <div class="container">
        <!-- Display Event Poster -->
        <div class="event-poster">
            <?php if (!empty($event['event_poster'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($event['event_poster']); ?>" alt="Event Poster" style="width:100%; max-height:400px;">
            <?php endif; ?>
        </div>

        <!-- Event Title and Description -->
        <div class="event-details-container">
            <div class="event-title-description">
                <h1><?php echo htmlspecialchars($event['event_title']); ?></h1>
                <p><?php echo nl2br(htmlspecialchars($event['event_description'])); ?></p>
            </div>

            <!-- Event Images Section -->
<?php if (!empty($event_images)): ?>
    <div class="image-display-container">
        <!-- Main Image -->
        <div class="main-image-container">
            <img id="mainImage" src="uploads/<?php echo urlencode($event_images[0]); ?>" alt="Event Image" class="main-event-image">
        </div>

        <!-- Thumbnails -->
        <div class="thumbnail-container">
            <?php foreach ($event_images as $image): ?>
                <img src="uploads/<?php echo urlencode($image); ?>" alt="Event Thumbnail" class="event-thumbnail" onclick="changeMainImage(this)">
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <p>No additional images available for this event.</p>
<?php endif; ?>


            <!-- Event Information (Date, Time, Contact Details) -->
            <div class="event-info">
                <h2>Select Your Attendance Date</h2>
                <?php if (!empty($event_dates)): ?>
                    <?php foreach ($event_dates as $date): ?>
                        <div class="date-selection-container">
                            <h3><?php echo htmlspecialchars($date); ?></h3>
                            <button class="book-now-button" onclick="window.location.href='SelectSection.php?id=<?php echo $event['id']; ?>&date=<?php echo htmlspecialchars($date); ?>'">
                                Book Now
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No available dates for this event.</p>
                <?php endif; ?>

                <p><strong>From:</strong> <?php echo htmlspecialchars($event['event_time_from']); ?></p>
                <p><strong>To:</strong> <?php echo htmlspecialchars($event['event_time_to']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($event['event_category']); ?></p>
                <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($event['contact_person']); ?></p>
                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($event['contact_number']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($event['email']); ?></p>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="terms-container">
            <h2>Event Terms</h2>
            <p><?php echo nl2br(htmlspecialchars($event['event_terms'])); ?></p>
        </div>
    </div>
</main>

<script>
    function changeMainImage(thumbnail) {
        document.getElementById("mainImage").src = thumbnail.src;
    }
</script>

</body>
</html>
