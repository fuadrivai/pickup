<?php
include("../connect.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}

$rfidid = isset($_POST['rfidid']) ? trim((string) $_POST['rfidid']) : '';
$studentName = isset($_POST['student_name']) ? trim((string) $_POST['student_name']) : '';
$grade = isset($_POST['grade']) ? trim((string) $_POST['grade']) : '';
$submittedParentCards = $_POST['rfidid_parents'] ?? [];
if (!is_array($submittedParentCards)) {
    $submittedParentCards = [$submittedParentCards];
}
$parentsCardIds = [];
foreach ($submittedParentCards as $parentsCardId) {
    if (is_string($parentsCardId) || is_numeric($parentsCardId)) {
        $parentsCardId = trim((string) $parentsCardId);
        if ($parentsCardId !== '') {
            $parentsCardIds[] = $parentsCardId;
        }
    }
}

if ($rfidid === '' || $studentName === '' || $grade === '') {
    header('Location: add-student.php?status=invalid');
    exit;
}

$gradeStatement = mysqli_prepare(
    $connect,
    "SELECT 1 FROM homeroom WHERE grade = ? LIMIT 1"
);
if (!$gradeStatement) {
    error_log('Gagal menyiapkan validasi grade siswa: ' . mysqli_error($connect));
    header('Location: add-student.php?status=error');
    exit;
}

if (
    !mysqli_stmt_bind_param($gradeStatement, 's', $grade) ||
    !mysqli_stmt_execute($gradeStatement) ||
    !mysqli_stmt_store_result($gradeStatement)
) {
    error_log('Gagal memvalidasi grade siswa: ' . mysqli_stmt_error($gradeStatement));
    mysqli_stmt_close($gradeStatement);
    header('Location: add-student.php?status=error');
    exit;
}

$gradeExists = mysqli_stmt_num_rows($gradeStatement) > 0;
mysqli_stmt_close($gradeStatement);
if (!$gradeExists) {
    header('Location: add-student.php?status=invalid');
    exit;
}

$studentStatement = mysqli_prepare(
    $connect,
    "INSERT INTO student (rfidid, student_name, grade) VALUES (?, ?, ?)"
);
if (!$studentStatement) {
    error_log('Gagal menyiapkan penambahan siswa: ' . mysqli_error($connect));
    header('Location: add-student.php?status=error');
    exit;
}

if (
    !mysqli_stmt_bind_param($studentStatement, 'sss', $rfidid, $studentName, $grade) ||
    !mysqli_stmt_execute($studentStatement)
) {
    error_log('Gagal menambahkan siswa: ' . mysqli_stmt_error($studentStatement));
    mysqli_stmt_close($studentStatement);
    header('Location: add-student.php?status=error');
    exit;
}

$studentId = mysqli_insert_id($connect);
mysqli_stmt_close($studentStatement);

if (count($parentsCardIds) > 0) {
    $registeredDate = date('Y-m-d');
    $cardStatement = mysqli_prepare(
        $connect,
        "INSERT INTO parents_card (rfidid, rfidid_parents, registered_date) VALUES (?, ?, ?)"
    );
    if (!$cardStatement) {
        error_log('Gagal menyiapkan penambahan kartu orang tua untuk siswa baru: ' . mysqli_error($connect));
        header('Location: edit-student.php?id=' . rawurlencode((string) $studentId) . '&status=card_error');
        exit;
    }

    foreach ($parentsCardIds as $parentsCardId) {
        if (
            !mysqli_stmt_bind_param($cardStatement, 'sss', $rfidid, $parentsCardId, $registeredDate) ||
            !mysqli_stmt_execute($cardStatement)
        ) {
            error_log('Gagal menambahkan kartu orang tua untuk siswa baru: ' . mysqli_stmt_error($cardStatement));
            mysqli_stmt_close($cardStatement);
            header('Location: edit-student.php?id=' . rawurlencode((string) $studentId) . '&status=card_error');
            exit;
        }
    }
    mysqli_stmt_close($cardStatement);
}

header('Location: edit-student.php?id=' . rawurlencode((string) $studentId) . '&status=created');
exit;
