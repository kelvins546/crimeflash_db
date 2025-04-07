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
        }
    } else {
        echo "No report found with ID: " . htmlspecialchars($report_id); // Debugging
    }
    $stmt->close();
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


// Sanitize and validate inputs
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Replacing deprecated FILTER_SANITIZE_STRING with htmlspecialchars
    $report_id = filter_input(INPUT_POST, 'report_id', FILTER_SANITIZE_NUMBER_INT);
    $reasons = filter_input(INPUT_POST, 'reasons', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    $additional_details = htmlspecialchars($_POST['additional_details'], ENT_QUOTES, 'UTF-8'); // Use htmlspecialchars for additional details

    if ($report_id && !empty($reasons)) {
        // Prepare the SQL statement
        $stmt = $conn->prepare("INSERT INTO report_response_reasons (report_id, reason, additional_details) VALUES (?, ?, ?)");

        // Bind parameters and execute the statement for each reason
        foreach ($reasons as $reason) {
            $stmt->bind_param("iss", $report_id, $reason, $additional_details);
            $stmt->execute();
        }

        // Close the statement
        $stmt->close();

        // Set a success flag for the modal
        $_SESSION['success_message'] = "Response submitted successfully!";
    } else {
        $error_message = "Please select at least one reason.";
    }
}
if (isset($_SESSION['success_message'])) {
    // Show the modal if the success message is set
    $success_message = $_SESSION['success_message'];

    // Unset the success message after showing the modal
    unset($_SESSION['success_message']);
}

// Query to fetch latitude and longitude
$query = "SELECT latitude, longitude FROM crime_reports WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$stmt->bind_result($latitude, $longitude);
$stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_on_the_way'])) {
    $on_the_way_id = filter_input(INPUT_POST, 'on_the_way_report_id', FILTER_SANITIZE_NUMBER_INT);

    // Optional: Check if ENUM supports 'on the way' or just use 'assigned'
    $new_status = 'on the way'; // Or 'assigned' if ENUM doesn't support 'on the way'

    // Reconnect to DB (if needed, otherwise reuse $conn)
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $update_stmt = $conn->prepare("UPDATE crime_reports SET status = ? WHERE id = ?");
    $update_stmt->bind_param("si", $new_status, $on_the_way_id);
    $update_stmt->execute();

    if ($update_stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Status updated to 'on the way'";
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $on_the_way_id);
        exit;
    } else {
        echo "Failed to update status.";
    }

    $update_stmt->close();
}


// Close connection after all operations are complete
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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery (required for your successModal trigger) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap 5 Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
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

    #map {
        height: 220px;
        width: 100%;
    }

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
    <?php if (isset($success_message)) : ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-body text-center">
                    <p><?= htmlspecialchars($success_message) ?></p>
                    <!-- You can add a custom message here if needed -->
                    <p class="mt-2">Your response has been successfully sent to the Clerk and Admin.
                        Please avoid submitting multiple rejections unless absolutely necessary.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-success" id="successOkBtn">OK</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Show modal when page loads (if $success_message is set)
    const successModal = new bootstrap.Modal(document.getElementById('successModal'));
    successModal.show();

    // Redirect to policereport.php when OK is clicked
    document.getElementById('successOkBtn').addEventListener('click', function() {
        window.location.href = 'policereport.php';
    });
    </script>
    <?php endif; ?>



    <!-- Include jQuery and Bootstrap JS -->



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
        <?php if (!empty($selectedReport)): ?>
        <div class="report-container">
            <h5 class="fw-bold text-center"><?= htmlspecialchars($selectedReport["location"] ?? 'Unknown Location') ?>
            </h5>
            <hr>
            <div id="map"></div>
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
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal"
                data-bs-target="#cantRespondModal">Can't Respond</button>
            <form action="../officer/policearrive.php" method="post">
                <input type="hidden" name="report_id" value="<?php echo htmlspecialchars($report_id); ?>">
                <input type="hidden" name="status" value="on the way">
                <button type="submit" class="btn btn-warning">On the way</button>
            </form>


            </a>

            <img src="../<?php echo htmlspecialchars($image_path); ?>" alt="Crime Report Image"
                class="crime-report-img">



        </div>
        <?php else: ?>
        <p class="text-center mt-4">Report not found or no data available.</p>
        <?php endif; ?>

        <!-- modal -->
        <!-- Modal -->
        <div class="modal fade" id="cantRespondModal" tabindex="-1" aria-labelledby="cantRespondModalLabel"
            aria-hidden="true">


            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="cantRespondModalLabel">Why can't you respond to this report?
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="" onsubmit="return validateReasons()">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="outsideJurisdiction"
                                    name="reasons[]">
                                <label class="form-check-label" for="outsideJurisdiction">Outside Jurisdiction</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="handlingCase" name="reasons[]">
                                <label class="form-check-label" for="handlingCase">Currently Handling Another
                                    Case</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notEnoughPersonnel"
                                    name="reasons[]">
                                <label class="form-check-label" for="notEnoughPersonnel">Not Enough Personnel</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="other" name="reasons[]">
                                <label class="form-check-label" for="other">Other</label>
                            </div>

                            <textarea class="form-control mt-2" id="additionalDetails" name="additional_details"
                                placeholder="Additional details..."></textarea>

                            <input type="hidden" name="report_id"
                                value="<?= htmlspecialchars($selectedReport['id']) ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">Submit</button>
                    </div>
                    </form>
                </div>
            </div>

            <!-- JS VALIDATION SCRIPT -->
            <script>
            function validateReasons() {
                const checkboxes = document.querySelectorAll('input[name="reasons[]"]');
                const otherCheckbox = document.getElementById('other');
                const additionalDetails = document.getElementById('additionalDetails');

                let atLeastOneChecked = Array.from(checkboxes).some(cb => cb.checked);

                if (!atLeastOneChecked) {
                    alert("Please select at least one reason.");
                    return false;
                }

                if (otherCheckbox.checked && additionalDetails.value.trim() === "") {
                    alert("Please provide additional details for 'Other'.");
                    additionalDetails.focus();
                    return false;
                }

                return true;
            }
            </script>

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

    document.getElementById("overlay").addEventListener("click", function() {
        document.getElementById("sidebar").classList.remove("active");
        document.getElementById("overlay").classList.remove("active");
    });
    </script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
    // Initialize map
    var map = L.map('map').setView([<?php echo $latitude; ?>, <?php echo $longitude; ?>], 13);

    // Add OpenStreetMap tile layer to the map
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Add a marker to the map at the crime report's location
    L.marker([<?php echo $latitude; ?>, <?php echo $longitude; ?>])
        .addTo(map)
        .bindPopup(
            "<b>Crime Location</b><br>Latitude: <?php echo $latitude; ?><br>Longitude: <?php echo $longitude; ?>")
        .openPopup();
    </script>
    <?php if (isset($success_message)) : ?>
    <script>
    $(document).ready(function() {
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
    });
    </script>
    <?php endif; ?>

</body>

</html>