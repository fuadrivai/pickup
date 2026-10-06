<?php 

include("connect.php");

$nama = $_POST['nama'];
$data = json_decode(file_get_contents('php://input'), true); 
$nama = $data['nama'];
$insert = mysqli_query($connect, "update student set tiime=now() , status = '', play = 'done' where rfidid='$nama'");


?>