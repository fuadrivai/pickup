<?php
include("../connect.php");

$id = $_POST['id'] ?? '';
$event_title = mysqli_real_escape_string($connect, $_POST['event_title']);
$start_time = mysqli_real_escape_string($connect, $_POST['start_time']);
$end_time = mysqli_real_escape_string($connect, $_POST['end_time']);
$is_repeating = (int)$_POST['is_repeating'];
$is_active = (int)$_POST['is_active'];

$event_date = 'NULL';
$repeat_days = "''";

if ($is_repeating == 0) {
    $date_val = mysqli_real_escape_string($connect, $_POST['event_date']);
    if (empty($date_val)) {
        $event_date = 'NULL';
    } else {
        $event_date = "'$date_val'";
    }
} else {
    if(isset($_POST['repeat_days'])) {
        $days = implode(',', $_POST['repeat_days']);
        $repeat_days = "'$days'";
    }
}

if ($id) {
    $query = "UPDATE schedules SET 
                event_title='$event_title', 
                start_time='$start_time', 
                end_time='$end_time', 
                is_repeating=$is_repeating, 
                event_date=$event_date,
                repeat_days=$repeat_days,
                is_active=$is_active
              WHERE id=$id";
} else {
    $query = "INSERT INTO schedules (event_title, start_time, end_time, is_repeating, event_date, repeat_days, is_active) 
              VALUES ('$event_title', '$start_time', '$end_time', $is_repeating, $event_date, $repeat_days, $is_active)";
}

if (!mysqli_query($connect, $query)) {
    die("Database Error: " . mysqli_error($connect) . "<br>Query: " . $query);
}
header("location: schedules.php");
?>
