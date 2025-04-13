<?php
require_once '../Connection/connection.php';

$searchQuery = isset($_GET['q']) ? $_GET['q'] : '';

// Prepare the SQL query with search filters
$sql = "
    SELECT p.product_id, p.product_name, p.product_price, p.product_category, p.created_at, p.status, 
           c.category_name, p.image_url, p.effective_date
    FROM product p
    JOIN category c ON p.product_category = c.category_id
    WHERE p.product_name LIKE ? OR c.category_name LIKE ? OR p.product_category LIKE ?
";

$stmt = $conn->prepare($sql);
$searchTerm = "%" . $searchQuery . "%";
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

// Display the filtered products
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $images = json_decode($row['image_url'], true);
        $firstImage = !empty($images) ? $images[0] : 'default.jpg';
        $product_id = $row['product_id'];
        $status = $row['status'];

        echo "<div class='product-card'>
                <div class='product-img' style='position: relative; cursor: pointer;' onclick=\"window.location.href='Product-Information.php?product_id={$product_id}'\">
                    <img class='card-img-top' src='../product_images/{$firstImage}' alt='Card image cap' 
                        data-product-id='{$product_id}' data-user-id='{$user_id}'>";

        if ($status === 'sold') {
            echo "<div class='sold-overlay' style='position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5em; font-weight: bold; text-transform: uppercase;'>Sold</div>";
        }

        echo "</div>
                <div class='product-card-text'>
                    <h4 class='product-price'>₱ {$row['product_price']}</h4>
                    <h3 class='product-name'>{$row['product_name']}</h3>
                    <p class='product-category'>{$row['category_name']}</p>
                    <p class='product-created'>Listed on " . date('Y-m-d h:i:s A', strtotime($row['created_at'])) . "</p>
                </div>
                <div class='product-buttons'>
                    <button type='button' class='markasavailable' id='markAsAvailableButton' onclick='handleMarkAsAvailable($product_id)' " . ($status === 'active' ? "style='display:none;'" : "") . ">Mark as Available</button>
                    <button type='button' class='markassold' id='markAsSoldButton' onclick='handleMarkAsSold($product_id)' " . ($status === 'sold' ? "style='display:none;'" : "") . ">Mark as Sold</button>
                    <div class='product-menu'>
                        <i class='bi bi-three-dots' onclick='toggleDropdown(\"$productId\")'></i>
                        <div class='dropdown-menu' id='$productId'>
                            <a href='#' onclick='viewProduct({$row['product_id']})'><i class='bi bi-eye dropdown-icon'></i> View</a>
                            <a href='#' onclick='editProduct({$row['product_id']})'><i class='bi bi-pencil-square dropdown-icon'></i>Edit</a>
                            <a href='#' onclick='deleteProduct({$row['product_id']})'><i class='bi bi-trash dropdown-icon'></i>Delete</a>
                        </div>
                    </div>
                </div>
            </div>";
    }
} else {
    echo "No products found.";
}

$stmt->close();
$conn->close();
?>
