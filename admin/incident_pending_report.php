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
// Fetch all crime reports
$query = "SELECT * FROM crime_reports";
$result = $conn->query($query);


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
        margin-top: 30px;

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
        width: 600px;
        display: flex;
        flex-direction: column;
        margin-left: 250px;
    }

    .title {
        text-align: center;
        font-size: 20px;
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
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="navigation-bar">
            <nav class="top-nav">
                <ul>
                    <li><a href="admin_panel.php">Accounts</a></li>
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
                            <i class=" fa-solid fa-circle-user"></i>
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
            <div class="search-nbtn">
                <div class="search-container">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search Incident ..." id="search-bar" onkeyup="search()">
                </div>

                <!--this should be in incident_details_report
          <div class="hero-report">
            <div class="report-container">
                <div class="title">Phase 1, Simbahan <br> Bagong Silang, Caloocan City</div>
                <div class="divider"></div>
                <div class="content">
                    <img src="images/image 7.png" alt="Map Location">
                    <img src="images/image (1).png" alt="Innt Image">
                </div>
                <div class="details">
                    <p><i>Time Reported: 15:30</i></p>
                    <p><i>Station Status: <span style="color: red;">Suspicious</span></i></p>
                    <p><b>Name:</b> Gian Felipe</p>
                    <p><b>Contact:</b> 09123456789</p>
                    <p><b>Description:</b> May nakita po akong nakahandusay na tao sa kalsada na puno ng dugo sa harap
                        ng Simbahan</p>
                </div>
                <div class="buttons">
                    <button class="suspicious">Mark as Suspicious</button>
                    <button class="legitimate">Mark as Legitimate</button>
                </div>
                <select class="select-phase">
                    <option>Phase 1</option>
                    <option>Phase 2</option>
                    <option>Phase 3</option>
                </select>
            </div>
        </div>-->
                <div class="container-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Report ID</th>
                                <th>Description</th>
                                <th>Date Submitted</th>
                                <th>Location</th>

                                <th>Status</th>
                                <th>Action</th> <!-- Column for Action button -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                // Fetch and display each report aowa table row
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['id'] . "</td>";
                                    echo "<td>" . $row['description'] . "</td>";
                                    echo "<td>" . $row['created_at'] . "</td>";
                                    echo "<td>" . $row['location'] . "</td>";

                                    echo "<td>" . $row['status'] . "</td>";
                                    echo "<td><a href='incident_details_pending.php?report_id=" . $row['id'] . "' class='btn'>view details</a></td>"; // Assign button

                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No reports found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>

</html>