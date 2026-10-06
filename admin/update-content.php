<?php
include("../connect.php");

$id = $_POST['id'] ?? '';
$title = mysqli_real_escape_string($connect, $_POST['title'] ?? '');
$display_type = mysqli_real_escape_string($connect, $_POST['display_type'] ?? '');
$content_link = mysqli_real_escape_string($connect, $_POST['content_link'] ?? '');
$content_html = mysqli_real_escape_string($connect, $_POST['content_html'] ?? '');
$sequence = (int)($_POST['sequence'] ?? 0);
$duration_seconds = (int)($_POST['duration_seconds'] ?? 10);
$is_active = (int)($_POST['is_active'] ?? 1);

$content_image_query = "";

if ($display_type == 'slide' && isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
    $target_dir = "../admin/img/";
    $file_name = time() . "_" . basename($_FILES["slide_image"]["name"]);
    $target_file = $target_dir . $file_name;
    if (move_uploaded_file($_FILES["slide_image"]["tmp_name"], $target_file)) {
        $content_image_query = "admin/img/$file_name";
    }
}

if ($id) {
    // Update existing
    $query = "UPDATE display_contents SET 
                title='$title', 
                display_type='$display_type', 
                content_link='$content_link', 
                content_html='$content_html',
                sequence=$sequence, 
                duration_seconds=$duration_seconds, 
                is_active=$is_active";
                
    if ($content_image_query) {
        $query .= ", content_image='$content_image_query'";
    }
    $query .= " WHERE id=$id";
} else {
    // Insert new
    $query = "INSERT INTO display_contents (title, display_type, content_link, content_html, content_image, sequence, duration_seconds, is_active) 
              VALUES ('$title', '$display_type', '$content_link', '$content_html', '$content_image_query', $sequence, $duration_seconds, $is_active)";
}

if (!mysqli_query($connect, $query)) {
    die("Database Error: " . mysqli_error($connect) . "<br>Query: " . $query);
}

header("location: display-contents.php");
?>
