<?php
session_start();
require 'db.php';
require 'helpers.php';

$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';
$message = '';
$messageType = '';

$eventId = filter_input(INPUT_GET, 'event_id', FILTER_VALIDATE_INT);
$event = null;

try {
    $banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: $banner1;
    $banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: $banner2;

    if ($eventId) {
        $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
    }
} catch (Exception $e) {
    $event = null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($fullName === '' || $email === '') {
        $message = 'Vul je volledige naam en e-mailadres in.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Vul een geldig e-mailadres in.';
        $messageType = 'error';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO inschrijven (event_id, event_title, full_name, email, created_at) VALUES (?, ?, ?, ?, NOW())');
            $stmt->execute([$event['id'], $event['title'], $fullName, $email]);
            $message = 'Je inschrijving is ontvangen. Bedankt!';
            $messageType = 'success';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $message = 'Dit e-mailadres is al ingeschreven voor dit evenement.';
                $messageType = 'error';
            } else {
                $message = 'Er ging iets mis bij het inschrijven. Probeer het later opnieuw.';
                $messageType = 'error';
            }
        }
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><link rel="stylesheet" href="custom.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>
    <title>Inschrijven</title>
    <meta name="description" content="Inschrijven voor een evenement van SociaalAI Lab Rotterdam.">
    <link rel="icon" type="image/png" href="images/Pixels_icon.png">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">
<div class="banner-wrapper">
    <div class="banner banner-1 active">
        <img class="" src="<?php echo htmlspecialchars($banner1); ?>">
    </div>
    <div class="banner banner-2">
        <img class="" src="<?php echo htmlspecialchars($banner2); ?>">
    </div>
</div>

<nav class="bg-white shadow-md">
    <div class="navigatie max-w-6xl mx-auto px-4 py-3 flex justify-center md:justify-between items-center">
        <button id="mobile-menu-toggle" class=" hamburger md:hidden self-end text-gray-700 focus:outline-none" aria-label="Open navigatie" aria-expanded="false">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>

        <div id="mobile-menu" class="menu hidden md:flex pr-5 space-x-8 font-medium items-center">
            <a href="index.php" class="menu inline-flex items-center gap-1 text-gray-700 hover:text-[#00811F] transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="agenda.php" class="menu text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="event.php" class="menu text-gray-700 hover:text-[#00811F] transition">Evenementen</a>
            <a href="terugblikken.php" class="menu text-gray-700 hover:text-[#00811F] transition">Terugblikken</a>
            <a href="over.php" class="menu text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

            <div class="relative" id="programma-dropdown">
                <button id="programma-toggle" aria-haspopup="true" aria-expanded="false" class="menu flex items-center gap-2 text-gray-700 hover:text-[#00811F] transition font-medium focus:outline-none">
                    <span>Wat doen we?</span>
                </button>

                <div id="programma-menu" class="hidden absolute top-0 mt-8 w-56 bg-white border border-gray-200 shadow-lg py-2 z-50 focus:outline-none" role="menu" aria-labelledby="programma-toggle">
                    <a href="programma/kennis.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="programma/actie.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="programma/faciliteit.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab</a>
                </div>
            </div>

            <a href="verantwoord-ai.php" class="menu text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="wie-zijn-we.php" class="menu text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="contact.php" class="menu text-gray-700 hover:text-[#00811F] transition">Contact</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                <a href="admin.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="min-h-[60vh] flex items-start justify-center px-3 sm:px-6">
    <section class="bg-white shadow-xl p-6 sm:p-8 max-w-md w-full mx-auto my-8 sm:my-12 rounded-lg">
        <?php if (!$event): ?>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-2">Evenement niet gevonden</h1>
            <p class="text-gray-700 text-sm sm:text-base">Het evenement bestaat niet of is niet meer beschikbaar.</p>
        <?php else: ?>
            <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-2">Inschrijven</h1>
            <p class="text-gray-700 mb-6 text-sm sm:text-base">
                <?php echo htmlspecialchars($event['title']); ?>
                <?php if (!empty($event['date'])): ?>
                    - <?php echo htmlspecialchars(formatEventDateDisplay($event['date'])); ?>
                <?php endif; ?>
            </p>

            <?php if ($message): ?>
                <div class="mb-6 p-4 rounded text-sm sm:text-base <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label for="full_name" class="block text-gray-700 font-medium text-sm sm:text-base mb-2">Volledige naam</label>
                    <input type="text" id="full_name" name="full_name" required class="w-full px-4 py-3 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00811F] text-base">
                </div>
                <div>
                    <label for="email" class="block text-gray-700 font-medium text-sm sm:text-base mb-2">E-mailadres</label>
                    <input type="email" id="email" name="email" required class="w-full px-4 py-3 sm:py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00811F] text-base">
                </div>
                <button type="submit" class="w-full bg-[#00811F] text-white px-6 py-3 rounded-md hover:bg-green-700 transition font-medium text-base sm:text-sm">Inschrijven</button>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    (function() {
        const toggle = document.getElementById('programma-toggle');
        const menu = document.getElementById('programma-menu');
        const caret = document.getElementById('programma-caret');

        if (!toggle || !menu) return;

        function openMenu() {
            menu.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
            if (caret) {
                caret.classList.add('rotate-180');
            }
            const first = menu.querySelector('[role="menuitem"]');
            if (first) first.focus();
        }

        function closeMenu() {
            menu.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
            if (caret) {
                caret.classList.remove('rotate-180');
            }
            toggle.focus();
        }

        function toggleMenu() {
            if (menu.classList.contains('hidden')) openMenu();
            else closeMenu();
        }

        toggle.addEventListener('click', function(e){
            e.preventDefault();
            toggleMenu();
        });

        toggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleMenu();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (menu.classList.contains('hidden')) openMenu();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!menu.classList.contains('hidden')) closeMenu();
            }
        });

        document.addEventListener('click', function(e) {
            const target = e.target;
            if (!menu.contains(target) && !toggle.contains(target)) {
                if (!menu.classList.contains('hidden')) closeMenu();
            }
        });

        const items = menu.querySelectorAll('[role="menuitem"]');
        items.forEach(item => {
            item.setAttribute('tabindex', '0');
            item.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeMenu();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const next = item.nextElementSibling || menu.querySelector('[role="menuitem"]');
                    if (next) next.focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = item.previousElementSibling || menu.querySelector('[role="menuitem"]:last-child');
                    if (prev) prev.focus();
                }
            });
        });
    })();

    (function () {
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');

        if (!mobileToggle || !mobileMenu) return;

        mobileToggle.addEventListener('click', function () {
            const isHidden = mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('open', !isHidden);
            mobileToggle.setAttribute('aria-expanded', (!isHidden).toString());
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
