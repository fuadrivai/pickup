<?php
header('Content-Type: application/json; charset=utf-8');
include 'connect.php';
date_default_timezone_set('Asia/Jakarta');

$weekday = (int) date('N');
$currentTime = date('H:i:s');
$weekdayNames = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
    7 => 'Minggu'
];

$query = '
    SELECT
        r.id AS roster_id,
        r.title AS roster_title,
        r.academic_year,
        s.id AS slot_id,
        s.section_title,
        s.assignment_title,
        s.start_time,
        s.end_time,
        s.notes AS slot_notes,
        s.sort_order,
        p.id AS place_id,
        p.name AS place_name,
        e.name AS event_name,
        t.id AS teacher_id,
        t.name AS teacher_name,
        t.photo_path AS teacher_photo,
        teacher_division.name AS teacher_division_name
    FROM duty_rosters r
    LEFT JOIN duty_roster_slots s
        ON s.roster_id = r.id
        AND s.start_time <= ?
        AND s.end_time > ?
    LEFT JOIN duty_places p ON p.id = s.place_id
    LEFT JOIN duty_events e ON e.id = s.event_id
    LEFT JOIN duty_roster_assignments a ON a.slot_id = s.id AND a.weekday = ?
    LEFT JOIN duty_teachers t ON t.id = a.teacher_id
    LEFT JOIN duty_divisions teacher_division ON teacher_division.id = t.division_id
    WHERE r.id = (
        SELECT newest.id
        FROM duty_rosters newest
        WHERE newest.is_active = 1
        ORDER BY newest.created_at DESC, newest.id DESC
        LIMIT 1
    )
    ORDER BY r.academic_year DESC, s.sort_order ASC, s.start_time ASC, s.id ASC, t.name ASC';

$statement = mysqli_prepare($connect, $query);
if (
    !$statement ||
    !mysqli_stmt_bind_param($statement, 'ssi', $currentTime, $currentTime, $weekday) ||
    !mysqli_stmt_execute($statement)
) {
    error_log('Gagal mengambil data duty roster untuk display: ' . ($statement ? mysqli_stmt_error($statement) : mysqli_error($connect)));
    if ($statement) {
        mysqli_stmt_close($statement);
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil data duty roster.']);
    exit;
}

$result = mysqli_stmt_get_result($statement);
if (!$result) {
    error_log('Gagal membaca hasil duty roster untuk display: ' . mysqli_stmt_error($statement));
    mysqli_stmt_close($statement);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membaca data duty roster.']);
    exit;
}

$rosters = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rosterId = (int) $row['roster_id'];
    if (!isset($rosters[$rosterId])) {
        $rosters[$rosterId] = [
            'roster_title' => $row['roster_title'],
            'academic_year' => $row['academic_year'],
            'areas' => []
        ];
    }

    if ($row['slot_id'] === null) {
        continue;
    }

    $areaKey = $row['place_id'] !== null
        ? 'place:' . $row['place_id']
        : 'other:' . ($row['assignment_title'] ?: $row['event_name'] ?: 'other');
    $areaName = $row['place_name'] ?: ($row['assignment_title'] ?: $row['event_name'] ?: 'Area belum ditentukan');
    if (!isset($rosters[$rosterId]['areas'][$areaKey])) {
        $rosters[$rosterId]['areas'][$areaKey] = [
            'place_name' => $areaName,
            'slots' => []
        ];
    }

    $slotId = (int) $row['slot_id'];
    if (!isset($rosters[$rosterId]['areas'][$areaKey]['slots'][$slotId])) {
        $rosters[$rosterId]['areas'][$areaKey]['slots'][$slotId] = [
            'section_title' => $row['section_title'],
            'assignment_title' => $row['assignment_title'],
            'start_time' => substr($row['start_time'], 0, 5),
            'end_time' => substr($row['end_time'], 0, 5),
            'event_name' => $row['event_name'],
            'notes' => $row['slot_notes'],
            'teachers' => []
        ];
    }

    if ($row['teacher_id'] !== null) {
        $rosters[$rosterId]['areas'][$areaKey]['slots'][$slotId]['teachers'][] = [
            'name' => $row['teacher_name'],
            'photo' => $row['teacher_photo'],
            'division' => $row['teacher_division_name']
        ];
    }
}

mysqli_stmt_close($statement);

foreach ($rosters as &$roster) {
    foreach ($roster['areas'] as &$area) {
        $area['slots'] = array_values($area['slots']);
    }
    unset($area);
    $roster['areas'] = array_values($roster['areas']);
}
unset($roster);

echo json_encode([
    'status' => 'success',
    'weekday' => $weekday,
    'weekday_name' => $weekdayNames[$weekday],
    'current_time' => substr($currentTime, 0, 5),
    'data' => array_values($rosters)
], JSON_UNESCAPED_UNICODE);
?>
