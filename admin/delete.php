<?php
include("../connect.php");
// menyimpan data kedalam variabel

$type = $_GET['type'];
// query SQL untuk insert data
if($type =='parents_card'){
    $studentId = isset($_GET['student_id']) ? trim((string) $_GET['student_id']) : '';
    $rfidid = isset($_GET['rfidid']) ? trim((string) $_GET['rfidid']) : '';
    $rfidid_parents = isset($_GET['rfidid_parents']) ? trim((string) $_GET['rfidid_parents']) : '';
    $returnUrl = $studentId !== ''
        ? 'edit-student.php?id=' . rawurlencode($studentId)
        : 'parents_card.php?rfidid=' . rawurlencode($rfidid);

    if ($rfidid_parents === '' || ($studentId === '' && $rfidid === '')) {
        header('Location: student-list.php');
        exit;
    }

    if ($studentId !== '') {
        if (!ctype_digit($studentId)) {
            header('Location: student-list.php');
            exit;
        }

        $studentStatement = mysqli_prepare($connect, "SELECT rfidid FROM student WHERE id = ? LIMIT 1");
        if (!$studentStatement) {
            error_log('Gagal menyiapkan pencarian siswa untuk penghapusan kartu: ' . mysqli_error($connect));
            header('Location: ' . $returnUrl . '&status=error');
            exit;
        }

        if (
            !mysqli_stmt_bind_param($studentStatement, 's', $studentId) ||
            !mysqli_stmt_execute($studentStatement) ||
            !mysqli_stmt_bind_result($studentStatement, $rfidid)
        ) {
            error_log('Gagal mencari siswa untuk penghapusan kartu: ' . mysqli_stmt_error($studentStatement));
            mysqli_stmt_close($studentStatement);
            header('Location: ' . $returnUrl . '&status=error');
            exit;
        }

        if (mysqli_stmt_fetch($studentStatement) !== true) {
            mysqli_stmt_close($studentStatement);
            header('Location: edit-student.php?id=' . rawurlencode($studentId) . '&status=student_not_found');
            exit;
        }
        mysqli_stmt_close($studentStatement);
    }

    $statement = mysqli_prepare(
        $connect,
        "DELETE FROM parents_card WHERE rfidid = ? AND rfidid_parents = ?"
    );
    if (!$statement) {
        error_log('Gagal menyiapkan penghapusan kartu orang tua: ' . mysqli_error($connect));
        header('Location: ' . $returnUrl . '&status=error');
        exit;
    }

    if (
        !mysqli_stmt_bind_param($statement, 'ss', $rfidid, $rfidid_parents) ||
        !mysqli_stmt_execute($statement)
    ) {
        error_log('Gagal menghapus kartu orang tua: ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        header('Location: ' . $returnUrl . '&status=error');
        exit;
    }

    $deleted = mysqli_stmt_affected_rows($statement) > 0;
    mysqli_stmt_close($statement);
    $status = $deleted ? 'deleted' : 'not_found';
    header('Location: ' . $returnUrl . '&status=' . $status);
    exit;
}
?>