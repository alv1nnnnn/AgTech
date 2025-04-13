<?php
$servername = "localhost";
$username = "u856995433_agtech_root";
$password = "BUagtech2024";
$dbname = "u856995433_agtech";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>