<?php
if (!isset($navPrefix)) {
    $navPrefix = '';
}
?>
<nav class="navbar onest-font">
    <div class="nav-container">
        <button id="mobile-menu-toggle" class="hamburger" aria-label="Toggle navigation">
            &#9776;
        </button>

        <ul id="nav-menu" class="nav-menu ">
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>index.php">Voorpagina</a></li>
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>agenda.php#agenda-terugblik-switch">Agenda</a></li>
            <li><a href="<?php echo htmlspecialchars($navPrefix); ?>over.php">Voor wie?</a></li>

            <li class="dropdown">
                <button class="dropbtn">Wat doen we?<span class="dropdown-caret" aria-hidden="true"></span></button>
                <ul class="dropdown-content">
                    <li><a href="<?php echo htmlspecialchars($navPrefix); ?>programma/kennis.php#watdoenwe-switch">Kennis & vaardigheden</a></li>
                    <li><a href="<?php echo htmlspecialchars($navPrefix); ?>programma/actie.php#watdoenwe-switch">Actie, onderzoek & ontwerp</a></li>
                    <li><a href="<?php echo htmlspecialchars($navPrefix); ?>programma/faciliteit.php#watdoenwe-switch">Faciliteit van het Lab</a></li>
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
        document.body.classList.toggle('no-scroll');
    });

    document.addEventListener('click', (e) => {
        if (!menu.contains(e.target) && !toggle.contains(e.target)) {
            menu.classList.remove('show');
            document.body.classList.remove('no-scroll');
        }
    });

    // Mobile dropdown toggle
    document.querySelectorAll('.dropdown .dropbtn').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = btn.closest('.dropdown');
            const content = dropdown.querySelector('.dropdown-content');
            const isOpen = content.style.display === 'block';
            // Close all dropdowns first
            document.querySelectorAll('.dropdown-content').forEach(function(d) {
                d.style.display = 'none';
            });
            document.querySelectorAll('.dropbtn').forEach(function(b) {
                b.classList.remove('open');
            });
            // Toggle current
            if (!isOpen) {
                content.style.display = 'block';
                btn.classList.add('open');
            }
        });
    });
</script>