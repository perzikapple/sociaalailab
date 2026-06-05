<?php
session_start();
require 'db.php';
require 'helpers.php';

if (!($_SESSION['can_access_admin'] ?? false)) {
    header('Location: login.php');
    exit;
}

$sessionPermissions = [];
$decodedPermissions = json_decode((string)($_SESSION['permissions'] ?? ''), true);
if (is_array($decodedPermissions)) {
    $sessionPermissions = array_map('strval', $decodedPermissions);
}

if (!in_array('access_booking', $sessionPermissions, true)) {
    header('Location: admin.php');
    exit;
}

// Staff color palette (auto-assigned to each unique staff member)
$staffColorPalette = [
    '#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8',
    '#F7DC6F', '#BB8FCE', '#85C1E2', '#F8B88B', '#52C9A8',
    '#E57373', '#64B5F6', '#81C784', '#FFD54F', '#BA68C8'
];

function getStaffColor($pdo, $staffName, $palette) {
    // Check if staff already has a color assigned
    $stmt = $pdo->prepare("SELECT color FROM location_staff WHERE staff_name = ? LIMIT 1");
    $stmt->execute([$staffName]);
    $existing = $stmt->fetchColumn();
    
    if ($existing) {
        return $existing; // Reuse existing color
    }
    
    // Get all colors in use
    $stmt = $pdo->prepare("SELECT DISTINCT color FROM location_staff WHERE color IS NOT NULL");
    $stmt->execute();
    $usedColors = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $usedColors = array_map('strtoupper', $usedColors);
    
    // Find first available color from palette
    foreach ($palette as $color) {
        if (!in_array(strtoupper($color), $usedColors)) {
            return $color;
        }
    }
    
    // If all colors used, return a random one (shouldn't happen with 15 colors)
    return $palette[count($usedColors) % count($palette)];
}

$currentMonth = $_GET['month'] ?? date('n');
$currentYear = $_GET['year'] ?? date('Y');
$view = $_GET['view'] ?? 'week';
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedDay = $_GET['selected_day'] ?? date('Y-m-d');

$selectedDateTime = new DateTime($selectedDate);
$weekStart = (clone $selectedDateTime)->modify('monday this week');
$weekEnd = (clone $weekStart)->modify('+6 days');

$locations = [
        ['id' => 1, 'name' => 'Grote Zaal 1', 'color' => '#00811F'],
        ['id' => 2, 'name' => 'Grote Zaal 2', 'color' => '#00811F'],
        ['id' => 3, 'name' => 'Grote Zaal 3', 'color' => '#00811F'],

        ['id' => 4, 'name' => 'Workshop 1', 'color' => '#0066CC'],
        ['id' => 5, 'name' => 'Workshop 2', 'color' => '#0066CC'],

        ['id' => 999, 'name' => 'Extern', 'color' => '#FF9500'],
];

$hardware = [
    ['id' => 1, 'name' => 'PC (Workstation 9950X3D RTX5090 96GB RAM)', 'quantity' => 1],
    ['id' => 2, 'name' => 'PC (Framework Max+ 395 128GB)', 'quantity' => 4],
    ['id' => 3, 'name' => 'Robohond Unitree Go2 X', 'quantity' => 1],
    ['id' => 4, 'name' => 'Robohond Unitree Go2 Pro', 'quantity' => 1],
    ['id' => 5, 'name' => 'Tablet 8,7" Samsung Galaxy Tab A11', 'quantity' => 1],
    ['id' => 6, 'name' => 'Tablet 11" Samsung Galaxy Tab A11', 'quantity' => 1],
    ['id' => 7, 'name' => 'VR bril Oculus Quest 3 512GB', 'quantity' => 2],
    ['id' => 8, 'name' => 'accu+zonnepaneel', 'quantity' => 1],
    ['id' => 9, 'name' => 'labkar buiten', 'quantity' => 1],
    ['id' => 10, 'name' => 'Wijkbot kar + afstandbediening', 'quantity' => 1],
    ['id' => 11, 'name' => 'labkar binnen', 'quantity' => 3],
    ['id' => 12, 'name' => 'speaker/microfoon Jabra Speak 2 75', 'quantity' => 1],
    ['id' => 13, 'name' => 'speaker/microfoon Jabra Speak 2 55', 'quantity' => 2],
    ['id' => 14, 'name' => 'Draadloze microfoon set van 2', 'quantity' => 1],
    ['id' => 15, 'name' => 'WiFi Router ASUS TUF BE9400', 'quantity' => 1],
    ['id' => 16, 'name' => 'laptop + muis + AC adapter', 'quantity' => 10],
    ['id' => 17, 'name' => 'toetsenbord', 'quantity' => 3],
    ['id' => 18, 'name' => 'muis', 'quantity' => 3],
    ['id' => 19, 'name' => 'Raspberry Pi + AC adapter + HDMI kabel', 'quantity' => 6],
    ['id' => 20, 'name' => 'Scherm 50" TCL 50Q6C', 'quantity' => 4],
    ['id' => 21, 'name' => 'Scherm 27" Philips 27E2N2500 + beugel', 'quantity' => 6],
    ['id' => 22, 'name' => 'Kensington Combinatie Ultra Laptop Slot x10', 'quantity' => 10],
    ['id' => 23, 'name' => 'HDMI 8k kabel 2m', 'quantity' => 6],
    ['id' => 24, 'name' => 'HDMI 8k kabel 5m', 'quantity' => 1],
    ['id' => 25, 'name' => 'Banner Sociaalailab', 'quantity' => 1],
    ['id' => 26, 'name' => 'Verwijsstandaard Sociaalailab', 'quantity' => 2],
];

function findById($arr, $id) {
    foreach ($arr as $i) if ($i['id'] == $id) return $i;
    return null;
}

/* LOAD BOOKINGS FIRST */
$stmt = $pdo->prepare("SELECT * FROM bookings");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";
// Support flash messages from booking POST handling
if (!empty($_SESSION['booking_message'])) {
    $message = $_SESSION['booking_message'];
    unset($_SESSION['booking_message']);
}

/* STAFF ASSIGNMENT HANDLING - CHECK FIRST */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['staff_dates'])) {
    $staffDates = trim($_POST['staff_dates']);
    $staffLocationId = $_POST['staff_location_id'] ?? 1;
    $staffName = trim($_POST['staff_name']);
    $startTime = trim($_POST['staff_start_time'] ?? null);
    $endTime = trim($_POST['staff_end_time'] ?? null);
    
    // Auto-assign color based on staff name
    $staffColor = getStaffColor($pdo, $staffName, $staffColorPalette);

    if (!empty($staffDates) && !empty($staffName)) {
        // Split dates by comma, newline, or semicolon
        $dateArray = preg_split('/[,;\n\r]+/', $staffDates, -1, PREG_SPLIT_NO_EMPTY);
        // Trim dates but don't remove duplicates - allow same date multiple times with different times
        $dateArray = array_map('trim', $dateArray);
        
        foreach ($dateArray as $dateStr) {
            // Validate date format (YYYY-MM-DD)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                $stmt = $pdo->prepare("
                    INSERT INTO location_staff (staff_date, location_id, staff_name, color, start_time, end_time)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$dateStr, $staffLocationId, $staffName, $staffColor, $startTime ?: null, $endTime ?: null]);
            }
        }
    }

    header("Location: booking.php?view=$view&date=$selectedDate");
    exit;
}

/* BOOKING INSERT - support multiple locations selection */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_date'])) {
    $bookingDate = $_POST['booking_date'] ?? null;
    $bookingStartTime = $_POST['start_time'] ?? null;
    $bookingEndTime = $_POST['end_time'] ?? null;
    $locationIds = $_POST['location_ids'] ?? [];
    // allow single location submitted as scalar for backwards compatibility
    if (!is_array($locationIds) && !empty($locationIds)) {
        $locationIds = [$locationIds];
    }

    if (!empty($bookingDate) && !empty($bookingStartTime) && !empty($bookingEndTime) && !empty($locationIds)) {
        $hardwareJson = $_POST['hardware_json'] ?? '[]';
        $inserted = [];
        $skipped = [];

        foreach ($locationIds as $locId) {
            $locId = intval($locId);
            if ($locId === 0) continue;

            $locationDescription = $locId == 999 ? ($_POST['location_description'] ?? '') : null;

            // Check for conflicting bookings for this location
            $conflict = false;
            foreach ($bookings as $b) {
                if ($b['booking_date'] === $bookingDate && $b['location_id'] == $locId) {
                    // Externe bookings (999) conflicteren niet met elkaar
                    if ($locId == 999) continue;

                    $bStart = (int)substr($b['start_time'], 0, 2);
                    $bEnd = (int)substr($b['end_time'], 0, 2);
                    $newStart = (int)substr($bookingStartTime, 0, 2);
                    $newEnd = (int)substr($bookingEndTime, 0, 2);

                    if ($newStart < $bEnd && $newEnd > $bStart) {
                        $conflict = true;
                        break;
                    }
                }
            }

            if ($conflict) {
                $skipped[] = $locId;
                continue;
            }

            $hardwareJson = $_POST['hardware_json'] ?? '[]';
            $staffPresent = trim($_POST['staff_present'] ?? '');
            $bookingTitle = trim($_POST['booking_title'] ?? '');
            
            $stmt = $pdo->prepare("INSERT INTO bookings (location_id, location_description, booking_date, start_time, end_time, hardware_ids, staff_present, title) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $locId,
                $locationDescription,
                $bookingDate,
                $bookingStartTime,
                $bookingEndTime,
                $hardwareJson,
                !empty($staffPresent) ? $staffPresent : null,
                !empty($bookingTitle) ? $bookingTitle : null
            ]);

            $inserted[] = $locId;
        }

        // prepare a user-friendly message and store in session so it survives the redirect
        $msgParts = [];
        if (!empty($inserted)) {
            $names = [];
            foreach ($inserted as $id) {
                $loc = findById($locations, $id);
                $names[] = $loc ? $loc['name'] : "#{$id}";
            }
            $msgParts[] = "Geboekt: " . implode(', ', $names);
        }
        if (!empty($skipped)) {
            $names = [];
            foreach ($skipped as $id) {
                $loc = findById($locations, $id);
                $names[] = $loc ? $loc['name'] : "#{$id}";
            }
            $msgParts[] = "Kon niet boeken (conflict): " . implode(', ', $names);
        }

        if (!empty($msgParts)) {
            $_SESSION['booking_message'] = implode(' | ', $msgParts);
        }

        header("Location: booking.php?view=$view&date=$selectedDate");
        exit;
    }
}

/* LOAD ALL STAFF ASSIGNMENTS FOR DISPLAY */
$stmt = $pdo->prepare("SELECT DISTINCT staff_name, color, COUNT(DISTINCT staff_date) as num_dates FROM location_staff WHERE location_id = 1 GROUP BY staff_name, color ORDER BY staff_name");
$stmt->execute();
$allStaffAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* LOAD STAFF ASSIGNMENTS FOR SELECTED DAY */
$stmt = $pdo->prepare("SELECT * FROM location_staff WHERE staff_date = ?");
/* LOAD STAFF DATA FOR SELECTED DAY AND MERGE TIME SLOTS */
$stmt = $pdo->prepare("SELECT staff_name, start_time, end_time, color FROM location_staff WHERE staff_date = ? AND location_id = 1 ORDER BY start_time");
$stmt->execute([$selectedDay]);
$staffData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by staff_name and merge overlapping/adjacent time slots
$staffByLocation = [];
$staffGroups = [];
foreach ($staffData as $staff) {
    $name = $staff['staff_name'];
    if (!isset($staffGroups[$name])) {
        $staffGroups[$name] = ['color' => $staff['color'], 'slots' => []];
    }
    $staffGroups[$name]['slots'][] = [
        'start' => $staff['start_time'],
        'end' => $staff['end_time']
    ];
}

// Merge overlapping and adjacent time slots for each staff member
foreach ($staffGroups as $name => $group) {
    $slots = $group['slots'];
    
    if (empty($slots)) continue;
    
    // Sort by start time
    usort($slots, function($a, $b) {
        $aStart = $a['start'] ?? '00:00';
        $bStart = $b['start'] ?? '00:00';
        return strcmp($aStart, $bStart);
    });
    
    // Merge overlapping/adjacent slots
    $merged = [];
    foreach ($slots as $slot) {
        $start = $slot['start'];
        $end = $slot['end'];
        
        if (empty($merged)) {
            $merged[] = $slot;
        } else {
            $lastSlot = &$merged[count($merged) - 1];
            $lastEnd = $lastSlot['end'] ?? '23:59';
            $currentStart = $start ?? '00:00';
            
            // Check if slots overlap or are adjacent (within 1 minute)
            if (strtotime($currentStart) <= strtotime($lastEnd)) {
                // Merge: extend the end time if new slot ends later
                if ($end && (!$lastSlot['end'] || strtotime($end) > strtotime($lastSlot['end']))) {
                    $lastSlot['end'] = $end;
                }
            } else {
                // No overlap, add as new slot
                $merged[] = $slot;
            }
        }
    }
    
    $staffByLocation[$name] = [
        'color' => $group['color'],
        'slots' => $merged
    ];
}

/* LOAD ALL DATES WITH STAFF FOR CALENDAR INDICATOR */
$stmt = $pdo->prepare("SELECT staff_date, GROUP_CONCAT(color SEPARATOR '|') as colors FROM location_staff WHERE location_id = 1 GROUP BY staff_date");
$stmt->execute();
$staffByDate = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $staffByDate[$row['staff_date']] = explode('|', $row['colors']);
}
$daysWithStaff = array_keys($staffByDate);
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Booking</title>

    <link rel="stylesheet" href="style/booking.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<header class="topbar">
    <div class="logo"><i class="fa fa-calendar"></i> Booking</div>
    <div class="nav">
        <a href="index.php">Terug naar site</a>
        <a href="admin.php">Terug naar admin</a>
        <a href="logout.php">Uitloggen</a>
    </div>
</header>

<div class="container">

    <!-- BOOKINGS -->
    <section class="card">
        <h2>Boekingen <?php if ($selectedDay): ?> - <?= date('d F Y', strtotime($selectedDay)) ?><?php endif; ?></h2>

        <?php 
        $dayBookings = array_filter($bookings, function($b) use ($selectedDay) {
            return $selectedDay && $b['booking_date'] === $selectedDay;
        });
        
        if ($selectedDay && empty($dayBookings)): 
        ?>
            <p style="color: #999; text-align: center; padding: 2rem;">Geen boekingen op deze dag</p>
        <?php elseif (!$selectedDay): ?>
            <p style="color: #999; text-align: center; padding: 2rem;">Selecteer een dag om boekingen te zien</p>
        <?php else: ?>
            <?php foreach ($dayBookings as $b): ?>
                <?php $loc = findById($locations, $b['location_id']); ?>
                <div class="booking">
                    <div>
                        <strong><?= htmlspecialchars($b['title'] ?? 'Ongetiteld') ?></strong><br>
                        <?= htmlspecialchars($b['location_id'] == 999 ? ($b['location_description'] ?? 'Extern') : $loc['name']) ?><br>
                        <span style="font-size: 0.9rem; color: #666;">
                            <?= substr($b['start_time'], 0, 5) ?> - <?= substr($b['end_time'], 0, 5) ?>
                            <?php 
                            if ($b['hardware_ids']): 
                                $hwList = json_decode($b['hardware_ids'], true);
                                if (!empty($hwList)):
                            ?>
                                <br><span style="font-size: 0.85rem; color: #00811F;"><strong>Hardware:</strong>
                                <?php 
                                $hwNames = [];
                                foreach ((array)$hwList as $hw) {
                                    if (isset($hw['id']) && isset($hw['quantity'])) {
                                        foreach ($hardware as $h) {
                                            if ($h['id'] == $hw['id']) {
                                                $hwNames[] = $hw['quantity'] . '× ' . $h['name'];
                                                break;
                                            }
                                        }
                                    }
                                }
                                echo implode(', ', $hwNames);
                                ?>
                                </span>
                            <?php endif; endif; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($selectedDay && !empty($staffByLocation)): ?>
            <div style="margin-top: 2rem; border-top: 2px solid #dfe8f3; padding-top: 1.5rem;">
                <h3 style="margin-bottom: 1rem; color: #00811F;">Ingedeeld personeel</h3>
                <div style="display: flex; flex-wrap: wrap; gap: 1rem;">
                    <?php foreach ($staffByLocation as $name => $info): ?>
                        <div style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; background: #f9fafb; border-radius: 10px; border: 2px solid #dfe8f3;">
                            <div style="width: 20px; height: 20px; border-radius: 4px; background: <?= htmlspecialchars($info['color']) ?>; border: 2px solid #ddd;"></div>
                            <div>
                                <span style="font-weight: 600; color: #00811F; display: block;"><?= htmlspecialchars($name) ?></span>
                                <span style="color: #666; font-size: 0.85rem;">
                                    <?php 
                                    if (empty($info['slots'])) {
                                        echo 'Hele dag';
                                    } else {
                                        $timeStrs = [];
                                        foreach ($info['slots'] as $slot) {
                                            if ($slot['start'] && $slot['end']) {
                                                $timeStrs[] = substr($slot['start'], 0, 5) . '-' . substr($slot['end'], 0, 5);
                                            } else {
                                                $timeStrs[] = 'Hele dag';
                                            }
                                        }
                                        echo implode(', ', $timeStrs);
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- MONTH CALENDAR -->
    <section class="card">
            <h2>Kalender <?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></h2>
            <div class="nav-buttons">
                <?php 
                $prevMonth = $currentMonth - 1 ?: 12;
                $prevYear = $currentMonth - 1 ? $currentYear : $currentYear - 1;
                $nextMonth = ($currentMonth % 12) + 1;
                $nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
                ?>
                <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn-nav"><i class="fa fa-chevron-left"></i></a>
                <a href="?month=<?= $currentMonth ?>&year=<?= $currentYear ?>" class="btn-nav"><?= date('F', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></a>
                <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn-nav"><i class="fa fa-chevron-right"></i></a>
            </div>
        </div>

        <table class="month-calendar">
            <thead>
                <tr>
                    <th>Ma</th>
                    <th>Di</th>
                    <th>Wo</th>
                    <th>Do</th>
                    <th>Vr</th>
                    <th>Za</th>
                    <th>Zo</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                $lastDay = mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear);
                $daysInMonth = date('d', $lastDay);
                $startWeekday = date('N', $firstDay) - 1;
                
                // Create array of dates with bookings
                $daysWithBookings = [];
                $bookingTitlesByDate = [];
                foreach ($bookings as $b) {
                    $daysWithBookings[] = $b['booking_date'];
                    if (!isset($bookingTitlesByDate[$b['booking_date']])) {
                        $bookingTitlesByDate[$b['booking_date']] = [];
                    }
                    if (!empty($b['title'])) {
                        $bookingTitlesByDate[$b['booking_date']][] = $b['title'];
                    }
                }
                
                $day = 1;
                for ($i = 0; $i < 6; $i++) {
                    echo "<tr>";
                    for ($j = 0; $j < 7; $j++) {
                        if (($i == 0 && $j < $startWeekday) || $day > $daysInMonth) {
                            echo "<td class=\"empty\"></td>";
                        } else {
                            $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                            $today = date('Y-m-d');
                            $isPast = $dateStr < $today ? 'past' : '';
                            $isToday = $dateStr === date('Y-m-d') ? 'today' : '';
                            $isSelected = $dateStr === $selectedDay ? 'selected' : '';
                            $hasBooking = in_array($dateStr, $daysWithBookings) ? 'has-booking' : '';
                            $hasStaff = in_array($dateStr, $daysWithStaff) ? 'has-staff' : '';
                            // Past dates ARE now clickable to view history
                            $clickable = 'onclick="selectDay(\'' . $dateStr . '\', ' . $currentMonth . ', ' . $currentYear . ')" style="cursor: pointer;"';
                            echo "<td class=\"day-cell $isToday $isSelected $hasBooking $hasStaff $isPast\" $clickable>";
                            echo "<span class=\"day-number\">$day</span>";
                            if ($hasStaff) {
                                echo "<div class=\"staff-indicators\">";
                                if (isset($staffByDate[$dateStr])) {
                                    foreach ($staffByDate[$dateStr] as $color) {
                                        echo "<div class=\"staff-indicator\" style=\"background-color: " . htmlspecialchars($color) . ";\"></div>";
                                    }
                                }
                                echo "</div>";
                            }
                            if ($hasBooking) {
                                echo "<div class=\"booking-indicator\"></div>";
                                if (!empty($bookingTitlesByDate[$dateStr])) {
                                    echo "<div class=\"booking-titles\">";
                                    foreach ($bookingTitlesByDate[$dateStr] as $title) {
                                        echo "<div class=\"booking-title\">" . htmlspecialchars($title) . "</div>";
                                    }
                                    echo "</div>";
                                }
                            }
                            echo "</td>";
                            $day++;
                        }
                    }
                    echo "</tr>";
                    if ($day > $daysInMonth) break;
                }
                ?>
            </tbody>
        </table>
    </section>

    <!-- TIME SCHEDULES FOR ALL DAYS (Hidden by default) -->
    <?php
    $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
    $lastDay = mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear);
    $daysInMonth = date('d', $lastDay);
    $startWeekday = date('N', $firstDay) - 1;
    
    $day = 1;
    for ($i = 0; $i < 6; $i++) {
        for ($j = 0; $j < 7; $j++) {
            if (($i == 0 && $j < $startWeekday) || $day > $daysInMonth) {
                // empty cells
            } else {
                $dateStr = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                $selectedDateObj = new DateTime($dateStr);
                $dayOfWeek = $selectedDateObj->format('N');
                $isWeekend = ($dayOfWeek >= 6);
                ?>
                <?php
                $day++;
            }
        }
    }
    ?>

    <!-- FORM 2 -->
    <section class="card form-card">
        <h2>Nieuwe booking</h2>

        <?php if ($message): ?>
            <div class="message error-message">
                <i class="fa fa-exclamation-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="bookingForm">
            <input type="text" name="booking_title" id="booking_title" placeholder="Titel/naam van de booking (bijv: Team Meeting, Workshop)" required>
            
            <input type="date" name="booking_date" id="booking_date" required>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; color: #00811F;">Kies één of meerdere ruimtes:</label>
                <div class="location-buttons">
                    <?php foreach ($locations as $l): ?>
                        <label class="location-button" style="cursor: pointer;">
                            <input type="checkbox" name="location_ids[]" value="<?= $l['id'] ?>" onchange="toggleLocationDescription()">
                            <span style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; background: #f9fafb; border: 2px solid #dfe8f3; border-radius: 10px; font-weight: 500; color: #333; transition: all 0.3s ease;">
                                <div style="width: 12px; height: 12px; border-radius: 3px; border: 2px solid #dfe8f3; background: white;"></div>
                                <?= htmlspecialchars($l['name']) ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <input type="text" name="location_description" id="location_description" placeholder="Waar/wat is de externe locatie?" style="display:none;">

            <div class="row">
                <input type="time" name="start_time" id="start_time" required>
                <input type="time" name="end_time" id="end_time" required>
            </div>

            <textarea name="staff_present" placeholder="Wie is/zijn er present bij deze booking?" style="resize: vertical; min-height: 60px;"></textarea>

            <div style="margin-bottom: 1.2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: #00811F;">Hardware toevoegen:</label>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                    <select id="hardware_select" style="flex: 1;">
                        <option value="">-- Kies hardware --</option>
                        <?php foreach ($hardware as $h): ?>
                            <option value="<?= $h['id'] ?>" data-max="<?= $h['quantity'] ?>"><?= htmlspecialchars($h['name']) ?> (max: <?= $h['quantity'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" id="hardware_qty" min="1" value="1" style="width: 80px;">
                    <button type="button" class="btn" onclick="addHardware()" style="width: auto; padding: 0.85rem 1.5rem;">Voeg toe</button>
                </div>
                
                <div id="selected_hardware" style="display: flex; flex-wrap: wrap; gap: 0.75rem;">
                    <!-- Items worden hier toegevoegd -->
                </div>
            </div>

            <!-- Hidden input voor hardware data -->
            <input type="hidden" name="hardware_json" id="hardware_json" value="[]">

            <button type="submit" class="btn" id="submitBtn">Boek nu</button>
        </form>
    </section>

</div>

<script>
function selectDay(dateStr, month, year) {
    // Update URL to include selected_day parameter
    window.location.href = `?month=${month}&year=${year}&selected_day=${dateStr}`;
}

function checkIfPastDate(dateStr) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const selectedDate = new Date(dateStr);
    selectedDate.setHours(0, 0, 0, 0);
    
    const submitBtn = document.getElementById('submitBtn');
    const bookingForm = document.getElementById('bookingForm');
    
    if (selectedDate < today) {
        // Past date - disable everything
        submitBtn.disabled = true;
        submitBtn.title = 'Je kan geen bookings toevoegen voor voorbijgegane dagen';
        // Disable all form inputs
        bookingForm.querySelectorAll('input, select, button').forEach(el => {
            if (el.id !== 'submitBtn') el.disabled = true;
        });
    } else {
        // Future date - enable everything
        submitBtn.disabled = false;
        submitBtn.title = '';
        bookingForm.querySelectorAll('input, select, button').forEach(el => {
            el.disabled = false;
        });
    }
}

// Toggle location description field based on whether extern (999) is selected
function toggleLocationDescription() {
    const checkboxes = document.querySelectorAll('input[name="location_ids[]"]');
    const descriptionField = document.getElementById('location_description');
    let externSelected = false;
    checkboxes.forEach(cb => {
        if (cb.checked && cb.value === '999') externSelected = true;
    });

    if (externSelected) {
        descriptionField.style.display = 'block';
        descriptionField.required = true;
    } else {
        descriptionField.style.display = 'none';
        descriptionField.required = false;
        descriptionField.value = '';
    }
}

// Initialize form with selected day if present
document.addEventListener('DOMContentLoaded', function() {
    const selectedDay = '<?= $selectedDay ?>';
    if (selectedDay) {
        document.getElementById('booking_date').value = selectedDay;
        
        // Check if date is in past and disable booking if so
        checkIfPastDate(selectedDay);
    }
    
    // Initialize location description field visibility
    toggleLocationDescription();
    
    // Listen for date field changes to enable/disable submit button
    const dateInput = document.getElementById('booking_date');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            checkIfPastDate(this.value);
        });
    }
    
    // Add form validation on submit
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            // Check if end time is after start time
            if (startTime && endTime && startTime >= endTime) {
                e.preventDefault();
                alert('De eindtijd moet na de begintijd liggen!');
                return false;
            }
        });
        
        // Update end_time minimum when start_time changes
        const startTimeInput = document.getElementById('start_time');
        if (startTimeInput) {
            startTimeInput.addEventListener('change', function() {
                const startTime = this.value;
                if (startTime) {
                    // Set end time to 1 hour after start time
                    const [hours, minutes] = startTime.split(':');
                    const nextHour = String(parseInt(hours) + 1).padStart(2, '0');
                    document.getElementById('end_time').value = nextHour + ':00';
                    document.getElementById('end_time').min = startTime;
                }
            });
        }
        
        // Auto-fill staff name fields if user is logged in
        const loggedInName = '<?= htmlspecialchars($_SESSION['first_name'] ?? '') ?>';
        if (loggedInName) {
            const staffNameInputs = document.querySelectorAll('input[name="staff_name"]');
            staffNameInputs.forEach(input => {
                if (input.value.trim() === '') {
                    input.placeholder = 'Naam van personeel (standaard: ' + loggedInName + ')';
                    input.addEventListener('focus', function() {
                        if (this.value.trim() === '') {
                            this.value = loggedInName;
                        }
                    });
                    input.addEventListener('blur', function() {
                        if (this.value.trim() === loggedInName) {
                            this.value = '';
                        }
                    });
                }
            });
        }
    }
});

// Hardware selection system
let selectedHardware = {};

function addHardware() {
    const select = document.getElementById('hardware_select');
    const qtyInput = document.getElementById('hardware_qty');
    const hardwareId = select.value;
    const qty = parseInt(qtyInput.value) || 1;
    
    if (!hardwareId) {
        alert('Kies eerst een hardware item');
        return;
    }
    
    const option = select.options[select.selectedIndex];
    const maxQty = parseInt(option.dataset.max) || 1;
    const hardwareName = option.text.split(' (max:')[0];
    
    if (qty > maxQty) {
        alert(`Maximaal ${maxQty} beschikbaar van dit item`);
        return;
    }
    
    selectedHardware[hardwareId] = {
        name: hardwareName,
        qty: qty,
        max: maxQty
    };
    
    updateHardwareDisplay();
    select.value = '';
    qtyInput.value = '1';
}

function removeHardware(hardwareId) {
    delete selectedHardware[hardwareId];
    updateHardwareDisplay();
}

function updateHardwareDisplay() {
    const container = document.getElementById('selected_hardware');
    container.innerHTML = '';
    
    let hasItems = false;
    for (const [hwId, hw] of Object.entries(selectedHardware)) {
        hasItems = true;
        const chip = document.createElement('div');
        chip.style.cssText = 'display: flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1rem; background: #f0f5ff; border-radius: 10px; border: 2px solid #dfe8f3;';
        chip.innerHTML = `
            <span style="flex: 1; font-weight: 600; color: #00811F;">${hw.name}</span>
            <span style="background: #00811F; color: white; padding: 0.3rem 0.6rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem;">${hw.qty}x</span>
            <button type="button" onclick="removeHardware('${hwId}')" style="background: none; border: none; color: #ff6b6b; cursor: pointer; font-size: 1.2rem; padding: 0;">×</button>
        `;
        container.appendChild(chip);
    }
    
    // Update hidden JSON field
    const jsonData = [];
    for (const [hwId, hw] of Object.entries(selectedHardware)) {
        jsonData.push({ id: hwId, quantity: hw.qty });
    }
    document.getElementById('hardware_json').value = JSON.stringify(jsonData);
}

// Prevent form submit if no hardware selected
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    if (Object.keys(selectedHardware).length === 0) {
        alert('Voeg minstens één hardware item toe');
        e.preventDefault();
        return false;
    }
});
</script>

<!-- STAFF SECTION -->
<section class="card form-card">
    <h2>Aanwezigheid op locatie toevoegen</h2>
    
    <form method="POST">
        <input type="hidden" name="staff_location_id" value="1">
        
        <input 
            type="text" 
            name="staff_name" 
            placeholder="Naam van personeelslid"
            required
        >
        
        <div class="row">
            <input type="time" name="staff_start_time" id="staff_start_time" placeholder="Start tijd (optioneel)">
            <input type="time" name="staff_end_time" id="staff_end_time" placeholder="Eind tijd (optioneel)">
        </div>
        
        <textarea 
            name="staff_dates" 
            placeholder="2024-01-15&#10;2024-01-16&#10;2024-01-17&#10;&#10;of: 2024-01-15, 2024-01-16, 2024-01-17"
            style="resize: vertical; min-height: 80px;"
            required
        ></textarea>
        
        <small style="color: #999; margin: -0.6rem 0 0 0; font-size: 0.85rem;">Formaat: YYYY-MM-DD (één per regel of gescheiden door komma)<br>Kleur wordt automatisch toegewezen!</small>
        
        <button type="submit" class="btn">Opslaan</button>
    </form>
</section>

</body>
</html>
