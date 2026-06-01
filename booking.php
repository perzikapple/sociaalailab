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
        ['id' => 1, 'name' => 'Projector', 'quantity' => 2],
        ['id' => 2, 'name' => 'Whiteboard', 'quantity' => 3],
        ['id' => 3, 'name' => 'Microfoon', 'quantity' => 4],
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

    if (!empty($staffDates) && !empty($staffName)) {
        // Split dates by comma, newline, or semicolon
        $dateArray = preg_split('/[,;\n\r]+/', $staffDates, -1, PREG_SPLIT_NO_EMPTY);
        // Remove duplicates and trim
        $dateArray = array_unique(array_map('trim', $dateArray));
        
        foreach ($dateArray as $dateStr) {
            // Validate date format (YYYY-MM-DD)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                // Check if this person already exists for this date and location
                $checkStmt = $pdo->prepare("
                    SELECT COUNT(*) FROM location_staff 
                    WHERE staff_date = ? AND location_id = ? AND staff_name = ?
                ");
                $checkStmt->execute([$dateStr, $staffLocationId, $staffName]);
                $exists = $checkStmt->fetchColumn();
                
                // Only insert if it doesn't already exist
                if (!$exists) {
                    $stmt = $pdo->prepare("
                        INSERT INTO location_staff (staff_date, location_id, staff_name)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$dateStr, $staffLocationId, $staffName]);
                }
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
        $hardwareJson = json_encode($_POST['hardware'] ?? []);
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

            $stmt = $pdo->prepare("INSERT INTO bookings (location_id, location_description, booking_date, start_time, end_time, hardware_ids) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $locId,
                $locationDescription,
                $bookingDate,
                $bookingStartTime,
                $bookingEndTime,
                $hardwareJson
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
$stmt = $pdo->prepare("SELECT DISTINCT staff_name, GROUP_CONCAT(DATE_FORMAT(staff_date, '%d-%m-%Y') ORDER BY staff_date SEPARATOR ', ') as dates FROM location_staff WHERE location_id = 1 GROUP BY staff_name ORDER BY staff_name");
$stmt->execute();
$allStaffAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* LOAD STAFF ASSIGNMENTS FOR SELECTED DAY */
$stmt = $pdo->prepare("SELECT * FROM location_staff WHERE staff_date = ?");
$stmt->execute([$selectedDay]);
$staffAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
$staffByLocation = [];
foreach ($staffAssignments as $staff) {
    $staffByLocation[$staff['location_id']] = $staff['staff_name'];
}

/* LOAD ALL DATES WITH STAFF FOR CALENDAR INDICATOR */
$stmt = $pdo->prepare("SELECT DISTINCT staff_date FROM location_staff WHERE location_id = 1");
$stmt->execute();
$daysWithStaff = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
                        <strong><?= $b['location_id'] == 999 ? ($b['location_description'] ?? 'Extern') : $loc['name'] ?></strong><br>
                        <?= substr($b['start_time'], 0, 5) ?> - <?= substr($b['end_time'], 0, 5) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if ($selectedDay && !empty($allStaffAssignments)): ?>
            <div style="margin-top: 2rem; border-top: 2px solid #dfe8f3; padding-top: 1.5rem;">
                <h3 style="margin-bottom: 1rem; color: #00811F;">Ingedeeld personeel</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem;">
                    <thead>
                        <tr style="background-color: #f9fafb;">
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dfe8f3; font-weight: 600; color: #00811F;">Naam</th>
                            <th style="padding: 1rem; text-align: left; border-bottom: 2px solid #dfe8f3; font-weight: 600; color: #00811F;">Datums</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allStaffAssignments as $staff): ?>
                            <tr style="border-bottom: 1px solid #dfe8f3;">
                                <td style="padding: 0.85rem 1rem;"><?= htmlspecialchars($staff['staff_name']) ?></td>
                                <td style="padding: 0.85rem 1rem;"><?= htmlspecialchars($staff['dates']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                foreach ($bookings as $b) {
                    $daysWithBookings[] = $b['booking_date'];
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
                                echo "<div class=\"staff-indicator\"></div>";
                            }
                            if ($hasBooking) {
                                echo "<div class=\"booking-indicator\"></div>";
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

    <!-- FORM -->
    <section class="card form-card">
        <h2>Nieuwe booking</h2>

        <?php if ($message): ?>
            <div class="message error-message">
                <i class="fa fa-exclamation-circle"></i> <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="bookingForm">
            <input type="date" name="booking_date" id="booking_date" required>

            <fieldset class="locations">
                <legend>Kies één of meerdere ruimtes</legend>
                <?php foreach ($locations as $l): ?>
                    <label style="display: inline-block; margin-right: 0.75rem;">
                        <input type="checkbox" name="location_ids[]" value="<?= $l['id'] ?>" onchange="toggleLocationDescription()">
                        <?= htmlspecialchars($l['name']) ?>
                    </label>
                <?php endforeach; ?>
            </fieldset>

            <input type="text" name="location_description" id="location_description" placeholder="Waar/wat is de externe locatie?" style="display:none;">

            <div class="row">
                <input type="time" name="start_time" id="start_time" required>
                <input type="time" name="end_time" id="end_time" required>
            </div>

            <div class="chips">
                <?php foreach ($hardware as $h): ?>
                    <label>
                        <input type="checkbox" name="hardware[]" value="<?= $h['id'] ?>">
                        <?= $h['name'] ?>
                    </label>
                <?php endforeach; ?>
            </div>

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
        
        <textarea 
            name="staff_dates" 
            placeholder="2024-01-15&#10;2024-01-16&#10;2024-01-17&#10;&#10;of: 2024-01-15, 2024-01-16, 2024-01-17"
            style="resize: vertical; min-height: 80px;"
            required
        ></textarea>
        
        <small style="color: #999; margin: -0.6rem 0 0 0; font-size: 0.85rem;">Formaat: YYYY-MM-DD (één per regel of gescheiden door komma)</small>
        
        <button type="submit" class="btn">Opslaan</button>
    </form>
</section>

</body>
</html>
