<?php
// Database connection
$servername = "localhost"; // Adjust this as per your server configuration
$username = "root"; // Your database username
$password = "root"; // Your database password
$dbname = "FYP_BookMyTicket"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect form data
    $full_name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $message = $conn->real_escape_string($_POST['message']);

    // SQL query to insert the data into the contacts table
    $sql = "INSERT INTO contacts (full_name, email, message) VALUES ('$full_name', '$email', '$message')";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Thank you for your message! We will get back to you shortly.</p>";
    } else {
        echo "<p>Error: " . $sql . "<br>" . $conn->error . "</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="ContactUS.css"> 
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
            <a href="UserEvent.html" class="nav-link">Events</a>
            <a href="Message.html" class="nav-link">Learn More</a>
            <a href="output_message.php" class="nav-link">About Us</a>
        </nav>
        <div class="button-container">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- If the user is logged in, display the username as a link to the Profile page -->
                <div class="ProfileContainer">
                    <a href="UserProfile.php" id="userProfileLink">
                        <?php echo htmlspecialchars($user_name); ?>
                    </a>
                </div>
            <?php else: ?>
                <!-- If the user is not logged in, show the login and sign-up buttons -->
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
        <section class="contact-container">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>If you have any questions or feedback, feel free to reach out to us.</p>
                <ul>
                    <li><strong>Email:</strong> BookMyTicket@gmail.com</li>
                    <li><strong>Phone:</strong> +0123456789</li>
                    <li><strong>Address:</strong> 11 George Town, Penang, Malaysia</li>
                </ul>
            </div>

            <div class="contact-form-container">
                <h2>Send Us a Message</h2>
                <form action="ContactUs.php" method="POST">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" required placeholder="Your Name">

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required placeholder="Your Email">

                    <label for="message">Message:</label>
                    <textarea id="message" name="message" rows="5" required placeholder="Your Message"></textarea>

                    <button type="submit">Submit</button>
                </form>
            </div>
        </section>
    </main>

    <footer class="footerbox">
        <p>&copy; 2024 Your Company. All rights reserved.</p>
    </footer>

</body>
</html>
