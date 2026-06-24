<?php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$rootDir = str_replace('\\', '/', dirname(__FILE__));
$imgPrefix = ($scriptDir === $rootDir) ? 'images/' : '../images/';
?>
<footer class="site-footer onest-font">
    <div class="footer-container">
        <h3>In samenwerking met:</h3>
        <div class="partners">
            <img alt="Gemeente Rotterdam" src="<?php echo $imgPrefix; ?>GemeenteRotterdam.png">
            <img alt="Erasmus Centre for Data Analytics" src="<?php echo $imgPrefix; ?>ECDA.png">
            <img alt="Hogeschool Rotterdam" src="<?php echo $imgPrefix; ?>HogeschoolRotterdam.png">
            <img alt="Erasmus Universiteit" src="<?php echo $imgPrefix; ?>EUR.png">
            <img alt="Techniek College Rotterdam" src="<?php echo $imgPrefix; ?>TechniekCollegeRotterdam.png">
        </div>
        <p class="copyright">
            &copy; <?php echo date('Y'); ?> Sociaal AILab — Samen werken aan inclusieve AI. Alle rechten voorbehouden.
        </p>
    </div>
</footer>