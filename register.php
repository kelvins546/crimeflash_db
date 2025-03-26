<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$name = $email = $contact_number = $address = $password = "";
$name_err = $email_err = $contact_number_err = $address_err = $password_err = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection
    $servername = "localhost";
    $username = "root";
    $password_db = "";
    $dbname = "crimeflash_db";

    $conn = new mysqli($servername, $username, $password_db, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Collect and sanitize form data
    $name = $conn->real_escape_string(trim($_POST['name']));
    $email_input = $_POST['email'];
    $contact_number_input = $_POST['contact_number'];
    $address_input = $_POST['address'];
    $password_input = $_POST['password'];

    // Validate inputs
    if (empty($name)) {
        $name_err = "Full Name is required.";
    }
    if (empty($email_input)) {
        $email_err = "Email Address is required.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $email_err = "Invalid email format.";
    } elseif (email_exists($email_input, $conn)) {
        $email_err = "Email is already taken.";
    }
    if (empty($contact_number_input)) {
        $contact_number_err = "Contact Number is required.";
    }
    if (empty($address_input)) {
        $address_err = "Address is required.";
    }
    if (empty($password_input)) {
        $password_err = "Password is required.";
    } elseif (strlen($password_input) < 6) {
        $password_err = "Password must be at least 6 characters.";
    }

    // If no errors, proceed to insert data into the database
    if (empty($name_err) && empty($email_err) && empty($contact_number_err) && empty($address_err) && empty($password_err)) {
        // Hash the password
        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

        // Insert into user_profiles table
        $stmt = $conn->prepare("INSERT INTO user_profiles (name, email, contact_number, address, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email_input, $contact_number_input, $address_input, $hashed_password);

        if ($stmt->execute()) {
            $success_message = "Registration successful!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }

    // Close the connection
    $conn->close();
}

// Function to check if email exists
function email_exists($email, $conn)
{
    $stmt = $conn->prepare("SELECT id FROM user_profiles WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f4f4f4;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }

    .container {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        width: 300px;
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-top: 10px;
        font-weight: bold;
    }

    input,
    textarea {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    button {
        width: 100%;
        padding: 10px;
        background-color: #5cb85c;
        border: none;
        color: #fff;
        font-size: 16px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
    }

    button:hover {
        background-color: #4cae4c;
    }

    .error {
        color: red;
        font-size: 0.9em;
    }

    .success {
        color: green;
        font-size: 1em;
        margin-bottom: 15px;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>Create an Account</h2>
        <?php
        if (isset($success_message)) {
            echo "<div class='success'>$success_message</div>";
        }
        if (isset($error_message)) {
            echo "<div class='error'>$error_message</div>";
        }
        ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                <div class="error"><?php echo $name_err; ?></div>
            </div>
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
            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="text" id="contact_number" name="contact_number"
                    value="<?php echo htmlspecialchars($contact_number); ?>" required>
                <div class="error"><?php echo $contact_number_err; ?></div>
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <textarea id="address" name="address" required><?php echo htmlspecialchars($address); ?></textarea>
                <div class="error"><?php echo $address_err; ?></div>
            </div>
            <li><a href="login.php">Login</a></li>
            <button type="submit">Register</button>
        </form>
    </div>
</body>

</html>