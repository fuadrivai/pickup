<?php
include("../connect.php");
// menyimpan data kedalam variabel

$type = $_GET['type'];
// query SQL untuk insert data
if ($type =='edit-student'){
    $student_name   = $_POST['student_name'];
    $grade          = $_POST['grade'];
    $rfidid          = $_POST['rfidid'];    
    $id          = $_POST['id'];    
$query="UPDATE student SET student_name='$student_name',grade='$grade',rfidid='$rfidid' where id='$id'";
mysqli_query($connect, $query);
// mengalihkan ke halaman index.php
header("location:/filewebhook/pickup/admin/student-list.php");
}else if($type =='display-settings'){
    $display_type = $_POST['display_type'];
    $link = $_POST['link'] ?? '';
    $content_schedule = $_POST['content_schedule'] ?? '';
    $content_image_query = "";

    if ($display_type == 'slide' && isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
        $target_dir = "../admin/img/";
        $file_name = time() . "_" . basename($_FILES["slide_image"]["name"]);
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["slide_image"]["tmp_name"], $target_file)) {
            $content_image_query = ", content_image='admin/img/$file_name'";
        }
    }

    $link_escaped = mysqli_real_escape_string($connect, $link);
    $schedule_escaped = mysqli_real_escape_string($connect, $content_schedule);
    $type_escaped = mysqli_real_escape_string($connect, $display_type);

    $query="UPDATE video SET display_type='$type_escaped', link='$link_escaped', content_schedule='$schedule_escaped' $content_image_query where id= 1";
    mysqli_query($connect, $query);
    
    // Check if path uses filewebhook based on previous code logic
    header("location: video-embed.php");
}else if($type =='parents_card'){
    $rfidid = isset($_POST['rfidid']) ? trim((string) $_POST['rfidid']) : '';
    $rfidid_parents = isset($_POST['rfidid_parents']) ? trim((string) $_POST['rfidid_parents']) : '';
    if ($rfidid === '' || $rfidid_parents === '') {
        header('Location: parents_card.php?rfidid=' . rawurlencode($rfidid) . '&status=invalid');
        exit;
    }

    $date = date("Y-m-d");
    $statement = mysqli_prepare(
        $connect,
        "INSERT INTO parents_card (rfidid, rfidid_parents, registered_date) VALUES (?, ?, ?)"
    );
    if (!$statement) {
        error_log('Gagal menyiapkan penambahan kartu orang tua: ' . mysqli_error($connect));
        header('Location: parents_card.php?rfidid=' . rawurlencode($rfidid) . '&status=error');
        exit;
    }

    if (
        !mysqli_stmt_bind_param($statement, 'sss', $rfidid, $rfidid_parents, $date) ||
        !mysqli_stmt_execute($statement)
    ) {
        error_log('Gagal menambahkan kartu orang tua: ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        header('Location: parents_card.php?rfidid=' . rawurlencode($rfidid) . '&status=error');
        exit;
    }

    mysqli_stmt_close($statement);
    header('Location: parents_card.php?rfidid=' . rawurlencode($rfidid) . '&status=added');
    exit;
}
?>