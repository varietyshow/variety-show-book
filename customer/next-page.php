<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['first_name'])) {
    header("Location: customer-loginpage.php");
    exit();
}


include 'db_connect.php'; // Include database connection

$first_name = htmlspecialchars($_SESSION['first_name']); // Sanitize first_name

// Get entertainer_id from the URL
$entertainer_id = isset($_GET['entertainer_id']) ? (int)$_GET['entertainer_id'] : 0;

// Get current month and year, or use GET parameters to navigate between months
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Calculate the previous and next months
$prevMonth = $month - 1;
$prevYear = $year;
$nextMonth = $month + 1;
$nextYear = $year;

if ($prevMonth == 0) {
    $prevMonth = 12;
    $prevYear--;
}
if ($nextMonth == 13) {
    $nextMonth = 1;
    $nextYear++;
}

// Fetch entertainer's schedule from the database
$sql = "SELECT date, status FROM sched_time WHERE entertainer_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $entertainer_id, $month, $year);
$stmt->execute();
$result = $stmt->get_result();

// Create an associative array to store schedule data (date => status)
$schedule = [];
while ($row = $result->fetch_assoc()) {
    $schedule[$row['date']] = $row['status'];
}

$stmt->close();

// Fetch entertainer's details from the database
$entertainerSql = "SELECT title FROM entertainer_account WHERE entertainer_id = ?";
$entertainerStmt = $conn->prepare($entertainerSql);
$entertainerStmt->bind_param("i", $entertainer_id);
$entertainerStmt->execute();
$entertainerStmt->bind_result($entertainer_name);
$entertainerStmt->fetch();
$entertainerStmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Dashboard</title>
    <link rel="stylesheet" href="style2.css">
    <style>
        /* Modal container */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Black background with opacity */
        }

        /* Modal content */
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
            text-align: center;
        }

        /* Close button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr); 
            gap: 10px;
            margin-top: 20px;
        }
        .day {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: center;
            position: relative;
            height: 100px; 
        }
        .available button {
            background-color: green;
            color: white;
            border: none;
            padding: 15px;
            cursor: pointer;
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
        }
        .booked {
            background-color: blue;
            color: white;
        }
        .unavailable {
            background-color: red;
            color: white;
        }
        .header {
            grid-column: span 7;
            font-weight: bold;
            text-align: center;
            background-color: #f0f0f0;
        }
        .month-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }
        .month-navigation button {
            background-color: green;
            color: white;
            padding: 10px;
            border: none;
            cursor: pointer;
        }
        .days-of-week {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<style>
    /* Modal container */
.modal {
    display: none; /* Hidden by default */
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Black background with opacity */
}

/* Modal content */
.modal-content {
    background-color: white;
    margin: 5% auto; /* Adjust this value to move it higher */
    padding: 20px;
    border-radius: 10px;
    width: 50%;
    text-align: center;
    max-height: 80vh; /* Set max height */
    overflow-y: auto; /* Enable scrolling */
}

.input-container {
        position: relative;
        margin-bottom: 20px;
    }

    .hover-input {
    width: 90%;
    padding: 10px;
    border: none; /* Remove the border */
    border-bottom: 2px solid #ccc; /* Add a bottom border */
    border-radius: 0; /* No rounding on corners */
    transition: border-color 0.3s, box-shadow 0.3s;
    font-size: 16px;
}

    .hover-input::placeholder {
        color: transparent; /* Hide placeholder text */
    }

    .input-container label {
        position: absolute;
        top: 10px;
        left: 10px;
        color: #999;
        font-size: 16px;
        transition: 0.2s ease all;
        pointer-events: none; /* Prevents clicking the label */
        padding: 0 5px; /* Add some padding to the label */
    }

    .hover-input:focus + label,
    .hover-input:not(:placeholder-shown) + label {
        top: -10px; /* Move label up */
        left: 10px;
        color: black; /* Change label color */
        font-size: 12px; /* Make label smaller */
    }

    .hover-input:focus {
    border-bottom: 2px solid black; /* Change bottom border color on focus */
    box-shadow: none; /* Remove shadow if not needed */
    outline: none; /* Remove default outline */
}

h4 {
    margin-top: 20px; /* Add space below the h4 */
}

hr {
    margin-bottom: 20px; /* Add space below the hr */
}

#priceRange, #priceOffer, #timeStart, #timeEnd {
    text-align: center;
}

</style>
<body>
    <header>
        <div class="logo">Logo</div>
        <nav>
            <div class="dropdown" id="dropdown">
                <button class="dropbtn" onclick="toggleDropdown()">
                    <img src="../images/sample.jpg" alt="Profile">
                </button>
                <div class="dropdown-content" id="dropdown-content">
                    <a href="customer-profile.php">View Profile</a>
                    <a href="customer-booking.php">Book Appointment</a>
                    <a href="customer-appointment.php">My Appointment</a>
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="welcome-message">
            <h1>Welcome, <?php echo $first_name; ?>!</h1>
            <p>We’re glad to have you here. Let’s get started!</p>
        </section>

        <section class="calendar-container">
            <div class="month-navigation">
                <button onclick="window.location.href='?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>&entertainer_id=<?php echo $entertainer_id; ?>'">&lt;</button>
                <h2><?php echo date('F', mktime(0, 0, 0, $month, 10)) . " " . $year; ?></h2>
                <button onclick="window.location.href='?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>&entertainer_id=<?php echo $entertainer_id; ?>'">&gt;</button>
            </div>

            <div class="days-of-week">
                <div>Sun</div>
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div>Sat</div>
            </div>

            <div class="calendar">
                <?php
                $firstDayOfMonth = strtotime("$year-$month-01");
                $daysInMonth = date('t', $firstDayOfMonth);
                $startDay = date('w', $firstDayOfMonth);

                // Empty cells before the first day of the month
                for ($i = 0; $i < $startDay; $i++) {
                    echo "<div class='day'></div>";
                }

                // Get today's date
                $today = date('Y-m-d');

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $currentDate = date('Y-m-d', strtotime("$year-$month-$day"));
                    $statusClass = '';

                    if (isset($schedule[$currentDate])) {
                        $status = $schedule[$currentDate];
                        if ($status == 'Available') {
                            $statusClass = 'available';
                        } elseif ($status == 'Booked') {
                            $statusClass = 'booked';
                        } else {
                            $statusClass = 'unavailable';
                        }
                    }

                    echo "<div class='day $statusClass'><span>$day</span>";

                    // Add "Book Now" button only if the day is available and today or in the future
                    if ($statusClass == 'available' && $currentDate >= $today) {
                        echo "<button onclick=\"openModal('$currentDate', $entertainer_id)\">Book Now</button>";
                    }

                    echo "</div>";
                }

                // Empty cells after the last day of the month
                $totalCells = $startDay + $daysInMonth;
                $remainingCells = 42 - $totalCells;

                for ($i = 0; $i < $remainingCells; $i++) {
                    echo "<div class='day'></div>";
                }
                ?>
            </div>
        </section>
    </main>

    <!-- Modal structure -->
    <div id="bookingModal" class="modal">
        <div class="modal-content" style="display: flex; flex-direction: column; align-items: center; width: 400px; padding: 20px; position: relative;">
            <span class="close" style="position: absolute; top: 10px; right: 10px; cursor: pointer;">&times;</span>
            <h2>Book Appointment</h2>
            <p id="modalDate"></p>
            
            <input type="hidden" id="bookingDate" value="">

            <!-- Sample content inside the modal -->
            <div class="entertainer-info" style="display: flex; flex-direction: column; align-items: center; width: 100%;">
                <h3>Entertainer: <?php echo htmlspecialchars($entertainer_name); ?></h3>
                <p style="font-size: 12px;">Please fill in the appointment details:</p>

                <h4 style="margin-top: 20px;">Customer Information</h4>
                <hr style="border: 1px solid black; width: 60%; margin-top: -25px;">

                <div class="input-container">
                    <input type="text" id="customerName" class="hover-input" placeholder=" " required>
                    <label for="customerName">Customer Name</label>
                </div>
                <div class="input-container">
                    <input type="text" id="contactNumber" class="hover-input" placeholder=" " required>
                    <label for="contactNumber">Contact Number</label>
                </div>
                <div class="input-container">
                    <input type="email" id="email" class="hover-input" placeholder=" " >
                    <label for="email">Email (Optional)</label>
                </div>

                <h4 style="margin-top: -10px;">Venue Address</h4>
                <hr style="border: 1px solid black; width: 60%; margin-top: -25px;">

                <div class="input-container">
                    <input type="text" id="street" class="hover-input" placeholder=" " required>
                    <label for="street">Street</label>
                </div>
                <div class="input-container">
                    <input type="text" id="barangay" class="hover-input" placeholder=" " required>
                    <label for="barangay">Barangay</label>
                </div>
                <div class="input-container">
                    <input type="text" id="municipality" class="hover-input" placeholder=" " required>
                    <label for="municipality">Municipality</label>
                </div>
                <div class="input-container">
                    <input type="text" id="province" class="hover-input" placeholder=" " required>
                    <label for="province">Province</label>
                </div>

                <h4 style="margin-top: -10px;">Booking Details</h4>
                <hr style="border: 1px solid black; width: 60%; margin-top: -25px;">
                <label for="timeStart">Time Start:</label>
                <input type="time" id="timeStart" onchange="updatePriceRange()" style="width: 200px;" required>

                <label for="timeEnd">Time End:</label>
                <input type="time" id="timeEnd" onchange="updatePriceRange()" style="width: 200px;" required>


                <label for="priceRange">Estimated Price Range:</label>
                <input type="text" id="priceRange" value="₱0.00 - ₱0.00" style="width: 200px;" required readonly>

                <label for="priceOffer">Price Offer:</label>
                <input type="text" id="priceOffer" placeholder="Enter your offer" style="width: 200px;" required>
            </div>
            <button onclick="confirmBooking()" style="margin-top: 20px;">Confirm Booking</button>
        </div>
    </div>

    <script>
        function formatPrice(price) {
            return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        function updatePriceRange() {
    const timeStart = document.getElementById("timeStart").value;
    const timeEnd = document.getElementById("timeEnd").value;

    if (timeStart && timeEnd) {
        const start = new Date(`1970-01-01T${timeStart}:00`);
        const end = new Date(`1970-01-01T${timeEnd}:00`);

        if (end > start) {
            const hours = (end - start) / (1000 * 60 * 60);
            const estimatedMin = formatPrice((hours * 2500).toFixed(2));
            const estimatedMax = formatPrice((hours * 5000).toFixed(2));

            document.getElementById("priceRange").value = `₱${estimatedMin} - ₱${estimatedMax}`;
        } else {
            document.getElementById("priceRange").value = "₱0.00 - ₱0.00";
        }
    } else {
        document.getElementById("priceRange").value = "₱0.00 - ₱0.00";
    }
}


function confirmBooking() {
    const customerName = document.getElementById("customerName").value.trim();
    const contactNumber = document.getElementById("contactNumber").value.trim();
    const email = document.getElementById("email").value.trim();
    const street = document.getElementById("street").value.trim();
    const barangay = document.getElementById("barangay").value.trim();
    const municipality = document.getElementById("municipality").value.trim();
    const province = document.getElementById("province").value.trim();
    const timeStart = document.getElementById("timeStart").value; // e.g. '10:00'
    const timeEnd = document.getElementById("timeEnd").value; // e.g. '10:30'
    const priceOffer = document.getElementById("priceOffer").value.trim();
    const entertainerId = <?php echo $entertainer_id; ?>; 
    const bookingDate = document.getElementById("bookingDate").value;

    if (!customerName || !contactNumber || !street || !barangay || !municipality || !province || !timeStart || !timeEnd) {
        alert("Please fill in all required fields.");
        return;
    }

    const startTime = timeStart.split(":").map(Number);
    const endTime = timeEnd.split(":").map(Number);

    // Ensure that timeEnd is greater than timeStart
    if (endTime[0] < startTime[0] || (endTime[0] === startTime[0] && endTime[1] <= startTime[1])) {
        alert("Time End must be greater than Time Start.");
        return;
    }

    // Function to format time to "Hour:minutes AM/PM"
    function formatTime(time) {
        const [hours, minutes] = time.split(':');
        const period = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = (hours % 12) || 12; // Convert to 12-hour format
        return `${formattedHours}:${minutes} ${period}`;
    }

    const formattedTimeStart = formatTime(timeStart);
    const formattedTimeEnd = formatTime(timeEnd);

    const data = {
        customerName,
        contactNumber,
        email,
        street,
        barangay,
        municipality,
        province,
        timeStart: formattedTimeStart,
        timeEnd: formattedTimeEnd,
        priceOffer,
        entertainerId,
        bookingDate
    };

    fetch('confirm_booking.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert("Booking confirmed!");
            document.getElementById("bookingModal").style.display = "none";
        } else {
            alert(data.message);
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert("An error occurred.");
    });
}




        function toggleDropdown() {
            const dropdown = document.getElementById('dropdown');
            const dropdownContent = document.getElementById('dropdown-content');
            if (dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
            } else {
                const openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function (openDropdown) {
                    openDropdown.classList.remove('show');
                });
                dropdown.classList.add('show');
            }
        }

        window.onclick = function(event) {
            if (!event.target.matches('.dropbtn') && !event.target.matches('.dropbtn img')) {
                const openDropdowns = document.querySelectorAll('.dropdown.show');
                openDropdowns.forEach(function (openDropdown) {
                    openDropdown.classList.remove('show');
                });
            }
        }

        // Function to open the modal and set date
        function openModal(date, entertainerId) {
    const modal = document.getElementById("bookingModal");
    document.getElementById("modalDate").innerText = "You are booking for " + date;
    modal.style.display = "block";
    // Store the date in a hidden input field or use a variable to send with fetch
    document.getElementById("bookingDate").value = date; // Assuming you create a hidden input in the modal
}

        // Function to close the modal
        document.querySelector(".close").onclick = function() {
            document.getElementById("bookingModal").style.display = "none";
        }

        // Close the modal if user clicks outside of it
        window.onclick = function(event) {
            const modal = document.getElementById("bookingModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
