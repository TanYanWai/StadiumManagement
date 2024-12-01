<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.html");
    exit();
}

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

// Handle marking a message as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $message_id = intval($_POST['message_id']);
    $updateSql = "UPDATE contacts SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch messages from the contacts table
$sql = "SELECT id, full_name, email, message, created_at, is_read FROM contacts ORDER BY created_at DESC";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Messages</title>
    <link rel="stylesheet" href="AdminDisplayMessage.css">
</head>
<body>
<header class="header">
    <div class="navigation">
        <div class="brand">
            <a href="#" class="logo"><i class="fas fa-envelope"></i><b>BookMyTicket</b></a>
        </div>
        <nav class="nav">
            <a href="AdminDashboard.php" class="nav-link">Dashboard</a>
            <a href="AdminAddEvent.php" class="nav-link">Create Event</a>
            <a href="AdminSelectEvent.php" class="nav-link">Edit Event</a>
            <a href="OrganizerRegistration.php" class="nav-link">Create Account</a>
            <a href="AdminScanQRCode.php" class="nav-link">Scan QR</a>
            <a href="AdminProfile.php" class="nav-link">Profile</a>
        </nav>
    </div>
</header>

<div class="container">
    <h1>Manage Messages</h1>
    <table class="message-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['message']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <?php echo $row['is_read'] ? '<span class="tag read">Read</span>' : '<span class="tag unread">Unread</span>'; ?>
                        </td>
                        <td>
                            <?php if (!$row['is_read']): ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="message_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="mark_read" class="mark-read-btn">Mark as Read</button>
                                </form>
                            <?php else: ?>
                                <span class="no-action">Already Read</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No messages found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
