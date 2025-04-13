<?php
// Include database connection
include '../Connection/connection.php';

if (isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];

    // Query to fetch buyers who have sent a message about this product
    $query = "SELECT DISTINCT c.sender_id, u.first_name, u.last_name, u.profile
              FROM chat c
              JOIN user u ON c.sender_id = u.user_id
              WHERE c.product_id = ? AND c.role = 'buyer'";

    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<div class='private-response'>
            <i class='bi bi-incognito'></i>
            <p>Your response is private</p>
            </div>";
            echo "<p class='who'>Who bought your item?</p>";
            echo "<p class='note'>Your response won't be shared with anyone.</p>";
            echo "<ul class='interested-buyers-list'>";

            while ($row = $result->fetch_assoc()) {
                $sender_id = $row['sender_id'];
                $first_name = $row['first_name'];
                $last_name = $row['last_name'];
                $profile_image = $row['profile'];

                echo "<li>";
                echo "<div class='buyer-info-wrapper' style='display: flex; align-items: center; cursor: pointer;' onclick='showPriceQuantityModal({$sender_id}, \"{$first_name}\", \"{$last_name}\")'>";

                // Display profile image if available, else show initials
                if (!empty($profile_image)) {
                    echo "<div class='buyer-avatar-wrapper' style='margin-right: 10px;'>
                            <img src='../images/{$profile_image}' alt='{$first_name} {$last_name}' class='buyer-avatar' style='width: 45px; height: 45px; border-radius: 50%; object-fit: cover;'>
                        </div>";
                } else {
                    $initials = strtoupper($first_name[0] . $last_name[0]);
                    echo "<div class='farmer-avatar' style='width: 45px; height: 45px; background-color: #F9BF29; color: darkgreen; border-radius: 50%; text-align: center; line-height: 45px; font-size: 24px; margin-right: 10px;'>
                            {$initials}
                        </div>";
                }

                // Display the buyer's name next to the avatar
                echo "<div class='buyer-name'>{$first_name} {$last_name}</div>";

                echo "</div>"; // Close buyer-info-wrapper

                echo "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>No interested buyers found for this product.</p>";
        }

        $stmt->close();
        echo "<div class='someone'>
        <i class='bi bi-people'></i>
        <p>Someone else</p>
        </div>";
    }
}
?>
