<?php 
session_start();
require 'db.php';
require 'helpers.php';

$banner1 = 'images/banner_website_01.jpg';
$banner2 = 'images/banner_website_02.jpg';

$fallbackBlocks = [
    [
        'title' => 'Welkom bij het Sociaal AI Lab Rotterdam! Denk, doe en leer mee',
        'body' => 'Samen maken we technologie sociaal.

In Rotterdam willen we dat iedereen mee kan doen in de digitale samenleving. Daarom is er nu het Sociaal AI Lab Rotterdam: een open plek in de stad waar bewoners, onderzoekers, ontwerpers en beleidsmakers samenwerken aan eerlijke, begrijpelijke en toegankelijke technologie.

Rotterdammers ontdekken hier in gesprekken, bijeenkomsten, leer- en doe-activiteiten wat AI betekent voor hun dagelijks leven en denken mee over wat technologie wél of niet moet doen.',
        'image' => '',
        'meta' => json_encode(['layout' => 'welcome'])
    ],
    [
        'title' => 'Meedenken',
        'body' => 'Rotterdammers krijgen een stem bij het ontwikkelen en beoordelen van AI.',
        'image' => 'Meedenken.png',
        'meta' => json_encode(['layout' => 'card'])
    ],
    [
        'title' => 'Samen leren',
        'body' => 'We vergroten de kennis over kansen en risico\'s van AI',
        'image' => 'Samen_leren.png',
        'meta' => json_encode(['layout' => 'card'])
    ],
    [
        'title' => 'Meedoen',
        'body' => 'We ontwikkelen samen oplossingen die passen bij de waarden van de stad',
        'image' => 'Meedoen.png',
        'meta' => json_encode(['layout' => 'card'])
    ],
    [
        'title' => 'Gelijke kansen voor iedereen',
        'body' => 'We onderzoeken samen wat kunstmatige intelligentie (AI) betekent voor het dagelijks leven in Rotterdam, en hoe we AI zo kunnen gebruiken dat het bijdraagt aan gelijke kansen voor iedereen.
Het lab hoort bij het gemeentelijke programma Digitale Inclusie, dat ervoor zorgt dat alle Rotterdammers veilig, vaardig en volwaardig kunnen meedoen in de digitale wereld.
Kunstmatige Intelligentie? Technologie is pas echt slim als ze óók sociaal is.',
        'image' => '',
        'meta' => json_encode(['layout' => 'info'])
    ]
];

try {
    $b1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn();
    $b2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn();
    if ($b1) $banner1 = $b1;
    if ($b2) $banner2 = $b2;

    $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM pages WHERE page_key = ? AND title = ?');
    $insertStmt = $pdo->prepare('INSERT INTO pages (page_key, title, body, image, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
    
    foreach ($fallbackBlocks as $block) {
        $checkStmt->execute(['index', $block['title'] ?? null]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            $insertStmt->execute([
                'index',
                $block['title'] ?? null,
                $block['body'] ?? null,
                $block['image'] ?? null,
                $block['meta'] ?? null
            ]);
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'index' ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC");
    $stmt->execute();
    $pageBlocks = $stmt->fetchAll();
    
    $welcomeBlock = null;
    $cardBlocks = [];
    $infoBlock = null;
    $customBlocks = [];
    
    foreach ($pageBlocks as $block) {
        $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
        $layout = $metaArr['layout'] ?? 'custom';
        
        if ($layout === 'welcome') {
            $welcomeBlock = $block;
        } elseif ($layout === 'card') {
            $cardBlocks[] = $block;
        } elseif ($layout === 'info') {
            $infoBlock = $block;
        } else {
            $customBlocks[] = $block;
        }
    }
    
    $stmt = $pdo->prepare("SELECT * FROM events WHERE date >= CURDATE() AND (show_on_homepage IS NULL OR show_on_homepage = 1) ORDER BY date, time LIMIT 2");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $pageBlocks = [];
    $welcomeBlock = null;
    $cardBlocks = [];
    $infoBlock = null;
    $customBlocks = [];
    $events = [];
}
?>

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><link rel="stylesheet" href="custom.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>SociaalAI Lab</title>
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
    
    <?php if ($welcomeBlock): ?>
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg mt- p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                <?php echo htmlspecialchars($welcomeBlock['title']); ?></h2>
            <?php
            $paragraphs = array_filter(explode("\n\n", $welcomeBlock['body']), function($p) { return trim($p) !== ''; });
            foreach ($paragraphs as $para): ?>
                <p class="text-gray-700 leading-relaxed mb-4">
                    <?php echo htmlspecialchars($para); ?>
                </p>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    
    <?php if (!empty($cardBlocks)): ?>
    <div class="flex flex-col flexrow justify-evenly w-full max-w-6xl mx-auto gap-8">
        <?php foreach ($cardBlocks as $block): ?>
        <div class="space-y-6">
            <div class="bg-white shadow-lg pt-0 pb-6 mb-4 min-h-[220px] max-w-sm mx-auto">
                <div class="flex flex-1 items-center justify-center">   
                    <?php if (!empty($block['image'])): ?>
                        <?php
                        $imagePath = trim((string)$block['image']);
                        if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'uploads/') === 0) {
                            $imageSrc = $imagePath;
                        } else {
                            $imageSrc = 'images/' . $imagePath;
                        }
                        ?>
                        <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($block['title']); ?>" class="w-auto h-32">
                    <?php endif; ?>
                </div>
                <h3 class="text-xl text-center font-semibold"><?php echo htmlspecialchars($block['title']); ?></h3>
                <p class="text-center p-4">
                    <?php echo htmlspecialchars($block['body']); ?>
                </p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php if ($infoBlock): ?>
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
            <p class="text-1xl md:text-1xl mb-4 text-gray-900">
                <?php
                $lines = explode("\n", str_replace("\r\n", "\n", $infoBlock['body']));
                foreach ($lines as $idx => $line):
                    $line = trim($line);
                    if (!empty($line)): ?>
                        <?php if (strpos($line, 'Kunstmatige Intelligentie') !== false): ?>
                            </p>
                            <p class="text-[#00811F] text-lg"><?php echo htmlspecialchars($line); ?></p>
                            <p class="text-1xl md:text-1xl mb-4 text-gray-900">
                        <?php else: ?>
                            <?php echo htmlspecialchars($line); ?><br>
                        <?php endif;
                    endif;
                endforeach;
                ?>
            </p>
        </div>
    </section>
    <?php endif; ?>

    <?php
    foreach ($customBlocks as $block):
        $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
        $hasImage = !empty($block['image']);
        $imagePosition = $metaArr['image_position'] ?? 'normal';
        if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) $imagePosition = 'normal';
        $imageClass = $hasImage ? 'with-image' : '';
    ?>
        <section class="text-block <?php echo htmlspecialchars($imageClass); ?> bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
            <?php if ($hasImage): ?>
                <div class="text-block-image-container <?php echo $imagePosition === 'right' ? 'md:order-2' : ''; ?>">
                    <?php
                    $imagePath = trim((string)$block['image']);
                    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, 'uploads/') === 0) {
                        $imageSrc = $imagePath;
                    } else {
                        $imageSrc = 'uploads/' . $imagePath;
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($imageSrc); ?>" alt="<?php echo htmlspecialchars($block['title']); ?>" class="text-block-image">
                </div>
            <?php endif; ?>
            <div class="text-block-content <?php echo $imagePosition === 'right' ? 'md:order-1' : ''; ?>">
                <?php if (!empty($block['title'])): ?>
                    <h3 class="text-2xl font-semibold mb-4 text-gray-900"><?php echo htmlspecialchars($block['title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($block['body'])): ?>
                    <div class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($block['body'])); ?></div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
        
<?php
foreach ($events as $event):
?>
<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
    <div class="flex-1">
        <span class="inline-block bg-[#00811F] text-white text-sm font-medium px-4 py-1 mb-4">Evenement</span>
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900"><?php echo htmlspecialchars($event['title']); ?></h2>
        <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <i class="fa-regular fa-calendar text-[#00811F] ml-[2px] text-3xl"></i>
                    <?php $dateDisplay = formatEventDateDisplay($event['date']); $timeDisplay = $event['time'] ? formatEventTimeDisplay($event['time']) : ''; ?>
                    <p class="text-gray-700"><strong> Wanneer:</strong> <?php echo htmlspecialchars($dateDisplay); ?><?php if ($timeDisplay) echo ' - ' . htmlspecialchars($timeDisplay); ?></p>
                </div>
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-location-dot text-[#00811F] ml-1 text-3xl"></i>
                <?php $loc = $event['location'] ?: 'Rotterdam - Hillevliet 90'; ?>
                <p class="text-gray-700 ml-1"><strong>Waar:</strong> <a href="<?php echo googleMapsDirectionsUrl($loc); ?>" target="_blank" rel="noopener noreferrer" class="underline hover:text-[#00811F]"><?php echo htmlspecialchars($loc); ?></a></p>
            </div>
            <div class="flex mb-6 space-x-3">
                <i class="fa-solid fa-bullseye text-[#00811F] text-3xl"></i>
                <p class="text-gray-700 pb-3"><strong> Wat:</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            </div>
        </div>
    </div>
    <div class="flex-1">
        <img src="<?php echo $event['image'] ? 'uploads/' . htmlspecialchars($event['image']) : 'images/event/Agenda_event_2_Studenten_en_bewoners_verkennen_de_sociale_invloed_van_AI.jpg'; ?>" alt="" class="w-full h-auto object-cover shadow-md">
    </div>
</section>
<?php endforeach; ?>

<?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
    <a href="admin.php" title="Voeg evenement toe" class="fixed bottom-6 right-6 bg-[#00811F] text-white rounded-full w-12 h-12 flex items-center justify-center text-3xl shadow-lg">+</a>
<?php endif; ?>
<a href="contact.php">
    <div class="flex items-center justify-center">
        <i class="text-[#cc0033] fa-3x fa-regular fa-envelope-open"></i>
    </div>
    <h2 class="text-center text-2xl md:text-xl font-bold text-white mb-2">Neem Contact Op</h2>
</a>

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
            const first = menu.querySelector('[role="menuitem"]');
            if (first) first.focus();
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
