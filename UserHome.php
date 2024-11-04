<?php
session_start(); // Start the session

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If user is not logged in, redirect to login page
    header("Location: Login.html");
    exit(); // Stop further execution
}

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
$user_id = $_SESSION['user_id'];
$userSql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $user_name = htmlspecialchars($user['username']);
}

// Fetch event posters for the slideshow
$posterSql = "SELECT event_poster FROM event_images WHERE event_poster IS NOT NULL"; 
$posterResult = $conn->query($posterSql);

// Fetch events and associated images from events and event_images tables
$eventSql = "
    SELECT e.id, e.event_title, e.event_date, e.event_time_from, e.event_time_to, e.event_category, ei.image_path 
    FROM events e
    LEFT JOIN event_images ei ON e.id = ei.event_id"; 

$eventResult = $conn->query($eventSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="UserHome.css">
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
            <a href="ChooseEvent.php" class="nav-link">Events</a>
            <a href="ContactUs.php" class="nav-link">Contact Us</a>
            <a href="ChooseEvent.php" class="nav-link">Rate Event</a>
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

<main>
    <!-- Slideshow for Event Posters -->
    <div class="adsBox">
        <div class="slideshow-container">
            <?php
            $posterCount = $posterResult->num_rows; // Store the number of posters

            if ($posterCount > 0) {
                $slideIndex = 1;
                while ($poster = $posterResult->fetch_assoc()) {
                    $posterPath = $poster['event_poster'];
                    if ($posterPath) {
                        echo '
                        <div class="mySlides fade">
                            <div class="numbertext">' . $slideIndex . ' / ' . $posterCount . '</div>
                            <img src="uploads/' . htmlspecialchars($posterPath) . '" style="width:100%">
                            <div class="text">Caption ' . $slideIndex . '</div>
                        </div>';
                        $slideIndex++;
                    }
                }
            } else {
                echo "No posters found.";
            }
            ?>
          
            <!-- Next and previous buttons -->
            <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
            <a class="next" onclick="plusSlides(1)">&#10095;</a>
        </div>
        <br>
          
        <!-- The dots/circles -->
        <div style="text-align:center">
            <?php
            for ($i = 1; $i <= $posterCount; $i++) {
                echo '<span class="dot" onclick="currentSlide(' . $i . ')"></span> ';
            }
            ?>
        </div>
    </div>

    <div class="content-below-ads">
        <!-- Event Type Links -->
        <div class="eventTypeBox">
            <div class="eventTypeTitleBox">
                <div class="eventTypeTitle">
                    <p>Event Category</p>
                </div>
            </div>
            <div class="eventType">
                <a href="UserEvent.html">Entertainment</a>
                <a href="UserEvent.html">Sport</a>
                <a href="UserEvent.html">Concert</a>
            </div>
        </div>

        <!-- Popular Events --> 
        <div class="popular-events">
            <h2>Popular Events</h2>
            <div class="events-container">
                <?php
                if ($eventResult->num_rows > 0) {
                    while ($event = $eventResult->fetch_assoc()) {
                        $eventId = htmlspecialchars($event['id']); // Get the event ID
                        $eventTitle = htmlspecialchars($event['event_title']);
                        $eventTimeFrom = htmlspecialchars($event['event_time_from']);
                        $eventCategory = htmlspecialchars($event['event_category']);
                        $imageSrc = $event['image_path'];

                        // Check if the image path is stored as a JSON array
                        $decodedImages = json_decode($imageSrc, true);
                        if (is_array($decodedImages) && !empty($decodedImages)) {
                            $imageSrc = $decodedImages[0];
                        }

                        // Check if the image exists or fallback to a default image
                        if (empty($imageSrc) || !file_exists("uploads/" . $imageSrc)) {
                            $imageSrc = "uploads/default.jpg";
                        } else {
                            $imageSrc = "uploads/" . $imageSrc;
                        }

                        // Make the event box clickable
                        echo '
                        <div class="event-box">
                            <a href="ViewEvent.php?id=' . $eventId . '" class="event-link"> <!-- Link to ViewEvent.php with event ID -->
                                <img src="' . $imageSrc . '" alt="Event Image">
                                <div class="event-details">
                                    <div class="event-title">' . $eventTitle . '</div>
                                    <div class="event-time">Time: ' . $eventTimeFrom . '</div>
                                    <div class="event-category">Category: ' . $eventCategory . '</div>
                                </div>
                            </a>
                        </div>';
                    }
                } else {
                    echo "No events found.";
                }
                ?>
            </div>
        </div>
    </div>
</main>

<div class="footerbox">
    <p>Malaysia . Penang</p>
</div>

<!-- JavaScript for auto-logout on tab or browser close -->
<script>
    window.onbeforeunload = function() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "Logout.php", false); // Synchronous request to log out
        xhr.send();
    };

    // JavaScript for slideshow control
    var slideIndex = 1;
    showSlides(slideIndex);

    function plusSlides(n) {
        showSlides(slideIndex += n);
    }

    function currentSlide(n) {
        showSlides(slideIndex = n);
    }

    function showSlides(n) {
        var i;
        var slides = document.getElementsByClassName("mySlides");
        var dots = document.getElementsByClassName("dot");
        if (n > slides.length) {slideIndex = 1}
        if (n < 1) {slideIndex = slides.length}
        for (i = 0; i < slides.length; i++) {
            slides[i].style.display = "none";
        }
        for (i = 0; i < dots.length; i++) {
            dots[i].className = dots[i].className.replace(" active", "");
        }
        slides[slideIndex-1].style.display = "block";
        dots[slideIndex-1].className += " active";
    }
</script>

</body>
</html>
