<?php 
session_start();
require 'db.php';
require 'helpers.php';

$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';

// Fallback blocks for seeding
$fallbackBlocks = [
    [
        'title' => 'Contact',
        'body' => 'Wil je meedoen, meedenken, meeleren of meer weten over activiteiten van het lab? Stuur ons een bericht en maak een afspraak om langs te komen, iedereen is welkom!',
        'image' => 'Contact_foto.jpg',
        'meta' => json_encode(['email' => 'digitaleinclusie@rotterdam.nl'])
    ],
    [
        'title' => 'Sociaal AI Lab Rotterdam',
        'body' => 'Wij zijn geopend op maandag, woensdag en vrijdag.',
        'image' => '',
        'meta' => json_encode([
            'address' => 'Hillevliet 90, 3074 KD Rotterdam',
            'email' => 'digitaleinclusie@rotterdam.nl',
            'map_embed' => '<iframe class="map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d4924.161289441116!2d4.5037668125406105!3d51.89599238212385!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47c433097cf8a783%3A0x6aabf347bcd316ef!2sHillevliet%2090%2C%203074%20KD%20Rotterdam!5e0!3m2!1sen!2snl!4v1763988130581!5m2!1sen!2snl" width="auto" height="auto" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>'
        ])
    ]
];

try {
    $b1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn();
    $b2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn();
    if ($b1) $banner1 = $b1;
    if ($b2) $banner2 = $b2;
} catch (Exception $e) {
}

// Seed fallback blocks if they don't exist
try {
    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE page_key = ? AND title = ?');
    $insertStmt = $pdo->prepare('INSERT INTO pages (page_key, title, body, image, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    
    foreach ($fallbackBlocks as $block) {
        $checkStmt->execute(['contact', $block['title'] ?? null]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            $insertStmt->execute([
                'contact',
                $block['title'] ?? null,
                $block['body'] ?? null,
                $block['image'] ?? null,
                $block['meta'] ?? null
            ]);
        }
    }
} catch (Exception $e) {
    // Seeding failed, continue anyway
}

try {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'contact' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
    $stmt->execute();
    $pageBlocks = $stmt->fetchAll();
} catch (Exception $e) {
    $pageBlocks = [];
}
?>

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><link rel="stylesheet" href="custom.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>contact</title>
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



<main>
    <?php
    foreach ($pageBlocks as $block):
        $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
        $hasImage = !empty($block['image']);
        $imagePosition = $metaArr['image_position'] ?? 'normal';
        if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) $imagePosition = 'normal';
        $imageSrc = '';
        if ($hasImage) {
            $imagePath = trim((string)$block['image']);
            if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'uploads/') === 0) {
                $imageSrc = $imagePath;
            } elseif (strpos($imagePath, '../') === 0 || preg_match('#^https?://#i', $imagePath)) {
                $imageSrc = $imagePath;
            } else {
                $imageSrc = 'uploads/' . $imagePath;
            }
        }
    ?>
        <section class="bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
            <div class="<?php echo ($hasImage && $imagePosition !== 'normal') ? 'flex flex-col md:flex-row items-start gap-8' : ''; ?>">
                <?php if ($hasImage && $imagePosition === 'left'): ?>
                    <div class="md:w-80 flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                             alt="<?php echo htmlspecialchars($block['title']); ?>" 
                             class="w-full h-64 object-cover rounded shadow-md">
                    </div>
                <?php endif; ?>

                <div class="mb-6 <?php echo ($hasImage && $imagePosition !== 'normal') ? 'md:mb-0 flex-1' : ''; ?>">
                    <?php if (!empty($block['title'])): ?>
                        <h3 class="font-bold text-2xl mb-2"><?php echo htmlspecialchars($block['title']); ?></h3>
                    <?php endif; ?>
                    <?php if (!empty($block['body'])): ?>
                        <div class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($block['body'])); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($metaArr['address'])): ?>
                        <?php $addr = $metaArr['address']; ?>
                        <div class="text-gray-700 mt-2"><strong>Adres:</strong> <a href="<?php echo googleMapsDirectionsUrl($addr); ?>" target="_blank" rel="noopener noreferrer" class="underline hover:text-[#00811F]"><?php echo htmlspecialchars($addr); ?></a></div>
                    <?php endif; ?>
                    <?php if (!empty($metaArr['email'])): ?>
                        <div class="text-gray-700 mt-2">
                            <a href="mailto:<?php echo htmlspecialchars($metaArr['email']); ?>">
                                <?php echo htmlspecialchars($metaArr['email']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($hasImage && $imagePosition === 'right'): ?>
                    <div class="md:w-80 flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                             alt="<?php echo htmlspecialchars($block['title']); ?>" 
                             class="w-full h-64 object-cover rounded shadow-md">
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($hasImage && $imagePosition === 'normal'): ?>
                <div>
                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" 
                         alt="<?php echo htmlspecialchars($block['title']); ?>" 
                         class="w-auto h-64 object-cover rounded shadow-md">
                </div>
            <?php endif; ?>
            
            <?php if (!empty($metaArr['map_embed'])): ?>
                <div class="mt-4"><?php echo $metaArr['map_embed']; ?></div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
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
