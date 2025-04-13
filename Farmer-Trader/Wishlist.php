<?php
session_start(); // Start session to access session variables

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login.php");
    exit();
}

// Database connection details
require_once '../Connection/connection.php';

// Prepare SQL query to retrieve profile picture URL and first name based on user_id
$user_id = $_SESSION['user_id'];
$sql = "SELECT profile, first_name, last_name FROM user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);

// Execute query
$stmt->execute();

// Bind result variables
$stmt->bind_result($profile_picture, $first_name, $last_name);

// Fetch the result
$stmt->fetch();

// Close statement
$stmt->close();

// Check if profile picture URL is empty, if so, generate initial avatar
if (empty($profile_picture)) {
    $initialAvatar = '<div class="avatar" style="width: 40px; height: 40px; background-color: #2D4A36; color: white; border-radius: 50%; text-align: center; line-height: 45px; font-size: 26px;">' . strtoupper(substr($first_name, 0, 1)) . '</div>';
    // Assign generated avatar to $profile_picture
    $profile_picture_html = $initialAvatar;
} else {
    // Variable to hold the profile picture HTML
    $profile_picture_html = '<img src="' . $profile_picture . '" alt="Profile Picture" class="profile-pic" id="profilePic">';
}

// Query to count unread messages
$query = "SELECT COUNT(*) AS unread_count FROM chat WHERE receiver_id = ? AND read_status = 'unread'";
$stmtchatcount = $conn->prepare($query);
$stmtchatcount->bind_param("i", $user_id); // Bind user_id parameter
$stmtchatcount->execute();
$result = $stmtchatcount->get_result();
$row = $result->fetch_assoc();
$unread_count = $row['unread_count'];

// Close statement for unread count
json_encode(['status' => 'success', 'unread_count' => $unread_count]);
$stmtchatcount->close();

// SQL query to fetch product data including the image URL
$sql = "SELECT 
            p.product_id,
            p.product_name,
            pp.current_price,
            p.location,
            pi.image_url
        FROM 
            products p
        JOIN 
            productprices pp ON p.product_id = pp.product_id
        JOIN 
            productimages pi ON p.product_id = pi.product_id
        WHERE 
            pp.effective_date = (
                SELECT MAX(effective_date) 
                FROM productprices 
                WHERE product_id = p.product_id
            )
        GROUP BY 
            p.product_id
        ORDER BY 
            pi.image_id ASC;";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Wishlist</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/wishlist.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/shop.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
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
            <h2>Wishlist</h2>
            <div class="profile-con profile-icon" id="profileIcon">
            <?php echo $profile_picture_html; ?>
    <div class="dropdown-menu-custom" id="profileDropdown">
            <a href="#" class="dropdown-item-custom" id="open-profile">
                <div class="dropdown-btn">
                <i class="bi bi-person-circle profile-action"></i>
                <p>Profile</p>
                </div>
            </a>
            <a href="#" class="dropdown-item-custom">
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

    <div class="wishlist-container">
<?php
$sql_wishlist = "
    SELECT 
        w.wishlist_id, 
        wi.product_id, 
        p.product_name, 
        pp.current_price, 
        pi.image_url, 
        p.location, 
        c.category_name
    FROM 
        wishlist w
    JOIN 
        wishlist_item wi ON w.wishlist_id = wi.wishlist_id
    JOIN 
        products p ON wi.product_id = p.product_id
    JOIN 
        productprices pp ON p.product_id = pp.product_id
    JOIN 
        productimages pi ON p.product_id = pi.product_id
    JOIN 
        category c ON p.category_id = c.category_id
    WHERE 
        w.user_id = ? AND 
        wi.status = 'active' AND 
        pp.effective_date = (
            SELECT MAX(effective_date) 
            FROM productprices 
            WHERE product_id = p.product_id
        )
";

$stmt_wishlist = $conn->prepare($sql_wishlist);
$stmt_wishlist->bind_param("i", $user_id);
$stmt_wishlist->execute();
$result_wishlist = $stmt_wishlist->get_result();

if ($result_wishlist->num_rows > 0) {
    while ($wishlist_item = $result_wishlist->fetch_assoc()) {
        $images = json_decode($wishlist_item['image_url'], true);
        $firstImage = !empty($images) ? $images[0] : 'default.jpg';

       echo '<div class="wishlist-card" data-wishlist-id="' . $wishlist_item['wishlist_id'] . '" data-product-id="' . $wishlist_item['product_id'] . '" onclick="redirectToProduct(' . $wishlist_item['product_id'] . ')">
        <img src="../product_images/' . $firstImage . '" alt="' . $wishlist_item['product_name'] . '" class="wishlist-img">
        <div class="wishlist-details">
            <h5 class="wishlist-price">₱ ' . $wishlist_item['current_price'] . '</h5>
            <p class="wishlist-product-name">' . $wishlist_item['product_name'] . '</p>
            <p class="wishlist-category-name">' . $wishlist_item['category_name'] . '</p>
            <p class="wishlist-product-location">' . $wishlist_item['location'] . '</p>
        </div>
        <button class="remove-wishlist-btn" onclick="event.stopPropagation(); removeFromWishlist(' . $wishlist_item['wishlist_id'] . ', ' . $wishlist_item['product_id'] . ')">
            <i class="bi bi-x-circle"></i>
        </button>
    </div>';
    }
} else {
    echo '<p>No active wishlist items found.</p>';
}

$stmt_wishlist->close();
?>
</div>


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

            // Populate address
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
function removeFromWishlist(wishlistId, productId) {
    // Send a POST request to the backend
    fetch('wishlist_handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ wishlist_id: wishlistId, product_id: productId }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                // Find and remove the corresponding wishlist card
                const card = document.querySelector(`.wishlist-card[data-product-id="${productId}"]`);
                if (card) {
                    card.remove(); // Removes the card from the DOM
                }
            } else {
                alert(data.message); // Show error message
            }
        })
        .catch((error) => console.error('Error:', error));
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
const body = document.querySelector('body'),
    sidebar = body.querySelector('nav'),
    toggle = body.querySelector(".toggle"),
    navLinks = body.querySelectorAll('.menu-links .nav-link'); // Select all nav links

// Toggle sidebar on toggle button click
toggle.addEventListener("click", (event) => {
    event.stopPropagation(); // Prevent the event from bubbling up
    sidebar.classList.toggle("close");
});

// Loop through nav links to apply event listeners
navLinks.forEach(link => {
    link.addEventListener('click', (event) => {
        // Prevent sidebar toggle when clicking nav links
        event.stopPropagation(); // Prevent the click event from bubbling up
        
        // Open sidebar if it is closed
        if (sidebar.classList.contains("close")) {
            sidebar.classList.remove("close");
        }

        // Special case for "All Products" and "Chats" links
        if (link.firstElementChild.href.includes("Shop.php") || link.firstElementChild.href.includes("farmer-inbox.php")) {
            event.preventDefault(); // Prevent default navigation
            window.location.href = link.firstElementChild.href; // Navigate to respective page
        }
    });
});

// Prevent sidebar from closing when clicking inside the sidebar
sidebar.addEventListener('click', (event) => {
    event.stopPropagation();
});

// Highlight the active nav link
document.addEventListener("DOMContentLoaded", function() {
    const currentLocation = window.location.href; // Get the current page URL

    navLinks.forEach(link => {
        // Check if the link's href matches the current location
        if (link.firstElementChild.href === currentLocation) {
            link.classList.add('active'); // Add 'active' class to the current page
        }
    });
});

// Logout confirmation
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

// Handle screen size adjustments
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

// Show/hide submenu on sell nav link click
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
document.addEventListener('DOMContentLoaded', function() {
    // Select all product cards
    const productCards = document.querySelectorAll('.product-card');

    // Add click event listener to each card
    productCards.forEach(card => {
        card.addEventListener('click', function() {
            // Get the product ID and user ID from the data attributes
            const productId = this.getAttribute('data-product-id');
            const userId = this.getAttribute('data-user-id');
            
            // Redirect to Product-Information.php with both product_id and user_id as query parameters
            window.location.href = 'Product-Information.php?product_id=' + productId + '&user_id=' + userId;
        });
    });
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

<script>
    function redirectToProduct(productId) {
    // Redirect to the product information page
    window.location.href = 'Product-Information.php?product_id=' + productId;
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const storedBreadcrumbs = JSON.parse(sessionStorage.getItem('breadcrumbs'));

    // If no breadcrumbs are stored, set a default breadcrumb trail for the Marketplace page
    if (!storedBreadcrumbs) {
        const breadcrumbs = [
            { name: "Marketplace", link: "Market.php" },
            { name: "Product Details", link: "" } // Current page does not need a link
        ];
        sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));
    } else {
        // If breadcrumbs are already set, check the referrer to adjust them based on the page the user came from
        const currentUrl = window.location.href;
        const referrer = document.referrer;

        if (referrer.includes("Wishlist.php")) {
            // If user came from Wishlist page, set breadcrumbs accordingly
            const breadcrumbs = [
                { name: "Wishlist", link: "Wishlist.php" },
                { name: "Product Details", link: "" } // Current page does not need a link
            ];
            sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));
        } else if (referrer.includes("Dashboard-Farmer.php")) {
            const breadcrumbs = [
                { name: "Dashboard", link: "Dashboard-Farmer.php" },
                { name: "Product Details", link: "" }
            ];
            sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));
        } else if (referrer.includes("Market.php")) {
            const breadcrumbs = [
                { name: "Marketplace", link: "Market.php" },
                { name: "Product Details", link: "" }
            ];
            sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));
        }
    }

    // Render the breadcrumbs on the page
    const breadcrumbsContainer = document.createElement('div');
    breadcrumbsContainer.classList.add('breadcrumbs');
    const breadcrumbs = JSON.parse(sessionStorage.getItem('breadcrumbs')) || [];

    breadcrumbs.forEach((crumb, index) => {
        const link = document.createElement('a');
        link.textContent = crumb.name;

        if (crumb.link) {
            link.href = crumb.link; // Make clickable
            link.style.color = "#007bff";
        } else {
            link.style.pointerEvents = 'none'; // Current page is not clickable
            link.style.color = '#555';
        }

        breadcrumbsContainer.appendChild(link);

        // Add separator if not the last breadcrumb
        if (index < breadcrumbs.length - 1) {
            const separator = document.createTextNode(" > ");
            breadcrumbsContainer.appendChild(separator);
        }
    });

    // Insert breadcrumbs before the main container
    const mainContainer = document.querySelector('.main-container');
    if (mainContainer) {
        mainContainer.parentNode.insertBefore(breadcrumbsContainer, mainContainer);
    }
});

// Function to navigate to the product details page and update breadcrumbs
function redirectToProduct(productId) {
    // Set breadcrumbs for navigating from Wishlist
    const breadcrumbs = [
        { name: "Wishlist", link: "Wishlist.php" },
        { name: "Product Details", link: "" }
    ];

    // Store updated breadcrumbs in sessionStorage
    sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));

    // Redirect to the product details page
    window.location.href = `Product-Information.php?product_id=${productId}`;
}

</script>
</body>
</html>