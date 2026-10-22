<?php
include_once __DIR__ . '/../connect.php';

function dutyRosterEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function dutyRosterRedirect($message)
{
    header('Location: duty-rosters.php?message=' . rawurlencode($message));
    exit;
}

$errorMessage = '';
$successMessage = isset($_GET['message']) ? (string) $_GET['message'] : '';
$modalOpen = false;
$formId = '';
$formTitle = '';
$formYear = '';
$formDivisionId = '';
$formNotes = '';
$formActive = '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string) $_POST['action'] : '';
    $idValue = isset($_POST['id']) ? trim((string) $_POST['id']) : '';
    if (!in_array($action, ['save', 'delete'], true) || ($idValue !== '' && !ctype_digit($idValue))) {
        $errorMessage = 'Permintaan tidak valid.';
    } else {
        $id = $idValue === '' ? 0 : (int) $idValue;
        if ($action === 'delete') {
            if ($id < 1) {
                $errorMessage = 'Jadwal yang akan dihapus tidak valid.';
            } else {
                $statement = mysqli_prepare($connect, 'DELETE FROM duty_rosters WHERE id = ?');
                if ($statement && mysqli_stmt_bind_param($statement, 'i', $id) && mysqli_stmt_execute($statement)) {
                    mysqli_stmt_close($statement);
                    dutyRosterRedirect('Jadwal dan seluruh mapping di dalamnya berhasil dihapus.');
                }
                error_log('Gagal menghapus duty roster: ' . ($statement ? mysqli_stmt_error($statement) : mysqli_error($connect)));
                if ($statement) {
                    mysqli_stmt_close($statement);
                }
                $errorMessage = 'Gagal menghapus jadwal.';
            }
        } else {
            $modalOpen = true;
            $formId = $idValue;
            $formTitle = trim(isset($_POST['title']) ? (string) $_POST['title'] : '');
            $formYear = trim(isset($_POST['academic_year']) ? (string) $_POST['academic_year'] : '');
            $formDivisionId = isset($_POST['division_id']) ? trim((string) $_POST['division_id']) : '';
            $formNotes = trim(isset($_POST['notes']) ? (string) $_POST['notes'] : '');
            $formActive = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? '0' : '1';

            if ($formTitle === '' || strlen($formTitle) > 150) {
                $errorMessage = 'Nama jadwal wajib diisi (maksimal 150 karakter).';
            } elseif ($formYear !== '' && strlen($formYear) > 30) {
                $errorMessage = 'Tahun akademik maksimal 30 karakter.';
            } elseif (!ctype_digit($formDivisionId) || (int) $formDivisionId < 1) {
                $errorMessage = 'Divisi wajib dipilih.';
            } elseif (strlen($formNotes) > 65535) {
                $errorMessage = 'Catatan terlalu panjang.';
            } else {
                $divisionId = (int) $formDivisionId;
                $yearValue = $formYear === '' ? null : $formYear;
                $notesValue = $formNotes === '' ? null : $formNotes;
                $activeValue = (int) $formActive;
                if ($id > 0) {
                    $statement = mysqli_prepare($connect, 'UPDATE duty_rosters SET title = ?, academic_year = ?, division_id = ?, notes = ?, is_active = ? WHERE id = ?');
                    $bound = $statement && mysqli_stmt_bind_param($statement, 'ssisii', $formTitle, $yearValue, $divisionId, $notesValue, $activeValue, $id);
                } else {
                    $statement = mysqli_prepare($connect, 'INSERT INTO duty_rosters (title, academic_year, division_id, notes, is_active) VALUES (?, ?, ?, ?, ?)');
                    $bound = $statement && mysqli_stmt_bind_param($statement, 'ssisi', $formTitle, $yearValue, $divisionId, $notesValue, $activeValue);
                }

                if ($statement && $bound && mysqli_stmt_execute($statement)) {
                    $savedRosterId = $id > 0 ? $id : mysqli_insert_id($connect);
                    mysqli_stmt_close($statement);
                    if ($id > 0) {
                        dutyRosterRedirect('Jadwal berhasil disimpan.');
                    }
                    header('Location: duty-roster-mappings.php?roster_id=' . (int) $savedRosterId . '&message=' . rawurlencode('Jadwal berhasil dibuat. Lanjutkan dengan mengisi Event Run, lokasi, waktu, hari, dan guru.'));
                    exit;
                }
                error_log('Gagal menyimpan duty roster: ' . ($statement ? mysqli_stmt_error($statement) : mysqli_error($connect)));
                if ($statement) {
                    mysqli_stmt_close($statement);
                }
                $errorMessage = 'Gagal menyimpan jadwal. Periksa kembali data dan pilihan divisi.';
            }
        }
    }
}

$divisions = [];
$divisionResult = mysqli_query($connect, 'SELECT id, name FROM duty_divisions ORDER BY name ASC');
if ($divisionResult) {
    while ($division = mysqli_fetch_assoc($divisionResult)) {
        $divisions[] = $division;
    }
} else {
    error_log('Gagal mengambil divisi duty roster: ' . mysqli_error($connect));
    $errorMessage = 'Gagal memuat master divisi.';
}

$rosters = [];
$rosterResult = mysqli_query(
    $connect,
    'SELECT r.id, r.title, r.academic_year, r.division_id, r.notes, r.is_active, d.name AS division_name,
            (SELECT COUNT(*) FROM duty_roster_slots s WHERE s.roster_id = r.id) AS slot_count
     FROM duty_rosters r
     JOIN duty_divisions d ON d.id = r.division_id
     ORDER BY d.name, r.academic_year DESC, r.title'
);
if ($rosterResult) {
    while ($roster = mysqli_fetch_assoc($rosterResult)) {
        $rosters[] = $roster;
    }
} else {
    error_log('Gagal mengambil duty roster: ' . mysqli_error($connect));
    $errorMessage = 'Jalankan duty-roster-mapping-migration.sql untuk menyiapkan tabel jadwal.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Duty Roster</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'sidebar.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php'; ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Duty Roster</h1>
                    <p class="mb-4">Buat kerangka jadwal untuk satu divisi. Setelah disimpan, Anda langsung mengisi Event Run, lokasi, waktu, hari, dan guru yang bertugas.</p>

                    <?php if ($successMessage !== '') { ?><div class="alert alert-success"><?php echo dutyRosterEscape($successMessage); ?></div><?php } ?>
                    <?php if ($errorMessage !== '') { ?><div class="alert alert-danger"><?php echo dutyRosterEscape($errorMessage); ?></div><?php } ?>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar Jadwal</h6>
                            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#rosterModal" data-mode="add">
                                <i class="fas fa-plus"></i> Buat Jadwal Duty
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr><th>Nama Jadwal</th><th>Tahun Akademik</th><th>Divisi</th><th>Mapping</th><th>Status</th><th>Aksi</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rosters as $roster) { ?>
                                            <tr>
                                                <td><?php echo dutyRosterEscape($roster['title']); ?></td>
                                                <td><?php echo dutyRosterEscape($roster['academic_year'] ?: '-'); ?></td>
                                                <td><?php echo dutyRosterEscape($roster['division_name']); ?></td>
                                                <td><?php echo dutyRosterEscape($roster['slot_count']); ?> baris</td>
                                                <td>
                                                    <?php if ($roster['is_active']) { ?><span class="badge badge-success">Aktif</span><?php } else { ?><span class="badge badge-secondary">Nonaktif</span><?php } ?>
                                                </td>
                                                <td class="text-nowrap">
                                                    <a href="duty-roster-mappings.php?roster_id=<?php echo dutyRosterEscape($roster['id']); ?>" class="btn btn-primary btn-sm">
                                                        <i class="fas fa-calendar-alt"></i> Rincian Jadwal
                                                    </a>
                                                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#rosterModal" data-mode="edit"
                                                        data-id="<?php echo dutyRosterEscape($roster['id']); ?>"
                                                        data-title="<?php echo dutyRosterEscape($roster['title']); ?>"
                                                        data-year="<?php echo dutyRosterEscape($roster['academic_year']); ?>"
                                                        data-division-id="<?php echo dutyRosterEscape($roster['division_id']); ?>"
                                                        data-notes="<?php echo dutyRosterEscape($roster['notes']); ?>"
                                                        data-active="<?php echo dutyRosterEscape($roster['is_active']); ?>"
                                                        aria-label="Edit jadwal <?php echo dutyRosterEscape($roster['title']); ?>">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <form method="post" class="d-inline" onsubmit="return confirm('Hapus jadwal beserta seluruh mapping-nya?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo dutyRosterEscape($roster['id']); ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" aria-label="Hapus jadwal <?php echo dutyRosterEscape($roster['title']); ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php if (count($rosters) === 0) { ?><tr><td colspan="6" class="text-center text-muted">Belum ada jadwal. Buat jadwal per divisi untuk mulai melakukan mapping.</td></tr><?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white"><div class="container my-auto"><div class="copyright text-center my-auto"><span>Copyright &copy; Your Website 2026</span></div></div></footer>
        </div>
    </div>

    <div class="modal fade" id="rosterModal" tabindex="-1" role="dialog" aria-labelledby="rosterModalTitle" aria-hidden="true">
        <div class="modal-dialog" role="document"><div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="rosterModalTitle">Buat Jadwal Duty</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        Isi identitas jadwal di sini. Setelah disimpan, Anda langsung ke form rincian untuk memilih Event Run yang sudah tersedia, Place, waktu, hari, dan guru.
                    </div>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="rosterId" value="<?php echo dutyRosterEscape($formId); ?>">
                    <div class="form-group">
                        <label for="rosterTitle">Nama jadwal</label>
                        <input type="text" class="form-control" name="title" id="rosterTitle" maxlength="150" value="<?php echo dutyRosterEscape($formTitle); ?>" placeholder="Contoh: Teachers' Duty Rotation" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="rosterDivision">Divisi</label>
                            <select class="form-control" name="division_id" id="rosterDivision" required>
                                <option value="">Pilih divisi</option>
                                <?php foreach ($divisions as $division) { ?>
                                    <option value="<?php echo dutyRosterEscape($division['id']); ?>" <?php if ((string) $formDivisionId === (string) $division['id']) echo 'selected'; ?>><?php echo dutyRosterEscape($division['name']); ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="rosterYear">Tahun akademik (opsional)</label>
                            <input type="text" class="form-control" name="academic_year" id="rosterYear" maxlength="30" value="<?php echo dutyRosterEscape($formYear); ?>" placeholder="Contoh: 2026/2027">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="rosterNotes">Catatan (opsional)</label>
                        <textarea class="form-control" name="notes" id="rosterNotes" rows="2"><?php echo dutyRosterEscape($formNotes); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="rosterActive">Status</label>
                        <select class="form-control" name="is_active" id="rosterActive">
                            <option value="1" <?php if ($formActive === '1') echo 'selected'; ?>>Aktif</option>
                            <option value="0" <?php if ($formActive === '0') echo 'selected'; ?>>Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div></div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script>
        $('#rosterModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var isEdit = button.data('mode') === 'edit';
            var modal = $(this);
            modal.find('.modal-title').text(isEdit ? 'Edit Jadwal Duty' : 'Buat Jadwal Duty');
            modal.find('#rosterId').val(isEdit ? button.attr('data-id') : '');
            modal.find('#rosterTitle').val(isEdit ? button.attr('data-title') : '');
            modal.find('#rosterDivision').val(isEdit ? button.attr('data-division-id') : '');
            modal.find('#rosterYear').val(isEdit ? button.attr('data-year') : '');
            modal.find('#rosterNotes').val(isEdit ? button.attr('data-notes') : '');
            modal.find('#rosterActive').val(isEdit ? button.attr('data-active') : '1');
        });
    </script>
    <?php if ($modalOpen) { ?><script>$('#rosterModal').modal('show');</script><?php } ?>
</body>
</html>
