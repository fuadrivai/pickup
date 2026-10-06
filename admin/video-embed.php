<!DOCTYPE html>
<html lang="en">
<?php
    include("../connect.php");
    $sql = mysqli_query($connect, "SELECT * FROM video where id = 1");
    $d = mysqli_fetch_array($sql)
?>
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SB Admin 2 - Blank</title>

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php
        include "sidebar.php";
         ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include 'header.php' ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Display Settings</h1>
                    <form method="post" action="update.php?type=display-settings" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="display_type" class="form-label">Display Type</label>
                            <select class="form-control" name="display_type" id="display_type" onchange="toggleFields()">
                                <option value="video" <?php if($d['display_type'] == 'video') echo 'selected'; ?>>YouTube Video</option>
                                <option value="link" <?php if($d['display_type'] == 'link') echo 'selected'; ?>>Web Link (iframe)</option>
                                <option value="slide" <?php if($d['display_type'] == 'slide') echo 'selected'; ?>>Slide / Image</option>
                                <option value="schedule" <?php if($d['display_type'] == 'schedule') echo 'selected'; ?>>Custom Schedule (Text)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3" id="field_link" style="display: none;">
                            <label for="link" class="form-label">Video / Web Link URL</label>
                            <input type="text" class="form-control" name="link" id="link" value="<?php echo $d['link']?>">
                            <small class="form-text text-muted">For YouTube videos, use the embed URL. For web links, enter the full URL (https://...).</small>
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
                            <label for="content_schedule" class="form-label">Custom Schedule (HTML supported)</label>
                            <textarea class="form-control" name="content_schedule" id="content_schedule" rows="10"><?php echo htmlspecialchars($d['content_schedule'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">You can write plain text or use HTML tags for better formatting (e.g., &lt;table&gt;, &lt;b&gt;, &lt;br&gt;).</small>
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
                <!-- /.container-fluid -->

                <script>
                    function toggleFields() {
                        var type = document.getElementById('display_type').value;
                        document.getElementById('field_link').style.display = (type === 'video' || type === 'link') ? 'block' : 'none';
                        document.getElementById('field_slide').style.display = (type === 'slide') ? 'block' : 'none';
                        document.getElementById('field_schedule').style.display = (type === 'schedule') ? 'block' : 'none';
                    }
                    // Run on load
                    document.addEventListener('DOMContentLoaded', toggleFields);
                </script>

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2020</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="login.html">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

</body>

</html>