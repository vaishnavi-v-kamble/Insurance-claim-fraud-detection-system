<?php
$conn = new mysqli("localhost", "root", "", "insurance_db", 3308);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>