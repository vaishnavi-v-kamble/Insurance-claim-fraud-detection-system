<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['claim_id']) || !isset($data['status'])) {
    echo json_encode(["status" => "error", "msg" => "Invalid request"]);
    exit;
}

$claim_id = $conn->real_escape_string($data['claim_id']);
$new_status = $conn->real_escape_string($data['status']);

$sql = "UPDATE claims SET status = '$new_status' WHERE claim_id = '$claim_id'";

if ($conn->query($sql)) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "msg" => $conn->error]);
}
?>
