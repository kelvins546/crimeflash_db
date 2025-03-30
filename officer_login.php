<?php
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Empty string for the default password
$database = "crimeflash_db";


// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $query = "SELECT id, badge_number, password, status FROM officer_profiles WHERE email = ?";
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id, $badge_number, $hashed_password, $status);
                $stmt->fetch();

                if ($status !== 'active') {
                    $error_message = 'Your account is not active.';
                } elseif (password_verify($password, $hashed_password)) {
                    // Authentication successful
                    $_SESSION['officer_id'] = $id;
                    $_SESSION['badge_number'] = $badge_number;
                    // Redirect to officer_dashboard.php
                    header('Location: officer_dashboard.php');
                    exit();
                } else {
                    $error_message = 'Invalid password.';
                }
            } else {
                $error_message = 'No account found with that email.';
            }
            $stmt->close();
        } else {
            $error_message = 'Database error.';
        }
    } else {
        $error_message = 'Please fill in all fields.';
    }
}


// Check if officer is logged in
if (isset($_SESSION['officer_id'])) {
    // Fetch officer details
    $query = "SELECT first_name, last_name, rank, station FROM officer_profiles WHERE id = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('i', $_SESSION['officer_id']);
        $stmt->execute();
        $stmt->bind_result($first_name, $last_name, $rank, $station);
        $stmt->fetch();
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Officer Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.0/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2>Welcome, <?php echo htmlspecialchars($rank . ' ' . $first_name . ' ' . $last_name); ?></h2>
        <p>Station: <?php echo htmlspecialchars($station); ?></p>
        <a href="?logout=true" class="btn btn-danger">Logout</a>
    </div>
</body>

</html>
<?php

    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Officer Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Poppins";
        width: 100%;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .container {
        border: 1px solid black;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 450px;
        height: 350px;
        flex-direction: column;
        padding: 20px;
        box-shadow: 10px 10px 15px rgba(0, 0, 0, 0.2);

    }

    .container h2 {
        font-size: 2rem;
        font-weight: 800;
        margin-bottom: 30px;

    }
    </style>
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-3">Officer Login</h2>
        <?php
            if (isset($error_message)) {
                echo '<div class="alert alert-danger">' . htmlspecialchars($error_message) . '</div>';
            }
            ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="btn btn-primary">Login</button>
        </form>
    </div>
</body>

</html>
<?php
}
?>