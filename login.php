<?php
session_start();

// Initialize variables
$email = $password = "";
$email_err = $password_err = $login_err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password_db = "";
    $dbname = "crimeflash_db";

    // Create connection
    $conn = new mysqli($servername, $username, $password_db, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Collect and sanitize form data
    $email = $conn->real_escape_string(trim($_POST['email']));
    $password_input = $_POST['password'];

    // Validate email
    if (empty($email)) {
        $email_err = "Email Address is required.";
    }

    // Validate password
    if (empty($password_input)) {
        $password_err = "Password is required.";
    }

    // If no errors, proceed to authenticate
    if (empty($email_err) && empty($password_err)) {
        // Prepare a select statement
        $stmt = $conn->prepare("SELECT id, email, password FROM user_profiles WHERE email = ?");
        $stmt->bind_param("s", $email);

        // Execute the statement
        $stmt->execute();
        $stmt->store_result();

        // Check if the email exists, then verify password
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $email_db, $hashed_password);
            if ($stmt->fetch() && password_verify($password_input, $hashed_password)) {
                // Password is correct, start a new session
                $_SESSION['user_id'] = $id;
                $_SESSION['email'] = $email_db;
                // Redirect to crime report form
                header("location: report_form.php");
                exit();
            } else {
                // Password is not valid
                $login_err = "Invalid email or password.";
            }
        } else {
            // Email doesn't exist
            $login_err = "No account found with that email.";
        }
        // Close the statement
        $stmt->close();
    }

    // Close the connection
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>Login</h2>
        <?php
        if (!empty($login_err)) {
            echo "<div class='error'>$login_err</div>";
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <div class="error"><?php echo $email_err; ?></div>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <div class="error"><?php echo $password_err; ?></div>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>