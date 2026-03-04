<?php
// Detect if we're in a subdirectory by checking if the calling script is in a subfolder
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME']));
$rootDir = str_replace('\\', '/', dirname(__FILE__));
$imgPrefix = ($scriptDir === $rootDir) ? 'images/' : '../images/';
?>
<footer class="site-footer mt-16 shadow-inner">
    <div class="container">
        <div class="footer-heading text-center text-lg font-semibold text-gray-800 py-2">
            Een samenwerking met:
        </div>
        <div class="footer-logos flex flex-wrap justify-center items-center gap-4 py-6">

            <div class="footer-logo w-32 h-20 flex items-center justify-center">
                <img alt="logo techniek collage Rotterdam" src="<?php echo $imgPrefix; ?>Techniek_College_Rotterdam_logoO.png" class="max-w-full max-h-full object-contain">
            </div>

            <div class="footer-logo w-32 h-20 flex items-center justify-center">
                <img alt="logo hogeschool Rotterdam" src="<?php echo $imgPrefix; ?>Hogeschool_Rotterdam.png" class="max-w-full max-h-full object-contain">
            </div>

            <div class="footer-logo w-32 h-20 flex items-center justify-center">
                <img alt="logo gemeente Rotterdam" src="<?php echo $imgPrefix; ?>Gemeente_Rotterdam.png" class="max-w-full max-h-full object-contain">
            </div>

            <div class="footer-logo w-32 h-20 flex items-center justify-center">
                <img alt="erasmus universiteit" src="<?php echo $imgPrefix; ?>Erasmus_uni.png" class="max-w-full max-h-full object-contain">
            </div>

            <div class="footer-logo w-32 h-20 flex items-center justify-center">
                <img alt="Erasmus Centre for Data Analytics" src="<?php echo $imgPrefix; ?>Erasmus_DataOP.png" class="max-w-full max-h-full object-contain">
            </div>

        </div>

        <div class="text-center text-sm text-white-600 py-2">
            &copy; <?php echo date('Y'); ?> Sociaal AILab — Samen werken aan inclusieve AI. Alle rechten voorbehouden.
        </div>
    </div>
</footer>
