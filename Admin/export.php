<?php
session_start();

require_once '../Connection/connection.php';

if (!isset($_SESSION['user_id'])) {
    exit("You must be logged in to view the report.");
}

// Fetch query parameters safely
$format = isset($_GET['format']) ? $conn->real_escape_string($_GET['format']) : '';
$startDate = isset($_GET['startDate']) ? $conn->real_escape_string($_GET['startDate']) : null;
$endDate = isset($_GET['endDate']) ? $conn->real_escape_string($_GET['endDate']) : null;
$reportType = isset($_GET['reportType']) ? $conn->real_escape_string($_GET['reportType']) : '';

// Set default date range if not provided
$startDate = $startDate ?: '2000-01-01';
$endDate = $endDate ?: date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    exit("Invalid date format. Use YYYY-MM-DD.");
}

// Queries for sales and insights using prepared statements

// Sales data query (no filtering by user)
$salesStmt = $conn->prepare("
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
    WHERE t.status = 'completed' 
      AND t.timestamp BETWEEN ? AND ?
    ORDER BY t.timestamp DESC
");
$salesStmt->bind_param('ss', $startDate, $endDate);
$salesStmt->execute();
$salesResult = $salesStmt->get_result();

// Insights data query (no filtering by user)
$insightsStmt = $conn->prepare("
    SELECT 
        p.product_name, 
        SUM(pp.clicks_count) AS total_clicks,
        SUM(pp.wishlist_count) AS total_wishlist,
        pp.performance_date
    FROM product_performance pp
    INNER JOIN products p ON pp.product_id = p.product_id
    WHERE pp.performance_date BETWEEN ? AND ?
    GROUP BY p.product_name
    ORDER BY total_clicks DESC
");
$insightsStmt->bind_param('ss', $startDate, $endDate);
$insightsStmt->execute();
$insightsResult = $insightsStmt->get_result();

if (!$salesResult || !$insightsResult) {
    error_log("SQL Error: " . $conn->error);
    exit("Error retrieving report data.");
}

// Export CSV
if ($format === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_all.csv"');

    $output = fopen('php://output', 'w');

    // Export sales data
    if ($reportType === 'sales' || $reportType === 'all') {
        fputcsv($output, ['Transaction ID', 'Buyer Name', 'Seller Name', 'Product Name', 'Quantity', 'Total Amount', 'Date & Time']);
        while ($row = $salesResult->fetch_assoc()) {
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
    }

    // Export insights data
    if ($reportType === 'insights' || $reportType === 'all') {
        fputcsv($output, []); // Blank row for separation
        fputcsv($output, ['Product Name', 'Clicks Count', 'Wishlist Count', 'Performance Date']);
        while ($row = $insightsResult->fetch_assoc()) {
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

// Export PDF
elseif ($format === 'pdf') {
    require_once('TCPDF/tcpdf.php');
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Add logo
    $logoPath = '../images/agtech-logo.png';
    if (file_exists($logoPath)) {
        $logoWidth = 40;
        $pageWidth = $pdf->getPageWidth();
        $xPosition = ($pageWidth - $logoWidth) / 2;
        $topPadding = 15;
        $pdf->Image($logoPath, $xPosition, $topPadding, $logoWidth);
    }
    $pdf->Ln(20);

    // Export sales report
    if ($reportType === 'sales' || $reportType === 'all') {
        $pdf->writeHTML('<h1>Sales Report</h1>', true, false, true, false, 'C');
        $html = '<table border="1" cellpadding="4"><tr>
                    <th>ID</th><th>Buyer Name</th><th>Seller Name</th><th>Product Name</th><th>Quantity</th><th>Total Amount</th><th>Date & Time</th>
                </tr>';
        while ($row = $salesResult->fetch_assoc()) {
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
        $pdf->writeHTML($html);
    }

    // Export insights report
    if ($reportType === 'insights' || $reportType === 'all') {
        $pdf->AddPage();
        $pdf->writeHTML('<h1>Market Insights Report</h1>', true, false, true, false, 'C');
        $html = '<table border="1" cellpadding="4"><tr>
                    <th>Product Name</th><th>Clicks Count</th><th>Wishlist Count</th><th>Performance Date</th>
                </tr>';
        while ($row = $insightsResult->fetch_assoc()) {
            $html .= "<tr>
                        <td>{$row['product_name']}</td>
                        <td>{$row['total_clicks']}</td>
                        <td>{$row['total_wishlist']}</td>
                        <td>{$row['performance_date']}</td>
                    </tr>";
        }
        $html .= '</table>';
        $pdf->writeHTML($html);
    }

    $pdf->Output('report_all.pdf', 'D');
    $conn->close();
    exit();
} else {
    exit("Invalid export format.");
}
?>
