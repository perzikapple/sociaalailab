<?php 
session_start();
require 'db.php';

// Fetch banners
$banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: 'images/banner_website_01.jpg';
$banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: 'images/banner_website_02.jpg';
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><link rel="stylesheet" href="custom.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>Informatie SociaalAI Lab</title>
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

<!-- Navigatie -->
<nav class="bg-white shadow-md">
    <div class="navigatie max-w-6xl mx-auto px-4 py-3 flex justify-center md:justify-between items-center">
        <!-- Hamburger knop alleen op mobiel -->
        <button id="mobile-menu-toggle" class=" hamburger md:hidden self-end text-gray-700 focus:outline-none" aria-label="Open navigatie">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>

        <!-- Menu links (exact dezelfde inhoud, alleen ingepakt + id + hidden-klasse) -->
        <div id="mobile-menu" class="menu hidden md:flex pr-5 space-x-8 font-medium">
            <a href="index.php" class="menu block m-4 text-gray-700 hover:text-[#00811F]  transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="agenda.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="terugblikken.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Terugblikken</a>
            <a href="over.php" class="menu block  m-4 text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

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
                    <a href="programma/kennis.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="programma/actie.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="programma/faciliteit.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab
                    </a>
                    <!-- voeg meer items toe naar behoefte -->
                </div>
            </div>

            <a href="verantwoord-ai.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="wie-zijn-we.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="contact.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Contact</a>
            <?php if (isset($_SESSION['user'])): ?>
                <a href="logout.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Logout</a>
            <?php else: ?>
                <a href="login.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Login</a>
                <a href="registratie.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Registratie</a>
            <?php endif; ?>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                <a href="admin.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Pagina content -->
<main>
          <section class="flex flex-col md:flex-row items-center  gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
            <h1 class="font-bold text-2xl">De Maatschappelijke Code voor Verantwoorde AI</h1>
            <p class="text-1xl md:text-1xl  mb-4 text-gray-900">
            Het Sociaal AI Lab werkt volgens de <strong>Rotterdamse Maatschappelijke Code voor Verantwoorde AI.</strong> 
            Deze afgesproken regels zorgen ervoor dat technologie in onze stad eerlijk, veilig en mensgericht blijft.
            Het is een document dat we blijven aanvullen, terwijl we samen leren en inzichten opdoen in het lab. 
            Het vormt een kader voor alle projecten en activiteiten. 
            </p>
            <p>Het ontwerpen en gebruiken van AI-toepassingen moet aan onze zes kernwaarden voldoen:</p>
        </div></section>
<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
    <div class="flex flex-1 justify-center items-center text-[#00811F]">
        <i class="fa-solid fa-1 text-9xl"></i>
    </div>
    <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Menselijke waardigheid
        </span>
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
        Ondersteunt mensen - vervangt ze niet.
        </h2>
    </div>
</section>

<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
    <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Gelijkheid en inclusie
        </span>
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
            Bevordert gelijkheid en toegankelijkheid zonder groepen uit te sluiten of ongelijkheid te vergroten.
        </h2>
    </div>
     <div class="flex flex-1 justify-center items-center text-[#00811F]">
            <i class="fa-solid fa-2 text-9xl"></i>
        </div>
</section>

<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
    <div class="flex flex-1 justify-center items-center text-[#00811F]">
        <i class="fa-solid fa-3 text-9xl"></i>
    </div>
    <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Transparantie
        </span>
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
        Maakt transparant hoe het werkt - zodat iedereen weet hoe en waarom AI wordt ingezet.
        </h2>
    </div>
</section>

<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
    <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Privacy en gegevensbescherming
        </span>
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
            Beschermt privacy en gegevens door zorgvuldig om te gaan met persoonlijke informatie. 
        </h2>
    </div>
    <div class="flex flex-1 justify-center items-center text-[#00811F]">
        <i class="fa-solid fa-4 text-9xl"></i>
    </div>
</section>

<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
    <div class="flex flex-1 justify-center items-center text-[#00811F]">
        <i class="fa-solid fa-5 text-9xl"></i>
    </div>
    <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Veiligheid en betrouwbaarheid
        </span>
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
        Werkt veilig en betrouwbaar en doet wat we verwachten, zonder onnodige risico's.
        </h2>
    </div>
</section>

<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
    <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Verantwoording
        </span>
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
            Houdt mensen verantwoordelijk - beslissingen blijven altijd in menselijke handen, niet bij de technologie.
        </h2>
    </div>
    <div class="flex flex-1 justify-center items-center text-[#00811F]">
            <i class="fa-solid fa-6 text-9xl"></i>
        </div>
</section>
<div class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
 <img src="images/Code_of_Conduct.jpg" alt="Code of Conduct" class=" w-auto h-auto">
                 
</div>
</main>

<footer class="bg-white mt-16 shadow-inner">
    <div class="flex justify-evenly py-6 items-center space-x-4">

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="logo techniek collage Rotterdam" src="images/Techniek_College_Rotterdam_logoOP.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="logo hogeschool Rotterdam" src="images/Hogeschool_Rotterdam.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="logo gemeente Rotterdam " src="images/Gemeente_Rotterdam.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="erasmus universiteit" src="images/Erasmus_uni.png" class="max-w-full max-h-full object-contain">
        </div>

        <div class="w-32 h-20 flex items-center justify-center">
            <img alt="Erasmus Centre for Data Analytics" src="images/Erasmus_DataOP.png" class="max-w-full max-h-full object-contain">
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
