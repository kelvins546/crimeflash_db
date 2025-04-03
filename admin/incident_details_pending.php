<?php

session_start();
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

// Handle AJAX request to fetch officers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_officers') {
    $station = $_POST['station'];
    $officers_query = "SELECT id, badge_number, last_name, first_name, rank FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
    $stmt = $conn->prepare($officers_query);
    $stmt->bind_param("s", $station);
    $stmt->execute();
    $result = $stmt->get_result();
    $officers = [];
    while ($row = $result->fetch_assoc()) {
        $officers[] = $row;
    }
    echo json_encode($officers);
    exit();
}

// Initialize variables
$report = [];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $station = $_POST['station'];
    $assign_all = isset($_POST['assign_all']);
    $officer_ids = $_POST['officer'] ?? [];

    if ($station && isset($_GET['report_id'])) {
        $report_id = $_GET['report_id'];

        if ($assign_all) {
            // Assign all officers from the selected station
            $officers_query = "SELECT id FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
            $stmt = $conn->prepare($officers_query);
            $stmt->bind_param("s", $station);
            $stmt->execute();
            $result = $stmt->get_result();

            $officer_ids = [];
            while ($row = $result->fetch_assoc()) {
                $officer_ids[] = $row['id'];
            }
        }

        if (!empty($officer_ids)) {
            // Assign selected officers
            $assigned_officer_ids = json_encode($officer_ids);
            $assign_query = "UPDATE crime_reports SET assigned_officer_ids = ? WHERE id = ?";
            $stmt = $conn->prepare($assign_query);
            $stmt->bind_param("si", $assigned_officer_ids, $report_id);

            if ($stmt->execute()) {
                echo "Officers assigned successfully.";
            } else {
                echo "Error assigning officers.";
            }
        } else {
            echo "Please select officers or choose to assign all.";
        }
    }
}



// Fetch stations for the dropdown
$stations_query = "SELECT DISTINCT station FROM officer_profiles WHERE status IN ('active', 'on-duty')";
$stations_result = $conn->query($stations_query);
$stations = [];
if ($stations_result->num_rows > 0) {
    while ($row = $stations_result->fetch_assoc()) {
        $stations[] = $row['station'];
    }
}

// Handle AJAX request for officer data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['station'])) {
    file_put_contents('debug.txt', print_r($_POST, true));

    $station = $_POST['station'];

    // Fetch officers based on the selected station
    $officers_query = "SELECT id, badge_number, first_name, last_name FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
    $stmt = $conn->prepare($officers_query);
    $stmt->bind_param("s", $station);
    $stmt->execute();
    $result = $stmt->get_result();

    $officers = [];
    while ($row = $result->fetch_assoc()) {
        $officers[] = $row;
    }

    // Return the officers as JSON
    echo json_encode($officers);
    exit();
}

// Handle verification status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['status'], $_POST['report_id'])) {
    $status = $_POST['status'];
    $report_id = $_POST['report_id'];

    // Validate status
    if (!in_array($status, ['awaiting_confirmation', 'suspicious', 'legitimate'])) {
        die("Invalid status.");
    }

    // Use MySQLi instead of PDO
    $query = $conn->prepare("UPDATE crime_reports SET verification_status = ? WHERE id = ?");
    $query->bind_param("si", $status, $report_id);

    if ($query->execute()) {
        echo "Verification status updated!";
        exit;
    } else {
        echo "Error updating status.";
        exit;
    }
}

$image_path = !empty($report['media_path']) ? $report['media_path'] : "uploads/default.png"; // Use default if empty

// Ensure correct relative path
if (!file_exists("../uploads" . $image_path)) {
    $image_path = "uploads/default.png"; // Fallback image
}

// Query to fetch latitude and longitude
$query = "SELECT latitude, longitude FROM crime_reports WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$stmt->bind_result($latitude, $longitude);
$stmt->fetch();

// Close connection
$stmt->close();
$conn->close();
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
        color: #fff;
        /* Text color */
        background-color: #218d04;
        cursor: pointer;
        appearance: none;
    }

    /* Ensure font consistency */
    form#assignmentForm select option {
        color: #fff;
    }

    /* Focus effect */
    form#assignmentForm select:focus {
        outline: none;
        border-color: #4CAF50;
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
                        <button class="suspicious" onclick="updateStatus('suspicious')">Mark as Suspicious</button>
                        <button class="legitimate" onclick="updateStatus('legitimate')">Mark as Legitimate</button>
                    </div>
                    <?php else: ?>
                    <p>Report not found.</p>
                    <?php endif; ?>


                    <form method="POST" action="" id="assignmentForm">

                        <div>
                            <label for="station">Select Station:</label>
                            <select name="station" id="station" class="select-phase" required>
                                <option value="">Select a Station</option>
                                <?php foreach ($stations as $station): ?>
                                <option value="<?= htmlspecialchars($station) ?>"><?= htmlspecialchars($station) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="officer">Assign Officer(s):</label>
                            <div id="officer-dropdown" class="checkbox-list">
                                <p>Select a station first</p>
                            </div>
                        </div>

                        <div>
                            <input type="checkbox" name="assign_all" id="assign_all">
                            <label for="assign_all">Assign all officers at selected station</label>
                        </div>

                        <button type="submit" name="assign">Assign Officer(s)</button>
                    </form>

                    <script>
                    document.getElementById("station").addEventListener("change", function() {
                        console.log("Form is submitting...");
                        let checkboxes = document.querySelectorAll("input[name='officer[]']:checked");
                        let selectedOfficers = [];

                        checkboxes.forEach((checkbox) => {
                            selectedOfficers.push(checkbox.value);
                        });

                        console.log("Selected Officers:", selectedOfficers);
                        var station = this.value;
                        var officerDropdown = document.getElementById("officer-dropdown");

                        if (station) {
                            var formData = new FormData();
                            formData.append("station", station);
                            formData.append("action", "fetch_officers");

                            fetch("", {
                                    method: "POST",
                                    body: formData
                                })
                                .then(response => response.text())
                                .then(data => {
                                    console.log("Raw response:", data);
                                    try {
                                        var officers = JSON.parse(data);
                                        console.log("Parsed officers:", officers);

                                        officerDropdown.innerHTML = ""; // clear previous options

                                        if (officers.length > 0) {
                                            officers.forEach(officer => {
                                                console.log("Adding officer:", officer);

                                                let checkbox = document.createElement("input");
                                                checkbox.type = "checkbox";
                                                checkbox.name = "officer[]";
                                                checkbox.value = officer.id;
                                                checkbox.id = "officer_" + officer.id;

                                                let label = document.createElement("label");
                                                label.htmlFor = checkbox.id;
                                                label.textContent = officer.badge_number + " - " +
                                                    officer.first_name + " " + officer.last_name;

                                                let div = document.createElement("div");
                                                div.appendChild(checkbox);
                                                div.appendChild(label);
                                                officerDropdown.appendChild(div);
                                            });

                                            console.log("Final officer dropdown content:", officerDropdown
                                                .innerHTML);
                                        } else {
                                            officerDropdown.innerHTML = "<p>No officers available</p>";
                                            console.log("No officers available.");
                                        }
                                    } catch (error) {
                                        console.error("JSON parse error:", error);
                                    }
                                })
                                .catch(error => {
                                    console.error("Fetch error:", error);
                                });
                        } else {
                            officerDropdown.innerHTML = "<p>Select a station first</p>";
                        }
                    });
                    </script>

                    <!-- Handle Assign all officers or specific officers -->

                    <script>
                    document.getElementById('assignmentForm').addEventListener('submit', function(event) {
                        event.preventDefault(); // Prevents the default form submission

                        // Check if at least one officer is selected
                        const selectedOfficers = document.querySelectorAll('input[name="officer[]"]:checked');
                        if (selectedOfficers.length === 0) {
                            alert('Please select at least one officer');
                            return;
                        }

                        // Submit form data via AJAX
                        var formData = new FormData(this); // Create form data from the form

                        fetch('', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.text())
                            .then(data => {
                                // Handle server response (e.g., show a success message)
                                alert('Officers assigned successfully!');
                                location.reload(); // Reload the page after successful submission
                            })
                            .catch(error => {
                                alert('Error assigning officers: ' + error);
                            });
                    });
                    </script>


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