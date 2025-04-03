<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crimeflash_db";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch report details if a report_id is provided
$selectedReport = [];
$user_details = null;
if (isset($_GET['id'])) {
    $report_id = $_GET['id'];

    $report_query = "SELECT * FROM crime_reports WHERE id = ?";
    $stmt = $conn->prepare($report_query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report_result = $stmt->get_result();

    if ($report_result->num_rows > 0) {
        $selectedReport = $report_result->fetch_assoc();
        if ($selectedReport['anonymous'] == 0) {
            $user_query = "SELECT * FROM user_profiles WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $selectedReport['user_id']);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_result->num_rows > 0) {
                $user_details = $user_result->fetch_assoc();
            }
            $user_stmt->close();
        }
    }
    $stmt->close();
}

// Ensure the correct image path
$image_path = !empty($selectedReport['media_path']) ? $selectedReport['media_path'] : "uploads/default.png";

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
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />


</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid icon-container">
            <button id="menu-toggle" class="btn text-white"><i class="fa-solid fa-bars"></i></button>
            <span class="navbar-brand mx-auto fw-bold">Reports</span>
            <a href="../police/policereport.html">
                <span class="navbar-brand text-white">
                    <i class="fa-solid fa-bell"></i>
                </span>
            </a>
        </div>
    </nav>

    <!-- Sidebar -->
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

    <!-- Content -->
    <div class="container mt-2">
        <?php
        // Ensure the correct image path
        $image_path = !empty($selectedReport['media_path']) ? $selectedReport['media_path'] : "uploads/default.png";
        ?>

        <?php if (!empty($selectedReport)): ?>
        <div class="report-container">
            <h5 class="fw-bold text-center"><?= htmlspecialchars($selectedReport["location"] ?? 'Unknown Location') ?>
            </h5>
            <hr>
            <div id="map"></div>

            <!-- Leaflet JS -->
            <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
            <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
            <script>
            // Function to initialize the map
            function initializeMap(destination) {
                // Create the map instance centered at the destination with a default zoom level
                var map = L.map('map').setView([destination.lat, destination.lng], 13);

                // Add OpenStreetMap tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Locate the user's position and add routing
                locateUser(map, destination);
            }

            // Function to locate the user and add routing
            function locateUser(map, destination) {
                map.locate({
                    setView: true,
                    maxZoom: 16
                });

                map.on('locationfound', function(e) {
                    var userLocation = e.latlng;

                    // Add routing control from user's location to destination
                    L.Routing.control({
                        waypoints: [
                            userLocation,
                            L.latLng(destination.lat, destination.lng)
                        ],
                        routeWhileDragging: true
                    }).addTo(map);
                });

                map.on('locationerror', function(e) {
                    alert('Error locating your position: ' + e.message);
                });
            }

            // Fetch destination coordinates from the server
            fetch('get_destination.php?id=<?php echo htmlspecialchars($_GET["id"]); ?>')
                .then(response => response.json())
                .then(destination => {
                    if (destination.lat && destination.lng) {
                        initializeMap(destination);
                    } else {
                        alert('Invalid destination coordinates.');
                    }
                })
                .catch(error => console.error('Error fetching destination coordinates:', error));
            </script>


            <p><strong>Description:</strong>
                <?= htmlspecialchars($selectedReport['description'] ?? 'No description available.') ?></p>

            <?php if ($selectedReport['anonymous'] == 0 && $user_details): ?>
            <p><strong>Name:</strong> <?= htmlspecialchars($user_details['name']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($user_details['contact_number']) ?></p>
            <?php else: ?>
            <p><strong>Name:</strong> Anonymous</p>
            <p><strong>Contact:</strong> N/A</p>
            <?php endif; ?>

            <p><em>Time Reported:</em> <?= htmlspecialchars($selectedReport['created_at'] ?? 'N/A') ?></p>
            <p class="status text-danger"><em>Station Status:
                    <?= htmlspecialchars($selectedReport['status'] ?? 'Pending') ?></em></p>

            <a href="../officer/policearrive.php">
                <button class="btn btn-success p-2">Arrived</button>
            </a>

            <img src="../<?= htmlspecialchars($image_path); ?>" alt="Crime Report Image" class="crime-report-img">
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Sidenav function
    document.getElementById("menu-toggle").addEventListener("click", function() {
        document.getElementById("sidebar").classList.add("active");
        document.getElementById("overlay").classList.add("active");
    });

    document.getElementById("close-sidebar").addEventListener("click", function() {
        document.getElementById("sidebar").classList.remove("active");
        document.getElementById("overlay").classList.remove("active");
    });
    </script>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>

</body>

</html>