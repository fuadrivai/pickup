<?php
include("../connect.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']) && $_POST['ajax'] === 'update-phone') {
    header('Content-Type: application/json; charset=utf-8');

    $homeroomId = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
    $phone = isset($_POST['phone']) ? trim((string) $_POST['phone']) : '';

    if ($homeroomId === '' || !ctype_digit($homeroomId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID homeroom tidak valid.']);
        exit;
    }

    if (strlen($phone) > 50) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nomor telepon maksimal 50 karakter.']);
        exit;
    }

    $existsStatement = mysqli_prepare($connect, "SELECT 1 FROM homeroom WHERE id = ? LIMIT 1");
    if (!$existsStatement) {
        error_log('Gagal menyiapkan pemeriksaan homeroom: ' . mysqli_error($connect));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memeriksa data homeroom.']);
        exit;
    }

    if (
        !mysqli_stmt_bind_param($existsStatement, 's', $homeroomId) ||
        !mysqli_stmt_execute($existsStatement) ||
        !mysqli_stmt_store_result($existsStatement)
    ) {
        error_log('Gagal memeriksa homeroom: ' . mysqli_stmt_error($existsStatement));
        mysqli_stmt_close($existsStatement);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memeriksa data homeroom.']);
        exit;
    }

    $homeroomExists = mysqli_stmt_num_rows($existsStatement) > 0;
    mysqli_stmt_close($existsStatement);
    if (!$homeroomExists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Data homeroom tidak ditemukan.']);
        exit;
    }

    $updateStatement = mysqli_prepare($connect, "UPDATE homeroom SET phone = ? WHERE id = ?");
    if (!$updateStatement) {
        error_log('Gagal menyiapkan update nomor homeroom: ' . mysqli_error($connect));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan nomor telepon.']);
        exit;
    }

    if (
        !mysqli_stmt_bind_param($updateStatement, 'ss', $phone, $homeroomId) ||
        !mysqli_stmt_execute($updateStatement)
    ) {
        error_log('Gagal menyimpan nomor homeroom: ' . mysqli_stmt_error($updateStatement));
        mysqli_stmt_close($updateStatement);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan nomor telepon.']);
        exit;
    }

    mysqli_stmt_close($updateStatement);
    echo json_encode(['success' => true, 'phone' => $phone]);
    exit;
}

$homerooms = mysqli_query($connect, "SELECT id, grade, phone FROM homeroom ORDER BY grade ASC");
if (!$homerooms) {
    error_log('Gagal mengambil daftar homeroom: ' . mysqli_error($connect));
    http_response_code(500);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Homeroom List</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Homeroom List</h1>
                    <div id="homeroomNotification" class="alert d-none" role="alert" aria-live="assertive"></div>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Homeroom Data</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Grade</th>
                                            <th>Phone</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($homerooms) { ?>
                                        <?php while ($homeroom = mysqli_fetch_assoc($homerooms)) { ?>
                                        <tr data-homeroom-id="<?php echo htmlspecialchars($homeroom['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <td><?php echo htmlspecialchars($homeroom['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($homeroom['grade'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="homeroom-phone"><?php echo htmlspecialchars($homeroom['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-success btn-circle btn-sm edit-phone-button"
                                                    data-id="<?php echo htmlspecialchars($homeroom['id'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-grade="<?php echo htmlspecialchars($homeroom['grade'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-phone="<?php echo htmlspecialchars($homeroom['phone'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    aria-label="Edit nomor telepon homeroom grade <?php echo htmlspecialchars($homeroom['grade'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
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

    <div class="modal fade" id="editPhoneModal" tabindex="-1" role="dialog" aria-labelledby="editPhoneModalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <form id="editPhoneForm" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPhoneModalTitle">Edit Nomor Telepon Homeroom</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="homeroomId" name="id">
                    <input type="hidden" name="ajax" value="update-phone">
                    <div class="form-group">
                        <label for="homeroomGrade">Grade</label>
                        <input type="text" class="form-control" id="homeroomGrade" readonly>
                    </div>
                    <div class="form-group">
                        <label for="homeroomPhone">Phone</label>
                        <input type="tel" class="form-control" id="homeroomPhone" name="phone" maxlength="50">
                    </div>
                    <div id="editPhoneStatus" role="alert" aria-live="polite"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="savePhoneButton">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(function () {
            var homeroomTable = $('#dataTable').DataTable();
            var notificationTimer;

            function showNotification(message, isSuccess) {
                var $notification = $('#homeroomNotification');
                window.clearTimeout(notificationTimer);
                $notification
                    .removeClass('d-none alert-success alert-danger')
                    .addClass(isSuccess ? 'alert-success' : 'alert-danger')
                    .text(message);
                notificationTimer = window.setTimeout(function () {
                    $notification.addClass('d-none');
                }, 6000);
            }

            $('#dataTable').on('click', '.edit-phone-button', function () {
                var $button = $(this);
                $('#homeroomId').val($button.attr('data-id'));
                $('#homeroomGrade').val($button.attr('data-grade'));
                $('#homeroomPhone').val($button.attr('data-phone'));
                $('#editPhoneStatus').text('').removeClass('text-danger');
                $('#editPhoneModal').modal('show');
            });

            $('#editPhoneForm').on('submit', function (event) {
                event.preventDefault();

                var $saveButton = $('#savePhoneButton');
                var $status = $('#editPhoneStatus');
                $saveButton.prop('disabled', true);
                $status.text('Menyimpan perubahan...');

                $.ajax({
                    url: 'homeroom-list.php',
                    method: 'POST',
                    dataType: 'json',
                    data: $(this).serialize()
                }).done(function (response) {
                    if (!response.success) {
                        $status.text(response.message || 'Gagal menyimpan nomor telepon.')
                            .addClass('text-danger');
                        showNotification(response.message || 'Gagal menyimpan nomor telepon.', false);
                        return;
                    }

                    var homeroomId = $('#homeroomId').val();
                    var $row = $('#dataTable tbody tr').filter(function () {
                        return $(this).attr('data-homeroom-id') === homeroomId;
                    });
                    $row.find('.homeroom-phone').text(response.phone);
                    $row.find('.edit-phone-button').attr('data-phone', response.phone);
                    homeroomTable.cell($row, 2).invalidate('dom');
                    $('#editPhoneModal').modal('hide');
                    showNotification('Nomor telepon homeroom berhasil diperbarui.', true);
                }).fail(function (xhr) {
                    var message = xhr.responseJSON && xhr.responseJSON.message
                        ? xhr.responseJSON.message
                        : 'Gagal menyimpan nomor telepon. Silakan coba lagi.';
                    $status.text(message).addClass('text-danger');
                    showNotification(message, false);
                }).always(function () {
                    $saveButton.prop('disabled', false);
                });
            });
        });
    </script>
</body>
</html>
