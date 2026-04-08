<?php
session_start();
ob_start(); // Start output buffering om debug output te voorkomen
require 'db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/phpmailer/src/Exception.php';
require 'vendor/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/src/SMTP.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Ongeldig email formaat.';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT email FROM accounts WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $message = 'Deze email is niet geregistreerd.';
        } else {
            // Generate a reset token (simple random string)
            $token = bin2hex(random_bytes(32));

            // For simplicity, store token in session with email (in production, use database)
            $_SESSION['reset_token'] = $token;
            $_SESSION['reset_email'] = $email;

            // Send email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                $mail->SMTPDebug = 0; // Debug uitgeschakeld voor veiligheid
                // Server settings - Elastic Email
                $mail->isSMTP();
                $mail->Host       = 'smtp.elasticemail.com'; // Elastic Email SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'indybrinkman2006@gmail.com'; // Vervang met je Elastic Email username (meestal je email)
                $mail->Password   = 'A9D9DCEB7DA750C8DDC4D599720EC836E1A9'; // Vervang met je Elastic Email SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 2525; // Of 587 als 2525 niet werkt
                // $mail->SMTPDebug  = 2; // Verwijder debug in productie

                // Recipients
                $mail->setFrom('indybrinkman2006@gmail.com', 'SociaalAI Lab');
                $mail->addAddress($email);


                // Content
                // Automatisch het juiste domein detecteren
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                // Verwijder :8000 poort voor live server
                $host = str_replace(':8000', '', $host);
                $resetLink = $protocol . "://" . $host . "/reset_password.php?token=" . $token;
                $mail->isHTML(false);
                $mail->Subject = 'Wachtwoord reset - SociaalAI Lab';
                $mail->Body    = "Klik op deze link om je wachtwoord te resetten: " . $resetLink . "\n\nAls je dit niet hebt aangevraagd, negeer deze email.";

                $mail->send();
                ob_clean(); // Clear any debug output from PHPMailer
                $success = true;
                $message = 'Er is een reset link naar je email gestuurd. Check ook je spam.';
            } catch (Exception $e) {
                ob_clean(); // Clear debug output on error too
                $message = 'Er is een fout opgetreden bij het verzenden van de email: ' . $mail->ErrorInfo;
            }
        }
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Wachtwoord vergeten - SociaalAI Lab">
    <title>Wachtwoord vergeten - SociaalAI Lab</title>
    <link rel="preload" href="build/assets/app-CAiCLEjY.js" as="script">
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-QWW4UvCRwT1iu11i/LCSVyitVqqkBIQviyLblhMlLKL6+0JSVDtB+cdcIUMyZVQd2+bwTKgCCAPEnjRBeWV2vQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">
    <div class="banner-wrapper relative">
        <img src="images/banner_website_01.jpg" alt="Banner 1" class="banner active h-60 md:h-96 w-full object-cover">
        <img src="images/banner_website_02.jpg" alt="Banner 2" class="banner h-60 md:h-96 w-full object-cover">
    </div>

    <?php
$navPrefix = '';
include __DIR__ . '/navbar.php';

?>

<main>
    <section class="bg-white shadow-lg rounded-2xl p-8 sm:p-10 w-[92%] max-w-md mx-auto my-12">
        <h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">Wachtwoord vergeten</h2>
        <?php if ($message): ?>
            <p class="text-center text-lg <?php echo $success ? 'text-green-600' : 'text-red-600'; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if (!$success): ?>
        <form method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-gray-700">E-mail:</label>
                <input type="email" id="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#00811F]">
            </div>
            <button type="submit" class="bg-[#00811F] text-white px-6 py-2 rounded-md hover:bg-green-700 transition">Verstuur reset link</button>
        </form>
        <?php endif; ?>
        <p class="text-center mt-4">
            <a href="login.php" class="text-[#00811F] hover:underline">Terug naar inloggen</a>
        </p>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<script>
    // Same navbar script as login.php
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
</script>
</body>
</html>
<?php ob_end_flush(); // Flush output buffer met de HTML, zonderde debug logs ?>