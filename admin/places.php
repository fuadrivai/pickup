<?php
$dutyMaster = [
    'key' => 'place',
    'title' => 'Place',
    'table' => 'duty_places',
    'query' => 'SELECT id, name FROM duty_places ORDER BY name',
    'has_division' => false,
    'has_photo' => false
];
include '_duty-master-page.php';
