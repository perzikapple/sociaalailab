<?php session_start(); ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preload" as="style" href="build/assets/app-DozK-03z.css"><link rel="modulepreload" as="script" href="build/assets/app-CAiCLEjY.js"><link rel="stylesheet" href="build/assets/app-DozK-03z.css"><script type="module" src="build/assets/app-CAiCLEjY.js"></script>
    <title>Registratie - SociaalAI Lab</title>
    <meta name="description" content="Registreer je bij het SociaalAI Lab Rotterdam.">
    <link rel="icon" type="image/png" href="images/Pixels_icon.png">
    <link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">

<div class="banner-wrapper">
    <div class="banner banner-1 active">
        <img src="images/banner_website_01.jpg">
    </div>
    <div class="banner banner-2">
        <img src="images/banner_website_02.jpg">
    </div>
</div>

<!-- Navigatie -->
<nav class="bg-white shadow-md">
    <div class="navigatie max-w-6xl mx-auto px-4 py-3 flex justify-center md:justify-between items-center">
        <button id="mobile-menu-toggle" class=" hamburger md:hidden self-end text-gray-700 focus:outline-none" aria-label="Open navigatie">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>

        <div id="mobile-menu" class="menu hidden md:flex pr-5 space-x-8 font-medium">
            <a href="index.php" class="menu block m-4 text-gray-700 hover:text-[#00811F]  transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="agenda.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="over.php" class="menu block  m-4 text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

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
<main class="flex items-center justify-center min-h-screen px-4">
    <div class="bg-white p-8 shadow-lg max-w-md w-full rounded-lg">
        <h1 class="text-3xl font-semibold text-[#00811F] mb-6 text-center">Registratie</h1>

        <?php
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $ww = trim($_POST['ww'] ?? '');
            $ww_confirm = trim($_POST['ww_confirm'] ?? '');

            if (empty($email) || empty($ww) || empty($ww_confirm)) {
                $error = 'Alle velden zijn verplicht.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Ongeldig e-mailadres.';
            } elseif (strlen($ww) < 8) {
                $error = 'Wachtwoord moet minstens 8 tekens zijn.';
            } elseif ($ww !== $ww_confirm) {
                $error = 'Wachtwoorden komen niet overeen.';
            } else {
                try {
                    require 'db.php';
                    
                    $stmt = $pdo->prepare('SELECT id FROM accounts WHERE email = ?');
                    $stmt->execute([$email]);
                    if ($stmt->rowCount() > 0) {
                        $error = 'Dit e-mailadres is al geregistreerd.';
                    } else {
                        $ww_hash = password_hash($ww, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare('INSERT INTO accounts (email, wachtwoord) VALUES (?, ?)');
                        $stmt->execute([$email, $ww_hash]);
                        $success = 'Registratie succesvol! <a href="login.php" class="text-[#00811F] font-semibold">Log hier in</a>';
                    }
                } catch (PDOException $e) {
                    $error = 'Databasefout: ' . $e->getMessage();
                }
            }
        }
        ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">E-mailadres</label>
                <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00811F]" required>
            </div>

            <div>
                <label for="ww" class="block text-gray-700 font-medium mb-2">Wachtwoord</label>
                <input type="password" id="ww" name="ww" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00811F]" required>
                <small class="text-gray-500">Minimaal 8 tekens</small>
            </div>

            <div>
                <label for="ww_confirm" class="block text-gray-700 font-medium mb-2">Bevestig wachtwoord</label>
                <input type="password" id="ww_confirm" name="ww_confirm" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-[#00811F]" required>
            </div>

            <button type="submit" class="w-full bg-[#00811F] text-white font-medium px-6 py-2 rounded-lg hover:bg-green-700 transition">
                Registreer
            </button>
        </form>

        <p class="text-center text-gray-600 mt-6">
            Heb je al een account? <a href="login.php" class="text-[#00811F] font-semibold hover:underline">Log in</a>
        </p>
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
  grid-area: 1 / 1;
}

.banner {
  opacity: 0;
  transition: opacity 1s ease;
}

.banner.active {
  opacity: 1;
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
}

@media (min-width: 760px) {
    .hamburger{
        display: none;
    }
}
</style>