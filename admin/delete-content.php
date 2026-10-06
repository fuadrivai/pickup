<?php
include("../connect.php");
$id = $_GET['id'];
if($id) {
    mysqli_query($connect, "DELETE FROM display_contents WHERE id='$id'");
}
header("location: display-contents.php");
?>
