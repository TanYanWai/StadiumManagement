<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "FYP_BookMyTicket";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the username based on the stored user ID
$userSql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_name = htmlspecialchars($user['username']);
}

// Check if the event_id is passed via the URL
if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']); // Convert to an integer to prevent SQL injection
} else {
    echo "No event selected.";
    exit();
}

// Fetch the event details (including the title)
$eventSql = "SELECT event_title FROM events WHERE id = ?";
$eventStmt = $conn->prepare($eventSql);
$eventStmt->bind_param("i", $event_id);
$eventStmt->execute();
$eventResult = $eventStmt->get_result();

if ($eventResult->num_rows > 0) {
    $event = $eventResult->fetch_assoc();
    $event_title = htmlspecialchars($event['event_title']);
} else {
    echo "Event not found.";
    exit();
}

// Fetch questions related to the event
$questionSql = "SELECT id, question_text FROM Questions";
$questionStmt = $conn->prepare($questionSql);
$questionStmt->execute();
$questions = $questionStmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Process the overall feedback for sentiment analysis
    $overall_feedback = $_POST['overall_feedback'] ?? '';

    // Prepare to insert the feedback into the Responses table
    $responseSql = "INSERT INTO Responses (user_id, event_id, question_id, rating) VALUES (?, ?, ?, ?)";
    $responseStmt = $conn->prepare($responseSql);

    // Loop through each question's rating
    $responses = $_POST['questions'] ?? [];
    foreach ($responses as $question_id => $response_data) {
        $rating = intval($response_data['rating']); // Get the rating

        // Bind parameters and execute the statement
        $responseStmt->bind_param("iiii", $user_id, $event_id, $question_id, $rating);

        if (!$responseStmt->execute()) {
            // Handle errors if the insert fails
            echo "Error inserting feedback for question ID {$question_id}: " . $conn->error . "<br>";
        }
    }

    // Insert the overall feedback into the OverallFeedback table
    if (!empty($overall_feedback)) {
        $overallFeedbackSql = "INSERT INTO OverallFeedback (user_id, event_id, feedback) VALUES (?, ?, ?)";
        $overallFeedbackStmt = $conn->prepare($overallFeedbackSql);
        $overallFeedbackStmt->bind_param("iis", $user_id, $event_id, $overall_feedback);
        if (!$overallFeedbackStmt->execute()) {
            echo "Error inserting overall feedback: " . $conn->error . "<br>";
        }
    }

    // Set a session variable to indicate feedback was submitted
    $_SESSION['feedback_submitted'] = true;

    // Close the prepared statements
    $responseStmt->close();
    if (isset($overallFeedbackStmt)) {
        $overallFeedbackStmt->close();
    }

    // Redirect to avoid form resubmission
    header("Location: RateEvent.php?event_id=" . $event_id);
    exit();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="RateEvent.css"> <!-- Link to your CSS file -->
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
            <a href="ChooseEvent.php" class="nav-link">Rate Events</a>
            <a href="ContactUs.php" class="nav-link">Contact Us</a>
            <a href="AboutUs.php" class="nav-link">About Us</a>
        </nav>
        <div class="button-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="ProfileContainer">
                    <a href="UserProfile.php" id="userProfileLink">
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
    <h1>Feedback for Event: <?php echo htmlspecialchars($event_title); ?></h1>

    <?php if (isset($_SESSION['feedback_submitted'])): ?>
        <p>Thank you for your feedback!</p>
        <?php unset($_SESSION['feedback_submitted']); // Unset the session variable after displaying the message ?>
    <?php endif; ?>

    <form method="POST">
        <h3>Questions for feedback:</h3>

        <?php while ($row = $questions->fetch_assoc()) { ?>
            <label><?php echo htmlspecialchars($row['question_text']); ?></label>
            <div class="star-rating">
                <span class="star" data-value="1">★</span>
                <span class="star" data-value="2">★</span>
                <span class="star" data-value="3">★</span>
                <span class="star" data-value="4">★</span>
                <span class="star" data-value="5">★</span>
                <input type="hidden" name="questions[<?php echo $row['id']; ?>][rating]" class="rating-value" value="">
            </div>
            <br><br>
        <?php } ?>

        <!-- Single feedback box for sentiment analysis -->
        <h3>Your overall feedback about the event:</h3>
        <textarea name="overall_feedback" rows="5" placeholder="Enter your overall feedback here..."></textarea>
        
        <input type="submit" value="Submit Feedback">
    </form>

    <div class="footerbox">
        <p>Malaysia . Penang</p>
    </div>

    <script>
    document.querySelectorAll('.star-rating .star').forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.getAttribute('data-value');
            const stars = this.parentNode.querySelectorAll('.star');

            // Clear previous selections
            stars.forEach(s => s.classList.remove('selected'));

            // Highlight selected stars
            for (let i = 0; i < rating; i++) {
                stars[i].classList.add('selected');
            }

            // Update the hidden input value
            this.parentNode.querySelector('.rating-value').value = rating;
        });
    });
    </script>
</body>
</html>
