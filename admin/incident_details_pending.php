<?php

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crimeflash_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if (isset($_POST['logout'])) {
    session_destroy(); // Destroy the session
    header('Location: admin_login.php'); // Redirect to login page
    exit();
}

// Fetch stations
$stations_query = "SELECT DISTINCT station FROM officer_profiles WHERE status IN ('active', 'on-duty')";
$stations_result = $conn->query($stations_query);
$stations = [];
if ($stations_result->num_rows > 0) {
    while ($row = $stations_result->fetch_assoc()) {
        $stations[] = $row['station'];
    }
}

// Fetch officers when a station is selected
$officers = [];
if (isset($_POST['station']) && !empty($_POST['station'])) {
    $station = $_POST['station'];
    $officers_query = "SELECT id, badge_number, first_name, last_name FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
    $stmt = $conn->prepare($officers_query);
    $stmt->bind_param("s", $station);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $officers[] = $row;
    }
}

// Handle officer assignment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $station = $_POST['station'];
    $report_id = $_POST['report_id'];

    // Fetch all active/on-duty officers from the selected station
    $officers_query = "SELECT id FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
    $stmt = $conn->prepare($officers_query);
    $stmt->bind_param("s", $station);
    $stmt->execute();
    $result = $stmt->get_result();

    // Prepare an array of officer IDs to be assigned
    $officer_ids = [];
    while ($row = $result->fetch_assoc()) {
        $officer_ids[] = $row['id'];
    }

    if (!empty($officer_ids)) {
        $officer_ids = array_map('strval', $officer_ids); // Ensure IDs are strings
        $assigned_officers = json_encode($officer_ids);  // Store as JSON string array

        // Update the report with the assigned officer IDs
        $update_query = "UPDATE crime_reports SET assigned_officer_ids = ?, status = 'assigned' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $assigned_officers, $report_id);

        if ($stmt->execute()) {
            // Trigger the modal display on success
            echo  "<script>
                    window.location.href = 'incident_pending_report.php';
                  </script>";
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "No active/on-duty officers available for this station.";
    }
}


// Handle verification status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status']) && isset($_POST['report_id'])) {
    $status = $_POST['status'];  // Get the new status from the button
    $report_id = $_POST['report_id'];  // Get the report ID

    // Update query to set verification status
    $update_query = "UPDATE crime_reports SET verification_status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $report_id);

    if ($stmt->execute()) {
        echo "Verification status updated successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Handle report retrieval logic
// Fetch report details (same logic as in your original code)
$user_details = null;
$assignment_success = false;
$error_message = '';

// Fetch report details
if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];
    $report_query = "SELECT * FROM crime_reports WHERE id = ?";
    $stmt = $conn->prepare($report_query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report_result = $stmt->get_result();
    if ($report_result->num_rows > 0) {
        $report = $report_result->fetch_assoc();
        if ($report['anonymous'] == 0) {
            $user_query = "SELECT * FROM user_profiles WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $report['user_id']);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            if ($user_result->num_rows > 0) {
                $user_details = $user_result->fetch_assoc();
            }
        }
    } else {
        die("Report not found.");
    }
} else {
    die("No report ID provided.");
}


$sql = "SELECT * FROM crime_reports WHERE id = ?";
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

// Query to fetch latitude and longitude
$query = "SELECT latitude, longitude FROM crime_reports WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$stmt->bind_result($latitude, $longitude);
$stmt->fetch();



if (isset($_POST['confirmTransfer'])) {
    $report_id = $_POST['report_id'];

    // Create a connection to the database with error handling
    $conn = new mysqli('localhost', 'root', '', 'crimeflash_db');

    // Check if the connection was successful
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch the details of the crime report to be transferred
    $stmt = $conn->prepare("SELECT * FROM crime_reports WHERE id = ?");

    // Check if prepare() was successful
    if (!$stmt) {
        die("Prepare failed for SELECT: " . $conn->error);
    }

    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the query executed successfully and returned results
    if ($result->num_rows > 0) {
        $report = $result->fetch_assoc();

        // Ensure that you pass correct values to INSERT (including NULLs where needed)
        $user_id = $report['user_id'];
        $officer_id = $report['officer_id'] ?? NULL;  // Null if no officer assigned
        $crime_type_id = $report['crime_type_id'];
        $description = $report['description'];
        $media_path = $report['media_path'] ?? NULL;  // Null if no media
        $contact_number = $report['contact_number'];
        $location = $report['location'];
        $latitude = $report['latitude'] ?? NULL;  // Null if not available
        $longitude = $report['longitude'] ?? NULL;  // Null if not available
        $status = $report['status'];
        $urgency_level = $report['urgency_level'] ?? 'medium';  // Default to 'medium'
        $anonymous = $report['anonymous'];
        $created_at = $report['created_at'];
        $updated_at = $report['updated_at'];
        $assigned_officer_id = $report['assigned_officer_id'] ?? NULL;  // Null if no officer assigned
        $assigned_officer_ids = $report['assigned_officer_ids'] ?? NULL;  // Null if no officer IDs
        $verification_status = $report['verification_status'] ?? 'awaiting_confirmation';  // Default if not set
        $resolution_notes = $report['resolution_notes'] ?? NULL;  // Null if no resolution notes
        $resolution_file = $report['resolution_file'] ?? NULL;  // Null if no resolution file
        $resolution_datetime = $report['resolution_datetime'] ?? NULL;  // Null if no resolution datetime

        // Prepare the SQL to insert into transferred_reports with error handling
        $insert_stmt = $conn->prepare("INSERT INTO transferred_reports 
            (user_id, officer_id, crime_type_id, description, media_path, contact_number, location, latitude, longitude, status, urgency_level, anonymous, created_at, updated_at, assigned_officer_id, assigned_officer_ids, verification_status, resolution_notes, resolution_file, resolution_datetime)
            VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)");

        // Check if prepare() was successful
        if (!$insert_stmt) {
            die("Prepare failed for INSERT: " . $conn->error);
        }

        // Bind the parameters for the prepared statement (match column count)
        if (!$insert_stmt->bind_param(
            "iissssssssisssssssss", // Match column types
            $user_id,
            $officer_id,
            $crime_type_id,
            $description,
            $media_path,
            $contact_number,
            $location,
            $latitude,
            $longitude,
            $status,
            $urgency_level,
            $anonymous,
            $created_at,
            $updated_at,
            $assigned_officer_id,
            $assigned_officer_ids,
            $verification_status,
            $resolution_notes,
            $resolution_file,
            $resolution_datetime
        )) {
            die("Parameter binding failed: " . $insert_stmt->error);
        }

        // Execute the insert statement with error handling
        if (!$insert_stmt->execute()) {
            die("Error executing INSERT: " . $insert_stmt->error);
        } else {
            // If the transfer is successful, delete the report from crime_reports
            $delete_stmt = $conn->prepare("DELETE FROM crime_reports WHERE id = ?");

            // Check if prepare() was successful for DELETE statement
            if (!$delete_stmt) {
                die("Prepare failed for DELETE: " . $conn->error);
            }

            $delete_stmt->bind_param("i", $report_id);

            // Execute the delete statement with error handling
            if (!$delete_stmt->execute()) {
                die("Error executing DELETE: " . $delete_stmt->error);
            } else {
                // Redirect to incident_pending_reports.php after success
                header("Location: incident_pending_report.php");
                exit(); // Don't forget to call exit after header to stop further execution
            }
        }
    } else {
        // If no report found
        echo "No such report found.";
    }

    // Safely close the connection if it's open
    if ($conn instanceof mysqli && !$conn->connect_errno) {
        $conn->close();
    }
}







// Close connection
$stmt->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incident Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <script src="https://kit.fontawesome.com/bac1e4ca00.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        width: 100%;
        font-family: "Poppins", sans-serif;
        min-height: 100vh;
        color: #000;
    }

    .wrapper {
        display: flex;
        flex-direction: row;
    }

    .top-nav {
        display: flex;
        align-items: center;
        width: 100%;
        height: 70px;
        background-color: #F3ce68;
        justify-content: space-between;
        position: fixed;
        top: 0;
        left: 0;
        color: #000;
        z-index: 10000 !important;
    }

    .top-nav ul {
        display: flex;
        list-style: none;
        margin-left: 300px;
    }

    .top-nav li a {
        text-decoration: none;
        color: #000;
        font-size: 1.1rem;
        padding-left: 30px;
    }

    .top-nav li a:hover {
        text-decoration: underline;
    }

    .top-nav .admin {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 30px;
    }

    .admin img {
        object-fit: contain;
        width: 90px;
        height: 80px;
        margin-bottom: 10px;
    }

    .admin h3 {
        color: #000;
        font-size: 1.5rem;
    }

    .sidenav {
        width: 250px;
        min-height: 100vh;
        background-color: #edb926;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 20px;

    }

    .sidenav ul {
        align-items: center;
        list-style: none;
        margin-top: 100px;


    }

    .sidenav li {
        margin-bottom: 30px;
    }

    .sidenav li a {
        text-decoration: none;
        color: #000;
        font-size: 1.1rem;
        padding: 5px;
    }

    .sidenav li a:hover {
        text-decoration: underline;

    }

    .hero {
        display: flex;
        flex-direction: column;
    }

    .search-container {
        margin-left: 120px;
        margin-top: 100px;
        position: relative;
        display: flex;
        align-items: center;
    }

    .search-container input {
        width: 450px;
        font-size: 1.1rem;
        padding: 10px 40px 10px 20px;
        border: none;
        border-radius: 25px;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
        transition: .3s ease-in-out;
    }

    .search-container i {
        position: absolute;
        left: 37%;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 1.3rem;
        color: #888;
    }

    .hero-report {
        display: flex;
        margin-top: 90px;

    }

    .container-table {
        margin-top: 100px;
        display: flex;
        align-items: center;
        justify-content: center;

    }

    table {
        margin-left: 30px;
    }

    th,
    td {
        padding: 14px;
        width: 200px;
        text-align: center;
        border-left: 1px solid black;
        font-weight: normal;

    }

    th {
        font-size: 16px;
        color: #000;
        background-color: #EDB926;

    }

    .btn {
        background-color: #edb926;
        color: #000;
        padding: 6px 10px;
        font-size: 14px;
        font-weight: normal;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
        text-decoration: none;
        display: inline-block;

    }


    .btn:hover {
        background-color: #d4a514;
        box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3);
        transform: translateY(-2px);

    }


    .btn:active {
        background-color: #b78b0f;
        transform: translateY(1px);

    }

    .report-container {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        width: 800px;
        display: flex;
        flex-direction: column;
        margin-left: 250px;
    }

    .title {
        text-align: center;
        font-size: 16px;
        font-weight: bold;
    }

    .divider {
        border-top: 1px solid #ccc;
        margin: 10px 0;
    }

    .content {
        display: flex;
        justify-content: space-between;
    }

    .content img {
        width: 45%;
        border-radius: 5px;
    }

    .details {
        margin-top: 10px;
    }

    .details p {
        margin: 5px 0;
        font-size: 14px;
    }

    .buttons {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
    }

    .buttons button {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }

    .suspicious {
        background: darkred;
        color: white;
    }

    .ass {
        background: green;
        color: white;
    }

    .leg {
        background: darkred;
        color: #fff;
    }


    .legitimate {
        background: goldenrod;
        color: #000;
    }

    .select-phase {
        width: 100%;
        margin-top: 10px;
    }


    #map {
        height: 250px;
        width: 50%;
    }

    .crime-report-img {
        height: 250px;
        width: 50%;
        object-fit: cover;
        /* Ensures the image fills the area without distortion */
        border-radius: 5px;
        /* Optional: adds rounded corners */
    }



    /* General form styles */
    form#assignmentForm {
        width: 35%;

        max-width: 500px;
        margin: 0;
        padding: 10px;

        border-radius: 5px;

    }

    form#assignmentForm label {
        font-size: 14px;
        font-weight: normal;
        margin-bottom: 8px;
        display: block;
        color: #333;
    }

    /* Dropdown Styling */
    form#assignmentForm select {
        width: 170px;
        padding: 7px;
        margin: 10px 0;

        border-radius: 5px;
        font-size: 14px;
        font-weight: normal;
        color: #000;
        /* Text color */
        background-color: #ccc;
        cursor: pointer;
        appearance: none;
    }

    /* Ensure font consistency */
    form#assignmentForm select option {
        color: #000;
    }

    /* Focus effect */
    form#assignmentForm select:focus {
        outline: none;
        border-color: #ccc;
    }

    /* Styling for officer dropdown */
    #officer-dropdown {
        border: 1px solid #ddd;
        padding: 10px;
        width: 270px;
        min-height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #officer-dropdown p {
        color: #888;
        font-style: italic;
        font-size: 14px;
    }

    /* Button styling */
    form#assignmentForm button {
        width: 130px;
        padding: 5px;
        margin: 5px 0;
        border: none;
        border-radius: 5px;
        font-size: 14px;
        background-color: #4CAF50;
        color: #fff;
        font-weight: normal;
        cursor: pointer;
    }

    form#assignmentForm button:hover {
        background-color: #45a049;
    }

    /* Modal Styles */
    .modaltransfer {
        display: none;
        /* Hidden by default */
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        /* Semi-transparent background */
        justify-content: center;
        align-items: center;
        z-index: 999999999;
    }

    .modal-contenttransfer {
        background-color: white;
        padding: 20px;
        border-radius: 5px;
        max-width: 400px;
        width: 80%;
        text-align: center;
    }

    button {
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        margin: 10px;
    }

    .confirm-btn {
        background: green;
        color: white;
    }

    .close-btn {
        background: red;
        color: white;
    }

    button:disabled {
        background-color: gray;
        cursor: not-allowed;
    }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="navigation-bar">
            <nav class="top-nav">
                <ul>
                    <li><a href="admin_panel.html">Accounts</a></li>
                    <li><a href="incident_pending_report.php">Incidents Records</a></li>
                    <li><a href="archieve.html">Archive</a></li>
                </ul>
                <div class="admin">
                    <h3>Welcome Admin</h3>
                    <img src="images/CRIMELOGOREMOVEDBG 1.png" alt="">
                </div>
            </nav>
            <aside class="sidenav">
                <ul>
                    <li>
                        <a href="dashboard.php">
                            <i class="fa-solid fa-circle-user"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="incident_pending_report.php">
                            <i class="fa-solid fa-circle-user"></i>
                            <span>Pending Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="incident_active_report.php">
                            <i class="fa-solid fa-circle-user"></i>
                            <span>Active Report</span>
                        </a>
                    </li>
                    <li>
                        <a href="incident_history.php">
                            <i class="fa-solid fa-circle-user"></i>
                            <span>History</span>
                        </a>
                    </li>
                    <li>
                        <form method="post" style="display: inline;">
                            <button type="submit" name="logout">Logout</button>
                        </form>
                    </li>
                </ul>
            </aside>
        </div>
        <main class="hero">




            <div class="hero-report">
                <div class="report-container">
                    <div class="title">
                        <?= htmlspecialchars($report['location'] ?? 'Unknown Location') ?>
                    </div>
                    <div class="divider"></div>
                    <div class="content">
                        <div id="map"></div>
                        <img src="../<?php echo htmlspecialchars($image_path); ?>" alt="Crime Report Image"
                            class="crime-report-img">




                    </div>
                    <?php if ($report): ?>
                    <div class="details">
                        <p><i>Time Reported: <?= htmlspecialchars($report['created_at'] ?? 'N/A') ?></i></p>

                        <p><i>Station Status:
                                <span style="color: <?= ($report['status'] == 'Suspicious') ? 'red' : 'green' ?>;">
                                    <?= htmlspecialchars($report['status'] ?? 'Pending') ?>
                                </span>
                            </i></p>

                        <!-- ✅ Display Verification Status -->
                        <p><i>Verification Status:
                                <span style="color: 
                      <?php


                        if ($report['verification_status'] == 'suspicious') echo 'orange';
                        elseif ($report['verification_status'] == 'legitimate') echo 'blue';
                        else echo 'gray'; // awaiting confirmation
                        ?>">
                                    <?= htmlspecialchars($report['verification_status'] ?? 'Awaiting Confirmation') ?>
                                </span>
                            </i></p>

                        <?php if ($report['anonymous'] == 0 && $user_details): ?>
                        <p><b>Name:</b> <?php echo htmlspecialchars($user_details['name']); ?></p>
                        <p><b>Contact:</b> <?php echo htmlspecialchars($user_details['contact_number']); ?></p>
                        <?php else: ?>
                        <p><b>Name:</b> Anonymous</p>
                        <p><b>Contact:</b> N/A</p>
                        <?php endif; ?>


                        <p><b>Description:</b>
                            <?= htmlspecialchars($report['description'] ?? 'No description available.') ?></p>
                    </div>

                    <!-- ✅ Buttons to Update Verification Status -->
                    <div class="buttons">
                        <!-- ✅ Buttons to Update Verification Status -->
                        <form method="POST" action="">
                            <input type="hidden" name="report_id" value="<?= htmlspecialchars($report_id) ?>">

                            <button type="submit" name="status" value="suspicious" class="suspicious">Mark as
                                Suspicious</button>
                            <button type="submit" name="status" value="legitimate" class="legitimate">Mark as
                                Legitimate</button>
                        </form>
                        <button type="button" class="ass" id="assignBtn">Assign</button>
                        <button type="button" class="leg" id="transferBtn">Transfer</button>

                    </div>
                    <?php else: ?>
                    <p>Report not found.</p>
                    <?php endif; ?>


                    <!-- Buttons to show form -->

                    <!-- The form that will be shown when "Assign" button is clicked -->
                    <form method="POST" action="" id="assignmentForm" style="display: none;">
                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report_id) ?>">

                        <div>
                            <select name="station" id="station" class="select-phase" required
                                onchange="this.form.submit()">
                                <option value="">Select a Station</option>
                                <?php foreach ($stations as $station): ?>
                                <option value="<?= htmlspecialchars($station) ?>"
                                    <?= (isset($_POST['station']) && $_POST['station'] == $station) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($station) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <?php if (isset($_POST['station']) && !empty($_POST['station'])): ?>
                        <div>
                            <h4>Preview: Officers at <?= htmlspecialchars($_POST['station']) ?> Station</h4>
                            <?php if (!empty($officers)): ?>
                            <ul>
                                <?php foreach ($officers as $officer): ?>
                                <li>
                                    <?= htmlspecialchars($officer['badge_number']) ?> -
                                    <?= htmlspecialchars($officer['first_name'] . ' ' . $officer['last_name']) ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php else: ?>
                            <p>No active/on-duty officers found in this station.</p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <button type="submit" name="assign">Assign Officer(s)</button>
                    </form>

                    <script>
                    // JavaScript to handle button clicks and toggle visibility of the form
                    document.getElementById('assignBtn').addEventListener('click', function() {
                        // Show the form and hide the button when clicked
                        document.getElementById('assignmentForm').style.display = 'block';
                        document.getElementById('assignBtn').style.display =
                            'none'; // Optionally hide the "Assign" button
                    });

                    // Optional: Add a handler for the "Transfer" button if needed
                    document.getElementById('transferBtn').addEventListener('click', function() {
                        // You can add logic to handle transfer action here

                    });
                    </script>



                    <!-- Modal Structure -->
                    <div id="transferModal" class="modaltransfer">
                        <div class="modal-contenttransfer">
                            <h2>Are you sure?</h2>
                            <p>This report is for the Barangay only. Are you sure you want to transfer it?</p>
                            <label>
                                <input type="checkbox" id="confirmTransfer" />
                                I confirm this report is for Barangay only.
                            </label>
                            <div>
                                <button id="confirmBtn" class="confirm-btn" disabled>Okay</button>
                                <button class="close-btn" id="closeBtn">Cancel</button>
                            </div>
                        </div>
                    </div>
                    <script>
                    // Get modal and buttons
                    const transferBtn = document.getElementById("transferBtn");
                    const transferModal = document.getElementById("transferModal");
                    const closeBtn = document.getElementById("closeBtn");
                    const confirmBtn = document.getElementById("confirmBtn");
                    const confirmCheckbox = document.getElementById("confirmTransfer");

                    // Open modal when Transfer button is clicked
                    transferBtn.addEventListener("click", function() {
                        transferModal.style.display = "flex";
                    });

                    // Close modal when Cancel button is clicked
                    closeBtn.addEventListener("click", function() {
                        transferModal.style.display = "none";
                    });

                    // Enable Okay button when checkbox is checked
                    confirmCheckbox.addEventListener("change", function() {
                        confirmBtn.disabled = !confirmCheckbox.checked;
                    });

                    // Handle confirmation of the transfer (when Okay button is clicked)
                    confirmBtn.addEventListener("click", function() {
                        if (confirmCheckbox.checked) {
                            // Your transfer logic here (e.g., submit the transfer form or make an API call)

                            transferModal.style.display = "none"; // Close modal after confirming
                        }
                    });
                    </script>
                    <script>
                    // Handle confirmation of the transfer (when Okay button is clicked)
                    confirmBtn.addEventListener("click", function() {
                        if (confirmCheckbox.checked) {
                            // Create a form to send data to PHP for transferring the report
                            const form = document.createElement("form");
                            form.method = "POST";
                            form.action = ""; // The current page

                            // Add necessary hidden inputs to the form
                            const reportIdInput = document.createElement("input");
                            reportIdInput.type = "hidden";
                            reportIdInput.name = "report_id";
                            reportIdInput.value =
                                "<?= $report_id ?>"; // The ID of the report you're transferring
                            form.appendChild(reportIdInput);

                            const confirmTransferInput = document.createElement("input");
                            confirmTransferInput.type = "hidden";
                            confirmTransferInput.name = "confirmTransfer";
                            confirmTransferInput.value = "true";
                            form.appendChild(confirmTransferInput);

                            // Append the form to the document body and submit it
                            document.body.appendChild(form);
                            form.submit();

                            // Close the modal
                            transferModal.style.display = "none"; // Close modal after confirming
                        }
                    });
                    </script>












                    <!-- Handle Assign all officers or specific officers -->




                </div>
            </div>
        </main>
    </div>
    <!-- Include Leaflet JS -->
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
    <!-- ✅ JavaScript to Handle Button Click -->
    <script>
    function updateStatus(status) {
        let reportId = <?= json_encode($report_id) ?>;

        fetch("", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `status=${status}&report_id=${reportId}`
            })
            .then(response => response.text())
            .then(responseText => {
                alert(responseText); // Show response message
                location.reload(); // Auto-refresh the page
            })
            .catch(console.error);
    }
    </script>


</body>

</html>