<?php
session_start(); // Start session to access session variables

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login/Login.php");
    exit();
}

// Check if product_id is provided via GET request
if (!isset($_GET['product_id'])) {
    die("Product not found.");
}

$product_id = intval($_GET['product_id']);

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
    $initialAvatar = '<div class="avatar" style="width: 40px; height: 40px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 50px; font-size: 26px;">' . strtoupper(substr($first_name, 0, 1)) . '</div>';
    // Assign generated avatar to $profile_picture
    $profile_picture_html = $initialAvatar;
} else {
    // Variable to hold the profile picture HTML
    $profile_picture_html = '<img src="' . $profile_picture . '" alt="Profile Picture" class="profile-pic" id="profilePic">';
}


// Prepare SQL query to retrieve product details and owner profile information
$sql = "SELECT 
            p.product_name, 
            pp.current_price, 
            p.location, 
            pi.image_url, 
            pd.description, 
            pd.unit, 
            u.user_id AS owner_user_id,
            u.first_name AS owner_first_name, 
            u.last_name AS owner_last_name, 
            u.profile AS owner_profile_picture, 
            c.category_name
        FROM 
            products p
        JOIN 
            productprices pp ON p.product_id = pp.product_id
        JOIN 
            productimages pi ON p.product_id = pi.product_id
        JOIN 
            productdetails pd ON p.product_id = pd.product_id
        JOIN 
            userproducts up ON p.product_id = up.product_id
        JOIN 
            user u ON up.user_id = u.user_id
        JOIN 
            category c ON p.category_id = c.category_id  -- Added category table join
        WHERE 
            p.product_id = ?
        AND 
            pp.effective_date = (
                SELECT MAX(effective_date) 
                FROM productprices 
                WHERE product_id = p.product_id
            )
        ORDER BY 
            pi.image_id ASC 
        LIMIT 1";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($product_name, $current_price, $location, $image_url, $description, $unit, $owner_user_id, $owner_first_name, $owner_last_name, $owner_profile_picture, $category);
$stmt->fetch();
$stmt->close();

// Decode the image URLs if they are stored as JSON or a comma-separated string
$image_urls = [];
if (!empty($image_url)) {
    // Attempt to decode as JSON
    $decoded_images = json_decode($image_url, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $image_urls = $decoded_images;
    } else {
        // Fallback to comma-separated string
        $image_urls = explode(',', $image_url);
    }
}

// Retrieve user_id from the userproducts table
$query = "SELECT user_id FROM userproducts WHERE product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$stmt->bind_result($product_owner_user_id);
$stmt->fetch();
$stmt->close();

// Check if product owner user_id was retrieved
if (!isset($product_owner_user_id)) {
    $product_owner_user_id = null; // Set to null if not set
}

// Prepare SQL query to check if a message has been sent for this product
$query = "SELECT COUNT(*) FROM chat WHERE product_id = ? AND sender_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($message_sent);
$stmt->fetch();
$stmt->close();

$message_sent = $message_sent > 0; // true if a message has been sent, false otherwise

$tab = isset($_GET['tab']) ? htmlspecialchars($_GET['tab']) : 'buying';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Product Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/product-information.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/farmer.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
    <!-- Include SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
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


<section id="product-details" class="section product-details">
<div class="close-btn">
<i class="bi bi-chevron-left" onclick="goBackToMarket();"></i>
<h4>Product Details</h4>
</div>
<i class="bi bi-chevron-left close-icon" onclick="window.history.back();"></i>
    <div class="main-container">
        <div class="product-image">
    <button class="arrow-left" onclick="showPreviousImage()" id="arrow-left">
        <i class="bi bi-caret-left-fill"></i>
    </button>
    <!-- Display the first image as the main image -->
    <?php if (!empty($image_urls) && isset($image_urls[0])): ?>
        <img src="../product_images/<?php echo htmlspecialchars(trim($image_urls[0])); ?>" alt="Product Image" class="main-image" id="main-image">
    <?php else: ?>
        <img src="../product_images/default-image.png" alt="Default Image" class="main-image" id="main-image">
    <?php endif; ?>
    <button class="arrow-right" onclick="showNextImage()" id="arrow-right">
        <i class="bi bi-caret-right-fill"></i>
    </button>
</div>
<div class="product-thumbnails">
    <!-- Display all images as thumbnails -->
    <?php if (!empty($image_urls)): ?>
        <?php foreach ($image_urls as $index => $url): ?>
            <img src="../product_images/<?php echo htmlspecialchars(trim($url)); ?>" alt="Thumbnail" class="thumbnail" id="thumbnail-<?php echo $index; ?>" onclick="showImage(<?php echo $index; ?>)">
        <?php endforeach; ?>
    <?php else: ?>
        <!-- Default thumbnail if no images -->
        <img src="../product_images/default-image.png" alt="Default Thumbnail" class="thumbnail" id="thumbnail-0">
    <?php endif; ?>
</div>

    </div>
    <div class="right-side">
        <header>
            <h4>Farmer Information</h4>
            <h6 id="farmer-details-btn">Farmer Details</h6>
        </header>
        <div class="profile">
            <?php if (empty($owner_profile_picture)): ?>
                <div class="farmer-avatar" style="width: 45px; height: 45px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 50px; font-size: 26px;">
                    <?php echo strtoupper(substr($owner_first_name, 0, 1)); ?>
                </div>
            <?php else: ?>
                <img src="<?php echo $owner_profile_picture; ?>" alt="Profile Picture" class="profile-pic">
            <?php endif; ?>
            <p><?php echo $owner_first_name . " " . $owner_last_name; ?></p>
        </div>

        <?php
// Include your database connection file
require_once '../Connection/connection.php'; 

// Fetch the user_id from the session
$logged_in_user_id = $_SESSION['user_id'];

// Function to check if the logged-in user is the owner of the product
function isProductOwner($product_id, $logged_in_user_id, $conn) {
    $query = "SELECT p.status 
              FROM products p 
              JOIN userproducts up ON p.product_id = up.product_id 
              WHERE p.product_id = ? AND up.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $product_id, $logged_in_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['status'] : null; // Return status if found, else null
}

// Assuming you have the product_id available
$product_id = $_GET['product_id']; // Adjust this to your product ID retrieval method

// Get product status if the user is the owner
$productStatus = isProductOwner($product_id, $logged_in_user_id, $conn);


if ($productStatus !== null): ?>
    <!-- Show actions if the logged-in user is the product owner -->
    
    <div class="owner-actions">
        <form action="mark-as-sold.php" method="post" id="markAsSoldForm" style="display: inline;">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <input type="hidden" name="selected_buyer_id" id="selectedBuyerId">
        </form>

         <!-- Mark as Sold Button -->
<button type="button" class="button mark-as-sold" id="markAsSoldButton" onclick="handleMarkAsSold(<?php echo $product_id; ?>)" 
    <?php echo ($productStatus === 'sold') ? 'style="display:none;"' : ''; ?>>
    Mark as Sold
</button>

<!-- Mark as Available Button -->
<button type="button" class="button mark-as-available" id="markAsAvailableButton" onclick="handleMarkAsAvailable(<?php echo $product_id; ?>)" 
    <?php echo ($productStatus === 'active') ? 'style="display:none;"' : ''; ?>>
    Mark as Available
</button>

        <div class="product-action">
            <a href="javascript:void(0);" class="button edit-product" onclick="navigateToEdit('<?php echo $product_id; ?>')">Edit</a>
            <a href="javascript:void(0);" class="button delete-product" onclick="confirmDelete('<?php echo $product_id; ?>')">Delete</a>
        </div>
    </div>

    <div class="bootstrap-modal-scope">
        <div class="modal" id="interestedBuyersModal" tabindex="-1" role="dialog" style="display: none;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Mark as Sold</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="closeModal()">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <div class="modal-body" id="modalBodyContent">

                    </div>
                    <div class="modal-footer">
                    
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-backdrop-custom" id="modalBackdrop"></div>

        <!-- Modal Structure for Agreed Price and Quantity -->
<div id="priceQuantityModal" class="modal" style="display: none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agreed Price and Quantity</h5>
                <button type="button" class="btn-close" onclick="closepriceQuantityModal()" aria-label="Close">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <form id="priceQuantityForm">
                <input type="hidden" name="receiver_id" id="receiver_id" value="<?php echo htmlspecialchars($receiver_id); ?>">
                <input type="hidden" name="sender_id" id="sender_id" value="<?php echo htmlspecialchars($sender_id); ?>">
                <input type="hidden" name="product_id" id="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                <div class="form-group">
                    <label for="agreedPrice" class="agreed">Agreed Price:</label>
                    <input type="number" id="agreedPrice" name="agreedPrice" placeholder="Enter the agreed price..." required>
                </div>
                <div class="form-group">
                    <label for="quantity" class="agreed">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" placeholder="Enter the agreed quantity..." required>
                </div>
                <div class="modal-footer">
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
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "fetch-interested-buyers.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                if (this.status === 200) {
                    document.getElementById("modalBodyContent").innerHTML = this.responseText;
                    document.getElementById("interestedBuyersModal").style.display = "block";
                    document.getElementById('modalBackdrop').style.display = 'block'; // Show the backdrop
                }
            };
            xhr.send("product_id=" + product_id);
        }
        

function handleMarkAsSold(product_id) {
    // Instead of updating the product status, show the interested buyers modal
    showInterestedBuyersModal(product_id);
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
<?php else: ?>


    <!-- If the logged-in user is not the product owner, handle messaging and save div -->
    <div class="profile-links">
        <?php if ($message_sent): ?>
            <!-- If a message has been sent, show the "Message already sent" text and save div -->
            <div class="message-sent <?php echo $message_sent ? '' : 'hidden'; ?>">
                <p>Message already sent.</p>
                <?php 
// Fetch the receiver's name from the database using the product owner's user ID
// Assume you already have $product_owner_user_id
$query = "SELECT first_name, last_name FROM user WHERE user_id = $product_owner_user_id";
$result = mysqli_query($conn, $query);

if ($result && $row = mysqli_fetch_assoc($result)) {
    $first_name = $row['first_name']; // Receiver's first name
    $last_name = $row['last_name'];  // Receiver's last name
}

// Prepare data for the redirect
$redirectData = [
    'user_id' => $product_owner_user_id, // Receiver ID
    'product_id' => $product_id,         // Product ID
    'first_name' => $first_name,         // Receiver's first name
    'last_name' => $last_name            // Receiver's last name
];

// Encode the data for URL
$encodedData = base64_encode(json_encode($redirectData));
?>
<a href="farmer-inbox.php?tab=buying&data=<?php echo $encodedData; ?>" class="button go-to-conversation">Go to Conversation</a>

            </div>
        <?php else: ?>
            <div class="message-form">
            <!-- If no message has been sent, show the form to send a message and save div -->
            <form action="farmer-inbox.php" method="post" class="message-form-container">
                <input type="hidden" name="receiver_id" value="<?php echo $product_owner_user_id; ?>">
                <input type="hidden" name="sender_id" value="<?php echo $_SESSION['user_id']; ?>">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                <input type="hidden" name="tab" value="<?php echo $tab; ?>">
                <input type="text" name="message" class="message" value="I'm interested in this product." required />
                <button type="submit" class="button send-message">Send</button>
            </form>
            </div>
        <?php endif; ?>
        <div class="save">
        <!-- Send Offer Button -->
        <div class="send-offer <?php echo $product_owner_user_id == $_SESSION['user_id'] ? 'hidden' : ''; ?>" 
     data-product-id="<?php echo $product_id; ?>" 
     data-product-owner-id="<?php echo $product_owner_user_id; ?>">
    <i class='fas fa-hand-holding'></i><i class="fa-solid fa-coins"></i>
</div>

<script>
        // Move the SweetAlert code here
        <?php if (!$product_name) { ?>
            Swal.fire({
                icon: 'error',
                title: 'Product Not Found',
                text: 'The product you are looking for is no longer available.',
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    history.back(); // Go back to the previous page
                }
            });
        <?php } ?>
    </script>

         <!-- Bootstrap Modal for Sending Offer -->
        <div class="modal fade" id="offerModal" tabindex="-1" aria-labelledby="offerModalLabel" aria-hidden="true" style="display: none;">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="offerModalLabel">Send Offer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"><i class='bi bi-x'></i></button>
                    </div>
                    <div class="modal-body">
                        <form id="offerForm" method="POST" action="send_offer.php">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $product_owner_user_id; ?>">
                            
                            <label for="priceOffer">Your Offer:</label>
                            <div class="input-with-symbol">
                                <span class="peso-symbol">₱</span>
                            <input type="number" id="priceOffer" name="price_offer" class="form-control" min="0"placeholder="00.00" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="send-offer-btn" form="offerForm">Send Offer</button>
                    </div>
                </div>
            </div>
        </div>


<?php
        // Assuming you have retrieved the wishlist status for the product
        $isInWishlist = false; // Change this based on your logic
        $statusClass = ''; // Initialize class for the heart icon

        // Check if the user has the product in their wishlist
        $stmt = $conn->prepare("SELECT status FROM wishlist w
                                JOIN wishlist_item wi ON w.wishlist_id = wi.wishlist_id
                                WHERE w.user_id = ? AND wi.product_id = ?");
        $stmt->bind_param("ii", $_SESSION['user_id'], $product_id);

        // Error handling for execution
        if (!$stmt->execute()) {
            echo "SQL Error: " . $stmt->error;
            exit;
        }

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $isInWishlist = true;
            $statusClass = $row['status'] === 'active' ? 'bi-heart-fill' : 'bi-heart'; // Set the class based on the status
        } else {
            // Default to bi-heart if not in wishlist
            $statusClass = 'bi-heart';
        }

        $stmt->close();
        ?>

        <div class="heart-icon">
            <i class="bi <?php echo $statusClass; ?>" id="wishlist-heart" onclick="toggleWishlist(<?php echo $product_id; ?>)"></i>
        </div>

    </div>
</div>
<?php endif; ?>

        <div class="product-info">
    <h4><?php echo $product_name; ?></h4>
    <p><strong>Price:</strong> ₱ <?php echo number_format($current_price, 0, '.', ','); ?> | <?php echo $unit; ?></p>
    <p><strong>Address:</strong> <?php echo $location; ?></p>
    <p><strong>Category:</strong> <?php echo $category; ?></p>
    <!-- Display Product Status -->
    <?php 
    if ($productStatus !== null): 
        // Adjust color and text based on product status
        $statusColor = '';
        $statusText = ucfirst($productStatus);
    
        if ($productStatus === 'sold') {
            $statusColor = 'red';
        } elseif ($productStatus === 'active') {
            $statusColor = 'green';
            $statusText = 'Available'; // Change text for inactive
        }
    ?>
        <p><strong>Status:</strong> <span style="color: <?php echo $statusColor; ?>;"><?php echo $statusText; ?></span></p>
    <?php endif; ?>
    <p><strong>Details:</strong></p>
    <div class="description-box">
        <p><?php echo $description; ?></p>
    </div>

    <?php
    // Check if the logged-in user owns the product
    $ownershipQuery = "
        SELECT user_id 
        FROM userproducts 
        WHERE product_id = ? AND user_id = ?
    ";
    $stmtOwnership = $conn->prepare($ownershipQuery);
    $stmtOwnership->bind_param("ii", $product_id, $user_id);
    $stmtOwnership->execute();
    $stmtOwnership->store_result();
    
    if ($stmtOwnership->num_rows > 0): // User owns the product
        ?>
        <div class="interested-people">
            <div class="interested-people-header">
            <h5>People Interested in this Product</h5>
            <a href="farmer-inbox.php" class="all-chat-link">See all chat</a>
            </div>
<ul class="people">
    <?php
    // Query to fetch users interested in the specific product
    $interestedPeopleQuery = "
        SELECT p.user_id, p.first_name, p.last_name, p.profile, c.message, MAX(c.sent_at) AS last_message_time
        FROM user p
        JOIN chat c ON c.sender_id = p.user_id
        WHERE c.product_id = ? AND c.role = 'buyer'
        GROUP BY p.user_id
        ORDER BY last_message_time DESC
    ";
    $stmtInterested = $conn->prepare($interestedPeopleQuery);
    $stmtInterested->bind_param("i", $product_id);
    $stmtInterested->execute();
    $resultInterested = $stmtInterested->get_result();

    while ($person = $resultInterested->fetch_assoc()) {
        $personName = htmlspecialchars($person['first_name']) . ' ' . htmlspecialchars($person['last_name']);
        $profileImage = $person['profile'];
        $lastMessage = htmlspecialchars($person['message']);
        $lastMessageTime = date('h:i A', strtotime($person['last_message_time']));
        $userId = $person['user_id'];
        $firstName = htmlspecialchars($person['first_name']);
        $lastName = htmlspecialchars($person['last_name']);
    ?>
        <li class="person" onclick="redirectToInbox(<?php echo $userId; ?>, '<?php echo urlencode($firstName); ?>', '<?php echo urlencode($lastName); ?>', <?php echo $product_id; ?>)">
            <?php if (!empty($profileImage)): ?>
            <picture>
                <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="Profile Picture" class="profile-image">
                </picture>
            <?php else: ?>
                <div class="avatar-image" style="width: 50px; height: 50px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 50px; font-size: 26px;">
                    <?php echo strtoupper(substr($personName, 0, 1)); ?>
                </div>
            <?php endif; ?>
            <span class="user-name"><?php echo $personName; ?></span>
            <span class="last-message"><?php echo $lastMessage; ?></span>
            <span class="message-time"><?php echo $lastMessageTime; ?></span>
        </li>
    <?php
    }
    $stmtInterested->close();
    ?>
</ul>

<script>
    function redirectToInbox(userId, firstName, lastName, productId) {
        // Create an object to hold the parameters
        const data = { user_id: userId, first_name: firstName, last_name: lastName, product_id: productId };
        
        // Convert the data object to a JSON string
        const jsonData = JSON.stringify(data);

        // Base64 encode the JSON string
        const encodedData = btoa(jsonData); // `btoa` is a JavaScript function that encodes data to Base64
        
        // Redirect to the inbox page with the encoded data
        window.location.href = `farmer-inbox.php?data=${encodeURIComponent(encodedData)}`;
    }
</script>

        </div>
        <?php
    endif;
    $stmtOwnership->close();
    ?>

    <div class="map-box">
        <div id="map" style="width: 100%; height: 140px; border-radius: 10px;"></div>
        <h5>Location</h5>
        <p><?php echo $location; ?></p>
    </div>
</div>

    
<!-- Modal for displaying farmer details -->
<div id="farmer-modal" class="farmer-modal-container">
    <div class="modal-content">
        <button class="close-button" id="close-modal">&times;</button>
        <div id="profile-container">
            <div class="profile-header">
                <div id="profile-container" class="profile-image-container">
                    <img id="profile-img" alt="Profile Image" style="display: none;">
                    <div id="profile-avatar" style="display: none;"></div>
                </div>
                <div class="profile-info">
                    <h2 id="profile-name"></h2> <!-- Seller Name -->
                    <div class="total-rating">
                        <span id="total-rating-stars"></span> <!-- Star icons will be inserted here -->
                        <span id="total-rating-value">(0.0)</span> <!-- Numeric value of the total rating -->
                    </div>
                </div>
            </div>
        </div>

        <hr class="info-divider">

        <div class="ratings-header">
            <h3>Seller Ratings</h3>
        </div>

        <div id="ratings-container">
            <!-- Ratings will be dynamically added here -->
        </div>
        <button id="see-all-ratings-btn" style="display: none;">See All Ratings</button>

        <hr class="info-divider">

        <div class="products-header">
            <h3>Products Available</h3>
        </div>

        <div id="products-container">
            <!-- Products will be dynamically added here -->
        </div>
    </div>
</div>
</section>

<style>
    .thumbnail {
        border: 2px solid transparent; /* Default border */
        cursor: pointer;
    }
    .thumbnail.active {
        border: 2px solid green !important; /* Active border color */
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const previews = document.querySelectorAll('.last-message');

    const adjustPreviewLength = () => {
        const screenWidth = window.innerWidth;

        previews.forEach(preview => {
            const fullText = preview.textContent;

            // Adjust substring length based on screen width
            if (screenWidth <= 868) {
                preview.textContent = fullText.length > 15 ? fullText.substr(0, 15) + '...' : fullText;
            } else {
                preview.textContent = fullText.length > 30 ? fullText.substr(0, 30) + '...' : fullText;
            }
        });
    };

    // Initial adjustment and on window resize
    adjustPreviewLength();
    window.addEventListener('resize', adjustPreviewLength);
});

</script>

<script>
    const farmerDetailsBtn = document.getElementById("farmer-details-btn");
    const farmerModal = document.getElementById("farmer-modal");
    const closeModalBtn = document.getElementById("close-modal");

    const profileImage = document.getElementById("profile-img");
    const profileAvatar = document.getElementById("profile-avatar");
    const profileName = document.getElementById("profile-name");
    const ratingsContainer = document.getElementById("ratings-container");
    const seeAllRatingsBtn = document.getElementById("see-all-ratings-btn");
    const productsContainer = document.getElementById("products-container");

    let allRatings = [];

    const calculateTotalRating = (ratings) => {
    if (!ratings || ratings.length === 0) return 0; // Return 0 if there are no ratings

    // Calculate the average rating
    const total = ratings.reduce((sum, rating) => sum + parseFloat(rating.rating_value || 0), 0);
    return (total / ratings.length).toFixed(1); // Keep one decimal place
};

const displayTotalRating = (ratings) => {
    const totalRatingValue = calculateTotalRating(ratings); // Calculate the total rating
    const totalRatingStars = document.getElementById("total-rating-stars");
    const totalRatingValueElement = document.getElementById("total-rating-value");

    // Clear previous stars
    totalRatingStars.innerHTML = "";

    // Generate stars
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement("span");
        star.textContent = "★"; // Use star icon
        if (i <= Math.floor(totalRatingValue)) {
            // Fully shaded stars
            star.style.color = "#FFD700"; // Gold for fully filled
        } else if (i - 1 < totalRatingValue && totalRatingValue < i) {
            // Partially shaded star
            const percentage = (totalRatingValue - (i - 1)) * 100; // Calculate percentage fill
            star.style.background = `linear-gradient(to right, #FFD700 ${percentage}%, #CCCCCC ${percentage}%)`;
            star.style.color = "transparent"; // Hide the text color for gradient effect
            star.style.webkitBackgroundClip = "text"; // Apply the gradient fill to text
        } else {
            // Unshaded stars
            star.style.color = "#CCCCCC"; // Grey for unfilled stars
        }
        totalRatingStars.appendChild(star);
    }

    // Update the numeric value
    totalRatingValueElement.textContent = `(${totalRatingValue})`;
};

    const displayLimitedRatings = (ratings, limit = 2) => {
        ratingsContainer.innerHTML = ""; // Clear previous ratings
        ratings.slice(0, limit).forEach(rating => {
            const ratingItem = document.createElement("div");
            ratingItem.classList.add("rating-item");

            const ratingValue = parseFloat(rating.rating_value) || 0;

            // Create star rating display
            const starsContainer = document.createElement("div");
            starsContainer.classList.add("stars-container");

            for (let i = 1; i <= 5; i++) {
                const star = document.createElement("span");
                star.classList.add("star-icon");
                star.innerHTML = "★"; // Use the solid star icon
                star.style.color = i <= ratingValue ? "#FFD700" : "#CCCCCC"; // Gold for filled, grey for empty
                starsContainer.appendChild(star);
            }

            ratingItem.innerHTML = `
                <div class="rating-header">
                        <strong>Rating:</strong> (${rating.rating_value || "0.0"})
                    </div>
            `;
            ratingItem.appendChild(starsContainer);
            ratingItem.innerHTML += `
                <div class="rating-comment">
                    <strong>Comment:</strong> ${rating.comment || "No comment provided"}
                </div>
            `;

            ratingsContainer.appendChild(ratingItem);
        });

        seeAllRatingsBtn.style.display = ratings.length > limit ? "block" : "none";
    };

    const displayProducts = (products) => {
    productsContainer.innerHTML = ""; // Clear previous products
    
    if (products && products.length > 0) {
        products.forEach(product => {
            // Create a new div to hold the product details
            const productItem = document.createElement("div");
            productItem.classList.add("product-item");

            // Set up the product card as a clickable link
            productItem.innerHTML = `
                <a href="Product-Information.php?product_id=${product.product_id}" class="product-card-info" style="text-decoration: none; color: inherit;">
                    <img src="${product.image_url}" alt="${product.product_name}" class="product_image">
                    <div class="product-info-item">
                        <p>₱${product.current_price || "N/A"}</p>
                        <h4>${product.product_name}</h4>
                        <p>${product.category_name || "N/A"}</p>
                    </div>
                </a>
            `;

            // Append the created product item to the products container
            productsContainer.appendChild(productItem);
        });
    } else {
        productsContainer.innerHTML = "<p>No products available for this seller.</p>";
    }
};

    
    const setProfile = (profile, fullName) => {
        if (profile.startsWith("<div")) {
            // If profile is an avatar, insert it as HTML and hide the image
            profileAvatar.innerHTML = profile;
            profileAvatar.style.display = "block";
            profileImage.style.display = "none";
        } else {
            // If profile is an image URL, set it and hide the avatar
            profileImage.src = profile;
            profileImage.style.display = "block";
            profileAvatar.style.display = "none";
        }
    };


    const showModal = () => {
        const productId = new URLSearchParams(window.location.search).get("product_id");
        if (productId) {
            fetch(`fetch_farmer_details.php?product_id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }

                    profileName.textContent = data.full_name || "N/A";

                    // Use the function to set the profile
                    setProfile(data.profile, data.full_name);

                    allRatings = data.ratings || [];
                    displayLimitedRatings(allRatings);

                    // Display total rating using fetched ratings
                    displayTotalRating(data.ratings || []);
            
                    displayProducts(data.products || []);
                    farmerModal.style.display = "block";
                })
                .catch(err => {
                    console.error(err);
                    alert("Failed to load farmer details.");
                });
        } else {
            alert("Product ID not found.");
        }
    };

    const hideModal = () => farmerModal.style.display = "none";

    farmerDetailsBtn.addEventListener("click", showModal);
    closeModalBtn.addEventListener("click", hideModal);

    window.addEventListener("click", (event) => {
        if (event.target === farmerModal) hideModal();
    });

    seeAllRatingsBtn.addEventListener("click", () => displayLimitedRatings(allRatings, allRatings.length));
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

        if (referrer.includes("Farmer.php")) {
            // If user came from Dashboard, set breadcrumbs accordingly
            const breadcrumbs = [
                { name: "Dashboard", link: "Farmer.php" },
                { name: "Product Details", link: "" } // Current page does not need a link
            ];
            sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));
        } else if (referrer.includes("Market.php")) {
            // If user came from Marketplace, set breadcrumbs for Marketplace
            const breadcrumbs = [
                { name: "Marketplace", link: "Market.php" },
                { name: "Product Details", link: "" } // Current page does not need a link
            ];
            sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));
        } else if (referrer.includes("Edit-Product.php")) {
            // If the user came from Edit-Product, reset the breadcrumb trail to only show Product Details
            const breadcrumbs = [
                { name: "Marketplace", link: "Market.php" },
                { name: "Product Details", link: "" } // Current page does not need a link
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

// Function to navigate to the edit page and update breadcrumbs
function navigateToEdit(productId) {
    const referrer = document.referrer;

    // Default breadcrumb if coming from Marketplace
    let breadcrumbs = [
        { name: "Marketplace", link: "Market.php" },
        { name: "Product Details", link: `Product-Information.php?product_id=${productId}` },
        { name: "Edit Product", link: "" } // Current page does not need a link
    ];

    // Check if user came from Dashboard and adjust breadcrumbs
    if (referrer.includes("Farmer.php")) {
        breadcrumbs = [
            { name: "Dashboard", link: "Farmer.php" },
            { name: "Product Details", link: `Product-Information.php?product_id=${productId}` },
            { name: "Edit Product", link: "" }
        ];
    }

    // Store updated breadcrumbs in sessionStorage
    sessionStorage.setItem('breadcrumbs', JSON.stringify(breadcrumbs));

    // Redirect to the Edit-Product page
    window.location.href = `Edit-Product.php?product_id=${productId}`;
}
</script>

<script>
    function goBackToMarket() {
    // Save the current scroll position in localStorage
    localStorage.setItem('scrollPosition', window.scrollY);
    
    // Navigate back to Market.php
    window.location.href = 'Market.php';
}

// On the Market.php page, restore the scroll position when the page loads
window.onload = function() {
    // Check if a scroll position is stored in localStorage
    const scrollPosition = localStorage.getItem('scrollPosition');
    
    // If a scroll position is found, scroll to that position
    if (scrollPosition) {
        window.scrollTo(0, parseInt(scrollPosition));
        // Clear the scroll position from localStorage after restoring it
        localStorage.removeItem('scrollPosition');
    }
};
</script>

<script>
// Function to get the product_id from the URL
function getProductIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('product_id');
}

async function loadProductLocation() {
    const productId = getProductIdFromUrl();

    if (!productId) {
        return;
    }

    try {
        // Fetch product location based on product_id
        const response = await fetch(`fetch_coordinates.php?product_id=${productId}`);
        const data = await response.json();

        if (data.error) {
            return;
        }

        const { location } = data;

        if (!location) {
            return;
        }

        // Geocode the product location to get coordinates
        const geoResponse = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(location)}`);
        const geoData = await geoResponse.json();

        if (geoData && geoData.length > 0) {
            const { lat, lon } = geoData[0];

            // Initialize the Leaflet map (centered around the product's location)
            const map = L.map('map').setView([lat, lon], 13); // Adjust the zoom level as needed

            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Add a marker for the product location
            L.marker([lat, lon]).addTo(map)
                .bindPopup(`<b>Location:</b> ${location}`)
                .openPopup();
        } else {
            
        }
    } catch (error) {
        console.error('Error loading product location:', error);
    }
}

// Call the function to load the product location and map
window.onload = loadProductLocation;
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
    let imageIndex = 0;
    const imageUrls = <?php echo json_encode($image_urls); ?>;
    const mainImage = document.getElementById('main-image');
    const arrowLeft = document.getElementById('arrow-left');
    const arrowRight = document.getElementById('arrow-right');

    function updateArrows() {
        arrowLeft.style.display = imageIndex === 0 ? 'none' : 'block';
        arrowRight.style.display = imageIndex === imageUrls.length - 1 ? 'none' : 'block';
    }

    function updateThumbnails() {
        document.querySelectorAll('.thumbnail').forEach(thumbnail => {
            thumbnail.classList.remove('active');
        });
        const currentThumbnail = document.getElementById(`thumbnail-${imageIndex}`);
        if (currentThumbnail) {
            currentThumbnail.classList.add('active');
        }
    }

    function showImage(index) {
        imageIndex = index;
        mainImage.src = '../product_images/' + imageUrls[imageIndex].trim();
        updateArrows();
        updateThumbnails();
    }

    function showNextImage() {
        if (imageUrls.length === 0) return;
        imageIndex = (imageIndex + 1) % imageUrls.length;
        mainImage.src = '../product_images/' + imageUrls[imageIndex].trim();
        updateArrows();
        updateThumbnails();
    }

    function showPreviousImage() {
        if (imageUrls.length === 0) return;
        imageIndex = (imageIndex - 1 + imageUrls.length) % imageUrls.length;
        mainImage.src = '../product_images/' + imageUrls[imageIndex].trim();
        updateArrows();
        updateThumbnails();
    }

    updateArrows();
    updateThumbnails();
</script>

<script>
function toggleWishlist(productId) {
    const heartIcon = document.getElementById('wishlist-heart');

    // Determine if the heart is filled or not and toggle the class accordingly
    let newStatus = heartIcon.classList.contains('bi-heart') ? 'active' : 'inactive';
    
    // Toggle the heart icon
    if (newStatus === 'active') {
        heartIcon.classList.remove('bi-heart');
        heartIcon.classList.add('bi-heart-fill');
    } else {
        heartIcon.classList.remove('bi-heart-fill');
        heartIcon.classList.add('bi-heart');
    }

    // Send AJAX request to update the wishlist status
    fetch('update_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_id: <?php echo $_SESSION['user_id']; ?>,
            product_id: productId,
            status: newStatus // Set status as 'active' or 'inactive'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Wishlist updated to ' + newStatus);
        } else {
            console.error('Error updating wishlist status:', data.message);
            // Revert icon change if failed
            heartIcon.classList.toggle('bi-heart');
            heartIcon.classList.toggle('bi-heart-fill');
        }
    })
    .catch(error => console.error('Error:', error));
}
</script>

<script>
// Show Bootstrap Modal
document.querySelector('.send-offer').addEventListener('click', function() {
    var offerModal = new bootstrap.Modal(document.getElementById('offerModal'));
    offerModal.show();
});
</script>

<script>
document.querySelectorAll('.send-offer').forEach(element => {
    element.addEventListener('click', function() {
        // Get the product ID and the offer price
        const productId = this.getAttribute('data-product-id');
        const priceOffer = document.getElementById('price_offer').value;

        // Check if both productId and priceOffer are valid
        if (!productId || !priceOffer) {
            
            return;
        }

        // First, store the product ID in the session using an AJAX request
        fetch('set_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ product_id: productId }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // If product ID was successfully stored in session, proceed to send the offer
                sendOffer(productId, priceOffer);
            } else {
                console.error(data.message);
                alert("Failed to store product ID in session.");
            }
        })
        .catch(error => console.error('Error setting session:', error));
    });
});

// Function to send the offer
function sendOffer(productId, priceOffer) {
    // Prepare the data to be sent in the offer request
    const offerData = {
        product_id: productId,
        price_offer: priceOffer
    };

    // Send the offer request using another AJAX call
    fetch('send_offer.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(offerData),
    })
    .then(response => response.text())
    .then(data => {
        // Display the server response (success or error message)
        alert(data); // Replace with your handling logic (e.g., update the UI)
    })
    .catch(error => console.error('Error sending offer:', error));
}

function confirmDelete(productId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete the product.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request
            fetch(`delete_product.php?product_id=${productId}`, { method: 'GET' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: data.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Redirect to Dashboard after deletion is successful
                            window.location.href = data.redirect; // Redirect to Dashboard.php
                        });

                        // Optionally, remove the product row or card from the UI
                        const productElement = document.getElementById(`product-${productId}`);
                        if (productElement) productElement.remove();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message || 'An error occurred while deleting the product.',
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'Could not connect to the server.',
                        icon: 'error',
                        confirmButtonText: 'Try Again'
                    });
                });
        }
    });
}
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