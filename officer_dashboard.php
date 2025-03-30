<?php
session_start();

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = ""; // Default password is an empty string
$database = "crimeflash_db";

// Check if the officer is logged in
if (!isset($_SESSION['officer_id'])) {
    header('Location: login.php');
    exit();
}

$officer_id = $_SESSION['officer_id'];

// Database connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Fetch officer details
$officer_query = "SELECT first_name, last_name FROM officer_profiles WHERE id = ?";
$officer_stmt = $conn->prepare($officer_query);
$officer_stmt->bind_param('i', $officer_id);
$officer_stmt->execute();
$officer_result = $officer_stmt->get_result();
$officer_details = $officer_result->fetch_assoc();

if (!$officer_details) {
    // Handle the case where no officer details are found
    echo "No officer details found.";
    exit();
}

// Fetch reports assigned to the logged-in officer
$query = "SELECT * FROM crime_reports WHERE JSON_CONTAINS(assigned_officer_ids, ?, '$')";
$stmt = $conn->prepare($query);
$officer_id_json = json_encode((string)$officer_id);
$stmt->bind_param('s', $officer_id_json);
$stmt->execute();
$result = $stmt->get_result();

$officer_id_json = json_encode((int)$officer_id);
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
} else {
    // Display login form
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.0/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h1>Welcome, Officer <?php echo htmlspecialchars($officer_details['first_name']); ?></h1>
        <h2>Assigned Reports</h2>
        <?php if ($result->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($report = $result->fetch_assoc()): ?>
                    <a href="view_report.php?report_id=<?php echo $report['id']; ?>"
                        class="list-group-item list-group-item-action">
                        <h5 class="mb-1">Report ID: <?php echo htmlspecialchars($report['id']); ?></h5>
                        <p class="mb-1"><?php echo htmlspecialchars($report['description']); ?></p>
                        <small>Assigned on: <?php echo htmlspecialchars($report['assigned_at']); ?></small>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No reports assigned to you at this time.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>