<?php
date_default_timezone_set('Asia/Manila');

        session_start(); // Start session to access session variables

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../Login/Login.php");
            exit();
        }

        require_once '../Connection/connection.php';

        // Get logged-in user's ID
        $user_id = $_SESSION['user_id'];

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
        if ($stmt->fetch()){
            $user_name = $first_name . ' ' . $last_name; 
        };

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
    
        $role = ''; // Determine if user is 'buyer' or 'seller'
    $receiver_name = '';
    $product_name = '';
    $chats = []; 
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0; // Default value if not set
    
// Fetch the logged-in user's name
$sender_name = $first_name . ' ' . $last_name;

// Fetch the chat participants and their details
$uniqueSendersSql = "
    SELECT DISTINCT 
        CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS participant_id,
        product_id,
        MAX(sent_at) AS latest_message_time
    FROM chat
    WHERE sender_id = ? OR receiver_id = ?
    GROUP BY participant_id, product_id
    ORDER BY latest_message_time DESC";

$stmtSenders = $conn->prepare($uniqueSendersSql);
$stmtSenders->bind_param("iii", $user_id, $user_id, $user_id);
$stmtSenders->execute();
$resultSenders = $stmtSenders->get_result();

$participants = [];
while ($row = $resultSenders->fetch_assoc()) {
    $participant_id = $row['participant_id'];
    $product_id = $row['product_id'];
    $latest_message_time = $row['latest_message_time'];

    $userSql = "SELECT first_name, last_name, profile FROM user WHERE user_id = ?";
    $stmtUser = $conn->prepare($userSql);
    $stmtUser->bind_param("i", $participant_id);
    $stmtUser->execute();
    $stmtUser->bind_result($p_first_name, $p_last_name, $p_profile);

    if ($stmtUser->fetch()) {
        $participants[] = [
            'id' => $participant_id,
            'name' => $p_first_name . ' ' . $p_last_name,
            'profile' => $p_profile,
            'product_id' => $product_id,
            'latest_message_time' => $latest_message_time
        ];
    }
    $stmtUser->close();
}
$stmtSenders->close();


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Assuming session is already started
    $sender_id = $_SESSION['user_id'];
    $receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if (!empty(trim($_POST['message']))) {
        $message = trim($_POST['message']);

        // Validate product_id
        $productCheckSql = "SELECT COUNT(*) FROM products WHERE product_id = ?";
        $stmtProductCheck = $conn->prepare($productCheckSql);
        $stmtProductCheck->bind_param("i", $product_id);
        $stmtProductCheck->execute();
        $stmtProductCheck->bind_result($productExists);
        $stmtProductCheck->fetch();
        $stmtProductCheck->close();

        if ($productExists > 0) {
            // Get the first_name and last_name of the receiver (user) from the user table
            $getUserNameSql = "SELECT first_name, last_name FROM user WHERE user_id = ?";
            $stmtGetUserName = $conn->prepare($getUserNameSql);
            $stmtGetUserName->bind_param("i", $receiver_id);
            $stmtGetUserName->execute();
            $stmtGetUserName->bind_result($first_name, $last_name);
            $stmtGetUserName->fetch();
            $stmtGetUserName->close();

            // Ensure we have first_name and last_name
            if (!$first_name || !$last_name) {
                echo "Error: User details not found.";
                exit;
            }

            // Set the message type
            $message_type = 'inquiry'; // Set the message type to 'inquiry'

            // Insert chat message
            $insertChatSql = "INSERT INTO chat (sender_id, receiver_id, product_id, message, sent_at, role, message_type)
                              VALUES (?, ?, ?, ?, NOW(), ?, ?)";
            $stmtInsertChat = $conn->prepare($insertChatSql);
            if ($stmtInsertChat) {
                $stmtInsertChat->bind_param("iiisss", $sender_id, $receiver_id, $product_id, $message, $role, $message_type);
                if ($stmtInsertChat->execute()) {
                    // Prepare data for redirect
                    $tab = isset($_POST['tab']) ? $_POST['tab'] : 'buying'; // Default to 'buying' if not set
                    $data = [
                        'user_id' => $receiver_id,
                        'product_id' => $product_id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'tab' => $tab
                    ];
                    $encodedData = base64_encode(json_encode($data));
                    header("Location: farmer-inbox.php?data=$encodedData&tab=$tab");
                    exit;
                } else {
                    echo "Error inserting message: " . $stmtInsertChat->error;
                }
                $stmtInsertChat->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        } else {
            echo "Error: The specified product ID does not exist.";
        }
    } else {
        echo "Error: Message cannot be empty.";
    }
}
    
    // Retrieve the receiver's name from the user table
    if (isset($receiver_id)) {
        $receiverSql = "SELECT first_name, last_name FROM user WHERE user_id = ?";
        $stmtReceiver = $conn->prepare($receiverSql);
        $stmtReceiver->bind_param("i", $receiver_id);
        $stmtReceiver->execute();
        $stmtReceiver->bind_result($first_name, $last_name);
        if ($stmtReceiver->fetch()) {
            $receiver_name = $first_name . ' ' . $last_name; // Combine first and last name
        }
        $stmtReceiver->close();
    }
    
    $product_name = ''; // Initialize as empty
    if ($product_id > 0) { // Check if a valid product ID is provided
        $productSql = "SELECT product_name FROM products WHERE product_id = ?";
        $stmtProduct = $conn->prepare($productSql);
        $stmtProduct->bind_param("i", $product_id);
        $stmtProduct->execute();
        $stmtProduct->bind_result($product_name);
        $stmtProduct->fetch();
        $stmtProduct->close();
    }
    
    $chatSql = "SELECT sender_id, receiver_id, message, sent_at FROM chat WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at DESC";
    $stmtChat = $conn->prepare($chatSql);
    $stmtChat->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    $stmtChat->execute();
    $result = $stmtChat->get_result();
    
    $chats = [];
    while ($row = $result->fetch_assoc()) {
        $chats[] = $row; // Add each chat message to the chats array
    }
    
    $stmtChat->close();
    
        // Query to count unread messages
    $query = "SELECT COUNT(*) AS unread_count FROM chat WHERE receiver_id = ? AND read_status = 'unread'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id); // Bind user_id parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $unread_count = $row['unread_count'];

    $stmt->close();
    ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Chat</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../css/farmer.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/messenger.css?v=<?php echo time(); ?>">
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
          <p class="agtech">AgTech</p>
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
                    <?php if ($unread_count > 0): ?>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
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
    <div class="nav-container" id="nav-container">
        <h2>Message</h2>
        <div class="profile-con" id="profileIcon">
            <?php echo $profile_picture_html; ?>
        <div class="dropdown-menu-custom" id="profileDropdown">
            <a href="#" class="dropdown-item-custom" id="open-profile">
                <div class="dropdown-btn">
                <i class="bi bi-person-circle profile-action"></i>
                <p>Profile</p>
                </div>
            </a>
            <a href="/manage-password" class="dropdown-item-custom">
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

        <div class="wrapper" id="wrapperSection">
        <div class="container">
            <div class="left" id="left-section">
                <div class="top-left">
                    <input type="text" placeholder="Search" class="search-input" />
                   <ul class="nav nav-tabs">
                        <li class="nav-item" data-tooltip="Shows buyers interested in your product">
                            <a class="nav-link active" id="selling-tab" data-toggle="tab" href="#selling">Selling</a>
                        </li>
                        <li class="nav-item" data-tooltip="Shows the sellers of the products you're interested in">
                            <a class="nav-link" id="buying-tab" data-toggle="tab" href="#buying">Buying</a>
                        </li>
                    </ul>
                </div>

    <div class="tab-content">
   <div id="selling-content" class="tab-pane active">
    <ul class="people">
        <?php
        // Selling Tab: Users interested in the logged-in user's products
        // Query to count unread messages for "selling" tab
        $selling_unread_count_query = "
            SELECT COUNT(*) AS unread_count
            FROM chat 
            WHERE receiver_id = ? AND role = 'buyer' AND read_status = 'unread'";
        $stmtSellingUnread = $conn->prepare($selling_unread_count_query);
        $stmtSellingUnread->bind_param("i", $user_id);
        $stmtSellingUnread->execute();
        $stmtSellingUnread->bind_result($selling_unread_count);
        $stmtSellingUnread->fetch();
        $stmtSellingUnread->close();
        
        // Modified query to get the most recent message
        $sellingSql = "
        SELECT p_sender.user_id AS sender_id, p_sender.first_name AS sender_first_name, p_sender.last_name AS sender_last_name, 
            p_sender.profile AS sender_profile, prod.product_name, c.product_id, 
            c.message, c.sent_at, c.sender_id AS latest_sender_id, 
            pi.image_url AS product_image
        FROM user p_sender
        JOIN chat c ON c.sender_id = p_sender.user_id
        JOIN products prod ON c.product_id = prod.product_id
        LEFT JOIN productimages pi ON pi.product_id = prod.product_id
        WHERE c.product_id IN (
            SELECT DISTINCT product_id
            FROM chat
            WHERE receiver_id = ? AND role != 'seller'
        )
        AND p_sender.user_id != ?
        GROUP BY p_sender.user_id, prod.product_name
        ORDER BY MAX(c.sent_at) DESC  -- Sort by the latest message date/time
        ";

        $stmtSelling = $conn->prepare($sellingSql);
        $stmtSelling->bind_param("ii", $user_id, $user_id);
        $stmtSelling->execute();
        $resultSelling = $stmtSelling->get_result();

        while ($participant = $resultSelling->fetch_assoc()) {
            $displayName = htmlspecialchars($participant['sender_first_name']) . ' ' . htmlspecialchars($participant['sender_last_name']);
            $displayProfile = $participant['sender_profile'];
            $productImage = $participant['product_image'];
            $latestMessage = $participant['message'];
            $latestMessageTime = $participant['sent_at'];
            $isSender = ($participant['latest_sender_id'] == $user_id);
            
            // Decode the JSON-encoded image_url into an associative array
            $productImageUrls = json_decode($productImage, true);
            // Check if the array is valid and contains at least one URL
            $firstProductImage = (!empty($productImageUrls) && is_array($productImageUrls)) ? $productImageUrls[0] : null;
           
            // Determine read status (you might need to adjust the query according to your logic)
            $readStatusSql = "
            SELECT read_status 
            FROM chat 
            WHERE product_id = ? 
            AND receiver_id = ? 
            AND sender_id = ?
            ORDER BY sent_at DESC 
            LIMIT 1
            ";
            $stmtReadStatus = $conn->prepare($readStatusSql);
            $stmtReadStatus->bind_param("iii", $participant['product_id'], $user_id, $participant['sender_id']);
            $stmtReadStatus->execute();
            $stmtReadStatus->bind_result($readStatus);
            $stmtReadStatus->fetch();
            $stmtReadStatus->close();

            // Set bold class if unread
            $boldClass = ($readStatus == 'unread') ? 'unread' : 'read';
            ?>

            <li class="person <?php echo htmlspecialchars($boldClass); ?>" data-chat="<?php echo htmlspecialchars($participant['sender_id']); ?>" data-product-id="<?php echo htmlspecialchars($participant['product_id']); ?>" onclick="showWriteArea(this)">
               <?php if (!empty($displayProfile)): ?>
               <picture>
                    <img src="<?php echo htmlspecialchars($displayProfile . '?v=' . time()); ?>" alt="Profile Picture" class="product-profile">
                    </picture>
                <?php else: ?>
                <picture>
                    <div class="chat-avatar" style="width: 60px; height: 60px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 60px; font-size: 40px; margin-right: 0px;">
                        <?php echo strtoupper(substr($displayName, 0, 1)); ?>
                    </div>
                    </picture>
                <?php endif; ?>
                <span class="user_name">
                    <?php echo $displayName; ?>
                </span>
                <span class="product_name">
                    <?php echo htmlspecialchars(' | ' . $participant['product_name']); ?>
                </span>
                <span class="time">
                <?php
                    // Retrieve the latest message time for the current user-product combination in the Selling tab
                    $latestMessageTimeSql = "
                    SELECT sent_at 
                    FROM chat 
                    WHERE product_id = ? 
                    AND ((sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)) 
                    ORDER BY sent_at DESC 
                    LIMIT 1";
                    $stmtLatestMessageTime = $conn->prepare($latestMessageTimeSql);
                    $stmtLatestMessageTime->bind_param("iiiii", $participant['product_id'], $user_id, $participant['sender_id'], $participant['sender_id'], $user_id);
                    $stmtLatestMessageTime->execute();
                    $stmtLatestMessageTime->bind_result($latestMessageTime);
                    if ($stmtLatestMessageTime->fetch()) {
                        echo date('h:i A', strtotime($latestMessageTime));
                    } else {
                        echo 'No messages';
                    }
                    $stmtLatestMessageTime->close();
                    ?>
                </span>
                <span class="preview" data-participant-id="<?php echo $participant['sender_id']; ?>">
                    <?php
                    // Retrieve and display the latest message preview with message type
                    $latestMessagePreviewSql = "
                        SELECT sender_id, message, message_type 
                        FROM chat 
                        WHERE product_id = ? 
                        AND ((sender_id = ? AND receiver_id = ?) 
                        OR (sender_id = ? AND receiver_id = ?)) 
                        ORDER BY sent_at DESC 
                        LIMIT 1";
                    $stmtLatestMessagePreview = $conn->prepare($latestMessagePreviewSql);
                    $stmtLatestMessagePreview->bind_param("iiiii", $participant['product_id'], $user_id, $participant['sender_id'], $participant['sender_id'], $user_id);
                    $stmtLatestMessagePreview->execute();
                    $stmtLatestMessagePreview->bind_result($senderId, $latestMessagePreview, $messageType);
                
                    if ($stmtLatestMessagePreview->fetch()) {
                        $isSender = ($senderId == $user_id);
                
                        // Determine the preview text based on the message type
                        if ($messageType == 'image') {
                            $previewText = 'Sent a photo.';
                        } else {
                            $previewText = htmlspecialchars($latestMessagePreview); // Full text for JavaScript to handle
                        }
                
                        // Display the preview with "You: " if the logged-in user is the sender
                        echo $isSender ? "You: <span class='preview-text'>$previewText</span>" : "<span class='preview-text'>$previewText</span>";
                    }
                    $stmtLatestMessagePreview->close();
                    ?>
                </span>
            </li>
        <?php
        }
        $stmtSelling->close();
        ?>
    </ul>
</div>

<div id="buying-content" class="tab-pane" style="display:none;">
    <ul class="people">
        <?php
        
        // Query to count unread messages for "buying" tab
        $buying_unread_count_query = "
            SELECT COUNT(*) AS unread_count
            FROM chat 
            WHERE receiver_id = ? AND role = 'seller' AND read_status = 'unread'";
        $stmtBuyingUnread = $conn->prepare($buying_unread_count_query);
        $stmtBuyingUnread->bind_param("i", $user_id);
        $stmtBuyingUnread->execute();
        $stmtBuyingUnread->bind_result($buying_unread_count);
        $stmtBuyingUnread->fetch();
        $stmtBuyingUnread->close();
        
        // Retrieve the logged-in user's name and profile picture
        $userProfileSql = "SELECT first_name, last_name, profile FROM user WHERE user_id = ?";
        $stmtUserProfile = $conn->prepare($userProfileSql);
        $stmtUserProfile->bind_param("i", $user_id);
        $stmtUserProfile->execute();
        $stmtUserProfile->bind_result($loggedInFirstName, $loggedInLastName, $loggedInProfile);
        $stmtUserProfile->fetch();
        $loggedInName = htmlspecialchars($loggedInFirstName . ' ' . $loggedInLastName);
        $stmtUserProfile->close();

        // Buying Tab: Products the logged-in user is interested in
        $buyingSql = "
        SELECT DISTINCT p.user_id, p.first_name, p.last_name, p.profile, prod.product_name, c.product_id, c.sender_id, pi.image_url AS product_image, c.receiver_id
        FROM user p
        JOIN chat c ON c.receiver_id = p.user_id
        JOIN products prod ON c.product_id = prod.product_id
        LEFT JOIN productimages pi ON pi.product_id = prod.product_id
        WHERE c.sender_id = ? AND c.role != 'seller'  -- Exclude sellers
        ORDER BY c.sent_at DESC
        ";
        $stmtBuying = $conn->prepare($buyingSql);
        $stmtBuying->bind_param("i", $user_id);
        $stmtBuying->execute();
        $resultBuying = $stmtBuying->get_result();
        while ($participant = $resultBuying->fetch_assoc()) {
            $profilePicture = $participant['profile'];
            $productImage = $participant['product_image'];
            $displayName = htmlspecialchars($participant['first_name'] . ' ' . $participant['last_name']);
            $productId = $participant['product_id'];

            // Decode the JSON-encoded product image URLs into an array
            $productImageUrls = json_decode($productImage, true);
            $firstProductImage = (!empty($productImageUrls) && is_array($productImageUrls)) ? $productImageUrls[0] : null;

            // Retrieve the latest message read status
            $latestMessagePreviewSql = "
            SELECT sender_id, message, read_status, message_type
            FROM chat 
            WHERE product_id = ? 
            AND ((sender_id = ? AND receiver_id = ?) 
            OR (sender_id = ? AND receiver_id = ?)) 
            ORDER BY sent_at DESC 
            LIMIT 1";
            $stmtLatestMessagePreview = $conn->prepare($latestMessagePreviewSql);
            $stmtLatestMessagePreview->bind_param("iiiii", $productId, $user_id, $participant['user_id'], $participant['user_id'], $user_id);
            $stmtLatestMessagePreview->execute();
            $stmtLatestMessagePreview->bind_result($senderId, $latestMessagePreview, $readStatus, $message_type);
            $stmtLatestMessagePreview->fetch();

            // Set bold class if unread
            // Set bold class if unread and not sent by the logged-in user
$boldClass = ($readStatus == 'unread' && $senderId != $user_id) ? 'unread' : 'read';
            $stmtLatestMessagePreview->close();
        ?>
            <li class="person <?php echo htmlspecialchars($boldClass); ?>" data-chat="<?php echo htmlspecialchars($participant['user_id']); ?>" data-product-id="<?php echo htmlspecialchars($productId); ?>" onclick="showWriteArea(this)">
                 <?php if (!empty($profilePicture)): ?>
                 <picture>
                    <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="product-profile">
                    </picture>
                <?php else: ?>
                <picture>
                    <div class="chat-avatar" style="width: 60px; height: 60px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 60px; font-size: 40px; margin-right: 0px;">
                        <?php echo strtoupper(substr($displayName, 0, 1)); ?>
                    </div>
                    </picture>
                <?php endif; ?>
                <span class="user_name" <?php echo $boldClass; ?>>
                    <?php
                    // Display the logged-in user's name if sender_id matches
                    if ($participant['receiver_id'] == $user_id) {
                        echo $loggedInName;
                    } else {
                        echo $displayName;
                    }
                    ?>
                </span>
                <span class="product_name" <?php echo $boldClass; ?>>
                    <?php echo htmlspecialchars(' | ' . $participant['product_name']); ?>
                </span>
                <span class="time">
                    <?php
                    // Retrieve the latest message time for the current user-product combination
                    $latestMessageTimeSql = "
                    SELECT sent_at
                    FROM chat 
                    WHERE product_id = ? 
                    AND ((sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)) 
                    ORDER BY sent_at DESC 
                    LIMIT 1";
                    $stmtLatestMessageTime = $conn->prepare($latestMessageTimeSql);
                    $stmtLatestMessageTime->bind_param("iiiii", $productId, $user_id, $participant['user_id'], $participant['user_id'], $user_id);
                    $stmtLatestMessageTime->execute();
                    $stmtLatestMessageTime->bind_result($latestMessageTime);
                    if ($stmtLatestMessageTime->fetch()) {
                        echo date('h:i A', strtotime($latestMessageTime));
                    } else {
                        echo 'No messages';
                    }
                    $stmtLatestMessageTime->close();
                    ?>
                </span>
                <span class="preview" <?php echo $boldClass; ?>>
                    <?php
                    // Check if the latest message is an image
                    if ($message_type == 'image') {
                        $previewText = 'Sent a photo.';
                    } else {
                        // Display the latest message preview text for non-image messages
                        $previewText = htmlspecialchars($latestMessagePreview);
                    }
                
                    // Display "You: " if the sender is the logged-in user
                    echo ($senderId == $user_id ? 'You: ' : '') . '<span class="preview-text">' . $previewText . '</span>';
                    ?>
                </span>
            </li>
        <?php
        }
        $stmtBuying->close();
        ?>
    </ul>
</div>
        </div>
        </div>

        <div class="right" id="rightDiv">
                        <i class="bi bi-chevron-left chat-close"></i>
        <div class="top">
            <?php
            $user_id = $_GET['user_id'] ?? '';
            $first_name = $_GET['first_name'] ?? '';
            $last_name = $_GET['last_name'] ?? '';
            $displayName = $first_name && $last_name ? "$first_name $last_name" : '';
            ?>
            <span><span class="name" id="chatName"><?php echo htmlspecialchars($displayName); ?></span></span>

        
            <div id="redirectedUser" style="display:none;" 
     data-user-id="<?php echo htmlspecialchars($user_id); ?>" 
     data-product-id="<?php echo isset($_GET['product_id']) ? htmlspecialchars($_GET['product_id']) : ''; ?>">
</div>
        </div>
        <div class="message"></div>
        
        <div id="imageModal" class="image-modal">
            <span class="close">&times;</span>
            <img class="modal-content" id="modalImage">
            <div id="caption"></div>
        </div>
        
        <div class="write" id="writeArea" style="display: none;">
        <div class="attachment">
            <form action="">
                <i class="bi bi-image upload-icon" id="uploadIcon" 
                    data-product-id="<?php echo htmlspecialchars($product_id); ?>" 
                    onclick="triggerFileInput()"></i>
                <input type="file" id="fileInput" class="hidden-input" accept="image/*" multiple style="display: none;">
                <input type="hidden" name="receiver_id" id="input-receiver_id" value="<?php echo htmlspecialchars($receiver_id); ?>">
                <input type="hidden" name="product_id" id="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
            </form>
        </div>
            <form id="chatForm" method="post">
                <input type="hidden" name="receiver_id" id="receiver_id" value="<?php echo htmlspecialchars($receiver_id); ?>">
                <input type="hidden" name="sender_id" value="<?php echo htmlspecialchars($sender_id); ?>">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                <input type="text" id="messageInput" name="message" placeholder="Type your message..." required />
                <button type="submit" class="write-link send">
                    <i class="bi bi-send"></i>
                </button>
            </form>
        </div>
    </div>
        </div>
    </div>

    <!-- Rate Seller Modal -->
<div class="modal" id="ratingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rate Seller</h5>
                <button type="button" class="btn-close" onclick="closeRatingModal()" aria-label="Close">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="modal-body">
                <p class="rating-label" id="sellerNameLabel"></p>
                <input type="hidden" id="chatIdInput" name="chat_id">
                <input type="hidden" id="ratedUserId" name="rated_user_id">
                <input type="hidden" id="productId" name="product_id">
                <div class="star-rating" id="starRating">
                    <span class="star" data="star" data-value="1">&#9733;</span>
                    <span class="star" data-value="2">&#9733;</span>
                    <span class="star" data-value="3">&#9733;</span>
                    <span class="star" data-value="4">&#9733;</span>
                    <span class="star" data-value="5">&#9733;</span>
                </div>
                <textarea id="review" name="review" class="form-control" rows="3"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="seller-rate-btn" onclick="submitRating()">Submit</button>
            </div>
        </div>
    </div>
</div>
</div>


<div class="bottom-nav" id="bottom-nav">
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
        errorMessageDiv.style.display = 'block';
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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const previews = document.querySelectorAll('.preview-text');

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
    document.addEventListener('DOMContentLoaded', function() {
        // Get the elements
        const chatCloseBtn = document.querySelector('.chat-close');
        const wrapperSection = document.getElementById('left-section');
        const rightDiv = document.getElementById('rightDiv');
        const navContainer = document.getElementById('nav-container');
        const bottomNav = document.getElementById('bottom-nav');
        
        // Hide the right section (chat) and show the wrapper when the chat-close button is clicked
        chatCloseBtn.addEventListener('click', function() {
            // Hide the right section (chat)
            rightDiv.style.display = 'none';
            
            // Show the wrapper section
            wrapperSection.style.display = 'block';
            // Show the wrapper section
            navContainer.style.display = '';
            // Show the wrapper section
            bottomNav.style.display = 'block';
        });
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
let selectedRating = 0;

function openRatingModal(chatId) {
    console.log("Opening modal with chat ID:", chatId); // Log chatId for debugging

    fetch(`getChatDetails.php?chat_id=${chatId}`) // Assuming you have an endpoint to get chat details
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sellerName = data.seller_name;
                const ratedUserId = data.sender_id; // Fetch from the response
                const productId = data.product_id; // Fetch from the response
                
                document.getElementById('sellerNameLabel').innerHTML = 
                    `How was your experience buying from <strong>${sellerName}</strong>?`;

                // Set the ratedUserId and productId
                document.getElementById('ratedUserId').value = ratedUserId;
                document.getElementById('productId').value = productId;

                // Log to confirm they are set correctly
                console.log("Rated User ID set to:", ratedUserId);
                console.log("Product ID set to:", productId);

                // Show the modal
                showModal();
            } else {
                console.error(data.message);
            }
        })
        .catch(error => {
            console.error('Error fetching chat details:', error);
        });
}

document.addEventListener('DOMContentLoaded', function () {
    const stars = document.querySelectorAll('#starRating .star');

    stars.forEach(star => {
        star.addEventListener('mouseover', function () {
            highlightStars(this.dataset.value);
        });
        star.addEventListener('mouseout', function () {
            highlightStars(selectedRating);
        });
        star.addEventListener('click', function () {
            selectedRating = this.dataset.value; // Set the selected rating value
            highlightStars(selectedRating); // Highlight the stars based on the selected rating
            console.log('Selected Rating:', selectedRating); // Log selected rating
        });
    });

    function highlightStars(rating) {
        stars.forEach(star => {
            star.style.color = star.dataset.value <= rating ? '#ffcc00' : '#ccc'; // Change color based on rating
        });
    }
});

function submitRating() {
    const raterId = <?php echo json_encode($_SESSION['user_id']); ?>; // Get the rater ID from session
    const ratedUserId = document.getElementById('ratedUserId').value;
    const productId = document.getElementById('productId').value;

    // Check for selected star rating
    const ratingValue = selectedRating;

    if (ratingValue === 0) { // Check if rating is still 0 (not selected)
        Swal.fire({
            icon: 'warning',
            title: 'Please select a rating before submitting.',
            showConfirmButton: true
        });
        return; // Exit the function if no rating is selected
    }

    const comment = document.getElementById('review').value;
    const role = 'seller'; // You can dynamically assign this based on your logic

    const formData = new FormData();
    formData.append('rater_id', raterId);
    formData.append('rated_user_id', ratedUserId);
    formData.append('role', role); // Pass the role as well
    formData.append('product_id', productId);
    formData.append('rating_value', ratingValue);
    formData.append('review', comment);
    formData.append('created_at', new Date().toISOString()); // Current timestamp

    fetch('submit_rating.php', {
        method: 'POST',
        body: formData,
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.text(); // Change to text for easier debugging
    })
    .then(responseText => {
        console.log("Response:", responseText); // Log the response for debugging
        try {
            const data = JSON.parse(responseText);
            if (data.success) {
                // Use SweetAlert for success notification
                Swal.fire({
                    icon: 'success',
                    title: 'Rating submitted successfully!',
                    showConfirmButton: true
                }).then(() => {
                    closeRatingModal(); // Close the modal after the user acknowledges the success message
                });
            } else {
                // Use SweetAlert for error notification
                Swal.fire({
                    icon: 'error',
                    title: 'Error submitting rating',
                    text: data.message,
                    showConfirmButton: true
                });
            }
        } catch (error) {
            console.error('JSON parse error:', error);
            // Use SweetAlert for JSON parse error notification
            Swal.fire({
                icon: 'error',
                title: 'Error parsing response',
                text: 'Please try again.',
                showConfirmButton: true
            });
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        // Use SweetAlert for fetch error notification
        Swal.fire({
            icon: 'error',
            title: 'Network Error',
            text: 'Unable to submit your rating. Please check your connection and try again.',
            showConfirmButton: true
        });
    });
}

function showModal() {
    document.getElementById('ratingModal').style.display = 'block';
}

function closeRatingModal() {
    document.getElementById('ratingModal').style.display = 'none';
}
</script>

<script>
    // Define the media query conditions
    const mediaQuery = window.matchMedia("(min-width: 320px) and (max-width: 814px)");

    // Function to apply the event listener based on the media query
    function applyClickEvents() {
        // Check if the media query matches
        if (mediaQuery.matches) {
            // Get all elements with the class 'person'
            const persons = document.querySelectorAll('.person');
            const rightDiv = document.getElementById('rightDiv');
            const leftContainer = document.querySelector('.left');
            const navContainer = document.getElementById('nav-container');
            const bottomNav = document.getElementById('bottom-nav');

            // Add click event listener to each person element
            persons.forEach(person => {
                person.addEventListener('click', () => {
                    // Show the right div
                    rightDiv.style.display = 'block';

                    // Hide the left container
                    leftContainer.style.display = 'none';

                    // Hide the nav-container and bottom-nav
                    navContainer.style.display = 'none';
                    bottomNav.style.display = 'none';
                });
            });
        } else {
            // Ensure nav-container and bottom-nav are visible when the media query doesn't match
            const navContainer = document.getElementById('nav-container');
            const bottomNav = document.getElementById('bottom-nav');
            navContainer.style.display = '';
            bottomNav.style.display = 'block';
        }
    }

    // Function to handle redirection and apply click events accordingly
    function handleRedirection() {
        // Check if there's a URL parameter for 'data'
        const encodedData = new URLSearchParams(window.location.search).get('data');
        
        if (encodedData) {
            // Decode and parse the URL parameter
            const decodedData = atob(encodedData);
            const data = JSON.parse(decodedData);

            const userIdFromUrl = data.user_id;
            const productIdFromUrl = data.product_id;
            let firstNameFromUrl = data.first_name;
            let lastNameFromUrl = data.last_name;

            // Decode '+' as space
            firstNameFromUrl = firstNameFromUrl.replace(/\+/g, ' ');
            lastNameFromUrl = lastNameFromUrl.replace(/\+/g, ' ');

            // Check if the media query matches before applying the click behavior
            if (mediaQuery.matches) {
                // Trigger the same functionality as clicking a person
                const persons = document.querySelectorAll('.person');
                persons.forEach(person => {
                    // If the person matches the user ID from the URL
                    if (person.dataset.chat == userIdFromUrl && person.dataset.productId == productIdFromUrl) {
                        // Show the right div
                        const rightDiv = document.getElementById('rightDiv');
                        const leftContainer = document.querySelector('.left');
                        const navContainer = document.getElementById('nav-container');
                        const bottomNav = document.getElementById('bottom-nav');

                        rightDiv.style.display = 'block';  // Show the right div
                        leftContainer.style.display = 'none';  // Hide the left container
                        navContainer.style.display = 'none';  // Hide the nav-container
                        bottomNav.style.display = 'none';  // Hide the bottom nav
                    }
                });
            }
        }
    }

    // Apply the click events initially
    applyClickEvents();

    // Handle redirection logic when the page is loaded
    handleRedirection();

    // Re-apply the logic when the window is resized, in case the media query changes
    mediaQuery.addListener(applyClickEvents);
</script>

<script>
    $(document).ready(function() {
        // Handle the selling tab click
        $('#selling-tab').click(function(e) {
            e.preventDefault();
            $('#selling-tab').addClass('active');
            $('#buying-tab').removeClass('active');

            // Show the selling content and hide buying content
            $('#selling-content').show();
            $('#buying-content').hide();
        });

        // Handle the buying tab click
        $('#buying-tab').click(function(e) {
            e.preventDefault();
            $('#buying-tab').addClass('active');
            $('#selling-tab').removeClass('active');

            // Show the buying content and hide selling content
            $('#buying-content').show();
            $('#selling-content').hide();
        });
    });
</script>


<script>
const body = document.querySelector('body'),
    sidebar = body.querySelector('nav'),
    toggle = body.querySelector(".toggle"),
    navLinks = body.querySelectorAll('.menu-links .nav-link'); // Select all nav links

// Toggle sidebar on toggle button click
toggle.addEventListener("click", () => {
    sidebar.classList.toggle("close");
});

// Function to keep sidebar open on specific link
function keepSidebarOpen(link) {
    link.addEventListener('click', (event) => {
        // If the sidebar is closed, open it
        if (sidebar.classList.contains("close")) {
            sidebar.classList.remove("close");
        }
    });
}

// Loop through nav links to apply event listeners
navLinks.forEach(link => {
    if (link.firstElementChild.href.includes("Shop.php")) { // Check if it's the "All Products" link
        link.addEventListener('click', (event) => {
            // Prevent default navigation
            event.preventDefault();
            // Open sidebar if it is closed
            if (sidebar.classList.contains("close")) {
                sidebar.classList.remove("close");
            }
            // Navigate to "All Products" page
            window.location.href = link.firstElementChild.href;
        });
    } else {
        keepSidebarOpen(link); // Apply to other links
    }
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
            if (topMenuIcons) {
                topMenuIcons.appendChild(menuIcons);
            }
        } else {
            // Move them back to the sidebar
            const sidebarMenu = document.querySelector('.menu-bar .menu');
            const topMenuIcons = document.querySelector('.top-menu-icons');
            if (topMenuIcons) {
                sidebarMenu.appendChild(topMenuIcons.querySelector('.menu-links'));
            }
        }
    }

    // Call function initially
    handleScreenSize();
    // Add event listener for window resize
    window.addEventListener('resize', handleScreenSize);
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
    document.querySelector('.message').classList.add('active-chat')

let friends = {
    list: document.querySelector('ul.people'),
    all: document.querySelectorAll('.left .person'),
    name: ''
  },
  chat = {
    container: document.querySelector('.container .right'),
    current: null,
    person: null,
    name: document.querySelector('.container .right .top .name')
  }

friends.all.forEach(f => {
  f.addEventListener('mousedown', () => {
  })
});

function setAciveChat(f) {
  friends.list.querySelector('.active').classList.remove('active')
  f.classList.add('active')
  chat.current = chat.container.querySelector('.active-chat')
  chat.person = f.getAttribute('data-chat')
  chat.current.classList.remove('active-chat')
  chat.container.querySelector('[data-chat="' + chat.person + '"]').classList.add('active-chat')
  friends.name = f.querySelector('.name').innerText
  chat.name.innerHTML = friends.name
}
</script>

<script>
    $(document).on('click', '.accept-button, .reject-button', function() {
    const action = $(this).data('action');
    const offerId = $(this).data('offer-id');  // Get the offer ID
    const chatId = $(this).data('chat-id');    // Get the chat ID
    const buttonContainer = $(this).closest('.action');

    $.ajax({
        url: 'handle_offer.php',
        method: 'POST',
        data: { action: action, offer_id: offerId, chat_id: chatId }, // Send both IDs
        success: function(response) {
            if (response.success) {
                // Remove the buttons when the offer is accepted or rejected
                buttonContainer.remove();  // This removes the entire button container
                // Optionally reload chat if needed
                loadChat(); 
            } else {
                console.log(response.error);
            }
        },
        error: function(xhr, status, error) {
            console.log("Error: " + error);
        }
    });
});

$(document).ready(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');

    // Activate the appropriate tab
    if (tab === 'buying') {
        $('#buying-tab').addClass('active');
        $('#selling-tab').removeClass('active');
        $('#buying-content').show();
        $('#selling-content').hide();
    } else {
        $('#selling-tab').addClass('active');
        $('#buying-tab').removeClass('active');
        $('#selling-content').show();
        $('#buying-content').hide();
    }

    // Check if offer data is present in URL (redirect from send_offer.php)
    const encodedData = urlParams.get('data');
    if (encodedData) {
        try {
            const decodedData = atob(encodedData);
            const data = JSON.parse(decodedData);

            const userId = data.user_id; // Receiver's user ID
            const productId = data.product_id;
            const firstName = data.first_name.replace(/\+/g, ' '); // Receiver's first name
            const lastName = data.last_name.replace(/\+/g, ' '); // Receiver's last name

            // Load chat for the redirected user (Receiver)
            if (userId && productId) {
                loadChatForRedirectedUser(userId, productId, firstName, lastName);
            } else {
                console.error("Invalid data in URL for chat redirection");
            }
        } catch (error) {
            console.error("Error decoding data: ", error);
        }
    } else {
        // Default: Load the first chat if no specific data is provided
        loadFirstChat();
    }

    // Attach click event to each person in the list to load chat
    $('.people .person').on('click', function () {
        const participantId = $(this).data('chat'); // Get participant ID
        const productId = $(this).data('product-id'); // Get product ID
        const fullName = $(this).find('.user_name').text(); // Get full name
        const [firstName, lastName] = fullName.split(' '); // Split full name into first and last name

        // Update the name in the chat header
        $('#chatName').text(`${firstName} ${lastName}`);

        // Load chat for the clicked participant
        loadChatForRedirectedUser(participantId, productId, firstName, lastName);
    });
});

// Function to load the first chat if no URL params
function loadFirstChat() {
    const firstPerson = $('.people .person').first(); // Select first person
    if (firstPerson.length) {
        const participantId = firstPerson.data('chat'); // Get participant ID
        const productId = firstPerson.data('product-id'); // Get product ID
        const participantName = firstPerson.find('.user_name').text(); // Get name

        // Set values in the form
        $('input[name="receiver_id"]').val(participantId);
        $('input[name="product_id"]').val(productId);
        $('#chatName').text(participantName); // Update name in chat header

        // Determine the role (buyer or seller)
        const currentTab = firstPerson.closest('.tab-pane').attr('id');
        const role = currentTab === 'selling-content' ? 'seller' : 'buyer';

        // Load chat
        loadChat(participantId, productId, role);

        // Show the write area
        $('#writeArea').show();
        $('#input-receiver_id').val(participantId);
        $('#product_id').val(productId);
    } else {
        console.error('No participants found for the first chat');
    }
}

function loadChatForRedirectedUser(userId, productId, firstName, lastName) {
    const participantName = `${firstName} ${lastName}`;
    $('#chatName').text(participantName); // Update chat header with receiver's name
    $('input[name="receiver_id"]').val(userId);
    $('input[name="product_id"]').val(productId);

    // Role is dynamic; assume 'buyer' for now
    const role = 'buyer';

    // Load the chat messages
    loadChat(userId, productId, role);

    // Ensure the write area is visible
    $('#writeArea').show();
}



// Updated loadChat function to handle chat loading
function loadChat(participantId, productId, role) {
    const messageContainer = document.querySelector('.message');

    // Check if the user is near the bottom before loading messages
    const atBottom =
        messageContainer.scrollHeight - messageContainer.clientHeight <=
        messageContainer.scrollTop + 1;

    $.ajax({
        url: 'load_chat.php',
        type: 'POST',
        data: {
            participant_id: participantId,
            product_id: productId,
            role: role
        },
        success: function(response) {
            try {
                const data = JSON.parse(response);
                $('.message').html(data.chats); // Update chat messages
                $('.product').text(data.product_name); // Update product name
                
                // Scroll to the bottom if the user was at the bottom
                if (atBottom) {
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                }
            } catch (e) {
                console.error("Invalid JSON response", e, response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading chat:', error);
        }
    });
}

// Detect new messages function (if applicable)
function onNewMessage(newMessage) {
    const messageContainer = document.querySelector('.message');
    const atBottom =
        messageContainer.scrollHeight - messageContainer.clientHeight <=
        messageContainer.scrollTop + 1;

    const messageElement = document.createElement('div');
    messageElement.className = 'message-item';
    messageElement.textContent = newMessage;
    messageContainer.appendChild(messageElement);

    // Only scroll to the bottom if the user was already at the bottom
    if (atBottom) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
}

// Submit the form via AJAX without showing any message or notification
$('#chatForm').on('submit', function(e) {
    e.preventDefault(); // Prevent the form from submitting traditionally
    var formData = $(this).serialize(); // Serialize form data

    // Add the message_type to the form data
    formData += '&message_type=' + encodeURIComponent('normal');

    // Get the ID of the active tab
    var currentTab = $('.nav-tabs .nav-link.active').attr('id').split('-')[0]; // gets 'selling' or 'buying'
    if (currentTab) {
        formData += '&tab=' + encodeURIComponent(currentTab); // Add the current tab to the form data
    } else {
        console.warn("No active tab found. The tab value will not be sent.");
    }

    $.ajax({
        type: 'POST',
        url: 'send_message.php', // Replace with the actual PHP script URL
        data: formData,
        success: function(response) {
            const data = JSON.parse(response);
            if (data.status === 'success') {
                // Update the message preview for the corresponding participant
                updatePreview(data.preview); // Call the function to update preview

                $('#messageInput').val(''); // Clear the message input
                loadChat(); // Reload the chat messages to reflect new input
            } else {
                console.error("Error:", data.message);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error sending message:", error);
            console.error("Status:", status);
            console.error("Response:", xhr.responseText);
        }
    });
});


// Function to update message preview
function updatePreview(preview) {
    // Assuming you have a way to identify which preview to update (like data attributes)
    $('.preview[data-participant-id="' + receiver_id + '"]').text(preview);
}

// Function to scroll to the bottom of the message div
function scrollToBottom() {
    const messageContainer = document.querySelector('.message');
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
}

// Polling for new messages every 2 seconds
setInterval(function() {
    const receiverId = $('#receiver_id').val(); // Get receiver_id from the hidden input
    const productId = $('input[name="product_id"]').val(); // Get product_id from the hidden input
    const role = $('.nav-tabs .nav-link.active').attr('id').split('-')[0] === 'selling' ? 'seller' : 'buyer'; // Determine role

    if (receiverId && productId) {
        loadChat(receiverId, productId, role); // Reload the chat messages
    }
}, 2000); // Poll every 2 seconds
</script>

<script>
function triggerFileInput() {
    // Get product_id from the input's value
    const productId = document.getElementById('product_id').value;
    // Get receiver_id from the hidden input field
    const receiverId = document.getElementById('input-receiver_id').value;

    // Trigger the file input click
    document.getElementById('fileInput').click();
}

document.getElementById('fileInput').addEventListener('change', function(event) {
    const files = event.target.files; // Get all selected files
    if (files.length > 0) {
        const productId = document.getElementById('product_id').value;
        const receiverId = document.getElementById('receiver_id').value;

        const formData = new FormData();
        let validFiles = true;

        // Validate file types and append them to FormData
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.startsWith('image/')) {
                validFiles = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File',
                    text: `"${file.name}" is not an image file. Only image files are allowed.`,
                    confirmButtonText: 'OK'
                });
                break;
            }
            formData.append('productImage[]', file);
        }

        if (validFiles) {
            // Append additional data
            formData.append('product_id', productId);
            formData.append('receiver_id', receiverId);

            // Make the fetch request to upload the images
            fetch('upload_chat_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Images uploaded successfully');
                    // Optionally, refresh the chat or update the UI here
                } else {
                    console.error('Upload failed:', data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    } else {
        console.log('No files selected.');
    }
});

function showWriteArea(element) {
    // Get the selected person's name
    const userName = element.querySelector('.user_name').innerText;

    // Set the name in the div with class 'top'
    const topDiv = document.querySelector('.top');
    if (topDiv) {
        topDiv.textContent = userName;
    }
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

<script>
        document.addEventListener("DOMContentLoaded", function () {
            // PHP variables are echoed into JavaScript
            var sellingUnreadCount = <?php echo $selling_unread_count; ?>;
            var buyingUnreadCount = <?php echo $buying_unread_count; ?>;

            // Function to add the unread dot
            function addUnreadDot(tabId) {
                var tab = document.querySelector(tabId);
                if (!tab) return; // Safety check in case tab doesn't exist
                var dot = document.createElement("span");
                dot.classList.add("unread-dot");
                tab.appendChild(dot);
            }

            // Logic to handle unread dots based on tabs' roles
            var sellingTab = document.querySelector("#selling-tab");
            var buyingTab = document.querySelector("#buying-tab");

            if (sellingTab && sellingTab.getAttribute("data-role") === "seller" && sellingUnreadCount > 0) {
                addUnreadDot("#selling-tab");
            }

            if (buyingTab && buyingTab.getAttribute("data-role") === "buyer" && buyingUnreadCount > 0) {
                addUnreadDot("#buying-tab");
            }
        });
    </script>
    
<script>
    document.querySelector('.search-input').addEventListener('input', function () {
    const searchQuery = this.value.toLowerCase();
    const sellingItems = document.querySelectorAll('#selling-content .person');
    const buyingItems = document.querySelectorAll('#buying-content .person');

    // Function to filter items
    function filterItems(items) {
        items.forEach(item => {
            const userName = item.querySelector('.user_name').textContent.toLowerCase();
            const productName = item.querySelector('.product_name').textContent.toLowerCase();
            const preview = item.querySelector('.preview-text').textContent.toLowerCase();

            // Check if any field matches the search query
            if (userName.includes(searchQuery) || productName.includes(searchQuery) || preview.includes(searchQuery)) {
                item.style.display = ''; // Show item
            } else {
                item.style.display = 'none'; // Hide item
            }
        });
    }

    // Apply filtering to both tabs
    filterItems(sellingItems);
    filterItems(buyingItems);
});

</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const captionText = document.getElementById('caption');
        const closeBtn = modal.querySelector('.close'); // Ensure the close button is inside the modal

        // Add click event for dynamically loaded images
        document.body.addEventListener('click', function (event) {
            if (event.target.classList.contains('send-image')) {
                modal.style.display = 'block';
                modalImg.src = event.target.src;
                captionText.innerHTML = event.target.alt || 'Image Preview';
            }
        });

        // Add click event to close button
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        // Add click event to close modal when clicking outside the modal content
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
</script>

<script>
const user_id = <?php echo json_encode($user_id); ?>; // PHP variable containing the logged-in user's ID

document.querySelectorAll('.person').forEach(function(person) {
    person.addEventListener('click', async function() {
        const productId = this.getAttribute('data-product-id');
        
        try {
            const response = await fetch('update_read_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `user_id=${user_id}&product_id=${productId}`
            });
            
            const result = await response.json();
            console.log(result);
        } catch (error) {
            console.error('Error updating read status:', error);
        }
    });
});
</script>

</body>
</html>