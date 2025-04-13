<?php
        session_start(); // Start session to access session variables

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: login.php");
            exit();
        }

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
            $initialAvatar = '<div class="avatar" style="width: 45px; height: 45px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 50px; font-size: 26px;">' . strtoupper(substr($first_name, 0, 1)) . '</div>';
            // Assign generated avatar to $profile_picture
            $profile_picture_html = $initialAvatar;
        } else {
            // Variable to hold the profile picture HTML
            $profile_picture_html = '<img src="' . $profile_picture . '" alt="Profile Picture" class="nav-profile-pic" id="profilePic">';
        }

        if (isset($_GET['product_id'])) {
            $product_id = $_GET['product_id'];
            $user_id = $_SESSION['user_id'];
        
            // Fetch product data along with prices, images, and details, validating against user
            $sql = "
                SELECT 
                    p.product_id,
                    p.product_name,
                    p.category_id,
                    p.location,
                    p.status,
                    
                    pp.price_id,
                    pp.current_price,
                    pp.previous_price,
                    pp.is_active,
                    pp.effective_date,
                    
                    pi.image_id,
                    pi.image_url,
                    
                    pd.detail_id,
                    pd.description,
                    pd.created_at
                    
                FROM 
                    userproducts up
                
                INNER JOIN 
                    products p ON up.product_id = p.product_id
                
                LEFT JOIN 
                    productprices pp ON p.product_id = pp.product_id
                
                LEFT JOIN 
                    productimages pi ON p.product_id = pi.product_id
                
                LEFT JOIN 
                    productdetails pd ON p.product_id = pd.product_id
                
                WHERE 
                    up.user_id = ? AND p.product_id = ?";
        
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
        
            // Check if product data exists
            if ($result->num_rows > 0) {
                $productData = [];
                
                while ($row = $result->fetch_assoc()) {
                    $productData['product_id'] = $row['product_id'];
                    $productData['product_name'] = $row['product_name'];
                    $productData['category_id'] = $row['category_id'];
                    $productData['location'] = $row['location'];
                    $productData['status'] = $row['status'];
                    
                    $productData['prices'][] = [
                        'price_id' => $row['price_id'],
                        'current_price' => $row['current_price'],
                        'previous_price' => $row['previous_price'],
                        'is_active' => $row['is_active'],
                        'effective_date' => $row['effective_date']
                    ];
                    
                    $productData['images'][] = [
                        'image_id' => $row['image_id'],
                        'image_url' => $row['image_url']
                    ];
                    
                    $productData['details'][] = [
                        'detail_id' => $row['detail_id'],
                        'description' => $row['description'],
                        'created_at' => $row['created_at']
                    ];
                }
        
            } else {
                echo "Product not found or you do not have access to this product.";
                exit();
            }
        
            $stmt->close();
            $conn->close();
        } else {
            header("Location: Farmer.php");
            exit();
        }
        ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - AgTech</title>
    <link rel="stylesheet" href="../css/create-product.css?v=<?php echo time(); ?>"> 
    <link rel="stylesheet" href="../css/profile.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <a href="farmer-inbox.php">
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
    <div class="nav-container"style="display: none;">
            <i class="bi bi-chevron-left back-btn" onclick="window.history.back();"></i>
            <h2>Edit Product</h2>
        <h1 class="user-name"><?php echo $first_name . ' ' . $last_name; ?></h1>
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
    <div class="create-product-container">
        <div class="create-product-header">
            <i class="bi bi-chevron-left close-icon" onclick="window.history.back();"></i>
        </div>
        <div class="profile-information">
            <?php echo $profile_picture_html; ?>
            <span class="farmer-name"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
        </div>

        <form action="update_product.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productData['product_id']); ?>">

            <div class="add-photos-container">
            <div class="image-previews" id="imagePreviews">
    <?php 
    // Decode JSON image URL string and prepend '../product_images/' path
    if (!empty($productData['images']) && is_array($productData['images'])):
        foreach ($productData['images'] as $image):
            // Decode JSON if image_url is stored as a JSON-encoded array
            $decoded_images = json_decode($image['image_url'], true);
            if (is_array($decoded_images)):
                foreach ($decoded_images as $img_name):
                    $image_path = '../product_images/' . htmlspecialchars($img_name);
                    // Display each image
                    if (file_exists($image_path)): ?>
                        <div class="image-preview">
                            <img src="<?php echo $image_path; ?>" alt="Product Image" class="product-image">
                            <button type="button" class="remove-btn" onclick="removeFetchedImage('<?php echo $img_name; ?>')">&times;</button>
                        </div>
                    <?php endif; 
                endforeach;
            endif;
        endforeach;
    else: ?>
        <p>No images available for this product.</p>
    <?php endif; ?>
</div>

    <input type="file" id="productImage" name="productImage[]" accept="image/*" multiple>
</div>


            <h4 class="description-heading">BE DESCRIPTIVE AS POSSIBLE</h4>
            
            <div class="form-group">
                <input type="text" id="productNameInput" name="productName" value="<?php echo htmlspecialchars($productData['product_name']); ?>" required>
            </div>

            <!-- Price Display -->
            <div class="form-group">
                <?php foreach ($productData['prices'] as $price): ?>
                    <div class="input-with-symbol">
                        <span class="peso-symbol">₱</span>
                            <input type="hidden" name="price_id" value="<?php echo htmlspecialchars($price['price_id']); ?>">
                <?php endforeach; ?>
                <input type="number" id="productPriceInput" name="productPrice" step="0.01" value="<?php echo htmlspecialchars($price['current_price']); ?>" placeholder="Update Price">
                                </div>
            </div>

            <div class="form-group">
                <select id="productCategoryInput" name="productCategory" required>
                    <option value="Swine" <?php if ($productData['category_id'] == "Swine") echo "selected"; ?>>Swine</option>
                    <option value="Poultry" <?php if ($productData['category_id'] == "Poultry") echo "selected"; ?>>Poultry</option>
                    <option value="Chicken" <?php if ($productData['category_id'] == "Chicken") echo "selected"; ?>>Chicken</option>
                </select>
            </div>
            
            <div class="form-group" id="unitContainer">
                <select id="productUnitInput" name="productUnit" required>
                    <option value="" disabled selected>Select Unit</option> <!-- Placeholder option -->
                    <!-- Units will be dynamically populated here -->
                </select>
            </div>

            <div class="form-group">
                <?php foreach ($productData['details'] as $detail): ?>
                    <textarea id="productDescriptionInput" name="productDescription" required><?php echo htmlspecialchars($detail['description']); ?></textarea>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <input type="text" id="productLocationInput" name="productLocation" value="<?php echo htmlspecialchars($productData['location']); ?>" required>
            </div>

            <div class="form-group button-container">
                <button type="submit" class="submit-btn">Update</button>
            </div>
        </form>
    </div>

    <div class="preview-container">
        <h2>Preview</h2>
        <div class="product-preview">
        <div class="image-display">
    <?php 
    if (!empty($productData['images']) && is_array($productData['images'])):
        // Decode JSON of the first image's URL array
        $decoded_images = json_decode($productData['images'][0]['image_url'], true);
        
        // Display the main image if decoding was successful and array is not empty
        if (is_array($decoded_images) && !empty($decoded_images)):
            $main_image_path = '../product_images/' . htmlspecialchars($decoded_images[0]); ?>
            <img id="mainImage" class="main-image" src="<?php echo $main_image_path; ?>" alt="Main Product Image">
            <button class="arrow left-arrow"><i class="bi bi-caret-left-fill"></i></button>
            <button class="arrow right-arrow"><i class="bi bi-caret-right-fill"></i></button>
            
            <div id="thumbnailList" class="thumbnail-list">
                <?php 
                // Loop through decoded images to generate thumbnails
                foreach ($decoded_images as $img_name): 
                    $thumbnail_path = '../product_images/' . htmlspecialchars($img_name);
                    if (file_exists($thumbnail_path)): ?>
                        <img src="<?php echo $thumbnail_path; ?>" class="thumbnail" alt="Thumbnail Image">
                    <?php endif;
                endforeach; ?>
            </div>
        <?php else: ?>
            <p>No images available for this product.</p>
        <?php endif;
    else: ?>
        <p>No images available for this product.</p>
    <?php endif; ?>
</div>

<div class="right-side">
    <header>
        <h4>Farmer Information</h4>
        <h6>Farmer Details</h6>
    </header>
    <div class="profile">
        <?php if (empty($profile_picture)): ?>
            <!-- If no profile picture, show initials as avatar -->
            <div class="farmer-avatar" style="width: 45px; height: 45px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 50px; font-size: 26px;">
                <p><?php echo strtoupper(substr($first_name, 0, 1)); ?></p>
            </div>
        <?php else: ?>
            <!-- If profile picture is available -->
            <div><img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture" class="profile-pic"></div>
        <?php endif; ?>
        <p><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></p>
    </div>
    
    <div class="message-form">
        <!-- Message form with disabled send button, as per original code -->
        <form action="farmer-inbox.php" method="post" class="message-form-container">
            <input type="text" name="message" class="message" value="I'm interested in this product." readonly />
            <button type="submit" class="button send-message" disabled>Send</button>
        </form>
    </div>
    
    <div class="product-info">
        <p id="previewProductName"><?php echo htmlspecialchars($productData['product_name']); ?></p>
        <p><strong>Price:</strong> ₱ <span id="previewProductPrice"><?php echo htmlspecialchars($productData['prices'][0]['current_price']); ?></span></p>
        <p><strong>Address:</strong> <span id="previewProductLocation"><?php echo htmlspecialchars($productData['location']); ?></span></p>
        <p><strong>Details:</strong></p>
        <div class="description-box">
            <p id="previewProductDescription"><?php echo htmlspecialchars($productData['details'][0]['description']); ?></p>
        </div>
    </div>
    
    <div class="map-box">
                <div class="map">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d159731.46987054707!2d123.7305!3d13.1378!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x33b4e90eaf154f81%3A0x39c740d77ea1d7c7!2sLegazpi+City%2C+Albay%2C+Philippines!5e0!3m2!1sen!2sus!4v1644977372242"
                        width="100%"
                        height="140px"
                        frameborder="0"
                        style="border:0; pointer-events: none;" <!-- Add pointer-events: none; here -->
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                </div>
        <h5>Location</h5>
        <p id="previewLocation"><?php echo htmlspecialchars($productData['location']); ?></p>
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
    document.addEventListener('DOMContentLoaded', function() {
    // This function updates the unit options based on the selected category
    function updateUnits() {
        const category = document.getElementById('productCategoryInput').value;
        const unitContainer = document.getElementById('unitContainer');
        const unitSelect = document.getElementById('productUnitInput');

        // Clear previous options
        unitSelect.innerHTML = '';

        // Handle units based on selected category
        if (category === 'Swine' || category === 'Chicken') {
            unitContainer.style.display = 'block';
            const units = ['kg', 'piece'];
            units.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit;
                option.textContent = unit;
                unitSelect.appendChild(option);
            });
        } else if (category === 'Poultry') {
            unitContainer.style.display = 'block';
            const units = ['per tray', 'piece'];
            units.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit;
                option.textContent = unit;
                unitSelect.appendChild(option);
            });
        } else {
            unitContainer.style.display = 'none';
        }

        // Set the selected unit if it's already set in the product data
        const selectedUnit = '<?php echo htmlspecialchars($productData["unit_id"] ?? ""); ?>'; // Ensure unit_id exists in productData
        if (selectedUnit) {
            unitSelect.value = selectedUnit;
        }
    }

    // Update the units based on the initial category selection
    updateUnits();

    // Add event listener to update units if the category is changed
    document.getElementById('productCategoryInput').addEventListener('change', updateUnits);
});
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    const mainContainer = document.querySelector('.main-container');

    if (mainContainer) {
        const breadcrumbsContainer = document.createElement('div');
        breadcrumbsContainer.classList.add('breadcrumbs');

        // Retrieve breadcrumbs from sessionStorage
        const breadcrumbs = JSON.parse(sessionStorage.getItem('breadcrumbs')) || [];

        breadcrumbs.forEach((crumb, index) => {
            const link = document.createElement('a');
            link.textContent = crumb.name;

            if (crumb.link) {
                link.href = crumb.link;
                link.style.color = "#007bff";
            } else {
                link.style.pointerEvents = 'none';
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
        mainContainer.parentNode.insertBefore(breadcrumbsContainer, mainContainer);
    } else {
        console.error("Main container not found");
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
const productImageInput = document.getElementById('productImage');
const imagePreviews = document.getElementById('imagePreviews');
const mainImageDisplay = document.getElementById('mainImage');
const thumbnailList = document.getElementById('thumbnailList');
let uploadedImages = [];
let currentImageIndex = 0;

// When clicking on the add-photo container, trigger file input
imagePreviews.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-btn')) {
        return;
    }
    productImageInput.click();
});

// Handling file input change (adding new images)
productImageInput.addEventListener('change', function() {
    const files = Array.from(this.files);
    
    files.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function(event) {
            uploadedImages.push(event.target.result); // Add image to uploaded images array
            
            // Display the first uploaded image as main image by default
            if (uploadedImages.length === 1) {
                mainImageDisplay.src = event.target.result;
                currentImageIndex = 0;
            }
            
            // Create a new thumbnail
            const thumbnail = document.createElement('img');
            thumbnail.src = event.target.result;
            thumbnail.classList.add('thumbnail');
            thumbnail.alt = `Thumbnail ${uploadedImages.length}`;
            thumbnail.addEventListener('click', () => showImage(uploadedImages.length - 1)); // Add click functionality to switch main image
            thumbnailList.appendChild(thumbnail);
            
            // Add image preview with remove button
            const imgElement = document.createElement('div');
            imgElement.classList.add('image-preview');
            imgElement.innerHTML = `
                <img src="${event.target.result}" alt="Product Image">
                <button class="remove-btn">&times;</button>
            `;
            imagePreviews.insertBefore(imgElement, imagePreviews.querySelector('.add-photo-btn'));
            
            // Add event listener to the remove button
            imgElement.querySelector('.remove-btn').addEventListener('click', function(e) {
                e.stopPropagation(); // Stop propagation to prevent triggering other events
                const indexToRemove = uploadedImages.indexOf(event.target.result);
                removeImage(indexToRemove);
                imgElement.remove();
            });
        };
        reader.readAsDataURL(file); // Read the file as a data URL
    });
});

// Function to display image in the main image display area by index
function showImage(index) {
    if (index >= 0 && index < uploadedImages.length) {
        mainImageDisplay.src = uploadedImages[index];
        currentImageIndex = index;
    }
}

function removeFetchedImage(imageName) {
    // Remove the image from the image-preview container
    const imageElement = document.querySelector(`.image-preview img[src*="${imageName}"]`).parentElement;
    if (imageElement) {
        imageElement.remove();
    }

    // Remove the image from the thumbnail-list
    const thumbnail = document.querySelector(`.thumbnail[src*="${imageName}"]`);
    if (thumbnail) {
        thumbnail.remove();
    }

    // Check if the image to be removed is the main image in the image-display
    const mainImage = document.getElementById('mainImage');
    if (mainImage && mainImage.src.includes(imageName)) {
        // Find the next image to display, if available
        const remainingThumbnails = document.querySelectorAll('.thumbnail');
        if (remainingThumbnails.length > 0) {
            // Set the main image to the first available thumbnail
            mainImage.src = remainingThumbnails[0].src;
        } else {
            // If no images remain, clear the main image
            mainImage.src = '';
        }
    }
}

function removeImage(index) {
    // Remove image from uploadedImages array
    uploadedImages.splice(index, 1);

    // Rebuild the thumbnails
    thumbnailList.innerHTML = '';
    uploadedImages.forEach((img, i) => {
        const thumbnail = document.createElement('img');
        thumbnail.src = img;
        thumbnail.classList.add('thumbnail');
        thumbnail.alt = `Thumbnail ${i + 1}`;
        thumbnail.addEventListener('click', () => showImage(i)); // Add click functionality to show image
        thumbnailList.appendChild(thumbnail);
    });

    // If the removed image was the currently displayed one, update the main image
    if (index === currentImageIndex) {
        currentImageIndex = (currentImageIndex >= uploadedImages.length) ? 0 : currentImageIndex;
        if (uploadedImages.length > 0) {
            mainImageDisplay.src = uploadedImages[currentImageIndex];
        } else {
            mainImageDisplay.src = ''; // Clear the main image if no images are left
        }
    }
}

// Add the "Add Photo" button at the end of the image previews container
function addAddPhotoBtn() {
    const addPhotoBtn = document.createElement('div');
    addPhotoBtn.classList.add('add-photo-btn');
    addPhotoBtn.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Add photo';
    imagePreviews.appendChild(addPhotoBtn);
}

addAddPhotoBtn();

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
</body>
</html>
