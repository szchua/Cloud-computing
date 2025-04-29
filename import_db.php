<?php
// Update these values based on your setup (local or RDS)
$db_host = "ecommerce-db.cfnp0oilx0it.us-east-1.rds.amazonaws.com"; // Use RDS endpoint if deploying to AWS
$db_user = "admin";      // Use "admin" for RDS
$db_pass = "lab-password";          // Use your RDS password
$db_name = "ecommerce_db";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>