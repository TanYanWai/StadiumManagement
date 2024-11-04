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
    $sql = "SELECT username, user_role FROM users WHERE id = ?";
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

    // Fetch all events
    $sql = "SELECT id, event_title FROM events"; // Fetch all events for admin
    $eventResult = $conn->query($sql);
    $events = [];

    while ($row = $eventResult->fetch_assoc()) {
        $events[] = $row;
    }

    // Check if an event is selected
    if (isset($_POST['event_id'])) {
        $event_id = $_POST['event_id'];

        // Fetch the attendance data for the selected event
        $sql = "SELECT COUNT(*) as count FROM bookings WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->bind_result($totalBookings);
        $stmt->fetch();
        $stmt->close();

        // Fetch attended booking count (attendance_status = 1)
        $sql = "SELECT COUNT(*) as count FROM attendances WHERE event_id = ? AND attendance_status = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->bind_result($totalAttendance);
        $stmt->fetch();
        $stmt->close();

        // Fetch missed booking count (attendance_status = 0)
        $sql = "SELECT COUNT(*) as count FROM attendances WHERE event_id = ? AND attendance_status = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $stmt->bind_result($totalMissed);
        $stmt->fetch();
        $stmt->close();

        // Fetch ratings for questions related to the event
        $questions = [];
        $questionSql = "SELECT id, question_text FROM Questions";
        $questionStmt = $conn->prepare($questionSql);
        $questionStmt->execute();
        $questionResult = $questionStmt->get_result();

        while ($row = $questionResult->fetch_assoc()) {
            $question_id = $row['id'];
            $ratingsSql = "SELECT COUNT(rating) AS count, rating FROM Responses WHERE event_id = ? AND question_id = ? GROUP BY rating";
            $ratingStmt = $conn->prepare($ratingsSql);
            $ratingStmt->bind_param("ii", $event_id, $question_id);
            $ratingStmt->execute();
            $ratingResult = $ratingStmt->get_result();

            $ratings = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            while ($ratingRow = $ratingResult->fetch_assoc()) {
                $ratings[$ratingRow['rating']] = $ratingRow['count'];
            }

            $questions[] = [
                'question_text' => $row['question_text'],
                'ratings' => array_values($ratings) // Flattening the array
            ];

            $ratingStmt->close();
        }
        $questionStmt->close();
    }

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
    <title>Admin Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="OrganizerDashboard.css"> <!-- Link to your CSS file -->
    <style>
        /* Add some basic styles */
        .chart-container {
            margin: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        canvas {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<header class="header">
    <div class="navigation">
        <div class="brand">
            <a href="#" class="logo"><i class="fas fa-heartbeat"></i><b>BookMyTicket</b></a>
        </div>
        <nav class="nav">
            <a href="AdminDashboard.php" class="nav-link">Home</a>
            <a href="AdminAddEvent.php" class="nav-link">Create Event</a>
            <a href="AdminSelectEvent.php" class="nav-link">Edit Event</a>
            <a href="OrganizerRegistration.php" class="nav-link">Create account</a>
            <a href="AdminProfile.php" class="nav-link">Profile</a>
        </nav>
        <div class="button-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="ProfileContainer">
                    <a href="OrganiserProfile.php" id="userProfileLink">
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
<body>

<form method="POST">
    <label for="event_id">Select Event:</label>
    <select name="event_id" id="event_id">
        <?php foreach ($events as $event): ?>
            <option value="<?php echo $event['id']; ?>" <?php echo isset($event_id) && $event_id == $event['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($event['event_title']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="Show Charts">
</form>

<?php if (isset($event_id)): ?>
    <div class="container">
        <div class="chart-container">
            <h2>Event Attendance Overview</h2>
            <canvas id="attendanceChart"></canvas>
        </div>

        <?php foreach ($questions as $index => $question): ?>
        <div class="chart-container">
            <canvas id="questionChart<?php echo $index; ?>"></canvas>
        </div>
        <?php endforeach; ?>
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

        <?php foreach ($questions as $index => $question): ?>
        var ctx<?php echo $index; ?> = document.getElementById('questionChart<?php echo $index; ?>').getContext('2d');
        var questionChart<?php echo $index; ?> = new Chart(ctx<?php echo $index; ?>, {
            type: 'pie',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: '<?php echo htmlspecialchars($question['question_text']); ?> Ratings',
                    data: <?php echo json_encode($question['ratings']); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#FF9F40', '#4BC0C0'],
                    hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#FF9F40', '#4BC0C0']
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
                        text: '<?php echo htmlspecialchars($question['question_text']); ?> Ratings'
                    }
                }
            }
        });
        <?php endforeach; ?>
    </script>
<?php endif; ?>

</body>
</html>
