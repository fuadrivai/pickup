<?php 

include("connect.php");

$rfidid = $_POST['nama'];
$sql = mysqli_query($connect, "SELECT * FROM parents_card where rfidid_parents = '$rfidid'");
$student_d = mysqli_fetch_array($sql);
$nama = $student_d['rfidid'];
$insert = mysqli_query($connect, "update student set tiime=now() , status = '', play = 'not yet' where rfidid='$nama'");
$result = mysqli_query($connect, "SELECT student.phone as student, homeroom.phone as homeroom, rfidid, student_name FROM student LEFT JOIN homeroom on student.grade = homeroom.grade where rfidid='$nama'");
while ($row = mysqli_fetch_array($result)) {
    if (!($row['homeroom'] == NULL)){
        
        $data = [
            'api_key' => '7bd77f56d1e7fc38a07739594d0b4b7c0f0e594c',
            'sender'  => '325293',
            'number'  => $row['homeroom'],
            'message' => 'Pickup for Ananda '.$row['student_name'].' Please go to the Front Gate'
        ];
        $url = "https://mhisnetshield.us/apiv2/send-message.php";
        // $url = "https://whatsapp.mhis.link/apiv2/send-message.php";
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($data))
        );

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }
    
}

?>