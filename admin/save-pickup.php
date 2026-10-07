<?php
include("../connect.php");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$studentId = isset($_POST['student_id']) ? trim((string) $_POST['student_id']) : '';
$parentName = isset($_POST['parent_name']) ? trim((string) $_POST['parent_name']) : '';
$parentPhone = isset($_POST['parent_phone']) ? trim((string) $_POST['parent_phone']) : null;
if ($parentPhone === '') {
    $parentPhone = null;
}

if ($studentId === '' || !ctype_digit($studentId) || $parentName === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Lengkapi nama orang tua.']);
    exit;
}

if (strlen($parentName) > 255 || ($parentPhone !== null && strlen($parentPhone) > 50)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nama atau nomor telepon terlalu panjang.']);
    exit;
}

$studentStatement = mysqli_prepare(
    $connect,
    "SELECT student_name, grade, rfidid FROM student WHERE id = ? LIMIT 1"
);
if (!$studentStatement) {
    error_log('Gagal menyiapkan query siswa untuk pickup: ' . mysqli_error($connect));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data siswa.']);
    exit;
}

if (
    !mysqli_stmt_bind_param($studentStatement, 's', $studentId) ||
    !mysqli_stmt_execute($studentStatement) ||
    !mysqli_stmt_bind_result($studentStatement, $studentName, $studentGrade, $studentRfid)
) {
    error_log('Gagal mengambil data siswa untuk pickup: ' . mysqli_stmt_error($studentStatement));
    mysqli_stmt_close($studentStatement);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal mengambil data siswa.']);
    exit;
}

$studentFound = mysqli_stmt_fetch($studentStatement);
mysqli_stmt_close($studentStatement);

if ($studentFound !== true) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Siswa tidak ditemukan.']);
    exit;
}

$insertStatement = mysqli_prepare(
    $connect,
    "INSERT INTO pickup_requests (student_id, student_name, student_grade, parent_name, parent_phone)
     VALUES (?, ?, ?, ?, ?)"
);
if (!$insertStatement) {
    error_log('Gagal menyiapkan penyimpanan data pickup: ' . mysqli_error($connect));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pickup.']);
    exit;
}

if (
    !mysqli_stmt_bind_param(
        $insertStatement,
        'sssss',
        $studentId,
        $studentName,
        $studentGrade,
        $parentName,
        $parentPhone
    ) ||
    !mysqli_stmt_execute($insertStatement)
) {
    error_log('Gagal menyimpan data pickup: ' . mysqli_stmt_error($insertStatement));
    mysqli_stmt_close($insertStatement);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan data pickup.']);
    exit;
}

mysqli_stmt_close($insertStatement);
require_once __DIR__ . '/../simpan.php';
$pickupResult = triggerPickupForStudent($connect, $studentRfid);

echo json_encode([
    'success' => true,
    'pickup' => $pickupResult
]);
?>
