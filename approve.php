<?php
include "db.php";
$id = $_GET['id'];

$conn->query("UPDATE claims SET status='Approved' WHERE claim_id='$id'");

header("Location: admin.php");
?>