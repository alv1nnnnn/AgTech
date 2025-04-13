<?php
session_start(); // Start session to access session variables

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login.php");
    exit();
}

require_once '../Connection/connection.php';

// Get the logged-in user_id
$user_id = $_SESSION['user_id'];

// Prepare SQL query to retrieve profile picture URL and first name based on user_id
$sql = "SELECT profile, first_name, last_name FROM user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($profile_picture, $first_name, $last_name);
$stmt->fetch();
$stmt->close();

// Generate the avatar if the profile picture is empty
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

// Query to fetch products under review
$reviewQuery = "
    SELECT pr.product_review_id, pr.product_name, pr.product_price, pr.product_category, pr.created_at, pr.status, pr.images
    FROM product_review pr
    WHERE pr.status = 'Reviewing' AND pr.user_id = ?
    ORDER BY pr.created_at DESC
";
$reviewStmt = $conn->prepare($reviewQuery);
$reviewStmt->bind_param("i", $user_id);
$reviewStmt->execute();
$reviewResult = $reviewStmt->get_result();

// Query to fetch approved products with detailed information
$approvedQuery = "
    SELECT p.product_id, p.product_name, pd.description, pp.current_price, pp.is_active, pp.effective_date, c.category_name, pt.image_url, p.status, p.location
    FROM userproducts up
    JOIN products p ON up.product_id = p.product_id
    JOIN productdetails pd ON p.product_id = pd.product_id
    JOIN productprices pp ON p.product_id = pp.product_id
    JOIN category c ON p.category_id = c.category_id
    JOIN productimages pt ON p.product_id = pt.product_id
    WHERE up.user_id = ? AND pp.is_active = TRUE
    ORDER BY pp.effective_date DESC
";
$approvedStmt = $conn->prepare($approvedQuery);
$approvedStmt->bind_param("i", $user_id);
$approvedStmt->execute();
$approvedResult = $approvedStmt->get_result();

// Count clicks for products owned by the user
$total_clicks = 0;

// Query to get all product_ids owned by the logged-in user
$productQuery = "SELECT product_id FROM userproducts WHERE user_id = ?";
$productStmt = $conn->prepare($productQuery);
$productStmt->bind_param("i", $user_id);
$productStmt->execute();
$productResult = $productStmt->get_result();

while ($product = $productResult->fetch_assoc()) {
    $product_id = $product['product_id'];

    // Get the clicks count for this product from the product_performance table
    $clickQuery = "SELECT SUM(clicks_count) AS total_clicks FROM product_performance WHERE product_id = ?";
    $clickStmt = $conn->prepare($clickQuery);
    $clickStmt->bind_param("i", $product_id);
    $clickStmt->execute();
    $clickResult = $clickStmt->get_result();
    $clickRow = $clickResult->fetch_assoc();

    $total_clicks += $clickRow['total_clicks'];

    $clickStmt->close();
}
$productStmt->close();

// Fetch the total wishlist count for products owned by the user
$wishlistQuery = "
    SELECT SUM(pp.wishlist_count) AS total_wishlist
    FROM product_performance pp
    JOIN userproducts up ON pp.product_id = up.product_id
    WHERE up.user_id = ?
";
$wishlistStmt = $conn->prepare($wishlistQuery);
$wishlistStmt->bind_param("i", $user_id);
$wishlistStmt->execute();
$wishlistResult = $wishlistStmt->get_result();
$wishlistRow = $wishlistResult->fetch_assoc();
$total_wishlist = $wishlistRow['total_wishlist'];
$wishlistStmt->close();

// Prepare other data for dashboard or profile page
$chatStmt = $conn->prepare("SELECT COUNT(*) AS chat_count FROM chat WHERE receiver_id = ? AND read_status = 'unread'");
$ratingStmt = $conn->prepare("SELECT AVG(rating_value) AS average_rating FROM review WHERE rated_user_id = ? AND role = 'seller'");
$productactiveStmt = $conn->prepare("SELECT COUNT(*) AS active_products FROM products INNER JOIN userproducts ON products.product_id = userproducts.product_id WHERE userproducts.user_id = ? AND products.status = 'active'");
$productsoldStmt = $conn->prepare("SELECT COUNT(*) AS sold_products FROM products INNER JOIN userproducts ON products.product_id = userproducts.product_id WHERE userproducts.user_id = ? AND products.status = 'sold'");

$chatStmt->bind_param('i', $user_id);
$productactiveStmt->bind_param('i', $user_id);
$productsoldStmt->bind_param('i', $user_id);
$ratingStmt->bind_param('i', $user_id);

$chatStmt->execute();
$chatResult = $chatStmt->get_result();
$chatCount = $chatResult->fetch_assoc()['chat_count'];

$ratingStmt->execute();
$ratingResult = $ratingStmt->get_result();
$averageRating = $ratingResult->fetch_assoc()['average_rating'];

$productactiveStmt->execute();
$productactiveResult = $productactiveStmt->get_result();
$activeProducts = $productactiveResult->fetch_assoc()['active_products'];

$productsoldStmt->execute();
$productsoldResult = $productsoldStmt->get_result();
$soldProducts = $productsoldResult->fetch_assoc()['sold_products'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/farmer.css?v=<?php echo time(); ?>">
     <link rel="stylesheet" href="../css/farmer-mobile.css?v=<?php echo time(); ?>">
     <link rel="stylesheet" href="../css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
</head>

<style>
    .hidden, #notification{
        display: none;
    }
    
    /* Ensure the body padding is removed when modal is open */
body.modal-open {
    padding-right: 0 !important;
  }
  

/* Modal Backdrop */
.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
  }
  
  /* Modal Container */
  .farmer-modal {
    display: none;
    position: fixed;
    z-index: 1050;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    outline: 0;
  }
  
  .farmer-modal.show {
    display: block;
  }
  
  /* Modal Dialog */
  .farmer-modal-dialog {
    position: relative;
    width: 100%;
    max-width: 500px;
    padding: 20px;
    margin: auto;
  }
  
  .farmer-modal-dialog-centered {
    display: flex;
    align-items: center;
    min-height: calc(100% - 1.75rem);
  }
  
  /* Modal Content */
  .farmer-modal-content {
    position: relative;
    background-color: #fff;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 0.3rem;
    outline: 0;
  }
  
  /* Modal Header */
  .farmer-modal-header {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    font-size: 20px;
  }
  
  .farmer-modal-title {
    margin-bottom: 0;
    line-height: 1.5;
    display: flex;
    flex-wrap: nowrap;
    flex-direction: row;
    align-content: center;
    justify-content: center;
    align-items: center;
    margin: auto;
  }

  #buyerNameLabel{
    font-weight: normal;
  }
  
  /* Close Button */
.btn-close {
    padding: 0;
    background: transparent;
    border: 0;
    -webkit-appearance: none;
    appearance: none;
  }
  
  /* Optional: Add some spacing around the close button if needed */
  .btn-close {
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 30px;
    background-color: lightgray;
    border-radius: 50%;
    line-height: 1;
  }
  
 /* Ensure label is positioned on top of the input */
.farmer-modal-body form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem; /* Adjust spacing between label and input if needed */
    padding: 10px;
  }
  
  /* Style for label to make sure it appears above the input */
  .farmer-modal-body label {
    margin-bottom: 0.5rem; /* Space between label and input */
  }
  
  /* Ensure input takes the full width */
  .farmer-modal-body .form-control {
    width: 100%;
    height: 40px;
    text-align: center;
  }
  
  /* Hide spinner controls in WebKit browsers */
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

  /* Modal Footer */
  .farmer-modal-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    border-top: 1px solid #dee2e6;
  }
  
  /* Transition Effects */
  .farmer-modal.fade .modal-dialog {
    transition: transform 0.3s ease-out;
    transform: translateY(-50px);
  }
  
  .farmer-modal.fade.show .modal-dialog {
    transform: translateY(0);
  }
  
  .farmer-modal.fade .modal-backdrop {
    opacity: 0;
    transition: opacity 0.15s linear;
  }
  
  .farmer-modal.fade.show .modal-backdrop {
    opacity: 1;
  }
  
   .private-response{
    display: flex;
    background-color: whitesmoke;
    height: 40px;
    width: 96%;
    justify-content: center;
    align-items: center;
    margin: auto;
    gap: 20px;
    margin-top: 10px;
    box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
  }

  .bi-incognito{
    font-size: 20px;
  }

  .interested-buyers-list{
    list-style: none;
  }

  .who{
    font-size: 20px;
    font-weight: 600;
    padding-left: 10px;
    padding-top: 10px;
  }

  .note{
    padding-left: 10px;
    margin-bottom: 20px;
  }

  .buyer-info-wrapper{
    background-color: whitesmoke;
    height: 60px;
    width: 100%;
    padding-left: 10px;
  }

  .buyer-info-wrapper:hover{
    background-color: #2D4A36;
    color: white;
  }

  .someone{
    display: flex;
    align-items: center;
    gap: 10px;
    background-color: whitesmoke;
    height: 60px;
    width: 100%;
    padding-left: 10px;
  }

  .someone:hover{
    background-color: #2D4A36;
    color: white;
  }

  .bi-people{
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 30px !important;
    color: white !important;
    height: 45px;
    width: 45px;
    background-color: gray;
    border-radius: 50%;
  }

  .someone p{
    display: flex;
    align-items: flex-start;
    left: 0;
  }
  
  .farmer-modal-backdrop-custom {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Dark semi-transparent background */
    z-index: 1040; /* Ensure it's behind the modal but above other content */
    display: none; /* Initially hidden */
}

.farmer-modal-open .farmer-modal-backdrop-custom {
    display: block;
}

.form-group{
    display: grid;
}

.form-group input{
    margin: 10px;
    height: 40px;
    border-radius: 5px;
    padding: 5px;
    border-color: #2D4A36;
}

.form-group label{
    margin-left: 10px;
}

.agreed-btn{
    height: 40px;
    width: 100%;
    background-color: #2D4A36;
    border-radius: 10px;
    color: white;
    border: none;
    font-size: 18px;
    font-weight: 500;
}

.agreed-btn:hover{
    background-color: #2D4A12;
}

.review-product-price, .product-price{
    font-size: 30px;
    margin-top: 5px;
}
</style>

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
            <ul class="menu-links submenu-links">
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
    <h2>Dashboard</h2>
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
    <h1>Overview</h1>
        <div class="card-container">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-count"><?php echo $chatCount; ?></h5>
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div class="card-title">
                    <h5>Chats to Answer</h5>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-count"><?php echo $averageRating ? round($averageRating, 2) : '0'; ?></h5>
                    <i class="bi bi-star"></i>
                </div>
                <div class="card-title">
                    <h5>Seller Rating</h5>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-count"><?php echo $activeProducts; ?></h5>
                    <i class="bi bi-check2-circle"></i>
                </div>
                <div class="card-title">
                    <h5>Active Products</h5>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-count"><?php echo $soldProducts; ?></h5>
                    <i class="bi bi-tag"></i>
                </div>
                <div class="card-title">
                    <h5>Sold Products</h5>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-count"><?php echo $total_clicks; ?></h5>
                    <i class="bi bi-eye"></i>
                </div>
                <div class="card-title">
                    <h5>Clicks of Products</h5>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-count"><?php echo $total_wishlist; ?></h5>
                    <i class="bi bi-heart"></i>
                </div>
                <div class="card-title">
                    <h5>Products Saves</h5>
                </div>
            </div>
        </div>

<div class="product-header">
        <h2>Your Product</h2>
        <a href="farmer-create-product.php" onclick="setCreateProductBreadcrumbs()"><button class="create-product-btn" >Create Product</button></a>
        </div>
        <div class="product-container">
    <?php
    $totalCount = 0; // Counter for total products displayed
    $hasProducts = false; // Flag to track if any product was displayed

    // Display products under review
    if ($reviewResult->num_rows > 0) {
        while ($row = $reviewResult->fetch_assoc()) {
            if ($totalCount >= 2) break; // Limit total products to 2
            $totalCount++;
            $hasProducts = true;

            $images = json_decode($row['images'], true); // Decode as associative array
            $firstImage = !empty($images) ? $images[0] : 'default.jpg'; // Fallback to a default image if none

            echo "<div class='review-product-card'>
                    <div class='review-product-img'>
                        <img class='review-card-img-top' src='../product_images/{$firstImage}' alt='Card image cap'>
                    </div>
                    <div class='review-product-card-text'>
                        <h4 class='review-product-price'>₱ {$row['product_price']}</h4>
                        <h3 class='review-product-name'>{$row['product_name']}</h3>
                        <p class='review-product-category'>{$row['product_category']}</p>
                        <p class='review-product-created'>Listed on " . date('Y-m-d h:i:s A', strtotime($row['created_at'])) . "</p>
                        <p class='review-product-status'>{$row['status']}</p>
                    </div>
                </div>";
        }
    }

    // Display approved products only if the total count is less than 2
    if ($totalCount < 2 && $approvedResult->num_rows > 0) {
        while ($row = $approvedResult->fetch_assoc()) {
            if ($totalCount >= 2) break; // Stop once total reaches 2
            $totalCount++;
            $hasProducts = true;

            $images = json_decode($row['image_url'], true); // Decode as associative array
            $firstImage = !empty($images) ? $images[0] : 'default.jpg'; // Fallback to a default image if none

            $product_id = $row['product_id'];
            $status = $row['status'];

            echo "<div class='product-card' data-product-id='{$product_id}' data-user-id='{$user_id}'>
                    <div class='product-img' style='position: relative; cursor: pointer;' onclick=\"window.location.href='Product-Information.php?product_id={$product_id}'\">
                        <img class='card-img-top' src='../product_images/{$firstImage}' alt='Card image cap'>";

            if ($status === 'sold') {
                echo "<div class='sold-overlay' style='
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background-color: rgba(0, 0, 0, 0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: white;
                        font-size: 1.5em;
                        font-weight: bold;
                        text-transform: uppercase;
                    '>Sold</div>";
            }

            echo "</div>
                    <div class='product-card-text'>
                        <h4 class='product-price'>₱ {$row['current_price']}</h4>
                        <h3 class='product-name'>{$row['product_name']}</h3>
                        <p class='product-category'>{$row['category_name']}</p>
                        <p class='product-location'>{$row['location']}</p>
                        <p class='product-created'>Listed on " . date('Y-m-d h:i:s A', strtotime($row['effective_date'])) . "</p>
                    </div>
                    <div class='product-buttons'>";

            // Mark as Available Button
            echo "<button type='button' class='markasavailable' id='markAsAvailableButton' 
                onclick='handleMarkAsAvailable($product_id)' 
                " . ($status === 'active' ? "style='display:none;'" : "") . ">
                Mark as Available
            </button>";

            // Mark as Sold Button
            echo "<button type='button' class='markassold' id='markAsSoldButton' 
                onclick='handleMarkAsSold($product_id)' 
                " . ($status === 'sold' ? "style='display:none;'" : "") . ">
                Mark as Sold
            </button>";

            // Dropdown Menu
            $productId = "dropdown-{$row['product_id']}";
            $productStatus = $row['status'];
            echo "<div class='product-menu'>
                    <i class='bi bi-three-dots' onclick='toggleDropdown(\"$productId\")'></i>
                    <div class='dropdown-menu' id='$productId'>";
            echo "<a href='#' onclick='viewProduct({$row['product_id']})'><i class='bi bi-eye dropdown-icon'></i> View</a>";
            if ($productStatus != 'sold') {
                echo "<a href='#' onclick='editProduct({$row['product_id']})'><i class='bi bi-pencil-square dropdown-icon'></i>Edit</a>";
            }
            echo "<a href='#' onclick='deleteProduct({$row['product_id']})'><i class='bi bi-trash dropdown-icon'></i>Delete</a>";
            echo "</div>
            </div>
          </div>
          </div>";
        }
    }

    // Close the statements and connection
    $reviewStmt->close();
    $approvedStmt->close();
    $conn->close();
    ?>

    <!-- Conditionally display See All Products Link or No Products Label -->
    <?php if ($hasProducts): ?>
        <div class="see-all-products">
            <a href="farmer-products.php" class="see-all-link">See All Products</a>
        </div>
    <?php else: ?>
        <div class="no-products-label">
            <p>No products available at the moment.</p>
        </div>
    <?php endif; ?>
</div>


<!-- Modal Structure for Agreed Price and Quantity -->
<div id="priceQuantityModal" class="farmer-modal" style="display: none;">
    <div class="farmer-modal-dialog">
        <div class="farmer-modal-content">
            <div class="farmer-modal-header">
                <h5 class="farmer-modal-title">Agreed Price and Quantity</h5>
                <button type="button" class="btn-close" onclick="closepriceQuantityModal()" aria-label="Close">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form id="priceQuantityForm">
                <input type="hidden" name="receiver_id" id="receiver_id" value="<?php echo htmlspecialchars($receiver_id); ?>">
                <input type="hidden" name="sender_id" id="sender_id" value="<?php echo htmlspecialchars($sender_id); ?>">
                <input type="hidden" name="product_id" id="product_id" value="">
                <div class="form-group">
                    <label for="agreedPrice" class="agreed">Agreed Price:</label>
                    <input type="number" id="agreedPrice" name="agreedPrice" placeholder="Enter the agreed price..." required>
                </div>
                <div class="form-group">
                    <label for="quantity" class="agreed">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" placeholder="Enter the agreed quantity..." required>
                </div>
                <div class="farmer-modal-footer">
                    <button type="submit" class="agreed-btn">Confirm</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <script>
        function closeModal() {
        document.getElementById('interestedBuyersModal').style.display = 'none';
        document.getElementById('modalBackdrop').style.display = 'none'; // Hide the backdrop
    }

    function closepriceQuantityModal(){
        document.getElementById('priceQuantityModal').style.display = 'none';
         // Close the interestedBuyersModal
    document.getElementById('interestedBuyersModal').style.display = 'block';
    }

    function showPriceQuantityModal(receiver_id, first_name, last_name) {
    // Set up buyer details for the modal
    document.getElementById('receiver_id').value = receiver_id;
    document.getElementById('priceQuantityModal').style.display = "block"; // Show price and quantity modal
    document.getElementById('modalBackdrop').style.display = 'block'; // Show the backdrop
     // Close the interestedBuyersModal
     document.getElementById('interestedBuyersModal').style.display = 'none';
}

function showInterestedBuyersModal(product_id) {
    if (product_id) {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "fetch-interested-buyers.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if (this.status === 200) {
                document.getElementById("modalBodyContent").innerHTML = this.responseText;
                document.getElementById("interestedBuyersModal").style.display = "block";
                document.getElementById("modalBackdrop").style.display = "block"; // Show the backdrop
                document.getElementById("product_id").value = product_id; // Assign product ID to the hidden input
            }
        };
        xhr.send("product_id=" + product_id);
    } else {
        console.error("Product ID is missing for the modal.");
    }
}


function handleMarkAsSold(product_id) {
    // Instead of updating the product status, show the interested buyers modal
    if (product_id) {
        showInterestedBuyersModal(product_id);
    } else {
        console.error("Product ID not found for Mark as Sold action.");
    }
}


// For Mark as Available with SweetAlert Confirmation
function handleMarkAsAvailable(product_id) {
    // SweetAlert2 confirmation dialog
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to mark this product as available?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, mark as available',
        cancelButtonText: 'No, cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Proceed with marking as available
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'mark_product_status.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function () {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.status === 'success') {
                            // Update buttons visibility
                            document.getElementById('markAsSoldButton').style.display = 'none';
                            document.getElementById('markAsAvailableButton').style.display = 'inline-block'; 
                            Swal.fire('Success!', response.message, 'success').then(() => {
                                // Reload the page after success
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    } catch (e) {
                        console.error("JSON Parse Error:", e);
                        Swal.fire('Error', 'Error parsing the response. See console for details.', 'error');
                    }
                } else {
                    Swal.fire('Error', 'Network error occurred.', 'error');
                }
            };

            xhr.onerror = function () {
                Swal.fire('Error', 'Network error occurred.', 'error');
            };

            // Send the product_id and action to mark it as available
            xhr.send(`product_id=${product_id}&action=available`);
        } else {
            // If user clicks cancel, show a notification
            Swal.fire('Cancelled', 'The product was not marked as available.', 'info');
        }
    });
}

function sendMessageToBuyer(ratedUserId, productId, tab) {
    const message = "Thank you for your purchase! Please rate your experience with the seller.";
    const messageType = "rate"; // Message type for rating
    const role = "seller"; // Default role for seller sending the message

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'send_message.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                console.log('Message sent successfully:', response.preview);
            } else {
                console.error('Error:', response.message);
            }
        } else {
            console.error('Error sending message to buyer.');
        }
    };

    xhr.onerror = function () {
        console.error('Network error.');
    };

    // Send the data, including the tab to decide the role
    xhr.send(`receiver_id=${ratedUserId}&product_id=${productId}&message=${encodeURIComponent(message)}&message_type=${encodeURIComponent(messageType)}&role=${encodeURIComponent(role)}&read_status=0&tab=${encodeURIComponent(tab)}`);
}

document.getElementById("priceQuantityForm").addEventListener("submit", function (event) {
    event.preventDefault();

    const agreedPrice = document.getElementById("agreedPrice").value;
    const quantity = document.getElementById("quantity").value;
    const receiver_id = document.getElementById("receiver_id").value;
    const product_id = document.getElementById("product_id").value;

    fetch("processTransaction.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            agreedPrice: agreedPrice,
            quantity: quantity,
            product_id: product_id,
            receiver_id: receiver_id
        })
    })
    .then(response => response.json())
    .then(data => {
    console.log(data); // Log the response for debugging

    if (data.duplicate) {
        Swal.fire({
            icon: "warning",
            title: "Duplicate Transaction",
            text: "This transaction already exists and cannot be submitted again."
        });
    } else if (data.success) {
        // Close the modal
        closeModal();
        closepriceQuantityModal(); // Close the agreed price and quantity modal

        // Update product status to sold
        fetch("update_product_status.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                product_id: product_id,
                status: "sold"
            })
        })
        .then(response => response.json())
        .then(statusData => {
            if (statusData.success) {
                Swal.fire({
                    icon: "success",
                    title: "Transaction Confirmed",
                    text: "The transaction has been confirmed, and the product is now marked as sold."
                }).then(() => {
                    // Send message to buyer after successful transaction confirmation
                    sendMessageToBuyer(receiver_id, product_id);
                    location.reload(); // Reload the page on "OK" click
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Status Update Error",
                    text: "Transaction confirmed, but there was an error updating the product status. Please try again."
                });
            }
        })
        .catch(error => {
            console.error("Error updating product status:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "Network error while updating product status. Please try again."
            });
        });
    } else {
        Swal.fire({
            icon: "error",
            title: "Error",
            text: "An error occurred: " + (data.error || "Please try again.")
        });
    }
})
.catch(error => {
    console.error("Error:", error);
    Swal.fire({
        icon: "error",
        title: "Error",
        text: "Network error. Please try again."
    });
});
});
</script>
    
    <div class="bootstrap-modal-scope">
        <div class="farmer-modal" id="interestedBuyersModal" tabindex="-1" role="dialog" style="display: none;">
            <div class="farmer-modal-dialog" role="document">
                <div class="farmer-modal-content">
                    <div class="farmer-modal-header">
                        <h5 class="farmer-modal-title">Mark as Sold</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeModal()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="farmer-modal-body" id="modalBodyContent">

                    </div>
                    <div class="farmer-modal-footer">
                    
                    </div>
                </div>
            </div>
        </div>

        <div class="farmer-modal-backdrop-custom" id="modalBackdrop"></div>

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
                        <i class='bx bx-camera' ></i>
                    </label>
                    <input type="file" id="profile-upload" style="display:none;" />
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
    function setCreateProductBreadcrumbs() {
        // Set breadcrumbs for Create Product page
        const breadcrumbs = [
            { name: "Dashboard", link: "Farmer.php" }, // Link to the Dashboard (Farmer.php)
            { name: "Create Product", link: "" } // Current page does not need a link
        ];

        // Store breadcrumbs in sessionStorage
        sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));
    }
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
        const file = e.target.files[0]; // Get the selected file
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                // Display the image preview
                profileImageContainer.innerHTML = `
                    <img id="profile-img-preview" src="${event.target.result}" alt="Profile Preview">
                `;
            };
            reader.readAsDataURL(file); // Read the file as a Data URL
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


// Function to toggle the visibility of the dropdown
function toggleDropdown(productId) {
    // Close any open dropdowns
    const allDropdowns = document.querySelectorAll('.dropdown-menu');
    allDropdowns.forEach(dropdown => {
        if (dropdown.id !== productId) {
            dropdown.style.display = 'none';
        }
    });

    // Toggle the clicked dropdown
    var dropdown = document.getElementById(productId);
    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
}

// Function to hide the dropdown if clicked outside
document.addEventListener('click', function(event) {
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    const productMenus = document.querySelectorAll('.product-menu');

    // Check if the click was outside any of the dropdowns or product menu icons
    let clickedInsideDropdown = false;
    dropdowns.forEach(function(dropdown) {
        if (dropdown.contains(event.target)) {
            clickedInsideDropdown = true;
        }
    });

    let clickedInsideProductMenu = false;
    productMenus.forEach(function(productMenu) {
        if (productMenu.contains(event.target)) {
            clickedInsideProductMenu = true;
        }
    });

    if (!clickedInsideDropdown && !clickedInsideProductMenu) {
        // Close all dropdowns if the click is outside any dropdown or product menu
        dropdowns.forEach(function(dropdown) {
            dropdown.style.display = 'none';
        });
    }
});



function viewProduct(productId) {
        // Set breadcrumbs for the Product Information page
        const breadcrumbs = [
            { name: "Dashboard", link: "Farmer.php" }, // Link to the Dashboard (Farmer.php)
            { name: "Product Details", link: "" } // Current page does not need a link
        ];

        // Store breadcrumbs in sessionStorage
        sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));

        // Redirect to Product-Information.php with the product_id as a query parameter
        window.location.href = `Product-Information.php?product_id=${productId}`;
    }


function editProduct(productId) {
    // Set breadcrumbs for the Product Information page
        const breadcrumbs = [
            { name: "Dashboard", link: "Farmer.php" }, // Link to the Dashboard (Farmer.php)
            { name: "Edit Product", link: "" } // Current page does not need a link
        ];

        // Store breadcrumbs in sessionStorage
        sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));

        // Redirect to Product-Information.php with the product_id as a query parameter
        window.location.href = `Edit-Product.php?product_id=${productId}`;
}

// Function to handle delete confirmation and AJAX request
function deleteProduct(productId) {
    // SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to delete the product
            var xhr = new XMLHttpRequest();
            xhr.open("GET", "delete_product.php?product_id=" + productId, true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // SweetAlert success message
                    Swal.fire(
                        'Deleted!',
                        'Your product has been deleted.',
                        'success'
                    ).then(() => {
                        // Reload the page after user clicks "Okay"
                        window.location.reload();
                    });

                    // Optionally, remove the product row from the page (before reload)
                    document.getElementById("product_" + productId).remove(); // Assuming you have an ID like "product_{product_id}" for the product row
                }
            };
            xhr.send();
        }
    });
}

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
setInterval(checkUnreadMessages, 3000); // Polling interval is now 30 seconds
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
