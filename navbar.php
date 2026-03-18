<?php
if (!isset($navPrefix)) {
    $navPrefix = '';
}
?>
<nav class="navbar">
    <div class="nav-container">
        <button id="mobile-menu-toggle" class="hamburger" aria-label="Toggle navigation">
            &#9776;
        </button>

        <ul id="nav-menu" class="nav-menu">
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>index.php">Voorpagina</a></li>
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>agenda.php">Agenda</a></li>
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>over.php">Voor wie?</a></li>

            <li class="dropdown">
                <button class="dropbtn">Wat doen we?</button>
                <ul class="dropdown-content">
                    <li><a href="<?php echo htmlspecialchars($navPrefix); ?>programma/kennis.php">Kennis & vaardigheden</a></li>
                    <li><a href="<?php echo htmlspecialchars($navPrefix); ?>programma/actie.php">Actie, onderzoek & ontwerp</a></li>
                    <li><a href="<?php echo htmlspecialchars($navPrefix); ?>programma/faciliteit.php">Faciliteit van het Lab</a></li>
                </ul>
            </li>

            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>verantwoord-ai.php">Verantwoorde AI</a></li>
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>wie-zijn-we.php">Wie zijn we?</a></li>
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>contact.php">Contact</a></li>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
                <li><a href="<?php echo htmlspecialchars($navPrefix); ?>admin.php">Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<script>
    const toggle = document.getElementById('mobile-menu-toggle');
    const menu = document.getElementById('nav-menu');

    toggle.addEventListener('click', () => {
        menu.classList.toggle('show');
    });
</script>