<?php
session_start();
include 'db_connect.php'; // Include the database connection

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: admin-loginpage.php"); // Redirect to login page if not logged in
    exit();
}

$first_name = htmlspecialchars($_SESSION['first_name']); // Retrieve and sanitize the first_name

// Fetch data from the database
$entertainers = [];
$items_per_page = isset($_GET['items-per-page']) ? intval($_GET['items-per-page']) : 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

$sql = "SELECT * FROM entertainer_account LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $entertainers[] = $row;
}
$stmt->close();

// Count total records for pagination
$total_result = $conn->query("SELECT COUNT(*) as total FROM entertainer_account");
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $items_per_page);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style1.css">
</head>
<style>
         body {
            overflow: auto; /* Only show scrollbar when necessary */
        }

        .content {
            overflow: hidden; /* Hide scrollbar initially */
        }

        /* Show scrollbar when content overflows */
        .content::-webkit-scrollbar {
            width: 0;
            height: 0;
        }

        .content {
            overflow-y: auto;
        }

            /* Schedule List Styles */
        .content {
            display: flex;
            justify-content: center; /* Center horizontally */
            align-items: flex-start; /* Align items at the top */
            margin-left: 100px;
            padding: 20px;
            padding-top: 100px; /* Give some space below the header */
            background-color: #f2f2f2;
            min-height: 100vh;
        }

        .schedule-container {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            width: 100%; /* Ensure it takes full width of the parent */
            max-width: 1200px; /* Set a maximum width for larger screens */
        }

        .search-container {
            display: flex;
            justify-content: space-between; /* Space between input and buttons */
            align-items: center; /* Align items vertically */
            margin-bottom: 20px;
            width: 100%;
        }

        .search-container input[type="text"] {
            
            margin-right: 20px; /* Space between input and buttons */
            padding: 10px;
        }

        .button-group {
            display: flex;
            gap: 10px; /* Space between buttons */
        }

        .button-group button {
            background: #f0f0f0;
            border: none;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .schedule-header {
            background-color: #fff;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 10px 10px 0 0;
        }

        .schedule-header h2 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .schedule-table th, .schedule-table td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            color: #333;
        }

        .schedule-table th {
            background-color: #f8f8f8;
            font-weight: normal;
        }

        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pagination select {
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .pagination span {
            color: #888;
        }

        .pagination-controls {
            display: flex;
            gap: 5px;
        }

        .pagination-controls img {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .header {
                width: 100%;
                left: 0;
            }

            .content {
                margin-left: 0;
                padding-top: 60px; /* Adjust padding for smaller screens */
                padding: 10px; /* Add padding to the content */
            }


            .pagination {
                flex-direction: row; /* Stack pagination controls vertically */
                align-items: flex-start;
            }

            .pagination-controls {
                width: 100%;
                justify-content: space-between; /* Space out controls */
            }
        }





</style>
<body>
    <header>
        <div class="logo">Logo</div>
        <nav>
            <div class="dropdown" id="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <img src="../images/sample.jpg" alt="Profile"> <!-- Replace with your image -->
                </button>
                <div class="dropdown-content" id="dropdown-content">
                    <a href="admin-profile.php">View Profile</a>
                    <a href="admin-entertainer.php">Entertainer List</a>
                    <a href="admin-customer.php">Customer List</a>
                    <a href="admin-appointments.php">Appointments</a>
                    <a href="logout.php">Logout</a> <!-- Logout link pointing to logout.php -->
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We’re glad to have you here. Let’s get started!</p>
        </section>

        <div class="content">
            <div class="schedule-container">
                <h2>Customer List</h2>

                <div class="search-container">
                    <input type="text" id="search-input" placeholder="Search By Firstname">
                    <div class="button-group">
                        <button class="refresh-btn" aria-label="Refresh" onclick="refreshList()">⟳</button>
                        <button class="add-btn" aria-label="Add" onclick="addEntertainer()">+</button>
                    </div>
                </div>

                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Firstname</th>
                            <th>Lastname</th>
                            <th>Address</th>
                            <th>Email </th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                </table>

                <div class="pagination">
                    <div>
                        <label for="items-per-page">Items per page:</label>
                        <select id="items-per-page" onchange="changeItemsPerPage(this.value)">
                            <option value="10" <?php echo $items_per_page == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $items_per_page == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="30" <?php echo $items_per_page == 30 ? 'selected' : ''; ?>>30</option>
                        </select>
                    </div>
                    <div class="page-controls">
                        <button <?php if ($page <= 1) echo 'disabled'; ?> onclick="changePage(<?php echo $page - 1; ?>)">◀</button>
                        <span id="pagination-info"><?php echo ($offset + 1) . '-' . min($offset + $items_per_page, $total_records) . ' of ' . $total_records; ?></span>
                        <button <?php if ($page >= $total_pages) echo 'disabled'; ?> onclick="changePage(<?php echo $page + 1; ?>)">▶</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            const dropdownContent = document.getElementById('dropdown-content');

            // Toggle the visibility of the dropdown content
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            } else {
                // Close any other open dropdowns
                const openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function (openDropdown) {
                    openDropdown.classList.remove('show');
                });

                dropdown.classList.add('show');
            }
        }

        // Close the dropdown if the user clicks outside of it
        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn') && !event.target.matches('.dropbtn img')) {
                const openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function (openDropdown) {
                    openDropdown.classList.remove('show');
                });
            }
        }

      


    </script>
</body>
</html>