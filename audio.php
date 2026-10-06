<?php
    include("connect.php");
     $sql = mysqli_query($connect, "SELECT * FROM student where tiime > NOW() - INTERVAL 5 SECOND AND play = 'not yet' ORDER BY tiime DESC");
    $result = array();
    
    while ($row = mysqli_fetch_assoc($sql)) {
        $data[] = $row;
    }

    echo json_encode(array("result" => $data));
?>