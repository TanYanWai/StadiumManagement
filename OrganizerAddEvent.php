<?php
// Start the session
session_start(); 

// Check if the user is logged in by verifying the session
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

// Fetch the organizer's name from the database
$sql = "SELECT username FROM users WHERE id = '$userId'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $organizer_name = $row['username']; // Assuming 'username' is the name field
} else {
    $organizer_name = "Organizer"; // Default value if not found
}


// Handle the form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $eventTitle = $conn->real_escape_string($_POST['event_title']);
    $eventDescription = $conn->real_escape_string($_POST['event_description']);
    $eventCategory = $conn->real_escape_string($_POST['event_category']);
    $contactPerson = $conn->real_escape_string($_POST['contact_person']);
    $contactNumber = $conn->real_escape_string($_POST['contact_number']);
    $email = $conn->real_escape_string($_POST['email']);
    $eventTerms = $conn->real_escape_string($_POST['event_terms']);
    
    // Get event time details
    $eventTimeFrom = $conn->real_escape_string($_POST['event_time_from']);
    $eventTimeTo = $conn->real_escape_string($_POST['event_time_to']);

    // Handle multiple event dates
    if (isset($_POST['event_dates']) && is_array($_POST['event_dates'])) {
        $eventDate = $conn->real_escape_string($_POST['event_dates'][0]); // Get the first event date
    } else {
        die("No event dates provided.");
    }

    // Insert event details into events table
    $sql = "INSERT INTO events (user_id, event_title, event_date, event_time_from, event_time_to, event_description, event_category, contact_person, contact_number, email, event_terms)
            VALUES ('$userId', '$eventTitle', '$eventDate', '$eventTimeFrom', '$eventTimeTo', '$eventDescription', '$eventCategory', '$contactPerson', '$contactNumber', '$email', '$eventTerms')";

    if ($conn->query($sql) === TRUE) {
        $eventId = $conn->insert_id; // Get the ID of the inserted event

        // Handle multiple event dates
        if (isset($_POST['event_dates']) && is_array($_POST['event_dates'])) {
            foreach ($_POST['event_dates'] as $eventDate) {
                $eventDate = $conn->real_escape_string($eventDate);
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
                echo "Error uploading poster file: " . $posterName . "<br>";
            }
        }

        // Handle event images upload
        $imagePaths = [];
        if (isset($_FILES['event_images']) && $_FILES['event_images']['error'][0] == UPLOAD_ERR_OK) {
            foreach ($_FILES['event_images']['tmp_name'] as $key => $tmpName) {
                $fileName = basename($_FILES['event_images']['name'][$key]);
                $imagePath = $targetDir . $fileName;
                if (move_uploaded_file($tmpName, $imagePath)) {
                    $imagePaths[] = $fileName; // Add image to array for later insertion
                } else {
                    echo "Error uploading image file: " . $fileName . "<br>";
                }
            }
        }

        // Insert poster and images into event_images table
        $imagePathsJson = json_encode($imagePaths);
        $mediaSql = "INSERT INTO event_images (event_id, event_poster, image_path) VALUES ('$eventId', '$posterName', '$imagePathsJson')";
        if ($conn->query($mediaSql) !== TRUE) {
            echo "Error inserting media paths: " . $conn->error;
        }

        // Redirect after successful submission with the event ID
        header("Location: EditSeatPrice.php?id=$eventId");
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
} else {
    echo "Invalid request method: " . $_SERVER['REQUEST_METHOD']; // Debugging line
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
            <a href="#" class="logo"><i class="fas fa-heartbeat"></i><b>BookMyTicket</b></a>
        </div>
        <nav class="nav">
            <a href="OrganizerDashboard.php" class="nav-link">Home</a>
            <a href="OrganizerAddEvent.php" class="nav-link">Create Event</a>
            <a href="OrganizerSelectEvent.php" class="nav-link">Edit Event</a>
            <a href="OrganizerPublicity.php" class="nav-link">Publicity</a>
            <a href="OrganizerProfile.php" class="nav-link">Profile</a>
        </nav>
        <div class="button-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="ProfileContainer">
                <a href="OrganiserProfile.php" id="userProfileLink">
                    <?php echo $organizer_name; ?>
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

                <label for="contactPerson">Contact Person:</label>
                <input type="text" id="contactPerson" name="contact_person" required>

                <label for="contactNumber">Contact Number:</label>
                <input type="text" id="contactNumber" name="contact_number" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <h3>Event Terms and Conditions</h3>
                <textarea name="event_terms" rows="5" required></textarea>

                <input type="submit" value="Submit Event" class="submit">
            </form>
        </div>
    </div>

    <script>
        document.getElementById('addDateButton').addEventListener('click', function() {
            var container = document.getElementById('eventDatesContainer');
            var newDateInput = document.createElement('input');
            newDateInput.type = 'date';
            newDateInput.name = 'event_dates[]';
            newDateInput.required = true;
            container.appendChild(newDateInput);
        });
    </script>

    <div class="footerbox">
        <p>Malaysia . Penang</p>
    </div>
</body>
</html>