<?php
$dutyMaster = [
    'key' => 'place',
    'title' => 'Place',
    'table' => 'duty_places',
    'query' => 'SELECT p.id, p.name, p.division_id, d.name AS division_name FROM duty_places p JOIN duty_divisions d ON d.id = p.division_id ORDER BY d.name, p.name',
    'has_division' => true,
    'has_photo' => false
];
include '_duty-master-page.php';
