<?php

session_start();


$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crimeflash_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("connection failed: " . $conn->connect_error);
}

// ✅ ensure officer is logged in
if (!isset($_SESSION["officer_id"])) {
    header("location: policelogin.php");
    exit();
}

$officer_id = (string) $_SESSION["officer_id"]; // force it to string
$officer_json = json_encode($officer_id); // encode it as JSON

$query = "SELECT * FROM crime_reports WHERE JSON_CONTAINS(assigned_officer_ids, ?, '$')";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $officer_json);
$stmt->execute();
$result = $stmt->get_result();


$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Notification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <script src="https://kit.fontawesome.com/bac1e4ca00.js" crossorigin="anonymous"></script>
    <style>
    body {
        background-color: #f8f9fa;
        font-family: "Poppins", sans-serif;
        overflow-x: hidden;
    }

    .navbar {
        background-color: #f4a623;
    }

    .icon-container {
        display: flex;
        align-items: center;

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
        margin-bottom: 30px;
    }

    .sidebar .menu-items {
        list-style: none;
        padding: 0;
        margin: 0;
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

    .content {
        margin-left: 0;
        transition: margin-left 0.3s ease-in-out;
    }

    .content.active {
        margin-left: 250px;
    }

    .card-custom {
        background-color: #7a6e6e;
        color: white;
        padding: 15px;
        border-radius: 10px;
    }

    .card-content {
        background-color: white;
        color: black;
        padding: 15px;
        border-radius: 10px;
    }

    .btn-custom {
        background-color: #f4a623;
        border: none;
        padding: 5px 15px;
        font-weight: bold;
    }

    .btn-custom a {
        text-decoration: none;
        color: white;
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
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid icon-container">
            <button id="menu-toggle" class="btn text-white"><i class="fa-solid fa-bars"></i></button>
            <a href="../police/policereport.html">
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
            <li><a href="../officer/policereport.php"><i class="fa-solid fa-folder"></i> ASSIGNED CASES</a></li>
            <li><a href="../officer/policeprofile.php"><i class="fa-solid fa-user"></i> PROFILE</a></li>
        </ul>
        <div class="logout">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </div>
    </div>


    <!-- Report Section -->
    <!-- In the reports list page -->
    <div class="container mt-4">
        <h4 class="text-center fw-bold">reports/notification</h4>

        <?php if (!empty($reports)): ?>
        <div class="d-flex justify-content-center mt-5">
            <?php foreach ($reports as $report): ?>
            <div class="card-custom col-10">
                <div class="card-content">
                    <p><strong>Location:</strong> <?= htmlspecialchars($report["location"]) ?></p>
                    <p><strong>Crime Type:</strong> <?= htmlspecialchars($report["crime_type_id"]) ?></p>
                    <p><strong>Description:</strong> <?= htmlspecialchars($report["description"]) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($report["contact_number"]) ?></p>
                    <p class="text-muted"><em>Date Reported: <?= htmlspecialchars($report["created_at"]) ?></em></p>
                    <p class="status-legitimate">Station Status: <?= htmlspecialchars($report["verification_status"]) ?>
                    </p>
                    <button class="btn btn-custom w-100">
                        <a href="policeview_report.php?id=<?= $report["id"] ?>">View Details</a>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p class="text-center mt-4">No reports assigned to you at the moment.</p>
        <?php endif; ?>
    </div>

    <!-- In the policeview_report.php -->


    <script>
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
</body>

</html>