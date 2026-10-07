<?php
include("../connect.php");
// menyimpan data kedalam variabel

$type = $_GET['type'];
// query SQL untuk insert data
if($type =='parents_card'){
    $rfidid = isset($_GET['rfidid']) ? trim((string) $_GET['rfidid']) : '';
    $rfidid_parents = isset($_GET['rfidid_parents']) ? trim((string) $_GET['rfidid_parents']) : '';
    if ($rfidid === '' || $rfidid_parents === '') {
        header('Location: student-list.php');
        exit;
    }

    $statement = mysqli_prepare(
        $connect,
        "DELETE FROM parents_card WHERE rfidid = ? AND rfidid_parents = ?"
    );
    if (!$statement) {
        error_log('Gagal menyiapkan penghapusan kartu orang tua: ' . mysqli_error($connect));
        header('Location: parents_card.php?rfidid=' . rawurlencode($rfidid) . '&status=error');
        exit;
    }

    if (
        !mysqli_stmt_bind_param($statement, 'ss', $rfidid, $rfidid_parents) ||
        !mysqli_stmt_execute($statement)
    ) {
        error_log('Gagal menghapus kartu orang tua: ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        header('Location: parents_card.php?rfidid=' . rawurlencode($rfidid) . '&status=error');
        exit;
    }

    $deleted = mysqli_stmt_affected_rows($statement) > 0;
    mysqli_stmt_close($statement);
    $status = $deleted ? 'deleted' : 'not_found';
    header('Location: parents_card.php?rfidid=' . rawurlencode($rfidid) . '&status=' . $status);
    exit;
}
?>