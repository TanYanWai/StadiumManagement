<?php
session_start(); // Make sure to start the session

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Retrieve the user ID from the session

    // Database connection details
    $servername = "localhost";
    $username = "root";
    $password = "root";
    $dbname = "FYP_BookMyTicket";

    // Create a connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check the connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch the username from the users table
    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = $user['username']; // Assign the fetched username
    } else {
        $user_name = 'Guest'; // Default value if no user is found
    }

    $stmt->close();
    $conn->close();
} else {
    $user_name = 'Guest'; // Default value if the user is not logged in
}

// Fetch the event ID and section ID from the URL parameters
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$section_id = isset($_GET['section']) ? $_GET['section'] : ''; // Get section as string

if ($event_id === 0 || empty($section_id)) {
    die("Invalid event or section.");
}

// Database connection for booking logic
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch event date range
$eventSql = "SELECT event_date FROM events WHERE id = ?";
$stmt = $conn->prepare($eventSql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$eventResult = $stmt->get_result();

if ($eventResult->num_rows > 0) {
    $event = $eventResult->fetch_assoc();
    $event_date = $event['event_date'];
} else {
    die("Event not found.");
}

// Fetch booked seats from the bookings table for the event, section, and date
$bookedSeatSql = "SELECT seat_number FROM bookings WHERE event_id = ? AND event_date = ? AND section_id = ?";
$stmtBooked = $conn->prepare($bookedSeatSql);
$stmtBooked->bind_param("iss", $event_id, $event_date, $section_id);
$stmtBooked->execute();
$bookedSeatResult = $stmtBooked->get_result();

$bookedSeats = [];
while ($row = $bookedSeatResult->fetch_assoc()) {
    $bookedSeats[] = $row['seat_number'];
}

$stmtBooked->close();
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
    <link rel="stylesheet" href="bookSeat.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100&display=swap" rel="stylesheet">
</head>
<body>
    <header>
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
    <div class="legend-container">
    <h1>Book Your Seat for the Event</h1>
        <div class="legend">
            <span class="available">Available</span>
            <span class="booked">Booked</span>
            <span class="selected">Selected</span>
        </div>
    </div>
        <form id="bookingForm" action="CheckOut.php" method="POST">
            <div class="seat-container">
                <?php for ($i = 1; $i <= 250; $i++): ?>
                    <?php
                    // Default seat status is available
                    $seatStatus = 'available';

                    // Check if the seat is booked
                    if (in_array($i, $bookedSeats)) {
                        $seatStatus = 'booked'; // Mark the seat as booked
                    }
                    ?>
                    <div class="seat <?php echo $seatStatus; ?>" data-seat-number="<?php echo $i; ?>" onclick="selectSeat(this)">
                        <?php echo $i; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Display Event Days -->
            <div class="event-days">
                <p>Event Day: <?php echo $event_date; ?></p>
                <input type="hidden" name="event_date" value="<?php echo $event_date; ?>">
            </div>

            <input type="hidden" name="selected_seats" id="selectedSeats" value="">
            <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section_id); ?>"> <!-- Added section ID -->
            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>"> <!-- Ensure this is present -->
            <button type="submit" class="confirm-button">Confirm Booking</button>
        </form>
    </main>

    <div class="footerbox">
        <p>Malaysia . Penang</p>
    </div>

    <script>
        function selectSeat(seat) {
            if (seat.classList.contains('booked')) {
                alert("This seat is already booked.");
                return;
            }

            seat.classList.toggle('selected');
            updateSelectedSeats();
        }

        function updateSelectedSeats() {
            const selectedSeats = [];
            const selectedElements = document.querySelectorAll('.seat.selected');
            selectedElements.forEach(element => {
                selectedSeats.push(element.getAttribute('data-seat-number'));
            });
            document.getElementById('selectedSeats').value = selectedSeats.join(',');
        }
    </script>
</body>
</html>