<?php
$dutyMaster = [
    'key' => 'event',
    'title' => 'Event Run',
    'table' => 'duty_events',
    'query' => 'SELECT id, name FROM duty_events ORDER BY name',
    'has_division' => false,
    'has_photo' => false
];
include '_duty-master-page.php';
