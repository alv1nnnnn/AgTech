<?php
session_start(); // Start session to access session variables

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect if not logged in
    exit();
}

require_once '../Connection/connection.php'; // Include your database connection file

// Prepare SQL query to retrieve profile picture URL, first name, last name, and location based on user_id
$user_id = $_SESSION['user_id'];

$sql = "SELECT profile, first_name, last_name, province, municipality, barangay FROM user WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id); // Bind user_id to the query

// Execute query
$stmt->execute();

// Bind result variables
$stmt->bind_result($profile_picture, $first_name, $last_name, $province, $municipality, $barangay);

// Fetch the result
$stmt->fetch();

// Close statement and connection
$stmt->close();
$conn->close();

// Check if profile picture URL is empty, if so, generate initial avatar
if (empty($profile_picture)) {
    // Generate an avatar using the first letter of the first name
    $initialAvatar = '<div class="avatar" style="width: 45px; height: 45px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 50px; font-size: 26px;">' . strtoupper(substr($first_name, 0, 1)) . '</div>';
    $profile_picture_html = $initialAvatar; // Assign generated avatar
} else {
    // HTML for displaying the profile picture
    $profile_picture_html = '<img src="' . htmlspecialchars($profile_picture) . '" alt="Profile Picture" class="profile-pic" id="profilePic">';
}

// Format the location
$location = htmlspecialchars($barangay . ', ' . $municipality . ', ' . $province);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Create Product</title>
    <link rel="stylesheet" href="../css/create-product.css?v=<?php echo time(); ?>"> 
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
    <div class="nav-container" style="display: none">
        <i class="bi bi-chevron-left back-btn" onclick="window.history.back();"></i>
    <h2>Create Product</h2>
    <h1 class="user-name"><?php echo $first_name . ' ' . $last_name; ?></h1>
        <div class="profile-con">
            <?php echo $profile_picture_html; ?>
        </div>
    </div>
</div>
<div class="main-container">
        <div class="create-product-container">
            <div class="create-product-header">
                <i class="bi bi-chevron-left close-icon" onclick="window.history.back();"></i>
            </div>
            <div class="profile-information">
                <div><?php echo $profile_picture_html; ?></div>
                <span class="farmer-name"><?php echo $first_name . ' ' . $last_name; ?></span>
            </div>

            <form action="submit_product.php" method="post" enctype="multipart/form-data">
    <div class="add-photos-container">
        <input type="file" id="productImage" name="productImage[]" accept="image/*" multiple>
        <div class="image-previews" id="imagePreviews"></div>
    </div>

    <h4 class="description-heading">BE DESCRIPTIVE AS POSSIBLE</h4>
    <div class="form-group">
        <input type="text" id="productNameInput" name="productName" placeholder="Product Name" required>
    </div>
    <div class="form-group">
        <div class="input-with-symbol">
            <span class="peso-symbol">₱</span>
            <input type="number" id="productPriceInput" name="productPrice" placeholder="Price" step="0.01" min="0">
        </div>
    </div>
    <div class="form-group">
        <select id="productCategoryInput" name="productCategory" required>
            <option value="" disabled selected>Select Category</option>
            <option value="Swine">Swine</option>
            <option value="Poultry">Poultry</option>
            <option value="Chicken">Chicken</option>
        </select>
    </div>
    <div class="form-group" id="unitContainer">
    <select id="productUnitInput" name="productUnit" required>
        <option value="" disabled selected>Select Unit</option> <!-- Placeholder option -->
        <!-- Units will be dynamically populated here -->
    </select>
</div>
    <div class="form-group">
        <textarea id="productDescriptionInput" name="productDescription" placeholder="Description" required></textarea>
    </div>
    <div class="form-group">
        <input type="text" id="productLocationInput" name="productLocation" value="<?php echo $location; ?>"></input>
    </div>

    <div class="form-group button-container">
        <button type="submit" class="submit-btn" id="submitBtn">Create</button>
    </div>
</form>
        </div>

        <div class="preview-container">
            <h2>Preview</h2>
            <div class="product-preview">
                <div class="image-display">
                    <img id="mainImage" class="main-image" src="">
                    <span class="image-label" id="imageLabel">Main Product Image</span>
                    <button class="arrow left-arrow"><i class="bi bi-caret-left-fill"></i></button>
                    <button class="arrow right-arrow"><i class="bi bi-caret-right-fill"></i></button>
                        <div id="thumbnailList" class="thumbnail-list"><img src="" class="thumbnail" alt="Thumbnail Image" style="display:none;"></div>
                        </div>
                        <div class="right-side">
        <header>
            <h4>Farmer Information</h4>
            <h6>Farmer Details</h6>
        </header>
        <div class="profile">
                <div><?php echo $profile_picture_html; ?></div>
            <p><?php echo $first_name . ' ' . $last_name; ?></p>
        </div>
        <div class="message-form">
            <!-- If no message has been sent, show the form to send a message and save div -->
            <form action="farmer-inbox.php" method="post" class="message-form-container">
                <input type="text" name="message" class="message" value="I'm interested in this product." readonly />
                <button type="submit" class="button send-message" disabled>Send</button>
            </form>
            </div>
            <div class="product-info">
            <p id="previewProductName">Sample Product Name</p>
    <p><strong>Price:</strong> ₱ <span id="previewProductPrice"></span></p> <!-- Update this line -->
    <p><strong>Address:</strong> <span id="previewProductLocation">Sample Location</span></p> <!-- Update this line -->
    <p><strong>Details:</strong></p>
    <div class="description-box">
        <p id="previewProductDescription">Sample description here.</p> <!-- Update this line -->
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
                <p id="previewLocation">Purok 3 Busay Daraga, Albay</p>
            </div>
        </div>
    </div>
</section>

<script src="../js/createproduct.js"></script>

<script>
    document.getElementById('productCategoryInput').addEventListener('change', function() {
        const category = this.value;
        const unitContainer = document.getElementById('unitContainer');
        const unitSelect = document.getElementById('productUnitInput');

        // Clear previous options
        unitSelect.innerHTML = '';

        if (category === 'Swine' || category === 'Chicken') {
            unitContainer.style.display = 'block';
            // Add unit options for Swine and Chicken
            const units = ['kg', 'piece'];
            units.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit;
                option.textContent = unit;
                unitSelect.appendChild(option);
            });
        } else if (category === 'Poultry') {
            unitContainer.style.display = 'block';
            // Add unit options for Poultry
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
const productImageInput = document.getElementById('productImage');
const imagePreviews = document.getElementById('imagePreviews');

// Main display elements for image preview
const mainImageDisplay = document.getElementById('mainImage');
const thumbnailList = document.getElementById('thumbnailList');
const imageLabel = document.getElementById('imageLabel'); // Get the span element
let uploadedImages = [];
let currentImageIndex = 0;

// Prevent triggering file input when clicking the remove button
imagePreviews.addEventListener('click', function (e) {
    if (e.target.classList.contains('remove-btn')) {
        return;
    }
    productImageInput.click();
});

productImageInput.addEventListener('change', function () {
    const files = Array.from(this.files); // Get newly selected files

    // Validate if any files are selected
    if (files.length === 0) {
        console.error("No files selected.");
        Swal.fire({
            title: 'No files selected!',
            text: 'Please choose an image.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    // Check if adding these files will exceed the limit
    if (uploadedImages.length + files.length > 10) {
        Swal.fire({
            title: 'Image Limit Exceeded!',
            text: 'You can only upload a maximum of 10 images.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }

    const validImages = files.filter((file) => file.type.startsWith('image/')); // Filter for valid images

    // Check if there are any valid images
    if (validImages.length === 0) {
        console.error("No valid images uploaded.");
        Swal.fire({
            title: 'Invalid File!',
            text: 'Only image files are allowed. Please select valid image files.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }

    imageLabel.style.display = 'none'; // Hide the label since files are being uploaded

    validImages.forEach((file) => {
        const reader = new FileReader();
        reader.onload = function (event) {
            const imageSrc = event.target.result;

            // Ensure the image is only added once to the array
            if (!uploadedImages.includes(imageSrc)) {
                uploadedImages.push(imageSrc);

                // Display the first uploaded image as the main image by default
                if (uploadedImages.length === 1) {
                    mainImageDisplay.src = imageSrc;
                    currentImageIndex = 0;
                }

                // Create and append a new thumbnail
                const thumbnail = document.createElement('img');
                thumbnail.src = imageSrc;
                thumbnail.classList.add('thumbnail');
                thumbnail.alt = `Thumbnail ${uploadedImages.length}`;
                thumbnail.addEventListener('click', () => showImage(uploadedImages.indexOf(imageSrc)));
                thumbnailList.appendChild(thumbnail);

                // Add image preview with remove button
                const imgElement = document.createElement('div');
                imgElement.classList.add('image-preview');
                imgElement.innerHTML = ` 
                    <img src="${imageSrc}" alt="Product Image">
                    <button class="remove-btn">&times;</button>
                `;
                imagePreviews.insertBefore(imgElement, imagePreviews.querySelector('.add-photo-btn'));

                // Add remove functionality
                imgElement.querySelector('.remove-btn').addEventListener('click', function (e) {
                    e.stopPropagation();
                    const indexToRemove = uploadedImages.indexOf(imageSrc);
                    removeImage(indexToRemove);
                    imgElement.remove();
                });

                // Dynamically create hidden input for each image
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'productImage[]'; // This is the same name as the form field
                hiddenInput.value = imageSrc; // Store the image data as the value
                document.querySelector('form').appendChild(hiddenInput); // Append to the form
            }
        };
        reader.readAsDataURL(file);
    });
});


// Function to display image in main display by index
function showImage(index) {
    if (index >= 0 && index < uploadedImages.length) {
        mainImageDisplay.src = uploadedImages[index];
        currentImageIndex = index;
        imageLabel.style.display = 'none'; // Hide label when an image is displayed
    }
}

// Navigation for left and right arrows
document.querySelector('.left-arrow').addEventListener('click', () => {
    currentImageIndex = (currentImageIndex - 1 + uploadedImages.length) % uploadedImages.length;
    showImage(currentImageIndex);
});

document.querySelector('.right-arrow').addEventListener('click', () => {
    currentImageIndex = (currentImageIndex + 1) % uploadedImages.length;
    showImage(currentImageIndex);
});

// Function to remove image from previews and update main display
function removeImage(index) {
    // Remove the image from uploadedImages array
    uploadedImages.splice(index, 1);
    thumbnailList.innerHTML = ''; // Clear thumbnails

    // Rebuild the thumbnails and set main image if necessary
    uploadedImages.forEach((img, i) => {
        const thumbnail = document.createElement('img');
        thumbnail.src = img;
        thumbnail.classList.add('thumbnail');
        thumbnail.alt = `Thumbnail ${i + 1}`;
        thumbnail.addEventListener('click', () => showImage(i));
        thumbnailList.appendChild(thumbnail);
    });

    // If the removed image was the currently displayed one
    if (index === currentImageIndex) {
        currentImageIndex = (currentImageIndex >= uploadedImages.length) ? 0 : currentImageIndex; // Reset index if it goes out of bounds
        if (uploadedImages.length > 0) {
            mainImageDisplay.src = uploadedImages[currentImageIndex]; // Show the next image
        } else {
            mainImageDisplay.src = ''; // Clear the display if no images are left
            imageLabel.style.display = 'block'; // Show label if no images are available
        }
    }
}

// Function to add the "Add Photo" button
function addAddPhotoBtn() {
    const addPhotoBtn = document.createElement('div');
    addPhotoBtn.classList.add('add-photo-btn');
    addPhotoBtn.innerHTML = '<i class="bi bi-file-earmark-plus"></i> Add photo';
    imagePreviews.appendChild(addPhotoBtn);
}

addAddPhotoBtn();
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