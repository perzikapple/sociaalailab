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
        <a href="?view=week&date=<?= $selectedDate ?>">Week</a>
        <a href="?view=day&date=<?= $selectedDate ?>">Dag</a>
    </div>
</header>

<div class="container">

    <!-- CALENDAR -->
    <section class="card">
        <h2>Planning</h2>

        <div class="calendar">
            <?php for ($i=0;$i<7;$i++):
                $d = (clone $weekStart)->modify("+$i day");
                $date = $d->format('Y-m-d');
                ?>
                <a class="day" href="?view=day&date=<?= $date ?>">
                    <span><?= $d->format('D') ?></span>
                    <strong><?= $d->format('d') ?></strong>
                </a>
            <?php endfor; ?>
        </div>
    </section>

    <!-- BOOKINGS -->
    <section class="card">
        <h2>Boekingen</h2>

        <?php foreach ($bookings as $b): ?>
            <?php $loc = findById($locations, $b['location_id']); ?>
            <div class="booking">
                <div class="bar" style="background:<?= $loc['color'] ?>"></div>
                <div>
                    <strong><?= $loc['name'] ?></strong><br>
                    <?= $b['booking_date'] ?> | <?= $b['start_time'] ?> - <?= $b['end_time'] ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- FORM -->
    <section class="card form-card">
        <h2>Nieuwe booking</h2>

        <form method="POST">
            <input type="date" name="booking_date" required>

            <select name="location_id">
                <?php foreach ($locations as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= $l['name'] ?></option>
                <?php endforeach; ?>
            </select>

            <div class="row">
                <input type="time" name="start_time" required>
                <input type="time" name="end_time" required>
            </div>

            <div class="chips">
                <?php foreach ($hardware as $h): ?>
                    <label>
                        <input type="checkbox" name="hardware[]" value="<?= $h['id'] ?>">
                        <?= $h['name'] ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <button class="btn">Boek nu</button>
        </form>
    </section>

</div>

<!-- MOBILE FIXED BUTTON -->
<button class="fab" onclick="document.querySelector('.form-card').scrollIntoView()">
    <i class="fa fa-plus"></i>
</button>

</body>
</html>