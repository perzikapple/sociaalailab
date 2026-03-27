<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<button id="sidebar-toggle" class="sidebar-toggle" aria-label="Toggle sidebar">
    <i class="fa-solid fa-bars"></i>
</button>

<aside id="sidebar" class="sidebar">
    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i>
                <span>Voorpagina</span>
            </a>
        </li>
        <li>
            <a href="agenda.php" class="<?php echo $current_page === 'agenda.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar"></i>
                <span>Agenda</span>
            </a>
        </li>
        <li>
            <a href="event.php" class="<?php echo $current_page === 'event.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Evenementen</span>
            </a>
        </li>
        <li>
            <a href="terugblikken.php" class="<?php echo $current_page === 'terugblikken.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-arrow-rotate-left"></i>
                <span>Terugblikken</span>
            </a>
        </li>
        <li>
            <a href="over.php" class="<?php echo $current_page === 'over.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-info"></i>
                <span>Voor wie?</span>
            </a>
        </li>
        <li>
            <button class="sidebar-menu-toggle" data-submenu="programma">
                <i class="fa-solid fa-cogs"></i>
                <span>Wat doen we?</span>
                <i class="fa-solid fa-chevron-down ml-auto"></i>
            </button>
            <ul class="sidebar-submenu" id="programma-submenu">
                <li>
                    <a href="programma/kennis.php" class="<?php echo $current_page === 'kennis.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-brain"></i>
                        <span>Kennis & vaardigheden</span>
                    </a>
                </li>
                <li>
                    <a href="programma/actie.php" class="<?php echo $current_page === 'actie.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-rocket"></i>
                        <span>Actie, onderzoek & ontwerp</span>
                    </a>
                </li>
                <li>
                    <a href="programma/faciliteit.php" class="<?php echo $current_page === 'faciliteit.php' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-building"></i>
                        <span>Faciliteit van het Lab</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="verantwoord-ai.php" class="<?php echo $current_page === 'verantwoord-ai.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-shield"></i>
                <span>Verantwoorde AI</span>
            </a>
        </li>
        <li>
            <a href="wie-zijn-we.php" class="<?php echo $current_page === 'wie-zijn-we.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Wie zijn we?</span>
            </a>
        </li>
        <li>
            <a href="contact.php" class="<?php echo $current_page === 'contact.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-envelope"></i>
                <span>Contact</span>
            </a>
        </li>
        <?php if (isset($_SESSION['user'])): ?>
            <li>
                <a href="logout.php" class="text-red-600 hover:text-red-700">
                    <i class="fa-solid fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        <?php endif; ?>
        <?php if (!empty($_SESSION['can_access_admin']) || (isset($_SESSION['admin']) && (int)$_SESSION['admin'] === 1)): ?>
            <li>
                <a href="admin.php" class="bg-yellow-100 hover:bg-yellow-200">
                    <i class="fa-solid fa-lock"></i>
                    <span>Admin</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebar-toggle');
    const submenuToggles = document.querySelectorAll('.sidebar-menu-toggle');

    toggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
    });

    submenuToggles.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const submenuId = this.dataset.submenu + '-submenu';
            const submenu = document.getElementById(submenuId);
            submenu.classList.toggle('open');
        });
    });

    const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            sidebar.classList.remove('open');
        });
    });
});
</script>
