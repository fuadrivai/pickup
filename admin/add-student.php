<?php
include("../connect.php");

$gradeOptions = mysqli_query(
    $connect,
    "SELECT DISTINCT grade FROM homeroom WHERE grade IS NOT NULL AND grade <> '' ORDER BY grade DESC"
);
if (!$gradeOptions) {
    error_log('Gagal mengambil pilihan grade homeroom: ' . mysqli_error($connect));
    http_response_code(500);
    exit('Unable to load grade options.');
}

$grades = [];
while ($gradeOption = mysqli_fetch_assoc($gradeOptions)) {
    $grades[] = $gradeOption['grade'];
}

$statusMessages = [
    'invalid' => ['warning', 'Isi Student ID, nama, dan grade terlebih dahulu.'],
    'error' => ['danger', 'Student gagal ditambahkan. Silakan periksa log server.']
];
$status = isset($_GET['status']) ? (string) $_GET['status'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tambah Student</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container {
            width: 100% !important;
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
                    <h1 class="h3 mb-4 text-gray-800">Tambah Student</h1>
                    <?php if (isset($statusMessages[$status])) { ?>
                        <div class="alert alert-<?php echo $statusMessages[$status][0]; ?>" role="alert">
                            <?php echo htmlspecialchars($statusMessages[$status][1], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    <?php } ?>
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="post" action="create-student.php">
                                <div class="form-group">
                                    <label for="studentRfid">Student ID</label>
                                    <input class="form-control" id="studentRfid" name="rfidid" required>
                                </div>
                                <div class="form-group">
                                    <label for="studentName">Nama Student</label>
                                    <input class="form-control" id="studentName" name="student_name" required>
                                </div>
                                <div class="form-group">
                                    <label for="studentGrade">Grade</label>
                                    <select class="form-control" id="studentGrade" name="grade" required
                                        <?php echo count($grades) === 0 ? 'disabled' : ''; ?>>
                                        <option value=""></option>
                                        <?php foreach ($grades as $grade) { ?>
                                            <option value="<?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($grade, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                    <?php if (count($grades) === 0) { ?>
                                        <small class="form-text text-warning">Belum ada grade di homeroom.</small>
                                    <?php } ?>
                                </div>
                                <div class="form-group">
                                    <label>Parent Card ID <span class="text-muted">(opsional, bisa lebih dari satu)</span></label>
                                    <div id="parentCardFields">
                                        <div class="input-group mb-2 parent-card-field">
                                            <input class="form-control" name="rfidid_parents[]" placeholder="Parent Card ID">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-danger remove-parent-card" type="button" aria-label="Hapus kartu orang tua" disabled>
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn btn-outline-primary btn-sm" id="addParentCard" type="button">
                                        <i class="fas fa-plus mr-1"></i> Tambah Parent Card
                                    </button>
                                </div>
                                <button type="submit" class="btn btn-primary" <?php echo count($grades) === 0 ? 'disabled' : ''; ?>>Simpan Student</button>
                                <a href="student-list.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; Your Website 2020</span>
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
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script>
        $('#studentGrade').select2({
            placeholder: 'Pilih grade',
            allowClear: true
        });

        $('#addParentCard').on('click', function () {
            var $field = $('#parentCardFields .parent-card-field').first().clone();
            $field.find('input').val('');
            $field.find('.remove-parent-card').prop('disabled', false);
            $('#parentCardFields').append($field);
        });

        $('#parentCardFields').on('click', '.remove-parent-card', function () {
            if ($('#parentCardFields .parent-card-field').length > 1) {
                $(this).closest('.parent-card-field').remove();
            }
        });
    </script>
</body>
</html>
