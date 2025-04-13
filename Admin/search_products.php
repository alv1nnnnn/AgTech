<?php
require_once '../Connection/connection.php';

// Get search query and filter
$query = isset($_GET['query']) ? mysqli_real_escape_string($conn, $_GET['query']) : '';
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'all';

// SQL query with JOINs to fetch all required fields
$sql = "
    SELECT 
        p.product_id, 
        p.product_name, 
        c.category_name, 
        pp.current_price, 
        CONCAT(u.first_name, ' ', u.last_name) AS owner_name, 
        p.location
    FROM 
        products p
    LEFT JOIN 
        category c 
    ON 
        p.category_id = c.category_id
    LEFT JOIN 
        productprices pp 
    ON 
        p.product_id = pp.product_id
    LEFT JOIN 
        userproducts up 
    ON 
        p.product_id = up.product_id
    LEFT JOIN 
        user u 
    ON 
        up.user_id = u.user_id
    WHERE 1=1
";

// Add filter for product status
if ($filter === 'active') {
    $sql .= " AND p.status = 'active'";
} elseif ($filter === 'sold') {
    $sql .= " AND p.status = 'sold'";
}

// Add search query filter
if (!empty($query)) {
    $sql .= " AND (p.product_name LIKE '%$query%' OR c.category_name LIKE '%$query%' OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$query%')";
}

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['product_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['current_price']) . "</td>";
        echo "<td>" . htmlspecialchars($row['owner_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['location']) . "</td>";
        echo "<td>";
        echo "<button class='btn btn-sm btn-primary edit-btn' 
                data-id='" . $row['product_id'] . "' 
                data-name='" . $row['product_name'] . "' 
                data-category='" . $row['category_name'] . "' 
                data-price='" . $row['current_price'] . "' 
                data-owner='" . $row['owner_name'] . "' 
                data-location='" . $row['location'] . "'>
                <i class='bi bi-pencil-square'></i>
                </button> ";
        echo "<button class='btn btn-sm btn-danger' onclick='deleteProduct(" . $row['product_id'] . ")'><i class='bi bi-trash'></i></button>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No products found</td></tr>";
}

mysqli_close($conn);
?>
