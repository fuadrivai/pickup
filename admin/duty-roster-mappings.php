<?php
include_once __DIR__ . '/../connect.php';

function dutyMappingEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dutyMappingRedirect($rosterId, $message)
{
    header('Location: duty-roster-mappings.php?roster_id=' . (int) $rosterId . '&message=' . rawurlencode($message));
    exit;
}

function dutyMappingGetRoster($connect, $rosterId)
{
    $statement = mysqli_prepare(
        $connect,
        'SELECT r.id, r.title, r.academic_year, r.notes, r.is_active
         FROM duty_rosters r WHERE r.id = ? LIMIT 1'
    );
    if (!$statement || !mysqli_stmt_bind_param($statement, 'i', $rosterId) || !mysqli_stmt_execute($statement)) {
        error_log('Gagal memeriksa jadwal duty roster: ' . ($statement ? mysqli_stmt_error($statement) : mysqli_error($connect)));
        if ($statement) {
            mysqli_stmt_close($statement);
        }
        return false;
    }
    $result = mysqli_stmt_get_result($statement);
    $roster = $result ? mysqli_fetch_assoc($result) : false;
    mysqli_stmt_close($statement);
    return $roster;
}

$rosterIdValue = isset($_GET['roster_id']) ? trim((string) $_GET['roster_id']) : '';
if (!ctype_digit($rosterIdValue) || (int) $rosterIdValue < 1) {
    http_response_code(400);
    exit('ID jadwal tidak valid.');
}
$rosterId = (int) $rosterIdValue;
$roster = dutyMappingGetRoster($connect, $rosterId);
if (!$roster) {
    http_response_code(404);
    exit('Jadwal duty roster tidak ditemukan.');
}

$errorMessage = '';
$successMessage = isset($_GET['message']) ? (string) $_GET['message'] : '';
$modalOpen = false;
$formId = '';
$formSection = '';
$formAssignmentTitle = '';
$formPlaceId = '';
$formEventId = '';
$formStartTime = '06:30';
$formEndTime = '07:00';
$formNotes = '';
$formSortOrder = '0';
$formTeachers = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $postedRosterId = isset($_POST['roster_id']) ? (string) $_POST['roster_id'] : '';
    $slotIdValue = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
    if (!ctype_digit($postedRosterId) || (int) $postedRosterId !== $rosterId || !in_array($action, ['save', 'delete'], true)) {
        $errorMessage = 'Permintaan tidak valid.';
    } elseif ($slotIdValue !== '' && !ctype_digit($slotIdValue)) {
        $errorMessage = 'ID mapping tidak valid.';
    } else {
        $slotId = $slotIdValue === '' ? 0 : (int) $slotIdValue;
        if ($action === 'delete') {
            if ($slotId < 1) {
                $errorMessage = 'Mapping yang akan dihapus tidak valid.';
            } else {
                $statement = mysqli_prepare($connect, 'DELETE FROM duty_roster_slots WHERE id = ? AND roster_id = ?');
                if ($statement && mysqli_stmt_bind_param($statement, 'ii', $slotId, $rosterId) && mysqli_stmt_execute($statement) && mysqli_stmt_affected_rows($statement) === 1) {
                    mysqli_stmt_close($statement);
                    dutyMappingRedirect($rosterId, 'Mapping jadwal berhasil dihapus.');
                }
                error_log('Gagal menghapus mapping duty roster: ' . ($statement ? mysqli_stmt_error($statement) : mysqli_error($connect)));
                if ($statement) {
                    mysqli_stmt_close($statement);
                }
                $errorMessage = 'Mapping gagal dihapus atau tidak ditemukan.';
            }
        } else {
            $modalOpen = true;
            $formId = $slotIdValue;
            $formSection = trim(isset($_POST['section_title']) ? (string) $_POST['section_title'] : '');
            $formAssignmentTitle = trim(isset($_POST['assignment_title']) ? (string) $_POST['assignment_title'] : '');
            $formPlaceId = isset($_POST['place_id']) ? trim((string) $_POST['place_id']) : '';
            $formEventId = isset($_POST['event_id']) ? trim((string) $_POST['event_id']) : '';
            $formStartTime = isset($_POST['start_time']) ? trim((string) $_POST['start_time']) : '';
            $formEndTime = isset($_POST['end_time']) ? trim((string) $_POST['end_time']) : '';
            $formNotes = trim(isset($_POST['notes']) ? (string) $_POST['notes'] : '');
            $formSortOrder = isset($_POST['sort_order']) ? trim((string) $_POST['sort_order']) : '0';
            $formTeachers = isset($_POST['teachers']) && is_array($_POST['teachers']) ? $_POST['teachers'] : [];
            $existingSlot = null;
            if ($slotId > 0) {
                $existingStatement = mysqli_prepare($connect, 'SELECT id FROM duty_roster_slots WHERE id = ? AND roster_id = ? LIMIT 1');
                if ($existingStatement && mysqli_stmt_bind_param($existingStatement, 'ii', $slotId, $rosterId) && mysqli_stmt_execute($existingStatement)) {
                    $existingResult = mysqli_stmt_get_result($existingStatement);
                    $existingSlot = $existingResult ? mysqli_fetch_assoc($existingResult) : false;
                } else {
                    $existingSlot = false;
                }
                if ($existingStatement) {
                    mysqli_stmt_close($existingStatement);
                }
            }

            $validTime = function ($time) {
                return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time) === 1;
            };
            $placeId = $formPlaceId === '' ? 0 : (ctype_digit($formPlaceId) ? (int) $formPlaceId : -1);
            $eventId = $formEventId === '' ? 0 : (ctype_digit($formEventId) ? (int) $formEventId : -1);
            $sortOrder = filter_var($formSortOrder, FILTER_VALIDATE_INT);
            if ($slotId > 0 && !$existingSlot) {
                $errorMessage = 'Mapping yang akan diubah tidak ditemukan pada jadwal ini.';
            } elseif ($formSection !== '' && strlen($formSection) > 150) {
                $errorMessage = 'Judul kelompok maksimal 150 karakter.';
            } elseif ($formAssignmentTitle !== '' && strlen($formAssignmentTitle) > 150) {
                $errorMessage = 'Nama tugas maksimal 150 karakter.';
            } elseif (!$validTime($formStartTime) || !$validTime($formEndTime) || $formStartTime >= $formEndTime) {
                $errorMessage = 'Jam mulai dan selesai tidak valid; jam selesai harus setelah jam mulai.';
            } elseif ($placeId < 0 || $eventId < 0 || $sortOrder === false) {
                $errorMessage = 'Lokasi, kegiatan, atau urutan tidak valid.';
            } elseif (strlen($formNotes) > 500) {
                $errorMessage = 'Catatan maksimal 500 karakter.';
            } else {
                if ($placeId > 0) {
                    $check = mysqli_prepare($connect, 'SELECT 1 FROM duty_places WHERE id = ? LIMIT 1');
                    if ($check) {
                        mysqli_stmt_bind_param($check, 'i', $placeId);
                        mysqli_stmt_execute($check);
                        mysqli_stmt_store_result($check);
                        $placeValid = mysqli_stmt_num_rows($check) === 1;
                        mysqli_stmt_close($check);
                    } else {
                        $placeValid = false;
                    }
                } else {
                    $placeValid = true;
                }
                if ($eventId > 0) {
                    $check = mysqli_prepare($connect, 'SELECT 1 FROM duty_events WHERE id = ? LIMIT 1');
                    if ($check) {
                        mysqli_stmt_bind_param($check, 'i', $eventId);
                        mysqli_stmt_execute($check);
                        mysqli_stmt_store_result($check);
                        $eventValid = mysqli_stmt_num_rows($check) === 1;
                        mysqli_stmt_close($check);
                    } else {
                        $eventValid = false;
                    }
                } else {
                    $eventValid = true;
                }
                if (!$placeValid || !$eventValid) {
                    $errorMessage = 'Place atau Event Run yang dipilih tidak ditemukan.';
                } else {
                    $normalizedTeachers = [];
                    $teacherIdsAreValid = true;
                    foreach ($formTeachers as $weekday => $teacherIds) {
                        if (!in_array((string) $weekday, ['1', '2', '3', '4', '5'], true) || !is_array($teacherIds)) {
                            $teacherIdsAreValid = false;
                            break;
                        }
                        $normalizedTeachers[(int) $weekday] = [];
                        foreach ($teacherIds as $teacherIdValue) {
                            $teacherIdValue = (string) $teacherIdValue;
                            if (!ctype_digit($teacherIdValue) || (int) $teacherIdValue < 1) {
                                $teacherIdsAreValid = false;
                                break 2;
                            }
                            $normalizedTeachers[(int) $weekday][] = (int) $teacherIdValue;
                        }
                        $normalizedTeachers[(int) $weekday] = array_values(array_unique($normalizedTeachers[(int) $weekday]));
                    }

                    if ($teacherIdsAreValid) {
                        $teacherCheck = mysqli_prepare($connect, 'SELECT 1 FROM duty_teachers WHERE id = ? LIMIT 1');
                        if (!$teacherCheck) {
                            $teacherIdsAreValid = false;
                        } else {
                            foreach ($normalizedTeachers as $teacherIds) {
                                foreach ($teacherIds as $teacherId) {
                                    mysqli_stmt_bind_param($teacherCheck, 'i', $teacherId);
                                    if (!mysqli_stmt_execute($teacherCheck) || !mysqli_stmt_store_result($teacherCheck) || mysqli_stmt_num_rows($teacherCheck) !== 1) {
                                        $teacherIdsAreValid = false;
                                        break 2;
                                    }
                                    mysqli_stmt_free_result($teacherCheck);
                                }
                            }
                            mysqli_stmt_close($teacherCheck);
                        }
                    }

                    if (!$teacherIdsAreValid) {
                        $errorMessage = 'Daftar guru tidak valid. Guru boleh berasal dari divisi mana pun.';
                    } else {
                        mysqli_begin_transaction($connect);
                        $placeValue = $placeId > 0 ? $placeId : null;
                        $eventValue = $eventId > 0 ? $eventId : null;
                        $sectionValue = $formSection === '' ? null : $formSection;
                        $assignmentValue = $formAssignmentTitle === '' ? null : $formAssignmentTitle;
                        $notesValue = $formNotes === '' ? null : $formNotes;
                        if ($slotId > 0) {
                            $saveStatement = mysqli_prepare(
                                $connect,
                                'UPDATE duty_roster_slots
                                 SET section_title = ?, assignment_title = ?, place_id = ?, event_id = ?,
                                     start_time = ?, end_time = ?, notes = ?, sort_order = ?
                                 WHERE id = ? AND roster_id = ?'
                            );
                            $saved = $saveStatement && mysqli_stmt_bind_param(
                                $saveStatement,
                                'ssiisssiii',
                                $sectionValue,
                                $assignmentValue,
                                $placeValue,
                                $eventValue,
                                $formStartTime,
                                $formEndTime,
                                $notesValue,
                                $sortOrder,
                                $slotId,
                                $rosterId
                            ) && mysqli_stmt_execute($saveStatement);
                        } else {
                            $saveStatement = mysqli_prepare(
                                $connect,
                                'INSERT INTO duty_roster_slots
                                 (roster_id, section_title, assignment_title, place_id, event_id, start_time, end_time, notes, sort_order)
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                            );
                            $saved = $saveStatement && mysqli_stmt_bind_param(
                                $saveStatement,
                                'issiisssi',
                                $rosterId,
                                $sectionValue,
                                $assignmentValue,
                                $placeValue,
                                $eventValue,
                                $formStartTime,
                                $formEndTime,
                                $notesValue,
                                $sortOrder
                            ) && mysqli_stmt_execute($saveStatement);
                            if ($saved) {
                                $slotId = mysqli_insert_id($connect);
                            }
                        }

                        $databaseError = '';
                        if (!$saved) {
                            $databaseError = $saveStatement ? mysqli_stmt_error($saveStatement) : mysqli_error($connect);
                        }
                        if ($saveStatement) {
                            mysqli_stmt_close($saveStatement);
                        }
                        if ($saved) {
                            $deleteAssignments = mysqli_prepare($connect, 'DELETE FROM duty_roster_assignments WHERE slot_id = ?');
                            $saved = $deleteAssignments && mysqli_stmt_bind_param($deleteAssignments, 'i', $slotId) && mysqli_stmt_execute($deleteAssignments);
                            if ($deleteAssignments) {
                                if (!$saved) {
                                    $databaseError = mysqli_stmt_error($deleteAssignments);
                                }
                                mysqli_stmt_close($deleteAssignments);
                            }
                        }
                        if ($saved) {
                            $assignmentStatement = mysqli_prepare(
                                $connect,
                                'INSERT INTO duty_roster_assignments (slot_id, weekday, teacher_id) VALUES (?, ?, ?)'
                            );
                            if (!$assignmentStatement) {
                                $saved = false;
                                $databaseError = mysqli_error($connect);
                            } else {
                                foreach ($normalizedTeachers as $weekday => $teacherIds) {
                                    foreach ($teacherIds as $teacherId) {
                                        mysqli_stmt_bind_param($assignmentStatement, 'iii', $slotId, $weekday, $teacherId);
                                        if (!mysqli_stmt_execute($assignmentStatement)) {
                                            $saved = false;
                                            $databaseError = mysqli_stmt_error($assignmentStatement);
                                            break 2;
                                        }
                                    }
                                }
                                mysqli_stmt_close($assignmentStatement);
                            }
                        }
                        if ($saved && mysqli_commit($connect)) {
                            dutyMappingRedirect($rosterId, 'Mapping jadwal berhasil disimpan.');
                        }

                        if ($databaseError === '') {
                            $databaseError = mysqli_error($connect);
                        }
                        mysqli_rollback($connect);
                        error_log('Gagal menyimpan mapping duty roster: ' . $databaseError);
                        $errorMessage = 'Mapping gagal disimpan. Periksa kembali master lokasi, Event Run, dan guru.';
                    }
                }
            }
        }
    }
}

$places = [];
$placeStatement = mysqli_prepare($connect, 'SELECT id, name FROM duty_places ORDER BY name');
if ($placeStatement && mysqli_stmt_execute($placeStatement)) {
    $placeResult = mysqli_stmt_get_result($placeStatement);
    while ($placeResult && $place = mysqli_fetch_assoc($placeResult)) {
        $places[] = $place;
    }
} else {
    error_log('Gagal mengambil lokasi duty roster: ' . ($placeStatement ? mysqli_stmt_error($placeStatement) : mysqli_error($connect)));
    $errorMessage = 'Gagal memuat master Place.';
}
if ($placeStatement) {
    mysqli_stmt_close($placeStatement);
}

$events = [];
$eventStatement = mysqli_prepare($connect, 'SELECT id, name FROM duty_events ORDER BY name');
if ($eventStatement && mysqli_stmt_execute($eventStatement)) {
    $eventResult = mysqli_stmt_get_result($eventStatement);
    while ($eventResult && $event = mysqli_fetch_assoc($eventResult)) {
        $events[] = $event;
    }
} else {
    error_log('Gagal mengambil Event Run duty roster: ' . ($eventStatement ? mysqli_stmt_error($eventStatement) : mysqli_error($connect)));
    $errorMessage = 'Gagal memuat master Event Run.';
}
if ($eventStatement) {
    mysqli_stmt_close($eventStatement);
}

$teachers = [];
$teacherResult = mysqli_query($connect, 'SELECT t.id, t.name, d.name AS division_name FROM duty_teachers t JOIN duty_divisions d ON d.id = t.division_id ORDER BY d.name, t.name');
if ($teacherResult) {
    while ($teacher = mysqli_fetch_assoc($teacherResult)) {
        $teachers[$teacher['division_name']][] = $teacher;
    }
} else {
    error_log('Gagal mengambil daftar guru duty roster: ' . mysqli_error($connect));
    $errorMessage = 'Gagal memuat master Guru.';
}

$slots = [];
$slotResult = mysqli_query(
    $connect,
    'SELECT s.id, s.section_title, s.assignment_title, s.place_id, s.event_id, s.start_time, s.end_time, s.notes, s.sort_order,
            p.name AS place_name, e.name AS event_name
     FROM duty_roster_slots s
     LEFT JOIN duty_places p ON p.id = s.place_id
     LEFT JOIN duty_events e ON e.id = s.event_id
     WHERE s.roster_id = ' . $rosterId . '
     ORDER BY p.name, s.sort_order, s.start_time, s.id'
);
if ($slotResult) {
    while ($slot = mysqli_fetch_assoc($slotResult)) {
        $slot['teachers_by_day'] = [];
        $assignmentResult = mysqli_query(
            $connect,
            'SELECT a.weekday, t.id, t.name
             FROM duty_roster_assignments a JOIN duty_teachers t ON t.id = a.teacher_id
             WHERE a.slot_id = ' . (int) $slot['id'] . ' ORDER BY t.name'
        );
        if ($assignmentResult) {
            while ($assignment = mysqli_fetch_assoc($assignmentResult)) {
                $weekday = (int) $assignment['weekday'];
                if (!isset($slot['teachers_by_day'][$weekday])) {
                    $slot['teachers_by_day'][$weekday] = [];
                }
                $slot['teachers_by_day'][$weekday][] = $assignment;
            }
        } else {
            error_log('Gagal mengambil penugasan guru: ' . mysqli_error($connect));
            $errorMessage = 'Gagal memuat mapping guru.';
        }
        $slots[] = $slot;
    }
} else {
    error_log('Gagal mengambil mapping jadwal: ' . mysqli_error($connect));
    $errorMessage = 'Gagal memuat daftar mapping.';
}

$weekdays = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];
$formTeachers = is_array($formTeachers) ? $formTeachers : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Rincian Duty Roster</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .mapping-days { min-width: 280px; }
        .teacher-picker .select2-container { width: 100% !important; }
        .teacher-picker .select2-selection--multiple { min-height: 42px; }
        .teacher-picker .select2-selection__rendered { display: block; }
        .teacher-picker .select2-selection__choice { margin-top: 4px; }
        .teacher-picker .select2-results__group { color: #4e73df; }
        .teacher-picker .select2-dropdown { z-index: 1060; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php'; ?>
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div>
                            <h1 class="h3 mb-2 text-gray-800">Rincian Duty Roster</h1>
                            <p class="mb-4">
                                <?php echo dutyMappingEscape($roster['title']); ?>
                                <?php if ($roster['academic_year']) { ?>&middot; <?php echo dutyMappingEscape($roster['academic_year']); ?><?php } ?>
                            </p>
                        </div>
                        <a href="duty-rosters.php" class="btn btn-secondary btn-sm mb-3"><i class="fas fa-arrow-left"></i> Daftar Jadwal</a>
                    </div>
                    <?php if ($roster['notes']) { ?><div class="alert alert-info"><?php echo nl2br(dutyMappingEscape($roster['notes'])); ?></div><?php } ?>
                    <?php if ($successMessage !== '') { ?><div class="alert alert-success"><?php echo dutyMappingEscape($successMessage); ?></div><?php } ?>
                    <?php if ($errorMessage !== '') { ?><div class="alert alert-danger"><?php echo dutyMappingEscape($errorMessage); ?></div><?php } ?>

                    <div class="alert alert-info">
                        Tambahkan satu baris untuk setiap area/lokasi dan rentang waktu. Place dan Event Run bersifat global; guru dari berbagai divisi dapat ditugaskan bersama.
                    </div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar Tugas dan Guru Piket</h6>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#mappingModal" data-mode="add">
                                <i class="fas fa-plus"></i> Tambah Baris Tugas
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-light border">
                                Setiap baris adalah satu area/tugas dan rentang waktu. Pilih guru per hari; satu hari dapat memiliki beberapa guru dari berbagai divisi.
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr><th>Kelompok / Tugas</th><th>Waktu</th><th>Event Run</th><th>Guru per Hari</th><th>Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php $currentPlaceName = null; foreach ($slots as $slot) { ?>
                                            <?php
                                            $placeName = $slot['place_name'] ?: 'Area belum ditentukan';
                                            if ($placeName !== $currentPlaceName) {
                                                $currentPlaceName = $placeName;
                                            ?>
                                                <tr class="table-secondary">
                                                    <th colspan="5" class="text-primary"><i class="fas fa-map-marker-alt mr-2"></i><?php echo dutyMappingEscape($placeName); ?></th>
                                                </tr>
                                            <?php } ?>
                                            <tr>
                                                <td>
                                                    <?php if ($slot['section_title']) { ?><strong><?php echo dutyMappingEscape($slot['section_title']); ?></strong><br><?php } ?>
                                                    <?php echo dutyMappingEscape($slot['assignment_title'] ?: '-'); ?>
                                                    <?php if ($slot['notes']) { ?><div class="small text-muted"><?php echo dutyMappingEscape($slot['notes']); ?></div><?php } ?>
                                                </td>
                                                <td class="text-nowrap"><?php echo dutyMappingEscape(substr($slot['start_time'], 0, 5)); ?> - <?php echo dutyMappingEscape(substr($slot['end_time'], 0, 5)); ?></td>
                                                <td><?php echo dutyMappingEscape($slot['event_name'] ?: '-'); ?></td>
                                                <td class="mapping-days">
                                                    <?php foreach ($weekdays as $weekday => $dayName) { ?>
                                                        <div><strong><?php echo dutyMappingEscape($dayName); ?>:</strong>
                                                            <?php
                                                            $dayTeachers = isset($slot['teachers_by_day'][$weekday]) ? $slot['teachers_by_day'][$weekday] : [];
                                                            echo count($dayTeachers) ? dutyMappingEscape(implode(', ', array_column($dayTeachers, 'name'))) : '<span class="text-muted">-</span>';
                                                            ?>
                                                        </div>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-nowrap">
                                                    <?php
                                                    $editAssignments = [];
                                                    foreach ($slot['teachers_by_day'] as $weekday => $assignedTeachers) {
                                                        $editAssignments[$weekday] = array_map(function ($teacher) {
                                                            return (string) $teacher['id'];
                                                        }, $assignedTeachers);
                                                    }
                                                    ?>
                                                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#mappingModal" data-mode="edit"
                                                        data-id="<?php echo dutyMappingEscape($slot['id']); ?>"
                                                        data-section="<?php echo dutyMappingEscape($slot['section_title']); ?>"
                                                        data-assignment="<?php echo dutyMappingEscape($slot['assignment_title']); ?>"
                                                        data-place-id="<?php echo dutyMappingEscape($slot['place_id']); ?>"
                                                        data-event-id="<?php echo dutyMappingEscape($slot['event_id']); ?>"
                                                        data-start="<?php echo dutyMappingEscape(substr($slot['start_time'], 0, 5)); ?>"
                                                        data-end="<?php echo dutyMappingEscape(substr($slot['end_time'], 0, 5)); ?>"
                                                        data-notes="<?php echo dutyMappingEscape($slot['notes']); ?>"
                                                        data-sort="<?php echo dutyMappingEscape($slot['sort_order']); ?>"
                                                        data-teachers="<?php echo dutyMappingEscape(json_encode($editAssignments)); ?>"
                                                        aria-label="Edit mapping">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus mapping ini?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="roster_id" value="<?php echo dutyMappingEscape($rosterId); ?>">
                                                        <input type="hidden" name="id" value="<?php echo dutyMappingEscape($slot['id']); ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" aria-label="Hapus mapping"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (count($slots) === 0) { ?><tr><td colspan="5" class="text-center text-muted">Belum ada mapping untuk jadwal ini.</td></tr><?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>Copyright &copy; Your Website 2026</span></div></div></footer>
        </div>
    </div>

    <div class="modal fade" id="mappingModal" tabindex="-1" role="dialog" aria-labelledby="mappingModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document"><div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="mappingModalTitle">Tambah Baris Tugas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="roster_id" value="<?php echo dutyMappingEscape($rosterId); ?>">
                    <input type="hidden" name="id" id="mappingId" value="<?php echo dutyMappingEscape($formId); ?>">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="sectionTitle">Judul kelompok (opsional)</label>
                            <input type="text" class="form-control" name="section_title" id="sectionTitle" maxlength="150" value="<?php echo dutyMappingEscape($formSection); ?>" placeholder="Contoh: Gate Greeting Officers">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="assignmentTitle">Nama tugas/baris (opsional)</label>
                            <input type="text" class="form-control" name="assignment_title" id="assignmentTitle" maxlength="150" value="<?php echo dutyMappingEscape($formAssignmentTitle); ?>" placeholder="Contoh: Main Gate">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-3">
                            <label for="mappingPlace">Place</label>
                            <select class="form-control" name="place_id" id="mappingPlace">
                                <option value="">Pilih Place (opsional)</option>
                                <?php foreach ($places as $place) { ?>
                                    <option value="<?php echo dutyMappingEscape($place['id']); ?>" <?php if ($formPlaceId === (string) $place['id']) echo 'selected'; ?>><?php echo dutyMappingEscape($place['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="mappingEvent">Event Run</label>
                            <select class="form-control" name="event_id" id="mappingEvent">
                                <option value="">Pilih Event Run (opsional)</option>
                                <?php foreach ($events as $event) { ?>
                                    <option value="<?php echo dutyMappingEscape($event['id']); ?>" <?php if ($formEventId === (string) $event['id']) echo 'selected'; ?>><?php echo dutyMappingEscape($event['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="mappingStart">Jam mulai</label>
                            <input type="time" class="form-control" name="start_time" id="mappingStart" value="<?php echo dutyMappingEscape($formStartTime); ?>" required>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="mappingEnd">Jam selesai</label>
                            <input type="time" class="form-control" name="end_time" id="mappingEnd" value="<?php echo dutyMappingEscape($formEndTime); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mappingNotes">Catatan (opsional)</label>
                        <input type="text" class="form-control" name="notes" id="mappingNotes" maxlength="500" value="<?php echo dutyMappingEscape($formNotes); ?>">
                    </div>
                    <div class="form-group">
                        <label for="mappingSort">Urutan tampilan</label>
                        <input type="number" class="form-control" name="sort_order" id="mappingSort" value="<?php echo dutyMappingEscape($formSortOrder); ?>">
                    </div>
                    <hr>
                    <h6 class="font-weight-bold">Guru yang bertugas per hari</h6>
                    <p class="small text-muted">Ketik nama guru atau divisi untuk mencari. Klik beberapa nama untuk menugaskan lebih dari satu guru di hari yang sama.</p>
                    <div class="form-row">
                        <?php foreach ($weekdays as $weekday => $dayName) { ?>
                            <div class="form-group col teacher-picker">
                                <label for="teachers-<?php echo $weekday; ?>"><?php echo dutyMappingEscape($dayName); ?></label>
                                <select class="form-control day-select" id="teachers-<?php echo $weekday; ?>" name="teachers[<?php echo $weekday; ?>][]" multiple data-placeholder="Cari guru...">
                                    <?php foreach ($teachers as $divisionName => $divisionTeachers) { ?>
                                        <optgroup label="<?php echo dutyMappingEscape($divisionName); ?>">
                                            <?php foreach ($divisionTeachers as $teacher) { ?>
                                                <option value="<?php echo dutyMappingEscape($teacher['id']); ?>" <?php if (isset($formTeachers[$weekday]) && is_array($formTeachers[$weekday]) && in_array((string) $teacher['id'], array_map('strval', $formTeachers[$weekday]), true)) echo 'selected'; ?>>
                                                    <?php echo dutyMappingEscape($teacher['name']); ?>
                                                </option>
                                            <?php } ?>
                                        </optgroup>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Mapping</button>
                </div>
            </form>
        </div></div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $('#mappingModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var isEdit = button.data('mode') === 'edit';
            var modal = $(this);
            modal.find('.modal-title').text(isEdit ? 'Edit Baris Tugas' : 'Tambah Baris Tugas');
            modal.find('#mappingId').val(isEdit ? button.attr('data-id') : '');
            modal.find('#sectionTitle').val(isEdit ? button.attr('data-section') : '');
            modal.find('#assignmentTitle').val(isEdit ? button.attr('data-assignment') : '');
            modal.find('#mappingPlace').val(isEdit ? button.attr('data-place-id') : '');
            modal.find('#mappingEvent').val(isEdit ? button.attr('data-event-id') : '');
            modal.find('#mappingStart').val(isEdit ? button.attr('data-start') : '06:30');
            modal.find('#mappingEnd').val(isEdit ? button.attr('data-end') : '07:00');
            modal.find('#mappingNotes').val(isEdit ? button.attr('data-notes') : '');
            modal.find('#mappingSort').val(isEdit ? button.attr('data-sort') : '0');
            var assigned = {};
            if (isEdit) {
                try {
                    assigned = JSON.parse(button.attr('data-teachers') || '{}');
                } catch (error) {
                    console.error('Gagal membaca mapping guru:', error);
                }
            }
            <?php foreach ($weekdays as $weekday => $dayName) { ?>
            modal.find('#teachers-<?php echo $weekday; ?>').val(assigned['<?php echo $weekday; ?>'] || []).trigger('change');
            <?php } ?>
        });
        $('#mappingModal').on('shown.bs.modal', function() {
            $(this).find('.day-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        width: '100%',
                        placeholder: $(this).data('placeholder'),
                        allowClear: true,
                        closeOnSelect: false,
                        dropdownParent: $('#mappingModal')
                    });
                }
            });
        });
    </script>
    <?php if ($modalOpen) { ?><script>$('#mappingModal').modal('show');</script><?php } ?>
</body>
</html>
