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
        <img class="" src="../images/banner_website_01.jpg">
    </div>
    <div class="banner banner-2">
        <img class="" src="../images/banner_website_02.jpg">
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
            <a href="../index.htm" class="menu block m-4 text-gray-700 hover:text-[#00811F]  transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="../agenda.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="../over.html" class="menu block  m-4 text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

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
                    <a href="kennis.html" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="actie.html" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="faciliteit.html" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab
                    </a>
                    <!-- voeg meer items toe naar behoefte -->
                </div>
            </div>

            <a href="../verantwoord-ai.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="../wie-zijn-we.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="../contact.html" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Contact</a>
        </div>
    </div>
</nav>

<!-- Pagina content -->
<main>
    
    <div class="mobile flex  items-center justify-center">
         <div class="bg-white p-6  shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="kennis.html"><h1 class="text-2xl text-[#00811F] font-semibold">Kennis & Vaardigheden</h1></a>
        </div>
        <div class="bg-white p-6 max-w-xl mt-6 w-full text-center border-r border-gray-500">
            <a href="actie.html"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Actie, Onderzoek & Ontwerp</h1></a>
        </div>
         <div class="bg-white p-6  max-w-xl mt-6 w-full text-center">
            <a href="faciliteit.html"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Faciliteiten</h1></a>
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
