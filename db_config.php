<?php
// Update these values based on your setup (local or RDS)
$db_host = "localhost"; // Use RDS endpoint if deploying to AWS
$db_user = "root";      // Use "admin" for RDS
$db_pass = "";          // Use your RDS password
$db_name = "ecommerce_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>