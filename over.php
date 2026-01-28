<?php session_start(); ?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>SociaalAI Lab</title>
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
            <a href="agenda.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="over.html" class="menu block  m-4 text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

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
                    <a href="programma/kennis.html" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="programma/actie.html" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="programma/faciliteit.html" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab
                    </a>
                    <!-- voeg meer items toe naar behoefte -->
                </div>
            </div>

            <a href="verantwoord-ai.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="wie-zijn-we.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="contact.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Contact</a>
            <a href="login.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">login</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                <a href="admin.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Pagina content -->
<main>
    
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Voor wie is het Sociaal AI Lab?
        </span>
            <p class="text-gray-700 leading-relaxed mb-4">
                <strong>Het Lab is er voor:</strong><br>
            <ul>
                <li>a. Rotterdammers die geraakt worden door digitale ontwikkelingen, maar niet altijd worden betrokken bij besluitvorming.</li>
                <li>b. Burgers die meer over AI willen weten, zoals betrokken denkers en doeners die actief zijn in het sociaal domein en die willen bijdragen aan de digitale toekomst van de stad. </li>
                <li>c. Inwoners die veel werken met AI en hun kennis met andere Rotterdammers willen delen.</li>
                <li>d.  Experts die samen met andere Rotterdammers willen ontdekken wat AI voor hen kan betekenen en hoe inwoners AI (willen) gebruiken.</li>
            </ul>
            
        </div>






    </section>

    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">






        <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
            Wat willen we met het Sociaal AI Lab?
        </span>
            <p class="text-gray-700 leading-relaxed mb-4">
             Het Sociaal AI Lab Rotterdam versterkt de positie van inwoners in een steeds digitalere samenleving. We doen dat door Rotterdammers actief te betrekken bij het ontwikkelen, begrijpen en beoordelen van AI-toepassingen die invloed hebben op hun leven. Het lab is een ontmoetingsplek waar kennis en praktijk samenkomen. Hier leren we van elkaar, delen we ervaringen en maken we technologie die werkt vóór en mét de stad.</p>
        </div>
    </section>

    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
        <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
        Hoe willen we dat doen?
        </span>
            <p class="text-gray-700 leading-relaxed mb-4">
            Technologie heeft pas echt waarde als die bijdraagt aan een samenleving waarin iedereen mee kan doen. In het Sociaal AI Lab werken we aan een digitale stad die recht doet aan iedereen. We willen dat álle Rotterdammers, juist ook degenen die vaak minder gehoord worden, invloed hebben op hoe technologie wordt gebruikt.
            Daarom organiseren we gesprekken, workshops, experimenten en ontwerpsessies waarin inwoners, ontwerpers en onderzoekers samen keuzes maken over hoe AI bijdraagt aan onze stad.
            </p>
        </div>






    </section>

    <section class="flex flex-col md:flex-col  gap-8 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <p class="flex-1">
       <span class="inline-block bg-green-100 text-green-800 text-sm font-medium px-4 py-1 mb-4">
           Wat zijn onze doelen?
        </span>



        <ul class="list-decimal pl-10 space-y-3 marker:text-gray-600 items-center">
            <li class="text-gray-700 leading-relaxed">
                <h2 class="font-bold">Participatie en inclusie versterken</h2>
                Rotterdammers krijgen een stem bij de ontwikkeling van AI en denken mee over wat technologie wel of juist níet moet doen.
            </li>

            <li class="text-gray-700 leading-relaxed">
                <h2 class="font-bold">Bewustwording en kennis vergroten</h2>
                We leren samen wat AI is, wat het kan, en waar we alert op moeten zijn, zodat iedereen beter begrijpt hoe technologie werkt.
            </li>

            <li class="text-gray-700 leading-relaxed">
               <h2 class="font-bold"> Verbinding met maatschappelijke thema’s</h2>
                We koppelen AI aan echte sociale uitdagingen in zorg, onderwijs, werk, veiligheid, duurzaamheid en welzijn.
            </li>

            <li class="text-gray-700 leading-relaxed">
                <h2 class="font-bold">Werken vanuit publieke waarden</h2>
               In alles wat we doen staan menselijke waardigheid, gelijkheid, transparantie, privacy, veiligheid en verantwoording centraal. </li>
            
           <li class="text-gray-700 leading-relaxed">
                <h2 class="font-bold">Toegankelijke infrastructuur</h2>
               Het lab biedt ruimte, fysiek op Hillevliet 90 én digitaal, waar Rotterdammers samen kunnen leren, ontdekken en experimenteren met AI. </li>
        </ul>
    </section>


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
