<!DOCTYPE html>
<html lang="en">
<?php
    include("../connect.php");
    $id = isset($_GET['id']) ? trim((string) $_GET['id']) : '';
    if ($id === '' || !ctype_digit($id)) {
        header('Location: student-list.php');
        exit;
    }

    $studentStatement = mysqli_prepare(
        $connect,
        "SELECT id, rfidid, student_name, grade FROM student WHERE id = ? LIMIT 1"
    );
    if (!$studentStatement) {
        error_log('Gagal menyiapkan data siswa untuk diedit: ' . mysqli_error($connect));
        http_response_code(500);
        exit('Unable to load student.');
    }

    if (
        !mysqli_stmt_bind_param($studentStatement, 's', $id) ||
        !mysqli_stmt_execute($studentStatement) ||
        !mysqli_stmt_bind_result($studentStatement, $studentId, $rfidid, $studentName, $grade)
    ) {
        error_log('Gagal mengambil data siswa untuk diedit: ' . mysqli_stmt_error($studentStatement));
        mysqli_stmt_close($studentStatement);
        http_response_code(500);
        exit('Unable to load student.');
    }

    if (mysqli_stmt_fetch($studentStatement) !== true) {
        mysqli_stmt_close($studentStatement);
        header('Location: student-list.php');
        exit;
    }
    mysqli_stmt_close($studentStatement);
    $d = [
        'id' => $studentId,
        'rfidid' => $rfidid,
        'student_name' => $studentName,
        'grade' => $grade
    ];

    $cardsStatement = mysqli_prepare(
        $connect,
        "SELECT rfidid_parents, registered_date FROM parents_card WHERE rfidid = ? ORDER BY registered_date DESC"
    );
    if (!$cardsStatement) {
        error_log('Gagal menyiapkan daftar kartu orang tua: ' . mysqli_error($connect));
        http_response_code(500);
        exit('Unable to load parents cards.');
    }

    if (
        !mysqli_stmt_bind_param($cardsStatement, 's', $rfidid) ||
        !mysqli_stmt_execute($cardsStatement) ||
        !mysqli_stmt_bind_result($cardsStatement, $parentsCardId, $registeredDate)
    ) {
        error_log('Gagal mengambil daftar kartu orang tua: ' . mysqli_stmt_error($cardsStatement));
        mysqli_stmt_close($cardsStatement);
        http_response_code(500);
        exit('Unable to load parents cards.');
    }

    $parentsCards = [];
    while (($fetchResult = mysqli_stmt_fetch($cardsStatement)) === true) {
        $parentsCards[] = [
            'rfidid_parents' => $parentsCardId,
            'registered_date' => $registeredDate
        ];
    }
    if ($fetchResult === false) {
        error_log('Gagal membaca daftar kartu orang tua: ' . mysqli_stmt_error($cardsStatement));
        mysqli_stmt_close($cardsStatement);
        http_response_code(500);
        exit('Unable to load parents cards.');
    }
    mysqli_stmt_close($cardsStatement);

    $statusMessages = [
        'created' => ['success', 'Student added successfully.'],
        'card_error' => ['warning', 'Student was added, but one or more parents cards could not be saved. Check the cards below and add any missing cards.'],
        'added' => ['success', 'Parents card added successfully.'],
        'deleted' => ['success', 'Parents card deleted successfully.'],
        'invalid' => ['warning', 'Enter a parents card ID.'],
        'not_found' => ['warning', 'The parents card was not found for this student.'],
        'student_not_found' => ['warning', 'The student was not found.'],
        'error' => ['danger', 'Unable to update the parents card. Please check the server error log.']
    ];
    $status = isset($_GET['status']) ? (string) $_GET['status'] : '';
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
                    <h1 class="h3 mb-4 text-gray-800">Edit Student - <?php echo htmlspecialchars($d['student_name'], ENT_QUOTES, 'UTF-8'); ?> </h1>
                    <?php if (isset($statusMessages[$status])) { ?>
                        <div class="alert alert-<?php echo $statusMessages[$status][0]; ?>" role="alert">
                            <?php echo htmlspecialchars($statusMessages[$status][1], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <form method="post" action="update.php?type=edit-student">
                        <input class="form-control" style="display:none" name="id" value="<?php echo htmlspecialchars((string) $d['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Student ID</label>
                            <input class="form-control" name="rfidid" value="<?php echo htmlspecialchars($d['rfidid'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputEmail1" class="form-label">Student Name</label>
                            <input class="form-control" name="student_name" value="<?php echo htmlspecialchars($d['student_name'], ENT_QUOTES, 'UTF-8'); ?>">
                            
                        </div>
                        <div class="mb-3">
                            <label for="exampleInputPassword1" class="form-label">Grade</label>
                            <input class="form-control" name="grade" value="<?php echo htmlspecialchars($d['grade'], ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <a href="delete-student.php?id=<?php echo urlencode($d['id']); ?>"
                            class="btn btn-danger"
                            onclick="return confirm('Apakah Anda yakin ingin menghapus siswa ini?');">
                            Hapus
                        </a>
                    </form>
                    <div class="card shadow mb-4 mt-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Parents Cards</h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="update.php?type=parents_card" class="form-inline mb-4">
                                <input type="hidden" name="student_id" value="<?php echo htmlspecialchars((string) $d['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-group mr-2 mb-2">
                                    <label class="sr-only" for="parentsCardId">Parents Card ID</label>
                                    <input class="form-control" id="parentsCardId" name="rfidid_parents"
                                        placeholder="Parents Card ID" required>
                                </div>
                                <button type="submit" class="btn btn-primary mb-2">Add Card</button>
                            </form>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Parents Card ID</th>
                                            <th>Register Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($parentsCards) === 0) { ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No parents cards registered.</td>
                                            </tr>
                                        <?php } else { ?>
                                            <?php foreach ($parentsCards as $card) { ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($card['rfidid_parents'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?php echo htmlspecialchars($card['registered_date'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <a href="delete.php?type=parents_card&amp;student_id=<?php echo rawurlencode((string) $d['id']); ?>&amp;rfidid_parents=<?php echo rawurlencode($card['rfidid_parents']); ?>"
                                                            class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Delete this parents card?');">
                                                            Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
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