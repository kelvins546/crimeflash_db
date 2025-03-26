<?php
// Include the database connection file
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crimeflash_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if report_id is passed in the URL
if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];
} else {
    die("No report ID provided.");
}
// Fetch the crime report details
$report_query = "SELECT * FROM crime_reports WHERE id = ?";
$stmt = $conn->prepare($report_query);
$stmt->bind_param("i", $report_id);
$stmt->execute();
$report_result = $stmt->get_result();

// Check if report exists
if ($report_result->num_rows == 0) {
    die("Report not found.");
}

$report = $report_result->fetch_assoc();
$user_details = null;
if ($report['anonymous'] == 0) {
    $user_query = "SELECT * FROM user_profiles WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $report['user_id']);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows > 0) {
        $user_details = $user_result->fetch_assoc();
    }
}

// Fetch active and on-duty officers from the database
$query = "SELECT * FROM OFFICER_profiles WHERE status IN ('active', 'on-duty')";
$result = $conn->query($query);

// Assign officer to the report when the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $officer_id = $_POST['officer_id'];

    // Update the crime report with the assigned officer
    $update_query = "UPDATE crime_reports SET officer_id = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $officer_id, $report_id);
    if ($stmt->execute()) {
        echo "Officer assigned successfully.";
    } else {
        echo "Error assigning officer: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Officer</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your custom CSS -->
</head>

<body>
    <div class="container">

        <h2>Assign Officer to Crime Report</h2>
        <div class="report-details">
            <h3>Report Details</h3>
            <p><strong>Media:</strong>
                <?php echo $report['media_path'] ? '<a href="' . $report['media_path'] . '" target="_blank">View Media</a>' : 'No media submitted'; ?>
            </p>
            <p><strong>Report ID:</strong> <?php echo $report['id']; ?></p>

            <p><strong>Description:</strong> <?php echo $report['description']; ?></p>
            <p><strong>Location:</strong> <?php echo $report['location']; ?></p>
            <p><strong>Latitude:</strong> <?php echo $report['latitude']; ?></p>
            <p><strong>Longitude:</strong> <?php echo $report['longitude']; ?></p>
            <p><strong>Status:</strong> <?php echo $report['status']; ?></p>
            <p><strong>Urgency Level:</strong> <?php echo $report['urgency_level']; ?></p>
            <p><strong>Date Submitted:</strong> <?php echo $report['created_at']; ?></p>
            <p><strong>Anonymous:</strong> <?php echo $report['anonymous'] ? 'Yes' : 'No'; ?></p>


        </div>
        <!-- Display User Details if not Anonymous -->
        <?php if ($report['anonymous'] == 0 && $user_details): ?>
        <div class="user-details">
            <h3>User Reporter Details</h3>
            <p><strong>Name:</strong> <?php echo $user_details['name']; ?></p>
            <p><strong>Email:</strong> <?php echo $user_details['email']; ?></p>
            <p><strong>Contact Number:</strong> <?php echo $user_details['contact_number']; ?></p>
            <p><strong>Address:</strong> <?php echo $user_details['address']; ?></p>
        </div>
        <?php endif; ?>

        <form action="" method="POST">
            <label for="officer_id">Select Officer:</label>
            <select name="officer_id" required>
                <option value="">Select an Officer</option>
                <?php
                // Display the list of active or on-duty officers
                while ($row = $result->fetch_assoc()) {
                    echo "<option value='" . $row['id'] . "'>" . $row['first_name'] . " " . $row['last_name'] . "</option>";
                }
                ?>
            </select>
            <br><br>
            <input type="submit" value="Assign Officer">
        </form>

        <br>
        <a href="view_reports.php" class="btn">Back to Reports</a> <!-- Link back to the reports page -->
    </div>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>