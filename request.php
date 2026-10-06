<?php
include(__DIR__ . '/connect.php');

if (isset($_GET['ajax']) && $_GET['ajax'] === 'grades') {
    header('Content-Type: application/json; charset=utf-8');

    $gradeQuery = mysqli_query(
        $connect,
        "SELECT DISTINCT grade
         FROM homeroom
         WHERE grade IS NOT NULL AND grade <> ''
         ORDER BY grade DESC"
    );
    if (!$gradeQuery) {
        error_log('Gagal mengambil grade untuk halaman request pickup: ' . mysqli_error($connect));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memuat daftar kelas.']);
        exit;
    }

    $grades = [];
    while ($gradeRow = mysqli_fetch_assoc($gradeQuery)) {
        $grades[] = $gradeRow['grade'];
    }

    echo json_encode(['success' => true, 'grades' => $grades]);
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'students') {
    header('Content-Type: application/json; charset=utf-8');

    $grade = isset($_GET['grade']) ? trim((string) $_GET['grade']) : '';
    $query = "SELECT id, student_name, grade FROM student";
    if ($grade !== '') {
        $query .= " WHERE grade = ?";
    }
    $query .= " ORDER BY grade DESC, student_name ASC";

    $statement = mysqli_prepare($connect, $query);
    if (!$statement) {
        error_log('Gagal menyiapkan daftar siswa untuk pickup: ' . mysqli_error($connect));
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memuat daftar siswa.']);
        exit;
    }

    if (
        ($grade !== '' && !mysqli_stmt_bind_param($statement, 's', $grade)) ||
        !mysqli_stmt_execute($statement) ||
        !mysqli_stmt_bind_result($statement, $studentId, $studentName, $studentGrade)
    ) {
        error_log('Gagal mengambil daftar siswa untuk pickup: ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memuat daftar siswa.']);
        exit;
    }

    $students = [];
    while (($fetchResult = mysqli_stmt_fetch($statement)) === true) {
        $students[] = [
            'id' => $studentId,
            'name' => $studentName,
            'grade' => $studentGrade
        ];
    }
    if ($fetchResult === false) {
        error_log('Gagal membaca daftar siswa untuk pickup: ' . mysqli_stmt_error($statement));
        mysqli_stmt_close($statement);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Gagal memuat daftar siswa.']);
        exit;
    }

    mysqli_stmt_close($statement);
    echo json_encode(['success' => true, 'students' => $students]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#c72558">
    <meta name="description" content="Halaman permintaan pickup siswa Sekolah Mutiara Harapan Bintaro.">
    <title>Permintaan Pickup | Mutiara Harapan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    <link href="admin/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --brand-magenta: #c72558;
            --brand-magenta-dark: #a31d47;
            --brand-green-light: #c1d87e;
            --brand-green-dark: #8bb93e;
            --bg-color: #f8f9fa;
            --ink: #333;
            --muted: #666;
            --border: #eee;
            --shadow: 0 10px 30px rgba(0, 0, 0, .05);
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background-color: var(--bg-color);
            color: var(--ink);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        .site-header {
            background: linear-gradient(135deg, var(--brand-magenta) 0%, var(--brand-magenta-dark) 100%);
            color: #fff;
            padding: 1rem 1.5rem;
        }

        .header-inner,
        .page-shell,
        .footer-inner {
            width: min(1120px, 100%);
            margin: 0 auto;
        }

        .header-inner {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .school-logo {
            width: 64px;
            height: 64px;
            padding: .35rem;
            object-fit: contain;
            background: #fff;
            border-radius: 14px;
        }

        .school-name {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: .02em;
        }

        .school-subtitle {
            margin: .3rem 0 0;
            color: rgba(255, 255, 255, .78);
            font-size: .9rem;
        }

        .page-shell {
            padding: 2.5rem 1.25rem 3rem;
        }

        .intro {
            margin-bottom: 1.5rem;
        }

        .eyebrow {
            margin: 0 0 .6rem;
            color: var(--brand-magenta);
            font-size: .78rem;
            font-weight: 800;
            letter-spacing: .13em;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            color: var(--brand-magenta-dark);
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            letter-spacing: -.035em;
        }

        .intro-copy {
            max-width: 650px;
            margin: .7rem 0 0;
            color: var(--muted);
            line-height: 1.65;
        }

        .panel {
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 20px;
            border-top: 5px solid var(--brand-green-dark);
            background: #fff;
            box-shadow: var(--shadow);
        }

        .panel-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        .panel-heading h2 {
            margin: 0;
            color: var(--brand-magenta-dark);
            font-size: 1.1rem;
        }

        .student-count {
            color: var(--muted);
            font-size: .9rem;
        }

        .filters {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
            align-items: end;
            gap: 1.25rem;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            background: #fcfcfc;
        }

        .filters .field {
            min-width: 0;
            margin: 0;
        }

        .filters > .col-md-4,
        .filters > .col-md-8 {
            width: auto;
            max-width: none;
            padding: 0;
        }

        .field label {
            display: block;
            margin-bottom: .45rem;
            color: var(--brand-magenta-dark);
            font-size: .85rem;
            font-weight: 700;
        }

        .field input,
        .field select {
            width: 100%;
            min-height: 46px;
            padding: .7rem .85rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            color: var(--ink);
            font: inherit;
            outline: none;
        }

        .field input:focus,
        .field select:focus {
            border-color: var(--brand-magenta);
            box-shadow: 0 0 0 3px rgba(199, 37, 88, .12);
        }

        .filters .select2-container {
            width: 100% !important;
        }

        .filters .select2-container .select2-selection--single {
            height: 46px;
            border: 1px solid #ddd;
            border-radius: 10px;
        }

        .filters .select2-container .select2-selection--single .select2-selection__rendered {
            padding: .55rem 2.25rem .55rem .85rem;
            color: var(--ink);
            line-height: 34px;
        }

        .filters .select2-container .select2-selection--single .select2-selection__arrow {
            top: 9px;
            right: 8px;
        }

        .filters .select2-container--focus .select2-selection--single {
            border-color: var(--brand-magenta);
            box-shadow: 0 0 0 3px rgba(199, 37, 88, .12);
        }

        .table-wrap {
            overflow-x: auto;
            padding: 1rem 1.5rem 1.25rem;
        }

        table.dataTable {
            width: 100%;
            margin: 0 !important;
            text-align: left;
        }

        table.dataTable thead th {
            border-bottom: 0;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: .65rem;
            color: var(--muted);
            font-size: .88rem;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: .35rem;
        }

        .dataTables_wrapper .dataTables_paginate ul.pagination {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: .35rem;
            margin: 0;
            padding-left: 0;
            list-style: none;
        }

        .dataTables_wrapper .dataTables_paginate .page-item {
            margin: 0;
        }

        .dataTables_wrapper .dataTables_paginate .page-link {
            min-width: 36px;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--brand-magenta);
            line-height: 1.35;
            text-align: center;
            text-decoration: none;
        }

        .dataTables_wrapper .dataTables_paginate .page-item.active .page-link {
            border-color: var(--brand-magenta);
            background: var(--brand-magenta);
            color: #fff;
        }

        .dataTables_wrapper .dataTables_paginate .page-item.disabled .page-link {
            color: #999;
        }

        .dataTables_wrapper .dataTables_paginate .page-link:focus {
            box-shadow: 0 0 0 3px rgba(199, 37, 88, .12);
        }

        th,
        td {
            padding: .95rem 1.5rem;
            border-bottom: 1px solid #eee;
        }

        th {
            background: var(--brand-green-dark);
            color: #fff;
            font-size: .76rem;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        td {
            font-size: .94rem;
        }

        tbody tr:nth-child(even) {
            background-color: #fcfcfc;
        }

        tbody tr:hover {
            background: #f5f8ed;
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .grade-badge {
            display: inline-block;
            padding: .3rem .65rem;
            border-radius: 999px;
            background: var(--brand-green-light);
            color: #354b19;
            font-size: .82rem;
            font-weight: 700;
        }

        .button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: .45rem;
            min-height: 38px;
            padding: .55rem .9rem;
            border: 0;
            border-radius: 9px;
            background: var(--brand-magenta);
            color: #fff;
            cursor: pointer;
            font: inherit;
            font-size: .86rem;
            font-weight: 700;
            text-decoration: none;
            transition: background .15s ease, transform .15s ease;
        }

        .button:hover {
            background: var(--brand-magenta-dark);
            transform: translateY(-1px);
        }

        .button:disabled {
            cursor: wait;
            opacity: .65;
            transform: none;
        }

        .button-secondary {
            border: 1px solid var(--border);
            background: #fff;
            color: var(--ink);
        }

        .button-secondary:hover {
            background: #f8f9fa;
            color: var(--brand-magenta-dark);
        }

        .empty-state {
            padding: 2.5rem 1rem;
            color: var(--muted);
            text-align: center;
        }

        .notice {
            display: none;
            margin: 1rem 1.5rem 0;
            padding: .85rem 1rem;
            border-radius: 10px;
            font-size: .92rem;
            line-height: 1.5;
        }

        .notice.is-visible {
            display: block;
        }

        .notice-success {
            background: #eaf6ef;
            color: #1c6339;
        }

        .notice-error {
            background: #fbeaec;
            color: #8a2034;
        }

        .notice-warning {
            background: #fff4dd;
            color: #79530a;
        }

        .request-modal-backdrop {
            position: fixed;
            z-index: 20;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(35, 15, 21, .58);
        }

        .request-modal-backdrop.is-open {
            display: flex;
        }

        .request-dialog {
            width: min(480px, 100%);
            overflow: hidden;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 24px 80px rgba(0, 0, 0, .25);
        }

        .request-dialog-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.25rem 1.4rem;
            background: var(--brand-magenta);
            color: #fff;
        }

        .request-dialog-header h2 {
            margin: 0;
            font-size: 1.15rem;
        }

        .request-dialog-header p {
            margin: .35rem 0 0;
            color: rgba(255, 255, 255, .82);
            font-size: .9rem;
        }

        .icon-button {
            border: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
            font-size: 1.5rem;
            line-height: 1;
        }

        .request-dialog-body {
            padding: 1.35rem 1.4rem;
        }

        .request-dialog-body .field {
            margin-bottom: 1rem;
        }

        .request-dialog-footer {
            display: flex;
            justify-content: flex-end;
            gap: .65rem;
            padding: 1rem 1.4rem 1.3rem;
            border-top: 1px solid var(--border);
        }

        .site-footer {
            padding: 1.2rem;
            color: var(--muted);
            font-size: .84rem;
            text-align: center;
        }

        @media (max-width: 640px) {
        .site-header {
            padding: .8rem 1rem;
        }

        .school-logo {
            width: 52px;
            height: 52px;
        }

        .school-name {
            font-size: 1rem;
        }

        .school-subtitle {
            font-size: .8rem;
        }

        .page-shell {
            padding: 1.7rem .8rem 2.2rem;
        }

        .filters {
            grid-template-columns: 1fr;
            padding: 1rem;
        }

        .filters .field {
            width: 100%;
            max-width: 100%;
            padding: 0;
        }

        .table-wrap {
            padding: .75rem;
        }

        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            float: none;
            width: 100%;
            text-align: center;
        }

        .dataTables_wrapper .dataTables_paginate ul.pagination {
            justify-content: center;
        }

        .panel-heading {
            padding: 1rem;
        }

        th,
        td {
            padding: .8rem 1rem;
        }
        }
    </style>
</head>

<body>
    <header class="site-header">
        <div class="header-inner">
            <img class="school-logo" src="https://mutiaraharapan.sch.id/wp-content/uploads/2017/01/logo-bintaro.png"
                alt="Logo Sekolah Mutiara Harapan Bintaro">
            <div>
                <p class="school-name">Mutiara Harapan Islamic School</p>
                <p class="school-subtitle">Bintaro · Student Pickup</p>
            </div>
        </div>
    </header>

    <main class="page-shell">
        <section class="intro">
            <p class="eyebrow">Layanan penjemputan</p>
            <h1>Permintaan Pickup Siswa</h1>
            <p class="intro-copy">Pilih nama siswa untuk mengirim permintaan pickup. Pastikan nama dan nomor telepon
                orang tua diisi dengan benar.</p>
        </section>

        <section class="panel" aria-labelledby="studentListTitle">
            <div class="panel-heading">
                <h2 id="studentListTitle">Daftar Siswa</h2>
                <span class="student-count" id="studentCount" aria-live="polite">Memuat siswa...</span>
            </div>
            <div class="filters">
                <div class="field col-md-4">
                    <label for="gradeFilter">Filter kelas</label>
                    <select id="gradeFilter">
                        <option value="">Semua kelas</option>
                    </select>
                </div>
                <div class="field col-md-8">
                    <label for="studentSearch">Cari nama siswa</label>
                    <input type="search" id="studentSearch" placeholder="Ketik nama siswa..." autocomplete="off">
                </div>
            </div>
            <div id="pageNotice" class="notice" role="status" aria-live="polite"></div>
            <div class="table-wrap">
                <table id="studentTable" class="display">
                    <thead>
                        <tr>
                            <th scope="col">Nama Siswa</th>
                            <th scope="col">Kelas</th>
                            <th scope="col">Permintaan</th>
                        </tr>
                    </thead>
                    <tbody id="studentRows"></tbody>
                </table>
            </div>
        </section>
        <footer class="site-footer">Sekolah Mutiara Harapan Bintaro</footer>
    </main>

    <div class="request-modal-backdrop" id="requestModal" role="dialog" aria-modal="true" aria-labelledby="requestModalTitle"
        aria-hidden="true">
        <form class="request-dialog" id="requestForm">
            <div class="request-dialog-header">
                <div>
                    <h2 id="requestModalTitle">Konfirmasi Pickup</h2>
                    <p><span id="selectedStudentName"></span> · Kelas <span id="selectedStudentGrade"></span></p>
                </div>
                <button type="button" class="icon-button" data-close-modal aria-label="Tutup">&times;</button>
            </div>
            <div class="request-dialog-body">
                <input type="hidden" id="selectedStudentId" name="student_id">
                <div class="field">
                    <label for="parentName">Nama orang tua</label>
                    <input type="text" id="parentName" name="parent_name" maxlength="255" required>
                </div>
                <div class="field">
                    <label for="parentPhone">Nomor telepon orang tua</label>
                    <input type="tel" id="parentPhone" name="parent_phone" maxlength="50" autocomplete="tel" required>
                </div>
                <div id="formNotice" class="notice" role="alert" aria-live="assertive"></div>
            </div>
            <div class="request-dialog-footer">
                <button type="button" class="button button-secondary" data-close-modal>Batalkan</button>
                <button type="submit" class="button" id="submitRequest">Kirim Permintaan</button>
            </div>
        </form>
    </div>

    <script src="admin/vendor/jquery/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <script src="admin/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="admin/vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        (function () {
            var students = [];
            var gradeFilter = document.getElementById('gradeFilter');
            var studentSearch = document.getElementById('studentSearch');
            var studentRows = document.getElementById('studentRows');
            var studentCount = document.getElementById('studentCount');
            var requestModal = document.getElementById('requestModal');
            var requestForm = document.getElementById('requestForm');
            var submitButton = document.getElementById('submitRequest');
            var pageNotice = document.getElementById('pageNotice');
            var formNotice = document.getElementById('formNotice');
            var activeStudentRequest = null;
            var studentTable = $('#studentTable').DataTable({
                dom: 'rtip',
                pageLength: 10,
                order: [[0, 'asc']],
                language: {
                    emptyTable: 'Belum ada data siswa.',
                    info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ siswa',
                    infoEmpty: 'Tidak ada siswa untuk ditampilkan',
                    infoFiltered: '(difilter dari _MAX_ siswa)',
                    lengthMenu: 'Tampilkan _MENU_ siswa',
                    loadingRecords: 'Memuat...',
                    paginate: {
                        first: 'Awal',
                        last: 'Akhir',
                        next: 'Berikutnya',
                        previous: 'Sebelumnya'
                    },
                    zeroRecords: 'Siswa tidak ditemukan.'
                }
            });

            function setNotice(element, message, type) {
                element.textContent = message;
                element.className = 'notice is-visible notice-' + type;
            }

            function clearNotice(element) {
                element.textContent = '';
                element.className = 'notice';
            }

            function escapeHtml(value) {
                return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    } [character];
                });
            }

            function renderStudents() {
                studentTable.clear();
                if (students.length > 0) {
                    studentTable.rows.add(students.map(function (student) {
                        return [
                            escapeHtml(student.name),
                            '<span class="grade-badge">' + escapeHtml(student.grade) + '</span>',
                            '<button type="button" class="button request-button" data-student-id="' +
                                escapeHtml(student.id) + '" data-student-name="' + escapeHtml(student.name) +
                                '" data-student-grade="' + escapeHtml(student.grade) + '">Ajukan Pickup</button>'
                        ];
                    }));
                }
                studentTable.column(0).search(studentSearch.value.trim()).draw();
            }

            studentTable.on('draw', function () {
                studentCount.textContent = studentTable.rows({ search: 'applied' }).count() + ' siswa';
            });

            function loadGrades() {
                return fetch('request.php?ajax=grades', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Gagal memuat daftar kelas.');
                        }
                        return response.json();
                    })
                    .then(function (result) {
                        if (!result.success) {
                            throw new Error(result.message || 'Gagal memuat daftar kelas.');
                        }
                        result.grades.forEach(function (grade) {
                            var option = document.createElement('option');
                            option.value = grade;
                            option.textContent = grade;
                            gradeFilter.appendChild(option);
                        });
                        $('#gradeFilter').trigger('change.select2');
                    });
            }

            function loadStudents(grade) {
                if (activeStudentRequest) {
                    activeStudentRequest.abort();
                }
                activeStudentRequest = new AbortController();
                studentCount.textContent = 'Memuat siswa...';

                var url = new URL('request.php', window.location.href);
                url.searchParams.set('ajax', 'students');
                if (grade) {
                    url.searchParams.set('grade', grade);
                }

                return fetch(url, {
                    signal: activeStudentRequest.signal,
                    headers: {
                        'Accept': 'application/json'
                    }
                }).then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (result) {
                            throw new Error(result.message || 'Gagal memuat daftar siswa.');
                        });
                    }
                    return response.json();
                }).then(function (result) {
                    if (!result.success) {
                        throw new Error(result.message || 'Gagal memuat daftar siswa.');
                    }
                    students = result.students;
                    clearNotice(pageNotice);
                    renderStudents();
                }).catch(function (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    students = [];
                    studentTable.clear().draw();
                    studentCount.textContent = 'Data tidak tersedia';
                    setNotice(pageNotice, error.message || 'Gagal memuat daftar siswa.', 'error');
                });
            }

            function openRequest(student) {
                document.getElementById('selectedStudentId').value = student.id;
                document.getElementById('selectedStudentName').textContent = student.name;
                document.getElementById('selectedStudentGrade').textContent = student.grade;
                document.getElementById('parentName').value = '';
                document.getElementById('parentPhone').value = '';
                clearNotice(formNotice);
                requestModal.classList.add('is-open');
                requestModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                document.getElementById('parentName').focus();
            }

            function closeRequest() {
                if (submitButton.disabled) {
                    return;
                }
                requestModal.classList.remove('is-open');
                requestModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            studentRows.addEventListener('click', function (event) {
                var button = event.target.closest('.request-button');
                if (!button) {
                    return;
                }
                openRequest({
                    id: button.getAttribute('data-student-id'),
                    name: button.getAttribute('data-student-name'),
                    grade: button.getAttribute('data-student-grade')
                });
            });

            studentSearch.addEventListener('input', function () {
                studentTable.column(0).search(this.value.trim()).draw();
            });
            $('#gradeFilter').select2({
                width: '100%',
                placeholder: 'Semua kelas',
                allowClear: true
            });
            $('#gradeFilter').on('change', function () {
                loadStudents($(this).val() || '');
            });

            document.querySelectorAll('[data-close-modal]').forEach(function (button) {
                button.addEventListener('click', closeRequest);
            });

            requestModal.addEventListener('click', function (event) {
                if (event.target === requestModal) {
                    closeRequest();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && requestModal.classList.contains('is-open')) {
                    closeRequest();
                }
            });

            requestForm.addEventListener('submit', function (event) {
                event.preventDefault();
                clearNotice(formNotice);
                submitButton.disabled = true;
                submitButton.textContent = 'Mengirim...';

                fetch('admin/save-pickup.php', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: new URLSearchParams(new FormData(requestForm)).toString()
                }).then(function (response) {
                    return response.json().then(function (result) {
                        if (!response.ok || !result.success) {
                            throw new Error(result.message ||
                                'Permintaan pickup gagal disimpan.');
                        }
                        return result;
                    });
                }).then(function (result) {
                    requestModal.classList.remove('is-open');
                    requestModal.setAttribute('aria-hidden', 'true');
                    document.body.style.overflow = '';

                    var message = 'Permintaan pickup berhasil dikirim.';
                    var type = 'success';
                    if (!result.pickup || !result.pickup.display_updated) {
                        message += ' Namun status layar pickup belum dapat dipastikan.';
                        type = 'warning';
                    } else if (!result.pickup.whatsapp_sent) {
                        message += ' ' + (result.pickup.error ||
                            'Notifikasi WhatsApp tidak terkirim.');
                        type = 'warning';
                    } else {
                        message += ' Homeroom telah diberi notifikasi.';
                    }
                    setNotice(pageNotice, message, type);
                    requestForm.reset();
                }).catch(function (error) {
                    setNotice(formNotice, error.message || 'Permintaan pickup gagal disimpan.',
                        'error');
                }).finally(function () {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Kirim Permintaan';
                });
            });

            loadGrades().then(function () {
                return loadStudents('');
            }).catch(function (error) {
                studentTable.clear().draw();
                studentCount.textContent = 'Data tidak tersedia';
                setNotice(pageNotice, error.message || 'Gagal memuat data kelas.', 'error');
            });
        }());
    </script>
</body>

</html>