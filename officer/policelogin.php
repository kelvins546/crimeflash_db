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
                    header('Location: policereport.php');
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
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <title>PoliceLogin</title>
</head>

<body>
    <style>
    * {
        box-sizing: border-box;
    }

    body,
    html {
        height: 100%;
        margin: 0;
        padding: 0;
        font-family: "Poppins", sans-serif;
    }

    .login-container {
        height: 100vh;
        display: flex;
        justify-content: space-evenly;
        align-items: center;
    }

    .login-box {
        width: 100%;
        max-width: 400px;
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        text-align: center;
        position: relative;
        overflow: hidden;

    }

    .login-box::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 120px;
        height: 120px;
        background: #f4a623;
        border-bottom-left-radius: 100px;
    }

    .logo {
        width: 80px;
        margin-top: 20px;
    }

    .login-box h3 {
        font-weight: 700;
        font-size: 1.5rem;
    }

    .login-box h3 span {
        color: #d4881e;
    }

    .btn-custom {
        background: #f4a623;
        color: white;
        width: 100%;
    }

    .btn-custom:hover {
        background: #d4881e;
        color: white;
    }

    .logopic-container {
        margin-bottom: 230px;
    }

    .logopic-container img {
        width: 600px;
        height: 600px;
        object-fit: cover;
    }

    .logopic-container p {
        font-style: italic;
        font-size: 3rem;
        margin-left: 20px;
        width: 600px;
    }

    @media (max-width:992px) {
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .logopic-container {
            display: none;
        }
    }
    </style>
    </head>

    <body>
        <div class="container-fluid">
            <div class="row p-5">
                <div class="login-container">
                    <div class="login-box col-md-6">
                        <img src="../images/CRIMELOGOREMOVEDBG 1.png" alt="Logo" class="logo">

                        <h3 class="mt-2">CRIME<span> FLASH</span></h3>
                        <p class="fw-bold">Police<span class="text-warning"> Log In</span></p>
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="mb-3">
                                <input type="email" class="form-control" id="email" name="email" placeholder="Email"
                                    required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-custom mb-3">Confirm</button>
                        </form>

                        <p class="fw-bold mt-2"><span class="text-warning">Justice</span> never sleeps.</p>
                    </div>
                    <div class="logopic-container col-md-6 ms-5">
                        <img src="../images/CRIMELOGOREMOVEDBG 1.png" alt="">
                        <p class="fw-bold mt-2"><span class="text-warning">Justice</span> never sleeps.</p>
                    </div>
                </div>
            </div>
        </div>
    </body>

</html>