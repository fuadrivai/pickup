<?php

function triggerPickupForStudent($connect, $rfidid)
{
    $updateStatement = mysqli_prepare(
        $connect,
        "UPDATE student SET tiime = NOW(), status = '', play = 'not yet' WHERE rfidid = ?"
    );
    if (!$updateStatement) {
        error_log('Gagal menyiapkan update pickup siswa: ' . mysqli_error($connect));
        return [
            'display_updated' => false,
            'whatsapp_sent' => false,
            'error' => 'Gagal memperbarui status pickup siswa.'
        ];
    }

    if (
        !mysqli_stmt_bind_param($updateStatement, 's', $rfidid) ||
        !mysqli_stmt_execute($updateStatement)
    ) {
        error_log('Gagal memperbarui status pickup siswa: ' . mysqli_stmt_error($updateStatement));
        mysqli_stmt_close($updateStatement);
        return [
            'display_updated' => false,
            'whatsapp_sent' => false,
            'error' => 'Gagal memperbarui status pickup siswa.'
        ];
    }
    mysqli_stmt_close($updateStatement);

    $studentStatement = mysqli_prepare(
        $connect,
        "SELECT s.student_name, h.phone
         FROM student s
         LEFT JOIN homeroom h ON s.grade = h.grade
         WHERE s.rfidid = ?
         LIMIT 1"
    );
    if (!$studentStatement) {
        error_log('Gagal menyiapkan query informasi homeroom pickup: ' . mysqli_error($connect));
        return [
            'display_updated' => true,
            'whatsapp_sent' => false,
            'error' => 'Pickup tercatat di layar, tetapi data homeroom gagal diambil.'
        ];
    }

    if (
        !mysqli_stmt_bind_param($studentStatement, 's', $rfidid) ||
        !mysqli_stmt_execute($studentStatement) ||
        !mysqli_stmt_bind_result($studentStatement, $studentName, $homeroomPhone)
    ) {
        error_log('Gagal mengambil informasi homeroom pickup: ' . mysqli_stmt_error($studentStatement));
        mysqli_stmt_close($studentStatement);
        return [
            'display_updated' => true,
            'whatsapp_sent' => false,
            'error' => 'Pickup tercatat di layar, tetapi data homeroom gagal diambil.'
        ];
    }

    $studentFound = mysqli_stmt_fetch($studentStatement);
    mysqli_stmt_close($studentStatement);
    if ($studentFound !== true) {
        return [
            'display_updated' => true,
            'whatsapp_sent' => false,
            'error' => 'Pickup tercatat di layar, tetapi siswa tidak ditemukan.'
        ];
    }

    if ($homeroomPhone === null || trim((string) $homeroomPhone) === '') {
        return [
            'display_updated' => true,
            'whatsapp_sent' => false,
            'whatsapp_skipped' => true,
            'error' => 'Pickup tercatat di layar, tetapi nomor telepon homeroom belum tersedia.'
        ];
    }

    $messageData = [
        'api_key' => '7bd77f56d1e7fc38a07739594d0b4b7c0f0e594c',
        'sender' => '325293',
        'number' => $homeroomPhone,
        'message' => 'Pickup for Ananda ' . $studentName . ' Please go to the Front Gate'
    ];
    $curl = curl_init('https://mhisnetshield.us/apiv2/send-message.php');
    if ($curl === false) {
        error_log('Gagal menginisialisasi request WhatsApp pickup.');
        return [
            'display_updated' => true,
            'whatsapp_sent' => false,
            'error' => 'Pickup tercatat di layar, tetapi layanan WhatsApp gagal diinisialisasi.'
        ];
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($messageData)
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false || $httpStatus < 200 || $httpStatus >= 300) {
        error_log('Gagal mengirim notifikasi WhatsApp pickup: ' . ($curlError ?: 'HTTP ' . $httpStatus));
        return [
            'display_updated' => true,
            'whatsapp_sent' => false,
            'error' => 'Pickup tercatat di layar, tetapi notifikasi WhatsApp gagal dikirim.'
        ];
    }

    return [
        'display_updated' => true,
        'whatsapp_sent' => true
    ];
}

if (
    isset($_SERVER['SCRIPT_FILENAME']) &&
    realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__
) {
    include(__DIR__ . '/connect.php');
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['nama'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data kartu pickup tidak tersedia.']);
        exit;
    }

    $parentCardRfid = trim((string) $_POST['nama']);
    if ($parentCardRfid === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Data kartu pickup tidak valid.']);
        exit;
    }

    $cardStatement = mysqli_prepare(
        $connect,
        "SELECT rfidid FROM parents_card WHERE rfidid_parents = ? LIMIT 1"
    );
    if (!$cardStatement) {
        error_log('Gagal menyiapkan query kartu pickup: ' . mysqli_error($connect));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memproses kartu pickup.']);
        exit;
    }

    if (
        !mysqli_stmt_bind_param($cardStatement, 's', $parentCardRfid) ||
        !mysqli_stmt_execute($cardStatement) ||
        !mysqli_stmt_bind_result($cardStatement, $studentRfid)
    ) {
        error_log('Gagal mengambil siswa dari kartu pickup: ' . mysqli_stmt_error($cardStatement));
        mysqli_stmt_close($cardStatement);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memproses kartu pickup.']);
        exit;
    }

    $cardFound = mysqli_stmt_fetch($cardStatement);
    mysqli_stmt_close($cardStatement);
    if ($cardFound !== true) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kartu orang tua tidak terdaftar.']);
        exit;
    }

    $pickupResult = triggerPickupForStudent($connect, $studentRfid);
    if (!$pickupResult['display_updated']) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $pickupResult['error']]);
        exit;
    }

    echo json_encode(['success' => true, 'pickup' => $pickupResult]);
}
?>
