<?php 
session_start();
require 'db.php';
require 'helpers.php';

$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';

try {
    $banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: $banner1;
    $banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: $banner2;

    $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'terugblikken' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
    $stmt->execute();
    $allBlocks = $stmt->fetchAll();
    
    // Filter blocks to only show custom layout
    $pageBlocks = [];
    foreach ($allBlocks as $block) {
        $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
        $layout = $metaArr['layout'] ?? 'custom';
        if ($layout === 'custom' || !in_array($layout, ['welcome', 'card', 'info', 'contact'], true)) {
            $pageBlocks[] = $block;
        }
    }

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
    <link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>Terugblikken</title>
    <meta name="description" content="SociaalAI helpt inwoners sterker te staan in een steeds digitalere wereld. We doen dit door Rotterdammers actief mee te laten denken, praten en beslissen over kunstmatige intelligentie.">
    <link rel="icon" type="image/png" href="images/Pixels_icon.png">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">

<div class="banner-wrapper">
    <img src="<?php echo htmlspecialchars($banner1); ?>" alt="Banner 1" class="banner active w-full object-cover h-60 md:h-96">
    <img src="<?php echo htmlspecialchars($banner2); ?>" alt="Banner 2" class="banner w-full object-cover h-60 md:h-96">
</div>

<?php
$navPrefix = '';
include __DIR__ . '/navbar.php';
?>

<main class="pt-8 sm:pt-12">
    <div id="agenda-terugblik-switch" class="mobile flex items-center justify-center gap-1" style="scroll-margin-top: 110px;">
         <div class="agenda bg-white p-6 shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="agenda.php#agenda-terugblik-switch"><h1 class="text-2xl text-[#00000] font-semibold">Agenda</h1></a>
        </div>
        <div class="terugblik bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="terugblikken.php#agenda-terugblik-switch"><h1 class="text-2xl text-[#00811F] font-semibold">Terugblik</h1></a>
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
        $greenText = trim((string)($metaArr['green_text'] ?? ($metaArr['green_heading'] ?? '')));
        $greenTextPosition = $metaArr['green_text_position'] ?? 'above';
        if (!in_array($greenTextPosition, ['above', 'below'], true)) $greenTextPosition = 'above';
        
        $flexDir = 'column';
        $flexWrap = 'nowrap';
        if ($imagePosition === 'left' && $hasText) {
            $flexDir = 'row';
            $flexWrap = 'nowrap';
        } elseif ($imagePosition === 'right' && $hasText) {
            $flexDir = 'row';
            $flexWrap = 'nowrap';
        }
        $sectionStyle = "display: flex; flex-direction: " . $flexDir . "; flex-wrap: " . $flexWrap . ";";
        if ($imagePosition !== 'normal' && $hasText) {
            $sectionStyle .= " gap: 2rem; align-items: flex-start;";
        } else {
            $sectionStyle .= " gap: 1.5rem;";
        }
    ?>
        <section class="bg-white shadow-lg px-3 sm:px-8 py-6 sm:py-8 max-w-6xl mx-auto mt-8 sm:mt-12 mb-6 sm:mb-12" style="<?php echo $sectionStyle; ?>">
            <?php if ($hasImage && $imagePosition === 'left'): ?>
                <?php
                $imageStyle = 'flex: 0 0 auto; max-width: 280px; width: 100%;';
                if (!$hasText) {
                    $imageStyle = 'width: 100%;';
                }
                ?>
                <div style="<?php echo $imageStyle; ?>">
                    <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title']); ?>" style="width: 100%; height: auto; border-radius: 0.5rem;">
                </div>
            <?php endif; ?>
            <?php if ($hasText): ?>
                <div style="<?php echo ($imagePosition !== 'normal' && $hasImage) ? 'flex: 1 1 auto; min-width: 0;' : ''; ?>">
                    <?php if ($greenText !== '' && $greenTextPosition === 'above'): ?>
                        <div class="green-highlight mb-3"><?php echo nl2br(htmlspecialchars($greenText)); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($block['title'])): ?>
                        <h3 class="text-xl sm:text-2xl font-semibold mb-4 text-gray-900"><?php echo renderEditorInline($block['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($block['body'])): ?>
                        <div class="text-sm sm:text-base text-gray-700 leading-relaxed"><?php echo renderEditorBlock($block['body']); ?></div>
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
                <?php if ($greenText !== '' && $greenTextPosition === 'below'): ?>
                    <div class="green-highlight mb-3"><?php echo nl2br(htmlspecialchars($greenText)); ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($hasImage && $imagePosition === 'right'): ?>
                <?php
                $imageStyle = 'flex: 0 0 auto; max-width: 280px; width: 100%;';
                if (!$hasText) {
                    $imageStyle = 'width: 100%;';
                }
                ?>
                <div style="<?php echo $imageStyle; ?>">
                    <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title']); ?>" style="width: 100%; height: auto; border-radius: 0.5rem;">
                </div>
            <?php endif; ?>
            <?php if ($hasImage && $imagePosition === 'normal'): ?>
                <div style="width: 100%; max-width: 600px;">
                    <img src="uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title']); ?>" style="width: 100%; height: auto; border-radius: 0.5rem;">
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
            <span class="inline-block text-white text-sm font-medium px-4 py-1 mb-4" style="background-color:#ce0245;">Evenement</span>
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900"><?php echo renderEditorInline($event['title']); ?></h2>
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
                    <div class="text-gray-700 pb-3 "><strong> Wat:</strong><div class="mt-1"><?php echo renderEditorBlock($event['description']); ?></div></div>
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
