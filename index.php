<?php
include 'connect.php';

// Fetch the playlist
$playlist_sql = mysqli_query($connect, "SELECT * FROM display_contents WHERE is_active=1 ORDER BY sequence ASC");
$playlist = [];
if ($playlist_sql) {
    while($row = mysqli_fetch_assoc($playlist_sql)) {
        $playlist[] = $row;
    }
}

$refreshInterval = 101 * 60; // Convert minutes to seconds
header("Refresh: $refreshInterval");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Pickup Display</title>
    <meta http-equiv='cache-control' content='no-cache'>
    <meta http-equiv='expires' content='0'>
    <meta http-equiv='pragma' content='no-cache'>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <style>
        :root {
            --brand-magenta: #c72558;
            --brand-green-light: #c1d87e;
            --brand-green-dark: #8bb93e;
            --bg-color: #f8f9fa;
        }
        
        body {
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            overflow: hidden;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, var(--brand-magenta) 0%, #a31d47 100%);
            color: white;
            padding: 15px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }
        .header h1 {
            margin: 0;
            font-weight: 800;
            font-size: 28px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .clock {
            font-size: 24px;
            font-weight: 600;
        }

        .main-container {
            display: flex;
            flex: 1;
            padding: 20px;
            gap: 20px;
            height: calc(100vh - 70px);
            transition: all 0.4s ease-in-out;
        }

        .content-col {
            flex: 1;
            width: 100%;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            border-top: 5px solid var(--brand-green-dark);
            display: flex;
            flex-direction: column;
            position: relative;
            transition: flex 0.4s ease-in-out;
        }

        /* Split view when student pickups exist */
        .main-container.has-pickup .content-col {
            flex: 1.5;
        }
        
        #dynamic-content {
            flex: 1;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: opacity 0.5s ease-in-out;
        }
        
        #dynamic-content iframe {
            width: 100%;
            height: 100%;
            border-radius: 10px;
        }
        #dynamic-content img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 10px;
        }

        /* Timetable Styles */
        .timetable-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .timetable-title {
            color: var(--brand-magenta);
            font-weight: 800;
            font-size: 32px;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
        }
        .timetable {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .timetable th {
            background: var(--brand-green-dark);
            color: white;
            padding: 15px 20px;
            text-align: left;
            font-size: 22px;
        }
        .timetable td {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            font-size: 24px;
            color: #333;
        }
        .timetable tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .timetable tr:last-child td {
            border-bottom: none;
        }
        .time-col {
            font-weight: 800;
            color: var(--brand-magenta);
            width: 35%;
        }

        /* Right Column - Pickup List */
        .pickup-col {
            flex: 1;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-top: 5px solid var(--brand-magenta);
            display: none; /* Hidden by default when no student pickup */
            flex-direction: column;
            overflow: hidden;
            animation: slideInRight 0.35s ease-out;
        }

        .main-container.has-pickup .pickup-col {
            display: flex;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(25px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        .pickup-header {
            background-color: var(--brand-magenta);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 800;
            font-size: 24px;
            letter-spacing: 1px;
        }
        .table-responsive {
            flex: 1;
            overflow: hidden;
            position: relative;
        }
        .pickup-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
            padding: 0 15px;
            position: absolute;
            top: 0;
            left: 0;
        }
        .pickup-table tr {
            background: #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-radius: 8px;
            transition: transform 0.2s;
        }
        .pickup-table td {
            padding: 20px 15px;
            font-size: 16px;
            font-weight: 600;
            color: #333;
            border: none;
        }
        .pickup-table td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
            border-left: 5px solid var(--brand-green-light);
        }
        .pickup-table td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            text-align: right;
            color: var(--brand-green-dark);
        }

        .scanner-form {
            position: fixed;
            top: -1000px;
            left: -1000px;
            opacity: 0;
            pointer-events: none;
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>SCHOOL INFORMATION</h1>
        <div class="clock" id="clock">00:00:00</div>
    </div>

    <div class="main-container">
        
        <!-- Left Column: Dynamic Content Playlist -->
        <div class="content-col">
            <div id="dynamic-content">
                <!-- Content injected here via JS -->
            </div>
        </div>

        <!-- Right Column: Pickup List -->
        <div class="pickup-col">
            <div class="pickup-header">
                STUDENT PICKUP
            </div>
            <div class="table-responsive" id="scroll-container">
                <table class="pickup-table" id="pickup-table">
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div class="scanner-form">
        <form method="post" class="form-user" onsubmit="event.preventDefault(); return false;">
            <input id="myInput" type="text" name="nama" autocomplete="off" autofocus>
            <button id="myBtn" type="button" class="tombol-simpan">Simpan</button>
        </form>
    </div>

    <script type="text/javascript">
        // Clock functionality
        function updateClock() {
            var now = new Date();
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = hours + ':' + minutes + ':' + seconds;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Playlist Carousel Logic
        var playlist = <?php echo json_encode($playlist); ?>;
        var currentItemIndex = -1;
        var displayContainer = document.getElementById('dynamic-content');

        // Automatically check for playlist updates every 1 minute
        setInterval(function() {
            $.getJSON("get_playlist.php", function(response) {
                if (response.status === 'success') {
                    playlist = response.data;
                    // If an item was deleted and index is out of bounds, reset it
                    if (currentItemIndex >= playlist.length) {
                        currentItemIndex = -1;
                    }
                }
            });
        }, 60000);

        function playNextItem() {
            if (playlist.length === 0) {
                displayContainer.innerHTML = '<h2>No active content to display</h2>';
                return;
            }

            currentItemIndex++;
            if (currentItemIndex >= playlist.length) {
                currentItemIndex = 0; // Loop back to start
            }

            var item = playlist[currentItemIndex];
            
            // Fade out
            displayContainer.style.opacity = 0;
            
            setTimeout(function() {
                // Change content
                if (item.display_type === 'video' || item.display_type === 'link') {
                    var url = item.content_link;
                    if(url.indexOf('youtube.com') !== -1 || url.indexOf('youtu.be') !== -1) {
                        url += (url.indexOf('?') !== -1) ? "&autoplay=1&mute=0" : "?autoplay=1&mute=0";
                    }
                    displayContainer.innerHTML = '<iframe src="' + url + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                } 
                else if (item.display_type === 'slide') {
                    displayContainer.innerHTML = '<img src="' + item.content_image + '" alt="Slide">';
                }
                else if (item.display_type === 'schedule') {
                    renderTimetable(item.title);
                }
                else if (item.display_type === 'api_events') {
                    renderApiEvents(item.title);
                }
                else if (item.display_type === 'embed_code') {
                    // Use srcdoc to safely isolate and execute embedded scripts (like Instagram's embed.js)
                    var escapedHtml = item.content_html.replace(/"/g, '&quot;');
                    displayContainer.innerHTML = '<iframe srcdoc="' + escapedHtml + '" frameborder="0" style="width:100%; height:100%; background:white; border-radius:10px;"></iframe>';
                }

                // Fade in
                displayContainer.style.opacity = 1;

                // Schedule next item
                setTimeout(playNextItem, item.duration_seconds * 1000);
            }, 500); // 500ms fade transition
        }

        function renderTimetable(title) {
            displayContainer.innerHTML = '<div class="timetable-container"><div class="timetable-title">' + title + '</div><table class="timetable"><thead><tr><th>Time</th><th>Description</th></tr></thead><tbody id="timetable-body"><tr><td colspan="2" style="text-align:center;">Loading schedule...</td></tr></tbody></table></div>';
            
            $.getJSON("get_schedules.php", function(response) {
                var tbody = $("#timetable-body");
                tbody.empty();
                if (response.status === 'success' && response.data.length > 0) {
                    $.each(response.data, function(index, event) {
                        var timeStr = event.start_time_formatted + ' - ' + event.end_time_formatted;
                        tbody.append('<tr><td class="time-col">' + timeStr + '</td><td>' + event.event_title + '</td></tr>');
                    });
                } else {
                    tbody.append('<tr><td colspan="2" style="text-align:center; padding: 30px; color: #888;">No events scheduled for today.</td></tr>');
                }
            });
        }
        
        function renderApiEvents(title) {
            displayContainer.innerHTML = '<div class="timetable-container"><div class="timetable-title">' + title + '</div><table class="timetable"><thead><tr><th>Date</th><th>Event</th></tr></thead><tbody id="api-events-body"><tr><td colspan="2" style="text-align:center;">Loading events...</td></tr></tbody></table></div>';
            
            $.getJSON("get_api_events.php", function(response) {
                var tbody = $("#api-events-body");
                tbody.empty();
                if (response.status === 'success' && response.data.length > 0) {
                    $.each(response.data, function(index, event) {
                        var startDate = new Date(event.starttime).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'});
                        var color = event.color || 'var(--brand-magenta)';
                        tbody.append('<tr><td class="time-col" style="color: ' + color + ';">' + startDate + '</td><td>' + event.subject + '</td></tr>');
                    });
                } else {
                    tbody.append('<tr><td colspan="2" style="text-align:center; padding: 30px; color: #888;">No school events happening tomorrow.</td></tr>');
                }
            });
        }

        // Start Playlist
        playNextItem();

        // Pickup List Logic
        var scrollContainer = $('#scroll-container');
        var table = $('#pickup-table');
        var isScrolling = true;
        var scrollAnimationId = null;
        var hasActivePickup = false;

        function startScroll() {
            if (scrollAnimationId) {
                cancelAnimationFrame(scrollAnimationId);
                scrollAnimationId = null;
            }

            if (!isScrolling || !hasActivePickup) {
                return;
            }

            var currentTop = parseInt(table.css('top')) || 0;
            var containerHeight = scrollContainer.height();
            var tableHeight = table.height();

            if (tableHeight > containerHeight && containerHeight > 0) {
                currentTop -= 1;
                if (Math.abs(currentTop) >= tableHeight) {
                    currentTop = containerHeight;
                }
                table.css('top', currentTop + 'px');
            } else {
                table.css('top', '0px');
            }
            scrollAnimationId = requestAnimationFrame(startScroll);
        }

        scrollContainer.hover(
            function() { 
                isScrolling = false; 
            },
            function() { 
                if (hasActivePickup) {
                    isScrolling = true; 
                    startScroll(); 
                }
            }
        );

        function updatePickupDisplay(students) {
            var tbody = $("#pickup-table tbody");
            var mainContainer = $(".main-container");

            if (students && students.length > 0) {
                hasActivePickup = true;
                mainContainer.addClass("has-pickup");

                tbody.empty();
                $.each(students, function() {
                    tbody.append("<tr><td>" + (this['student_name'] || '') + "</td><td>" + (this['grade'] || '') + "</td></tr>");
                });

                if (!scrollAnimationId) {
                    isScrolling = true;
                    startScroll();
                }
            } else {
                hasActivePickup = false;
                mainContainer.removeClass("has-pickup");
                tbody.empty();
                table.css('top', '0px');
                if (scrollAnimationId) {
                    cancelAnimationFrame(scrollAnimationId);
                    scrollAnimationId = null;
                }
            }
        }

        function fetchPickupData() {
            $.ajaxSetup({ cache: false });
            $.getJSON("data.php", function(data) {
                updatePickupDisplay(data ? data.result : []);
            }).fail(function() {
                $.getJSON("https://whatsapp.mhis.link/filewebhook/pickup/data.php", function(data) {
                    updatePickupDisplay(data ? data.result : []);
                }).fail(function() {
                    if (!hasActivePickup) {
                        updatePickupDisplay([]);
                    }
                });
            });
        }

        // Initial fetch and regular polling
        fetchPickupData();
        setInterval(fetchPickupData, 3000);

        // Scanner input management
        var input = document.getElementById("myInput");

        function keepInputFocused() {
            if (document.activeElement !== input) {
                input.focus();
            }
        }

        // Keep input focused at all times for automatic scanner detection
        input.focus();
        document.addEventListener('click', keepInputFocused);
        window.addEventListener('keydown', function(event) {
            if (document.activeElement !== input) {
                input.focus();
            }
        });
        setInterval(keepInputFocused, 1000);

        // Handle Enter key from barcode / RFID scanner
        $(input).on("keydown keypress", function(event) {
            if (event.key === "Enter" || event.keyCode === 13) {
                event.preventDefault();
                $("#myBtn").click();
            }
        });

        // Submit scanned data
        $(".tombol-simpan").click(function(){
            var inputVal = $.trim($(input).val());
            if (!inputVal) {
                return;
            }

            var data = $('.form-user').serialize();
            input.value = "";

            $.ajax({
                type: 'POST',
                url: "simpan.php",
                data: data,
                success: function() { 
                    fetchPickupData(); 
                },
                error: function() {
                    $.ajax({
                        type: 'POST',
                        url: "https://whatsapp.mhis.link/filewebhook/pickup/simpan.php",
                        data: data,
                        success: function() { 
                            fetchPickupData(); 
                        }
                    });
                }
            });
        });

    </script>
</body>
</html>