<?php
session_start();
include '../Connection/connection.php'; // Ensure you have a file for database connection

// Get the logged-in user's ID
$userId = $_SESSION['user_id'] ?? 0; // Adjust according to your session variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['reportType'];

    if ($reportType === 'sales') {
        $query = "SELECT 
    t.transaction_id, 
    t.status, 
    t.timestamp, 
    b.first_name AS buyer_first_name, 
    b.last_name AS buyer_last_name, 
    s.first_name AS seller_first_name, 
    s.last_name AS seller_last_name,
    td.product_id,
    td.quantity,
    td.amount,
    td.AgreedPrice,
    t.timestamp,
    p.product_name,
    pp.current_price
FROM 
    transaction t
INNER JOIN 
    user b ON t.buyer_id = b.user_id
INNER JOIN 
    user s ON t.seller_id = s.user_id
INNER JOIN 
    transaction_details td ON t.transaction_id = td.transaction_id
INNER JOIN 
    products p ON td.product_id = p.product_id
INNER JOIN 
    productprices pp ON p.product_id = pp.product_id
WHERE 
    t.status = 'completed' AND t.seller_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $salesData = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $salesData[] = [
                    'transaction_id' => $row['transaction_id'],
                    'timestamp' => date('m/d/Y h:i A', strtotime($row['timestamp'])), // Convert to 12-hour format with AM/PM
                    'buyer_name' => $row['buyer_first_name'] . ' ' . $row['buyer_last_name'],
                    'seller_name' => $row['seller_first_name'] . ' ' . $row['seller_last_name'],
                    'product_name' => $row['product_name'],
                    'quantity' => $row['quantity'],
                    'selling_price' => $row['current_price'],
                    'agreed_price' => $row['AgreedPrice'],
                    'amount' => $row['amount']
                ];
            }
        }

        echo json_encode($salesData);
        exit;
        
    } elseif ($reportType === 'insights') {
    $query = "SELECT 
                  p.product_name,
                  SUM(pp.clicks_count) AS total_clicks,
                  SUM(pp.wishlist_count) AS total_wishlist
              FROM 
                  product_performance pp
              INNER JOIN 
                  userproducts up ON pp.product_id = up.product_id
              INNER JOIN 
                  products p ON pp.product_id = p.product_id
              WHERE 
                  up.user_id = ?
              GROUP BY 
                  p.product_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $insightsData = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $insightsData[] = [
                'product_name' => $row['product_name'],
                'total_clicks' => $row['total_clicks'],
                'total_wishlist' => $row['total_wishlist']
            ];
        }
    }

    echo json_encode($insightsData);
    exit;
    } else {
        echo json_encode(['error' => 'Invalid report type selected.']);
    }
}
?>
