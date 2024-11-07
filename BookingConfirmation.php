<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

// Retrieve booking details from session
$event_date = isset($_SESSION['event_date']) ? $_SESSION['event_date'] : 'N/A';
$selected_seats = isset($_SESSION['selected_seats']) ? $_SESSION['selected_seats'] : [];
$total_price = isset($_SESSION['total_price']) ? $_SESSION['total_price'] : 0;
$confirmation_number = isset($_SESSION['confirmation_number']) ? $_SESSION['confirmation_number'] : rand(100000, 999999); // Use stored confirmation number or generate a new one

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link rel="stylesheet" href="BookingConfirmation.css">
</head>
<body>
    <header>
        <h1>Booking Confirmation</h1>
    </header>

    <main>
        <h2>Your booking has been confirmed!</h2>
        <p>Thank you for your purchase. You will receive a confirmation email shortly.</p>

        <h3>Booking Details:</h3>
        <p><strong>Event Date:</strong> <?php echo htmlspecialchars($event_date); ?></p>
        <p><strong>Selected Seats:</strong> <?php echo htmlspecialchars(implode(', ', $selected_seats)); ?></p>
        <p><strong>Total Price:</strong> $<?php echo htmlspecialchars($total_price); ?></p>
        <p><strong>Confirmation Number:</strong> <?php echo htmlspecialchars($confirmation_number); ?></p>

        <p><a href="UserHome.php">Return to Homepage</a></p>
        <p><a href="ChooseEvent.php">View My Bookings</a></p>
    </main>
</body>
</html>
