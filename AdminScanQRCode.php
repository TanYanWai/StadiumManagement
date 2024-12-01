<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizer - Take Attendance</title>

    <!-- Add the QR scanner script here -->
    <script type="module">
        import QrScanner from './node_modules/qr-scanner/qr-scanner.min.js'; // Ensure the path is correct

        // Set the path for the worker file
        QrScanner.WORKER_PATH = './node_modules/qr-scanner/qr-scanner-worker.min.js'; // Adjust the path as necessary

        document.addEventListener('DOMContentLoaded', () => {
            const videoElem = document.getElementById('qr-video');
            const scanner = new QrScanner(videoElem, result => {
                console.log('Decoded QR Code:', result.data); // Log the raw data
                sendQRCode(result.data); // Send the QR code data to the server

                // Stop the scanner immediately after a QR code is scanned
                scanner.stop();

                // Automatically restart the scanner after a 3-second delay
                setTimeout(() => {
                    scanner.start(); // Restart the scanner to scan the next QR code
                }, 3000); // 3 seconds delay
            }, {
                highlightScanRegion: true,
            });

            document.getElementById('start-scan').addEventListener('click', () => {
                scanner.start();
            });

            document.getElementById('stop-scan').addEventListener('click', () => {
                scanner.stop();
            });

            // Function to send QR code data to PHP for updating attendance
            function sendQRCode(qrCodeData) {
                fetch('UpdateAttendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ qr_code: qrCodeData }),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response from server:', data); // Log the server response
                    if (data.success) {
                        alert('Attendance marked successfully!');
                    } else {
                        alert('Error marking attendance: ' + data.message);
                    }
                })
                .catch(err => console.error('Error:', err));
            }
        });
    </script>

    <link rel="stylesheet" href="OrganizerScanQRCode.css">
</head>

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
                <!-- If the organizer is logged in, display the username as a link to the Profile page -->
                <div class="ProfileContainer">
                <a href="OrganiserProfile.php" id="userProfileLink">
                        <?php echo $user_name; ?>
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
<body>
    <h1>Admin - Take Attendance</h1>
    <video id="qr-video" style="width: 100%; height: auto;"></video> <!-- Video element to show the camera -->
    <button id="start-scan">Start Scan</button>
    <button id="stop-scan">Stop Scan</button>
</body>
</html>
</html>
