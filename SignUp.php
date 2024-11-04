<?php
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
$username = $_POST['username'];
$email = $_POST['email'];
$phonenumber = $_POST['phonenumber'];
$raw_password = $_POST['password']; // Raw password from form (will be hashed)

// Password validation
if (!preg_match('/[A-Za-z]/', $raw_password) || !preg_match('/\d/', $raw_password) || strlen($raw_password) < 6) {
    echo "<script>alert('Password must be at least 6 characters long, contain at least one letter and one number.'); window.location.href='SignUp.html';</script>";
    exit();
}

// Check if the email already exists
$emailCheckSql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($emailCheckSql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Email already exists
    echo "<script>alert('Email is already registered. Please use a different email.'); window.location.href='SignUp.html';</script>";
    exit();
}

// Hash the password for security
$hashed_password = password_hash($raw_password, PASSWORD_BCRYPT);

// SQL query to insert the data into the table
$sql = "INSERT INTO users (username, email, phonenumber, password) VALUES (?, ?, ?, ?)";

// Prepare and bind
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $username, $email, $phonenumber, $hashed_password);

// Execute the statement
if ($stmt->execute()) {
    header("Location: Login.html");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

// Close the connection
$stmt->close();
$conn->close();
?>
