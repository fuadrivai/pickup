<?php
header('Content-Type: application/json');

$url = "https://calendar.mutiaraharapan.sch.id/api/schedule?branch=1&division=1,2,3,4&category=Public";

// Fetch the JSON from the API
$response = @file_get_contents($url);

if ($response === FALSE) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to fetch API']);
    exit;
}

$events = json_decode($response, true);
if (!is_array($events)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON from API']);
    exit;
}

// Get tomorrow's start and end timestamps
$target_start = strtotime('tomorrow midnight');
$target_end = strtotime('+2 days midnight') - 1;

$filtered_events = [];

foreach ($events as $event) {
    if (!isset($event['starttime']) || !isset($event['endtime'])) continue;
    
    $start_ts = strtotime($event['starttime']);
    $end_ts = strtotime($event['endtime']);
    
    // Check if the event overlaps with tomorrow
    if ($start_ts <= $target_end && $end_ts >= $target_start) {
        $filtered_events[] = $event;
    }
}

// Sort filtered events by start time
usort($filtered_events, function($a, $b) {
    return strtotime($a['starttime']) - strtotime($b['starttime']);
});

echo json_encode(['status' => 'success', 'data' => $filtered_events]);
?>
