<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to login page if not logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password_db = "";
$dbname = "crimeflash_db";

$conn = new mysqli($servername, $username, $password_db, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$description = $location = $urgency_level = "";
$latitude = $longitude = $media_path = "";
$anonymous = 0;
$crime_type = null;  // Set initial value for crime type as null (optional field)
$success_message = $error_message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $description = isset($_POST['description']) ? $conn->real_escape_string(trim($_POST['description'])) : "";
    $location = isset($_POST['location']) ? $conn->real_escape_string(trim($_POST['location'])) : "";

    $urgency_level = isset($_POST['urgency_level']) ? intval($_POST['urgency_level']) : 0;
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
    $crime_type = isset($_POST['crime_type']) ? intval($_POST['crime_type']) : null; // crime_type is now optional
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;

    // Validate required fields (remove crime_type from validation)
    if (empty($description) || empty($location) || empty($urgency_level)) {
        $error_message = "Description, Location, and Urgency Level are required.";
    }

    // Handle file upload
    if (!empty($_FILES['media']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
        $media_tmp_name = $_FILES['media']['tmp_name'];
        $media_name = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['media']['name']));
        $media_type = $_FILES['media']['type'];
        $media_dir = 'uploads/';

        if (!is_dir($media_dir)) {
            mkdir($media_dir, 0777, true);
        }

        if (in_array($media_type, $allowed_types)) {
            $media_path = $media_dir . $media_name;
            if (!move_uploaded_file($media_tmp_name, $media_path)) {
                $error_message = "Error uploading media file.";
            }
        } else {
            $error_message = "Invalid file type. Allowed: JPG, PNG, GIF, MP4.";
        }
    }

    // If no errors, insert into database
    if (empty($error_message)) {
        $stmt = $conn->prepare("INSERT INTO crime_reports (user_id, crime_type_id, description, location, urgency_level, latitude, longitude, media_path, anonymous) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        // Bind crime_type as nullable (using NULL for optional field)
        $stmt->bind_param("iissiddsi", $_SESSION['user_id'], $crime_type, $description, $location, $urgency_level, $latitude, $longitude, $media_path, $anonymous);

        if ($stmt->execute()) {
            $success_message = "Crime report submitted successfully!";
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Crime Report</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f4f4f4;
    }

    .container {
        width: 50%;
        margin: 50px auto;
        padding: 20px;
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .form-group textarea {
        height: 150px;
    }

    button {
        width: 100%;
        padding: 10px;
        background-color: #28a745;
        color: #fff;
        border: none;
        border-radius: 4px;
        font-size: 16px;
    }

    .error {
        color: red;
        font-size: 14px;
    }

    .success {
        color: green;
        font-size: 14px;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2>Submit Crime Report</h2>

        <!-- Display error or success message -->
        <?php if (!empty($error_message)) : ?>
        <div class="error"><?php echo $error_message; ?></div>
        <?php elseif (!empty($success_message)) : ?>
        <div class="success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="media">Upload Media:</label>
                <!-- Add onchange to trigger previewMedia function -->
                <input type="file" id="media" name="media" onchange="previewMedia()">
            </div>


            <div class="form-group">
                <label for="crime_type">Crime Type:</label>
                <select id="crime_type" name="crime_type" required>
                    <option value="">Select Crime Type</option>
                    <option value="1">Theft</option>
                    <option value="2">Assault</option>
                    <option value="3">Vandalism</option>
                    <!-- Add other crime types as needed -->
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>

            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" required>
            </div>

            <div class="form-group">
                <label for="latitude">Latitude:</label>
                <input type="text" id="latitude" name="latitude" readonly>
            </div>

            <div class="form-group">
                <label for="longitude">Longitude:</label>
                <input type="text" id="longitude" name="longitude" readonly>
            </div>

            <div class="form-group">
                <button type="button" onclick="getLocation()">Use My Location</button>
            </div>

            <div class="form-group">
                <label for="urgency_level">Urgency Level:</label>
                <select id="urgency_level" name="urgency_level" required>
                    <option value="1">Low</option>
                    <option value="2">Medium</option>
                    <option value="3">High</option>
                </select>
            </div>

            <div class="form-group">
                <label for="anonymous">Submit Anonymously:</label>
                <input type="checkbox" id="anonymous" name="anonymous" value="1">
            </div>



            <div class="form-group">
                <button type="submit">Submit Report</button>
            </div>
        </form>
    </div>

    <script>
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                document.getElementById('latitude').value = position.coords.latitude;
                document.getElementById('longitude').value = position.coords.longitude;
            }, function() {
                alert("Geolocation service failed.");
            });
        } else {
            alert("Your browser does not support geolocation.");
        }
    }



    function previewMedia() {
        var file = document.getElementById("media").files[0];
        var previewContainer = document.getElementById("mediaPreviewContainer");

        // Clear any previous previews
        previewContainer.innerHTML = "";

        if (file) {
            var reader = new FileReader();

            // If the file is an image
            if (file.type.startsWith('image')) {
                reader.onload = function(e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.maxWidth = "100%";
                    img.style.maxHeight = "300px";
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
            // If the file is a video
            else if (file.type.startsWith('video')) {
                var video = document.createElement('video');
                video.controls = true;
                video.style.maxWidth = "100%";
                video.style.maxHeight = "300px";
                var source = document.createElement('source');
                source.src = URL.createObjectURL(file);
                source.type = file.type;
                video.appendChild(source);
                previewContainer.appendChild(video);
            } else {
                previewContainer.innerHTML = "<p>Preview not available for this file type.</p>";
            }
        }
    }
    </script>
</body>

</html>