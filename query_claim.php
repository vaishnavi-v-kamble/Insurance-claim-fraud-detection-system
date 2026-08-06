<?php
include "db.php";
$claim = $argv[1] ?? '';
if (!$claim) { echo "Usage: php query_claim.php <CLAIM_ID>\n"; exit(1); }
$stmt = $conn->prepare("SELECT * FROM claims WHERE claim_id = ? LIMIT 1");
$stmt->bind_param('s', $claim);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
if ($row) {
    echo json_encode($row, JSON_PRETTY_PRINT) . PHP_EOL;
} else {
    echo "Not found\n";
}
?>