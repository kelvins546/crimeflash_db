<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crimeflash_db";

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];  // Fetch the report ID from the form submission
} else {
    die("Report ID not provided.");
}

$selectedReport = [];
$reporterDetails = [];

// SQL Query to fetch report details and reporter information (joined)
$stmt = $conn->prepare("
    SELECT u.name, u.contact_number, c.anonymous, c.created_at, c.status, c.latitude, c.longitude, c.user_id 
    FROM crime_reports c 
    JOIN user_profiles u ON c.user_id = u.id 
    WHERE c.id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $report_id);
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}
$result = $stmt->get_result();

if ($result === false) {
    die("Query failed: " . $conn->error);
}

if ($result->num_rows > 0) {
    $selectedReport = $result->fetch_assoc();
    $user_id = $selectedReport['user_id'];
    $anonymous = $selectedReport['anonymous'];
} else {
    die("No report found for ID: " . $report_id);
}

// Fetch reporter details if not anonymous
if ($anonymous == 0) {
    $stmt2 = $conn->prepare("SELECT name, contact_number FROM user_profiles WHERE id = ?");
    if (!$stmt2) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt2->bind_param("i", $user_id);
    if (!$stmt2->execute()) {
        die("Execute failed: " . $stmt2->error);
    }
    $result2 = $stmt2->get_result();

    if ($result2 === false) {
        die("Query failed: " . $conn->error);
    }

    if ($result2->num_rows > 0) {
        $reporterDetails = $result2->fetch_assoc();
    } else {
        die("No reporter found for user_id: " . $user_id);
    }
}

$stmt->close();
$stmt2->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Navigation</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>
</head>

<body>
    <h3>Real-Time Navigation to Crime Scene</h3>
    <div id="map" style="height: 500px; width: 500px;"></div>

    <h4>Reporter Details:</h4>
    <?php if ($anonymous == 0): ?>
        <p> <strong>Name:</strong><?php echo htmlspecialchars($reporterDetails['name']); ?></p>
        <p><strong>Contact Number:</strong><?php echo htmlspecialchars($reporterDetails['contact_number']); ?></p>
    <?php else: ?>
        <p><strong>Name:</strong> Anonymous</p>
        <p><strong>Contact:</strong> N/A</p>
    <?php endif; ?>


    <p>Time Reported: <em> </em><?php echo htmlspecialchars($selectedReport['created_at']); ?></p>
    <p>Status: <?php echo htmlspecialchars($selectedReport['status']); ?></p>

    <!-- "Arrived" Button -->
    <form action="path/to/arrived_script.php" method="post">
        <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report_id); ?>">
        <button type="submit" class="btn btn-success">Arrived</button>
    </form>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var map = L.map('map').setView([14.5995, 120.9842], 13); // Default Manila location

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var reportLat = <?php echo json_encode($selectedReport['latitude'] ?? null); ?>;
            var reportLng = <?php echo json_encode($selectedReport['longitude'] ?? null); ?>;

            if (!reportLat || !reportLng) {
                console.error("Report location not found.");
                alert("Report location not found.");
                return;
            }

            // var officerLat = 14.75489560; // UCC
            //   var officerLng = 121.02991520; // UCC
            var officerLat = 14.55226880; // Static user latitude
            var officerLng = 121.06137600; // Static user longitude


            var officerMarker = L.marker([officerLat, officerLng], {
                icon: L.icon({
                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
                    iconSize: [40, 40]
                })
            }).addTo(map).bindPopup("Your Location").openPopup();

            var reportMarker = L.marker([reportLat, reportLng], {
                icon: L.icon({
                    iconUrl: 'https://cdn-icons-png.flaticon.com/512/684/684908.png',
                    iconSize: [40, 40]
                })
            }).addTo(map).bindPopup("Crime Report Location").openPopup();

            var route = L.Routing.control({
                waypoints: [
                    L.latLng(officerLat, officerLng),
                    L.latLng(reportLat, reportLng)
                ],
                routeWhileDragging: true
            }).addTo(map);
        });
    </script>
</body>

</html>