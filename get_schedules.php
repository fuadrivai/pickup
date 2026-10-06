<?php
header('Content-Type: application/json');
include("connect.php");

$today_date = date('Y-m-d');
// date('w') returns 0 (for Sunday) through 6 (for Saturday)
$day_of_week = date('w'); 

// We use FIND_IN_SET to check if the day_of_week is in the comma-separated repeat_days string
$query = "SELECT * FROM schedules 
          WHERE is_active = 1 
          AND (
              (is_repeating = 0 AND event_date = '$today_date') 
              OR 
              (is_repeating = 1 AND FIND_IN_SET('$day_of_week', repeat_days) > 0)
          )
          AND start_time <= ADDTIME(CURTIME(), '04:00:00')
          AND end_time >= SUBTIME(CURTIME(), '01:00:00')
          ORDER BY start_time ASC";

$sql = mysqli_query($connect, $query);
$events = [];

while($row = mysqli_fetch_assoc($sql)) {
    // Format times for display (e.g., 08:00)
    $row['start_time_formatted'] = date('H:i', strtotime($row['start_time']));
    $row['end_time_formatted'] = date('H:i', strtotime($row['end_time']));
    $events[] = $row;
}

echo json_encode(['status' => 'success', 'data' => $events]);
?>
