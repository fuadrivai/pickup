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
        d.id AS division_id,
        d.name AS division_name,
        s.id AS slot_id,
        s.section_title,
        s.assignment_title,
        s.start_time,
        s.end_time,
        s.notes AS slot_notes,
        p.name AS place_name,
        e.name AS event_name,
        t.id AS teacher_id,
        t.name AS teacher_name,
        t.photo_path AS teacher_photo,
        teacher_division.name AS teacher_division_name
    FROM duty_rosters r
    JOIN duty_divisions d ON d.id = r.division_id
    LEFT JOIN duty_roster_slots s
        ON s.roster_id = r.id
        AND s.start_time <= ?
        AND s.end_time > ?
    LEFT JOIN duty_places p ON p.id = s.place_id
    LEFT JOIN duty_events e ON e.id = s.event_id
    LEFT JOIN duty_roster_assignments a ON a.slot_id = s.id AND a.weekday = ?
    LEFT JOIN duty_teachers t ON t.id = a.teacher_id
    LEFT JOIN duty_divisions teacher_division ON teacher_division.id = t.division_id
    WHERE r.is_active = 1
      AND NOT EXISTS (
          SELECT 1
          FROM duty_rosters newer
          WHERE newer.division_id = r.division_id
            AND newer.is_active = 1
            AND (
                newer.created_at > r.created_at
                OR (newer.created_at = r.created_at AND newer.id > r.id)
            )
      )
    ORDER BY d.name ASC, s.sort_order ASC, s.start_time ASC, s.id ASC, t.name ASC';

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

$divisions = [];
while ($row = mysqli_fetch_assoc($result)) {
    $divisionId = (int) $row['division_id'];
    if (!isset($divisions[$divisionId])) {
        $divisions[$divisionId] = [
            'division_name' => $row['division_name'],
            'roster_title' => $row['roster_title'],
            'academic_year' => $row['academic_year'],
            'slots' => []
        ];
    }

    if ($row['slot_id'] === null) {
        continue;
    }

    $slotId = (int) $row['slot_id'];
    if (!isset($divisions[$divisionId]['slots'][$slotId])) {
        $divisions[$divisionId]['slots'][$slotId] = [
            'section_title' => $row['section_title'],
            'assignment_title' => $row['assignment_title'],
            'start_time' => substr($row['start_time'], 0, 5),
            'end_time' => substr($row['end_time'], 0, 5),
            'place_name' => $row['place_name'],
            'event_name' => $row['event_name'],
            'notes' => $row['slot_notes'],
            'teachers' => []
        ];
    }

    if ($row['teacher_id'] !== null) {
        $divisions[$divisionId]['slots'][$slotId]['teachers'][] = [
            'name' => $row['teacher_name'],
            'photo' => $row['teacher_photo'],
            'division' => $row['teacher_division_name']
        ];
    }
}

mysqli_stmt_close($statement);

foreach ($divisions as &$division) {
    $division['slots'] = array_values($division['slots']);
}
unset($division);

echo json_encode([
    'status' => 'success',
    'weekday' => $weekday,
    'weekday_name' => $weekdayNames[$weekday],
    'current_time' => substr($currentTime, 0, 5),
    'data' => array_values($divisions)
], JSON_UNESCAPED_UNICODE);
?>
