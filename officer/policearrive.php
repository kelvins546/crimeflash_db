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

// If coming from form submission (POST)
if (isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    $new_status = 'on the way'; // Make sure ENUM in database supports this

    $update_stmt = $conn->prepare("UPDATE crime_reports SET status = ? WHERE id = ?");
    if (!$update_stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $update_stmt->bind_param("si", $new_status, $report_id);
    if (!$update_stmt->execute()) {
        die("Execute failed: " . $update_stmt->error);
    }

    $update_stmt->close();

    // Redirect to GET method to prevent form resubmission
    header("Location: policearrive.php?report_id=" . urlencode($report_id));
    exit();
}

// ✅ Handle redirected GET access
if (!isset($report_id) && isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];
}

// Validate report_id before proceeding
if (!isset($report_id)) {
    die("No report ID provided.");
}

$selectedReport = [];
$reporterDetails = [];

// Fetch report details and user info
$stmt = $conn->prepare("
    SELECT u.name, u.contact_number, c.anonymous, c.created_at, c.verification_status, c.latitude, c.longitude, c.user_id, c.location, c.description 
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

if ($result === false || $result->num_rows === 0) {
    die("No report found for ID: " . htmlspecialchars($report_id));
}

$selectedReport = $result->fetch_assoc();
$user_id = $selectedReport['user_id'];
$anonymous = $selectedReport['anonymous'];

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

    if ($result2 === false || $result2->num_rows === 0) {
        die("No reporter found for user_id: " . $user_id);
    }

    $reporterDetails = $result2->fetch_assoc();
    $stmt2->close();
}

// Fetch media path
$sql = "SELECT media_path FROM crime_reports WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $report = $result->fetch_assoc();
    $image_path = !empty($report['media_path']) ? $report['media_path'] : "uploads/default.png";
} else {
    $image_path = "uploads/default.png";
}

// Check file existence
$absolute_path = "../" . $image_path;
if (!file_exists($absolute_path)) {
    $image_path = "uploads/default.png";
}
$image_url = "/" . $image_path;

$stmt->close();
$conn->close();
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/bac1e4ca00.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

    <style>
    body {
        background-color: #f8f9fa;
        font-family: "Poppins", sans-serif;
    }

    .navbar {
        background-color: #f4a623;
    }

    .icon-container {
        display: flex;
        color: black;
        align-items: center;
        padding: 15px;
    }

    .icon-container i {
        color: black;
    }

    .sidebar {
        width: 250px;
        height: 100vh;
        background-color: #605c5c;
        position: fixed;
        left: -250px;
        top: 0;
        transition: 0.3s ease-in-out;
        padding-top: 20px;
        z-index: 1000;
    }

    .sidebar.active {
        left: 0;
    }

    .sidebar .profile {
        text-align: center;
        padding: 20px;
        color: white;
    }

    .sidebar .profile img {
        width: 80px;
        border-radius: 50%;
        margin-bottom: 20px;
    }

    .sidebar .menu-items {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar .menu-items li {
        padding: 10px 20px;
        text-decoration: none;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .sidebar .menu-items li a {
        padding: 10px 20px;
        color: white;
        text-decoration: none;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .sidebar .menu-items li i {
        margin-right: 10px;
        color: #f4a623;
    }

    .sidebar .menu-items li:hover {
        background-color: #444;
    }

    .logout {
        position: absolute;
        bottom: 20px;
        left: 20px;
        color: white;
        cursor: pointer;
    }

    .logout i {
        margin-right: 5px;
    }

    .report-container {
        background: white;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-top: 10px;
    }

    .report-container p {
        margin-bottom: 5px;
    }

    .status {
        font-style: italic;
    }

    .btn-custom {
        background-color: #f4a623;
        color: white;
        border: none;
        padding: 5px 15px;
        font-weight: bold;
    }

    .btn-custom:hover {
        background-color: #d4881e;
        color: white;
    }

    .status-legitimate {
        color: green;
        font-weight: bold;
    }

    .close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 20px;
        color: white;
        cursor: pointer;
    }

    .overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        z-index: 999;
    }

    .overlay.active {
        display: block;
    }

    /*  #map {
        height: 220px;
        width: 100%;
    } */

    .crime-report-img {
        height: 250px;
        width: 50%;
        object-fit: cover;
        /* Ensures the image fills the area without distortion */
        border-radius: 5px;
        /* Optional: adds rounded corners */
    }
    </style>

</head>


<body>
    <!-- navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid icon-container">
            <button id="menu-toggle" class="btn text-white  "><i class="fa-solid fa-bars"></i></button>
            <span class="navbar-brand mx-auto fw-bold">Reports</span>
            <a href="../police/policereport.html    ">
                <span class="navbar-brand text-white">
                    <i class="fa-solid fa-bell"></i>
                </span>
            </a>
        </div>
    </nav>
    <!-- sidebar -->
    <div id="sidebar" class="sidebar">
        <span class="close-btn" id="close-sidebar">&times;</span>
        <div class="profile">
            <img src="../images/policeprofile.png" alt="User Profile">
            <h5>SPO3 Fred Samson</h5>

            <p>Northern Police District LNPD<br>Senior Police Officer</p>
            <span class="badge bg-success">Active</span>
        </div>
        <ul class="menu-items">
            <li><a href=""><i class="fa-solid fa-folder"></i> ASSIGNED CASES</a></li>
            <li><a href="../police/policeprofile.html"><i class="fa-solid fa-user"></i> PROFILE</a></li>
        </ul>
        <div class="logout">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </div>
    </div>


    <!-- content -->
    <div class="container mt-2">



        <div class="report-container">
            <h5 class="fw-bold text-center"><?= htmlspecialchars($selectedReport["location"] ?? 'Unknown Location') ?>
            </h5>
            <hr>
            <div id="map" style="height: 500px; width: 500px;"></div>
            <p><strong>Description:</strong>
                <?= htmlspecialchars($selectedReport['description'] ?? 'No description available.') ?></p>
            <?php if ($anonymous == 0): ?>
            <p><strong>Name:</strong> <?= htmlspecialchars($reporterDetails['name']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($reporterDetails['contact_number']) ?></p>
            <?php else: ?>
            <p><strong>Name:</strong> Anonymous</p>
            <p><strong>Contact:</strong> N/A</p>
            <?php endif; ?>
            <p><em>Time Reported:</em> <?= htmlspecialchars($selectedReport['created_at'] ?? 'N/A') ?></p>

            <p><i>Verification Status:
                    <span style="color: 
                      <?php


                        if ($selectedReport['verification_status'] == 'suspicious') echo 'orange';
                        elseif ($selectedReport['verification_status'] == 'legitimate') echo 'blue';
                        else echo 'gray'; // awaiting confirmation
                        ?>">
                        <?= htmlspecialchars($selectedReport['verification_status'] ?? 'Awaiting Confirmation') ?>
                    </span>
                </i></p>

            </a>

            <img src="../<?php echo htmlspecialchars($image_path); ?>" alt="Crime Report Image"
                class="crime-report-img">
            <!-- Arrived Button -->
            <button id="arrivedBtn" class="btn btn-primary mt-3">Arrived</button>

            <!-- Modal -->
            <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="locationModalLabel">Location Alert</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="modalMessage">
                            <!-- Modal message will be inserted here -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="arrivedConfirmBtn">Arrived
                                Already</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var map = L.map('map').setView([14.5995, 120.9842], 13); // Default Manila location

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            var reportLat = <?php echo json_encode($selectedReport['latitude'] ?? null); ?>;
            var reportLng = <?php echo json_encode($selectedReport['longitude'] ?? null); ?>;

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

            // Function to calculate distance between two coordinates (Haversine formula)
            function getDistance(lat1, lon1, lat2, lon2) {
                var R = 6371; // Radius of the Earth in km
                var dLat = (lat2 - lat1) * Math.PI / 180;
                var dLon = (lon2 - lon1) * Math.PI / 180;
                var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                    Math.sin(dLon / 2) * Math.sin(dLon / 2);
                var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                var distance = R * c; // Distance in km
                return distance * 1000; // Convert to meters
            }

            // Initialize modal as hidden by default
            var modal = new bootstrap.Modal(document.getElementById('locationModal'));

            // Add event listener for the "Arrived" button
            document.getElementById('arrivedBtn').addEventListener('click', function() {
                var distance = getDistance(officerLat, officerLng, reportLat, reportLng);
                var modalMessage = document.getElementById('modalMessage');

                if (distance > 500) { // 500 meters threshold
                    modalMessage.textContent =
                        'You need to be closer to the report location to mark it as "Arrived."';
                } else {
                    modalMessage.textContent =
                        'You are already close to the report location. Proceed to mark as "Arrived."';
                }

                // Show modal only when button is clicked
                modal.show();
            });

            // Handle the "Arrived Already" button
            document.getElementById('arrivedConfirmBtn').addEventListener('click', function() {
                var reportId = <?php echo $report_id; ?>;
                if (!reportId) {
                    alert("Invalid report ID.");
                    return;
                }
                // Redirect to policecase_solve.php and pass the report_id
                window.location.href = 'policecase_solve.php?report_id=' + reportId;
            });
        });
        </script>


    </div>

</body>

</html>