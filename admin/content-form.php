<!DOCTYPE html>
<html lang="en">
<?php
    include("../connect.php");
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $d = [
        'id' => '', 'title' => '', 'display_type' => 'video', 
        'content_link' => '', 'content_image' => '', 'sequence' => '0', 
        'duration_seconds' => '10', 'is_active' => 1
    ];
    if ($id) {
        $sql = mysqli_query($connect, "SELECT * FROM display_contents WHERE id = '$id'");
        if($row = mysqli_fetch_array($sql)) {
            $d = $row;
        }
    }
?>
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Display Content</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <div id="wrapper">
        <?php include "sidebar.php"; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php' ?>

                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800"><?php echo $id ? 'Edit' : 'Add'; ?> Display Content</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="post" action="update-content.php" enctype="multipart/form-data">
                                <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="title" class="form-label">Title (Internal Name)</label>
                                        <input type="text" class="form-control" name="title" id="title" value="<?php echo $d['title']; ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="display_type" class="form-label">Display Type</label>
                                        <select class="form-control" name="display_type" id="display_type" onchange="toggleFields()">
                                            <option value="video" <?php if($d['display_type'] == 'video') echo 'selected'; ?>>YouTube Video</option>
                                            <option value="link" <?php if($d['display_type'] == 'link') echo 'selected'; ?>>Web Link (iframe)</option>
                                            <option value="slide" <?php if($d['display_type'] == 'slide') echo 'selected'; ?>>Slide / Image</option>
                                            <option value="schedule" <?php if($d['display_type'] == 'schedule') echo 'selected'; ?>>Timetable / Schedule</option>
                                            <option value="api_events" <?php if($d['display_type'] == 'api_events') echo 'selected'; ?>>School API Events</option>
                                            <option value="embed_code" <?php if($d['display_type'] == 'embed_code') echo 'selected'; ?>>Custom HTML Embed (Instagram, Twitter, Widget)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3" id="field_link" style="display: none;">
                                    <label for="content_link" class="form-label">Video / Web Link URL</label>
                                    <input type="text" class="form-control" name="content_link" id="content_link" value="<?php echo htmlspecialchars($d['content_link']); ?>">
                                    <small class="form-text text-muted">For YouTube videos, use the embed URL. For web links, enter the full URL.</small>
                                </div>
                                
                                <div class="mb-3" id="field_embed" style="display: none;">
                                    <label for="content_html" class="form-label">Embed HTML Code</label>
                                    <textarea class="form-control" name="content_html" id="content_html" rows="5"><?php echo htmlspecialchars($d['content_html'] ?? ''); ?></textarea>
                                    <small class="form-text text-muted">Paste the embed code snippet from Instagram, Twitter, or any third-party widget here.</small>
                                </div>

                                <div class="mb-3" id="field_slide" style="display: none;">
                                    <label for="slide_image" class="form-label">Upload Slide Image</label>
                                    <input type="file" class="form-control" name="slide_image" id="slide_image" accept="image/*">
                                    <?php if($d['content_image']): ?>
                                        <div class="mt-2">
                                            <p>Current Image:</p>
                                            <img src="../<?php echo $d['content_image']; ?>" style="max-height: 200px;" alt="Current Slide">
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3" id="field_schedule" style="display: none;">
                                    <div class="alert alert-info">
                                        When the playlist reaches this item, it will automatically fetch and display today's schedule from the <strong>Schedule Manager</strong>. Make sure you add events there!
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="field_api_events" style="display: none;">
                                    <div class="alert alert-info">
                                        When the playlist reaches this item, it will fetch tomorrow's public events directly from the <strong>School Calendar API</strong>.
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="sequence" class="form-label">Sequence (Order)</label>
                                        <input type="number" class="form-control" name="sequence" id="sequence" value="<?php echo $d['sequence']; ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="duration_seconds" class="form-label">Duration (Seconds)</label>
                                        <input type="number" class="form-control" name="duration_seconds" id="duration_seconds" value="<?php echo $d['duration_seconds']; ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="is_active" class="form-label">Status</label>
                                        <select class="form-control" name="is_active" id="is_active">
                                            <option value="1" <?php if($d['is_active'] == 1) echo 'selected'; ?>>Active</option>
                                            <option value="0" <?php if($d['is_active'] == 0) echo 'selected'; ?>>Inactive</option>
                                        </select>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary">Save Content</button>
                                <a href="display-contents.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2026</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script>
        function toggleFields() {
            var type = document.getElementById('display_type').value;
            document.getElementById('field_link').style.display = (type === 'video' || type === 'link') ? 'block' : 'none';
            document.getElementById('field_slide').style.display = (type === 'slide') ? 'block' : 'none';
            document.getElementById('field_schedule').style.display = (type === 'schedule') ? 'block' : 'none';
            document.getElementById('field_api_events').style.display = (type === 'api_events') ? 'block' : 'none';
            document.getElementById('field_embed').style.display = (type === 'embed_code') ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', toggleFields);
    </script>
</body>
</html>
