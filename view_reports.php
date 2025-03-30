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


// Fetch all crime reports
$query = "SELECT * FROM crime_reports";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Crime Reports</title>
    <link rel="stylesheet" href="">
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        margin: 0;
        padding: 0;
    }

    .tb-container {
        width: 80%;
        margin: 20px auto;
        background-color: #fff;
        padding: 20px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    table,
    th,
    td {
        border: 1px solid #ddd;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
    }

    th {
        background-color: #f2f2f2;
        color: #333;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f1f1f1;
    }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        display: flex;
    }

    .container {
        display: flex;
    }

    .sidenav {
        width: 250px;
        background-color: #2c3e50;
        color: white;
        padding: 20px;
        min-height: 100vh;
    }

    .sidenav h2 {
        text-align: center;
        color: white;
        font-size: 2rem;

    }

    .sidenav ul {
        list-style-type: none;
        padding-left: 20px;

    }

    .sidenav ul li {
        margin: 30px 0;
    }

    .sidenav ul li a {
        color: white;
        text-decoration: none;
        font-size: 20px;
    }

    .sidenav ul li a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <div class="container">
        <nav class="sidenav">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="adminpanel.php">Dashboard</a></li>
                <li><a href="view_reports.php">View Crime Reports</a></li>
                <li><a href="adminpanel.php#add_user_form">Add User</a></li>
                <li><a href="adminpanel.php#add_officer_form">Add Officer</a></li>
                <li>
                    <form method="POST" action="" style="display:inline;">
                        <button type="submit" name="logout">Logout</button>
                    </form>
                </li>
            </ul>
        </nav>
        <div class="tb-container">

            <h2>Submitted Crime Reports</h2>
            <table>
                <thead>
                    <tr>
                        <th>Report ID</th>

                        <th>Description</th>
                        <th>Date Submitted</th>
                        <th>Location</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
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
                            echo "<td>" . $row['latitude'] . "</td>";
                            echo "<td>" . $row['longitude'] . "</td>";
                            echo "<td>" . $row['status'] . "</td>";
                            echo "<td><a href='assign_officer.php?report_id=" . $row['id'] . "' class='btn'>Assign Officer</a></td>"; // Assign button

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
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>