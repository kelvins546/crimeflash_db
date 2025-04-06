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

    // Process the report (e.g., update status, etc.)
    // Your SQL query here (e.g., update crime report status)

    // After processing, redirect to avoid resubmitting the form on refresh
    header("Location: policearrive.php?report_id=" . urlencode($report_id));
    exit();
} else {
    // Handle if report_id is not provided in the URL (GET request)
    if (isset($_GET['report_id'])) {
        $report_id = $_GET['report_id'];  // Fetch the report ID from the URL
    } else {
        die("Report ID not provided.");
    }
}

$selectedReport = [];
$reporterDetails = [];

// SQL Query to fetch report details and reporter information (joined)
$stmt = $conn->prepare("
    SELECT u.name, u.contact_number, c.anonymous, c.created_at, c.verification_status, c.latitude, c.longitude, c.user_id, C.location, c.description 
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



$sql = "SELECT media_path FROM crime_reports WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $report = $result->fetch_assoc();
    $image_path = !empty($report['media_path']) ? $report['media_path'] : "uploads/default.png"; // Default image if none found
} else {
    $image_path = "uploads/default.png";
}

// Ensure file exists, otherwise use default image
$absolute_path = "../" . $image_path;  // Adjust relative path as needed
if (!file_exists($absolute_path)) {
    $image_path = "uploads/default.png";
}

$image_url = "/" . $image_path; // Path for the front-end





$stmt->close();
$stmt2->close();
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
            <button id="caseSolvedBtn" class="btn btn-success mt-2" data-bs-toggle="modal"
                data-bs-target="#caseSolvedModal">
                Case Solved
            </button>

            <!-- Confirmation Modal -->
            <div class="modal fade" id="caseSolvedModal" tabindex="-1" aria-labelledby="caseSolvedModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center p-3">
                        <div class="modal-body">
                            <i class="fa-solid fa-triangle-exclamation fa-3x text-danger"></i>
                            <h5 class="fw-bold mt-3">Are you sure the case is solved?</h5>
                            <div class="mt-3">
                                <button id="confirmCaseSolved" class="btn btn-success">Yes, Confirm</button>
                                <button class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="resolutionModal" tabindex="-1" aria-labelledby="resolutionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-3">
                    <div class="modal-header">
                        <h5 class="fw-bold">Resolution Summary (Required)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="fw-bold">What was the issue? What actions were taken? What was the
                            outcome?</label>
                        <textarea class="form-control mt-2" rows="3" placeholder="Additional Notes (if any)"></textarea>

                        <label class="fw-bold mt-3">Attach Files here.</label>
                        <input type="file" class="form-control">

                        <label class="fw-bold mt-3">Date & Time of Resolution</label>
                        <div class="d-flex">
                            <input type="date" class="form-control me-2">
                            <input type="time" class="form-control">
                        </div>

                        <div class="form-check mt-3">
                            <input type="checkbox" class="form-check-input" id="confirmCheck">
                            <label for="confirmCheck" class="form-check-label">
                                I confirm that all the provided information is true and accurate to the best of my
                                knowledge.
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success w-100">SUBMIT</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
        // Sidenav fuNCTION//
        document.getElementById("menu-toggle").addEventListener("click", function() {
            document.getElementById("sidebar").classList.add("active");
            document.getElementById("overlay").classList.add("active");
        });

        document.getElementById("close-sidebar").addEventListener("click", function() {
            document.getElementById("sidebar").classList.remove("active");
            document.getElementById("overlay").classList.remove("active");
        });

        document.getElementById("confirmCaseSolved").addEventListener("click", function() {
            // Hide the first modal
            var caseSolvedModal = bootstrap.Modal.getInstance(document.getElementById('caseSolvedModal'));
            caseSolvedModal.hide();

            // Show the Resolution Summary modal
            var resolutionModal = new bootstrap.Modal(document.getElementById('resolutionModal'));
            resolutionModal.show();
        });
        </script>

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


        });
        </script>


    </div>

</body>

</html>