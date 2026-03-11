<?php 
session_start();
require '../db.php';
require '../helpers.php';

// Fallback banners
$banner1 = '../images/banner_website_01.jpg';
$banner2 = '../images/banner_website_02.jpg';

try {
    $b1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn();
    $b2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn();
    if ($b1) $banner1 = (strpos($b1, 'images/') === 0) ? '../' . $b1 : $b1;
    if ($b2) $banner2 = (strpos($b2, 'images/') === 0) ? '../' . $b2 : $b2;
} catch (Exception $e) {
    // Use fallbacks
}
?>

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="../build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="../build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="../build/assets/app-DozK-03z.css"><link rel="stylesheet" href="../custom.css"><script type="module" src="../build/assets/app-CAiCLEjY.js"></script>    <title>Actie, Onderzoek & Ontwerp</title>
    <meta name="description" content="SociaalAI helpt inwoners sterker te staan in een steeds digitalere wereld. We doen dit door Rotterdammers actief mee te laten denken, praten en beslissen over kunstmatige intelligentie.">
    <link rel="icon" type="image/png" href="../images/Pixels_icon.png">
    <link rel="stylesheet" href="../ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            <a href="../index.php" class="menu inline-flex items-center gap-1 text-gray-700 hover:text-[#00811F] transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="../agenda.php" class="menu text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="../over.php" class="menu text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

            <div class="relative" id="programma-dropdown">
                <button id="programma-toggle" aria-haspopup="true" aria-expanded="false" class="menu flex items-center gap-2 text-gray-700 hover:text-[#00811F] transition font-medium focus:outline-none">
                    <i class="fa-solid fa-caret-right text-xs" aria-hidden="true"></i>
                    <span>Wat doen we?</span>
                </button>

                <div id="programma-menu" class="hidden absolute top-0 mt-8 w-56 bg-white border border-gray-200 shadow-lg py-2 z-50 focus:outline-none" role="menu" aria-labelledby="programma-toggle">
                    <a href="kennis.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="actie.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="faciliteit.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab</a>
                </div>
            </div>

            <a href="../verantwoord-ai.php" class="menu text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="../wie-zijn-we.php" class="menu text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="../contact.php" class="menu text-gray-700 hover:text-[#00811F] transition">Contact</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                <a href="../admin.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Pagina content -->
<main>
    <?php
    // Fallback blocks for seeding
    $fallbackBlocks = [
        [
            'title' => 'Digiderius – de digitale Erasmus',
            'body' => 'Digiderius is een "digitaal mens" waarmee deelnemers kunnen praten over onderwerpen als onderwijs, cultuur en technologie. Ontdek zelf hoe jij deze digitale gesprekspartner ervaart.',
            'image' => 'images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_%20Digiderius.jpeg',
            'meta' => null
        ],
        [
            'title' => 'Samen AI-toepassingen ontwerpen',
            'body' => 'Samen met bewoners ontwerpen we AI-oplossingen voor sociale vraagstukken, bijvoorbeeld rond armoede, zorg of veiligheid.',
            'image' => 'images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_Samen_AI_toepassingen_ontwerpen.jpeg',
            'meta' => null
        ],
        [
            'title' => 'Inclusieve zorg en AI',
            'body' => 'Met Rotterdamse vrouwen en non-binaire personen van kleur bespreken we uitsluiting en vooroordelen in de zorg.',
            'image' => 'images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_%20Inclusieve_AI_in_de_Zorg.JPG',
            'meta' => null
        ],
        [
            'title' => 'Ondersteuning aan bewoners verbeteren met AI?',
            'body' => 'We onderzoeken samen hoe AI kan helpen om Rotterdammers met weinig digitale ervaring beter te ondersteunen.',
            'image' => 'images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_Ondersteuning_aan_bewoners_verbeteren_%20met_AI.jpg',
            'meta' => null
        ],
        [
            'title' => 'Verantwoorde AI binnen organisaties (ELSA-aanpak)',
            'body' => 'We willen begrijpen of AI goed, rechtvaardig en maatschappelijk verantwoord is, vooral als we het inzetten in publieke diensten.',
            'image' => 'images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_Verantwoorde_AI_binnen%20_Organizaties_%28ELSA-aanpak%29.png',
            'meta' => null
        ],
        [
            'title' => 'Studenten en bewoners verkennen de sociale invloed van AI',
            'body' => 'Samen met bewoners uit de omgeving van de Hillevliet, onderzoeken studenten wat AI betekent voor de wijk.',
            'image' => 'images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_%20Studenten_en_bewoners_verkennen_de_sociale_invloed_van_AI.jpg',
            'meta' => null
        ],
        [
            'title' => 'Wijkbots – in te zetten voor een betrokken stad?',
            'body' => 'We kijken hoe slimme, zelfstandige machines in de toekomst kunnen helpen in onze openbare ruimtes.',
            'image' => 'images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_wijkbots.jpg',
            'meta' => null
        ]
    ];

    // Seed fallback blocks if they don't exist
    try {
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE page_key = ? AND title = ?');
        $insertStmt = $pdo->prepare('INSERT INTO pages (page_key, title, body, image, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        
        foreach ($fallbackBlocks as $block) {
            $checkStmt->execute(['programma-actie', $block['title'] ?? null]);
            if ((int)$checkStmt->fetchColumn() === 0) {
                $insertStmt->execute([
                    'programma-actie',
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

    // Fetch page blocks from database
    $pageBlocks = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'programma-actie' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
        $stmt->execute();
        $pageBlocks = $stmt->fetchAll();
    } catch (Exception $e) {
        $pageBlocks = [];
    }

    $cardBlocks = array_values(array_filter($pageBlocks, function ($block) {
        $title = trim((string)($block['title'] ?? ''));
        return $title !== 'Actie, Onderzoek & Ontwerp';
    }));
    
    // Display custom cards from admin
    ?>

    <section class="text-block bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="text-block-content">
            <h3 class="text-2xl font-semibold mb-4 text-gray-900">Actie, Onderzoek &amp; Ontwerp</h3>
            <div class="text-gray-700 leading-relaxed">We ontwikkelen samen met bewoners en organisaties innovatieve AI-toepassingen die bijdragen aan een betere samenleving.</div>
        </div>
    </section>

    <div class="mobile flex items-center justify-center">
        <div class="bg-white p-6 shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="kennis.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Kennis & Vaardigheden</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="actie.php"><h1 class="text-2xl text-[#00811F] font-semibold">Actie, Onderzoek & Ontwerp</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center">
            <a href="faciliteit.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Faciliteiten</h1></a>
        </div>
    </div>

    <!-- Custom cards from database -->
    <?php if (!empty($cardBlocks)): ?>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4">
        <?php foreach ($cardBlocks as $block): ?>
            <?php
            $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
            $imagePosition = $metaArr['image_position'] ?? 'normal';
            if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) $imagePosition = 'normal';
            
            $flexDir = 'column';
            if ($imagePosition === 'left') {
                $flexDir = 'row';
            } elseif ($imagePosition === 'right') {
                $flexDir = 'row-reverse';
            }
            
            $hasImage = !empty($block['image']);
            $hasText = !empty($block['title']) || !empty($block['body']);
            $cardStyle = 'display: flex; flex-direction: ' . $flexDir . '; ';
            if ($imagePosition !== 'normal' && $hasText) {
                $cardStyle .= 'gap: 1rem; align-items: flex-start;';
            } else {
                $cardStyle .= 'gap: 0; justify-content: space-between; flex-direction: column;';
            }
            ?>
            <div class="bg-white p-4 shadow-lg" style="<?php echo $cardStyle; ?>">
                <?php if ($hasImage && $imagePosition === 'left'): ?>
                    <?php
                    $imagePath = trim((string)$block['image']);
                    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'uploads/') === 0) {
                        $imageSrc = '../' . $imagePath;
                    } elseif (strpos($imagePath, '../') === 0 || preg_match('#^https?://#i', $imagePath)) {
                        $imageSrc = $imagePath;
                    } else {
                        $imageSrc = '../uploads/' . $imagePath;
                    }
                    ?>
                    <div style="<?php echo $hasText ? 'flex-shrink: 0; width: 120px; min-width: 120px; max-width: 120px;' : 'width: 100%;'; ?>">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" style="width: 100%; <?php echo $hasText ? 'height: 112px; object-fit: cover;' : 'height: auto;'; ?>">
                    </div>
                <?php endif; ?>

                <div style="<?php echo ($hasImage && $imagePosition !== 'normal') ? 'flex: 1; padding: 0 1.5rem;' : ''; ?>">
                <?php if (!empty($block['title'])): ?>
                    <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($block['title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($block['body'])): ?>
                    <p class="mb-2 text-gray-700 text-sm"><?php echo nl2br(htmlspecialchars($block['body'])); ?></p>
                <?php endif; ?>
                </div>

                <?php if ($hasImage && $imagePosition === 'right'): ?>
                    <?php
                    $imagePath = trim((string)$block['image']);
                    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'uploads/') === 0) {
                        $imageSrc = '../' . $imagePath;
                    } elseif (strpos($imagePath, '../') === 0 || preg_match('#^https?://#i', $imagePath)) {
                        $imageSrc = $imagePath;
                    } else {
                        $imageSrc = '../uploads/' . $imagePath;
                    }
                    ?>
                    <div style="<?php echo $hasText ? 'flex-shrink: 0; width: 120px; min-width: 120px; max-width: 120px;' : 'width: 100%;'; ?>">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" style="width: 100%; <?php echo $hasText ? 'height: 112px; object-fit: cover;' : 'height: auto;'; ?>">
                    </div>
                <?php endif; ?>

                <?php if ($hasImage && $imagePosition === 'normal'): ?>
                    <?php
                    $imagePath = trim((string)$block['image']);
                    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'uploads/') === 0) {
                        $imageSrc = '../' . $imagePath;
                    } elseif (strpos($imagePath, '../') === 0 || preg_match('#^https?://#i', $imagePath)) {
                        $imageSrc = $imagePath;
                    } else {
                        $imageSrc = '../uploads/' . $imagePath;
                    }
                    ?>
                    <div style="<?php echo $hasText ? 'margin-top: auto; width: 100%; max-width: 400px;' : 'width: 100%;'; ?>">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" style="width: 100%; <?php echo $hasText ? 'height: 160px; object-fit: cover;' : 'height: auto;'; ?>">
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../footer.php'; ?>

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
            // focus eerste item voor keyboard users
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

        // klik op de knop: toggle
        toggle.addEventListener('click', function(e){
            e.preventDefault();
            toggleMenu();
        });

        // keyboard op de knop: Enter of Space opent/toggle
        toggle.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                toggleMenu();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (menu.classList.contains('hidden')) openMenu();
            }
        });

        // sluit op escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                if (!menu.classList.contains('hidden')) closeMenu();
            }
        });

        // klik buiten: sluit menu
        document.addEventListener('click', function(e) {
            const target = e.target;
            if (!menu.contains(target) && !toggle.contains(target)) {
                if (!menu.classList.contains('hidden')) closeMenu();
            }
        });

        // optioneel: sluit en navigeer op menuitem click (voor a tags standaard)
        const items = menu.querySelectorAll('[role="menuitem"]');
        items.forEach(item => {
            item.setAttribute('tabindex', '0'); // focusable
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
    .flex-col {
        display: flex;
        flex-direction: column;
    }

    .flex-col img {
        max-width: 550px;
        max-height: 360px;
        object-fit: cover;
        display: block;
        border-radius: 4px;
    }
</style>
</body>
</html>
