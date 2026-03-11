<?php 
session_start();
require 'db.php';
require 'helpers.php';

$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';

$fallbackBlocks = [
    [
        'title' => 'Voorbeeld Terugblik',
        'body' => 'Dit is een voorbeeld van een terugblik.',
        'image' => '',
        'meta' => null
    ]
];

try {
    $banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: $banner1;
    $banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: $banner2;

    // seed_page_blocks removed to prevent auto-creation
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'terugblikken' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
    $stmt->execute();
    $pageBlocks = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM events WHERE date < CURDATE() ORDER BY date DESC, time DESC");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $pageBlocks = [];
    $events = [];
}
?>

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><link rel="stylesheet" href="custom.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>Terugblikken</title>
    <meta name="description" content="SociaalAI helpt inwoners sterker te staan in een steeds digitalere wereld. We doen dit door Rotterdammers actief mee te laten denken, praten en beslissen over kunstmatige intelligentie.">
    <link rel="icon" type="image/png" href="images/Pixels_icon.png">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">

<div class="banner-wrapper">
    <img src="<?php echo htmlspecialchars($banner1); ?>" alt="Banner 1" class="banner active w-full object-cover h-60 md:h-96">
    <img src="<?php echo htmlspecialchars($banner2); ?>" alt="Banner 2" class="banner w-full object-cover h-60 md:h-96">
</div>

<nav class="bg-white shadow-md sticky top-0 z-40">
    <div class="flex justify-center items-center px-4 md:px-8 py-4">

        <button id="mobile-menu-toggle" class="md:hidden hamburger focus:outline-none" aria-label="Toggle navigation">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>

        <div id="mobile-menu" class="menu hidden md:flex pr-5 space-x-8 font-medium items-center">
            <a href="index.php" class="menu inline-flex items-center gap-1 text-gray-700 hover:text-[#00811F] transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="agenda.php" class="menu text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="over.php" class="menu text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

            <div class="relative" id="programma-dropdown">
                <button id="programma-toggle" aria-haspopup="true" aria-expanded="false" class="menu flex items-center gap-2 text-gray-700 hover:text-[#00811F] transition font-medium focus:outline-none">
                    <i class="fa-solid fa-caret-right text-xs" aria-hidden="true"></i>
                    <span>Wat doen we?</span>
                    
                </button>

                <div id="programma-menu" class="hidden absolute top-0 mt-8 w-56 bg-white border border-gray-200 shadow-lg py-2 z-50 focus:outline-none" role="menu" aria-labelledby="programma-toggle">
                    <a href="programma/kennis.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="programma/actie.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="programma/faciliteit.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab
                    </a>
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

<main class="pt-8 sm:pt-12">
    <div class="mobile flex items-center justify-center">
         <div class="bg-white p-6 shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="agenda.php"><h1 class="text-2xl text-[#00000] font-semibold">Agenda</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="terugblikken.php"><h1 class="text-2xl text-[#00811F] font-semibold">Terugblik</h1></a>
        </div>
    </div>

    <?php
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'terugblikken' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
    $stmt->execute();
    $pageBlocks = $stmt->fetchAll();
    foreach ($pageBlocks as $block):
        $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
        $hasImage = !empty($block['image']);
        $hasText = !empty($block['title']) || !empty($block['body']);
        $imagePosition = $metaArr['image_position'] ?? 'normal';
        if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) $imagePosition = 'normal';
        
        $flexDir = 'column';
        if ($imagePosition === 'left' && $hasText) {
            $flexDir = 'row';
        } elseif ($imagePosition === 'right' && $hasText) {
            $flexDir = 'row-reverse';
        }
        $sectionStyle = "display: flex; flex-direction: " . $flexDir . "; ";
        if ($imagePosition !== 'normal' && $hasText) {
            $sectionStyle .= "gap: 2rem; align-items: center;";
        } else {
            $sectionStyle .= "gap: 1.5rem;";
        }
    ?>
        <section class="bg-white shadow-lg px-3 sm:px-8 py-6 sm:py-8 max-w-6xl mx-auto mt-8 sm:mt-12 mb-6 sm:mb-12" style="<?php echo $sectionStyle; ?>">
            <?php if ($hasImage): ?>
                <?php
                $imageStyle = '';
                if (!$hasText) {
                    $imageStyle = 'width: 100%;';
                } elseif ($imagePosition !== 'normal') {
                    $imageStyle = 'flex: 0 0 50%; max-width: 600px;';
                } else {
                    $imageStyle = 'width: 100%; max-width: 600px;';
                }
                ?>
                <div style="<?php echo $imageStyle; ?>">
                    <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title']); ?>" style="width: 100%; height: auto; border-radius: 0.5rem;">
                </div>
            <?php endif; ?>
            <?php if ($hasText): ?>
                <div style="<?php echo ($imagePosition !== 'normal' && $hasImage) ? 'flex: 0 0 50%; padding: 0 1.5rem;' : ''; ?>">
                    <?php if (!empty($block['title'])): ?>
                        <h3 class="text-xl sm:text-2xl font-semibold mb-4 text-gray-900"><?php echo htmlspecialchars($block['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($block['body'])): ?>
                        <div class="text-sm sm:text-base text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($block['body'])); ?></div>
                    <?php endif; ?>
                <?php if (!empty($metaArr['date']) || !empty($metaArr['time'])): ?>
                    <div class="text-gray-600 text-xs sm:text-sm mt-2 flex flex-col sm:flex-row gap-2 sm:gap-4">
                        <?php if (!empty($metaArr['date'])): ?>
                            <span><strong>Datum:</strong> <?php echo htmlspecialchars(formatEventDateDisplay($metaArr['date'])); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($metaArr['time'])): ?>
                            <span><strong>Tijd:</strong> <?php echo htmlspecialchars(formatEventTimeDisplay($metaArr['time'])); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php
    require 'db.php';
    $stmt = $pdo->prepare("SELECT * FROM events WHERE date < CURDATE() ORDER BY date DESC, time DESC");
    $stmt->execute();
    $events = $stmt->fetchAll();
    
    if (empty($events)):
    ?>
        <section class="bg-white shadow-lg p-8 max-w-6xl mx-auto my-12 text-center">
            <p class="text-gray-700">Er zijn nog geen voorbije evenementen.</p>
        </section>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
            <span class="inline-block bg-[#00811F] text-white text-sm font-medium px-4 py-1 mb-4">Evenement</span>
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900"><?php echo htmlspecialchars($event['title']); ?></h2>
            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <i class="fa-regular fa-calendar text-[#00811F] ml-[2px]  text-3xl"></i>
                    <?php $dateDisplay = formatEventDateDisplay($event['date']); $timeDisplay = $event['time'] ? formatEventTimeDisplay($event['time']) : ''; ?>
                    <p class="text-gray-700"><strong> Wanneer:</strong> <?php echo htmlspecialchars($dateDisplay); ?> <?php echo $timeDisplay ? '- ' . htmlspecialchars($timeDisplay) : ''; ?></p>
                </div>
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-location-dot text-[#00811F] ml-1 text-3xl"></i>
                    <?php $loc = $event['location'] ?: 'Rotterdam - Hillevliet 90'; ?>
                    <?php $mapsLocationUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode((string)$loc); ?>
                    <p class="text-gray-700 ml-1 "><strong>Waar:</strong> <a href="<?php echo htmlspecialchars($mapsLocationUrl); ?>" target="_blank" rel="noopener noreferrer" class="underline hover:text-[#00811F]"><?php echo htmlspecialchars($loc); ?></a></p>
                </div>
                <div class="flex mb-6 space-x-3">
                    <i class="fa-solid fa-bullseye text-[#00811F] text-3xl"></i>
                    <p class="text-gray-700 pb-3 "><strong> Wat:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>
            </div>
        </div>
        <?php if ($event['image']): ?>
        <div class="flex-1">
            <img src="uploads/<?php echo htmlspecialchars($event['image']); ?>" alt="" class="w-full h-auto object-cover shadow-md">
        </div>
        <?php endif; ?>
    </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    (function() {
        const toggle = document.getElementById('programma-toggle');
        const menu = document.getElementById('programma-menu');

        if (!toggle || !menu) return;

        function openMenu() {
            menu.classList.remove('hidden');
            toggle.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
            menu.classList.add('hidden');
            toggle.setAttribute('aria-expanded', 'false');
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

<style>
.banner-wrapper {
  display: grid;
}

.banner {
  grid-area: 1 / 1;
}

.banner {
  opacity: 0;
  transition: opacity 1s ease;
}

.banner.active {
  opacity: 1;
}

@media (max-width: 1024px) {
    .menu{
        margin-top: 10px;
        margin-bottom: 10px;
    }
    .navigatie{
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
        align-items: flex-start;
    }
}

@media (min-width: 760px) {
    .hamburger{
        display: none;
    }
}

.text-block-content{
    padding: 30px;
}
</style>
