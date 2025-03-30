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

// Check if the form is submitted
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if login credentials match (simple example, adjust as necessary)
    $query = "SELECT * FROM admins WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $_SESSION['admin'] = $email; // Store session variable
        header('Location: admin_panel.php'); // Redirect to admin panel
        exit();
    } else {
        echo "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
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
        font-family: "Poppins", sans-serif;
    }

    .container {
        display: flex;
        width: 100%;
        min-height: 100vh;
        justify-content: center;
        align-items: center;
    }

    .left-side h1 {
        font-weight: bold;
        font-size: 4rem;
        color: #EDB926;
        margin-bottom: 30px;
    }

    .container-login {
        border: 1px solid rgba(0, 0, 0, 0.2);
        box-shadow: .5rem 6px 8px rgb(0, 0, 0, 0.2);
        border-radius: 10px;
        width: 450px;
        height: 350px;
        padding: 40px;
        background-color: whitesmoke;
        flex-direction: column;
        justify-content: center;
        align-items: center;

    }

    .container-login label {
        font-size: 18px;
        margin-bottom: 20px;
    }

    .container-login input {
        width: 100%;
        padding: 10px;
        margin-bottom: 30px;
        border-radius: 5px;
        border: 1px solid black;

    }

    .colored-btn {
        text-decoration: none;
        padding: 10px 20px;
        font-size: 13px;
        width: 100%;
        text-align: center;
        background-color: #EDB926;
        line-height: 1.5;
        font-weight: 500;
        color: white;
        letter-spacing: 2px;
        cursor: pointer;
        margin-bottom: 20px;
    }

    .colored-btn:hover {
        background-color: rgb(175, 135, 25);
    }

    .right-side {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .right-side img {
        width: 600px;
        height: 550px;
        margin-left: 50px;
        margin-bottom: 20px;

    }
    </style>
</head>

<body>
    <div class="container">
        <div class="left-side">
            <h1>Admin Login</h1>
            <form method="POST" action="">
                <div class="container-login">
                    <label for="email">Email</label>
                    <input type="email" class="input" id="email" name="email" required>
                    <label for="password">Password</label>
                    <input type="password" class="input" id="password" name="password" required>
                    <button type="submit" class="submit-btn colored-btn" name="login">Login</button>
                </div>
            </form>
        </div>
        <div class="right-side">
            <img src="images/CRIMELOGOREMOVEDBG 1.png" alt="">
        </div>
</body>

</html>