<?php
require('vendor/autoload.php'); // Include Composer's autoloader
use Dompdf\Dompdf;

// Database connection details
$servername = "localhost";
$username = "root"; // Your database username
$password = "root"; // Your database password
$dbname = "FYP_BookMyTicket"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];

    // Fetch attendance data
    $attendanceQuery = "SELECT COUNT(*) AS totalBookings, 
                               SUM(CASE WHEN attendance_status = 1 THEN 1 ELSE 0 END) AS totalAttendance, 
                               SUM(CASE WHEN attendance_status = 0 THEN 1 ELSE 0 END) AS totalMissed 
                        FROM attendances WHERE event_id = ?";
    $stmt = $conn->prepare($attendanceQuery);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendanceData = $result->fetch_assoc();
    $totalBookings = $attendanceData['totalBookings'] ?? 0;
    $totalAttendance = $attendanceData['totalAttendance'] ?? 0;
    $totalMissed = $attendanceData['totalMissed'] ?? 0;

    // Initialize counts for sentiment analysis (if you wish to add it back later)
    $positiveCount = 0;
    $neutralCount = 0;
    $negativeCount = 0;

    // Here you can add code for sentiment analysis if needed, e.g., loop through feedback

    // Fetch question ratings
    $questionsQuery = "SELECT question_text, 
                              (SELECT COUNT(*) FROM responses WHERE question_id = questions.id AND rating = 1) AS '1_star', 
                              (SELECT COUNT(*) FROM responses WHERE question_id = questions.id AND rating = 2) AS '2_star', 
                              (SELECT COUNT(*) FROM responses WHERE question_id = questions.id AND rating = 3) AS '3_star', 
                              (SELECT COUNT(*) FROM responses WHERE question_id = questions.id AND rating = 4) AS '4_star', 
                              (SELECT COUNT(*) FROM responses WHERE question_id = questions.id AND rating = 5) AS '5_star' 
                        FROM Questions";
    $result = $conn->query($questionsQuery);
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }

    // Prepare content for the report
    $reportContent = '<h1>Final Report for Event ID: ' . htmlspecialchars($event_id) . '</h1>';
    $reportContent .= '<h2>Attendance Overview</h2>';
    $reportContent .= '<p>Total Bookings: ' . htmlspecialchars($totalBookings) . '</p>';
    $reportContent .= '<p>Total Attendance: ' . htmlspecialchars($totalAttendance) . '</p>';
    $reportContent .= '<p>Total Missed: ' . htmlspecialchars($totalMissed) . '</p>';

    // Sentiment Analysis Data (if you implement it)
    $reportContent .= '<h2>Sentiment Analysis</h2>';
    $reportContent .= '<p>Positive Feedback: ' . htmlspecialchars($positiveCount) . '</p>';
    $reportContent .= '<p>Neutral Feedback: ' . htmlspecialchars($neutralCount) . '</p>';
    $reportContent .= '<p>Negative Feedback: ' . htmlspecialchars($negativeCount) . '</p>';

    // Fetch and add question ratings
    foreach ($questions as $question) {
        $reportContent .= '<h2>' . htmlspecialchars($question['question_text']) . '</h2>';
        $reportContent .= '<ul>';
        $reportContent .= '<li>1 Star: ' . htmlspecialchars($question['1_star']) . '</li>';
        $reportContent .= '<li>2 Star: ' . htmlspecialchars($question['2_star']) . '</li>';
        $reportContent .= '<li>3 Star: ' . htmlspecialchars($question['3_star']) . '</li>';
        $reportContent .= '<li>4 Star: ' . htmlspecialchars($question['4_star']) . '</li>';
        $reportContent .= '<li>5 Star: ' . htmlspecialchars($question['5_star']) . '</li>';
        $reportContent .= '</ul>';
    }

    // Initialize DOMPDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($reportContent);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    // Output the generated PDF to Browser
    $dompdf->stream("final_report_event_{$event_id}.pdf", ["Attachment" => true]);
}

// Close the database connection
$conn->close();
?>
