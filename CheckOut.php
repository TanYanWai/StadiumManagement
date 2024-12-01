<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

// Database connection
$host = "localhost"; // Change this to "127.0.0.1" if "localhost" doesn't work
$username = "root"; // MAMP default username is usually root
$password = "root"; // MAMP default password is usually root
$dbname = "FYP_BookMyTicket"; // Replace with your actual database name


// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($user_name);
$stmt->fetch();
$stmt->close();

// Get the selected seats, event date, and section ID from the POST request
$selectedSeats = isset($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];
$event_date = isset($_POST['event_date']) ? $_POST['event_date'] : '';
$section_id = isset($_POST['section_id']) ? $_POST['section_id'] : ''; // Get section ID
$event_id = isset($_POST['event_id']) ? $_POST['event_id'] : ''; // Get event ID

// Initialize total price
$totalPrice = 0;

// Ensure the selected seats and event_id are provided
if (!empty($selectedSeats) && $event_id) {
    // Prepare the SQL query to fetch row prices
    $sql = "SELECT seat_price FROM seat_prices WHERE event_id = ? AND `row_number` = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    // Extract unique row numbers from selected seats
    $rowCounts = []; // Array to store the count of seats selected per row
    foreach ($selectedSeats as $seat) {
        // Extract row number from seat string (e.g., "1-12" -> 1)
        $rowNumber = (int)explode('-', $seat)[0];
        if (!isset($rowCounts[$rowNumber])) {
            $rowCounts[$rowNumber] = 0;
        }
        $rowCounts[$rowNumber]++;
    }

    // Fetch seat prices for each unique row
    foreach ($rowCounts as $rowNumber => $seatCount) {
        // Log the query for debugging
        error_log("Fetching price for event_id: $event_id and row_number: $rowNumber");

        $stmt->bind_param("ii", $event_id, $rowNumber);

        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        $stmt->bind_result($seatPrice);
        if ($stmt->fetch()) {
            // Add the total price for seats in this row
            $totalPrice += $seatPrice * $seatCount;
        } else {
            error_log("No seat price found for row_number: $rowNumber");
            $totalPrice += 100 * $seatCount; // Default price for missing rows
        }
    }
    $stmt->close();
}

// Log final total price for debugging
error_log("Total price calculated: $totalPrice");


// Check if user name is fetched; if not, set to 'Guest'
$user_name = $user_name ?: 'Guest';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="CheckOut.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
                    <a href="UserProfile.php" id="userProfileLink"><?php echo htmlspecialchars($user_name); ?></a>
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
        <h2>Your Booking Details</h2>
        <p>Event Date: <?php echo htmlspecialchars($event_date); ?></p>
        <p>Selected Seats: <?php echo htmlspecialchars(implode(', ', $selectedSeats)); ?></p>
        <p>Section: <?php echo htmlspecialchars($section_id); ?></p>
        <p>Total Price: $<?php echo htmlspecialchars($totalPrice); ?></p>

        <form action="Payment.php" method="POST">
            <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars(implode(',', $selectedSeats)); ?>">
            <input type="hidden" name="event_date" value="<?php echo htmlspecialchars($event_date); ?>">
            <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section_id); ?>">
            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
            <button type="submit" class="confirm-button">Confirm Payment</button>
        </form>
    </main>

    <div class="footerbox">
        <p>Malaysia . Penang</p>
    </div>
</body>
</html>
