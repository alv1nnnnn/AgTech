<?php
session_start(); // Start session to access session variables

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login.php");
    exit();
}

require_once '../Connection/connection.php';

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Prepare SQL query to retrieve profile picture URL and first name based on user_id
$sql = "SELECT profile, first_name, last_name FROM user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_picture, $first_name, $last_name);
$stmt->fetch();
$stmt->close();

// Generate avatar if no profile picture is found
if (empty($profile_picture)) {
    $initialAvatar = '<div class="avatar" style="width: 40px; height: 40px; background-color: #2D4A36; color: white; border-radius: 50%; text-align: center; line-height: 45px; font-size: 26px;">' . strtoupper(substr($first_name, 0, 1)) . '</div>';
    $profile_picture_html = $initialAvatar;
} else {
    $profile_picture_html = '<img src="' . $profile_picture . '" alt="Profile Picture" class="profile-pic" id="profilePic">';
}

// Query to count unread messages
$query = "SELECT COUNT(*) AS unread_count FROM chat WHERE receiver_id = ? AND read_status = 'unread'";
$stmtchatcount = $conn->prepare($query);
$stmtchatcount->bind_param("i", $user_id);
$stmtchatcount->execute();
$result = $stmtchatcount->get_result();
$row = $result->fetch_assoc();
$unread_count = $row['unread_count'];
$stmtchatcount->close();

// Check if start_date and end_date are set in URL
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Base SQL query to retrieve transactions for the logged-in user as the seller
$sql = "
SELECT 
    t.transaction_id, 
    t.status, 
    t.timestamp, 
    b.first_name AS buyer_first_name, 
    b.last_name AS buyer_last_name, 
    s.first_name AS seller_first_name, 
    s.last_name AS seller_last_name,
    td.product_id,
    td.quantity,
    td.amount,
    td.AgreedPrice,
    t.timestamp,
    p.product_name,
    pp.current_price,
    pperf.clicks_count,            -- Added click_count
    pperf.wishlist_count          -- Added wishlist_count
FROM 
    transaction t
INNER JOIN 
    user b ON t.buyer_id = b.user_id
INNER JOIN 
    user s ON t.seller_id = s.user_id
INNER JOIN 
    transaction_details td ON t.transaction_id = td.transaction_id
INNER JOIN 
    products p ON td.product_id = p.product_id
INNER JOIN 
    productprices pp ON p.product_id = pp.product_id
INNER JOIN
    product_performance pperf ON p.product_id = pperf.product_id   -- Join with product_performance to get clicks and wishlist counts
WHERE 
    t.status = 'completed' AND t.seller_id = ?
";

// Apply date range filter
if ($start_date && $end_date) {
    $sql .= " AND t.timestamp BETWEEN ? AND ?";
}

// Prepare and execute the statement
$stmt = $conn->prepare($sql);

// Bind parameters based on whether date range is set
if ($start_date && $end_date) {
    $stmt->bind_param("iss", $user_id, $start_date, $end_date);
} else {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Reports</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/farmer.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/report-mobile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/jquery/dist/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            color: #2D4A36;
        }

        .export-dropdown {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            align-content: flex-end;
            justify-content: flex-end;
            gap: 20px;
            margin-top: 20px;
        }

        .export-button {
            background-color: #2D4A36;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            height: 37px;
        }

        .report-dropdown select {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
             cursor: pointer;
        }


        .table-container {
            border: 1px solid #ccc;
            padding: 20px;
            background-color: #fff;
            margin-top: 20px;
            margin-left:40px;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th, .table-container td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }

        .table-container th {
            background-color: #2D4A36;
            color: #fff;
        }

        .home h1 {
            color: #2D4A36;
        }
        
    .hidden{
        display: none;
    }
.visible {
    display: none; /* Make it visible when required */
}

.chart-container{
    width: 50%;
    margin-left: 40px;
    margin-top: 20px;
    display: flex;
    gap: 20px
}

#insightsBarChart{
    width: 90% !important;
    height: 310px !important;
}
    </style>
</head>
<body>
<nav class="sidebar close">
    <header>
      <div class="image-text">
        <span class="image">
          <img src="../images/AgTech-Logo.png" alt="">
        </span>

        <div class="text logo-text">
          <span class="name">AgTech</span>
        </div>
      </div>

      <i class='bx bx-chevron-right toggle'></i>
    </header>

    <div class="menu-bar">
    <div class="menu">
        <ul class="menu-links">
            <li class="nav-link">
                <a href="Market.php">
                    <i class="bi bi-shop icon"></i>
                    <span class="text nav-text">All Products</span>
                </a>
            </li>
             <li class="nav-link">
                <a href="farmer-inbox.php" class="icon-container">
                    <i class="bi bi-chat-dots icon"></i>
                    <span class="text nav-text">Chats</span>
                </a>
            </li>
            <li class="nav-link">
                <a href="Wishlist.php">
                    <i class="bi bi-heart icon"></i>
                    <span class="text nav-text">Wishlist</span>
                </a>
            </li>
            <li class="nav-link" id="sellNavLink">
                <a href="#">
                    <i class="bi bi-tags icon"></i>
                    <span class="text nav-text">Sell</span>
                    <i class="bi-chevron-right icon"></i>
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="submenu-container" id="submenuContainer" style="display:none;">
    <div class="menu">
        <ul class="menu-links">
            <li class="nav-link">
                <a href="#" id="closeSubmenu">
                    <i class="bi bi-x icon"></i>
                    <span class="text nav-text">Close</span>
                </a>
            </li>
            <li class="nav-link">
                <a href="Farmer.php">
                    <i class='bx bx-tachometer icon'></i>
                    <span class="text nav-text">Seller Dashboard</span>
                </a>
            </li>
            <li class="nav-link">
                <a href="farmer-products.php">
                    <i class='bx bx-cart icon'></i>
                    <span class="text nav-text">Product List</span>
                </a>
            </li>
            <li class="nav-link">
                <a href="Price-Percentage.php">
                    <i class="bi bi-percent icon"></i>
                    <span class="text nav-text">Price Percentage</span>
                </a>
            </li>
            <li class="nav-link">
                <a href="farmer-reviews.php">
                    <i class="bi bi-list-stars icon"></i>
                    <span class="text nav-text">Reviews</span>
                </a>
            </li>
            <li class="nav-link">
                <a href="farmer-reports.php">
                    <i class="bi bi-file-earmark-bar-graph icon"></i>
                    <span class="text nav-text">Reports</span>
                </a>
            </li>
        </ul>
    </div>
</div>

    <div class="bottom-content">
        <li>
            <a href="#" id="logout-link">
                <i class='bx bx-log-out icon'></i>
                <span class="text nav-text">Logout</span>
            </a>
        </li>
    </div>
</nav>
<section class="home">
     <div id="notification" class="hidden">New Message!</div>
    <div class="nav-container">
    <h2>Reports</h2>
        <div class="profile-con" id="profileIcon">
            <?php echo $profile_picture_html; ?>
          <div class="dropdown-menu-custom" id="profileDropdown">
            <a href="#" class="dropdown-item-custom" id="open-profile">
                <div class="dropdown-btn">
                <i class="bi bi-person-circle profile-action"></i>
                <p>Profile</p>
                </div>
            </a>
            <a href="" class="dropdown-item-custom">
                <div class="dropdown-btn">
                <i class="bi bi-gear profile-action"></i>
                <p>Manage Password</p>
                </div>
            </a>
            <!-- Add some spacing between other menu items and logout button -->
            <div class="logout-separator"></div>
        </div>
</div>
    </div>
    <div class="main-container">
    <div class="export-dropdown">
    <input type="text" id="dateRangePicker" class="export-button" placeholder="Select Date Range" />
    
    <button id="exportCsvButton" class="export-button" onclick="exportData('csv')">Export as CSV</button>
    <button id="exportPdfButton" class="export-button" onclick="exportData('pdf')">Export as PDF</button>

<script>
    // Function to export the currently displayed report table to a CSV or PDF file
    function exportData(format) {
        const reportType = $('#report-type').val(); // Get the selected report type (sales or insights)
        
        if (!reportType || reportType === 'default') {
            alert("Please select a valid report type before exporting.");
            return;
        }

        // Assume that the logged-in user's ID is available in the session as user_id
        const userId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
        
        if (userId === null) {
            alert("You must be logged in to export data.");
            return;
        }

        // Get selected date range from the date picker
        const dateRange = $('#dateRangePicker').val();
        const [startDate, endDate] = dateRange ? dateRange.split(' - ') : [null, null];

        // Construct the URL with user ID, report type, format, and date range
        const url = `export.php?reportType=${reportType}&format=${format}&userId=${userId}&startDate=${startDate}&endDate=${endDate}`;
        
        // Redirect to export.php with the selected format, user ID, and date range
        window.location.href = url;
    }

    $(document).ready(function() {
        $('#dateRangePicker').daterangepicker({
            opens: 'left',
            autoUpdateInput: true, // Automatically updates the input field
            ranges: {
                'Today': [moment(), moment()],
                '7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                'Last 90 Days': [moment().subtract(89, 'days'), moment()],
            },
            locale: {
                format: 'YYYY-MM-DD'
            }
        });
    });
</script>

        <div class="report-dropdown">
            <select id="report-type">
                <option value="default">Select Report Type</option>
                <option value="sales">Sales</option>
                <option value="insights">Market Insights</option>
            </select>
        </div>
    </div>
    
    <div class="chart-container">
        <canvas id="salesBarChart"></canvas>
        <canvas id="insightsBarChart"></canvas> <!-- Market Insights Pie Chart -->
    </div>

    <div class="table-container" id="report-table-container"></div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function () {
    const salesCtx = document.getElementById('salesBarChart').getContext('2d');
    const insightsCtx = document.getElementById('insightsBarChart').getContext('2d');

    let salesBarChart, insightsBarChart;

    function updateSalesChart(data) {
    // Log the data to see if it's in the correct format
    console.log(data);

    const salesLabels = data.map(item => item.timestamp);
    const salesValues = data.map(item => parseFloat(item.amount));

    // Log labels and values
    console.log(salesLabels, salesValues);

    if (salesBarChart) salesBarChart.destroy();

    salesBarChart = new Chart(salesCtx, {
        type: 'bar', // Bar chart type
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'Sales Amount',
                data: salesValues,
                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Sales Amount'
                    },
                    beginAtZero: true
                }
            }
        }
    });
}


    // Function to update the insights chart
    function updateInsightsChart(data) {
        const insightsLabels = data.map(item => item.product_name);
        const clicksValues = data.map(item => parseInt(item.total_clicks));
        const wishlistValues = data.map(item => parseInt(item.total_wishlist));

        if (insightsBarChart) insightsBarChart.destroy();

        insightsBarChart = new Chart(insightsCtx, {
            type: 'bar', // Bar chart type
            data: {
                labels: insightsLabels,
                datasets: [
                    {
                        label: 'Total Clicks',
                        data: clicksValues,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)',
                        borderColor: 'rgba(153, 102, 255, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Total Wishlist',
                        data: wishlistValues,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Products'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Counts'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Function to create and display the sales table
    function createSalesTable(data) {
        if (data.length === 0) {
            let tableHTML = '<table class="table"><thead><tr><th>ID</th><th>Date & Time</th><th>Buyer Name</th><th>Seller Name</th><th>Product Name</th><th>Quantity</th><th>Selling Price</th><th>Agreed Price</th><th>Total Amount</th></tr></thead><tbody>';
            tableHTML += '<tr><td colspan="9" style="text-align: center;">No sales data available</td></tr>';
            tableHTML += '</tbody></table>';
            $('#report-table-container').html(tableHTML);
            return; // Stop further execution if no data
        }

        let tableHTML = '<table class="table"><thead><tr><th>ID</th><th>Date & Time</th><th>Buyer Name</th><th>Seller Name</th><th>Product Name</th><th>Quantity</th><th>Selling Price</th><th>Agreed Price</th><th>Total Amount</th></tr></thead><tbody>';

        data.forEach(item => {
            tableHTML += `<tr>
                            <td>${item.transaction_id}</td>
                            <td>${item.timestamp}</td>
                            <td>${item.buyer_name}</td>
                            <td>${item.seller_name}</td>
                            <td>${item.product_name}</td>
                            <td>${item.quantity}</td>
                            <td>${item.selling_price}</td>
                            <td>${item.agreed_price}</td>
                            <td>${item.amount}</td>
                          </tr>`;
        });

        tableHTML += '</tbody></table>';
        $('#report-table-container').html(tableHTML);
    }

    // Function to create and display the insights table
    function createInsightsTable(data) {
        if (data.length === 0) {
            let tableHTML = '<table class="table"><thead><tr><th>Product Name</th><th>Total Clicks</th><th>Total Wishlist</th></tr></thead><tbody>';
            tableHTML += '<tr><td colspan="3" style="text-align: center;">No insights data available</td></tr>';
            tableHTML += '</tbody></table>';
            $('#report-table-container').html(tableHTML);
            return; // Stop further execution if no data
        }

        let tableHTML = '<table class="table"><thead><tr><th>Product Name</th><th>Total Clicks</th><th>Total Wishlist</th></tr></thead><tbody>';

        data.forEach(item => {
            tableHTML += `<tr>
                            <td>${item.product_name}</td>
                            <td>${item.total_clicks}</td>
                            <td>${item.total_wishlist}</td>
                          </tr>`;
        });

        tableHTML += '</tbody></table>';
        $('#report-table-container').html(tableHTML);
    }

    // Function to fetch sales report data
    function fetchSalesReport() {
        $.ajax({
            url: 'fetch_report.php',
            type: 'POST',
            data: { reportType: 'sales' },
           success: function (response) {
    console.log("Response: ", response); // Log response
    const data = JSON.parse(response);
    console.log("Parsed Data: ", data); // Log parsed data
    updateSalesChart(data); 
    createSalesTable(data); 
},
            error: function () {
                console.error("Error fetching sales report data.");
            }
        });
    }

    // Function to fetch insights report data
    function fetchInsightsReport() {
        $.ajax({
            url: 'fetch_report.php',
            type: 'POST',
            data: { reportType: 'insights' },
            success: function (response) {
                const data = JSON.parse(response);
                updateInsightsChart(data);  // Update insights chart
                createInsightsTable(data);  // Create and display insights table
            },
            error: function () {
                console.error("Error fetching insights report data.");
            }
        });
    }

    // Fetch the sales report by default when the page loads
    fetchSalesReport();

    // Handle the dropdown selection change to switch between reports
    $('#report-type').on('change', function() {
        const selectedReport = $(this).val();

        if (selectedReport === 'sales') {
            fetchSalesReport();  // Fetch and display sales report
        } else if (selectedReport === 'insights') {
            fetchInsightsReport();  // Fetch and display insights report
        } else {
            // Optionally handle invalid or no report selected
            $('#report-table-container').html('<p>Please select a report type.</p>');
        }
    });
});
</script>


    <?php $conn->close(); ?>
    
    <div class="bottom-nav">
    <!-- Main Navigation Links -->
    <div class="nav-links" id="mainNavLinks">
        <a href="Market.php" class="nav-link">
            <i class="bi bi-shop icon"></i>
            <span class="nav-text">All Products</span>
        </a>
        <a href="farmer-inbox.php" class="nav-link icon-container">
            <i class="bi bi-chat-dots icon"></i>
            <span class="nav-text">Chats</span>
        </a>
        <a href="Wishlist.php" class="nav-link">
            <i class="bi bi-heart icon"></i>
            <span class="nav-text">Wishlist</span>
        </a>
        <a href="#" class="nav-link" id="mobile-sellNavLink">
            <i class="bi bi-tags icon"></i>
            <span class="nav-text">Sell</span>
        </a>
    </div>

    <!-- Submenu Container (Initially Hidden) -->
            <div class="mobile-submenu-container" id="mobile-submenuContainer">
            <a href="#" id="closemobile-Submenu" class="submenu-nav-link">
                <i class="bi bi-x icon"></i>
                <span>Close</span>
            </a>
            <a href="Farmer.php" class="submenu-nav-link">
                <i class="bi bi-grid icon"></i>
                <span>Seller Dashboard</span>
            </a>
            <a href="farmer-products.php" class="submenu-nav-link">
                <i class="bi bi-cart icon"></i>
                <span>Product List</span>
            </a>
            <a href="Price-Percentage.php" class="submenu-nav-link">
                <i class="bi bi-percent icon"></i>
                <span>Price Percentage</span>
            </a>
            <a href="farmer-reviews.php" class="submenu-nav-link">
                <i class="bi bi-list-stars icon"></i>
                <span>Reviews</span>
            </a>
            <a href="farmer-reports.php" class="submenu-nav-link">
                <i class="bi bi-file-earmark-bar-graph icon"></i>
                <span>Reports</span>
            </a>
        </div>
    </div>

<div id="profile-modal" class="modal">
    <div class="modal-content">
        <button class="close-button">&times;</button>
        <form id="profile-form">
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-image-container">
                        <img id="profile-img" src="" alt="Profile Picture">
                    </div>
                    <label class="upload-label" for="profile-upload">
                        <i class='bx bx-camera'></i>
                    </label>
                    <input type="file" id="profile-upload" style="display:none;" accept="image/*" />

                    <div class="profile-info">
                        <h2 id="profile-name"></h2>
                         <a href="javascript:void(0);" class="edit-profile-btn" id="open-edit-profile">Edit Profile</a>
                        <p class="info-item">
                            <i class='bx bx-phone'></i> <span class="info-value" id="phone"></span>
                            <span class="separator">|</span>
                            <i class='bx bx-envelope' ></i><span class="info-value" id="email"></span>
                        </p>
                    </div>
                </div>
            </div>

            <hr class="info-divider">

            <!-- Personal Information -->
            <div class="personal-info-header">
                <h3>Personal Information</h3>
            </div>

            <div class="profile-info-container">
                <div class="personal-info">
                    <!-- Personal Information Fields (Now under "Personal Information") -->
                    <label class="info-item">
                        <i class="bx bx-calendar"></i> Date of Birth:
                        <span class="info-value" id="dob"></span>
                    </label>
                    <label class="info-item">
                        <i class='bx bxs-calendar-alt' ></i> Age:
                        <span class="info-value" id="age"></span>
                    </label>
                    <label class="info-item">
                        <i class="bi bi-geo-alt"></i> Address:
                        <span class="info-value" id="address"></span>
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="save-button">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>


<div id="edit-profile-modal" class="edit-profile-modal">
    <div class="modal-content edit-profile-container">
        <div class="modal-header">
            <h2>Edit Profile</h2>
            <!-- Close Button -->
            <button class="close-btn">&times;</button>
        </div>
        <form action="update_user.php" method="POST">
            <div class="edit-profile-form">
                <div class="input-group">
                    <label for="first_name">First Name</label>
                    <input type="text" id="edit_first_name" name="first_name" value="" required>
                </div>
                <div class="input-group">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="edit_last_name" name="last_name" value="" required>
                </div>
                <div class="input-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="edit_dob" name="dob" value="" required onchange="calculateAge()">
                </div>
                <div class="input-group">
                    <label for="age">Age</label>
                    <input type="number" id="edit_age" name="age" value="" required readonly>
                </div>
                <div class="input-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="edit_phone" name="phone" value="" required>
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="edit_email" name="email" value="">
                </div>
                <div class="input-group">
                    <label for="province">Province</label>
                    <input type="text" id="edit_province" name="province" value="" required>
                </div>
                <div class="input-group">
                    <label for="municipality">Municipality</label>
                    <select id="edit_municipality" name="municipality" required>
                        <option value="Options" selected>Select Municipality</option>
                        <option value="Bacacay">Bacacay</option>
                        <option value="Camalig">Camalig</option>
                        <option value="Daraga">Daraga</option>
                        <option value="Guinobatan">Guinobatan</option>
                        <option value="Lagonoy">Lagonoy</option>
                        <option value="Legazpi">Legazpi</option>
                        <option value="Libon">Libon</option>
                        <option value="Ligao">Ligao</option>
                        <option value="Malilipot">Malilipot</option>
                        <option value="Manito">Manito</option>
                        <option value="Oas">Oas</option>
                        <option value="Pio Duran">Pio Duran</option>
                        <option value="Polangui">Polangui</option>
                        <option value="Rapu-Rapu">Rapu-Rapu</option>
                        <option value="Santo Domingo">Santo Domingo</option>
                        <option value="Tigaon">Tigaon</option>
                        <option value="Tiwi">Tiwi</option>
                    </select>
                </div>
                <div class="input-group">
                    <label for="barangay">Barangay</label>
                    <input type="text" id="edit_barangay" name="barangay" value="" required>
                </div>
                <div class="input-group">
                    <label for="postal_code">Postal Code</label>
                    <input type="text" id="edit_postal_code" name="postal_code" value="" required>
                </div>

                <div class="edit-form-action">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="save-btn">Apply Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Overlay -->
<div class="manage-password-overlay hidden" id="manage-password-overlay"></div>

<!-- Modal -->
<div class="manage-password-modal hidden" id="manage-password-modal">
    <div class="modal-header">
        <h1 class="modal-title">Manage Account Password</h1>
        <span class="close-btn" id="close-manage-password-modal">&times;</span>
    </div>
    <div class="modal-body">
        <div id="error-message" class="alert alert-danger" style="display: none;"></div>

        <form id="password-form" class="space-y-6">
            <!-- Current Password -->
            <div class="space-y-4">
                <div>
                    <label for="current-password" class="password-label">CURRENT PASSWORD</label>
                    <input
                        id="current-password"
                        name="current_password"
                        type="password"
                        required
                        class="input"
                    />
                </div>
                <!-- New Password -->
                <div>
                    <label for="new-password" class="password-label">NEW PASSWORD</label>
                    <input
                        id="new-password"
                        name="new_password"
                        type="password"
                        required
                        class="input"
                    />
                </div>
                <!-- Confirm Password -->
                <div>
                    <label for="confirm-password" class="password-label">CONFIRM NEW PASSWORD</label>
                    <input
                        id="confirm-password"
                        name="confirm_password"
                        type="password"
                        required
                        class="input"
                    />
                </div>
            </div>
            <!-- Divider -->
            <hr class="divider" />
            <!-- Password Requirements -->
            <div class="password-requirements">
                <h2>Required Password Format:</h2>
                <ul>
                    <li id="length-requirement">- Must be 8 characters or more</li>
                    <li id="uppercase-requirement">- At least one uppercase character</li>
                    <li id="lowercase-requirement">- At least one lowercase character</li>
                    <li id="number-requirement">- At least one number</li>
                    <li id="symbol-requirement">- At least one symbol</li>
                </ul>
            </div>
            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn">Save changes</button>
            </div>
        </form>
    </div>
</div>
</section>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Handle active state for nav links in the mobile bottom-nav
        const navLinks = document.querySelectorAll('.bottom-nav .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove 'active' class from all nav links
                navLinks.forEach(navLink => navLink.classList.remove('active'));
                
                // Add 'active' class to the clicked nav link
                link.classList.add('active');
            });
        });

        // Highlight the active nav link based on the current URL
        const currentLocation = window.location.href; // Get the current page URL
        navLinks.forEach(link => {
            if (link.href === currentLocation) {
                link.classList.add('active');
            }
        });

        // Handle active state for submenu links
        const submenuLinks = document.querySelectorAll('.mobile-submenu-container .submenu-nav-link');
        submenuLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove 'active' class from all submenu links
                submenuLinks.forEach(submenuLink => submenuLink.classList.remove('active'));

                // Add 'active' class to the clicked submenu link
                link.classList.add('active');
            });
        });
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
    const managePasswordLink = document.querySelector(".dropdown-item-custom:nth-child(2)"); // Second dropdown item
    const modal = document.getElementById("manage-password-modal");
    const modalOverlay = document.getElementById("manage-password-overlay");
    const closeModalBtn = document.getElementById("close-manage-password-modal");

    // Function to show modal
    const showModal = () => {
        modal.classList.remove("hidden");
        modalOverlay.classList.remove("hidden");
    };

    // Function to hide modal
    const hideModal = () => {
        modal.classList.add("hidden");
        modalOverlay.classList.add("hidden");
    };

    // Event listener for opening modal
    managePasswordLink.addEventListener("click", (event) => {
        event.preventDefault(); // Prevent default link behavior
        showModal();
    });

    // Event listener for closing modal
    closeModalBtn.addEventListener("click", hideModal);

    // Close modal when clicking outside of it
    modalOverlay.addEventListener("click", hideModal);
});

document.getElementById('password-form').addEventListener('submit', async function (e) {
    e.preventDefault();
    const errorMessageDiv = document.getElementById('error-message');
    errorMessageDiv.style.display = 'none';

    const formData = new FormData(this);

    try {
        const response = await fetch('update_password.php', {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();

        if (result.status === 'success') {
            // Show success message with SweetAlert
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: result.message,
            }).then(() => {
                // Reload the page after the success message
                location.reload();
            });
        } else {
            // If error, show the message in the existing error div
            errorMessageDiv.textContent = result.message; // Set the error message
            errorMessageDiv.style.display = 'block';  // Show the error message div
        }
    } catch (error) {
        console.error('Error:', error);

        // If there's a problem, show a general error message in the error div
        errorMessageDiv.textContent = 'An unexpected error occurred.';
        errorMessageDiv.style.display = 'block'; // Show the error message div
    }
});
</script>

<script>
 function adjustLogoutPosition() {
    const logoutLink = document.querySelector('#logout-link');
    const separator = document.querySelector('.logout-separator');
    const bottomContent = document.querySelector('.bottom-content');

    if (window.innerWidth <= 768) {
        // Move logout-link below the separator in the dropdown
        separator.parentNode.appendChild(logoutLink);
    } else {
        // Move logout-link back to the bottom content
        bottomContent.appendChild(logoutLink);
    }
}

// Run on page load
adjustLogoutPosition();

// Add event listener for window resize
window.addEventListener('resize', adjustLogoutPosition);
</script>

  <script>
        function calculateAge() {
            const dobInput = document.getElementById('dob');
            const ageInput = document.getElementById('age');
            const dob = new Date(dobInput.value);
            const today = new Date();
            
            // Check if the date of birth is valid
            if (dobInput.value === "") {
                ageInput.value = "";
                return;
            }
            
            // Calculate the age
            let age = today.getFullYear() - dob.getFullYear();
            const monthDifference = today.getMonth() - dob.getMonth();
            
            // If the birthday hasn't occurred yet this year, subtract 1 from the age
            if (monthDifference < 0 || (monthDifference === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            // Ensure the age is not negative
            if (age < 0) {
                age = 0;
            }
            
            // Set the calculated age in the input field
            ageInput.value = age;
        }
    </script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('edit-profile-modal');
    const closeModalBtn = document.querySelector('.close-btn');
    const cancelModalBtn = document.querySelector('.cancel-btn'); // Add cancel-btn selector
    const openModalBtn = document.getElementById('open-edit-profile');

    // Open the modal
    openModalBtn.addEventListener('click', (e) => {
        e.preventDefault();
        modal.style.display = 'block';
    });

    // Close the modal using the close button
    closeModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close the modal using the cancel button
    cancelModalBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    // Close the modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const profileModal = document.getElementById('profile-modal');
    const openProfileBtn = document.getElementById('open-profile');
    const closeModalBtn = document.querySelector('.close-button');
    const modalVisibleClass = 'modal-visible';

    // Show the modal and fetch user data
    openProfileBtn.addEventListener('click', async (e) => {
        e.preventDefault();

        try {
            // Fetch user data from the server
            const response = await fetch('fetch_user.php');
            if (!response.ok) throw new Error('Failed to fetch user data.');

            const text = await response.text();
        
            // Parse JSON response if valid
            let userData;
            try {
                userData = JSON.parse(text);
            } catch (error) {
                throw new Error('Invalid JSON response received.');
            }

            if (userData.error) {
                alert(userData.error);
                return;
            }


            // Handle profile picture
            const profileImageContainer = document.querySelector('.profile-image-container');
            
           if (userData.profile) {
    profileImageContainer.innerHTML = `
        <img id="profile-img" src="${userData.profile}?v=${new Date().getTime()}" alt="Profile Picture">
`;
} else {
    const initial = userData.first_name ? userData.first_name.charAt(0).toUpperCase() : '?';
    profileImageContainer.innerHTML = `
        <div class="user-profile" style="
            width: 100%; 
            height: 100%; 
            background-color: #2D4A36; 
            color: white; 
            border-radius: 50%; 
            text-align: center; 
            line-height: 130px; 
            font-size: 60px;">
            ${initial}
        </div>`;
}

            // Populate user information
            document.querySelector('.profile-info h2').innerHTML = `${userData.first_name || ''} ${userData.last_name || ''} `;
            document.querySelector('.profile-info #phone').textContent = userData.phone_number || 'No phone number provided';
            document.querySelector('.profile-info #email').textContent = userData.email || 'No email provided';

            // Populate addressers
            const address = [
                userData.province || 'No province provided',
                userData.municipality || 'No municipality provided',
                userData.barangay || 'No barangay provided',
                userData.postal_code || 'No postal code provided',
            ].join(', ');
            document.querySelector('.personal-info #address').textContent = address;

            // Populate personal information
            document.querySelector('.personal-info #dob').textContent = userData.birthdate || 'No birthdate provided';
            document.querySelector('.personal-info #age').textContent = userData.age || 'No age provided';

            // Show the modal
            profileModal.classList.add(modalVisibleClass);
        } catch (error) {
            console.error('Error fetching or processing user data:', error);
            alert('An error occurred while loading your profile. Please try again.');
        }
    });

    // Close the modal
    closeModalBtn.addEventListener('click', () => {
        profileModal.classList.remove(modalVisibleClass);
    });

    // Close the modal when clicking outside of it
    window.addEventListener('click', (e) => {
        if (e.target === profileModal) {
            profileModal.classList.remove(modalVisibleClass);
        }
    });
});
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const editProfileModal = document.getElementById('edit-profile-modal');
    const openEditProfileBtn = document.getElementById('open-edit-profile');
    const closeModalBtn = editProfileModal.querySelector('.close-btn');

    // Open the "Edit Profile" modal
    openEditProfileBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            // Fetch user data
            const response = await fetch('fetch_user.php');
            if (!response.ok) throw new Error('Failed to fetch user data.');

            const userData = await response.json();
            if (userData.error) {
                alert(userData.error);
                return;
            }

            // Populate input fields in the "Edit Profile" modal
            document.getElementById('edit_first_name').value = userData.first_name || '';
            document.getElementById('edit_last_name').value = userData.last_name || '';
            document.getElementById('edit_phone').value = userData.phone_number || '';
            document.getElementById('edit_email').value = userData.email || '';
            document.getElementById('edit_dob').value = userData.birthdate || '';
            document.getElementById('edit_age').value = userData.age || '';
            document.getElementById('edit_province').value = userData.province || '';
            document.getElementById('edit_municipality').value = userData.municipality || 'Options';
            document.getElementById('edit_barangay').value = userData.barangay || '';
            document.getElementById('edit_postal_code').value = userData.postal_code || '';

            // Show the modal
            editProfileModal.style.display = 'block';
        } catch (error) {
            console.error('Error fetching or processing user data:', error);
            alert('An error occurred while loading the edit profile form. Please try again.');
        }
    });

    // Close the modal
    closeModalBtn.addEventListener('click', () => {
        editProfileModal.style.display = 'none';
    });

    // Close the modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === editProfileModal) {
            editProfileModal.style.display = 'none';
        }
    });
});

</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const applyChangesBtn = document.querySelector('.edit-form-action button[type="submit"]');

    applyChangesBtn.addEventListener('click', (e) => {
        e.preventDefault();

        // Get the form values from the `edit-profile-modal`
        const firstName = document.getElementById('edit_first_name')?.value || '';
        const lastName = document.getElementById('edit_last_name')?.value || '';
        const phone = document.getElementById('edit_phone')?.value || '';
        const email = document.getElementById('edit_email')?.value || '';
        const dob = document.getElementById('edit_dob')?.value || '';
        const age = document.getElementById('edit_age')?.value || '';
        const province = document.getElementById('edit_province')?.value || '';
        const municipality = document.getElementById('edit_municipality')?.value || '';
        const barangay = document.getElementById('edit_barangay')?.value || '';
        const postalCode = document.getElementById('edit_postal_code')?.value || '';

        // Update the `profile-modal` content dynamically
        const profileNameEl = document.getElementById('profile-name');
        const phoneEl = document.getElementById('phone');
        const emailEl = document.getElementById('email');
        const dobEl = document.getElementById('dob');
        const ageEl = document.getElementById('age');
        const addressEl = document.getElementById('address');

        if (profileNameEl) {
            const fullName = `${firstName} ${lastName}`.trim();
            profileNameEl.textContent = fullName || 'No name provided';
        } 
        if (phoneEl) phoneEl.textContent = phone || 'No phone number provided';
        if (emailEl) emailEl.textContent = email || 'No email provided';
        if (dobEl) dobEl.textContent = dob || 'No birthdate provided';
        if (ageEl) ageEl.textContent = age || 'No age provided';
        if (addressEl) addressEl.textContent = `${province}, ${municipality}, ${barangay}, ${postalCode}`;

        // Close the `edit-profile-modal`
        const editProfileModal = document.getElementById('edit-profile-modal');
        if (editProfileModal) editProfileModal.style.display = 'none';
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const saveButton = document.querySelector('.save-button');
    const profileUpload = document.getElementById('profile-upload');
    const profileImageContainer = document.querySelector('.profile-image-container');
    const editProfileModal = document.getElementById('edit-profile-modal');
    const profileModal = document.getElementById('profile-modal');

    // Handle profile picture upload and preview
    profileUpload.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        // Validate file type
        const validImageTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!validImageTypes.includes(file.type)) {
            Swal.fire({
                title: 'Invalid File Type',
                text: 'Please upload an image file (JPEG, PNG, GIF).',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            // Clear the input
            profileUpload.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            profileImageContainer.innerHTML = `
                <img id="profile-img-preview" src="${event.target.result}" alt="Profile Preview">
            `;
        };
        reader.readAsDataURL(file);
    }
});


    saveButton.addEventListener('click', async (e) => {
        e.preventDefault();

        // Show a confirmation dialog using SweetAlert before saving
        const confirmation = await Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to save the changes to your profile?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, save it!',
            cancelButtonText: 'Cancel'
        });

        if (confirmation.isConfirmed) {
            // Collect user data from the form
            const firstName = document.getElementById('edit_first_name').value.trim() || '';
            const lastName = document.getElementById('edit_last_name').value.trim() || '';
            const phone = document.getElementById('edit_phone').value.trim() || '';
            const email = document.getElementById('edit_email').value.trim() || '';
            const dob = document.getElementById('edit_dob').value.trim() || '';
            const age = document.getElementById('edit_age').value.trim() || '';
            const province = document.getElementById('edit_province').value.trim() || '';
            const municipality = document.getElementById('edit_municipality').value.trim() || '';
            const barangay = document.getElementById('edit_barangay').value.trim() || '';
            const postalCode = document.getElementById('edit_postal_code').value.trim() || '';

            // Prepare the FormData object
            const formData = new FormData();

            // Include the profile picture if a new one has been uploaded
            if (profileUpload.files.length > 0) {
                formData.append('profile', profileUpload.files[0]);
            }

            // Append other profile fields
            formData.append('first_name', firstName);
            formData.append('last_name', lastName);
            formData.append('phone', phone);
            formData.append('email', email);
            formData.append('dob', dob);
            formData.append('age', age);
            formData.append('province', province);
            formData.append('municipality', municipality);
            formData.append('barangay', barangay);
            formData.append('postal_code', postalCode);

            try {
                // Send the data to the server
                const response = await fetch('update_user.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();

                if (result.success) {
                    // SweetAlert for success
                    Swal.fire({
                        title: 'Success!',
                        text: 'Profile updated successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Close the edit profile modal
                        editProfileModal.style.display = 'none';
                        // Reload the page after closing the modal
                        location.reload();
                    });

                    // Update the profile modal with the new data
                    if (result.data) {
                        const updatedData = result.data;

                        document.getElementById('profile-name').textContent = `${updatedData.first_name} ${updatedData.last_name}`;
                        document.getElementById('phone').textContent = updatedData.phone || 'No phone number provided';
                        document.getElementById('email').textContent = updatedData.email || 'No email provided';
                        document.getElementById('dob').textContent = updatedData.dob || 'No birthdate provided';
                        document.getElementById('age').textContent = updatedData.age || 'No age provided';
                        document.getElementById('address').textContent = `${updatedData.province}, ${updatedData.municipality}, ${updatedData.barangay}, ${updatedData.postal_code}`;
                    }

                    // If the profile picture was updated, show the new image
                    if (result.profile) {
                        profileImageContainer.innerHTML = `
                            <img id="profile-img" src="${result.profile}" alt="Profile Picture">
                        `;
                    }
                } else {
                    // SweetAlert for failure
                    Swal.fire({
                        title: 'Error!',
                        text: result.error || 'Failed to update profile. Please try again.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (error) {
                console.error('Error saving profile:', error);

                // SweetAlert for catch error
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while saving your profile. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        }
    });
});
</script>

<script>
// Function to toggle the filter options visibility
  document.getElementById('filterIcon').addEventListener('click', function(event) {
    const options = document.getElementById('filterOptions');
    options.style.display = options.style.display === '' ? 'none' : '';
    event.stopPropagation(); // Prevent click from closing the menu immediately
  });

  // Function to apply the selected filter
  function applyFilter(filter) {
    console.log('Filter applied:', filter);
    document.getElementById('filterOptions').style.display = 'none'; // Hide options after selection
    
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        // Get the category_name from the data attribute
        const productCategoryName = card.getAttribute('data-category-name').toLowerCase();  // Convert to lowercase for easier comparison

        // Check if the product category matches the selected filter
        if (filter === 'all' || productCategoryName === filter.toLowerCase()) {
            card.style.display = ''; // Show the product
        } else {
            card.style.display = 'none'; // Hide the product
        }
    });
}

// Close the filter options if clicked outside
window.addEventListener('click', function(event) {
    if (!event.target.closest('.label') && !event.target.closest('#filterOptions')) {
      document.getElementById('filterOptions').style.display = 'none';
    }
});
</script>

<script>
    const mobileSellNavLink = document.getElementById('mobile-sellNavLink');
    const mobileSubmenuContainer = document.getElementById('mobile-submenuContainer');
    const closeMobileSubmenu = document.getElementById('closemobile-Submenu');

    // Toggle the submenu visibility
    mobileSellNavLink.addEventListener('click', function(event) {
        event.preventDefault(); // Prevent default link behavior
        
        // Toggle 'show' class to display/hide the submenu
        mobileSubmenuContainer.classList.toggle('show');
    });

    // Close the submenu when clicking the close icon
    closeMobileSubmenu.addEventListener('click', function(event) {
        event.preventDefault();
        mobileSubmenuContainer.classList.remove('show'); // Hide the submenu when close icon is clicked
    });
</script>

<script>
function toggleDropdown() {
    const dropdown = document.querySelector('.dropdown-menu');
    const isMobile = window.matchMedia("(max-width: 768px)").matches;

    if (isMobile) {
        dropdown.classList.toggle('show');
    }
}

document.addEventListener('click', function (event) {
    const dropdown = document.querySelector('.dropdown-menu');
    const profileCon = document.querySelector('.profile-con');

    if (dropdown && !dropdown.contains(event.target) && !profileCon.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

document.getElementById("profileIcon").addEventListener("click", function () {
    const dropdown = document.getElementById("profileDropdown");
    dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
});

// Close dropdown when clicking outside
document.addEventListener("click", function (e) {
    const profileIcon = document.getElementById("profileIcon");
    const dropdown = document.getElementById("profileDropdown");

    if (!profileIcon.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = "none";
    }
});

</script>

<script>  
    document.addEventListener("DOMContentLoaded", function() {
        // Handle active state for nav links in the mobile bottom-nav
        const navLinks = document.querySelectorAll('.bottom-nav .nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove 'active' class from all nav links
                navLinks.forEach(navLink => navLink.classList.remove('active'));
                
                // Add 'active' class to the clicked nav link
                link.classList.add('active');
            });
        });

        // Highlight the active nav link based on the current URL
        const currentLocation = window.location.href; // Get the current page URL
        navLinks.forEach(link => {
            if (link.href === currentLocation) {
                link.classList.add('active');
            }
        });

        // Handle active state for submenu links
        const submenuLinks = document.querySelectorAll('.mobile-submenu-container .submenu-nav-link');
        submenuLinks.forEach(link => {
            link.addEventListener('click', function() {
                // Remove 'active' class from all submenu links
                submenuLinks.forEach(submenuLink => submenuLink.classList.remove('active'));

                // Add 'active' class to the clicked submenu link
                link.classList.add('active');
            });
        });
    });
</script>

<script>
function toggleDropdown() {
    const dropdown = document.querySelector('.dropdown-menu');
    const isMobile = window.matchMedia("(max-width: 768px)").matches;

    if (isMobile) {
        dropdown.classList.toggle('show');
    }
}

document.addEventListener('click', function (event) {
    const dropdown = document.querySelector('.dropdown-menu');
    const profileCon = document.querySelector('.profile-con');

    if (dropdown && !dropdown.contains(event.target) && !profileCon.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const toggle = document.querySelector(".toggle");
    const sidebar = document.querySelector("nav");
    const submenuContainer = document.getElementById("submenuContainer");
    const sellNavLink = document.getElementById("sellNavLink");
    const closeSubmenu = document.getElementById("closeSubmenu");
    const submenuLinks = submenuContainer.querySelectorAll(".menu-links .nav-link");

    // Toggle sidebar open/close
    toggle.addEventListener("click", () => {
        sidebar.classList.toggle("close");
    });

    // Open the submenu when the 'Sell' link is clicked
    sellNavLink.addEventListener("click", function(event) {
        event.preventDefault();
        submenuContainer.style.display = "block"; // Show submenu
    });

    // Close the submenu and go back to the main menu when 'Close' link is clicked
    closeSubmenu.addEventListener("click", function(event) {
        event.preventDefault();
        submenuContainer.style.display = "none"; // Hide submenu
        sidebar.classList.remove("close"); // Ensure sidebar stays open
        // Optionally, you can add logic to navigate back to the main menu here, if needed.
    });

    // Prevent closing the sidebar or returning to the main menu when clicking submenu links
    submenuLinks.forEach(link => {
        link.addEventListener("click", function(event) {
            event.preventDefault(); // Prevent default link behavior
            
            // Navigate to the target URL
            const targetUrl = this.querySelector('a').href; // Get the URL of the clicked link
            window.location.href = targetUrl; // Redirect to the link's URL
        });
    });

    // Ensure sidebar and submenu stay open on page load if on a submenu page
    if (window.location.pathname.includes("Farmer.php") || 
        window.location.pathname.includes("farmer-products.php") || 
        window.location.pathname.includes("Price-Percentage.php") || 
        window.location.pathname.includes("farmer-reviews.php") || 
        window.location.pathname.includes("farmer-reports.php")) {
        submenuContainer.style.display = "block"; // Keep submenu open
        sidebar.classList.remove("close"); // Keep sidebar open
    }
});

// Active link highlight
document.addEventListener("DOMContentLoaded", function() {
    const currentLocation = window.location.pathname; // Get the current page URL
    const navLinks = document.querySelectorAll('.menu-links .nav-link');

    navLinks.forEach(link => {
        if (link.firstElementChild.href === window.location.href) {
            link.classList.add('active'); // Add 'active' class to the current page
        }
    });
});
</script>

<script>
    document.getElementById('logout-link').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent default action

    Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'green',
        cancelButtonColor: 'lightgreen',
        confirmButtonText: 'Yes, log me out!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '../Login/Login.php'; // Redirect to logout page
        }
    });
});

document.addEventListener("DOMContentLoaded", function() {
    function handleScreenSize() {
        if (window.innerWidth <= 425 && window.innerHeight <= 605) {
            // Move menu icons to the top when the screen size is small
            const menuIcons = document.querySelector('.menu-links');
            const topMenuIcons = document.querySelector('.top-menu-icons');
            topMenuIcons.appendChild(menuIcons);
        } else {
            // Move them back to the sidebar
            const sidebarMenu = document.querySelector('.menu-bar .menu');
            const menuIcons = document.querySelector('.menu-links');
            sidebarMenu.appendChild(menuIcons);
        }
    }

    // Initial check
    handleScreenSize();

    // Check on window resize
    window.addEventListener("resize", handleScreenSize);
});

  </script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentLocation = window.location.pathname; // Get the current page URL
    const navLinks = document.querySelectorAll('.menu-links .nav-link');

    navLinks.forEach(link => {
        // Check if the link's href matches the current location
        if (link.firstElementChild.href === window.location.href) {
            link.classList.add('active'); // Add 'active' class to the current page
        }
    });
});
</script>

<script>
document.getElementById("sellNavLink").addEventListener("click", function() {
    var submenuContainer = document.getElementById("submenuContainer");
    submenuContainer.style.display = "block"; // Show submenu
});

document.getElementById("closeSubmenu").addEventListener("click", function() {
    var submenuContainer = document.getElementById("submenuContainer");
    submenuContainer.style.display = "none"; // Hide submenu
});

</script>

<script>
let lastUnreadCount = 0; // To track the last unread message count
let isFetching = false; // To prevent overlapping requests

function checkUnreadMessages() {
    if (isFetching) return; // Skip if a fetch is already ongoing

    isFetching = true; // Set fetching to true
    fetch(`check_unread_messages.php?t=${new Date().getTime()}`) // Prevent caching
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                const unreadBadge = document.querySelector('.icon-container .badge');
                const notificationDiv = document.getElementById('notification');

                // Only show notification if the unread count has changed
                if (data.unread_count > 0 && data.unread_count !== lastUnreadCount) {
                    // Update badge
                    if (!unreadBadge) {
                        const badge = document.createElement('span');
                        badge.className = 'badge';
                        badge.textContent = data.unread_count;
                        document.querySelector('.icon-container').appendChild(badge);
                    } else {
                        unreadBadge.textContent = data.unread_count;
                    }

                    // Update and show the notification
                    notificationDiv.textContent = `You have ${data.unread_count} new message(s)!`;
                    notificationDiv.classList.remove('hidden'); // Make visible
                    notificationDiv.classList.add('visible');

                    // Hide after 3 seconds
                    setTimeout(() => {
                        notificationDiv.classList.remove('visible');
                        notificationDiv.classList.add('hidden'); // Hide again
                    }, 3000);

                    // Update the last unread count
                    lastUnreadCount = data.unread_count;
                } else if (data.unread_count === 0) {
                    // No unread messages: reset badge and hide notification
                    if (unreadBadge) unreadBadge.remove();
                    notificationDiv.classList.remove('visible');
                    notificationDiv.classList.add('hidden');
                    lastUnreadCount = 0; // Reset count
                }
            }
        })
        .catch(error => console.error('Error fetching unread messages:', error))
        .finally(() => {
            isFetching = false; // Reset fetch state
        });
}

// Check messages every 30 seconds
setInterval(checkUnreadMessages, 3000); // Polling interval is now 3 seconds

</script>

<script>
let idleTime = 0; // Initialize idle time counter

// Reset idle time on user activity
function resetIdleTime() {
    idleTime = 0;
}

// Increment the idle time counter
function incrementIdleTime() {
    idleTime++;
    if (idleTime >= 600) { // Timeout after 600 seconds (10 minutes) of inactivity
        // Show SweetAlert with a redirect on confirmation
        Swal.fire({
            title: 'Session Timeout',
            text: 'You have been inactive for too long. Click "OK" to log in again.',
            icon: 'warning',
            confirmButtonText: 'OK'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '../Login/Login.php'; // Redirect to login
            }
        });

        // Stop further increments to avoid showing the alert multiple times
        idleTime = -1;
    }
}

// Events that indicate activity
document.addEventListener('mousemove', resetIdleTime); // Mouse movement
document.addEventListener('keypress', resetIdleTime); // Key presses
document.addEventListener('click', resetIdleTime); // Mouse clicks
document.addEventListener('scroll', resetIdleTime); // Scrolling

// Check idle time every second
setInterval(incrementIdleTime, 1000);
</script>
</body>
</html>
