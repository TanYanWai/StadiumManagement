<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Get the QR code data from the request
$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['qr_code'])) {
    echo json_encode(['success' => false, 'message' => 'QR code data not provided']);
    exit();
}

$qr_code = $data['qr_code'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "FYP_BookMyTicket";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Parse the QR code data
list($userInfo, $eventInfo, $seatsInfo) = explode("\n", $qr_code);
preg_match('/User: (\d+)/', $userInfo, $userMatch);
preg_match('/Event: (\d+)/', $eventInfo, $eventMatch);
preg_match('/Seats: (\d+)/', $seatsInfo, $seatsMatch);

if (empty($userMatch) || empty($eventMatch) || empty($seatsMatch)) {
    echo json_encode(['success' => false, 'message' => 'Invalid QR code format']);
    exit();
}

$user_id = $userMatch[1];
$event_id = $eventMatch[1];
$seat_number = $seatsMatch[1];

// Update attendance
$sql = "UPDATE attendances SET attendance_status = 1, scan_time = NOW() WHERE user_id = ? AND event_id = ? AND seat_number = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare statement failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param('iis', $user_id, $event_id, $seat_number);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Attendance marked']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update attendance: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
