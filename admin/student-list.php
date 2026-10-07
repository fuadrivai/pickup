<?php
    include("../connect.php");

    if (isset($_GET['ajax']) && $_GET['ajax'] === 'students') {
        header('Content-Type: application/json; charset=utf-8');

        $grade = isset($_GET['grade']) ? trim((string) $_GET['grade']) : '';
        if ($grade !== '') {
            $gradeStatement = mysqli_prepare($connect, "SELECT 1 FROM homeroom WHERE grade = ? LIMIT 1");
            if (!$gradeStatement) {
                error_log('Gagal menyiapkan query validasi grade: ' . mysqli_error($connect));
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memvalidasi grade.']);
                exit;
            }

            if (
                !mysqli_stmt_bind_param($gradeStatement, 's', $grade) ||
                !mysqli_stmt_execute($gradeStatement) ||
                !mysqli_stmt_store_result($gradeStatement)
            ) {
                error_log('Gagal memvalidasi grade: ' . mysqli_stmt_error($gradeStatement));
                mysqli_stmt_close($gradeStatement);
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Gagal memvalidasi grade.']);
                exit;
            }

            $gradeExists = mysqli_stmt_num_rows($gradeStatement) > 0;
            mysqli_stmt_close($gradeStatement);

            if (!$gradeExists) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Grade tidak ditemukan.']);
                exit;
            }
        }

        $studentQuery = "SELECT s.id, s.rfidid, s.student_name, s.grade,
                            (SELECT COUNT(*) FROM parents_card pc WHERE pc.rfidid = s.rfidid) AS parents_card_count
                         FROM student s";
        if ($grade !== '') {
            $studentQuery .= " WHERE s.grade = ?";
        }
        $studentQuery .= " ORDER BY s.grade DESC";

        $studentStatement = mysqli_prepare($connect, $studentQuery);
        if (!$studentStatement) {
            error_log('Gagal menyiapkan query daftar siswa: ' . mysqli_error($connect));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data siswa.']);
            exit;
        }

        if (
            ($grade !== '' && !mysqli_stmt_bind_param($studentStatement, 's', $grade)) ||
            !mysqli_stmt_execute($studentStatement) ||
            !mysqli_stmt_bind_result(
                $studentStatement,
                $studentId,
                $rfidId,
                $studentName,
                $studentGrade,
                $parentsCardCount
            )
        ) {
            error_log('Gagal mengambil data siswa: ' . mysqli_stmt_error($studentStatement));
            mysqli_stmt_close($studentStatement);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data siswa.']);
            exit;
        }

        $students = [];
        while (($fetchResult = mysqli_stmt_fetch($studentStatement)) === true) {
            $students[] = [
                'id' => $studentId,
                'rfidid' => $rfidId,
                'student_name' => $studentName,
                'grade' => $studentGrade,
                'parents_card_count' => $parentsCardCount
            ];
        }
        if ($fetchResult === false) {
            error_log('Gagal membaca data siswa: ' . mysqli_stmt_error($studentStatement));
            mysqli_stmt_close($studentStatement);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data siswa.']);
            exit;
        }
        mysqli_stmt_close($studentStatement);

        echo json_encode(['success' => true, 'students' => $students]);
        exit;
    }

    $sql = mysqli_query($connect, "SELECT * FROM student ORDER BY grade DESC");
    $gradeOptions = mysqli_query(
        $connect,
        "SELECT DISTINCT grade FROM homeroom WHERE grade IS NOT NULL AND grade <> '' ORDER BY grade DESC"
    );
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>SB Admin 2 - Tables</title>

    <!-- Custom fonts for this template -->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">

    <!-- Custom styles for this page -->
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <style>
        #dataTable_filter {
            display: none;
        }

        #pickupNotification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1100;
            min-width: 280px;
        }
    </style>

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
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h1 class="h3 mb-0 text-gray-800">List Siswa</h1>
                        <a href="add-student.php" class="btn btn-primary">
                            <i class="fas fa-user-plus mr-1"></i> Tambah Student
                        </a>
                    </div>
                    <!-- DataTales Example -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">DataTables</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="gradeFilter">Filter Grade</label>
                                    <select class="form-control" id="gradeFilter" style="width: 100%;">
                                        <option value="">Semua grade</option>
                                        <?php while ($gradeOption = mysqli_fetch_assoc($gradeOptions)) { ?>
                                        <option value="<?php echo htmlspecialchars($gradeOption['grade'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($gradeOption['grade'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="studentNameSearch">Cari Nama Siswa</label>
                                    <input type="search" class="form-control" id="studentNameSearch"
                                        placeholder="Ketik nama siswa...">
                                </div>
                            </div>
                            <div id="studentFilterStatus" class="mb-3" role="status" aria-live="polite"></div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Student Id</th>
                                            <th>Student Name</th>
                                            <th>Grade</th>
                                            <th>Parents Card</th>
                                            <th>Pickup</th>
                                            <th>Action</th>

                                        </tr>
                                    </thead>
                                    <tfoot>
                                        <tr>
                                            <th>Student Id</th>
                                            <th>Student Name</th>
                                            <th>Grade</th>
                                            <th>Parents Card</th>
                                            <th>Pickup</th>
                                            <th>Action</th>

                                        </tr>
                                    </tfoot>
                                    <tbody>
                                        <?php while($d = mysqli_fetch_array($sql)){
                                            $rfidid = $d['rfidid'];
                                            $parents_card = mysqli_query($connect, "SELECT * FROM parents_card where rfidid = '$rfidid'");
                                            $count = mysqli_num_rows($parents_card);
                                            ?>
                                        <tr>
                                            <td><?php echo$d['rfidid']; ?></td>
                                            <td><?php echo$d['student_name']; ?></td>
                                            <td><?php echo$d['grade']; ?></td>
                                            <td><a
                                                    href="edit-student.php?id=<?php echo urlencode($d['id']); ?>"><?php echo$count; ?></a>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm pickup-button"
                                                    data-student-id="<?php echo htmlspecialchars($d['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-student-name="<?php echo htmlspecialchars($d['student_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-student-grade="<?php echo htmlspecialchars($d['grade'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    Pickup
                                                </button>
                                            </td>
                                            <td>
                                                <a href="edit-student.php?id=<?php echo $d['id'] ?>"
                                                    class="btn btn-success btn-circle btn-sm">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

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

    <div id="pickupNotification" class="alert d-none" role="alert" aria-live="assertive">
        <span id="pickupNotificationMessage"></span>
        <button type="button" class="close" id="dismissPickupNotification" aria-label="Tutup">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>

    <div class="modal fade" id="pickupModal" tabindex="-1" role="dialog" aria-labelledby="pickupModalTitle"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="pickupForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pickupModalTitle">Pickup Siswa</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="pickupStudentId" name="student_id">
                    <div class="mb-3">
                        <strong>Nama siswa:</strong>
                        <span id="pickupStudentName"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Kelas:</strong>
                        <span id="pickupStudentGrade"></span>
                    </div>
                    <div class="form-group">
                        <label for="pickupParentName">Nama orang tua</label>
                        <input type="text" class="form-control" id="pickupParentName" name="parent_name"
                            maxlength="255" required>
                    </div>
                    <div class="form-group">
                        <label for="pickupParentPhone">Nomor telepon</label>
                        <input type="tel" class="form-control" id="pickupParentPhone" name="parent_phone"
                            maxlength="50" required>
                    </div>
                    <div id="pickupFormStatus" role="alert" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="pickupSubmitButton">Submit</button>
                </div>
            </form>
        </div>
    </div>

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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <!-- Page level plugins -->
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Page level custom scripts -->
    <script src="js/demo/datatables-demo.js"></script>
    <script>
        $(function () {
            var studentTable = $('#dataTable').DataTable();
            var studentRequest = null;
            var pickupNotificationTimer = null;

            function showPickupNotification(message, type) {
                var $notification = $('#pickupNotification');

                window.clearTimeout(pickupNotificationTimer);
                $notification
                    .removeClass('d-none alert-success alert-danger alert-warning')
                    .addClass(type === 'success' ? 'alert-success' : (type === 'warning' ? 'alert-warning' : 'alert-danger'));
                $('#pickupNotificationMessage').text(message);

                pickupNotificationTimer = window.setTimeout(function () {
                    $notification.addClass('d-none');
                }, 6000);
            }

            $('#dismissPickupNotification').on('click', function () {
                window.clearTimeout(pickupNotificationTimer);
                $('#pickupNotification').addClass('d-none');
            });

            $('#dataTable').on('click', '.pickup-button', function () {
                var $button = $(this);
                $('#pickupStudentId').val($button.attr('data-student-id'));
                $('#pickupStudentName').text($button.attr('data-student-name'));
                $('#pickupStudentGrade').text($button.attr('data-student-grade'));
                $('#pickupFormStatus').text('').removeClass('text-danger text-success');
                $('#pickupParentName, #pickupParentPhone').val('');
                $('#pickupModal').modal('show');
            });

            $('#pickupForm').on('submit', function (event) {
                event.preventDefault();

                var $submitButton = $('#pickupSubmitButton');
                var $status = $('#pickupFormStatus');
                $submitButton.prop('disabled', true);
                $status.text('Menyimpan data pickup...').removeClass('text-danger text-success');

                $.ajax({
                    url: 'save-pickup.php',
                    method: 'POST',
                    dataType: 'json',
                    data: $(this).serialize()
                }).done(function (response) {
                    if (!response.success) {
                        var message = response.message || 'Gagal menyimpan data pickup.';
                        $status.text(message).addClass('text-danger');
                        showPickupNotification(message, 'error');
                        return;
                    }

                    $('#pickupModal').modal('hide');

                    var message = 'Data pickup berhasil disimpan.';
                    var notificationType = 'success';
                    if (!response.pickup) {
                        message += ' Namun status layar pickup dan WhatsApp tidak dapat dipastikan.';
                        notificationType = 'warning';
                    } else if (!response.pickup.display_updated) {
                        message += ' ' + (response.pickup.error || 'Data gagal ditampilkan pada layar pickup.');
                        notificationType = 'warning';
                    } else if (response.pickup.whatsapp_sent) {
                        message += ' Siswa ditampilkan pada layar pickup dan notifikasi WhatsApp terkirim ke homeroom.';
                    } else {
                        message += ' Siswa ditampilkan pada layar pickup, tetapi ' +
                            (response.pickup.error || 'notifikasi WhatsApp gagal dikirim.');
                        notificationType = 'warning';
                    }
                    showPickupNotification(message, notificationType);
                }).fail(function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Gagal menyimpan data pickup. Silakan coba lagi.';
                    $status.text(message).addClass('text-danger');
                    showPickupNotification(message, 'error');
                }).always(function () {
                    $submitButton.prop('disabled', false);
                });
            });

            $('#studentNameSearch').on('input', function () {
                studentTable.column(1).search(this.value).draw();
            });

            $('#gradeFilter').select2({
                placeholder: 'Pilih grade',
                allowClear: true
            }).on('change', function () {
                var selectedGrade = $(this).val();
                var $status = $('#studentFilterStatus');

                if (studentRequest) {
                    studentRequest.abort();
                }

                $status.text('Memuat data siswa...');
                studentRequest = $.ajax({
                    url: 'student-list.php',
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        ajax: 'students',
                        grade: selectedGrade || ''
                    }
                }).done(function (response) {
                    if (!response.success) {
                        $status.text(response.message || 'Gagal memuat data siswa.');
                        return;
                    }

                    var rows = response.students.map(function (student) {
                        var id = encodeURIComponent(student.id);

                        return [
                            escapeHtml(student.rfidid),
                            escapeHtml(student.student_name),
                            escapeHtml(student.grade),
                            '<a href="edit-student.php?id=' + id + '">' +
                                escapeHtml(student.parents_card_count) + '</a>',
                            '<button type="button" class="btn btn-primary btn-sm pickup-button"' +
                                ' data-student-id="' + escapeHtml(student.id) + '"' +
                                ' data-student-name="' + escapeHtml(student.student_name) + '"' +
                                ' data-student-grade="' + escapeHtml(student.grade) + '">Pickup</button>',
                            '<a href="edit-student.php?id=' + id + '" class="btn btn-success btn-circle btn-sm">' +
                                '<i class="fas fa-pencil-alt"></i></a>'
                        ];
                    });

                    studentTable.clear().rows.add(rows).draw();
                    $status.text('');
                }).fail(function (xhr, status) {
                    if (status !== 'abort') {
                        $status.text('Gagal memuat data siswa. Silakan coba lagi.');
                    }
                });
            });

            function escapeHtml(value) {
                return $('<div>').text(value == null ? '' : value).html();
            }
        });
    </script>

</body>

</html>