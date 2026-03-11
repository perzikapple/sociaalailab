<?php 
session_start();
require 'db.php';
require 'helpers.php';

// Fallback banners
$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';

$fallbackBlocks = [
    [
        'title' => 'Evenementen',
        'body' => 'Dit is de evenementen pagina. Hier zie je alle evenementen voor de komende 2 weken.',
        'image' => '',
        'meta' => null
    ]
];

try {
    $banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: $banner1;
    $banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: $banner2;

    // seed_page_blocks removed to prevent auto-creation
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'evenementen' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
    $stmt->execute();
    $pageBlocks = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM events WHERE date >= CURDATE() AND date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY) ORDER BY date, time");
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
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><link rel="stylesheet" href="custom.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>Event</title>
    <meta name="description" content="SociaalAI helpt inwoners sterker te staan in een steeds digitalere wereld. We doen dit door Rotterdammers actief mee te laten denken, praten en beslissen over kunstmatige intelligentie.">
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

<?php
$navPrefix = '';
include __DIR__ . '/navbar.php';
?>

<main>
    <?php foreach ($pageBlocks as $block): ?>
        <?php
        $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
        $hasImage = !empty($block['image']);
        $hasText = !empty($block['title']) || !empty($block['body']);
        $imagePosition = $metaArr['image_position'] ?? 'normal';
        if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) $imagePosition = 'normal';
        $greenText = trim((string)($metaArr['green_text'] ?? ($metaArr['green_heading'] ?? '')));
        
        $flexDir = 'column';
        if ($imagePosition === 'left' && $hasText) {
            $flexDir = 'row';
        } elseif ($imagePosition === 'right' && $hasText) {
            $flexDir = 'row-reverse';
        }
        $divStyle = '';
        if ($hasImage && $imagePosition !== 'normal' && $hasText) {
            $divStyle = 'display: flex; flex-direction: ' . $flexDir . '; align-items: flex-start; gap: 2rem;';
        }
        ?>
        <section class="bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
            <div style="<?php echo $divStyle; ?>">
                <?php if ($hasImage && $imagePosition === 'left' && $hasText): ?>
                    <div style="flex: 0 0 50%; min-width: 0; max-width: 600px;">
                        <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" class="w-full h-auto object-cover shadow-md">
                    </div>
                <?php elseif ($hasImage && !$hasText): ?>
                    <div style="width: 100%;">
                        <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" class="w-full h-auto object-cover shadow-md">
                    </div>
                <?php endif; ?>

                <?php if ($hasText): ?>
                <div style="<?php echo ($hasImage && $imagePosition !== 'normal') ? 'flex: 1; padding: 0 1.5rem;' : ''; ?>">
                    <?php if ($greenText !== ''): ?>
                        <div class="green-highlight mb-3"><?php echo nl2br(htmlspecialchars($greenText)); ?></div>
                    <?php endif; ?>
                    <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($block['title']); ?></h1>
                    <p class="text-gray-700 text-lg"><?php echo nl2br(htmlspecialchars($block['body'])); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($hasImage && $imagePosition === 'right' && $hasText): ?>
                    <div style="flex: 0 0 50%; min-width: 0; max-width: 600px;">
                        <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" class="w-full h-auto object-cover shadow-md">
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($hasImage && $imagePosition === 'normal' && $hasText): ?>
                <div class="mt-6" style="max-width: 600px;">
                    <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" class="w-full h-auto object-cover shadow-md">
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>

    <?php if (empty($events)): ?>
        <section class="bg-white shadow-lg p-8 max-w-4xl mx-auto my-12 text-center">
            <h2 class="text-2xl font-semibold text-gray-900 mb-2">Geen evenementen in de komende 2 weken</h2>
            <p class="text-gray-700">Houd de agenda in de gaten voor nieuwe activiteiten.</p>
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
                            <p class="text-gray-700"><strong> Wanneer:</strong> <?php echo htmlspecialchars($dateDisplay); ?><?php if ($timeDisplay) echo ' - ' . htmlspecialchars($timeDisplay); ?></p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <i class="fa-solid fa-location-dot text-[#00811F] ml-1 text-3xl"></i>
                            <?php $loc = $event['location'] ?: 'Rotterdam - Hillevliet 90'; ?>
                            <p class="text-gray-700 ml-1 "><strong>Waar:</strong> <a href="<?php echo googleMapsDirectionsUrl($loc); ?>" target="_blank" rel="noopener noreferrer" class="underline hover:text-[#00811F]"><?php echo htmlspecialchars($loc); ?></a></p>
                        </div>
                        <div class="flex mb-6 space-x-3">
                            <i class="fa-solid fa-bullseye text-[#00811F] text-3xl"></i>
                            <p class="text-gray-700 pb-3 "><strong> Wat:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($event['show_signup_button'])): ?>
                    <a href="inschrijven.php?event_id=<?php echo (int)$event['id']; ?>" class="mt-4 inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">
                        Inschrijven
                    </a>
                    <?php endif; ?>
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

.slide {
  inset: 0;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  opacity: 0;
  transition: opacity 1s ease-in-out;
}

.slide.active {
  opacity: 1;
}

@media (max-width: 768px) {
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
          .slide{
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
    }
     @media (min-width: 760px) {
        .hamburger{
            display: none;
        }
      
    }
</style></div>
</body>
</html>