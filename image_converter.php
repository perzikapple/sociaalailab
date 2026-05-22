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

<?php

function upload_error_message($code) {
    $errors = [
        UPLOAD_ERR_OK => 'Geen fout',
        UPLOAD_ERR_INI_SIZE => 'Bestand is groter dan upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'Bestand is groter dan MAX_FILE_SIZE in het formulier',
        UPLOAD_ERR_PARTIAL => 'Bestand is gedeeltelijk geüpload',
        UPLOAD_ERR_NO_FILE => 'Geen bestand geüpload',
        UPLOAD_ERR_NO_TMP_DIR => 'Geen tijdelijke map op server',
        UPLOAD_ERR_CANT_WRITE => 'Kan bestand niet opslaan op schijf',
        UPLOAD_ERR_EXTENSION => 'Upload gestopt door PHP extensie',
    ];
    return $errors[$code] ?? 'Onbekende fout (' . $code . ')';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>";
    print_r($_FILES);
    echo "</pre>";

    if (!isset($_FILES['image'])) {
        die("Geen bestand ontvangen");
    }

    if ($_FILES['image']['error'] !== 0) {
        $err = upload_error_message($_FILES['image']['error']);
        echo '<div style="color:#b71c1c;background:#fff3f3;border:1px solid #ffcdd2;padding:12px 18px;border-radius:7px;font-size:1.1em;font-weight:600;max-width:420px;margin:18px auto;text-align:center;box-shadow:0 1px 6px rgba(183,28,28,0.06);">';
        echo 'Upload fout: ' . $err . ' (code: ' . $_FILES['image']['error'] . ')<br>';
        echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . '<br>';
        echo 'post_max_size: ' . ini_get('post_max_size') . '<br>';
        echo 'memory_limit: ' . ini_get('memory_limit') . '<br>';
        echo 'max_execution_time: ' . ini_get('max_execution_time') . '<br>';
        echo '</div>';
        exit;
    }

    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $target = $uploadDir . basename($_FILES['image']['name']);

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo "Upload gelukt!";
    } else {
        echo '<div style="color:#b71c1c;background:#fff3f3;border:1px solid #ffcdd2;padding:12px 18px;border-radius:7px;font-size:1.1em;font-weight:600;max-width:420px;margin:18px auto;text-align:center;box-shadow:0 1px 6px rgba(183,28,28,0.06);">';
        echo "Fout bij uploaden naar uploads/.<br>";
        echo 'upload_max_filesize: ' . ini_get('upload_max_filesize') . '<br>';
        echo 'post_max_size: ' . ini_get('post_max_size') . '<br>';
        echo 'memory_limit: ' . ini_get('memory_limit') . '<br>';
        echo 'max_execution_time: ' . ini_get('max_execution_time') . '<br>';
        echo '</div>';
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <input type="file" name="image">
    <button type="submit">Upload</button>
    <input type="file" name="image">
    <button type="submit">Upload</button>
</form>
