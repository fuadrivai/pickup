<?php
include("../connect.php");
// menyimpan data kedalam variabel

$type = $_GET['type'];
// query SQL untuk insert data
if($type =='parents_card'){
    $rfidid_parents  = $_GET['rfidid_parents'];
    $date = date("Y-m-d");
    $query="DELETE FROM parents_card WHERE rfidid_parents = '$rfidid_parents'";
    mysqli_query($connect, $query);
    // mengalihkan ke halaman index.php
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}
?>