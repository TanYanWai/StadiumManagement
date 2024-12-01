<?php
session_start();

// Database connection
$servername = "localhost"; 
$username = "root"; 
$password = "root"; 
$dbname = "FYP_BookMyTicket"; 

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize organizer name with a default value
$organizer_name = "Guest";

// Fetch organizer's name if logged in
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // Use the session variable

    $sql = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $organizer_name = $row['username'] ?? "Organizer"; // Fallback to "Organizer" if username is null
    }
    $stmt->close();
}

// Fetch the event ID from the URL
if (isset($_GET['id'])) {
    $event_id = intval($_GET['id']); // Get event ID from URL
} else {
    die("No event ID specified.");
}

// Set default values for rows and seats
$rows = 10; // 10 rows, each containing 25 seats (so 250 seats in total)
$seats_per_row = 25;
$seat_types = ['Elite', 'Premium', 'Standard', 'Normal']; // Define seat types

// Handle form submission for seat prices
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    foreach ($_POST['seat_type'] as $row => $seat_type) {
        $price = floatval($_POST['seat_price'][$seat_type]); // Get price for the current seat type

        if ($price === 0) {
            echo "Price for row $row cannot be zero. Please enter a valid price.<br>";
            continue; // Skip this iteration if the price is invalid
        }

        $seat_start = ($row - 1) * $seats_per_row + 1; // Start of seat range for this row
        $seat_end = $row * $seats_per_row; // End of seat range for this row

        $seat_type = $conn->real_escape_string($seat_type);
        $price = $conn->real_escape_string($price);

        for ($seat_number = $seat_start; $seat_number <= $seat_end; $seat_number++) {
            $sql = "INSERT INTO seat_prices (event_id, `row_number`, seat_type, seat_price)
                    VALUES ('$event_id', '$row', '$seat_type', '$price')
                    ON DUPLICATE KEY UPDATE seat_type='$seat_type', seat_price='$price'";

            if ($conn->query($sql) !== TRUE) {
                echo "Error updating seat prices: " . $conn->error;
            }
        }
    }

    // Redirect after successful submission
    header("Location: OrganizerDashboard.php");
    exit();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Seat Prices</title>
    <link rel="stylesheet" href="OrganizerEditSeatPrice.css">
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="ProfileContainer">
                    <a href="OrganizerProfile.php" id="organizerProfileLink">
                        <?php echo htmlspecialchars($organizer_name ?? "Guest", ENT_QUOTES, 'UTF-8'); ?>
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
    <div class="edit-seat-container">
        <h1>Edit Seat Prices for Event ID: <?php echo $event_id; ?></h1>
        <form method="POST" action="">
            <h2>Set Prices for Each Seat Type</h2>
            <div class="seat-pricing">
                <?php foreach ($seat_types as $type): ?>
                    <div class="seat-type">
                        <label for="<?php echo strtolower($type); ?>_price"><?php echo $type; ?> Price:</label>
                        <input type="number" name="seat_price[<?php echo $type; ?>]" id="<?php echo strtolower($type); ?>_price" step="0.01" required>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <h2>Select Seat Type for Each Row</h2>
            <table>
                <thead>
                    <tr>
                        <th>Row Number</th>
                        <th>Seat Type</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($row = 1; $row <= $rows; $row++): ?>
                    <tr>
                        <td><?php echo "Row $row"; ?></td>
                        <td>
                            <select name="seat_type[<?php echo $row; ?>]" required>
                                <?php foreach ($seat_types as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <button type="submit">Save Prices</button>
        </form>
    </div>
</main>
</body>
</html>
