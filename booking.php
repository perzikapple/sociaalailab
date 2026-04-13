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

// Ensure valid month/year
if ($currentMonth < 1 || $currentMonth > 12) $currentMonth = date('n');
if ($currentYear < date('Y') || $currentYear > date('Y') + 2) $currentYear = date('Y');

$firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
$lastDay = mktime(0, 0, 0, $currentMonth + 1, 0, $currentYear);
$daysInMonth = date('t', $firstDay);
$startingDayOfWeek = date('w', $firstDay);
$monthName = date('F Y', $firstDay);

// Locations
$locations = [
    ['id' => 1, 'name' => 'Grote Zaal', 'color' => '#00811F'],
    ['id' => 2, 'name' => 'Kleine Workshop Ruimte', 'color' => '#0066CC'],
];

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

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_booking') {
        // TODO: Implement booking creation logic
        $message = 'Boeking functionaliteit wordt nog gebouwd.';
    }
}

?>
<!doctype html>
<html lang="nl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Booking Agenda</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
    <link rel="stylesheet" href="admin-styles.css?v=<?php echo filemtime(__DIR__ . '/admin-styles.css'); ?>">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style/booking.css?v=<?php echo filemtime(__DIR__ . '/style/booking.css'); ?>">
</head>

<body>
    <nav class="navbar">
        <div class="admin-header-inner flex justify-between items-center px-8 glass-header">
            <div class="admin-header-brand flex items-center gap-3">
                <span class="icon-glass"><i class="fa-solid fa-calendar-check"></i></span>
                <h1 class="text-3xl font-extrabold gradient-text drop-shadow-lg">Booking Agenda</h1>
            </div>
            <div class="admin-header-actions flex items-center gap-3 flex-nowrap">
                <a href="admin.php" class="btn btn-premium">
                    <i class="fa-solid fa-cogs"></i> <span class="hide-sm">Admin Panel</span>
                </a>
                <a href="index.php" class="btn btn-premium">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hide-sm">Terug naar site</span>
                </a>
                <a href="logout.php" class="btn btn-premium">
                    <i class="fa-solid fa-sign-out-alt"></i> <span class="hide-sm">Uitloggen</span>
                </a>
            </div>
        </div>
    </nav>

    <div class="booking-container">
        <!-- Header -->
        <div class="booking-header glass-card">
            <h1 class="text-2xl font-extrabold gradient-text mb-2"><span class="icon-glass"><i class="fa-solid fa-calendar-days"></i></span> Ruimte Booking</h1>
            <p class="subtitle-premium">Plan uw vergadering of event in één van onze ruimtes</p>
        </div>

        <!-- Calendar and Hardware -->
        <div class="booking-content">
            <!-- Calendar -->
            <div class="calendar-wrapper">
                <div class="calendar-nav">
                    <div>
                        <a href="?month=<?php echo $currentMonth === 1 ? 12 : $currentMonth - 1; ?>&year=<?php echo $currentMonth === 1 ? $currentYear - 1 : $currentYear; ?>" style="color: inherit; text-decoration: none;">
                            <button <?php echo ($currentMonth === date('n') && $currentYear === date('Y')) ? 'disabled' : ''; ?>>
                                <i class="fa-solid fa-chevron-left"></i> Vorige
                            </button>
                        </a>
                    </div>
                    <h2><?php echo htmlspecialchars($monthName); ?></h2>
                    <div>
                        <a href="?month=<?php echo $currentMonth === 12 ? 1 : $currentMonth + 1; ?>&year=<?php echo $currentMonth === 12 ? $currentYear + 1 : $currentYear; ?>" style="color: inherit; text-decoration: none;">
                            <button <?php echo ($currentMonth === 12 && $currentYear >= date('Y') + 2) ? 'disabled' : ''; ?>>
                                Volgende <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </a>
                    </div>
                </div>

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
                                    foreach ($locations as $location) {
                                        // Example: alternate availability
                                        $available = ((($dayCounter + $location['id']) % 3) !== 0);
                                        $status = $available ? 'Beschikbaar' : 'Bezet';
                                        $class = $available ? 'available' : 'unavailable';
                                        echo '<div class="room-slot ' . $class . '" style="background-color: ' . htmlspecialchars($location['color']) . '">' . htmlspecialchars($location['name']) . '</div>';
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
            </div>

            <!-- Sidebar -->
            <div class="sidebar">
                <!-- Legend -->
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