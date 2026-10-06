<!DOCTYPE html>
<html lang="en">
<?php
    include("../connect.php");
    $id = isset($_GET['id']) ? $_GET['id'] : null;
    $d = [
        'id' => '', 'event_title' => '', 'event_date' => date('Y-m-d'), 
        'start_time' => '08:00', 'end_time' => '09:00', 
        'is_repeating' => 0, 'repeat_days' => '', 'is_active' => 1
    ];
    if ($id) {
        $sql = mysqli_query($connect, "SELECT * FROM schedules WHERE id = '$id'");
        if($row = mysqli_fetch_array($sql)) {
            $d = $row;
        }
    }
    $days = explode(',', $d['repeat_days']);
?>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Schedule</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include "sidebar.php"; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'header.php' ?>
                <div class="container-fluid">
                    <h1 class="h3 mb-4 text-gray-800"><?php echo $id ? 'Edit' : 'Add'; ?> Schedule Event</h1>
                    
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form method="post" action="update-schedule.php">
                                <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                
                                <div class="mb-3">
                                    <label for="event_title" class="form-label">Event Title</label>
                                    <input type="text" class="form-control" name="event_title" id="event_title" value="<?php echo $d['event_title']; ?>" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_time" class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="start_time" id="start_time" value="<?php echo date('H:i', strtotime($d['start_time'])); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_time" class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="end_time" id="end_time" value="<?php echo date('H:i', strtotime($d['end_time'])); ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3 border p-3 rounded">
                                    <label class="form-label font-weight-bold">Schedule Type</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="is_repeating" id="type_once" value="0" <?php if($d['is_repeating'] == 0) echo 'checked'; ?> onchange="toggleRepeat()">
                                        <label class="form-check-label" for="type_once">One-Time Event</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="is_repeating" id="type_repeat" value="1" <?php if($d['is_repeating'] == 1) echo 'checked'; ?> onchange="toggleRepeat()">
                                        <label class="form-check-label" for="type_repeat">Repeating Event</label>
                                    </div>
                                </div>

                                <div class="mb-3" id="field_date" style="display: none;">
                                    <label for="event_date" class="form-label">Event Date</label>
                                    <input type="date" class="form-control" name="event_date" id="event_date" value="<?php echo $d['event_date']; ?>">
                                </div>

                                <div class="mb-3" id="field_repeat" style="display: none;">
                                    <label class="form-label">Repeat Days</label>
                                    <div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="repeat_days[]" value="1" <?php if(in_array('1', $days)) echo 'checked'; ?>>
                                            <label class="form-check-label">Mon</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="repeat_days[]" value="2" <?php if(in_array('2', $days)) echo 'checked'; ?>>
                                            <label class="form-check-label">Tue</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="repeat_days[]" value="3" <?php if(in_array('3', $days)) echo 'checked'; ?>>
                                            <label class="form-check-label">Wed</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="repeat_days[]" value="4" <?php if(in_array('4', $days)) echo 'checked'; ?>>
                                            <label class="form-check-label">Thu</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="repeat_days[]" value="5" <?php if(in_array('5', $days)) echo 'checked'; ?>>
                                            <label class="form-check-label">Fri</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="repeat_days[]" value="6" <?php if(in_array('6', $days)) echo 'checked'; ?>>
                                            <label class="form-check-label">Sat</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="repeat_days[]" value="0" <?php if(in_array('0', $days)) echo 'checked'; ?>>
                                            <label class="form-check-label">Sun</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <select class="form-control" name="is_active" id="is_active">
                                        <option value="1" <?php if($d['is_active'] == 1) echo 'selected'; ?>>Active</option>
                                        <option value="0" <?php if($d['is_active'] == 0) echo 'selected'; ?>>Inactive</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-primary">Save Schedule</button>
                                <a href="schedules.php" class="btn btn-secondary">Cancel</a>
                            </form>
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
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script>
        function toggleRepeat() {
            var isRepeating = document.getElementById('type_repeat').checked;
            document.getElementById('field_date').style.display = isRepeating ? 'none' : 'block';
            document.getElementById('field_repeat').style.display = isRepeating ? 'block' : 'none';
        }
        document.addEventListener('DOMContentLoaded', toggleRepeat);
    </script>
</body>
</html>
