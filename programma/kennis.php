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
    <link rel="modulepreload" as="script" href="../build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="../style.css?v=<?php echo filemtime(__DIR__.'/../style.css'); ?>"><script type="module" src="../build/assets/app-CAiCLEjY.js"></script>    <title>Kennis & Vaardigheden</title>
    <meta name="description" content="SociaalAI helpt inwoners sterker te staan in een steeds digitalere wereld. We doen dit door Rotterdammers actief mee te laten denken, praten en beslissen over kunstmatige intelligentie.">
    <link rel="icon" type="image/png" href="../images/Pixels_icon.png">
    <link rel="stylesheet" href="../ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">

<div class="banner-wrapper">
    <img src="<?php echo htmlspecialchars($banner1); ?>" alt="Banner 1" class="banner active w-full object-cover h-60 md:h-96">
    <img src="<?php echo htmlspecialchars($banner2); ?>" alt="Banner 2" class="banner w-full object-cover h-60 md:h-96">
</div>

<?php
$navPrefix = '../';
include __DIR__ . '/../navbar.php';
?>

<!-- Pagina content -->
<main class="watdoenwe-page">
    <?php
    // Fallback blocks for seeding
    $fallbackBlocks = [
        [
            'title' => 'AI-vaardigheidstrainingen',
            'body' => 'Workshops voor inwoners, jongeren en professionals over wat AI is en hoe je het verantwoord kunt gebruiken. Deze sessies zijn vergroten digitale vaardigheden. Hoe werkt het precies? Hoe kan AI jou helpen? Waar moet je aan denken als je het gebruikt?',
            'image' => 'images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20AI-vaardigheidstrainingen.jpg',
            'meta' => null
        ],
        [
            'title' => 'Ontrafel de AI-Machine',
            'body' => 'Een theatrale en interactieve sessie voor de jeugd, waarin deelnemers kunnen ontdekken hoe kunstmatige intelligentie werkt, leert en beslissingen neemt.',
            'image' => 'images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Ontrafel_de%20_AI_Machine.jpeg',
            'meta' => null
        ],
        [
            'title' => 'Samen toekomstbeelden cre�ren met AI',
            'body' => 'Een interactieve sessie die mensen inzicht geeft in generatieve AI, en waarin samen creatieve scenario\'s gemaakt worden voor een rechtvaardige en inclusieve AI-toekomst..',
            'image' => 'images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Samen_toekomstbeelden_cre%C3%ABren_met_behulp_van_AI.jpeg',
            'meta' => null
        ],
        [
            'title' => 'Test je AI-kennis',
            'body' => 'Ontdek hoeveel jij al weet over AI en verdien een badge als beloning voor het maken van onze AI-test.',
            'image' => 'images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Test_je_AI_kennis.jpg',
            'meta' => null
        ],
        [
            'title' => 'AI in het basisonderwijs',
            'body' => 'Leuke lessen waarin basisschoolleerlingen op een speelse manier kennismaken met kunstmatige intelligentie. Ze worden geprikkeld om nieuwsgierig te zijn, creatief te denken en tegelijkertijd te oefenen met digitale vaardigheden.',
            'image' => 'images/wat_doen_we/kennis_vaardigheden/Basisschool.jpg',
            'meta' => null
        ],
        [
            'title' => 'Echt of Nep?',
            'body' => 'Weet jij nog wat echt is en wat nep? Door de snelle opkomst van generatieve AI wordt het steeds moeilijker om te zien wat door AI is gemaakt en wat door mensen. In deze workshop serie gaan we aan de slag met wat dat betekent voor ons nieuws, ons onderwijs en voor de democratie.',
            'image' => 'images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Echt_of_Nep.png',
            'meta' => null
        ],
        [
            'title' => 'Feministische AI',
            'body' => 'Ontdek spelenderwijs hoe generatieve AI werkt, welke vooroordelen erin kunnen zitten en hoe je technologie eerlijker kunt maken door aandacht voor macht- en ongelijkheid.',
            'image' => 'images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Feministische_AI.jpg',
            'meta' => null
        ]
    ];

    // Seed fallback blocks if they don't exist
    try {
        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE page_key = ? AND title = ?');
        $insertStmt = $pdo->prepare('INSERT INTO pages (page_key, title, body, image, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        
        foreach ($fallbackBlocks as $block) {
            $checkStmt->execute(['programma-kennis', $block['title'] ?? null]);
            if ((int)$checkStmt->fetchColumn() === 0) {
                $insertStmt->execute([
                    'programma-kennis',
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
        $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'programma-kennis' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
        $stmt->execute();
        $pageBlocks = $stmt->fetchAll();
    } catch (Exception $e) {
        $pageBlocks = [];
    }

    $cardBlocks = array_values(array_filter($pageBlocks, function ($block) {
        $title = trim((string)($block['title'] ?? ''));
        return $title !== 'Kennis & Vaardigheden';
    }));
    
    // Display custom cards from admin
    ?>

    <section class="text-block bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="text-block-content">
            <h3 class="text-2xl font-semibold mb-4 text-gray-900">Kennis &amp; Vaardigheden</h3>
            <div class="text-gray-700 leading-relaxed">Verken de wereld van kunstmatige intelligentie door middel van workshops, trainingen en educatieve programma's. We bieden verschillende activiteiten aan voor alle niveaus.</div>
        </div>
    </section>

    <div class="mobile flex items-center justify-center">
        <div class="bg-white p-6 shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="kennis.php"><h1 class="text-2xl text-[#00811F] font-semibold">Kennis & Vaardigheden</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="actie.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Actie, Onderzoek & Ontwerp</h1></a>
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
            $greenText = trim((string)($metaArr['green_text'] ?? ($metaArr['green_heading'] ?? '')));
            
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
                <?php if ($greenText !== ''): ?>
                    <div class="green-highlight mb-3"><?php echo nl2br(htmlspecialchars($greenText)); ?></div>
                <?php endif; ?>
                <?php if (!empty($block['title'])): ?>
                    <h3 class="text-lg font-semibold mb-2"><?php echo renderEditorInline($block['title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($block['body'])): ?>
                    <div class="mb-2 text-gray-700 text-sm"><?php echo renderEditorBlock($block['body']); ?></div>
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
                    <div style="<?php echo $hasText ? 'margin-top: auto; width: 100%; max-width: 560px;' : 'width: 100%;' ; ?>">
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($block['title'] ?? ''); ?>" style="width: 100%; <?php echo $hasText ? 'height: 360px; object-fit: cover; object-position:cover;' : 'height: auto;'; ?>">
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


