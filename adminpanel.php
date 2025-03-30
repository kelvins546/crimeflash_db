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
    $station = $_POST['station'];
    $contact_number = $_POST['contact_number'];
    $status = $_POST['status'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $query = "INSERT INTO OFFICER_profiles (badge_number, first_name, last_name, rank, station, email, contact_number, status,password)
              VALUES ('$badge_number', '$first_name', '$last_name', '$rank', '$station', '$email', '$contact_number', '$status','$password')";

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
                <li><a href="">Dashboard</a></li>
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

                <label for="rank">Rank:</label>
                <select name="rank" id="rank" required>
                    <option value="" disabled selected>Select a rank</option>
                    <option value="Police General">Police General</option>
                    <option value="Police Lieutenant General">Police Lieutenant General</option>
                    <option value="Police Major General">Police Major General</option>
                    <option value="Police Brigadier General">Police Brigadier General</option>
                    <option value="Police Colonel">Police Colonel</option>
                    <option value="Police Lieutenant Colonel">Police Lieutenant Colonel</option>
                    <option value="Police Major">Police Major</option>
                    <option value="Police Captain">Police Captain</option>
                    <option value="Police Lieutenant">Police Lieutenant</option>
                    <option value="Police Executive Master Sergeant">Police Executive Master Sergeant</option>
                    <option value="Police Chief Master Sergeant">Police Chief Master Sergeant</option>
                    <option value="Police Senior Master Sergeant">Police Senior Master Sergeant</option>
                    <option value="Police Master Sergeant">Police Master Sergeant</option>
                    <option value="Police Staff Sergeant">Police Staff Sergeant</option>
                    <option value="Police Corporal">Police Corporal</option>
                    <option value="Patrolman/Patrolwoman">Patrolman/Patrolwoman</option>
                </select>



                <label for="station">Station:</label>
                <select name="station" id="station" required>
                    <option value="">Select Station</option>
                    <option value="S1">S1</option>
                    <option value="S2">S2</option>
                    <option value="S3">S3</option>
                    <option value="S4">S4</option>
                    <option value="S5">S5</option>
                    <option value="S6">S6</option>
                    <option value="S7">S7</option>
                    <option value="S8">S8</option>
                    <option value="S9">S9</option>
                    <option value="S10">S10</option>
                    <option value="S11">S11</option>
                    <option value="S12">S12</option>
                </select>

                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="contact_number" placeholder="Contact Number" required>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="on-duty">On Duty</option>
                    <option value="off-duty">Off Duty</option>
                    <option value="suspended">Suspended</option>
                </select>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="create_officer">Create Officer</button>
            </form>
        </div>
    </div>
</body>

</html>