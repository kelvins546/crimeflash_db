<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "crimeflash_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX request to fetch officers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_officers') {
    $station = $_POST['station'];
    $officers_query = "SELECT id, badge_number, last_name, first_name, rank FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
    $stmt = $conn->prepare($officers_query);
    $stmt->bind_param("s", $station);
    $stmt->execute();
    $result = $stmt->get_result();
    $officers = [];
    while ($row = $result->fetch_assoc()) {
        $officers[] = $row;
    }
    echo json_encode($officers);
    exit();
}

// Initialize variables
$report = [];
$user_details = null;
$assignment_success = false;
$error_message = '';

// Fetch report details
if (isset($_GET['report_id'])) {
    $report_id = $_GET['report_id'];
    $report_query = "SELECT * FROM crime_reports WHERE id = ?";
    $stmt = $conn->prepare($report_query);
    $stmt->bind_param("i", $report_id);
    $stmt->execute();
    $report_result = $stmt->get_result();
    if ($report_result->num_rows > 0) {
        $report = $report_result->fetch_assoc();
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
    } else {
        die("Report not found.");
    }
} else {
    die("No report ID provided.");
}

// Handle form submission to assign officers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $station = $_POST['station'];
    $assign_all = isset($_POST['assign_all']);
    $officer_id = $_POST['officer'] ?? null;

    if ($station && isset($_GET['report_id'])) {
        $report_id = $_GET['report_id'];

        if ($assign_all) {
            // Fetch all available officers at the selected station
            $officers_query = "SELECT id FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
            $stmt = $conn->prepare($officers_query);
            $stmt->bind_param("s", $station);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $officer_ids = [];
                while ($row = $result->fetch_assoc()) {
                    $officer_ids[] = $row['id'];
                }
                // Convert officer IDs array to JSON
                $assigned_officer_ids = json_encode($officer_ids);
                // Update the crime report with the assigned officer IDs
                $assign_query = "UPDATE crime_reports SET assigned_officer_ids = ? WHERE id = ?";
                $stmt = $conn->prepare($assign_query);
                $stmt->bind_param("si", $assigned_officer_ids, $report_id);
                if ($stmt->execute()) {
                    $assignment_success = true;
                } else {
                    $error_message = "Error assigning officers.";
                }
            } else {
                $error_message = "No officers available at the selected station.";
            }
        } elseif ($officer_id) {
            // Assign to a specific officer
            $assigned_officer_ids = json_encode([$officer_id]);
            $assign_query = "UPDATE crime_reports SET assigned_officer_ids = ? WHERE id = ?";
            $stmt = $conn->prepare($assign_query);
            $stmt->bind_param("si", $assigned_officer_ids, $report_id);
            if ($stmt->execute()) {
                $assignment_success = true;
            } else {
                $error_message = "Error assigning officer.";
            }
        } else {
            $error_message = "Please select an officer or choose to assign to all.";
        }
    } else {
        $error_message = "Please select a station and ensure a valid report ID is provided.";
    }
}


// Fetch stations for the dropdown
$stations_query = "SELECT DISTINCT station FROM officer_profiles WHERE status IN ('active', 'on-duty')";
$stations_result = $conn->query($stations_query);
$stations = [];
if ($stations_result->num_rows > 0) {
    while ($row = $stations_result->fetch_assoc()) {
        $stations[] = $row['station'];
    }
}

// Handle AJAX request to fetch officers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fetch_officers') {
    $station = $_POST['station'];
    $officers_query = "SELECT id, badge_number, last_name, first_name, rank FROM officer_profiles WHERE status IN ('active', 'on-duty') AND station = ?";
    $stmt = $conn->prepare($officers_query);
    $stmt->bind_param("s", $station);
    $stmt->execute();
    $result = $stmt->get_result();
    $officers = [];
    while ($row = $result->fetch_assoc()) {
        $officers[] = $row;
    }
    echo json_encode($officers);
    exit(); // Terminate script execution after handling AJAX request
}


?>



<!DOCTYPE html>
<html lang="en">




<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Officer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="styles.css"> <!-- Link to your custom CSS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <Script>
    $(document).ready(function() {
        $('#station').change(function() {
            var station = $(this).val();
            if (station) {
                $.ajax({
                    type: 'POST',
                    url: '', // Targets the same PHP file
                    data: {
                        action: 'fetch_officers',
                        station: station
                    },
                    success: function(response) {
                        var officers = JSON.parse(response);
                        $('#officer').empty().append(
                            '<option value="">--Select Officer--</option>');
                        if (officers.length > 0) {
                            $.each(officers, function(index, officer) {
                                $('#officer').append(
                                    $('<option></option>').val(officer.id).text(
                                        officer.rank + ' ' + officer
                                        .first_name + ' ' + officer.last_name +
                                        ' (Badge No: ' + officer.badge_number +
                                        ')'
                                    )
                                );
                            });
                        } else {
                            $('#officer').append(
                                '<option value="">No officers available</option>');
                        }
                    },
                    error: function() {
                        alert('Error fetching officers.');
                    }
                });
            } else {
                $('#officer').empty().append('<option value="">--Select Officer--</option>');
            }
        });
    });
    </Script>

    <style>
    * {
        padding: 0;
        margin: 0;
        box-sizing: border-box
    }

    body {
        font-family: "Poppins";
        width: 100%;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;

    }

    .container-report {
        box-shadow: 10px 10px 15px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        width: 600px;
        height: 700px;
        padding: 25px;
    }


    .container-report h2 {
        font-size: 2.1rem;
        font-weight: bold;
        text-align: center;
        color: #EDB926;
        margin-bottom: 20px;
    }

    .container h3 {
        text-align: center;
        font-size: 1.2rem;
        color: #EDB926;
        font-weight: bold;
        margin-bottom: 30px;
    }


    .report-details {
        font-size: 1rem;
        display: flex;
        align-items: center;
        flex-direction: row;
        margin-bottom: 20px;

    }

    .report-details h3 {
        text-align: center;
        font-size: 1.2rem;
        margin-bottom: 30px;
    }

    .report-details .left-text {
        margin-right: 40px;
    }

    label {
        font-size: 1.1rem;
        font-weight: bold;
    }

    input {
        padding: 15px;
        background-color: #EDB926;
        color: black;
        border: none;
        border-radius: 5px;
        cursor: pointer;


    }

    .input-drop {
        display: flex;
        margin-bottom: 5px;
    }
    </style>
</head>

<body>
    <div class="container-report">
        <h2>Assign Officer to Crime Report</h2>
        <div class="container">
            <!-- Report Details Section -->
            <h3>Report Details</h3>
            <div class="report-details">
                <div class="left-text">
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($report['description']); ?></u></p>
                    <p><strong>Location:</strong><u><?php echo htmlspecialchars($report['location']); ?></u></p>
                    <p><strong>Latitude:</strong><u><?php echo htmlspecialchars($report['latitude']); ?></u></p>
                    <p><strong>Longitude:</strong><u><?php echo htmlspecialchars($report['longitude']); ?></u></p>
                </div>
                <div class="right-text">
                    <p><strong>Status:</strong><u><?php echo htmlspecialchars($report['status']); ?></u></p>
                    <p><strong>Urgency Level:</strong><u><?php echo htmlspecialchars($report['urgency_level']); ?></u>
                    </p>
                    <p><strong>Date Submitted:</strong><u> <?php echo htmlspecialchars($report['created_at']); ?></u>
                    </p>
                    <p><strong>Anonymous:</strong> <?php echo $report['anonymous'] ? 'Yes' : 'No'; ?></p>
                </div>
            </div>

            <!-- User Details Section (Displayed if not Anonymous) -->
            <?php if ($report['anonymous'] == 0 && $user_details): ?>
            <div class="user-details">
                <h3>User Reporter Details</h3>
                <p><strong>Name:</strong> <u><?php echo htmlspecialchars($user_details['name']); ?></u></p>
                <p><strong>Email:</strong><u> <?php echo htmlspecialchars($user_details['email']); ?></u></p>
                <p><strong>Contact Number:</strong><u>
                        <?php echo htmlspecialchars($user_details['contact_number']); ?></u></p>
                <p><strong>Address:</strong> <u><?php echo htmlspecialchars($user_details['address']); ?></u></p>
            </div>
            <?php endif; ?>

            <h2>Assign Officer to Crime Report</h2>
            <form method="post"
                action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?report_id=' . htmlspecialchars($report_id); ?>">
                <!-- Station Selection Dropdown -->
                <div class="input-drop">
                    <label for="station">Select Station:</label>
                    <select id="station" name="station">
                        <option value="">--Select Station--</option>
                        <?php foreach ($stations as $station): ?>
                        <option value="<?php echo htmlspecialchars($station); ?>">
                            <?php echo htmlspecialchars($station); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Officers Selection Dropdown -->
                <label for="officer">Select Officer:</label>
                <select id="officer" name="officer">
                    <option value="">--Select Officer--</option>
                    <!-- Officers will be populated here based on the selected station -->
                </select>

                <br><br>

                <!-- Assign to All Officers Checkbox -->
                <label for="assign_all">Assign to All Officers in Selected Station:</label>
                <input type="checkbox" name="assign_all" id="assign_all">
                <br><br>

                <!-- Submit Button -->
                <input type="submit" name="assign" value="Assign Officer(s)">
            </form>

            <?php if ($assignment_success): ?>
            <p style="color: green;">Officer assigned successfully.</p>
            <?php elseif (!empty($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
        </div>
</body>

</html>