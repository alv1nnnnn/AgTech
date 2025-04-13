<?php
session_start();

require_once '../Connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    exit("You must be logged in to view the report.");
}

// Fetch query parameters safely
$userId = isset($_GET['userId']) ? intval($_GET['userId']) : intval($_SESSION['user_id']);
$reportType = isset($_GET['reportType']) ? $conn->real_escape_string($_GET['reportType']) : '';
$format = isset($_GET['format']) ? $conn->real_escape_string($_GET['format']) : '';
$startDate = isset($_GET['startDate']) ? $conn->real_escape_string($_GET['startDate']) : null;
$endDate = isset($_GET['endDate']) ? $conn->real_escape_string($_GET['endDate']) : null;

// Validate date format
if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = null;
if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $endDate = null;

// Construct the query
if ($reportType === 'sales') {
    $sql = "
    SELECT 
        t.transaction_id, 
        CONCAT(b.first_name, ' ', b.last_name) AS buyer_name, 
        CONCAT(s.first_name, ' ', s.last_name) AS seller_name,
        p.product_name,
        td.quantity,
        td.amount,
        t.timestamp
    FROM transaction t
    INNER JOIN user b ON t.buyer_id = b.user_id
    INNER JOIN user s ON t.seller_id = s.user_id
    INNER JOIN transaction_details td ON t.transaction_id = td.transaction_id
    INNER JOIN products p ON td.product_id = p.product_id
    WHERE t.status = 'completed' AND t.seller_id = $userId";
    
    if ($startDate && $endDate) {
        $sql .= " AND t.timestamp BETWEEN '$startDate 00:00:00' AND '$endDate 23:59:59'";
    }

    $sql .= " ORDER BY t.timestamp DESC";
} elseif ($reportType === 'insights') {
    $sql = "
    SELECT 
        p.product_name, 
        SUM(pp.clicks_count) AS total_clicks,
        SUM(pp.wishlist_count) AS total_wishlist,
        pp.performance_date
    FROM product_performance pp
    INNER JOIN userproducts up ON pp.product_id = up.product_id
    INNER JOIN products p ON pp.product_id = p.product_id
    WHERE up.user_id = $userId";

    if ($startDate && $endDate) {
        $sql .= " AND pp.performance_date BETWEEN '$startDate' AND '$endDate'";
    }

   $sql .= " GROUP BY p.product_name ORDER BY total_clicks DESC";
} else {
    exit("Invalid report type.");
}

$result = $conn->query($sql);
if (!$result) {
    error_log("SQL Error: " . $conn->error);
    exit("Error retrieving report data.");
}

// Export CSV
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $reportType . '.csv"');

    $output = fopen('php://output', 'w');
    if ($reportType === 'sales') {
        fputcsv($output, ['Transaction ID', 'Buyer Name', 'Seller Name', 'Product Name', 'Quantity', 'Total Amount', 'Date & Time']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['transaction_id'],
                $row['buyer_name'],
                $row['seller_name'],
                $row['product_name'],
                $row['quantity'],
                $row['amount'],
                $row['timestamp'],
            ]);
        }
    } elseif ($reportType === 'insights') {
        fputcsv($output, ['Product Name', 'Clicks Count', 'Wishlist Count', 'Performance Date']);
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['product_name'],
                $row['total_clicks'],
                $row['total_wishlist'],
                $row['performance_date'],
            ]);
        }
    }
    fclose($output);
    $conn->close();
    exit();
}

// Export PDF (optional adjustments can be made here)
elseif ($format === 'pdf') {
    require_once('TCPDF/tcpdf.php');
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
        
// Calculate the x position to center the logo
    $logoPath = '../images/agtech-logo.png'; // Ensure the correct path to your logo
    if (file_exists($logoPath)) {
        $logoWidth = 40; // Set the width of the logo
        $pageWidth = $pdf->getPageWidth(); // Get the page width
        $xPosition = ($pageWidth - $logoWidth) / 2; // Calculate the center position
        $topPadding = 15; // Define the top padding (increase this value for more padding)

        $pdf->Image($logoPath, $xPosition, $topPadding, $logoWidth); // Position the logo with top padding
    }

    $pdf->Ln(20); // Add a line break after the logo and title to create space



    // Generate report content
    if ($reportType === 'sales') {
        $html = '<h1>Sales Report</h1><table border="1" cellpadding="4"><tr>
                    <th>ID</th><th>Buyer Name</th><th>Seller Name</th><th>Product Name</th><th>Quantity</th><th>Total Amount</th><th>Date & Time</th>
                </tr>';
        while ($row = $result->fetch_assoc()) {
            $html .= "<tr>
                        <td>{$row['transaction_id']}</td>
                        <td>{$row['buyer_name']}</td>
                        <td>{$row['seller_name']}</td>
                        <td>{$row['product_name']}</td>
                        <td>{$row['quantity']}</td>
                        <td>{$row['amount']}</td>
                        <td>{$row['timestamp']}</td>
                    </tr>";
        }
        $html .= '</table>';
    } elseif ($reportType === 'insights') {
        $html = '<h1>Market Insights Report</h1><table border="1" cellpadding="4"><tr>
                    <th>Product Name</th><th>Clicks Count</th><th>Wishlist Count</th><th>Performance Date</th>
                </tr>';
        while ($row = $result->fetch_assoc()) {
            $html .= "<tr>
                        <td>{$row['product_name']}</td>
                        <td>{$row['total_clicks']}</td>
                        <td>{$row['total_wishlist']}</td>
                        <td>{$row['performance_date']}</td>
                    </tr>";
        }
        $html .= '</table>';
    }
    $pdf->writeHTML($html);
    $pdf->Output("report_$reportType.pdf", 'D');
    $conn->close();
    exit();
} else {
    exit("Invalid export format.");
}
?>
