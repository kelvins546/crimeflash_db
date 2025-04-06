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
if (isset($_POST['logout'])) {
    session_destroy(); // Destroy the session
    header('Location: admin_login.php'); // Redirect to login page
    exit();
}

// create user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact_number = $_POST['contact_number'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // check if email already exists
    $check_email = $conn->prepare("SELECT id FROM user_profiles WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $_SESSION['error_message'] = "email already exists!";
    } else {
        // insert user
        $stmt = $conn->prepare("INSERT INTO user_profiles (name, email, contact_number, address, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $contact_number, $address, $password);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "user created successfully!";
        } else {
            $_SESSION['error_message'] = "error: " . $stmt->error;
        }

        $stmt->close();
    }
    $check_email->close();

    header("location: " . $_SERVER['PHP_SELF']);
    exit;
}

// update user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $id = $_POST['edit_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    $stmt = $conn->prepare("UPDATE user_profiles SET name=?, email=?, contact_number=?, address=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $contact_number, $address, $id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error: " . $stmt->error;
    }

    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// fetch users for table display
$result = $conn->query("SELECT id, email, name, contact_number, address FROM user_profiles");

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
            z-index: -1;
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


        .top-nav .navtoh {
            display: flex;
            margin-left: 300px;
            list-style: none;

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
            display: flex;
            align-items: center;
            z-index: 1;
            /* Reset z-index to avoid any unintended overlap */
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
            left: 780px;

            top: 16.5%;
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

        /* Table styling */
        table {
            margin-left: 120px;
            border-collapse: collapse;
            border-radius: 10px;
            /* Rounded corners for the whole table */
            overflow: hidden;
            /* Ensures that rounded corners are visible */
            width: 100%;

        }

        /* Table headers */
        th,
        td {
            padding: 14px;
            text-align: center;
            border: 1px solid black;

            font-weight: normal;
        }

        /* Table header styling */
        th {
            font-size: 16px;
            color: #000;
            background-color: #EDB926;
        }

        /* Add line between rows */
        tr:nth-child(even) {
            background-color: #f2f2f2;
            /* Alternating row color for better readability */
        }

        tr:hover {
            background-color: #ddd;
            /* Light gray on row hover */
        }


        /* Suspended Button */
        .btn-suspended {
            background-color: #e74c3c;
            /* Red color for suspended */
            color: #fff;
            /* White text for contrast */
            padding: 6px 12px;
            font-size: 14px;
            font-weight: normal;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 3px 3px 10px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-suspended:hover {
            background-color: #c0392b;
            /* Darker red on hover */
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3);
            /* Enhanced shadow on hover */
            transform: translateY(-2px);
            /* Lift button effect */
        }

        .btn-suspended:active {
            background-color: #a93226;
            /* Even darker red when pressed */
            transform: translateY(1px);
            /* Slight downward movement for pressed effect */
        }

        /* General Button */
        .btn {
            background-color: #edb926;
            /* Original button color */
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
            /* Darken color on hover */
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3);
            /* Enhanced shadow on hover */
            transform: translateY(-2px);
            /* Lift button effect */
        }

        .btn:active {
            background-color: #b78b0f;
            /* Darker color when pressed */
            transform: translateY(1px);
            /* Slight downward movement for pressed effect */
        }

        /* Edit Button - Gray color */
        .btn-edit {
            background-color: #95a5a6;
            /* Gray color for editing */
            color: #fff;
            /* White text */
            padding: 6px 12px;
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

        .btn-edit:hover {
            background-color: #7f8c8d;
            /* Darker gray on hover */
            box-shadow: 5px 5px 15px rgba(0, 0, 0, 0.3);
            /* Enhanced shadow on hover */
            transform: translateY(-2px);
            /* Lift button effect */
        }

        .btn-edit:active {
            background-color: #707b7c;
            /* Even darker gray when pressed */
            transform: translateY(1px);
            /* Slight downward movement for pressed effect */
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
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            margin: 10% auto;
            width: 30%;
            border-radius: 8px;
            text-align: center;
        }

        .close {
            float: right;
            font-size: 28px;
            cursor: pointer;
        }

        /* Make the dropdown menu hidden by default */
        .dropdown-menu {
            display: none !important;
            position: absolute;
            background-color: white;
            list-style: none;
            margin: 0;
            padding: 0;
            min-width: 200px;
            z-index: 999000 !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            left: 0px !important;
            /* Align dropdown to the left of the parent */
            top: 100%;
            /* Position it directly below the parent */
            border-radius: 5px;
        }

        /* Show the dropdown when hovering over the parent */
        .dropdown:hover .dropdown-menu {
            display: block !important;
        }

        /* Set the parent li to position relative so we can position the dropdown properly */
        .dropdown {
            position: relative;
        }

        /* Optional: Ensure that dropdown items look better */
        .dropdown-menu li a {
            padding: 10px 15px;
            display: block;
            color: #333;
            text-decoration: none;
            z-index: 999000;
            font-size: 16px;
        }

        .dropdown-menu li a:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="navigation-bar">
            <nav class="top-nav">
                <div class="navtoh">



                    <div class="nav">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">Accounts ▾</a>
                            <ul class="dropdown-menu">
                                <li><a href="active_accounts.php">Active Accounts</a></li>
                                <li><a href="suspended_accounts.php">Suspended Accounts</a></li>
                            </ul>
                        </li>
                    </div>
                    <li><a href="incident_pending_report.php">Incidents Records</a></li>
                    <li><a href="archieve.html">Archive</a></li>

                </div>
                <div class="admin">
                    <h3>Welcome Admin</h3>
                    <img src="images/CRIMELOGOREMOVEDBG 1.png" alt="">
                </div>
            </nav>
            <aside class="sidenav">
                <ul>
                    <li>
                        <a href="admin_panel.php">
                            <i class="fa-solid fa-circle-user"></i>
                            <span>User Account</span>
                        </a>
                    </li>
                    <li>
                        <a href="account_officer.php">
                            <i class="fa-solid fa-circle-user"></i>
                            <span>Officer Account</span>
                        </a>
                    </li>
                    <li>
                        <a href="account_barangay.php">
                            <i class=" fa-solid fa-circle-user"></i>
                            <span>Barangay Account</span>
                        </a>
                    </li>
                    </li>
                    <li>
                        <a href="account_clerk.php">
                            <i class=" fa-solid fa-circle-user"></i>
                            <span>Clerk Account</span>
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
                <!-- create user button -->
                <button class="btn btn-primary" onclick="openModal()">Create User</button>

                <!-- success/error messages -->
                <?php if (isset($_SESSION['success_message'])) : ?>
                    <p class="success"><?= $_SESSION['success_message'];
                                        unset($_SESSION['success_message']); ?></p>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])) : ?>
                    <p class="error"><?= $_SESSION['error_message'];
                                        unset($_SESSION['error_message']); ?></p>
                <?php endif; ?>

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
                <!-- create user modal -->
                <div id="userModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal()">&times;</span>
                        <h2>Create User</h2>
                        <form method="POST" action="">
                            <input type="text" name="name" placeholder="Name" required>
                            <input type="email" name="email" placeholder="Email" required>
                            <input type="text" name="contact_number" placeholder="Contact Number" required>
                            <textarea name="address" placeholder="Address"></textarea>
                            <input type="password" name="password" placeholder="Password" required>
                            <button type="submit" name="create_user">Create User</button>

                        </form>
                    </div>
                </div>
                <div class="container-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Fullname</th>
                                <th>Contact</th>


                                <th>Address</th>
                                <th>Action</th> <!-- Column for Action button -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                // Fetch and display each report aowa table row
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $row['email'] . "</td>";
                                    echo "<td>" . $row['name'] . "</td>";
                                    echo "<td>" . $row['contact_number'] . "</td>";
                                    echo "<td>" . $row['address'] . "</td>";


                                    echo "<td>
                                    <button class='btn-edit' onclick='openEditModal(" . json_encode($row) . ")'>Edit</button>
                                    <button class='btn-suspended' onclick='openEditModal(" . json_encode($row) . ")'>Suspend</button>
                           
                                  </td>";


                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No users found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- edit modal -->
            <!-- edit modal -->
            <div id="editModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Edit User</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="id" id="editUserId">
                        <input type="text" name="name" id="editName" placeholder="Full Name" required>
                        <input type="email" name="email" id="editEmail" placeholder="Email" required>
                        <input type="text" name="contact_number" id="editContact" placeholder="Contact Number" required>
                        <textarea name="address" id="editAddress" placeholder="Address"></textarea>
                        <button type="submit" name="update_user">Save Changes</button>
                    </form>
                </div>
            </div>


        </main>
    </div>
    <!-- modal script -->
    <script>
        function openEditModal(user) {
            document.getElementById('editUserId').value = user.id;
            document.getElementById('editName').value = user.name;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editContact').value = user.contact_number;
            document.getElementById('editAddress').value = user.address;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>

    <script>
        function openModal() {
            document.getElementById("userModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("userModal").style.display = "none";
        }

        window.onclick = function(event) {
            let modal = document.getElementById("userModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>