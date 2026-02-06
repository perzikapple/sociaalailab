<?php 
session_start();
require '../db.php';

// Fetch banners
$banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: '../images/banner_website_01.jpg';
$banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: '../images/banner_website_02.jpg';

// Adjust paths for subfolder
$banner1 = str_replace(['images/', 'uploads/'], ['../images/', '../uploads/'], $banner1);
$banner2 = str_replace(['images/', 'uploads/'], ['../images/', '../uploads/'], $banner2);

// Fetch text blocks for programma-actie page
$stmt = $pdo->prepare("SELECT * FROM pages WHERE page_key = 'programma-actie' ORDER BY created_at ASC");
$stmt->execute();
$pageBlocks = $stmt->fetchAll();
?>
<style>
@media (max-width: 1024px) {
  .mobile{
    flex-direction: column;
  }
  .mobile-col{
    flex-direction: column;
  }
}
</style>
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
</div>

<!-- Navigatie -->
<nav class="bg-white shadow-md">
    <div class="navigatie max-w-6xl mx-auto px-4 py-3 flex justify-center md:justify-between items-center">

        <!-- Hamburger knop alleen op mobiel -->
        <button id="mobile-menu-toggle" class=" hamburger md:hidden self-end text-gray-700 focus:outline-none" aria-label="Open navigatie">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>

        <!-- Menu links (exact dezelfde inhoud, alleen ingepakt + id + hidden-klasse) -->
        <div id="mobile-menu" class="menu hidden md:flex pr-5 space-x-8 font-medium">
            <a href="../index.php" class="menu block m-4 text-gray-700 hover:text-[#00811F]  transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="../agenda.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="../terugblikken.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Terugblikken</a>
            <a href="../over.php" class="menu block  m-4 text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

            <!-- Programma met dropdown -->
            <div class="relative" id="programma-dropdown">
                <!-- Toggle knop -->
                <button id="programma-toggle" aria-haspopup="true" aria-expanded="false" class="menu flex items-center gap-2 text-gray-700 hover:text-[#00811F] transition font-medium focus:outline-none">
                    <span>Wat doen we?</span>
                    <!-- pijl die roteert bij open -->
                    
                </button>

                <!-- Dropdown menu (verborgen standaard) -->
                <div id="programma-menu" class="hidden absolute top-0 mt-8 w-56 bg-white border border-gray-200 shadow-lg py-2 z-50 focus:outline-none" role="menu" aria-labelledby="programma-toggle">
                    <!-- Elke link is role=menuitem voor a11y -->
                    <a href="kennis.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="actie.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="faciliteit.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab
                    </a>
                    <!-- voeg meer items toe naar behoefte -->
                </div>
            </div>

            <a href="../verantwoord-ai.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="../wie-zijn-we.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="../contact.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Contact</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="../logout.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Logout</a>
            <?php else: ?>
                <a href="../login.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Login</a>
            <?php endif; ?>
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
            <a href="kennis.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Kennis & Vaardigheden</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="actie.php"><h1 class="text-2xl text-[#00811F] font-semibold">Actie, Onderzoek & Ontwerp</h1></a>
        </div>
         <div class="bg-white p-6  max-w-xl mt-6 w-full text-center">
            <a href="faciliteit.php"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Faciliteiten</h1></a>
        </div>
    </div>
             
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8 p-6">
        <!-- Kolom 1 -->
        <div class="flex flex-col justify-between space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Digiderius – de digitale Erasmus</h3>
                <p class="">
                Digiderius is een “digitaal mens” waarmee deelnemers kunnen praten over onderwerpen als onderwijs, cultuur en technologie. Ontdek zelf hoe jij deze digitale gesprekspartner ervaart. Dit helpt ons te begrijpen wat Rotterdammers nodig hebben om met deze “chatbots” prettig om te kunnen gaan.</p>
                <div class="flex-1">
                <img src="../images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_%20Digiderius.jpeg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
            
            <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Samen AI-toepassingen ontwerpen</h3>
                <p class="">
                    Samen met bewoners ontwerpen we AI-oplossingen voor sociale vraagstukken, bijvoorbeeld rond armoede, zorg of veiligheid. Rotterdammers zetten tijdens deze activiteiten hun behoeftes aan eerlijke technologie en praktische oplossingen om in de praktijk
                </p>
                <div class="">
                <img src="../images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_Samen_AI_toepassingen_ontwerpen.jpeg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>

        <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Inclusieve zorg en AI</h3>
                <p class="">
                    Met Rotterdamse vrouwen en non-binaire personen van kleur bespreken we uitsluiting en vooroordelen in de zorg. Samen vertalen we hun ervaringen naar principes voor eerlijke en inclusieve AI. Deze inzichten delen we met onderzoekers, beleidsmakers en organisaties om ze in praktijk te brengen.
                </p>
                <div class="">
                <img src="../images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_%20Inclusieve_AI_in_de_Zorg.JPG" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>

             <!-- Kolom 2 -->
            <div class="flex flex-col justify-between space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Ondersteuning aan bewoners verbeteren met AI?</h3>
                <p class="">
                We onderzoeken samen hoe AI kan helpen om Rotterdammers met weinig digitale ervaring beter te ondersteunen. In de sessies werken we met vrijwilligers van het Trefpunt, zodat zij bewoners nog beter kunnen helpen. 
                </p>
                <div class="">
                <img src="../images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_Ondersteuning_aan_bewoners_verbeteren_%20met_AI.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
            
            <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Verantwoorde AI binnen organisaties (ELSA-aanpak)</h3>
                <p class="">
                  We willen begrijpen of AI goed, rechtvaardig en maatschappelijk verantwoord is, vooral als we het inzetten in publieke diensten. Daarom organiseren we workshops met mensen uit overheid, bedrijfsleven, samenleving en de natuur, zodat we vanuit gedeelde waarden kunnen bepalen welke technologie we gebruiken en hoe. 
                <div class="">
                <img src="../images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_Verantwoorde_AI_binnen%20_Organizaties_%28ELSA-aanpak%29.png" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>

        <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Studenten en bewoners verkennen de sociale invloed van AI</h3>
                <p class="">
                    Samen met bewoners uit de omgeving van de Hillevliet, onderzoeken studenten wat AI betekent voor de wijk. Dit vertalen ze gezamenlijk (co-creatie) naar een creatief eindresultaat, zoals een muurschildering, publicatie of interviewreeks.</p>
                <div class="">
                <img src="../images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_%20Studenten_en_bewoners_verkennen_de_sociale_invloed_van_AI.jpg" alt="SociaalAI Inspiratiedag" class="w-auto h-64 object-cover">
                </div>
            </div>
            
            <div class="flex flex-col justify-between relative space-y-6 space-x-6 bg-white p-6">
                <h3 class="text-xl font-semibold mb-4">Wijkbots – in te zetten voor een betrokken stad? </h3>
                <p class="">
                  We kijken hoe slimme, zelfstandige machines in de toekomst kunnen helpen in onze openbare ruimtes. Denk aan bots die buurtinformatie verzamelen, bewoners ondersteunen of lokale diensten ondersteunen. We onderzoeken hoe deze technologie kan bijdragen aan een stad die beter luistert naar haar inwoners.<div class="flex-1">
                <img src="../images/wat_doen_we/actie_onderzoek_ontwerp/Wat_doen_we_wijkbots.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-64 object-cover">
                </div>
            </div>
</div></main>

<footer class="bg-white mt-16 shadow-inner">
    <div class="flex justify-evenly py-6 items-center space-x-4">

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="logo techniek collage Rotterdam" src="../images/Techniek_College_Rotterdam_logoOP.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="logo hogeschool Rotterdam" src="../images/Hogeschool_Rotterdam.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="logo gemeente Rotterdam " src="../images/Gemeente_Rotterdam.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="erasmus universiteit" src="../images/Erasmus_uni.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="Erasmus Centre for Data Analytics" src="../images/Erasmus_DataOP.png" class="max-w-full max-h-full object-contain">
        </div>

    </div>
</footer>

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

<!-- Extra script alleen voor het mobiele hamburger-menu -->
    (function () {
        const mobileToggle = document.getElementById('mobile-menu-toggle');
        const mobileMenu = document.getElementById('mobile-menu');

        if (!mobileToggle || !mobileMenu) return;

        mobileToggle.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
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
  grid-area: 1 / 1; /* zelfde grid-cel */
}

/* voorbeeld fade */
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

/* Mobile fix */
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
</style>
