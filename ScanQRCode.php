<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Take Attendance</title>
    <!-- Add the QR scanner script here -->
    <script type="module">
        import QrScanner from './js/qr-scanner.min.js'; // Ensure the path is correct

        // Set the path for the worker file
        QrScanner.WORKER_PATH = './js/qr-scanner-worker.min.js';

        // Ensure the DOM is loaded before accessing elements
        document.addEventListener('DOMContentLoaded', () => {
            // Initialize video element
            const videoElem = document.getElementById('qr-video');
            
            // Initialize the QR scanner
            const scanner = new QrScanner(videoElem, result => {
                console.log('Decoded QR Code:', result);
                sendQRCode(result); // Send the QR code data to the server
            }, {
                highlightScanRegion: true, // Highlights the scan region for clarity
            });

            // Start scanning when the button is clicked
            document.getElementById('start-scan').addEventListener('click', () => {
                scanner.start();
            });

            // Stop scanning when the button is clicked
            document.getElementById('stop-scan').addEventListener('click', () => {
                scanner.stop();
            });

            // Function to send QR code data to the PHP backend
            function sendQRCode(qrCodeData) {
                fetch('UpdateAttendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ qr_code: qrCodeData }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Attendance marked successfully!');
                    } else {
                        alert('Error marking attendance.');
                    }
                })
                .catch(err => console.error('Error:', err));
            }
        });
    </script>
</head>
<body>
    <h1>Admin - Take Attendance</h1>
    <video id="qr-video" style="width: 100%; height: auto;"></video> <!-- Video element to show the camera -->
    <button id="start-scan">Start Scan</button>
    <button id="stop-scan">Stop Scan</button>
</body>
</html>
