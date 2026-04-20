<?php
session_start();
require 'db.php';
require 'helpers.php';

if (!($_SESSION['can_access_admin'] ?? false)) {
    header('Location: login.php');
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
        ['id' => 1, 'name' => 'Grote Zaal', 'color' => '#00811F'],
        ['id' => 2, 'name' => 'Workshop', 'color' => '#0066CC'],
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

$message = "";

/* BOOKING INSERT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO bookings (location_id, booking_date, start_time, end_time, hardware_ids)
    VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
            $_POST['location_id'],
            $_POST['booking_date'],
            $_POST['start_time'],
            $_POST['end_time'],
            json_encode($_POST['hardware'] ?? [])
    ]);

    header("Location: booking.php?view=$view&date=$selectedDate");
    exit;
}

/* BOOKINGS */
$stmt = $pdo->prepare("SELECT * FROM bookings");
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                    <div class="bar" style="background:<?= $loc['color'] ?>"></div>
                    <div>
                        <strong><?= $loc['name'] ?></strong><br>
                        <?= $b['start_time'] ?> - <?= $b['end_time'] ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <!-- MONTH CALENDAR -->
    <section class="card">
        <div class="calendar-header">
            <h2>Kalender <?= date('F Y', mktime(0, 0, 0, $currentMonth, 1, $currentYear)) ?></h2>
            <div class="nav-buttons">
                <?php 
                $prevMonth = $currentMonth - 1 ?: 12;
                $prevYear = $currentMonth - 1 ? $currentYear : $currentYear - 1;
                $nextMonth = ($currentMonth % 12) + 1;
                $nextYear = $currentMonth == 12 ? $currentYear + 1 : $currentYear;
                $selectedDayParam = $selectedDay ? "&selected_day=$selectedDay" : '';
                ?>
                <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?><?= $selectedDayParam ?>" class="btn-nav"><i class="fa fa-chevron-left"></i></a>
                <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn-nav">Vandaag</a>
                <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?><?= $selectedDayParam ?>" class="btn-nav"><i class="fa fa-chevron-right"></i></a>
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
                            $isToday = $dateStr === date('Y-m-d') ? 'today' : '';
                            $isSelected = $dateStr === $selectedDay ? 'selected' : '';
                            $hasBooking = in_array($dateStr, $daysWithBookings) ? 'has-booking' : '';
                            echo "<td class=\"day-cell $isToday $isSelected $hasBooking\" onclick=\"selectDay('$dateStr', $currentMonth, $currentYear)\" style=\"cursor: pointer;\">";
                            echo "<span class=\"day-number\">$day</span>";
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
    <section class="card day-details" id="details-<?= $dateStr ?>" style="display: none;">
        <div class="day-details-header">
            <h2>Beschikbare uren - <?= date('d F Y', strtotime($dateStr)) ?></h2>
            <button class="close-btn" onclick="closeDayDetails()"><i class="fa fa-times"></i></button>
        </div>
        
        <div class="time-schedule">
            <div class="time-header">
                <span>Tijd</span>
                <span>Beschikbaarheid</span>
            </div>
            
            <?php
            for ($hour = 8; $hour < 18; $hour++) {
                $timeStart = sprintf('%02d:00', $hour);
                $timeEnd = sprintf('%02d:00', $hour + 1);
                
                // Check if there's already a booking for this timeslot
                $isBooked = false;
                foreach ($bookings as $b) {
                    if ($b['booking_date'] === $dateStr) {
                        $bStart = (int)substr($b['start_time'], 0, 2);
                        $bEnd = (int)substr($b['end_time'], 0, 2);
                        if ($hour >= $bStart && $hour < $bEnd) {
                            $isBooked = true;
                            break;
                        }
                    }
                }
                
                $statusClass = $isWeekend ? 'weekend' : ($isBooked ? 'booked' : 'available');
                $statusText = $isWeekend ? 'Weekend gesloten' : ($isBooked ? 'Geboekt' : 'Beschikbaar');
                
                echo "<div class=\"time-slot $statusClass\" onclick=\"selectTime(this, '$timeStart', '$timeEnd', '$dateStr')\">";
                echo "<span class=\"time\">$timeStart - $timeEnd</span>";
                echo "<span class=\"status\">$statusText</span>";
                echo "</div>";
            }
            ?>
        </div>
    </section>
                <?php
                $day++;
            }
        }
    }
    ?>

    <!-- FORM -->
    <section class="card form-card">
        <h2>Nieuwe booking</h2>

        <form method="POST" id="bookingForm">
            <input type="date" name="booking_date" id="booking_date" required>

            <select name="location_id">
                <?php foreach ($locations as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= $l['name'] ?></option>
                <?php endforeach; ?>
            </select>

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

            <button type="submit" class="btn">Boek nu</button>
        </form>
    </section>

</div>

<!-- MOBILE FIXED BUTTON -->
<button class="fab" onclick="document.querySelector('.form-card').scrollIntoView()">
    <i class="fa fa-plus"></i>
</button>

<script>
let currentOpenDay = null;

function selectDay(dateStr, month, year) {
    // Update URL to include selected_day parameter
    window.location.href = `?month=${month}&year=${year}&selected_day=${dateStr}`;
}

function showDayDetails(dateStr) {
    // Close any previously open day details
    if (currentOpenDay) {
        document.getElementById('details-' + currentOpenDay).style.display = 'none';
    }
    
    // Show the details for the clicked day
    const detailsElement = document.getElementById('details-' + dateStr);
    if (detailsElement) {
        detailsElement.style.display = 'block';
        currentOpenDay = dateStr;
        
        // Smooth scroll to the details section
        setTimeout(() => {
            detailsElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
}

function closeDayDetails() {
    if (currentOpenDay) {
        document.getElementById('details-' + currentOpenDay).style.display = 'none';
        currentOpenDay = null;
    }
}

function selectTime(element, startTime, endTime, selectedDay) {
    // Don't allow selecting if weekend or already booked
    if (element.classList.contains('weekend') || element.classList.contains('booked')) {
        return;
    }
    
    // Get the parent section to remove selections only from that day's slots
    const parentSection = element.closest('.day-details');
    if (parentSection) {
        parentSection.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
    }
    
    // Add selection to clicked element
    element.classList.add('selected');
    
    // Update form fields
    document.getElementById('booking_date').value = selectedDay;
    document.getElementById('start_time').value = startTime;
    document.getElementById('end_time').value = endTime;
    
    // Scroll to form
    setTimeout(() => {
        document.querySelector('.form-card').scrollIntoView({ behavior: 'smooth' });
    }, 100);
}

// Initialize form with selected day if present
document.addEventListener('DOMContentLoaded', function() {
    const selectedDay = '<?= $selectedDay ?>';
    if (selectedDay) {
        document.getElementById('booking_date').value = selectedDay;
        
        // Also open the day details automatically
        showDayDetails(selectedDay);
    }
});
</script>

</body>
</html>