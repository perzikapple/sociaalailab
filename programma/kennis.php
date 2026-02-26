<?php 
session_start();
require '../db.php';

// Fallback banners
$banner1 = '../images/banner_website_01.jpg';
$banner2 = '../images/banner_website_02.jpg';

// Fallback page blocks
$fallbackBlocks = [
    [
        'title' => 'Voorbeeld Kennis',
        'body' => 'Dit is een voorbeeld van een tekstblok voor kennis.',
        'image' => '',
        'meta' => null
    ]
    <?php include __DIR__ . '/../footer.php'; ?>

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

                </div>
            </div>

            <a href="../verantwoord-ai.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="../wie-zijn-we.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="../contact.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Contact</a>
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
    
    <div class="mobile flex  items-center justify-center">
         <div class="bg-white p-6  shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="kennis.php"><h1 class="text-2xl text-[#00811F] font-semibold">Kennis & Vaardigheden</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="actie.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Actie, Onderzoek & Ontwerp</h1></a>
        </div>
         <div class="bg-white p-6  max-w-xl mt-6 w-full text-center">
            <a href="faciliteit.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Faciliteiten</h1></a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-6">

        <!-- Kolom 1 -->
   
        <div class="flex flex-col justify-between space-y-6 space-x-6 bg-white p-6">
            <h3 class="text-xl font-semibold mb-4"> AI-vaardigheidstrainingen</h3>
            <p class="">
            Workshops voor inwoners, jongeren en professionals over wat AI is en hoe je het verantwoord kunt gebruiken. Deze sessies zijn vergroten digitale vaardigheden. Hoe werkt het precies? Hoe kan AI jou helpen? Waar moet je aan denken als je het gebruikt?
            </p>
            <div class="">
            <img src="../images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20AI-vaardigheidstrainingen.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
            </div>
        </div>
    
            
            <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Ontrafel de AI-Machine</h3>
                <p class="">
                    Een theatrale en interactieve sessie voor de jeugd, waarin deelnemers kunnen ontdekken hoe kunstmatige
                    intelligentie werkt, leert en beslissingen neemt.
                </p>
                <div class="">
                <img src="../images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Ontrafel_de%20_AI_Machine.jpeg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>

        <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Samen toekomstbeelden creëren met AI</h3>
                <p class="">
                    Een interactieve sessie die mensen inzicht geeft in generatieve AI, en waarin samen creatieve scenario’s
                    gemaakt worden voor een rechtvaardige en inclusieve AI-toekomst..
                </p>
                <div class="">
                <img src="../images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Samen_toekomstbeelden_cre%C3%ABren_met_behulp_van_AI.jpeg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>

             <!-- Kolom 2 -->
            <div class="flex flex-col justify-between space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Test je AI-kennis</h3>
                <p class="">
               Ontdek hoeveel jij al weet over AI en verdien een badge als beloning voor het maken van onze AI-test. </p>
                <div class="">
                <img src="../images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Test_je_AI_kennis.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
            
            <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">AI in het basisonderwijs </h3>
                <p class="">
                    Leuke lessen waarin basisschoolleerlingen op een speelse manier kennismaken met kunstmatige intelligentie. Ze worden geprikkeld om nieuwsgierig te zijn, creatief te denken en tegelijkertijd te oefenen met digitale vaardigheden.
                </p>
                <div class="">
                <img src="../images/wat_doen_we/kennis_vaardigheden/Basisschool.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>

        <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Echt of Nep?</h3>
                <p class="">
                   Weet jij nog wat echt is en wat nep? Door de snelle opkomst van generatieve AI wordt het steeds moeilijker om te zien wat door AI is gemaakt en wat door mensen. In deze workshop serie gaan we aan de slag met wat dat betekent voor ons nieuws, ons onderwijs en voor de democratie.</p>
                <div class="">
                <img src="../images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Echt_of_Nep.png" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
            
            <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Feministische AI – vind patronen en verrassingen in AI door middel van een interactief spel </h3>
                <p class="">
                    Ontdek spelenderwijs hoe generatieve AI werkt, welke vooroordelen erin kunnen zitten en hoe je technologie eerlijker kunt maken door aandacht voor macht‐ en ongelijkheid.
                </p>
                <div class="">
                <img src="../images/wat_doen_we/kennis_vaardigheden/Wat_doen_we_%20Feministische_AI.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
</div></main>

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


