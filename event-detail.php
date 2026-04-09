<?php
session_start();
require 'db.php';
require 'helpers.php';

$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';

try {
    $banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: $banner1;
    $banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: $banner2;
} catch (Exception $e) {
    // Gebruik fallback banners.
}

$eventId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$event = null;

if ($eventId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
    $stmt->execute([$eventId]);
    $event = $stmt->fetch();
}

$isPastEvent = false;
$eventSummary = '';

if ($event) {
    $compareDate = !empty($event['end_date']) ? $event['end_date'] : $event['date'];
    $today = new DateTime('today');
    $end = DateTime::createFromFormat('Y-m-d', (string)$compareDate);
    if ($end instanceof DateTime) {
        $end->setTime(0, 0, 0);
        $isPastEvent = $end < $today;
    }

    if ($isPastEvent) {
        $eventSummary = eventNarrativeSummary(
            $event['title'] ?? '',
            $event['description'] ?? '',
            $event['date'] ?? null,
            $event['location'] ?? null,
            850
        );
        if ($eventSummary === '') {
            $eventSummary = 'Samenvatting volgt binnenkort.';
        }
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>
    <title>Evenement detail</title>
    <meta name="description" content="Meer informatie over het evenement van SociaalAI Lab.">
    <link rel="icon" type="image/png" href="images/Pixels_icon.png">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">

<div class="banner-wrapper">
    <div class="banner banner-1 active">
        <img src="<?php echo htmlspecialchars($banner1); ?>" alt="Banner 1">
    </div>
    <div class="banner banner-2">
        <img src="<?php echo htmlspecialchars($banner2); ?>" alt="Banner 2">
    </div>
</div>

<?php
$navPrefix = '';
include __DIR__ . '/navbar.php';
?>

<main>
    <section class="bg-white shadow-lg p-8 max-w-4xl mx-auto my-12">
        <?php if (!$event): ?>
            <h1 class="text-2xl font-semibold mb-3 text-gray-900">Evenement niet gevonden</h1>
            <p class="text-gray-700 mb-6">Dit evenement bestaat niet meer of de link is onjuist.</p>
            <a href="agenda.php#agenda-terugblik-switch" class="inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">Terug naar agenda</a>
        <?php else: ?>
            <?php
                $dateDisplay = formatEventDateDisplay($event['date']);
                $endDateDisplay = !empty($event['end_date']) ? formatEventDateDisplay($event['end_date']) : null;
                $timeDisplay = !empty($event['time']) ? formatEventTimeDisplay($event['time']) : '';
                $timeEndDisplay = !empty($event['time_end']) ? formatEventTimeDisplay($event['time_end']) : '';
                $location = !empty($event['location']) ? $event['location'] : 'Rotterdam - Hillevliet 90';
            ?>

            <span class="inline-block text-white text-sm font-medium px-4 py-1 mb-4" style="background-color:#ce0245;">Evenement info</span>
            <h1 class="text-3xl md:text-4xl font-semibold mb-4 text-gray-900"><?php echo renderEditorInline($event['title']); ?></h1>

            <div class="space-y-3 mb-6">
                <p class="text-gray-700"><strong>Wanneer:</strong> <?php echo htmlspecialchars($dateDisplay); ?><?php if ($endDateDisplay) { echo ' t/m ' . htmlspecialchars($endDateDisplay); } ?></p>
                <?php if ($timeDisplay || $timeEndDisplay): ?>
                    <p class="text-gray-700"><strong>Tijd:</strong> <?php echo htmlspecialchars($timeDisplay); ?><?php if ($timeEndDisplay) { echo ' - ' . htmlspecialchars($timeEndDisplay); } ?></p>
                <?php endif; ?>
                <p class="text-gray-700"><strong>Locatie:</strong> <?php echo htmlspecialchars($location); ?></p>
            </div>

            <?php if (!empty($event['image'])): ?>
                <div class="mb-6">
                    <img src="uploads/<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars((string)$event['title']); ?>" class="w-full md:w-2/3 h-auto object-cover shadow-md mx-auto">
                </div>
            <?php endif; ?>

            <div class="text-gray-700 mb-8 leading-relaxed">
                <?php echo renderEditorBlock($event['description']); ?>
            </div>

            <?php if (!empty($event['show_signup_button'])): ?>
                <div class="mb-6">
                    <a href="inschrijven.php?event_id=<?php echo (int)$event['id']; ?>" class="inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">Inschrijven</a>
                </div>
            <?php endif; ?>

            <?php if ($isPastEvent): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-md p-6 mb-6">
                    <h2 class="text-2xl font-semibold mb-3 text-gray-900">Samenvatting</h2>
                    <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($eventSummary); ?></p>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 border border-gray-200 rounded-md p-6 mb-6">
                    <h2 class="text-2xl font-semibold mb-3 text-gray-900">Samenvatting</h2>
                    <p class="text-gray-700 leading-relaxed">Na afloop van dit evenement verschijnt hier een samenvatting.</p>
                </div>
            <?php endif; ?>

            <div class="flex flex-wrap gap-3">
                <a href="agenda.php#agenda-terugblik-switch" class="inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">Terug naar agenda</a>
                <?php if (!empty($event['info_link'])): ?>
                    <a href="<?php echo htmlspecialchars($event['info_link']); ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center bg-[#ce0245] text-white font-semibold px-6 py-3 rounded-md shadow hover:opacity-90 transition">Externe info</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    (function() {
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        const menu = document.getElementById('nav-menu');
        if (!mobileToggle || !menu) return;

        mobileToggle.addEventListener('click', function () {
            menu.classList.toggle('show');
        });
    })();

    const banners = document.querySelectorAll('.banner');
    let current = 0;

    setInterval(() => {
      banners[current].classList.remove('active');
      current = (current + 1) % banners.length;
      banners[current].classList.add('active');
    }, 10000);
</script>

</body>
</html>
