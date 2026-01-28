<?php session_start(); ?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>Initiatiefnemers</title>
    <meta name="description" content="SociaalAI helpt inwoners sterker te staan in een steeds digitalere wereld. We doen dit door Rotterdammers actief mee te laten denken, praten en beslissen over kunstmatige intelligentie.">
    <link rel="icon" type="image/png" href="images/Pixels_icon.png">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">


<div class="banner-wrapper">
    <div class="banner banner-1 active">
        <img class="" src="images/banner_website_01.jpg">
    </div>
    <div class="banner banner-2">
        <img class="" src="images/banner_website_02.jpg">
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
            <a href="login.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">login</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                <a href="admin.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Pagina content -->
<main>
    
     
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl min-h-[360px] mx-auto my-12">
        <div class="flex-1">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                Gemeente Rotterdam
            </h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                De gemeente Rotterdam is initiatiefnemer binnen het programma Digitale Inclusie.
                Met het actieplan zet de gemeente zich in om alle inwoners digitaal vaardiger te maken,
                zodat iedereen veilig en zelfstandig kan meedoen in de digitale samenleving.
            </p>
        </div>

        <div class="flex-1">
            <img src="images/Gemeente_Rotterdam.png" alt="Gemeente Rotterdam" class="w-full h-auto object-contain">
        </div>
    </section>

    
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl min-h-[360px] mx-auto my-12">

        <div class="flex-1">
            <img src="images/Hogeschool_Rotterdam.png" alt="Hogeschool Rotterdam" class="w-full h-auto object-contain">
        </div>

        <div class="flex-1">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                Hogeschool Rotterdam
            </h2>

            <p class="text-gray-700 leading-relaxed mb-4">
                De Hogeschool Rotterdam doet praktijkgericht onderzoek om kennis en innovaties te ontwikkelen met die direct toe te passen is in de praktijk.'
                Onder meer door mensen direct vanaf het begin te betrekken in het ontwerpproces van digitale toepassingen (co-design) en samen met Rotterdammers te experimenteren door prototypes te maken (civic prototyping).
        </p></div>
    </section>

       
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl min-h-[360px] mx-auto my-12">
        <div class="flex-1">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                Techniek College Rotterdam
            </h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                Het Techniek College Rotterdam betrekt mbo-studenten actief bij het ontwikkelen van praktische AI-toepassingen.
                Met projecten, workshops en praktijkgericht onderzoek draagt het college bij aan een toekomst waarin technologie
                begrijpelijk en toegankelijk is voor iedereen.
            </p>
        </div>

        <div class="flex-1 flex items-center justify-center">
            <img src="images/Techniek_College_Rotterdam_logo.png" alt="Techniek College Rotterdam" class="w-auto object-cover p-8">
        </div>
    </section>

    
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl min-h-[360px] mx-auto my-12">

        <div class="flex-1">
            <img src="images/Erasmus_uni.png" alt="Erasmus Universiteit Rotterdam" class="w-full h-auto max-h-[250px] object-contain mx-auto">
        </div>

        <div class="flex-1">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                Erasmus Universiteit Rotterdam
            </h2>

            <p class="text-gray-700 leading-relaxed mb-4">
                De Erasmus Universiteit onderzoekt hoe mensen en technologie beter kunnen samenwerken en wil via onder andere co-creatie bijdragen aan het verantwoord gebruik van AI met aandacht voor sociale en economische waarde. Het <strong>Erasmus Centre for Data Analytics (ECDA) </strong>is hierin de trekker en verbindt wetenschap, bedrijfsleven en overheid rond de gevolgen van data, kunstmatige intelligentie, en digitalisering. Het doel is om AI te stimuleren die waardevol is voor de samenleving.</p>
        </div>
    </section>
    
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl min-h-[360px] mx-auto my-12">
    <ul class="list-disc pl-10 space-y-3 marker:text-gray-600 items-center">
            <h1>Vanaf het begin werken we hierin nauw samen met diverse sociale en technologie partners, waaronder:</h1>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Wijkwijs</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Netwerk Digitale Inclusie</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Bibliotheek</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Trefpunt Vreewijk</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">De Buurtouders010</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Equals</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Future Society Lab</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Parai</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Mascotte.ai</h2>
                </li>
                <li class="text-gray-700 leading-relaxed">
                    <h2 class="font-bold">Civic AI Lab Amsterdam</h2>
                </li>
                <p>Samen met deze partners werken we aan technologische oplossingen die bijdragen aan het dagelijks leven in Rotterdam. Wij geloven dat technologie pas echt slim is als het ook sociaal is.  </p>
                <p class="text-[#00811F]">AI voor Rotterdammers door Rotterdammers.</p>
    </ul></section>
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
