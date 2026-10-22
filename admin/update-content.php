<?php
include("../connect.php");

$id = trim((string) ($_POST['id'] ?? ''));
$title = trim((string) ($_POST['title'] ?? ''));
$display_type = (string) ($_POST['display_type'] ?? '');
$content_link = (string) ($_POST['content_link'] ?? '');
$content_html = (string) ($_POST['content_html'] ?? '');
$sequence = (int)($_POST['sequence'] ?? 0);
$duration_seconds = (int)($_POST['duration_seconds'] ?? 10);
$is_active = (int)($_POST['is_active'] ?? 1);
$allowedDisplayTypes = ['video', 'link', 'slide', 'schedule', 'duty_roster', 'api_events', 'embed_code'];

if (
    $title === '' ||
    !in_array($display_type, $allowedDisplayTypes, true) ||
    ($id !== '' && !ctype_digit($id)) ||
    $sequence < 0 ||
    $duration_seconds < 1 ||
    !in_array($is_active, [0, 1], true)
) {
    http_response_code(400);
    exit('Data display content tidak valid.');
}

$contentImage = '';

if ($display_type == 'slide' && isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
    $target_dir = "../admin/img/";
    $file_name = bin2hex(random_bytes(16)) . "_" . basename($_FILES["slide_image"]["name"]);
    $target_file = $target_dir . $file_name;
    if (move_uploaded_file($_FILES["slide_image"]["tmp_name"], $target_file)) {
        $contentImage = "admin/img/$file_name";
    }
}

try {
    if ($id !== '') {
        if ($contentImage !== '') {
            $query = 'UPDATE display_contents
                      SET title = ?, display_type = ?, content_link = ?, content_html = ?, content_image = ?,
                          sequence = ?, duration_seconds = ?, is_active = ?
                      WHERE id = ?';
            $statement = mysqli_prepare($connect, $query);
            mysqli_stmt_bind_param(
                $statement,
                'sssssiiii',
                $title,
                $display_type,
                $content_link,
                $content_html,
                $contentImage,
                $sequence,
                $duration_seconds,
                $is_active,
                $id
            );
        } else {
            $query = 'UPDATE display_contents
                      SET title = ?, display_type = ?, content_link = ?, content_html = ?,
                          sequence = ?, duration_seconds = ?, is_active = ?
                      WHERE id = ?';
            $statement = mysqli_prepare($connect, $query);
            mysqli_stmt_bind_param(
                $statement,
                'ssssiiii',
                $title,
                $display_type,
                $content_link,
                $content_html,
                $sequence,
                $duration_seconds,
                $is_active,
                $id
            );
        }
    } else {
        $query = 'INSERT INTO display_contents
                  (title, display_type, content_link, content_html, content_image, sequence, duration_seconds, is_active)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $statement = mysqli_prepare($connect, $query);
        mysqli_stmt_bind_param(
            $statement,
            'sssssiii',
            $title,
            $display_type,
            $content_link,
            $content_html,
            $contentImage,
            $sequence,
            $duration_seconds,
            $is_active
        );
    }

    mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
} catch (mysqli_sql_exception $exception) {
    error_log('Gagal menyimpan display content: ' . $exception->getMessage());
    http_response_code(500);
    exit('Gagal menyimpan display content. Silakan periksa konfigurasi database atau hubungi administrator.');
}

header("location: display-contents.php");
?>
