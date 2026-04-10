<?php
session_start();
require 'db.php';
require 'helpers.php';

$canAccessAdmin = (bool)($_SESSION['can_access_admin'] ?? false);
if (!$canAccessAdmin) {
    header('Location: login.php');
    exit;
}

// Current month and year
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$view = isset($_GET['view']) ? trim($_GET['view']) : 'month';
$selectedDate = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

// Validate view
if (!in_array($view, ['month', 'week', 'day'])) {
    $view = 'month';
}

// Ensure valid month/year
if ($currentMonth < 1 || $currentMonth > 12) $currentMonth = date('n');
if ($currentYear < date('Y') || $currentYear > date('Y') + 2) $currentYear = date('Y');

$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$lastDay = mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear);
$daysInMonth = date('t', $firstDay);
$startingDayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Week calculations
$selectedDateTime = DateTime::createFromFormat('Y-m-d', $selectedDate) ?: new DateTime();
$weekStart = (clone $selectedDateTime)->modify('monday this week');
$weekEnd = (clone $weekStart)->modify('+6 days');
$weekNumber = $weekStart->format('W');

// Calculate previous and next week dates for navigation
$prevWeekStart = (clone $weekStart)->modify('-1 week');
$nextWeekStart = (clone $weekStart)->modify('+1 week');

// Day info
$dayOfWeek = $selectedDateTime->format('l');
$dayDisplay = $selectedDateTime->format('d F Y');

// Locations
$locations = [
    ['id' => 1, 'name' => 'Grote Zaal', 'color' => '#00811F'],
    ['id' => 2, 'name' => 'Kleine Workshop Ruimte', 'color' => '#0066CC'],
];

// Helper function to find item by id
function findById($array, $id) {
    foreach ($array as $item) {
        if ($item['id'] == $id) {
            return $item;
        }
    }
    return null;
}

// Hardware items
$hardware = [
    ['id' => 1, 'name' => 'Projector', 'quantity' => 2],
    ['id' => 2, 'name' => 'Whiteboard', 'quantity' => 3],
    ['id' => 3, 'name' => 'Microfoon', 'quantity' => 4],
    ['id' => 4, 'name' => 'Speakerset', 'quantity' => 2],
    ['id' => 5, 'name' => 'Laptop', 'quantity' => 5],
    ['id' => 6, 'name' => 'Camera', 'quantity' => 2],
];

$message = '';

// Ensure bookings table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        location_id INT NOT NULL,
        booking_date DATE NOT NULL,
        hardware_ids JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
    // Table might already exist
}

// Get bookings for wider range (month + extra)
$bookings = [];
try {
    $firstOfMonth = sprintf('%04d-%02d-01', $currentYear, $currentMonth);
    $lastOfMonth = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $daysInMonth);
    
    // Get bookings for month (and potentially wider range for views)
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_date BETWEEN ? AND ?");
    $stmt->execute([$firstOfMonth, $lastOfMonth]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Also get bookings for week if in week view
    if ($view === 'week' || $view === 'day') {
        $weekStartStr = $weekStart->format('Y-m-d');
        $weekEndStr = $weekEnd->format('Y-m-d');
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_date BETWEEN ? AND ?");
        $stmt->execute([$weekStartStr, $weekEndStr]);
        $weekBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Merge and deduplicate
        $allDates = array_merge(
            array_column($bookings, 'booking_date'),
            array_column($weekBookings, 'booking_date')
        );
        $uniqueDates = array_unique($allDates);
        
        if (count($uniqueDates) === count(array_merge($bookings, $weekBookings))) {
            $bookings = array_merge($bookings, $weekBookings);
        }
    }
} catch (Exception $e) {
    // Query might fail if table doesn't exist yet
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_booking') {
        try {
            $location_id = isset($_POST['location_id']) ? (int)$_POST['location_id'] : 0;
            $booking_date = isset($_POST['booking_date']) ? trim($_POST['booking_date']) : '';
            $hardware_ids = isset($_POST['hardware']) ? (array)$_POST['hardware'] : [];
            
            // Sanitize hardware IDs
            $hardware_ids = array_map(function($id) { return (int)$id; }, $hardware_ids);
            
            if ($location_id > 0 && !empty($booking_date)) {
                // Check if room is already booked on this date
                $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE location_id = ? AND booking_date = ?");
                $checkStmt->execute([$location_id, $booking_date]);
                $roomCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($roomCheck['count'] > 0) {
                    $message = 'Deze ruimte is al geboekt op deze datum!';
                } else {
                    // Check hardware availability
                    $hardwareIssue = false;
                    $hardwareErrors = [];
                    
                    if (!empty($hardware_ids)) {
                        // Count hardware usage on this date
                        $hardwareUsage = []; // [hardware_id => count]
                        
                        // Count already booked hardware on this date
                        $stmt = $pdo->prepare("SELECT hardware_ids FROM bookings WHERE booking_date = ?");
                        $stmt->execute([$booking_date]);
                        $existingBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($existingBookings as $booking) {
                            $bookedHardware = json_decode($booking['hardware_ids'] ?? '[]', true);
                            foreach ($bookedHardware as $hw_id) {
                                $hw_id = (int)$hw_id;
                                $hardwareUsage[$hw_id] = ($hardwareUsage[$hw_id] ?? 0) + 1;
                            }
                        }
                        
                        // Check if requested hardware fits
                        foreach ($hardware_ids as $hw_id) {
                            $hw = findById($hardware, $hw_id);
                            $currentUsage = $hardwareUsage[$hw_id] ?? 0;
                            
                            if ($currentUsage >= $hw['quantity']) {
                                $hardwareIssue = true;
                                $hardwareErrors[] = $hw['name'] . ' (slechts ' . $hw['quantity'] . ' beschikbaar)';
                            }
                        }
                    }
                    
                    if ($hardwareIssue) {
                        $message = 'Volgende hardware is niet meer beschikbaar: ' . implode(', ', $hardwareErrors);
                    } else {
                        // All checks passed, create booking
                        $stmt = $pdo->prepare("INSERT INTO bookings (location_id, booking_date, hardware_ids) VALUES (?, ?, ?)");
                        $stmt->execute([$location_id, $booking_date, json_encode($hardware_ids)]);
                        $message = 'Boeking succesvol aangemaakt!';
                        
                        // Refresh bookings
                        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE booking_date BETWEEN ? AND ?");
                        $firstOfMonth = sprintf('%04d-%02d-01', $currentYear, $currentMonth);
                        $lastOfMonth = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $daysInMonth);
                        $stmt->execute([$firstOfMonth, $lastOfMonth]);
                        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }
                }
            } else {
                $message = 'Vul alle verplichte velden in.';
            }
        } catch (Exception $e) {
            $message = 'Fout bij het aanmaken van boeking: ' . $e->getMessage();
        }
    }
}

?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Booking Agenda</title>
<link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
<link rel="stylesheet" href="admin-styles.css?v=<?php echo filemtime(__DIR__.'/admin-styles.css'); ?>">
<link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="style/booking.css?v=<?php echo filemtime(__DIR__.'/style/booking.css'); ?>">
</head>
<body>
<nav class="navbar">
    <div class="admin-header-inner flex justify-between items-center px-8">
        <div class="admin-header-brand">
            <i class="fa-solid fa-calendar-check text-2xl"></i>
            <h1 class="text-2xl font-bold">Booking Agenda</h1>
        </div>
        <div class="admin-header-actions flex items-center gap-3 flex-nowrap">
            <a href="admin.php" class="btn btn-secondary text-sm">
                <i class="fa-solid fa-cogs"></i> Admin Panel
            </a>
            <a href="index.php" class="btn btn-secondary text-sm">
                <i class="fa-solid fa-arrow-left"></i> Terug naar site
            </a>
            <a href="logout.php" class="btn btn-secondary text-sm">
                <i class="fa-solid fa-sign-out-alt"></i> Uitloggen
            </a>
        </div>
    </div>
</nav>

<div class="booking-container">
    <!-- Header -->
    <div class="booking-header">
        <h1><i class="fa-solid fa-calendar-days"></i> Ruimte Booking</h1>
        <p>Plan uw vergadering of event in één van onze ruimtes</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="booking-message <?php echo strpos($message, 'succesvol') !== false ? 'success' : 'error'; ?>">
            <i class="fa-solid fa-<?php echo strpos($message, 'succesvol') !== false ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- View Selector -->
    <div class="view-selector">
        <a href="?view=month&month=<?php echo $currentMonth; ?>&year=<?php echo $currentYear; ?>" class="view-btn <?php echo $view === 'month' ? 'active' : ''; ?>">
            <i class="fa-solid fa-calendar"></i> Maand
        </a>
        <a href="?view=week&date=<?php echo $selectedDate; ?>" class="view-btn <?php echo $view === 'week' ? 'active' : ''; ?>">
            <i class="fa-solid fa-calendar-week"></i> Week
        </a>
        <a href="?view=day&date=<?php echo $selectedDate; ?>" class="view-btn <?php echo $view === 'day' ? 'active' : ''; ?>">
            <i class="fa-solid fa-calendar-day"></i> Dag
        </a>
    </div>
    
    <!-- Day Preview -->
    <div class="day-preview">
        <div class="day-preview-content">
            <div class="day-preview-date">
                <div class="day-preview-day"><?php echo htmlspecialchars($dayOfWeek); ?></div>
                <div class="day-preview-date-text"><?php echo htmlspecialchars($dayDisplay); ?></div>
            </div>
            <div class="day-preview-bookings">
                <h4>Boekingen deze dag:</h4>
                <?php
                $dayBookings = array_filter($bookings, function($booking) use ($selectedDate) {
                    return $booking['booking_date'] === $selectedDate;
                });
                
                if (empty($dayBookings)):
                ?>
                    <p class="no-bookings">Geen boekingen vandaag</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($dayBookings as $booking): ?>
                            <?php $location = findById($locations, $booking['location_id']); ?>
                            <li style="border-left: 4px solid <?php echo htmlspecialchars($location['color'] ?? '#999'); ?>">
                                <strong><?php echo htmlspecialchars($location['name'] ?? '?'); ?></strong>
                                <?php
                                $hardwareList = json_decode($booking['hardware_ids'] ?? '[]', true);
                                if (!empty($hardwareList)) {
                                    $hardwareItems = [];
                                    foreach ($hardwareList as $hw_id) {
                                        $hw = findById($hardware, $hw_id);
                                        if ($hw) $hardwareItems[] = $hw['name'];
                                    }
                                    if (!empty($hardwareItems)) {
                                        echo '<br><small>' . htmlspecialchars(implode(', ', $hardwareItems)) . '</small>';
                                    }
                                }
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Calendar and Hardware -->
    <div class="booking-content">
        <!-- Calendar Column -->
        <div class="calendar-column">
            <!-- Calendar -->
        <div class="calendar-wrapper">
            <div class="calendar-nav">
                <div>
                    <?php if ($view === 'month'): ?>
                        <a href="?month=<?php echo $currentMonth === 1 ? 12 : $currentMonth - 1; ?>&year=<?php echo $currentMonth === 1 ? $currentYear - 1 : $currentYear; ?>&view=month" style="color: inherit; text-decoration: none;">
                            <button <?php echo ($currentMonth === date('n') && $currentYear === date('Y')) ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-chevron-left"></i> Vorige
                            </button>
                        </a>
                    <?php elseif ($view === 'week'): ?>
                        <a href="?date=<?php echo $prevWeekStart->format('Y-m-d'); ?>&view=week" style="color: inherit; text-decoration: none;">
                            <button>
                                <i class="fa-solid fa-chevron-left"></i> Vorige
                            </button>
                        </a>
                    <?php elseif ($view === 'day'): ?>
                        <a href="?date=<?php echo date('Y-m-d', strtotime($selectedDate . ' -1 day')); ?>&view=day" style="color: inherit; text-decoration: none;">
                            <button>
                                <i class="fa-solid fa-chevron-left"></i> Vorige
                            </button>
                        </a>
                    <?php endif; ?>
                </div>
                <h2>
                    <?php
                    if ($view === 'month') {
                        echo htmlspecialchars($monthName);
                    } elseif ($view === 'week') {
                        echo 'Week ' . $weekNumber . ' - ' . $weekStart->format('d M') . ' t/m ' . $weekEnd->format('d M Y');
                    } elseif ($view === 'day') {
                        echo $dayOfWeek . ' ' . htmlspecialchars($dayDisplay);
                    }
                    ?>
                </h2>
                <div>
                    <?php if ($view === 'month'): ?>
                        <a href="?month=<?php echo $currentMonth === 12 ? 1 : $currentMonth + 1; ?>&year=<?php echo $currentMonth === 12 ? $currentYear + 1 : $currentYear; ?>&view=month" style="color: inherit; text-decoration: none;">
                            <button <?php echo ($currentMonth === 12 && $currentYear >= date('Y') + 2) ? 'disabled' : ''; ?>>
                                Volgende <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </a>
                    <?php elseif ($view === 'week'): ?>
                        <a href="?date=<?php echo $nextWeekStart->format('Y-m-d'); ?>&view=week" style="color: inherit; text-decoration: none;">
                            <button>
                                Volgende <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </a>
                    <?php elseif ($view === 'day'): ?>
                        <a href="?date=<?php echo date('Y-m-d', strtotime($selectedDate . ' +1 day')); ?>&view=day" style="color: inherit; text-decoration: none;">
                            <button>
                                Volgende <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($view === 'month'): ?>
                <!-- MONTH VIEW -->
                <table class="calendar-table">
                <thead>
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $dayCounter = 1;
                    $prevMonthDays = date('t', mktime(0, 0, 0, $currentMonth - 1, 1, $currentYear));
                    
                    for ($week = 0; $week < 6; $week++) {
                        echo '<tr>';
                        for ($day = 0; $day < 7; $day++) {
                            $cellNum = $week * 7 + $day;
                            
                            if ($cellNum < $startingDayOfWeek) {
                                // Previous month
                                $date = $prevMonthDays - ($startingDayOfWeek - $cellNum - 1);
                                echo '<td class="other-month"><div class="day-number">' . $date . '</div></td>';
                            } elseif ($dayCounter > $daysInMonth) {
                                // Next month
                                echo '<td class="other-month"><div class="day-number">' . ($dayCounter - $daysInMonth) . '</div></td>';
                                $dayCounter++;
                            } else {
                                // Current month
                                $date = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $dayCounter);
                                echo '<td>';
                                echo '<div class="day-number">' . $dayCounter . '</div>';
                                
                                // Show only booked rooms for this day
                                $dayBookings = array_filter($bookings, function($booking) use ($date) {
                                    return $booking['booking_date'] === $date;
                                });
                                
                                foreach ($dayBookings as $booking) {
                                    $location = findById($locations, $booking['location_id']);
                                    if ($location) {
                                        $hardwareList = json_decode($booking['hardware_ids'] ?? '[]', true);
                                        $hardwareName = '';
                                        if (!empty($hardwareList)) {
                                            $hardwareItems = [];
                                            foreach ($hardwareList as $hid) {
                                                $hw = findById($hardware, $hid);
                                                if ($hw) {
                                                    $hardwareItems[] = $hw['name'];
                                                }
                                            }
                                            $hardwareName = ' (' . implode(', ', $hardwareItems) . ')';
                                        }
                                        echo '<div class="room-slot" style="background-color: ' . htmlspecialchars($location['color']) . '"><strong>' . htmlspecialchars($location['name']) . '</strong>' . htmlspecialchars($hardwareName) . '</div>';
                                    }
                                }
                                
                                echo '</td>';
                                $dayCounter++;
                            }
                        }
                        echo '</tr>';
                        if ($dayCounter > $daysInMonth) break;
                    }
                    ?>
                </tbody>
            </table>
            <?php elseif ($view === 'week'): ?>
                <!-- WEEK VIEW -->
                <div class="week-view">
                    <div class="week-header">
                        <div class="time-slot-header"></div>
                        <?php for ($d = 0; $d < 7; $d++): ?>
                            <?php 
                            $dayObj = (clone $weekStart)->modify("+$d days");
                            $dateStr = $dayObj->format('Y-m-d');
                            $dayName = $dayObj->format('l');
                            $dayNum = $dayObj->format('d');
                            ?>
                            <div class="week-day-header" data-date="<?php echo $dateStr; ?>" onclick="window.location.href='?view=day&date=<?php echo $dateStr; ?>'">
                                <div class="week-day-name"><?php echo substr($dayName, 0, 3); ?></div>
                                <div class="week-day-number"><?php echo $dayNum; ?></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                    
                    <div class="week-body">
                        <div class="time-slots">
                            <?php for ($hour = 8; $hour < 18; $hour++): ?>
                                <div class="time-slot"><?php echo str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00'; ?></div>
                            <?php endfor; ?>
                        </div>
                        
                        <?php foreach ($locations as $location): ?>
                            <div class="week-room">
                                <div class="room-title" style="background-color: <?php echo htmlspecialchars($location['color']); ?>">
                                    <?php echo htmlspecialchars($location['name']); ?>
                                </div>
                                <div class="room-slots">
                                    <?php for ($d = 0; $d < 7; $d++): ?>
                                        <?php 
                                        $dayObj = (clone $weekStart)->modify("+$d days");
                                        $dateStr = $dayObj->format('Y-m-d');
                                        $dayBookings = array_filter($bookings, function($booking) use ($dateStr, $location) {
                                            return $booking['booking_date'] === $dateStr && $booking['location_id'] === $location['id'];
                                        });
                                        ?>
                                        <div class="room-slot-column">
                                            <?php if (!empty($dayBookings)): ?>
                                                <?php foreach ($dayBookings as $booking): ?>
                                                    <div class="booked-slot" style="background-color: <?php echo htmlspecialchars($location['color']); ?>">
                                                        <small>Geboekt</small>
                                                        <?php
                                                        $hardwareList = json_decode($booking['hardware_ids'] ?? '[]', true);
                                                        if (!empty($hardwareList)) {
                                                            $hardwareItems = [];
                                                            foreach ($hardwareList as $hw_id) {
                                                                $hw = findById($hardware, $hw_id);
                                                                if ($hw) $hardwareItems[] = $hw['name'];
                                                            }
                                                            if (!empty($hardwareItems)) {
                                                                echo '<small>' . htmlspecialchars(implode(', ', $hardwareItems)) . '</small>';
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="empty-slot"></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($view === 'day'): ?>
                <!-- DAY VIEW -->
                <div class="day-view">
                    <?php foreach ($locations as $location): ?>
                        <div class="day-room-section">
                            <h3 style="color: <?php echo htmlspecialchars($location['color']); ?>"><?php echo htmlspecialchars($location['name']); ?></h3>
                            <?php
                            $dayBookings = array_filter($bookings, function($booking) use ($selectedDate, $location) {
                                return $booking['booking_date'] === $selectedDate && $booking['location_id'] === $location['id'];
                            });
                            
                            if (!empty($dayBookings)):
                                foreach ($dayBookings as $booking):
                                    $hardwareList = json_decode($booking['hardware_ids'] ?? '[]', true);
                                    $hardwareItems = [];
                                    foreach ($hardwareList as $hw_id) {
                                        $hw = findById($hardware, $hw_id);
                                        if ($hw) $hardwareItems[] = $hw['name'];
                                    }
                            ?>
                                <div class="day-booking-item" style="border-left: 5px solid <?php echo htmlspecialchars($location['color']); ?>">
                                    <div class="booking-header">
                                        <strong><?php echo htmlspecialchars($location['name']); ?></strong>
                                    </div>
                                    <?php if (!empty($hardwareItems)): ?>
                                        <div class="booking-hardware">
                                            <strong>Hardware:</strong> <?php echo htmlspecialchars(implode(', ', $hardwareItems)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <p class="no-bookings">Geen boekingen voor deze ruimte vandaag</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Booking Form -->
        <div class="sidebar-card booking-form-card booking-form-below">
            <h3><i class="fa-solid fa-plus-circle"></i> Nieuwe Boeking</h3>
            <form method="POST" id="bookingForm">
                <input type="hidden" name="action" value="create_booking">
                
                <div class="form-group">
                    <label for="booking_date">Datum:</label>
                    <input type="date" id="booking_date" name="booking_date" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           max="<?php echo date('Y-m-d', strtotime('+2 years')); ?>">
                </div>
                
                <div class="form-group">
                    <label for="location_id">Ruimte:</label>
                    <select id="location_id" name="location_id" required>
                        <option value="">-- Selecteer ruimte --</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo (int)$location['id']; ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Hardware:</label>
                    <div class="hardware-checkboxes">
                        <?php foreach ($hardware as $item): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="hardware[]" value="<?php echo (int)$item['id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Boeking Maken</button>
            </form>
        </div>
        </div>
        
            <!-- Legend -->
            <div class="sidebar">
            <div class="sidebar-card">
                <h3><i class="fa-solid fa-map-location-dot"></i> Ruimtes</h3>
                <?php foreach ($locations as $location): ?>
                    <div class="legend-item">
                        <div class="color-box" style="background-color: <?php echo htmlspecialchars($location['color']); ?>; "></div>
                        <span><?php echo htmlspecialchars($location['name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Hardware -->
            <div class="sidebar-card">
                <h3><i class="fa-solid fa-laptop"></i> Hardware</h3>
                <ul class="hardware-list">
                    <?php foreach ($hardware as $item): ?>
                        <li><strong><?php echo (int)$item['quantity']; ?></strong>x <?php echo htmlspecialchars($item['name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
</body>
</html>
