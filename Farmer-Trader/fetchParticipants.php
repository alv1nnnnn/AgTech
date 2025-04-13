<?php

session_start();

require '../Connection/connection.php'; // Ensure you include your database connection

// Check if user_id is set in the session
if (!isset($_SESSION['user_id'])) {
    echo 'User not logged in.';
    exit();
}

$type = $_GET['type']; // Get the type (sender or receiver)
$user_id = $_SESSION['user_id']; // Get user_id from the session

$type = $_GET['type']; // Get the type (sender or receiver)
$user_id = $_SESSION['user_id']; // Assuming you store user_id in session

// Determine the column to fetch based on the type
$column = ($type === 'sender') ? 'sender_id' : 'receiver_id';

// Prepare SQL to fetch participants based on the selected type
$sql = "
    SELECT DISTINCT p.user_id, p.first_name, p.last_name, p.profile, c.product_id
    FROM chat c
    JOIN user p ON p.user_id = c.$column
    WHERE c.$column = ? OR c.receiver_id = ?
    ORDER BY c.sent_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$participants = [];
while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
}

foreach ($participants as $participant):
    $product_name = '';

    if ($participant['product_id'] > 0) {
        $productSql = "SELECT product_name FROM Products WHERE product_id = ?";
        $stmtProduct = $conn->prepare($productSql);
        $stmtProduct->bind_param("i", $participant['product_id']);
        $stmtProduct->execute();
        $stmtProduct->bind_result($product_name);
        $stmtProduct->fetch();
        $stmtProduct->close();
    }
?>
    <li class="person" data-chat="<?php echo $participant['id']; ?>" data-product-id="<?php echo $participant['product_id']; ?>">
        <?php if (empty($participant['profile'])): ?>
            <div class="avatar" style="width: 45px; height: 45px; background-color: #F9BF29; color: green; border-radius: 50%; text-align: center; line-height: 50px; font-size: 26px;">
                <?php echo strtoupper(substr($participant['name'], 0, 1)); ?>
            </div>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($participant['profile']); ?>" alt="Profile Picture" class="profile-pic">
        <?php endif; ?>
        <span class="receiver_name"><?php echo htmlspecialchars($participant['name']); ?></span>
        <span>-</span>
        <span class="product_name"><?php echo htmlspecialchars($product_name); ?></span>
        <span class="time">
            <?php
                $latestMessageSql = "SELECT sent_at FROM chat WHERE ($column = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at DESC LIMIT 1";
                $stmtLatestMessage = $conn->prepare($latestMessageSql);
                $stmtLatestMessage->bind_param("iiii", $user_id, $participant['id'], $participant['id'], $user_id);
                $stmtLatestMessage->execute();
                $stmtLatestMessage->bind_result($latestMessageTime);
                if ($stmtLatestMessage->fetch()) {
                    echo date('h:i A', strtotime($latestMessageTime)); 
                }
                $stmtLatestMessage->close();
            ?>
        </span>
        <span class="preview">
            <?php
                $latestMessageSql = "SELECT sender_id, message FROM chat WHERE product_id = ? AND (($column = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY sent_at DESC LIMIT 1";
                $stmtLatestMessage = $conn->prepare($latestMessageSql);
                $stmtLatestMessage->bind_param("iiiii", $participant['product_id'], $user_id, $participant['id'], $participant['id'], $user_id);
                $stmtLatestMessage->execute();
                $stmtLatestMessage->bind_result($senderId, $latestMessagePreview);
                if ($stmtLatestMessage->fetch()) {
                    $isSender = ($senderId == $user_id);
                    $previewText = htmlspecialchars(substr($latestMessagePreview, 0, 30)) . (strlen($latestMessagePreview) > 30 ? '...' : '');
                    echo $isSender ? "You: $previewText" : $previewText;
                }
                $stmtLatestMessage->close();
            ?>
        </span>
    </li>
<?php endforeach; ?>
