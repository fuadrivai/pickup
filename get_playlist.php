<?php
header('Content-Type: application/json');
include("connect.php");

$playlist_sql = mysqli_query($connect, "SELECT * FROM display_contents WHERE is_active=1 ORDER BY sequence ASC");
$playlist = [];
while($row = mysqli_fetch_assoc($playlist_sql)) {
    $playlist[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $playlist]);
?>
