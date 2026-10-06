<?php
    include("connect.php");
    $sql = mysqli_query($connect, "SELECT * FROM student where tiime > NOW() - INTERVAL 720 SECOND AND status = '' ORDER BY tiime DESC");
    $result = array();
    $count = mysqli_num_rows($sql);
        if ($count > 0){
            while ($row = mysqli_fetch_assoc($sql)) {
            $data[] = $row;
        }
    }else{
        $data = [];
    }
    echo json_encode(array("result" => $data));
?>