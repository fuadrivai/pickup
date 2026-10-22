<?php
$dutyMaster = [
    'key' => 'division',
    'title' => 'Divisi',
    'table' => 'duty_divisions',
    'query' => 'SELECT id, name FROM duty_divisions ORDER BY name ASC',
    'has_division' => false,
    'has_photo' => false
];
include '_duty-master-page.php';
