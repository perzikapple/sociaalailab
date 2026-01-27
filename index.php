<style>
@media (min-width: 1024px) {
  .flexrow {
    flex-direction: row;

  }
  .kolom2{
    margin: 0 10px;
  }
}
</style>


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
            <a href="register.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Registratie</a>
        </div>
    </div>
</nav>

<!-- Pagina content -->
<main>
    
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg mt- p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                Welkom bij het Sociaal AI Lab Rotterdam! Denk, doe en leer mee:</h2>
                <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">Samen maken we technologie sociaal.</h2>
            <p class="text-gray-700 leading-relaxed mb-4">
                In Rotterdam willen we dat iedereen mee kan doen in de digitale samenleving. Daarom is er nu het Sociaal AI Lab Rotterdam: een open plek in de stad waar bewoners, onderzoekers, ontwerpers en beleidsmakers samenwerken aan eerlijke, begrijpelijke en toegankelijke technologie.
            </p>
            <p class="text-gray-700 leading-relaxed mb-6">
                Rotterdammers ontdekken hier in gesprekken, bijeenkomsten, leer- en doe-activiteiten wat AI betekent voor hun dagelijks leven en denken mee over wat technologie wél of niet moet doen. 
            </p>
        </div>
    </section>
    <div class="flex flex-col flexrow justify-evenly w-full max-w-6xl mx-auto ">
        <!-- Kolom 1 -->
        <div class="space-y-6">

             <div class=" bg-white shadow-lg  pt-0 pb-6 mb-4 min-h-[220px] max-w-sm mx-auto ">
                <div class="flex flex-1 items-center justify-center">   
            <img src="images/Meedenken.png" alt="SociaalAI Inspiratiedag" class="w-auto h-32">
            </div>
                <h3 class="text-xl text-center font-semibold">Meedenken</h3>
                <p class="text-center p-4">
                Rotterdammers krijgen een stem bij het ontwikkelen en beoordelen van AI.
                </p>
            </div>
        </div>
         <!-- Kolom 2 -->
         <div class="space-y-6">
            <div class="kolom2 bg-white shadow-lg  pt-0 pb-6 mb-4 min-h-[220px] max-w-sm mx-auto ">
                <div class="flex flex-1 items-center justify-center">
            <img src="images/Samen_leren.png" alt="SociaalAI Inspiratiedag" class="w-auto h-32">
            </div>
                <h3 class="text-xl text-center font-semibold">Samen leren</h3>
                <p class="text-center p-4">
                 We vergroten de kennis over kansen en risico's van AI
                </p>
            </div>
        </div>
        <!-- Kolom 3 -->
        <div class="space-y-6">
            <div class="bg-white shadow-lg  pt-0 pb-6 min-h-[220px] max-w-sm mx-auto ">
                <div class="flex flex-1 items-center justify-center">
            <img src="images/Meedoen.png" alt="SociaalAI Inspiratiedag" class="w-auto h-32">
            </div>
                <h3 class="text-xl text-center font-semibold">Meedoen</h3>
                <p class="text-center p-4">
                We ontwikkelen samen oplossingen die passen bij de waarden van de stad
                </p>
            </div>
        </div>
    </div>
        <section class="flex flex-col md:flex-row items-center  gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
            <p class="text-1xl md:text-1xl  mb-4 text-gray-900">
            We onderzoeken samen wat kunstmatige intelligentie (AI) betekent voor het dagelijks leven in Rotterdam, en hoe we AI zo kunnen gebruiken dat het bijdraagt aan <strong>gelijke kansen voor iedereen.</strong>
            Het lab hoort bij het gemeentelijke programma <strong>Digitale Inclusie,</strong> dat ervoor zorgt dat alle Rotterdammers veilig, vaardig en volwaardig kunnen meedoen in de digitale wereld.
            </p>
            <p class=" text-[#00811F] text-lg">Kunstmatige Intelligentie? Technologie is pas echt slim als ze óók sociaal is.</p>
        </div></section>
        
<!--Event 1  -->
    <section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
        <span class="inline-block bg-[#00811F] text-white text-sm font-medium px-4 py-1 mb-4">
            Evenement
        </span>
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                 Studenten en bewoners verkennen de sociale invloed van AI
            </h2>
           <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <i class="fa-regular fa-calendar text-[#00811F] ml-[2px]  text-3xl"></i>
                    <p class="text-gray-700"><strong> Wanneer:</strong> 1 november - 1 februari 2025</p>
                </div>
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-location-dot text-[#00811F] ml-1 text-3xl"></i>
                    <p class="text-gray-700 ml-1 "><strong>Waar:</strong> Rotterdam - Hillevliet 90</p>
                </div>
                <div class="flex mb-6 space-x-3">
                    <i class="fa-solid fa-bullseye text-[#00811F] text-3xl"></i>
                    <p class="text-gray-700 pb-3 "><strong> Wat:</strong> Samen met bewoners uit de omgeving van de Hillevliet, onderzoeken studenten wat AI betekent voor de wijk. Ze vertalen dit gezamenlijk (co-creatie) naar een creatief eindresultaat, zoals een muurschildering, publicatie of interviewreeks.  </p>
                </div>
            </div>
        </div>
        <div class="flex-1">
            <img src="images/event/Agenda_event_2_Studenten_en_bewoners_verkennen_de_sociale_invloed_van_AI.jpg" alt="SociaalAI Inspiratiedag" class="w-full h-auto object-cover shadow-md">
        </div>
    </section>
 
<!--Event 2 -->
<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">
        <div class="flex-1">
        <span class="inline-block bg-[#00811F] text-white text-sm font-medium px-4 py-1 mb-4">
            Evenement
        </span>
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">
                 PizzAI-sessie van AI010
            </h2>
           <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <i class="fa-regular fa-calendar text-[#00811F] ml-[2px]  text-3xl"></i>
                    <p class="text-gray-700"><strong> Wanneer:</strong> Woensdag 11 februari om 18:00 (Inloop: 17:00)</p>
                </div>
                <div class="flex items-center space-x-3">
                    <i class="fa-solid fa-location-dot text-[#00811F] ml-1 text-3xl"></i>
                    <p class="text-gray-700 ml-1 "><strong>Waar:</strong> Rotterdam - Hillevliet 90, Ruimte 0.25</p>
                </div>
                <div class="flex mb-6 space-x-3">
                    <i class="fa-solid fa-bullseye text-[#00811F] text-3xl"></i>
                    <p class="text-gray-700 pb-3 "><strong> Wat:</strong> AI010 is de AI-community van Rotterdam, voor ondernemers, makers en vernieuwers. AI010 verbindt mensen die met AI aan de slag willen in hun bedrijf, organisatie of creatieve project. Deze avond werkt het Sociaal AI Lab samen met AI010 met een avond over AI. Aanmelding verloopt via AI010, zie onderstaande link naar de groepen van AI010.  </p>
                </div>
            </div>
             <a href="//www.ai010.nl/over#groepen" class="inline-block bg-[#00811F] text-white font-medium px-6 py-2 hover:bg-green-700 transition">
                    Meer info
                </a>
        </div>
    </section>
   
    <!--Hoogtepunten
      <div class="mobile flex  items-center justify-center">
         <div class="bg-white p-6  shadow-lg max-w-xl mt-6 w-full border-r text-center">
            <a href="https://sociaalailab.nl/hoogtepunten"><h1 class="text-2xl hover:text-[#00811F] font-semibold">Hoogtepunten</h1></a>
        </div>
    </div>
          
    <section id="slide" class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg max-w-6xl mx-auto my-12">
        
        <div class="slide1 flex-1 p-8">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">  
            OPEN MIDDAG EN OPEN AVOND IN HET AI LAB
            Ontdek wat slimme technologie kan betekenen 
            </h2>
           <div class="space-y-4">
                <div class="flex mb-6 space-x-3">
                    <p class="text-gray-700 pb-3 ">Ontdek de wereld van slimme technologie in het Sociaal AI Lab Rotterdam.
                    Op 17 december staan de deuren wijd open voor iedereen die nieuwsgierig is naar AI.
                    Praat met Digiderius, speel het Waardenspel en zie de Wijkbot in actie.
                    Maak een digitale kerstkaart, ontdek hoe technologie de stad ingaat en wat AI voor
                    jou kan betekenen. Vrije inloop.</div>
            </div>
              <a href="/event" class="inline-block bg-[#00811F] text-white font-medium px-6 py-2 hover:bg-green-700 transition">
                    Bekijk hoogtepunten
            </a>
        </div>
        <div class="slide1 flex-1">
            <img src="https://sociaalailab.nl/images/event/open_middag&amp;open_avond.jpg"
                 alt="SociaalAI Inspiratiedag"
                 class="w-full h-auto object-cover shadow-md">
        </div>

        <div class="slide2 flex-1 p-8">
            <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">  
            OPEN MIDDAG EN OPEN AVOND IN HET AI LAB
            Ontdek wat slimme technologie kan betekenen 
            </h2>
           <div class="space-y-4">
                <div class="flex mb-6 space-x-3">
                    <p class="text-gray-700 pb-3 ">Ontdek de wereld van slimme technologie in het Sociaal AI Lab Rotterdam.
                    Op 17 december staan de deuren wijd open voor iedereen die nieuwsgierig is naar AI.
                    Praat met Digiderius, speel het Waardenspel en zie de Wijkbot in actie.
                    Maak een digitale kerstkaart, ontdek hoe technologie de stad ingaat en wat AI voor
                    jou kan betekenen. Vrije inloop.</div>
            </div> 
              <a href="/event" class="inline-block bg-[#00811F] text-white font-medium px-6 py-2 hover:bg-green-700 transition">
                    Bekijk hoogtepunten
            </a>
        </div>
        <div class="slide2 flex-1">
            <img src="https://sociaalailab.nl/images/event/open_middag&amp;open_avond.jpg"
                 alt="SociaalAI Inspiratiedag"
                 class="w-full h-auto object-cover shadow-md">
        </div>

    </section>
    <style>
        .slide2 {
            display: none;
            transform: translateX(210%);
            padding-right: 32px;
        }
        #slide {
            overflow: hidden;
        }
        .slide1 {
        padding-right: 32px;
        position: relative;      /* zodat we left kunnen gebruiken */
        width: 100%;
        transition: left 0.5s ease-in-out;
        animation: slideLeft 5s forwards;
        animation-delay: 10s;     /* wachten 5 seconden voordat animatie start */
        overflow:   hidden;
}

/* animatie */
@keyframes slideLeft {
  0% {
    left: 0;               /* beginpositie */
  }
  100% {
    left: -100%;           /* helemaal naar links */
  }
}   
    </style>
-->
<a href="contact.html">
    <div class="flex items-center justify-center">
        <i class="text-[#cc0033] fa-3x fa-regular fa-envelope-open"></i>
    </div>
    <h2 class="text-center text-2xl md:text-xl font-bold text-white mb-2">Neem Contact Op</h2>
</a>

    
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
