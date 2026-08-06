<?php
include "db.php";

$result = $conn->query("SELECT * FROM claims");
?>

<h2>Admin Panel</h2>

<table border="1" cellpadding="10">
<tr>
<th>Claim ID</th>
<th>Name</th>
<th>Vehicle</th>
<th>Cost</th>
<th>Status</th>
<th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()){ ?>
<tr>
<td><?= $row['claim_id'] ?></td>
<td><?= $row['name'] ?></td>
<td><?= $row['vehicle'] ?></td>
<td><?= $row['cost'] ?></td>
<td><?= $row['status'] ?></td>
<td>
<a href="approve.php?id=<?= $row['claim_id'] ?>">Approve</a> |
<a href="reject.php?id=<?= $row['claim_id'] ?>">Reject</a>
</td>
</tr>
<?php } ?>
</table>