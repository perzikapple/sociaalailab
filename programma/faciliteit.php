<?php 
session_start();
require '../db.php';

// Fallback banners
$banner1 = '../images/banner_website_01.jpg';
$banner2 = '../images/banner_website_02.jpg';

// Fallback page blocks
$fallbackBlocks = [
    [
        'title' => 'Voorbeeld Faciliteit',
        'body' => 'Dit is een voorbeeld van een tekstblok voor faciliteit.',
        'image' => '',
        'meta' => null
    ]
    <?php include __DIR__ . '/../footer.php'; ?>

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="../build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="../build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="../build/assets/app-DozK-03z.css"><script type="module" src="../build/assets/app-CAiCLEjY.js"></script>    <title>hoogtenpunten</title>
    <meta name="description" content="SociaalAI helpt inwoners sterker te staan in een steeds digitalere wereld. We doen dit door Rotterdammers actief mee te laten denken, praten en beslissen over kunstmatige intelligentie.">
    <link rel="icon" type="image/png" href="../images/Pixels_icon.png">
    <link rel="stylesheet" href="../ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">


<div class="banner-wrapper">
    <div class="banner banner-1 active">
        <img class="" src="<?php echo htmlspecialchars($banner1); ?>">
    </div>
    <div class="banner banner-2">
        <img class="" src="<?php echo htmlspecialchars($banner2); ?>">
    </div>
    <?php include __DIR__ . '/../footer.php'; ?>

                    <!-- Elke link is role=menuitem voor a11y -->
                    <a href="kennis.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="actie.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="faciliteit.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab
                    </a>
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
    // Display custom text blocks from admin
    foreach ($pageBlocks as $block):
        $metaArr = $block['meta'] ? json_decode($block['meta'], true) : [];
        $hasImage = !empty($block['image']);
        $imageClass = $hasImage ? 'with-image' : '';
    ?>
        <section class="text-block <?php echo htmlspecialchars($imageClass); ?> bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
            <?php if ($hasImage): ?>
                <div class="text-block-image-container">
                    <img src="../uploads/<?php echo htmlspecialchars($block['image']); ?>" alt="<?php echo htmlspecialchars($block['title']); ?>" class="text-block-image">
                </div>
            <?php endif; ?>
            <div class="text-block-content">
                <?php if (!empty($block['title'])): ?>
                    <h3 class="text-2xl font-semibold mb-4 text-gray-900"><?php echo htmlspecialchars($block['title']); ?></h3>
                <?php endif; ?>
                <?php if (!empty($block['body'])): ?>
                    <div class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($block['body'])); ?></div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
    
      <div class="mobile flex flex-row flex-1 items-center justify-center mt-10">
         <div class="bg-white p-6 shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="kennis.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Kennis & Vaardigheden</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="actie.php"><h1 class="text-2xl hover:text-[#00811F]  font-semibold">Actie, Onderzoek & Ontwerp</h1></a>
        </div>
         <div class="bg-white p-6  max-w-xl mt-6 w-full text-center">
            <a href="faciliteit.php"><h1 class="text-2xl text-[#00811F] font-semibold">Faciliteiten</h1></a>
        </div>
    </div>

    <div class="mobile-col flex justify-evenly gap-8 p-6">
        <!-- Kolom 1 -->
        <div class="space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Het Sociaal AI Lab is op Hillevliet en online te bezoeken.</h3>
                <p class="h-40">
                Het Lab biedt de plek én de technische ondersteuning voor alle activiteiten van het programma. Dat kan op locatie aan de Hillevliet, maar ook in een digitale omgeving. Het Lab is het kloppende hart van het programma.
                </p>
                <div class="flex-1">
                <img src="../images/wat_doen_we/faciliteiten/Wat_doen_we_Sociaal_AI%20Lab_Rotterdam.png" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
            
            <div class="relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">De mobiele AI Labkar – een pop-uplocatie waar bewoners kunnen kennismaken met AI</h3>
                <p class="h-40">
                    Samen met bewoners ontwerpen we AI-oplossingen voor sociale vraagstukken, bijvoorbeeld rond armoede, zorg of veiligheid. Rotterdammers zetten tijdens deze activiteiten hun behoeftes aan eerlijke technologie en praktische oplossingen om in de praktijk
                </p>
                <div class="flex-1">
                <img src="../images/wat_doen_we/faciliteiten/Wat_doen_we_AI_Labkar.png" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
</div></main>

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

</body>
</html>


