<?php
session_start(); // Start the session

// Database connection
$host = "localhost"; // Change this to "127.0.0.1" if "localhost" doesn't work
$dbname = "FYP_BookMyTicket"; // Replace with your actual database name
$username = "root"; // MAMP default username is usually root
$password = "root"; // MAMP default password is usually root

// Create a new PDO connection
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch the user ID from the session
$user_id = $_SESSION['user_id'];

// Query to get the username from the users table
$query = "SELECT username FROM users WHERE id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();

// Fetch the user's data
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    $user_name = $user['username'];
} else {
    $user_name = 'User';  // Default value if not found
}

// Fetch the event ID and selected date from the URL
if (isset($_GET['id']) && isset($_GET['date'])) {
    $event_id = intval($_GET['id']);
    $event_date = htmlspecialchars($_GET['date']);
} else {
    die("No event ID or date specified.");
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="UserHome.css"> <!-- Link to CSS file -->
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
                    <a href="UserProfile.php" id="userProfileLink"><?php echo htmlspecialchars($user_name); ?></a>
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
    <div class="stadium-container">
        <h1>Select a Section for <?php echo htmlspecialchars($event_date); ?></h1>

        <div class="stadium-layout">
            <div class="stage">Stage</div>
            <?php
            $sections = [];
            for ($i = 1; $i <= 20; $i++) {
                $sections[] = "A$i";
                $sections[] = "B$i";
            }
            foreach ($sections as $key => $section) {
                $angle = ($key / count($sections)) * 2 * M_PI; // Calculate angle for circular placement
                $x = 200 * cos($angle); // x coordinate
                $y = 200 * sin($angle); // y coordinate
                echo "<div class='section' style='position: absolute; left: calc(50% + {$x}px); top: calc(50% + {$y}px);' onclick=\"navigateToBookSeat('$section')\">
                        <p>$section</p>
                      </div>";
            }
            ?>
        </div>
    </div>
</main>

<div class="footerbox">
    <p>Malaysia . Penang</p>
</div>

<script>
    function navigateToBookSeat(section) {
        const eventID = <?php echo $event_id; ?>;
        const eventDate = "<?php echo $event_date; ?>";
        window.location.href = `BookSeat.php?section=${section}&id=${eventID}&date=${eventDate}`;
    }
</script>
</body>
</html>
