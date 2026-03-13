<?php
if (!isset($navPrefix)) {
    $navPrefix = '';
}
?>
<nav class="bg-white shadow-md sticky top-0 z-40">
    <div class="flex justify-center items-center px-4 md:px-8 py-4">

        <button id="mobile-menu-toggle" class="md:hidden hamburger focus:outline-none" aria-label="Toggle navigation" aria-expanded="false" aria-controls="mobile-menu">
            <i class="fa-solid fa-bars text-2xl"></i>
        </button>

        <div id="mobile-menu" class="menu hidden md:flex pr-5 space-x-8 font-medium items-center">
            <a href="<?php echo htmlspecialchars($navPrefix); ?>index.php" class="menu inline-flex items-center gap-1 text-gray-700 hover:text-[#00811F] transition"><i class="fa-solid fa-house"></i> Voorpagina</a>
            <a href="<?php echo htmlspecialchars($navPrefix); ?>agenda.php" class="menu text-gray-700 hover:text-[#00811F] transition">Agenda</a>
            <a href="<?php echo htmlspecialchars($navPrefix); ?>over.php" class="menu text-gray-700 hover:text-[#00811F] transition">Voor wie?</a>

            <div class="relative" id="programma-dropdown">
                <button id="programma-toggle" aria-haspopup="true" aria-expanded="false" class="menu flex items-center gap-2 text-gray-700 hover:text-[#00811F] transition font-medium focus:outline-none">
                    <i class="fa-solid fa-caret-right text-xs" aria-hidden="true"></i>
                    <span>Wat doen we?</span>
                </button>

                <div id="programma-menu" class="hidden absolute top-0 mt-8 bg-white border border-gray-200 shadow-lg py-2 z-50 focus:outline-none" role="menu" aria-labelledby="programma-toggle">
                    <a href="<?php echo htmlspecialchars($navPrefix); ?>programma/kennis.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Kennis & vaardigheden</a>
                    <a href="<?php echo htmlspecialchars($navPrefix); ?>programma/actie.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Actie, onderzoek & ontwerp</a>
                    <a href="<?php echo htmlspecialchars($navPrefix); ?>programma/faciliteit.php" class="menu block px-4 py-2 text-gray-700 hover:bg-gray-100" role="menuitem">Faciliteit van het Lab</a>
                </div>
            </div>

            <a href="<?php echo htmlspecialchars($navPrefix); ?>verantwoord-ai.php" class="menu text-gray-700 hover:text-[#00811F] transition">Verantwoorde AI</a>
            <a href="<?php echo htmlspecialchars($navPrefix); ?>wie-zijn-we.php" class="menu text-gray-700 hover:text-[#00811F] transition">Wie zijn we?</a>
            <a href="<?php echo htmlspecialchars($navPrefix); ?>contact.php" class="menu text-gray-700 hover:text-[#00811F] transition">Contact</a>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                <a href="<?php echo htmlspecialchars($navPrefix); ?>admin.php" class="menu block m-4 text-gray-700 hover:text-[#00811F] transition">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
