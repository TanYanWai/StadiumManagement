<?php
session_start();

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

// Include the QR code generator and SendGrid
require 'vendor/autoload.php'; // Ensure SendGrid and QR code libraries are autoloaded
use SendGrid\Mail\Mail;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "FYP_BookMyTicket";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve user's email based on user_id from session
$user_id = $_SESSION['user_id'];
$query = "SELECT email FROM users WHERE id = ?";
$stmt_email = $conn->prepare($query);
$stmt_email->bind_param("i", $user_id);
$stmt_email->execute();
$result_email = $stmt_email->get_result();
if ($result_email->num_rows > 0) {
    $user = $result_email->fetch_assoc();
    $user_email = $user['email']; // Store the email in a variable
} else {
    die("User email not found in the database.");
}

// Get the selected seats, event date, section ID, and event ID from the POST request
$selectedSeats = isset($_POST['selected_seats']) ? explode(',', $_POST['selected_seats']) : [];
$event_date = isset($_POST['event_date']) ? $_POST['event_date'] : '';
$section_id = isset($_POST['section_id']) ? $_POST['section_id'] : '';
$event_id = isset($_POST['event_id']) ? $_POST['event_id'] : ''; // Capture event_id

// Check if event_id is empty
if (empty($event_id)) {
    die("Event ID is missing.");
}

// Check if user exists
$user_check_query = "SELECT id FROM users WHERE id = ?";
$stmt_user_check = $conn->prepare($user_check_query);
$stmt_user_check->bind_param("i", $user_id);
$stmt_user_check->execute();
$result_user_check = $stmt_user_check->get_result();
if ($result_user_check->num_rows === 0) {
    die("User ID does not exist.");
}

// Check if event exists
$event_check_query = "SELECT id FROM events WHERE id = ?";
$stmt_event_check = $conn->prepare($event_check_query);
$stmt_event_check->bind_param("i", $event_id);
$stmt_event_check->execute();
$result_event_check = $stmt_event_check->get_result();
if ($result_event_check->num_rows === 0) {
    die("Event ID does not exist.");
}

// Define seat price (you may fetch this from the database)
$seatPrice = 100; // Example price
$totalPrice = count($selectedSeats) * $seatPrice; // Calculate total price

// Prepare the statement for inserting bookings (including QR code path)
$stmt_booking = $conn->prepare("INSERT INTO bookings (user_id, event_id, seat_number, event_date, section_id, qr_code_image_path) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt_booking) {
    die("Prepare failed for bookings: " . $conn->error);
}

// Prepare the statement for inserting attendances
$stmt_attendance = $conn->prepare("INSERT INTO attendances (user_id, event_id, seat_number, attendance_status) VALUES (?, ?, ?, ?)");
if (!$stmt_attendance) {
    die("Prepare failed for attendances: " . $conn->error);
}

// Check if selected seats are not empty
if (empty($selectedSeats)) {
    die("No seats selected.");
}

// Set the QR code directory and check if it exists
$qrCodeDir = __DIR__ . '/qrcodes/';
if (!is_dir($qrCodeDir)) {
    mkdir($qrCodeDir, 0777, true); // Create the directory if it doesn't exist
}

$qrCodeImagePath = $qrCodeDir . 'booking_qr_' . time() . '.png'; // Initialize QR code path
$attendance_status = 0; // Default value (not attended)

foreach ($selectedSeats as $seat) {
    if (empty($seat)) {
        continue; // Skip empty seats
    }

    // Cast seat number as integer
    $seat_number = (int)$seat;

    // Insert into bookings table (including QR code image path)
    $stmt_booking->bind_param("iissss", $user_id, $event_id, $seat_number, $event_date, $section_id, $qrCodeImagePath);
    if (!$stmt_booking->execute()) {
        echo "Error executing query for bookings: " . $stmt_booking->error . "<br>";
        exit; // Stop execution if there’s an error
    }

    // Log the values being inserted into attendances
    echo "Inserting into attendances: User ID = $user_id, Event ID = $event_id, Seat Number = $seat_number, Attendance Status = $attendance_status<br>";

    // Insert into attendances table
    $stmt_attendance->bind_param("iisi", $user_id, $event_id, $seat_number, $attendance_status);
    if (!$stmt_attendance->execute()) {
        echo "Error executing query for attendances: " . $stmt_attendance->error . "<br>";
        exit; // Stop execution if there’s an error
    }
}

// Generate QR code for the booking
$qrCodeData = "User: {$_SESSION['user_id']}\nEvent: {$event_id}\nSeats: " . implode(',', $selectedSeats) . "\nDate: {$event_date}\nSection: {$section_id}";
$qrCode = new QrCode($qrCodeData);
$qrCode->setSize(300); // Set size of the QR code

// Create a PngWriter instance
$writer = new PngWriter();

// Attempt to save the QR code image to a file
try {
    $result = $writer->write($qrCode);
    $result->saveToFile($qrCodeImagePath);
} catch (Exception $e) {
    die("Failed to save QR code image to file: " . $e->getMessage());
}

// Send QR code via email to the user using SendGrid
$mail = new Mail();
$mail->setFrom('p21013583@student.newinti.edu.my', 'Lucas'); // Use verified email
$mail->setSubject('Your Event Booking Confirmation');
$mail->addTo($user_email, 'User'); // Send to the user's email

// Attach QR code with proper filename
$mail->addAttachment(
    file_get_contents($qrCodeImagePath), // File content
    'image/png',                         // MIME type
    'booking_qr_code.png'                // Filename for the attachment
);

// Create the email body
$mail->addContent("text/html", "Dear User,<br><br>Thank you for booking with us!<br><br>"
                . "Event ID: {$event_id}<br>"
                . "Seats: " . implode(',', $selectedSeats) . "<br>"
                . "Date: {$event_date}<br>"
                . "Section: {$section_id}<br><br>"
                . "Please find your QR code attached. Present it at the event to confirm your attendance.");

// Send the email
// api key is here
$sendgrid = new SendGrid(''); // Replace with your actual SendGrid API key
try {
    $response = $sendgrid->send($mail);
    if ($response->statusCode() == 202) {
        echo 'Email has been sent with the QR code!';
    } else {
        echo 'Email not sent. Status code: ' . $response->statusCode();
        echo 'Response body: ' . $response->body();
    }
} catch (Exception $e) {
    echo 'Caught exception: ' . $e->getMessage() . "\n";
}

// Set session variables for confirmation page
$_SESSION['event_date'] = $event_date;
$_SESSION['selected_seats'] = $selectedSeats;
$_SESSION['total_price'] = $totalPrice;
$_SESSION['confirmation_number'] = rand(100000, 999999); // Generate a confirmation number

// Close statements and connection
$stmt_booking->close();
$stmt_attendance->close();
$conn->close();

// Redirect to confirmation page after booking
header("Location: BookingConfirmation.php");
exit();
?>
