<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/bac1e4ca00.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
 <style>
        body {
            background-color: #f8f9fa;
            font-family: "Poppins",sans-serif;
        }
        .navbar {
            background-color: #f4a623;
        }
        .icon-container{
            display: flex;
            color: black;
            align-items: center;
            padding: 15px;
        }
        .icon-container  i{
         color: black;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #605c5c;
            position: fixed;
            left: -250px;
            top: 0;
            transition: 0.3s ease-in-out;
            padding-top: 20px;
            z-index: 1000;
        }
        .sidebar.active {
            left: 0;
        }
        .sidebar .profile {
            text-align: center;
            padding: 20px;
            color: white;
        }
        .sidebar .profile img {
            width: 80px;
            border-radius: 50%;
            margin-bottom: 20px;
        }
        .sidebar .menu-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar .menu-items li {
            padding: 10px 20px;
            text-decoration: none;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .sidebar .menu-items li a{
            padding: 10px 20px;
            color: white;
            text-decoration: none;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .sidebar .menu-items li i {
            margin-right: 10px;
            color: #f4a623;
        }
        .sidebar .menu-items li:hover {
            background-color: #444;
        }
        .logout {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: white;
            cursor: pointer;
        }
        .logout i {
            margin-right: 5px;
        }
        .report-container {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
        }
        .report-container p{
            margin-bottom: 5px;
        }
        .report-container .btn{
            font-size: 1.1rem;
            width: 150px;
            
        }
        .status {
            font-style: italic;
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 20px;
            color: white;
            cursor: pointer;
        }
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }
        .overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- navbar -->
    <nav class="navbar navbar-expand-lg">
    <div class="container-fluid icon-container">
        <button id="menu-toggle" class="btn text-white  "><i class="fa-solid fa-bars"></i></button>
        <span class="navbar-brand mx-auto fw-bold">Reports</span>
        <a href="../police/policereport.html    ">
        <span class="navbar-brand text-white"> 
            <i class="fa-solid fa-bell"></i>
        </span>
        </a>
    </div>
</nav>
    <!-- sidebar -->
<div id="sidebar" class="sidebar">
    <span class="close-btn" id="close-sidebar">&times;</span> 
    <div class="profile">
        <img src="../images/policeprofile.png" alt="User Profile"> 
        <h5>SPO3 Fred Samson</h5>
       
        <p>Northern Police District LNPD<br>Senior Police Officer</p>
        <span class="badge bg-success">Active</span>
    </div>
    <ul class="menu-items">
        <li><a href=""><i class="fa-solid fa-folder"></i> ASSIGNED CASES</a></li>
        <li><a href="../police/policeprofile.html"><i class="fa-solid fa-user"></i> PROFILE</a></li>
    </ul>
    <div class="logout">
        <i class="fa-solid fa-sign-out-alt"></i> Logout
    </div>
</div>
<!-- content -->
    <div class="container mt-2">
        <div class="report-container">
            <h5 class="fw-bold text-center">Phase 1, Simbahan, Bagong Silang, Caloocan City</h5>
            <hr>
            <img src="../images/image 7.png" alt="Map" class="img-fluid rounded mb-2">
            <p><strong>Description:</strong> May nakita po akong nakahandusay na tao sa kalsada na puno ng dugo sa harap ng Simbahan</p>
            <p><strong>Name:</strong> Gian Felipe</p>
            <p><strong>Contact:</strong> 09123456789</p>
            <p><em>Time Reported:</em> 15:30</p>
            <p class="status text-danger"><em>Station Status: Suspicious</em></p>
            <button id="caseSolvedBtn" class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#caseSolvedModal">
                Case Solved
            </button><br>
            <img src="../images/image (1).png" alt="Crime Scene" class="img-fluid mt-3 rounded">
        </div>
      
    </div>
    <!-- Confirmation Modal -->
    <div class="modal fade" id="caseSolvedModal" tabindex="-1" aria-labelledby="caseSolvedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-3">
            <div class="modal-body">
                <i class="fa-solid fa-triangle-exclamation fa-3x text-danger"></i>
                <h5 class="fw-bold mt-3">Are you sure the case is solved?</h5>
                <div class="mt-3">
                    <button id="confirmCaseSolved" class="btn btn-success">Yes, Confirm</button>
                    <button class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Resolution Summary Modal -->
<div class="modal fade" id="resolutionModal" tabindex="-1" aria-labelledby="resolutionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="fw-bold">Resolution Summary (Required)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="fw-bold">What was the issue? What actions were taken? What was the outcome?</label>
                <textarea class="form-control mt-2" rows="3" placeholder="Additional Notes (if any)"></textarea>

                <label class="fw-bold mt-3">Attach Files here.</label>
                <input type="file" class="form-control">

                <label class="fw-bold mt-3">Date & Time of Resolution</label>
                <div class="d-flex">
                    <input type="date" class="form-control me-2">
                    <input type="time" class="form-control">
                </div>

                <div class="form-check mt-3">
                    <input type="checkbox" class="form-check-input" id="confirmCheck">
                    <label for="confirmCheck" class="form-check-label">
                        I confirm that all the provided information is true and accurate to the best of my knowledge.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success w-100">SUBMIT</button>
            </div>
        </div>
    </div>
</div>
    <script>
        // Sidenav fuNCTION//
        document.getElementById("menu-toggle").addEventListener("click", function() {
            document.getElementById("sidebar").classList.add("active");
            document.getElementById("overlay").classList.add("active");
        });
    
        document.getElementById("close-sidebar").addEventListener("click", function() {
            document.getElementById("sidebar").classList.remove("active");
            document.getElementById("overlay").classList.remove("active");
        });

    document.getElementById("confirmCaseSolved").addEventListener("click", function () {
        // Hide the first modal
        var caseSolvedModal = bootstrap.Modal.getInstance(document.getElementById('caseSolvedModal'));
        caseSolvedModal.hide();

        // Show the Resolution Summary modal
        var resolutionModal = new bootstrap.Modal(document.getElementById('resolutionModal'));
        resolutionModal.show();
    });
    </script>
</body>
</html>
