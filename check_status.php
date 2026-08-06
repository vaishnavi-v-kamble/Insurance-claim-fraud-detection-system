<?php
include "db.php";

$claim_id = $_GET['id'];

$sql = "SELECT status FROM claims WHERE claim_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $claim_id);
$stmt->execute();

$result = $stmt->get_result();

if($row = $result->fetch_assoc()){
    echo json_encode($row);
}else{
    echo json_encode(["status"=>"Not Found"]);
}
?>