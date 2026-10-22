<?php
$dutyMaster = [
    'key' => 'teacher',
    'title' => 'Guru',
    'table' => 'duty_teachers',
    'query' => 'SELECT t.id, t.name, t.photo_path, t.division_id, d.name AS division_name FROM duty_teachers t JOIN duty_divisions d ON d.id = t.division_id ORDER BY d.name, t.name',
    'has_division' => true,
    'has_photo' => true
];
include '_duty-master-page.php';
