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
        $user_name = htmlspecialchars($user['username']); // Escape to prevent XSS
    } else {
        $user_name = 'Guest'; // Default value if no user is found
    }

    $stmt->close();

    // Fetch all events (remove the user_id filter to fetch all events)
    $sql = "SELECT id, event_title FROM events";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $eventResult = $stmt->get_result();

    $events = [];
    while ($row = $eventResult->fetch_assoc()) {
        $events[] = $row;
    }

    $stmt->close();

    // Initialize sentiment counts
    $positiveCount = $neutralCount = $negativeCount = 0;

    // Check if an event is selected
    if (isset($_POST['event_id'])) {
        $event_id = $_POST['event_id'];

        // Fetch attendance data for the selected event
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
            $ratingStmt->bind_result($ratingCount, $ratingValue);

            // Initialize ratings
            $ratings = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            while ($ratingStmt->fetch()) {
                $ratings[$ratingValue] = $ratingCount;
            }

            $questions[] = [
                'question_text' => htmlspecialchars($row['question_text']), // Escape to prevent XSS
                'ratings' => array_values($ratings) // Flattening the array
            ];

            $ratingStmt->close();
        }
        $questionStmt->close();

        // Fetch user feedback for the selected event
        $sentimentSql = "SELECT feedback FROM OverallFeedback WHERE event_id = ? AND feedback IS NOT NULL";
        $stmt = $conn->prepare($sentimentSql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $feedbackResult = $stmt->get_result();

        $feedbackList = [];
        while ($row = $feedbackResult->fetch_assoc()) {
            $feedbackList[] = $row['feedback'];
        }
        $stmt->close();

        // Simple sentiment analysis logic
        foreach ($feedbackList as $feedback) {
            $feedback = strtolower($feedback);
            if (strpos($feedback, 'good') !== false || strpos($feedback, 'excellent') !== false || strpos($feedback, 'great') !== false) {
                $positiveCount++;
            } elseif (strpos($feedback, 'bad') !== false || strpos($feedback, 'terrible') !== false || strpos($feedback, 'poor') !== false) {
                $negativeCount++;
            } else {
                $neutralCount++;
            }
        }
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
    <title>Organizer Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="OrganizerDashboard.css"> <!-- Link to your CSS file -->
</head>
<body>

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
            <a href="AdminScanQRCode.php" class="nav-link">Scan QR</a>
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

<form method="POST" action="OrganizerDashboard.php">
    <label for="event_id">Select Event:</label>
    <select name="event_id" id="event_id">
        <?php foreach ($events as $event): ?>
            <option value="<?php echo $event['id']; ?>" <?php echo isset($event_id) && $event_id == $event['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($event['event_title']); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="Show Charts">
    <a href="AdminDisplayMessage.php" class="button" style="display: inline-block; padding: 8px 15px; background-color: #007BFF; color: white; text-decoration: none; border-radius: 4px; margin-left: 10px;">Manage Messages</a>
</form>

<?php if (isset($event_id)): ?>
    <div class="container">
        <div class="chart-container">
            <h2>Event Attendance Overview</h2>
            <canvas id="attendanceChart"></canvas>
        </div>

        <div class="chart-container">
            <h2>Sentiment Analysis Overview</h2>
            <canvas id="sentimentChart"></canvas>
        </div>

        <div class="charts">
            <?php foreach ($questions as $index => $question): ?>
                <div class="chart-container">
                    <h3>Ratings for: <?php echo $question['question_text']; ?></h3>
                    <canvas id="ratingChart<?php echo $index; ?>"></canvas>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<script>
    // Create the charts with Chart.js
    document.addEventListener("DOMContentLoaded", function () {
        <?php if (isset($event_id)): ?>
            // Attendance chart
            var attendanceChart = new Chart(document.getElementById('attendanceChart'), {
                type: 'pie',
                data: {
                    labels: ['Attended', 'Missed'],
                    datasets: [{
                        data: [<?php echo $totalAttendance; ?>, <?php echo $totalMissed; ?>],
                        backgroundColor: ['#36A2EB', '#FF5733']
                    }]
                }
            });

            // Sentiment analysis chart
            var sentimentChart = new Chart(document.getElementById('sentimentChart'), {
                type: 'pie',
                data: {
                    labels: ['Positive', 'Negative', 'Neutral'],
                    datasets: [{
                        data: [<?php echo $positiveCount; ?>, <?php echo $negativeCount; ?>, <?php echo $neutralCount; ?>],
                        backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                    }]
                }
            });

            // Rating charts for each question
            <?php foreach ($questions as $index => $question): ?>
                new Chart(document.getElementById('ratingChart<?php echo $index; ?>'), {
                    type: 'pie',
                    data: {
                        labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                        datasets: [{
                            data: <?php echo json_encode($question['ratings']); ?>,
                            backgroundColor: ['#FF5733', '#FF8C00', '#FFD700', '#90EE90', '#28a745']
                        }]
                    }
                });
            <?php endforeach; ?>
        <?php endif; ?>
    });
</script>

</body>
</html>
