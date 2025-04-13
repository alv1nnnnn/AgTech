<?php
require_once '../Connection/connection.php';

$query = isset($_GET['query']) ? mysqli_real_escape_string($conn, $_GET['query']) : '';

// SQL query to search in specific columns for admin users
$sql = "
    SELECT user_id, first_name, last_name, phone_number, email, user_type, profile 
    FROM user 
    WHERE user_type = 'admin' 
    AND (
        first_name LIKE '%$query%' OR 
        last_name LIKE '%$query%' OR 
        email LIKE '%$query%' OR 
        phone_number LIKE '%$query%'
    )
";

$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $profileImage = !empty($row['profile']) ? "../images/" . htmlspecialchars($row['profile']) : "../images/default-profile.png";
        
        echo "<tr>";
        echo "<td><img src='" . $profileImage . "' alt='Profile Picture' class='user-profile'></td>";
        echo "<td>" . htmlspecialchars($row['first_name']) . " " . htmlspecialchars($row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['phone_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
        echo "<td>Active</td>";
        echo "<td>";
        echo "<button class='btn btn-sm btn-primary' onclick='editUser(" . $row['user_id'] . ")'><i class='bi bi-pencil-square'></i></button> ";
        echo "<button class='btn btn-sm btn-danger' onclick='deleteUser(" . $row['user_id'] . ")'><i class='bi bi-trash'></i></button>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7'>No results found</td></tr>";
}

mysqli_close($conn);
?>
