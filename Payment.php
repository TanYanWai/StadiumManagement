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

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Fetch user details if needed (optional)
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get the selected seats, event date, and section ID from the POST request
$selectedSeats = isset($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];
$event_date = isset($_POST['event_date']) ? $_POST['event_date'] : '';
$section_id = isset($_POST['section_id']) ? $_POST['section_id'] : '';
$event_id = isset($_POST['event_id']) ? $_POST['event_id'] : '';

// Define seat price (assuming a fixed price for simplicity; you can fetch it from the database if needed)
$seatPrice = 100; // Example price for each seat
$totalPrice = count($selectedSeats) * $seatPrice;

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link rel="stylesheet" href="payment.css">
</head>
<body>
    <header>
        <h1>Payment</h1>
    </header>

    <main class="payment-container">
        <h2>Your Payment Details</h2>
        <p>Event Date: <?php echo htmlspecialchars($event_date); ?></p>
        <p>Selected Seats: <?php echo htmlspecialchars(implode(', ', $selectedSeats)); ?></p>
        <p>Section: <?php echo htmlspecialchars($section_id); ?></p>
        <p>Total Price: $<?php echo htmlspecialchars($totalPrice); ?></p>

        <form action="ConfirmBooking.php" method="POST">
            <input type="hidden" name="selected_seats" value="<?php echo htmlspecialchars(implode(',', $selectedSeats)); ?>">
            <input type="hidden" name="event_date" value="<?php echo htmlspecialchars($event_date); ?>">
            <input type="hidden" name="section_id" value="<?php echo htmlspecialchars($section_id); ?>">
            <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">

            <!-- Credit Card Details -->
            <h3>Credit Card Information</h3>
            <label for="card_number">Card Number:</label>
            <input type="text" id="card_number" name="card_number" required pattern="\d{16}" title="Please enter a valid 16-digit card number.">

            <label for="card_expiry">Expiry Date:</label>
            <input type="month" id="card_expiry" name="card_expiry" required>

            <label for="card_cvc">CVC:</label>
            <input type="text" id="card_cvc" name="card_cvc" required pattern="\d{3}" title="Please enter a valid 3-digit CVC.">

            <button type="submit" class="confirm-button">Complete Payment</button>
        </form>
    </main>

    <footer class="footerbox">
        <p>&copy; 2024 Hong Leong Bank. All rights reserved.</p>
    </footer>
</body>
</html>
