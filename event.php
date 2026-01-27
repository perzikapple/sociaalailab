<style>
.margin{
    margin:36px;
}        

@media (max-width: 1024px) {
  .mobile{
    flex-direction: column;
  }
  .margin{
    margin-top:20px;
    margin-bottom: 0;
  }
}

</style> 

<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>    <title>Event</title>
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
            <a href="index.htm" class="menu block m-4 text-gray-700 hover:text-[#00811F]  transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
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
        </div>
    </div>
</nav>

<!-- Pagina content -->
<main>
        <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
        <span class="inline-block bg-[#00811F] text-white text-sm font-medium px-4 py-1 mb-4">
            Evenement
        </span>
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">  
            OPEN MIDDAG EN OPEN AVOND IN HET AI LAB
            Ontdek wat slimme technologie kan betekenen 
            </h2>
           <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <i class="fa-regular fa-calendar text-[#00811F] ml-[2px]  text-3xl"></i>
                    <p class="text-gray-700"><strong> Wanneer:</strong> Woensdag 17 december</p>
                </div>
                <div class="flex items-center space-x-3">
                    <i class="fa-regular fa-clock text-[#00811F] text-3xl"></i>
                    <p class="text-gray-700"><strong>Hoelaat:</strong> Tussen 15:00 en 21:00 uur</p>
                </div>
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-location-dot text-[#00811F] ml-1 text-3xl"></i>
                    <p class="text-gray-700 ml-1 "><strong>Waar:</strong> Rotterdam - Hillevliet 90</p>
                </div>
                <div class="flex mb-6 space-x-3">
                    <i class="fa-solid fa-bullseye text-[#00811F] text-3xl"></i>
                    <p class="text-gray-700 pb-3 "><strong> Wat:</strong> Ontdek de wereld van slimme technologie in het Sociaal AI Lab Rotterdam.
                    Op 17 december staan de deuren wijd open voor iedereen die nieuwsgierig is naar AI.
                    Praat met Digiderius, speel het Waardenspel en zie de Wijkbot in actie.
                    Maak een digitale kerstkaart, ontdek hoe technologie de stad ingaat en wat AI voor
                    jou kan betekenen. Vrije inloop.</p></div>
            </div>
        </div>
        <div class="flex-1">
            <img src="images/event/open_middag%26open_avond.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-auto object-cover shadow-md">
        </div>
    </section>
    <div class="mobile flex justify-evenly">

        <div class="margin bg-white shadow-lg p-4">
            <div class=" p-6  ">
                <h3 class="text-xl font-semibold ">Tijdens de open middag kunt u:</h3>
                <ul class="list-disc list-inside">
                    <li>In gesprek met Digiderius: Wat als je gewoon in gesprek kan gaan met AI?</li>
                    <li>Het Waardenspel spelen: wat vindt u belangrijk in uw omgeving?</li>
                    <li> De Wijkbot zien werken</li>
                    <li> Uw eigen digitale kerstkaart maken</li>
                    <li> De Labkar bekijken: waarmee we slimme technologie naar andere plekken in de stad brengen</li>
                </ul>
            </div>
        </div>

                <div class=" margin bg-white shadow-lg p-4">
                    <div class=" p-6">
                        <h3 class="text-xl font-semibold ">Wat is er te beleven?</h3>
                        <ul class="list-disc list-inside">
                            <li>In gesprek met Digiderius</li>
                            <li>Ervaren hoe praten en een gesprek met AI werkt.</li>
                            <li>Het Waardenspel</li>
                            <li>Onderzoeken wat belangrijk is in de wijk en hoe technologie kan ondersteunen.</li>
                            <li>De Wijkbot in actie</li>
                            <li>Bekijk hoe deze robot in huis, tuin, keuken of in een huis van de wijk zou kunnen worden ingezet.</li>
                            <li>De Labkar</li>
                            <li>Ontdekken hoe we slimme technologie naar andere plekken in de stad brengen.</li>
                            <li>Digitale kerstkaarten maken</li>
                            <li>Een creatieve kennismaking met slimme tools</li>
                        </ul>

                    </div>
                </div>     
</div></main>

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
