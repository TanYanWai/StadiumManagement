<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

$userId = $_SESSION['user_id']; // Get the logged-in user's ID from the session

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "root";
$dbname = "FYP_BookMyTicket";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch admin information
$admin_name = ""; // Initialize the variable
$userSql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    $admin_name = $admin['username'] ?? ''; // Assign username to $admin_name
}

// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $eventTitle = $conn->real_escape_string($_POST['event_title']);
    $eventDescription = $conn->real_escape_string($_POST['event_description']);
    $eventCategory = $conn->real_escape_string($_POST['event_category']);
    $contactPerson = $conn->real_escape_string($_POST['contact_person']);
    $contactNumber = $conn->real_escape_string($_POST['contact_number']);
    $email = $conn->real_escape_string($_POST['email']);
    $eventTerms = $conn->real_escape_string($_POST['event_terms']);
    $eventTimeFrom = $conn->real_escape_string($_POST['event_time_from']);
    $eventTimeTo = $conn->real_escape_string($_POST['event_time_to']);

    // Validate required fields
    if (empty($eventTitle) || empty($eventDescription) || empty($contactPerson) || empty($contactNumber) || empty($email) || empty($eventTimeFrom) || empty($eventTimeTo)) {
        die("All fields are required.");
    }

    // Validate text inputs
    if (strlen($eventTitle) > 100 || !preg_match('/^[a-zA-Z0-9\s]+$/', $eventTitle)) {
        die("Invalid event title. Only letters, numbers, and spaces are allowed, and it must not exceed 100 characters.");
    }
    if (strlen($contactPerson) > 50 || !preg_match('/^[a-zA-Z\s]+$/', $contactPerson)) {
        die("Invalid contact person name. Only letters and spaces are allowed, and it must not exceed 50 characters.");
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }

    // Validate contact number format
    if (!preg_match('/^\d{10}$/', $contactNumber)) {
        die("Invalid contact number. It should be a 10-digit number.");
    }

    // Validate event time
    if ($eventTimeFrom >= $eventTimeTo) {
        die("Event start time must be earlier than the end time.");
    }

    // Handle event dates
    if (isset($_POST['event_dates']) && is_array($_POST['event_dates']) && !empty($_POST['event_dates'][0])) {
        $eventDate = $conn->real_escape_string($_POST['event_dates'][0]); // Use the first date

        // Validate date format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
            die("Invalid date format. Use YYYY-MM-DD.");
        }
    } else {
        die("Please provide at least one valid event date.");
    }

    // Insert event details into the database
    $sql = "INSERT INTO events (user_id, event_title, event_date, event_time_from, event_time_to, event_description, event_category, contact_person, contact_number, email, event_terms)
            VALUES ('$userId', '$eventTitle', '$eventDate', '$eventTimeFrom', '$eventTimeTo', '$eventDescription', '$eventCategory', '$contactPerson', '$contactNumber', '$email', '$eventTerms')";

    if ($conn->query($sql) === TRUE) {
        $eventId = $conn->insert_id; // Get the ID of the inserted event

        // Insert additional event dates into the event_dates table
        if (isset($_POST['event_dates']) && is_array($_POST['event_dates'])) {
            foreach ($_POST['event_dates'] as $date) {
                $eventDate = $conn->real_escape_string($date);

                // Validate date format
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $eventDate)) {
                    die("Invalid date format. Use YYYY-MM-DD.");
                }

                $dateSql = "INSERT INTO event_dates (event_id, event_date) VALUES ('$eventId', '$eventDate')";
                if (!$conn->query($dateSql)) {
                    echo "Error inserting event date: " . $conn->error;
                }
            }
        }

        // Handle event poster upload
        $targetDir = "uploads/";
        $posterName = "";
        if (isset($_FILES['event_poster']) && $_FILES['event_poster']['error'] == UPLOAD_ERR_OK) {
            $posterName = basename($_FILES['event_poster']['name']);
            $posterPath = $targetDir . $posterName;
            if (!move_uploaded_file($_FILES['event_poster']['tmp_name'], $posterPath)) {
                echo "Error uploading poster file.";
            }
        }

        // Redirect after successful submission
        header("Location: AdminEditSeatPrice.php?id=$eventId");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@100&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="AdminAddEvent.css">
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
                        <?php echo htmlspecialchars($admin_name); ?>!
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

<div class="main-content">
    <div class="container">
        <h3>Event Poster (Maximum size: 1920x600 pixels)</h3>
        <form action="AdminAddEvent.php" method="post" enctype="multipart/form-data">
            <label for="eventPoster">Upload Poster:</label>
            <input type="file" name="event_poster" accept="image/*" required>

            <h3>Images (Maximum 10)</h3>
            <label for="eventImages">Upload Event Images:</label>
            <input type="file" name="event_images[]" multiple accept="image/*" required>

            <h3>Event Dates (Add Multiple Dates)</h3>
            <label for="eventDates">Event Dates:</label>
            <div id="eventDatesContainer">
                <input type="date" id="eventDate" name="event_dates[]" required>
            </div>
            <button type="button" id="addDateButton">Add Another Date</button>

            <h3>Event Details</h3>
            <label for="eventTitle">Event Title:</label>
            <input type="text" id="eventTitle" name="event_title" required>

            <label for="eventDescription">Description:</label>
            <textarea id="eventDescription" name="event_description" rows="4" required></textarea>

            <label for="eventCategory">Event Category:</label>
            <select id="eventCategory" name="event_category" required>
                <option value="concert">Concert</option>
                <option value="conference">Conference</option>
                <option value="workshop">Workshop</option>
                <!-- Add more categories as needed -->
            </select>

            <label for="eventTimeFrom">Event Time From:</label>
            <input type="time" id="eventTimeFrom" name="event_time_from" required>

            <label for="eventTimeTo">Event Time To:</label>
            <input type="time" id="eventTimeTo" name="event_time_to" required>

            <h3>Contact Information</h3>
            <label for="contactPerson">Contact Person:</label>
            <input type="text" id="contactPerson" name="contact_person" required>

            <label for="contactNumber">Contact Number:</label>
            <input type="text" id="contactNumber" name="contact_number" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="eventTerms">Event Terms and Conditions:</label>
            <textarea id="eventTerms" name="event_terms" rows="4" required></textarea>

            <button type="submit">Create Event</button>
        </form>
    </div>
</div>

<script>
document.getElementById('addDateButton').addEventListener('click', function() {
    var dateInput = document.createElement('input');
    dateInput.type = 'date';
    dateInput.name = 'event_dates[]';
    dateInput.required = true;
    document.getElementById('eventDatesContainer').appendChild(dateInput);
});

document.querySelector('form').addEventListener('submit', function(e) {
    let valid = true;

    // Validate the number of uploaded images (Max 10)
    var eventImages = document.querySelector('input[name="event_images[]"]');
    if (eventImages && eventImages.files.length > 10) {
        alert("You can upload a maximum of 10 images.");
        valid = false;
    }

    // Validate that event_time_from is earlier than event_time_to
    var eventTimeFrom = document.getElementById('eventTimeFrom').value;
    var eventTimeTo = document.getElementById('eventTimeTo').value;
    if (eventTimeFrom && eventTimeTo && eventTimeFrom >= eventTimeTo) {
        alert("Event start time must be earlier than the end time.");
        valid = false;
    }

    // Validate event poster size (1920x600 max)
    var eventPoster = document.querySelector('input[name="event_poster"]');
    if (eventPoster && eventPoster.files.length > 0) {
        var file = eventPoster.files[0];
        var img = new Image();
        img.onload = function() {
            if (img.width > 1920 || img.height > 600) {
                alert("Event poster size must be 1920x600 pixels or smaller.");
                valid = false;
            }
        };
        img.src = URL.createObjectURL(file);
    }

    // Validate text inputs for length and characters
    var eventTitle = document.getElementById('eventTitle').value.trim();
    if (eventTitle.length > 100 || !/^[a-zA-Z0-9\s]+$/.test(eventTitle)) {
        alert("Invalid event title. Only letters, numbers, and spaces are allowed, and it must not exceed 100 characters.");
        valid = false;
    }

    var contactPerson = document.getElementById('contactPerson').value.trim();
    if (contactPerson.length > 50 || !/^[a-zA-Z\s]+$/.test(contactPerson)) {
        alert("Invalid contact person name. Only letters and spaces are allowed, and it must not exceed 50 characters.");
        valid = false;
    }

    // Validate contact number format
    var contactNumber = document.getElementById('contactNumber').value.trim();
    if (!/^\d{10}$/.test(contactNumber)) {
        alert("Invalid contact number. It should be a 10-digit number.");
        valid = false;
    }

    // Validate email format
    var email = document.getElementById('email').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert("Invalid email format.");
        valid = false;
    }

    // Check for empty required fields
    var requiredFields = document.querySelectorAll('[required]');
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            alert("Please fill out all required fields.");
            valid = false;
        }
    });

    // Prevent form submission if any validation fails
    if (!valid) {
        e.preventDefault();
    }
});
</script>

</body>
</html>
