<?php
include("../connect.php");
$id = $_GET['id'];
if($id) {
    mysqli_query($connect, "DELETE FROM schedules WHERE id='$id'");
}
header("location: schedules.php");
?>
