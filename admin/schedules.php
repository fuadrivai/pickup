<!DOCTYPE html>
<html lang="en">
<?php
    include("../connect.php");
    $sql = mysqli_query($connect, "SELECT * FROM schedules ORDER BY start_time ASC");
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Schedule Manager</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php' ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Timetable Manager</h1>
                    <p class="mb-4">Manage events that appear when the display playlist shows a "Timetable / Schedule" item.</p>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Scheduled Events</h6>
                            <a href="schedule-form.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add New Event</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Event Title</th>
                                            <th>Time</th>
                                            <th>Date / Repeat</th>
                                            <th>Active</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($d = mysqli_fetch_array($sql)){ ?>
                                        <tr>
                                            <td><?php echo $d['event_title']; ?></td>
                                            <td><?php echo date('H:i', strtotime($d['start_time'])) . ' - ' . date('H:i', strtotime($d['end_time'])); ?></td>
                                            <td>
                                                <?php if($d['is_repeating']) { 
                                                    $days = explode(',', $d['repeat_days']);
                                                    $dayNames = ['1'=>'Mon', '2'=>'Tue', '3'=>'Wed', '4'=>'Thu', '5'=>'Fri', '6'=>'Sat', '0'=>'Sun'];
                                                    $names = array_map(function($day) use ($dayNames) { return $dayNames[$day] ?? ''; }, $days);
                                                    echo "Repeats: " . implode(', ', $names);
                                                } else { 
                                                    echo "Once: " . date('d M Y', strtotime($d['event_date']));
                                                } ?>
                                            </td>
                                            <td>
                                                <?php if($d['is_active']) { ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php } else { ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php } ?>
                                            </td>
                                            <td>
                                                <a href="schedule-form.php?id=<?php echo $d['id'] ?>" class="btn btn-success btn-circle btn-sm">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <a href="delete-schedule.php?id=<?php echo $d['id'] ?>" class="btn btn-danger btn-circle btn-sm" onclick="return confirm('Are you sure?');">
                                                    <i class="fas fa-trash"></i>
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
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#dataTable').DataTable();
        });
    </script>
</body>
</html>
