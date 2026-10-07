<?php
include("../connect.php");

if (isset($_GET['ajax']) && $_GET['ajax'] === 'pickup-requests') {
    header('Content-Type: application/json; charset=utf-8');

    $grade = isset($_GET['grade']) ? trim((string) $_GET['grade']) : '';
    if ($grade !== '') {
        $gradeStatement = mysqli_prepare($connect, "SELECT 1 FROM homeroom WHERE grade = ? LIMIT 1");
        if (!$gradeStatement) {
            error_log('Gagal menyiapkan validasi grade pickup: ' . mysqli_error($connect));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Gagal memvalidasi grade.']);
            exit;
        }

        if (
            !mysqli_stmt_bind_param($gradeStatement, 's', $grade) ||
            !mysqli_stmt_execute($gradeStatement) ||
            !mysqli_stmt_store_result($gradeStatement)
        ) {
            error_log('Gagal memvalidasi grade pickup: ' . mysqli_stmt_error($gradeStatement));
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

    $query = "SELECT id, student_name, student_grade, parent_name, parent_phone, created_at
              FROM pickup_requests";
    if ($grade !== '') {
        $query .= " WHERE student_grade = ?";
    }
    $query .= " ORDER BY created_at DESC, id DESC";

    $statement = mysqli_prepare($connect, $query);
    if (!$statement) {
        error_log('Gagal menyiapkan daftar pickup: ' . mysqli_error($connect));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data pickup.']);
        exit;
    }

    if (
        ($grade !== '' && !mysqli_stmt_bind_param($statement, 's', $grade)) ||
        !mysqli_stmt_execute($statement) ||
        !mysqli_stmt_bind_result(
            $statement,
            $requestId,
            $studentName,
            $studentGrade,
            $parentName,
            $parentPhone,
            $createdAt
        )
    ) {
        error_log('Gagal mengambil daftar pickup: ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data pickup.']);
        exit;
    }

    $requests = [];
    while (($fetchResult = mysqli_stmt_fetch($statement)) === true) {
        $requests[] = [
            'id' => $requestId,
            'student_name' => $studentName,
            'student_grade' => $studentGrade,
            'parent_name' => $parentName,
            'parent_phone' => $parentPhone,
            'created_at' => $createdAt
        ];
    }

    if ($fetchResult === false) {
        error_log('Gagal membaca daftar pickup: ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal mengambil data pickup.']);
        exit;
    }

    mysqli_stmt_close($statement);
    echo json_encode(['success' => true, 'requests' => $requests]);
    exit;
}

$gradeOptions = mysqli_query(
    $connect,
    "SELECT DISTINCT grade FROM homeroom WHERE grade IS NOT NULL AND grade <> '' ORDER BY grade DESC"
);
if (!$gradeOptions) {
    error_log('Gagal mengambil pilihan grade pickup: ' . mysqli_error($connect));
    http_response_code(500);
}

$pickupRequests = mysqli_query(
    $connect,
    "SELECT id, student_name, student_grade, parent_name, parent_phone, created_at
     FROM pickup_requests
     ORDER BY created_at DESC, id DESC"
);
if (!$pickupRequests) {
    error_log('Gagal mengambil daftar pickup: ' . mysqli_error($connect));
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Pickup Requests</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <style>
        #dataTable_filter {
            display: none;
        }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Pickup Requests</h1>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar Pickup Siswa</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="gradeFilter">Filter Grade</label>
                                    <select class="form-control" id="gradeFilter" style="width: 100%;">
                                        <option value="">Semua grade</option>
                                        <?php if ($gradeOptions) { ?>
                                        <?php while ($gradeOption = mysqli_fetch_assoc($gradeOptions)) { ?>
                                        <option value="<?php echo htmlspecialchars($gradeOption['grade'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($gradeOption['grade'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                        <?php } ?>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="studentNameSearch">Cari Nama Siswa</label>
                                    <input type="search" class="form-control" id="studentNameSearch"
                                        placeholder="Ketik nama siswa...">
                                </div>
                            </div>
                            <div id="pickupListStatus" class="mb-3" role="status" aria-live="polite"></div>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Nama Siswa</th>
                                            <th>Grade</th>
                                            <th>Nama Orang Tua</th>
                                            <th>Nomor Telepon</th>
                                            <th>Waktu Pickup</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($pickupRequests) { ?>
                                        <?php while ($request = mysqli_fetch_assoc($pickupRequests)) { ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($request['student_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($request['student_grade'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($request['parent_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($request['parent_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($request['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                        <?php } ?>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
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
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(function () {
            var pickupTable = $('#dataTable').DataTable();
            var pickupRequest = null;

            $('#studentNameSearch').on('input', function () {
                pickupTable.column(0).search(this.value).draw();
            });

            $('#gradeFilter').select2({
                placeholder: 'Pilih grade',
                allowClear: true
            }).on('change', function () {
                var selectedGrade = $(this).val();

                if (pickupRequest) {
                    pickupRequest.abort();
                }

                $('#pickupListStatus').text('Memuat data pickup...');
                pickupRequest = $.ajax({
                    url: 'pickup-requests.php',
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        ajax: 'pickup-requests',
                        grade: selectedGrade || ''
                    }
                }).done(function (response) {
                    if (!response.success) {
                        $('#pickupListStatus').text(response.message || 'Gagal memuat data pickup.');
                        return;
                    }

                    var rows = response.requests.map(function (request) {
                        return [
                            escapeHtml(request.student_name),
                            escapeHtml(request.student_grade),
                            escapeHtml(request.parent_name),
                            escapeHtml(request.parent_phone),
                            escapeHtml(request.created_at)
                        ];
                    });

                    pickupTable.clear().rows.add(rows).draw();
                    $('#pickupListStatus').text('');
                }).fail(function (xhr, status) {
                    if (status !== 'abort') {
                        $('#pickupListStatus').text('Gagal memuat data pickup. Silakan coba lagi.');
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
