<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$servername = "localhost"; 
$username = "root"; 
$password = "root"; 
$dbname = "FYP_BookMyTicket"; 

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "POST request received"; // Debugging line
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $input_username_or_email = $conn->real_escape_string($_POST['username']); // Can be either username or email
        $input_password = $_POST['password'];

        // SQL query to fetch user data by username or email
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $input_username_or_email, $input_username_or_email);

        // Execute the statement
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch user data
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($input_password, $user['password'])) {
                // Password is correct, start session and redirect based on user role
                session_start();
                $_SESSION['user_id'] = $user['id']; // Store user ID in session

                // Redirect based on user role
                switch ($user['user_role']) {
                    case 'admin':
                        header("Location: AdminDashboard.php");
                        break;
                    case 'organiser':
                        header("Location: OrganizerDashboard.php");
                        break;
                    case 'user':
                    default:
                        header("Location: UserHome.php");
                        break;
                }
                exit(); // Stop further script execution after redirect
            } else {
                // Password is incorrect
                echo "<script>alert('Incorrect password. Please try again.'); window.location.href='Login.html';</script>";
            }
        } else {
            // Username or email not found
            echo "<script>alert('Username or email not found. Please try again.'); window.location.href='Login.html';</script>";
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "<script>alert('Please enter both username/email and password.'); window.location.href='Login.html';</script>";
    }
} else {
    echo "Invalid request method: " . $_SERVER['REQUEST_METHOD']; // Debugging line
}

// Close the connection
$conn->close();
?>
