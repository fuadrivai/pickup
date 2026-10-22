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

        .duty-roster-container {
            width: 100%;
            height: 100%;
            overflow-y: auto;
            overscroll-behavior: contain;
            padding: 8px;
        }
        .duty-roster-heading {
            color: var(--brand-magenta);
            font-size: clamp(22px, 2vw, 34px);
            font-weight: 800;
            margin-bottom: 4px;
            text-align: center;
            text-transform: uppercase;
        }
        .duty-roster-date {
            color: #5f6368;
            font-size: clamp(14px, 1.2vw, 20px);
            font-weight: 600;
            margin-bottom: 18px;
            text-align: center;
        }
        .duty-division {
            background: #fff;
            border: 1px solid #e4e8ef;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0,0,0,.05);
            margin-bottom: 16px;
            overflow: hidden;
        }
        .duty-division-title {
            background: #eaf2f8;
            color: #27364b;
            font-size: clamp(16px, 1.35vw, 22px);
            font-weight: 800;
            padding: 10px 14px;
        }
        .duty-division-title small {
            color: #68778d;
            font-size: .72em;
            font-weight: 600;
            margin-left: 8px;
        }
        .duty-slots-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            padding: 12px;
        }
        .duty-slot {
            background: #fff;
            border: 1px solid #e2e9ef;
            border-radius: 10px;
            min-width: 0;
            overflow: hidden;
        }
        .duty-slot-heading {
            background: #f7f9fc;
            border-bottom: 1px solid #e2e9ef;
            padding: 10px 12px;
        }
        .duty-time {
            color: var(--brand-magenta);
            display: block;
            font-size: clamp(14px, 1.1vw, 18px);
            font-weight: 800;
            margin-bottom: 3px;
        }
        .duty-place { color: #27364b; font-size: clamp(15px, 1.2vw, 20px); font-weight: 800; }
        .duty-task { color: #68778d; display: block; font-size: .8em; margin-top: 2px; }
        .duty-teachers {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fill, minmax(125px, 1fr));
            padding: 10px;
        }
        .duty-teacher {
            background: #fff;
            border: 1px solid #e2e9ef;
            border-radius: 10px;
            min-width: 0;
            overflow: hidden;
        }
        .duty-teacher-photo, .duty-teacher-initial {
            align-items: center;
            background: linear-gradient(135deg, #d8e8ed, #eff5f7);
            display: flex;
            height: clamp(120px, 13vw, 190px);
            justify-content: center;
            object-fit: cover;
            width: 100%;
        }
        .duty-teacher-initial { color: #31515a; font-size: clamp(36px, 5vw, 64px); font-weight: 800; }
        .duty-teacher-info { display: flex; flex-direction: column; gap: 3px; line-height: 1.2; padding: 8px 9px 10px; }
        .duty-teacher-name { color: #27364b; font-size: clamp(13px, 1vw, 16px); font-weight: 800; overflow-wrap: anywhere; }
        .duty-teacher-division { color: #68778d; font-size: clamp(11px, .8vw, 13px); }
        .duty-empty { color: #77808c; font-size: 18px; padding: 24px; text-align: center; }

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
        var dutyRosterRefreshTimer = null;
        var dutyScrollAnimationId = null;
        var dutyScrollDirection = 1;
        var dutyScrollLastFrame = 0;
        var dutyScrollPauseUntil = 0;
        var dutyScrollContainer = null;
        var dutyScrollResizeObserver = null;

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
            stopDutyRosterAutoScroll();
            if (dutyRosterRefreshTimer !== null) {
                clearInterval(dutyRosterRefreshTimer);
                dutyRosterRefreshTimer = null;
            }

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
                else if (item.display_type === 'duty_roster') {
                    renderDutyRoster(item.title);
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

        function renderDutyRoster(title) {
            var container = $('<div>', { 'class': 'duty-roster-container' });
            var heading = $('<div>', { 'class': 'duty-roster-heading' }).text(title || 'Duty Roster');
            var dateLabel = $('<div>', { 'class': 'duty-roster-date' }).text('Memuat jadwal piket saat ini...');
            container.append(heading, dateLabel);
            displayContainer.replaceChildren(container[0]);

            function loadCurrentDutyRoster() {
                $.getJSON('get_duty_roster.php')
                .done(function(response) {
                    var scrollPosition = container.scrollTop();
                    dateLabel.text(response.weekday_name + ', ' + new Date().toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    }) + ' · ' + response.current_time);

                    container.find('.duty-division, .duty-empty').remove();
                    if (response.status !== 'success' || !Array.isArray(response.data) || response.data.length === 0) {
                        container.append($('<div>', {
                            'class': 'duty-empty',
                            text: 'Tidak ada tugas piket yang sedang berlangsung saat ini.'
                        }));
                        return;
                    }

                    response.data.forEach(function(division) {
                        var section = $('<section>', { 'class': 'duty-division' });
                        var divisionTitle = $('<div>', { 'class': 'duty-division-title' }).text(division.division_name);
                        if (division.academic_year) {
                            divisionTitle.append($('<small>').text(division.academic_year));
                        }
                        section.append(divisionTitle);

                        if (!Array.isArray(division.slots) || division.slots.length === 0) {
                            section.append($('<div>', {
                                'class': 'duty-empty',
                                text: 'Belum ada tugas untuk hari ini.'
                            }));
                            container.append(section);
                            return;
                        }

                        var slotsGrid = $('<div>', { 'class': 'duty-slots-grid' });

                        division.slots.forEach(function(slot) {
                            var placeLabel = slot.place_name || slot.assignment_title || slot.event_name || 'Tugas piket';
                            var slotCard = $('<article>', { 'class': 'duty-slot' });
                            var slotHeading = $('<div>', { 'class': 'duty-slot-heading' })
                                .append($('<span>', { 'class': 'duty-time' }).text(slot.start_time + ' - ' + slot.end_time))
                                .append($('<span>', { 'class': 'duty-place' }).text(placeLabel));
                            var taskLabel = [slot.section_title, slot.assignment_title, slot.event_name]
                                .filter(function(value) { return value && value !== placeLabel; })
                                .join(' · ');
                            if (taskLabel) {
                                slotHeading.append($('<span>', { 'class': 'duty-task' }).text(taskLabel));
                            }

                            var teacherList = $('<div>', { 'class': 'duty-teachers' });
                            if (Array.isArray(slot.teachers) && slot.teachers.length > 0) {
                                slot.teachers.forEach(function(teacher) {
                                    var teacherCard = $('<div>', { 'class': 'duty-teacher' });
                                    var photoPath = typeof teacher.photo === 'string' ? teacher.photo : '';
                                    var initial = (teacher.name || '?').trim().charAt(0).toUpperCase() || '?';
                                    if (
                                        /^https?:\/\//i.test(photoPath) ||
                                        (photoPath.indexOf('admin/uploads/teachers/') === 0 && photoPath.indexOf('..') === -1)
                                    ) {
                                        var teacherPhoto = $('<img>', {
                                            'class': 'duty-teacher-photo',
                                            src: photoPath,
                                            alt: 'Foto ' + (teacher.name || '')
                                        }).on('error', function() {
                                            $(this).replaceWith($('<span>', {
                                                'class': 'duty-teacher-initial',
                                                text: initial
                                            }));
                                        });
                                        teacherCard.append(teacherPhoto);
                                    } else {
                                        teacherCard.append($('<span>', { 'class': 'duty-teacher-initial', text: initial }));
                                    }
                                    var teacherInfo = $('<span>', { 'class': 'duty-teacher-info' })
                                        .append($('<span>', { 'class': 'duty-teacher-name' }).text(teacher.name || 'Nama tidak tersedia'))
                                        .append($('<span>', { 'class': 'duty-teacher-division' }).text(teacher.division || 'Divisi tidak tersedia'));
                                    teacherCard.append(teacherInfo);
                                    teacherList.append(teacherCard);
                                });
                            } else {
                                teacherList.append($('<span>', { 'class': 'text-muted' }).text('Belum ditentukan'));
                            }
                            if (slot.notes) {
                                slotCard.attr('title', slot.notes);
                            }
                            slotCard.append(slotHeading, teacherList);
                            slotsGrid.append(slotCard);
                        });

                        section.append(slotsGrid);
                        container.append(section);
                    });
                    container.scrollTop(scrollPosition);
                    if (dutyScrollContainer !== container[0] || dutyScrollAnimationId === null) {
                        startDutyRosterAutoScroll(container[0]);
                    }
                })
                .fail(function() {
                    dateLabel.text('');
                    container.find('.duty-division, .duty-empty').remove();
                    container.append($('<div>', {
                        'class': 'duty-empty text-danger',
                        text: 'Gagal memuat Duty Roster. Silakan periksa koneksi dan data jadwal.'
                    }));
                });
            }

            loadCurrentDutyRoster();
            dutyRosterRefreshTimer = setInterval(loadCurrentDutyRoster, 30000);
        }

        function stopDutyRosterAutoScroll() {
            if (dutyScrollAnimationId !== null) {
                cancelAnimationFrame(dutyScrollAnimationId);
                dutyScrollAnimationId = null;
            }
            if (dutyScrollResizeObserver !== null) {
                dutyScrollResizeObserver.disconnect();
                dutyScrollResizeObserver = null;
            }
            dutyScrollContainer = null;
            dutyScrollLastFrame = 0;
            dutyScrollPauseUntil = 0;
        }

        function startDutyRosterAutoScroll(container) {
            if (!container || !container.isConnected) {
                return;
            }
            stopDutyRosterAutoScroll();
            dutyScrollContainer = container;
            dutyScrollDirection = 1;
            dutyScrollLastFrame = 0;
            dutyScrollPauseUntil = 0;
            if (typeof ResizeObserver !== 'undefined') {
                dutyScrollResizeObserver = new ResizeObserver(function() {
                    if (dutyScrollContainer === container && dutyScrollAnimationId === null) {
                        dutyScrollAnimationId = requestAnimationFrame(animateDutyRoster);
                    }
                });
                dutyScrollResizeObserver.observe(container);
                if (container.firstElementChild) {
                    dutyScrollResizeObserver.observe(container.firstElementChild);
                }
            }

            function animateDutyRoster(timestamp) {
                if (!container.isConnected || dutyScrollContainer !== container) {
                    dutyScrollAnimationId = null;
                    return;
                }

                if (dutyScrollLastFrame === 0) {
                    dutyScrollLastFrame = timestamp;
                }
                var elapsed = Math.min(timestamp - dutyScrollLastFrame, 100);
                dutyScrollLastFrame = timestamp;
                var maxScroll = container.scrollHeight - container.clientHeight;
                if (maxScroll <= 1) {
                    container.scrollTop = 0;
                    dutyScrollAnimationId = null;
                    return;
                }

                if (timestamp >= dutyScrollPauseUntil) {
                    var nextScroll = container.scrollTop + dutyScrollDirection * elapsed * 0.045;
                    if (nextScroll >= maxScroll) {
                        container.scrollTop = maxScroll;
                        dutyScrollDirection = -1;
                        dutyScrollPauseUntil = timestamp + 900;
                    } else if (nextScroll <= 0) {
                        container.scrollTop = 0;
                        dutyScrollDirection = 1;
                        dutyScrollPauseUntil = timestamp + 900;
                    } else {
                        container.scrollTop = nextScroll;
                    }
                }

                dutyScrollAnimationId = null;
                if (dutyScrollContainer === container && container.isConnected && maxScroll > 1) {
                    dutyScrollAnimationId = requestAnimationFrame(animateDutyRoster);
                }
            }

            dutyScrollAnimationId = requestAnimationFrame(animateDutyRoster);
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