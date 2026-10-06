<?php
include("../connect.php");
// menyimpan data kedalam variabel


// query SQL untuk insert data

    $rfidid_parents  = $_GET['id'];
    $date = date("Y-m-d");
    $query="DELETE FROM student WHERE id = '$rfidid_parents'";
    mysqli_query($connect, $query);
    header('Location: student-list.php');

?>