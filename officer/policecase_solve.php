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

// Fetch report_id either from POST or GET
$report_id = $_POST['report_id'] ?? $_GET['report_id'] ?? null;

if (!$report_id) {
    die("Report ID not provided.");
}

// Fetch report details and reporter information
$selectedReport = [];
$reporterDetails = [];
$stmt = $conn->prepare("SELECT u.name, u.contact_number, c.anonymous, c.created_at, c.verification_status, c.latitude, c.longitude, c.user_id, C.location, c.description FROM crime_reports c JOIN user_profiles u ON c.user_id = u.id WHERE c.id = ?");
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();

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
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();

    if ($result2->num_rows > 0) {
        $reporterDetails = $result2->fetch_assoc();
    } else {
        die("No reporter found for user_id: " . $user_id);
    }
}

// Fetch the media (if available) associated with the report
$sql = "SELECT media_path FROM crime_reports WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$result = $stmt->get_result();
$image_path = "uploads/default.png"; // Default image if none found

if ($result->num_rows > 0) {
    $report = $result->fetch_assoc();
    $image_path = !empty($report['media_path']) ? $report['media_path'] : "uploads/default.png";
}

// Ensure file exists, otherwise use default image
$absolute_path = "../" . $image_path;  // Adjust relative path as needed
if (!file_exists($absolute_path)) {
    $image_path = "uploads/default.png";
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resolution_notes = $_POST['resolution_notes'];
    $resolution_date = $_POST['resolution_date'];
    $resolution_time = $_POST['resolution_time'];

    // Handle file upload
    $filePath = null;
    if (isset($_FILES['resolution_file']) && $_FILES['resolution_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['resolution_file']['tmp_name'];
        $fileName = $_FILES['resolution_file']['name'];
        $fileDest = '../uploads/' . $fileName;

        if (move_uploaded_file($fileTmpPath, $fileDest)) {
            $filePath = $fileDest;
        } else {
            echo "Error moving the uploaded file!";
            $filePath = null;
        }
    } else {
        $filePath = null;
    }

    // Combine date and time for resolution_datetime
    $resolution_datetime = $resolution_date . ' ' . $resolution_time;

    // Prepare the SQL query to update the crime report with resolution data
    $query = "UPDATE crime_reports 
              SET resolution_notes = ?, resolution_file = ?, resolution_datetime = ?, status = 'resolved'
              WHERE id = ?";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('sssi', $resolution_notes, $filePath, $resolution_datetime, $report_id);

        if ($stmt->execute()) {
            // Success: Set session variable for success message
            $_SESSION['success'] = "Great job! The case has been marked as resolved. The report has been archived and can be reviewed by an admin.";
            header("Location: " . $_SERVER['PHP_SELF'] . "?report_id=" . $report_id);
            exit();
        } else {
            echo "Error inserting data: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
}

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
            <!-- case solved button (passes report_id) -->
            <button id="caseSolvedBtn" class="btn btn-success mt-2" data-bs-toggle="modal"
                data-bs-target="#caseSolvedModal" data-report-id="<?= $report_id ?>">
                Case Solved
            </button>


            <!-- Confirmation Modal -->
            <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="caseSolvedModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content text-center p-3">
                        <div class="modal-body">
                            <i class="fa-solid fa-triangle-exclamation fa-3x text-danger"></i>
                            <h5 class="fw-bold mt-3">Are you sure the case is solved?</h5>
                            <div class="mt-3">
                                <button id="confirmResolution" class="btn btn-success">Yes, Confirm</button>
                                <button class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- modal for resolving case -->
        <div class="modal fade" id="resolutionModal" tabindex="-1" aria-labelledby="resolutionModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-3">
                    <div class="modal-header">
                        <h5 class="fw-bold">Resolution Summary (Required)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- This form submits to the same file -->
                        <form action="" method="POST" enctype="multipart/form-data">
                            <!-- hidden input for report_id -->
                            <input type="hidden" id="modal_report_id" name="report_id"
                                value="<?= htmlspecialchars($_GET['report_id'] ?? '') ?>">

                            <label class="fw-bold">What was the issue? What actions were taken? What was the
                                outcome?</label>
                            <textarea class="form-control mt-2" rows="3" id="resolution_notes" name="resolution_notes"
                                placeholder="Additional Notes (if any)" required></textarea>

                            <label class="fw-bold mt-3">Attach Files here.</label>
                            <input type="file" class="form-control" id="fileInput" name="resolution_file" required>

                            <label class="fw-bold mt-3">Date & Time of Resolution</label>
                            <div class="d-flex">
                                <input type="date" class="form-control me-2" id="resolution_date" name="resolution_date"
                                    required>
                                <input type="time" class="form-control" id="resolution_time" name="resolution_time"
                                    required>
                            </div>

                            <div class="form-check mt-3">
                                <input type="checkbox" class="form-check-input" id="confirmCheck" name="confirmCheck"
                                    required>
                                <label for="confirmCheck" class="form-check-label">
                                    I confirm that all the provided information is true and accurate to the best of my
                                    knowledge.
                                </label>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success w-100">SUBMIT</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- Success Modal -->
        <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content p-3">
                    <div class="modal-header">
                        <h5 class="fw-bold">Great Job!</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>The case has been marked as resolved.</p>
                        <p>The report has been archived and can be reviewed by an admin.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success w-100"
                            onclick="window.location.href='policereport.php'">Okay</button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
        <script>
        // JavaScript to trigger the modal when the form is successfully submitted
        window.onload = function() {
            var successModal = new bootstrap.Modal(document.getElementById('successModal'));
            successModal.show();
        };
        </script>
        <?php unset($_SESSION['success']); // Unset the success message after showing the modal 
            ?>
        <?php endif; ?>



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
        </script>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            var confirmModal = new bootstrap.Modal(document.getElementById("confirmModal"));
            var resolutionModal = new bootstrap.Modal(document.getElementById("resolutionModal"));

            document.getElementById("caseSolvedBtn").addEventListener("click", function() {
                confirmModal.show(); // show confirmation modal
            });

            document.getElementById("confirmResolution").addEventListener("click", function() {
                confirmModal.hide(); // close confirmation modal
                resolutionModal.show(); // open resolution modal
            });
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