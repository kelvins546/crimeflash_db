<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "crimeflash_db";
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: admin_login.php');
    exit();
}
// Logout functionality
if (isset($_POST['logout'])) {
    session_destroy(); // Destroy the session
    header('Location: admin_login.php'); // Redirect to login page
    exit();
}

// Create user
if (isset($_POST['create_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "INSERT INTO user_profiles (name, email, contact_number, address, password) 
              VALUES ('$name', '$email', '$contact_number', '$address', '$password')";

    if ($conn->query($query)) {
        echo "User created successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Create officer
if (isset($_POST['create_officer'])) {
    $badge_number = $_POST['badge_number'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $rank = $_POST['rank'];
    $station = $_POST['station'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $status = $_POST['status'];

    $query = "INSERT INTO OFFICER_profiles (badge_number, first_name, last_name, rank, station, email, contact_number, status)
              VALUES ('$badge_number', '$first_name', '$last_name', '$rank', '$station', '$email', '$contact_number', '$status')";

    if ($conn->query($query)) {
        echo "Officer created successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}





?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
    /* Add some basic styles */
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
    }

    .sidenav h2 {
        text-align: center;
    }

    .sidenav ul {
        list-style-type: none;
        padding: 0;
    }

    .sidenav ul li {
        margin: 15px 0;
    }

    .sidenav ul li a {
        color: white;
        text-decoration: none;
        font-size: 18px;
    }

    .sidenav ul li a:hover {
        text-decoration: underline;
    }

    .main-content {
        flex: 1;
        padding: 20px;
    }

    h2 {
        margin-top: 30px;
    }

    form input,
    form textarea,
    form select,
    form button {
        width: 100%;
        padding: 10px;
        margin: 10px 0;
    }

    form button {
        background-color: #2c3e50;
        color: white;
        border: none;
        cursor: pointer;
    }

    form button:hover {
        background-color: #34495e;
    }
    </style>
</head>

<body>
    <div class="container">
        <!-- Side Navigation -->
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

        <!-- Main Content -->
        <div class="main-content">
            <h1>Welcome, Admin</h1>

            <!-- Add User Form -->
            <h2 id="add_user_form">Create User</h2>
            <form method="POST" action="">
                <input type="text" name="name" placeholder="Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="contact_number" placeholder="Contact Number" required>
                <textarea name="address" placeholder="Address"></textarea>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="create_user">Create User</button>
            </form>

            <!-- Add Officer Form -->
            <h2 id="add_officer_form">Create Officer</h2>
            <form method="POST" action="">
                <input type="text" name="badge_number" placeholder="Badge Number" required>
                <input type="text" name="first_name" placeholder="First Name" required>
                <input type="text" name="last_name" placeholder="Last Name" required>
                <input type="text" name="rank" placeholder="Rank" required>
                <input type="text" name="station" placeholder="Station" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="contact_number" placeholder="Contact Number" required>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="on-duty">On Duty</option>
                    <option value="off-duty">Off Duty</option>
                    <option value="suspended">Suspended</option>
                </select>
                <button type="submit" name="create_officer">Create Officer</button>
            </form>
        </div>
    </div>
</body>

</html>