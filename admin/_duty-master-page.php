<?php
include_once __DIR__ . '/../connect.php';

function dutyMasterPageEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dutyMasterPageRedirect($page, $message, $type)
{
    header('Location: ' . $page . '?message=' . rawurlencode($message) . '&type=' . rawurlencode($type));
    exit;
}

function dutyMasterPageGetRow($connect, $table, $id)
{
    $statement = mysqli_prepare($connect, "SELECT * FROM $table WHERE id = ? LIMIT 1");
    if (!$statement || !mysqli_stmt_bind_param($statement, 'i', $id) || !mysqli_stmt_execute($statement)) {
        error_log('Gagal mengambil master duty roster: ' . ($statement ? mysqli_stmt_error($statement) : mysqli_error($connect)));
        if ($statement) {
            mysqli_stmt_close($statement);
        }
        return false;
    }

    $result = mysqli_stmt_get_result($statement);
    $row = $result ? mysqli_fetch_assoc($result) : false;
    mysqli_stmt_close($statement);
    return $row;
}

function dutyMasterPageRemovePhoto($photoPath)
{
    if (!is_string($photoPath) || !preg_match('#^admin/uploads/teachers/[a-f0-9]{32}\.(jpg|png|webp)$#', $photoPath)) {
        return;
    }

    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $photoPath);
    if (is_file($absolutePath) && !unlink($absolutePath)) {
        error_log('Gagal menghapus foto guru: ' . $absolutePath);
    }
}

function dutyMasterPagePhotoSource($photoPath)
{
    if (preg_match('#^https?://#i', (string) $photoPath)) {
        return (string) $photoPath;
    }

    return '../' . ltrim((string) $photoPath, '/');
}

$masterKey = $dutyMaster['key'];
$pageName = basename($_SERVER['SCRIPT_NAME']);
$errorMessage = '';
$successMessage = isset($_GET['message']) && isset($_GET['type']) && $_GET['type'] === 'success'
    ? (string) $_GET['message']
    : '';
$modalOpen = false;
$formId = '';
$formName = '';
$formDivisionId = '';
$currentPhotoPath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $idValue = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
    if (!in_array($action, ['save', 'delete'], true) || ($idValue !== '' && !ctype_digit($idValue))) {
        $errorMessage = 'Permintaan tidak valid.';
    } else {
        $id = $idValue === '' ? 0 : (int) $idValue;
        if ($action === 'delete') {
            if ($id < 1) {
                $errorMessage = 'Data yang akan dihapus tidak valid.';
            } else {
                $existing = $masterKey === 'teacher' ? dutyMasterPageGetRow($connect, $dutyMaster['table'], $id) : true;
                if ($existing === false) {
                    $errorMessage = 'Data tidak ditemukan atau gagal diperiksa.';
                } else {
                    $statement = mysqli_prepare($connect, 'DELETE FROM ' . $dutyMaster['table'] . ' WHERE id = ?');
                    if ($statement && mysqli_stmt_bind_param($statement, 'i', $id) && mysqli_stmt_execute($statement)) {
                        mysqli_stmt_close($statement);
                        if ($masterKey === 'teacher') {
                            dutyMasterPageRemovePhoto($existing['photo_path']);
                        }
                        dutyMasterPageRedirect($pageName, 'Data ' . $dutyMaster['title'] . ' berhasil dihapus.', 'success');
                    }

                    error_log('Gagal menghapus master duty roster: ' . ($statement ? mysqli_stmt_error($statement) : mysqli_error($connect)));
                    if ($statement) {
                        mysqli_stmt_close($statement);
                    }
                    $errorMessage = 'Data tidak dapat dihapus karena masih digunakan oleh data lain.';
                }
            }
        } else {
            $modalOpen = true;
            $formId = $idValue;
            $formName = trim(isset($_POST['name']) ? (string) $_POST['name'] : '');
            $formDivisionId = isset($_POST['division_id']) ? trim((string) $_POST['division_id']) : '';
            $existing = $id > 0 ? dutyMasterPageGetRow($connect, $dutyMaster['table'], $id) : null;
            if ($masterKey === 'teacher' && is_array($existing)) {
                $currentPhotoPath = (string) $existing['photo_path'];
            }

            $divisionValid = !$dutyMaster['has_division']
                || (ctype_digit($formDivisionId) && (int) $formDivisionId > 0);
            if ($id > 0 && $existing === false) {
                $errorMessage = 'Data ' . strtolower($dutyMaster['title']) . ' yang akan diubah tidak ditemukan.';
            } elseif ($formName === '' || strlen($formName) > ($masterKey === 'division' ? 100 : 150)) {
                $errorMessage = 'Nama wajib diisi dan tidak boleh melebihi batas panjang.';
            } elseif (!$divisionValid) {
                $errorMessage = 'Divisi wajib dipilih.';
            } else {
                $newPhotoPath = null;
                $upload = isset($_FILES['photo']) ? $_FILES['photo'] : null;
                if ($masterKey === 'teacher' && $upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($upload['error'] !== UPLOAD_ERR_OK) {
                        $errorMessage = 'Upload foto gagal. Silakan coba lagi.';
                    } elseif ($upload['size'] > 5 * 1024 * 1024) {
                        $errorMessage = 'Ukuran foto maksimal 5 MB.';
                    } else {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo ? finfo_file($finfo, $upload['tmp_name']) : false;
                        if ($finfo) {
                            finfo_close($finfo);
                        }
                        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                        if (!$mimeType || !isset($allowedTypes[$mimeType]) || !getimagesize($upload['tmp_name'])) {
                            $errorMessage = 'Format foto harus JPG, PNG, atau WebP.';
                        } else {
                            $uploadDirectory = __DIR__ . '/uploads/teachers';
                            if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                                $errorMessage = 'Folder upload foto tidak dapat dibuat.';
                            } else {
                                $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
                                $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $fileName;
                                if (move_uploaded_file($upload['tmp_name'], $destination)) {
                                    $newPhotoPath = 'admin/uploads/teachers/' . $fileName;
                                } else {
                                    $errorMessage = 'Foto gagal disimpan ke server.';
                                }
                            }
                        }
                    }
                }

                if ($errorMessage === '') {
                    if ($masterKey === 'division') {
                        if ($id > 0) {
                            $statement = mysqli_prepare($connect, 'UPDATE duty_divisions SET name = ? WHERE id = ?');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 'si', $formName, $id);
                        } else {
                            $statement = mysqli_prepare($connect, 'INSERT INTO duty_divisions (name) VALUES (?)');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 's', $formName);
                        }
                    } elseif ($masterKey === 'teacher') {
                        $divisionId = (int) $formDivisionId;
                        $photoPath = $newPhotoPath !== null ? $newPhotoPath : ($id > 0 ? $existing['photo_path'] : null);
                        if ($id > 0) {
                            $statement = mysqli_prepare($connect, 'UPDATE duty_teachers SET name = ?, photo_path = ?, division_id = ? WHERE id = ?');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 'ssii', $formName, $photoPath, $divisionId, $id);
                        } else {
                            $statement = mysqli_prepare($connect, 'INSERT INTO duty_teachers (name, photo_path, division_id) VALUES (?, ?, ?)');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 'ssi', $formName, $photoPath, $divisionId);
                        }
                    } elseif ($masterKey === 'event') {
                        if ($id > 0) {
                            $statement = mysqli_prepare($connect, 'UPDATE duty_events SET name = ? WHERE id = ?');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 'si', $formName, $id);
                        } else {
                            $statement = mysqli_prepare($connect, 'INSERT INTO duty_events (name) VALUES (?)');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 's', $formName);
                        }
                    } elseif ($masterKey === 'place') {
                        if ($id > 0) {
                            $statement = mysqli_prepare($connect, 'UPDATE duty_places SET name = ? WHERE id = ?');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 'si', $formName, $id);
                        } else {
                            $statement = mysqli_prepare($connect, 'INSERT INTO duty_places (name) VALUES (?)');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 's', $formName);
                        }
                    } else {
                        $divisionId = (int) $formDivisionId;
                        if ($id > 0) {
                            $statement = mysqli_prepare($connect, 'UPDATE ' . $dutyMaster['table'] . ' SET name = ?, division_id = ? WHERE id = ?');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 'sii', $formName, $divisionId, $id);
                        } else {
                            $statement = mysqli_prepare($connect, 'INSERT INTO ' . $dutyMaster['table'] . ' (name, division_id) VALUES (?, ?)');
                            $bound = $statement && mysqli_stmt_bind_param($statement, 'si', $formName, $divisionId);
                        }
                    }

                    if (!empty($statement) && !empty($bound) && mysqli_stmt_execute($statement)) {
                        mysqli_stmt_close($statement);
                        if ($masterKey === 'teacher' && $newPhotoPath !== null && $id > 0) {
                            dutyMasterPageRemovePhoto($existing['photo_path']);
                        }
                        dutyMasterPageRedirect($pageName, 'Data ' . $dutyMaster['title'] . ' berhasil disimpan.', 'success');
                    }

                    error_log('Gagal menyimpan master duty roster: ' . (!empty($statement) ? mysqli_stmt_error($statement) : mysqli_error($connect)));
                    if (!empty($statement)) {
                        mysqli_stmt_close($statement);
                    }
                    if ($newPhotoPath !== null) {
                        dutyMasterPageRemovePhoto($newPhotoPath);
                    }
                    $errorMessage = 'Gagal menyimpan data. Periksa nama duplikat dan pilihan divisi.';
                }
            }
        }
    }
}

$divisions = [];
if ($dutyMaster['has_division']) {
    $divisionResult = mysqli_query($connect, 'SELECT id, name FROM duty_divisions ORDER BY name ASC');
    if ($divisionResult) {
        while ($division = mysqli_fetch_assoc($divisionResult)) {
            $divisions[] = $division;
        }
    } else {
        error_log('Gagal mengambil master divisi: ' . mysqli_error($connect));
        $errorMessage = 'Gagal memuat pilihan divisi.';
    }
}

$rows = [];
$result = mysqli_query($connect, $dutyMaster['query']);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
} else {
    error_log('Gagal mengambil master ' . $masterKey . ': ' . mysqli_error($connect));
    $errorMessage = 'Master belum tersedia atau gagal dimuat. Jalankan setup SQL duty-masters.sql.';
}

$modalId = 'dutyMasterModal';
$teacherDivisionCounts = [];
if ($masterKey === 'teacher') {
    foreach ($divisions as $division) {
        $teacherDivisionCounts[(string) $division['id']] = [
            'name' => $division['name'],
            'count' => 0
        ];
    }
    foreach ($rows as $row) {
        $divisionId = (string) $row['division_id'];
        if (!isset($teacherDivisionCounts[$divisionId])) {
            $teacherDivisionCounts[$divisionId] = [
                'name' => $row['division_name'],
                'count' => 0
            ];
        }
        $teacherDivisionCounts[$divisionId]['count']++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Master <?php echo dutyMasterPageEscape($dutyMaster['title']); ?></title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <?php if ($masterKey === 'teacher') { ?>
        <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <?php } ?>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Master <?php echo dutyMasterPageEscape($dutyMaster['title']); ?></h1>
                    <p class="mb-4">Kelola data <?php echo strtolower(dutyMasterPageEscape($dutyMaster['title'])); ?> untuk duty roster.</p>

                    <?php if ($successMessage !== '') { ?>
                        <div class="alert alert-success"><?php echo dutyMasterPageEscape($successMessage); ?></div>
                    <?php } ?>
                    <?php if ($errorMessage !== '') { ?>
                        <div class="alert alert-danger"><?php echo dutyMasterPageEscape($errorMessage); ?></div>
                    <?php } ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar <?php echo dutyMasterPageEscape($dutyMaster['title']); ?></h6>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#<?php echo $modalId; ?>" data-mode="add">
                                <i class="fas fa-plus"></i> Tambah <?php echo dutyMasterPageEscape($dutyMaster['title']); ?>
                            </button>
                        </div>
                        <div class="card-body">
                            <?php if ($masterKey === 'teacher') { ?>
                                <ul class="nav nav-pills mb-3" id="teacherDivisionTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button type="button" class="nav-link active" data-division-name="" aria-pressed="true">
                                            Semua Divisi <span class="badge badge-light ml-1"><?php echo count($rows); ?></span>
                                        </button>
                                    </li>
                                    <?php foreach ($teacherDivisionCounts as $divisionCount) { ?>
                                        <li class="nav-item" role="presentation">
                                            <button type="button" class="nav-link" data-division-name="<?php echo dutyMasterPageEscape($divisionCount['name']); ?>" aria-pressed="false">
                                                <?php echo dutyMasterPageEscape($divisionCount['name']); ?>
                                                <span class="badge badge-light ml-1"><?php echo (int) $divisionCount['count']; ?></span>
                                            </button>
                                        </li>
                                    <?php } ?>
                                </ul>
                            <?php } ?>
                            <div class="table-responsive">
                                <table class="table table-bordered" <?php if ($masterKey === 'teacher') { ?>id="teacherDataTable"<?php } ?> width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nama</th>
                                            <?php if ($dutyMaster['has_division']) { ?><th>Divisi</th><?php } ?>
                                            <?php if ($dutyMaster['has_photo']) { ?><th>Foto</th><?php } ?>
                                            <th style="width: 120px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rows as $row) { ?>
                                            <tr>
                                                <td><?php echo dutyMasterPageEscape($row['id']); ?></td>
                                                <td><?php echo dutyMasterPageEscape($row['name']); ?></td>
                                                <?php if ($dutyMaster['has_division']) { ?><td><?php echo dutyMasterPageEscape($row['division_name']); ?></td><?php } ?>
                                                <?php if ($dutyMaster['has_photo']) { ?>
                                                    <td>
                                                        <?php if (!empty($row['photo_path'])) { ?>
                                                            <img src="<?php echo dutyMasterPageEscape(dutyMasterPagePhotoSource($row['photo_path'])); ?>" alt="Foto <?php echo dutyMasterPageEscape($row['name']); ?>" style="width:48px;height:48px;object-fit:cover;border-radius:50%;">
                                                        <?php } else { ?>
                                                            <span class="text-muted">Tidak ada foto</span>
                                                        <?php } ?>
                                                    </td>
                                                <?php } ?>
                                                <td class="text-nowrap">
                                                    <button
                                                        type="button"
                                                        class="btn btn-success btn-sm"
                                                        data-toggle="modal"
                                                        data-target="#<?php echo $modalId; ?>"
                                                        data-mode="edit"
                                                        data-id="<?php echo dutyMasterPageEscape($row['id']); ?>"
                                                        data-name="<?php echo dutyMasterPageEscape($row['name']); ?>"
                                                        data-division-id="<?php echo dutyMasterPageEscape(isset($row['division_id']) ? $row['division_id'] : ''); ?>"
                                                        data-photo="<?php echo dutyMasterPageEscape(isset($row['photo_path']) ? $row['photo_path'] : ''); ?>"
                                                        aria-label="Edit <?php echo dutyMasterPageEscape($row['name']); ?>">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <form method="post" action="<?php echo dutyMasterPageEscape($pageName); ?>" class="d-inline" onsubmit="return confirm('Hapus data ini?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo dutyMasterPageEscape($row['id']); ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" aria-label="Hapus <?php echo dutyMasterPageEscape($row['name']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (count($rows) === 0 && $masterKey !== 'teacher') { ?>
                                            <tr><td colspan="<?php echo 3 + ($dutyMaster['has_division'] ? 1 : 0) + ($dutyMaster['has_photo'] ? 1 : 0); ?>" class="text-center text-muted">Belum ada data.</td></tr>
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
                    <div class="copyright text-center my-auto"><span>Copyright &copy; Your Website 2026</span></div>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="<?php echo $modalId; ?>" tabindex="-1" role="dialog" aria-labelledby="dutyMasterModalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="post" action="<?php echo dutyMasterPageEscape($pageName); ?>" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="dutyMasterModalTitle">Tambah <?php echo dutyMasterPageEscape($dutyMaster['title']); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="dutyMasterId" value="<?php echo dutyMasterPageEscape($formId); ?>">
                        <div class="form-group">
                            <label for="dutyMasterName">Nama</label>
                            <input type="text" class="form-control" id="dutyMasterName" name="name" maxlength="<?php echo $masterKey === 'division' ? '100' : '150'; ?>" value="<?php echo dutyMasterPageEscape($formName); ?>" required>
                        </div>
                        <?php if ($dutyMaster['has_division']) { ?>
                            <div class="form-group">
                                <label for="dutyMasterDivision">Divisi</label>
                                <select class="form-control" id="dutyMasterDivision" name="division_id" required>
                                    <option value="">Pilih divisi</option>
                                    <?php foreach ($divisions as $division) { ?>
                                        <option value="<?php echo dutyMasterPageEscape($division['id']); ?>" <?php if ((string) $formDivisionId === (string) $division['id']) echo 'selected'; ?>>
                                            <?php echo dutyMasterPageEscape($division['name']); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php } ?>
                        <?php if ($dutyMaster['has_photo']) { ?>
                            <div class="form-group">
                                <label for="dutyMasterPhoto">Foto Guru (opsional)</label>
                                <input type="file" class="form-control-file" id="dutyMasterPhoto" name="photo" accept="image/jpeg,image/png,image/webp">
                                <small class="form-text text-muted">Format JPG, PNG, atau WebP; maksimal 5 MB. Kosongkan jika tidak ada foto.</small>
                                <div id="currentPhoto" class="mt-2" <?php if ($currentPhotoPath === '') echo 'style="display:none"'; ?>>
                                    <img src="<?php echo $currentPhotoPath !== '' ? dutyMasterPageEscape(dutyMasterPagePhotoSource($currentPhotoPath)) : ''; ?>" alt="Foto guru saat ini" style="width:64px;height:64px;object-fit:cover;border-radius:50%;">
                                    <span class="small text-muted ml-2">Foto saat ini (tidak memilih file akan mempertahankan foto ini)</span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <?php if ($masterKey === 'teacher') { ?>
        <script src="vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <?php } ?>
    <script>
        <?php if ($masterKey === 'teacher') { ?>
        var teacherTable = $('#teacherDataTable').DataTable({
            pageLength: 10,
            lengthMenu: [[5, 10, 20, 50, 100], [5, 10, 20, 50, 100]],
            order: [[1, 'asc']],
            columnDefs: [{ targets: [3, 4], orderable: false }],
            language: {
                search: 'Cari:',
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ guru',
                infoEmpty: 'Belum ada data guru',
                infoFiltered: '(disaring dari _MAX_ total guru)',
                zeroRecords: 'Guru tidak ditemukan',
                emptyTable: 'Belum ada data guru',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Berikutnya',
                    previous: 'Sebelumnya'
                }
            }
        });

        $('#teacherDivisionTabs').on('click', '.nav-link', function() {
            var divisionName = $(this).attr('data-division-name');
            $('#teacherDivisionTabs .nav-link')
                .removeClass('active')
                .attr('aria-pressed', 'false');
            $(this).addClass('active').attr('aria-pressed', 'true');

            if (divisionName === '') {
                teacherTable.column(2).search('').draw();
                return;
            }

            var escapedDivision = $.fn.dataTable.util.escapeRegex(divisionName);
            teacherTable.column(2).search('^' + escapedDivision + '$', true, false).draw();
        });
        <?php } ?>

        $('#<?php echo $modalId; ?>').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var isEdit = button.data('mode') === 'edit';
            var modal = $(this);
            modal.find('.modal-title').text((isEdit ? 'Edit ' : 'Tambah ') + <?php echo json_encode($dutyMaster['title']); ?>);
            modal.find('#dutyMasterId').val(isEdit ? button.attr('data-id') : '');
            modal.find('#dutyMasterName').val(isEdit ? button.attr('data-name') : '');
            <?php if ($dutyMaster['has_division']) { ?>
            modal.find('#dutyMasterDivision').val(isEdit ? button.attr('data-division-id') : '');
            <?php } ?>
            <?php if ($dutyMaster['has_photo']) { ?>
            modal.find('#dutyMasterPhoto').val('');
            var photoPath = isEdit ? button.attr('data-photo') : '';
            modal.find('#currentPhoto').toggle(!!photoPath);
            var photoSource = photoPath && /^https?:\/\//i.test(photoPath) ? photoPath : '../' + (photoPath || '');
            modal.find('#currentPhoto img').attr('src', photoSource);
            <?php } ?>
        });
    </script>
    <?php if ($modalOpen) { ?>
        <script>$('#<?php echo $modalId; ?>').modal('show');</script>
    <?php } ?>
</body>
</html>
