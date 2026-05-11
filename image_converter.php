<?php
// image_converter.php
// Secure image converter backend for PNG/JPG (max 10MB)

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Alleen POST toegestaan.';
    exit;
}

if (!isset($_FILES['image']) || !isset($_POST['format'])) {
    http_response_code(400);
    echo 'Geen bestand of formaat ontvangen.';
    exit;
}

$file = $_FILES['image'];
$format = $_POST['format'] === 'png' ? 'png' : 'jpg';
$maxSize = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo '<div style="background:#fffbe6;color:#856404;border:1px solid #ffe58f;padding:8px 14px;border-radius:7px;font-size:1em;font-weight:500;max-width:340px;margin:18px auto 0;text-align:center;box-shadow:0 1px 6px rgba(255,193,7,0.06);">'
        . '⚠️ Bestand is groter dan 10MB.<br>'
        . '<span style="font-size:0.97em;font-weight:400;">Kies een kleinere afbeelding.</span>'
        . '</div>';
    exit;
}

$tmpPath = $file['tmp_name'];

// Prefer Imagick if available and defined, using closure to avoid static analysis errors
if (extension_loaded('imagick') && class_exists('Imagick')) {
    $imagickConvert = function ($tmpPath, $format) {
        try {
            $img = new Imagick($tmpPath);
            $img->setImageFormat($format);
            if ($format === 'jpg') {
                $img->setImageCompression(Imagick::COMPRESSION_JPEG);
                $img->setImageCompressionQuality(90);
            } else {
                $img->setImageCompressionQuality(9);
            }
            $img->stripImage();
            header('Content-Type: image/' . $format);
            header('Content-Disposition: attachment; filename="converted.' . $format . '"');
            echo $img;
            $img->destroy();
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo 'Fout bij conversie (Imagick): ' . $e->getMessage();
            exit;
        }
    };
    $imagickConvert($tmpPath, $format);
}

// Fallback to GD
if (!extension_loaded('gd')) {
    http_response_code(500);
    echo 'Geen beeldverwerking beschikbaar op de server.';
    exit;
}

$src = null;
if ($file['type'] === 'image/png') {
    $src = imagecreatefrompng($tmpPath);
} elseif ($file['type'] === 'image/jpeg') {
    $src = imagecreatefromjpeg($tmpPath);
}
if (!$src) {
    http_response_code(500);
    echo 'Kon afbeelding niet laden.';
    exit;
}

ob_start();
if ($format === 'jpg') {
    imagejpeg($src, null, 90);
    header('Content-Type: image/jpeg');
    header('Content-Disposition: attachment; filename="converted.jpg"');
} else {
    imagepng($src, null, 9);
    header('Content-Type: image/png');
    header('Content-Disposition: attachment; filename="converted.png"');
}
$imgData = ob_get_clean();
imagedestroy($src);
echo $imgData;
exit;
