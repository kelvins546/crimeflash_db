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


$report_id = 9;
$selectedReport = [];

$stmt = $conn->prepare("SELECT latitude, longitude FROM crime_reports WHERE id = ?");
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
} else {
    die("No report found for ID: " . $report_id);
}
$stmt->close();
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var map = L.map('map').setView([14.5995, 120.9842], 13); // Default Manila location

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            function success(position) {
                var userLat = position.coords.latitude;
                var userLng = position.coords.longitude;

                var reportLat = <?php echo json_encode($selectedReport['latitude'] ?? null); ?>;
                var reportLng = <?php echo json_encode($selectedReport['longitude'] ?? null); ?>;

                if (!reportLat || !reportLng) {
                    console.error("Report location not found.");
                    alert("Report location not found.");
                    return;
                }

                var officerMarker = L.marker([userLat, userLng], {
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
                        L.latLng(userLat, userLng),
                        L.latLng(reportLat, reportLng)
                    ],
                    routeWhileDragging: true
                }).addTo(map);

                setInterval(() => {
                    navigator.geolocation.getCurrentPosition(function(updatedPosition) {
                        userLat = updatedPosition.coords.latitude;
                        userLng = updatedPosition.coords.longitude;
                        officerMarker.setLatLng([userLat, userLng]);
                        route.setWaypoints([
                            L.latLng(userLat, userLng),
                            L.latLng(reportLat, reportLng)
                        ]);
                    });
                }, 5000); // Update every 5 seconds
            }

            function error(err) {
                console.error("Geolocation error:", err);
                alert("Unable to retrieve your location.");
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(success, error);
            } else {
                console.error("Geolocation not supported");
                alert("Geolocation is not supported by your browser.");
            }
        });
    </script>
</body>

</html>