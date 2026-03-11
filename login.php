<?php
session_start();
require 'db.php';

$banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: 'images/banner_website_01.jpg';
$banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: 'images/banner_website_02.jpg';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid email format.';
    } else {
        // Look up user in database
        $stmt = $pdo->prepare("SELECT email, wachtwoord, admin FROM accounts WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = 'Email or password incorrect.';
        } elseif ($pass !== $user['wachtwoord']) {
            $message = 'Email or password incorrect.';
        } else {
            // Set session and redirect
            $_SESSION['user'] = $user['email'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['admin'] = $user['admin'];
            header('Location: admin.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Log in bij het SociaalAI Lab">
    <title>Log in - SociaalAI Lab</title>
    <link rel="preload" href="build/assets/app-CAiCLEjY.js" as="script">
    <link rel="preload" href="build/assets/app-DozK-03z.css" as="style">
    <link rel="stylesheet" href="build/assets/app-DozK-03z.css">
    <link rel="stylesheet" href="custom.css?v=<?php echo filemtime(__DIR__.'/custom.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-QWW4UvCRwT1iu11i/LCSVyitVqqkBIQviyLblhMlLKL6+0JSVDtB+cdcIUMyZVQd2+bwTKgCCAPEnjRBeWV2vQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">
    <div class="banner-wrapper relative">
        <img src="<?php echo htmlspecialchars($banner1); ?>" alt="Banner 1" class="banner active h-60 md:h-96 w-full object-cover">
        <img src="<?php echo htmlspecialchars($banner2); ?>" alt="Banner 2" class="banner h-60 md:h-96 w-full object-cover">
    </div>

    <?php
$navPrefix = '';
include __DIR__ . '/navbar.php';
?>

<main>
    <section class="bg-white shadow-lg rounded-2xl p-8 sm:p-10 w-[92%] max-w-md mx-auto my-12">
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">Log in bij het SociaalAI Lab</h2>
        <?php if ($message): ?>
            <p class="text-center text-lg <?php echo strpos($message, 'successful') !== false ? 'text-green-600' : 'text-red-600'; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-gray-700">E-mail:</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00811F]">
            </div>
            <div>
                <label for="password" class="block text-gray-700">Wachtwoord:</label>
                <input type="password" id="password" name="password" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00811F]">
            </div>
            <button type="submit" class="bg-[#00811F] text-white px-6 py-2 rounded-md hover:bg-green-700 transition">Log in</button>
        </form>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

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
