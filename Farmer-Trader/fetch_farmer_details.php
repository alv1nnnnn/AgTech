<?php
// Include database connection
include('../Connection/connection.php');

// Check if product_id is provided in the URL
$product_id = isset($_GET['product_id']) ? $_GET['product_id'] : null;
if (!$product_id) {
    echo json_encode(["error" => "Product ID not provided"]);
    exit;
}

// SQL to fetch seller details along with ratings based on product_id
$sql_user = "
    SELECT 
        u.profile,
        CONCAT(u.first_name, ' ', u.last_name) AS full_name,
        u.phone_number,
        u.email,
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'rating_value', r.rating_value,
                    'comment', r.comment
                )
            )
            FROM review r
            WHERE r.rated_user_id = u.user_id
        ) AS ratings
    FROM user u
    WHERE EXISTS (
        SELECT 1
        FROM userproducts up
        WHERE up.user_id = u.user_id
        AND up.product_id = ?
    )";

$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $product_id);  // Bind the product_id
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $data = $result_user->fetch_assoc();
    $data['ratings'] = json_decode($data['ratings'], true); // Decode JSON from SQL

    // Check if the profile image is empty and set a default avatar if needed
    $profile_image = !empty($data['profile']) ? $data['profile'] : "<div class='avatar' style='height: 130px;
    width: 130px;
    background-color: #2D4A36;
    color: white;
    border-radius: 50%;
    text-align: center;
    line-height: 130px;
    font-size: 60px;
    margin-left: 48px'>" . strtoupper(substr($data['full_name'], 0, 1)) . "</div>";
    $data['profile'] = $profile_image;

    // Now fetch all products for this user
    // SQL to fetch all products for this user with status 'active'
    $sql_products = "
        SELECT 
            p.product_id,
            p.product_name,
            pi.image_url,
            pp.current_price,
            c.category_name
        FROM products p
        LEFT JOIN userproducts up ON up.product_id = p.product_id
        LEFT JOIN productimages pi ON p.product_id = pi.product_id
        LEFT JOIN productprices pp ON p.product_id = pp.product_id
        LEFT JOIN category c ON p.category_id = c.category_id
        WHERE up.user_id = (
            SELECT u.user_id FROM user u WHERE u.profile = ? 
        )
        AND p.status = 'active'";  // Add this condition to filter by active status

    $stmt_products = $conn->prepare($sql_products);
    $stmt_products->bind_param("s", $data['profile']);  // Bind the profile to match the user
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();

    // Initialize products array
    $products = [];
    while ($product = $result_products->fetch_assoc()) {
        // Decode the image_url if it's JSON-encoded and check if it's an array
        $image_url = !empty($product['image_url']) ? json_decode($product['image_url'], true) : null;

        // If the decoded image_url is an array, use the first element, otherwise use a default
        if (is_array($image_url) && !empty($image_url[0])) {
            $image_url = "../product_images/" . $image_url[0];  // Prepend the directory path
        } else {
            $image_url = '../product_images/default-image.jpg';  // Fallback image if no valid image URL is found
        }

        $products[] = [
            'product_id' => $product['product_id'],
            'product_name' => $product['product_name'],
            'image_url' => $image_url,  // Use the correct image URL
            'current_price' => $product['current_price'],
            'category_name' => $product['category_name']
        ];
    }

    // Add the products array to the data
    $data['products'] = $products;

    // Return the JSON response with user and product data
    echo json_encode($data);
} else {
    echo json_encode(["error" => "No details found for the specified product or user"]);
}
?>