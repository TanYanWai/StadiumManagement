<?php
session_start(); // Start the session

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

    // Fetch total booking count from the bookings table
    $sql = "SELECT COUNT(*) as count FROM bookings";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($totalBookings);
    $stmt->fetch();
    $stmt->close();

    // Fetch attended booking count (attendance_status = 1) from the attendances table
    $sql = "SELECT COUNT(*) as count FROM attendances WHERE attendance_status = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($totalAttendance);
    $stmt->fetch();
    $stmt->close();

    // Fetch missed booking count (attendance_status = 0) from the attendances table
    $sql = "SELECT COUNT(*) as count FROM attendances WHERE attendance_status = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $stmt->bind_result($totalMissed);
    $stmt->fetch();
    $stmt->close();

    $conn->close();
} else {
    $user_name = 'Guest'; // Default value if the user is not logged in
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<h1>Welcome, <?php echo $user_name; ?></h1>

<div>
    <canvas id="attendanceChart"></canvas>
</div>

<script>
    var ctx = document.getElementById('attendanceChart').getContext('2d');
    var attendanceChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Total Bookings', 'Attended', 'Missed'],
            datasets: [{
                label: 'Event Attendance Details',
                data: [
                    <?php echo $totalBookings; ?>,
                    <?php echo $totalAttendance; ?>,
                    <?php echo $totalMissed; ?>
                ],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56'],
                hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Event Attendance Overview'
                }
            }
        }
    });
</script>

</body>
</html>


