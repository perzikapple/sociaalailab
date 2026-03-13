<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header('Location: login.php');
    exit;
}

$message = '';
<script>
function openImageModal(event, imageSrc) {

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $timeRaw = $_POST['time'] ?? ($_POST['start_time'] ?? null);
        $time = is_string($timeRaw) ? trim($timeRaw) : null;
        $time_end = $_POST['time_end'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? ($_POST['place'] ?? ($_POST['plek'] ?? '')));
        $showSignupButton = isset($_POST['show_signup_button']) ? 1 : 0;

        if ($date === '') {
            $message = 'Datum is verplicht.';
        } else {
            $upload = handleUpload('image');
            if (isset($upload['error'])) {
                $message = $upload['error'];
            } else {
                $imageName = $upload['name'] ?? null;
                // voeg updated_at en updated_by toe bij insert
                $stmt = $pdo->prepare('INSERT INTO events (title, date, time, time_end, description, image, location, show_signup_button, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
                $stmt->execute([$title, $date, $time ?: null, $time_end ?: null, $description, $imageName, $location ?: null, $showSignupButton, $currentUser]);
                $eventId = $pdo->lastInsertId();
                // Audit log: event created
                audit_log($pdo, 'create', 'events', $eventId, 'title: ' . $title, $currentUser);
                header('Location: admin.php?page=agenda&ok=create');
                exit;
            }
        }

    } elseif ($action === 'update' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $timeRaw = $_POST['time'] ?? ($_POST['start_time'] ?? null);
        $time = is_string($timeRaw) ? trim($timeRaw) : null;
        $time_end = $_POST['time_end'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? ($_POST['place'] ?? ($_POST['plek'] ?? '')));
        $showSignupButton = isset($_POST['show_signup_button']) ? 1 : 0;
        $removeImage = isset($_POST['remove_image']) ? 1 : 0;

        if ($title === '' || $date === '') {
            $message = 'Titel en datum zijn verplicht.';
        } else {
            // check existing image
            $stmt = $pdo->prepare('SELECT image FROM events WHERE id = ?');
            <script>
            function openImageModal(event, imageSrc) {
        $deletedCount = 0;
        foreach ($ids as $id) {
            $stmt = $pdo->prepare('SELECT image FROM events WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['image'] && file_exists(__DIR__ . '/uploads/' . $row['image'])) {
                @unlink(__DIR__ . '/uploads/' . $row['image']);
            }
            $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
            $result = $stmt->execute([$id]);
            if ($result) {
                $deletedCount++;
                audit_log($pdo, 'delete', 'events', $id, null, $currentUser);
            }
        }
        if ($deletedCount > 0) {
            header('Location: admin.php?page=agenda&ok=delete');
        } else {
            $message = 'Geen items verwijderd.';
        }
        exit;

    } elseif ($action === 'reorder_event') {
        $id = (int)($_POST['id'] ?? 0);
        $direction = $_POST['direction'] ?? '';

        if ($id && ($direction === 'up' || $direction === 'down')) {
            $currentUser = $_SESSION['user'] ?? null;
            $pdo->beginTransaction();
            try {
                // Normaliseer sort_order als nodig
                $stmt = $pdo->prepare('SELECT id, sort_order, created_at FROM events ORDER BY created_at ASC, id ASC');
                $stmt->execute();
                $items = $stmt->fetchAll();

                $needsNormalize = false;
                foreach ($items as $item) {
                    if (empty($item['sort_order'])) {
                        $needsNormalize = true;
                        break;
                    }
                }

                if ($needsNormalize) {
                    $update = $pdo->prepare('UPDATE events SET sort_order = ? WHERE id = ?');
                    $pos = 1;
                    foreach ($items as $item) {
                        $update->execute([$pos, $item['id']]);
                        $pos++;
                    }
                }

                // Haal gesorteerde events op
                $stmt = $pdo->prepare('SELECT id, sort_order FROM events ORDER BY sort_order ASC, id ASC');
                $stmt->execute();
                $ordered = $stmt->fetchAll();

                // Zoek index van huidige event
                $index = null;
                foreach ($ordered as $i => $item) {
                    if ((int)$item['id'] === $id) {
                        $index = $i;
                        break;
                    }
                }

                // Wissel met vorige/volgende event
                if ($index !== null) {
                    $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
                    if (isset($ordered[$swapIndex])) {
                        $current = $ordered[$index];
                        $swap = $ordered[$swapIndex];
                        $update = $pdo->prepare('UPDATE events SET sort_order = ? WHERE id = ?');
                        $update->execute([$swap['sort_order'], $current['id']]);
                        $update->execute([$current['sort_order'], $swap['id']]);
                    }
                }

                audit_log($pdo, 'update', 'events', $id, 'reorder ' . $direction, $currentUser);
                $pdo->commit();
                header('Location: admin.php?page=agenda');
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $message = 'Volgorde bijwerken mislukt.';
            }
        }

    } elseif ($action === 'delete_bulk_pages' && !empty($_POST['page_ids'])) {
        $ids = $_POST['page_ids'];
        if (!is_array($ids)) $ids = [$ids];
        $ids = array_map('intval', $ids);
        
        $deletedCount = 0;
        foreach ($ids as $id) {
            $stmt = $pdo->prepare('SELECT image FROM pages WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['image'] && file_exists(__DIR__ . '/uploads/' . $row['image'])) {
                @unlink(__DIR__ . '/uploads/' . $row['image']);
            }
            $stmt = $pdo->prepare('DELETE FROM pages WHERE id = ?');
            $result = $stmt->execute([$id]);
            if ($result) {
                $deletedCount++;
                audit_log($pdo, 'delete', 'pages', $id, null, $currentUser);
            }
        }
        if ($deletedCount > 0) {
            header('Location: admin.php?page=' . urlencode($_POST['page_key'] ?? 'agenda') . '&ok=delete');
        } else {
            $message = 'Geen items verwijderd.';
        }
        exit;

    } elseif ($action === 'update_banner') {
        $defaultBanner1 = 'images/banner_website_01.jpg';
        $defaultBanner2 = 'images/banner_website_02.jpg';
        $removeBanner1 = isset($_POST['remove_banner1']) ? 1 : 0;
        $removeBanner2 = isset($_POST['remove_banner2']) ? 1 : 0;

        if ($removeBanner1) {
            if (strpos((string)$banner1, 'uploads/') === 0) {
                $oldPath = __DIR__ . '/' . $banner1;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner1'")->execute([$defaultBanner1]);
            $banner1 = $defaultBanner1;
            audit_log($pdo, 'update', 'settings', null, 'banner1 reset to default', $currentUser);
        }

        $banner1_file = handleUpload('banner1');
        if (!isset($banner1_file['error']) && !empty($banner1_file['name'])) {
            $path = 'uploads/' . $banner1_file['name'];
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner1'")->execute([$path]);
            $banner1 = $path;
            audit_log($pdo, 'update', 'settings', null, 'banner1 set to ' . $path, $currentUser);
        }

        if ($removeBanner2) {
            if (strpos((string)$banner2, 'uploads/') === 0) {
                $oldPath = __DIR__ . '/' . $banner2;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner2'")->execute([$defaultBanner2]);
            $banner2 = $defaultBanner2;
            audit_log($pdo, 'update', 'settings', null, 'banner2 reset to default', $currentUser);
        }

        $banner2_file = handleUpload('banner2');
        if (!isset($banner2_file['error']) && !empty($banner2_file['name'])) {
            $path = 'uploads/' . $banner2_file['name'];
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner2'")->execute([$path]);
            $banner2 = $path;
            audit_log($pdo, 'update', 'settings', null, 'banner2 set to ' . $path, $currentUser);
        }
        $message = 'Banners bijgewerkt.';

    } elseif ($action === 'reset_banners') {
        $defaultBanner1 = 'images/banner_website_01.jpg';
        $defaultBanner2 = 'images/banner_website_02.jpg';
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner1'")->execute([$defaultBanner1]);
        $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner2'")->execute([$defaultBanner2]);
        audit_log($pdo, 'update', 'settings', null, 'banners reset to default', $currentUser);
        $banner1 = $defaultBanner1;
        $banner2 = $defaultBanner2;
        $message = 'Banners zijn teruggezet naar de standaard waarden.';
    }
}

// Handle page management (create_page, update_page, delete_page)
$pageAction = $_POST['page_action'] ?? '';
if ($pageAction === 'create_page') {
    $pageKey = $_POST['page_key'] ?? '';
    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        $title = ' ';
    }
    $body = trim($_POST['body'] ?? '');
    $insertPosition = $_POST['insert_position'] ?? 'bottom';
    $imagePosition = $_POST['image_position'] ?? 'normal';
    $greenText = trim($_POST['green_text'] ?? '');
    if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) {
        $imagePosition = 'normal';
    }

    if (empty($pageKey)) {
        $message = 'Page key is verplicht.';
    } else {
        $meta = ['image_position' => $imagePosition];
        if ($greenText !== '') {
            $meta['green_text'] = $greenText;
        }

        // Extra velden voor contact pagina
        if ($pageKey === 'contact') {
            if (!empty($_POST['address'])) $meta['address'] = $_POST['address'];
            if (!empty($_POST['email'])) $meta['email'] = $_POST['email'];
            if (!empty($_POST['map_embed'])) $meta['map_embed'] = $_POST['map_embed'];
        }

        // Change 'page_image' to 'image' to match the form field name
        $upload = handleUpload('image');
        $imageName = $upload['name'] ?? null;

        if ($insertPosition === 'top') {
            $shiftStmt = $pdo->prepare('UPDATE pages SET sort_order = COALESCE(sort_order, 0) + 1 WHERE page_key = ?');
            $shiftStmt->execute([$pageKey]);
            $nextOrder = 1;
        } else {
            $maxStmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM pages WHERE page_key = ?');
            $maxStmt->execute([$pageKey]);
            $nextOrder = (int)$maxStmt->fetchColumn() + 1;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO pages (page_key, title, body, image, meta, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->execute([
            $pageKey,
            $title,
            $body,
            $imageName,
            json_encode($meta),
            $nextOrder
        ]);

        audit_log($pdo, 'create', 'pages', $pdo->lastInsertId(), 'page_key: ' . $pageKey, $currentUser);

        header('Location: admin.php?page=' . urlencode($pageKey) . '&ok=create');
        exit;
    }
} elseif ($pageAction === 'update_page') {

    $id = (int)($_POST['id'] ?? 0);
    $pageKey = $_POST['page_key'] ?? '';
    $title = trim($_POST['title'] ?? '');
    if ($title === '') {
        $title = ' ';
    }
    $body = trim($_POST['body'] ?? '');
    $removeImage = isset($_POST['remove_image']) ? 1 : 0;
    $greenText = trim($_POST['green_text'] ?? '');

    if ($id) {

        $stmt = $pdo->prepare('SELECT image, meta FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $oldImage = $row ? $row['image'] : null;
        $imagePosition = $_POST['image_position'] ?? 'normal';
        if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) {
            $imagePosition = 'normal';
        }

        $meta = [];
        if (!empty($row['meta'])) {
            $decodedMeta = json_decode($row['meta'], true);
            if (is_array($decodedMeta)) {
                $meta = $decodedMeta;
            }
        }
        $meta['image_position'] = $imagePosition;
        if ($greenText !== '') {
            $meta['green_text'] = $greenText;
        } else {
            unset($meta['green_text']);
        }

        if ($pageKey === 'contact') {
            if (!empty($_POST['address'])) $meta['address'] = $_POST['address'];
            if (!empty($_POST['email'])) $meta['email'] = $_POST['email'];
            if (!empty($_POST['map_embed'])) $meta['map_embed'] = $_POST['map_embed'];
        }

        // Change 'page_image' to 'image' to match the form field name
        $upload = handleUpload('image');
        if ($removeImage) {
            $imageName = null;
        } else {
            $imageName = $upload['name'] ?? $oldImage;
        }

        $stmt = $pdo->prepare(
            'UPDATE pages
             SET title=?, body=?, image=?, meta=?, updated_at=NOW()
             WHERE id=?'
        );

        $stmt->execute([
            $title,
            $body,
            $imageName,
            json_encode($meta),
            $id
        ]);

        if (!empty($upload['name']) && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
            @unlink(__DIR__ . '/uploads/' . $oldImage);
        }
        if ($removeImage && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
            @unlink(__DIR__ . '/uploads/' . $oldImage);
        }

        audit_log($pdo, 'update', 'pages', $id, 'page_key: ' . $pageKey, $currentUser);

        header('Location: admin.php?page=' . urlencode($pageKey) . '&ok=update');
        exit;
    }
} elseif ($pageAction === 'update_page_green_text') {

    $id = (int)($_POST['id'] ?? 0);
    $pageKey = $_POST['page_key'] ?? '';
    $greenText = trim($_POST['green_text'] ?? '');

    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT meta FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $meta = [];
        if (!empty($row['meta'])) {
            $decodedMeta = json_decode($row['meta'], true);
            if (is_array($decodedMeta)) {
                $meta = $decodedMeta;
            }
        }

        if ($greenText !== '') {
            $meta['green_text'] = $greenText;
        } else {
            unset($meta['green_text']);
        }

        $updateMetaStmt = $pdo->prepare('UPDATE pages SET meta = ?, updated_at = NOW() WHERE id = ?');
        $updateMetaStmt->execute([json_encode($meta), $id]);

        audit_log($pdo, 'update', 'pages', $id, 'green_text aangepast', $currentUser);

        header('Location: admin.php?page=' . urlencode($pageKey) . '&ok=update');
        exit;
    }
} elseif ($pageAction === 'reorder_page') {

    $id = (int)($_POST['id'] ?? 0);
    $pageKey = $_POST['page_key'] ?? '';
    $direction = $_POST['direction'] ?? '';

    if ($id && $pageKey && ($direction === 'up' || $direction === 'down')) {
        $currentUser = $_SESSION['user'] ?? null;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT id, sort_order, created_at FROM pages WHERE page_key = ? ORDER BY created_at ASC, id ASC');
            $stmt->execute([$pageKey]);
            $items = $stmt->fetchAll();

            $needsNormalize = false;
            foreach ($items as $item) {
                if (empty($item['sort_order'])) {
                    $needsNormalize = true;
                    break;
                }
            }

            if ($needsNormalize) {
                $update = $pdo->prepare('UPDATE pages SET sort_order = ? WHERE id = ?');
                $pos = 1;
                foreach ($items as $item) {
                    $update->execute([$pos, $item['id']]);
                    $pos++;
                }
            }

            $stmt = $pdo->prepare('SELECT id, sort_order FROM pages WHERE page_key = ? ORDER BY sort_order ASC, id ASC');
            $stmt->execute([$pageKey]);
            $ordered = $stmt->fetchAll();

            $index = null;
            foreach ($ordered as $i => $item) {
                if ((int)$item['id'] === $id) {
                    $index = $i;
                    break;
                }
            }

            if ($index !== null) {
                $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
                if (isset($ordered[$swapIndex])) {
                    $current = $ordered[$index];
                    $swap = $ordered[$swapIndex];
                    $update = $pdo->prepare('UPDATE pages SET sort_order = ? WHERE id = ?');
                    $update->execute([$swap['sort_order'], $current['id']]);
                    $update->execute([$current['sort_order'], $swap['id']]);
                }
            }

            audit_log($pdo, 'update', 'pages', $id, 'reorder ' . $pageKey . ' ' . $direction, $currentUser);
            $pdo->commit();
            header('Location: admin.php?page=' . urlencode($pageKey));
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Volgorde bijwerken mislukt.';
        }
    }

} elseif ($pageAction === 'delete_page') {

    $id = (int)($_POST['id'] ?? 0);

    if ($id) {

        $stmt = $pdo->prepare('SELECT image FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row && $row['image'] && file_exists(__DIR__ . '/uploads/' . $row['image'])) {
            @unlink(__DIR__ . '/uploads/' . $row['image']);
        }

        $stmt = $pdo->prepare('DELETE FROM pages WHERE id = ?');
        $stmt->execute([$id]);

        audit_log($pdo, 'delete', 'pages', $id, null, $currentUser);

        $pageKey = $_POST['page_key'] ?? 'index';
        header('Location: admin.php?page=' . urlencode($pageKey) . '&ok=delete');
        exit;
    }
}

// Edit modus
$editEvent = null;
if (!empty($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare('SELECT * FROM events WHERE id = ?');
    $stmt->execute([$id]);
    $editEvent = $stmt->fetch();
}

// Edit page mode
$editPage = null;
if (!empty($_GET['edit_page'])) {
    $id = (int)$_GET['edit_page'];
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    $editPage = $stmt->fetch();
}

// Haal events voor overzicht (datum-gestuurd)
$stmt = $pdo->prepare('SELECT * FROM events ORDER BY date ASC, time ASC, created_at ASC, id ASC');
$stmt->execute();
$events = $stmt->fetchAll();
?>
<?php
// Welke admin pagina tonen (standaard index)
$page = $_GET['page'] ?? 'index';

$pageItems = [];
if ($page !== 'banner' && $page !== 'agenda') {
    $stmt = $pdo->prepare(
        'SELECT * FROM pages WHERE page_key = ?
         ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC'
    );
    $stmt->execute([$page]);
    $pageItems = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin - SociaalAI Lab</title>
<link rel="stylesheet" href="build/assets/app-DozK-03z.css">
<link rel="stylesheet" href="custom.css?v=<?php echo filemtime(__DIR__.'/custom.css'); ?>">
<link rel="stylesheet" href="admin-styles.css?v=<?php echo filemtime(__DIR__.'/admin-styles.css'); ?>">
<link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" href="images/Pixels_icon.png">
<script src="custom.js"></script>
</head>
<body class="admin-page">
<nav class="admin-header text-white p-4 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-gears text-2xl"></i>
            <h1 class="text-2xl font-bold">Admin Panel</h1>
            <?php if (!empty($_SESSION['voornaam']) || !empty($_SESSION['user'])): ?>
                <span class="text-sm md:text-base font-medium bg-white/20 px-3 py-1 rounded-full">
                    Welkom <?php echo htmlspecialchars($_SESSION['voornaam'] ?? $_SESSION['user']); ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="flex gap-3">
            <a href="index.php" class="btn btn-secondary text-sm">
                <i class="fa-solid fa-arrow-left"></i> Terug naar site
            </a>
            <a href="logout.php" class="btn btn-secondary text-sm">
                <i class="fa-solid fa-sign-out-alt"></i> Uitloggen
            </a>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto p-6">
    <?php if (!empty($message)): ?>
    <div class="alert alert-error">
        <i class="fa-solid fa-exclamation-circle"></i> <?php echo htmlspecialchars($message); ?>
    </div>
    <?php endif; ?>

    <div class="grid admin-layout-grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <aside class="sidebar" style="align-self:start;">
            <div class="sidebar-header" style="top:0;">
                <i class="fa-solid fa-list"></i> Navigatie
            </div>
            <div class="sidebar-nav-scroll" style="min-height:0;">
            <nav class="divide-y">
                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Beheer</div>
                <a href="admin.php?page=banner" class="sidebar-link <?php echo $page==='banner' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-image"></i> Banners
                </a>
                <a href="admin.php?page=agenda" class="sidebar-link <?php echo $page==='agenda' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar"></i> Agenda
                </a>

                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Pagina's</div>
                <a href="admin.php?page=index" class="sidebar-link <?php echo $page==='index' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house"></i> Homepage
                </a>
                <a href="admin.php?page=evenementen" class="sidebar-link <?php echo $page==='evenementen' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-check"></i> Evenementen
                </a>
                <a href="admin.php?page=terugblikken" class="sidebar-link <?php echo $page==='terugblikken' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-history"></i> Terugblikken
                </a>
                <a href="admin.php?page=over" class="sidebar-link <?php echo $page==='over' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-info-circle"></i> Voor wie?
                </a>
                <a href="admin.php?page=wie-zijn-we" class="sidebar-link <?php echo $page==='wie-zijn-we' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-people-group"></i> Wie zijn we?
                </a>
                <a href="admin.php?page=verantwoord-ai" class="sidebar-link <?php echo $page==='verantwoord-ai' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-shield"></i> Verantwoord AI
                </a>
                <a href="admin.php?page=contact" class="sidebar-link <?php echo $page==='contact' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-envelope"></i> Contact
                </a>

                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Wat doen we</div>
                <a href="admin.php?page=programma-kennis" class="sidebar-link <?php echo $page==='programma-kennis' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-brain"></i> Kennis & Vaardigheden
                </a>
                <a href="admin.php?page=programma-actie" class="sidebar-link <?php echo $page==='programma-actie' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-rocket"></i> Actie, onderzoek & ontwerp
                </a>
                <a href="admin.php?page=programma-faciliteit" class="sidebar-link <?php echo $page==='programma-faciliteit' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-building"></i> Faciliteit van het Lab
                </a>
            </nav>
            </div>
        </aside>

        <div class="lg:col-span-3">

            <?php if ($page === 'agenda'): ?>
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-calendar text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Agenda Beheer</h2>
                    </div>

                    <?php if ($editEvent): ?>
                        <a href="admin.php?page=agenda" class="btn btn-secondary mb-6">
                            <i class="fa-solid fa-arrow-left"></i> Terug
                        </a>
                    <?php endif; ?>

                    <?php if ($editEvent): ?>
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6">
                            <h3 class="font-semibold text-lg">Bewerk Evenement</h3>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo (int)$editEvent['id']; ?>">

                            <div>
                                <label class="form-label">Titel</label>
                                <input name="title" required class="form-input" value="<?php echo htmlspecialchars($editEvent['title']); ?>" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="form-label">Wanneer (datum)</label>
                                    <input type="date" name="date" required class="form-input" value="<?php echo htmlspecialchars($editEvent['date']); ?>" />
                                </div>
                                <div>
                                    <label class="form-label">Starttijd</label>
                                    <input type="time" name="time" class="form-input" value="<?php echo htmlspecialchars($editEvent['time'] ?? ''); ?>" placeholder="bijv. 14:30" />
                                </div>
                                <div>
                                    <label class="form-label">Eindtijd <span class="text-xs text-gray-500">(optioneel)</span></label>
                                    <input type="time" name="time_end" class="form-input" value="<?php echo htmlspecialchars($editEvent['time_end'] ?? ''); ?>" placeholder="bijv. 16:00" />
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Plaats</label>
                                <input name="location" class="form-input" value="<?php echo htmlspecialchars($editEvent['location'] ?? ''); ?>" placeholder="bijv. Rotterdam - Hillevliet 90" />
                            </div>

                            <div>
                                <label class="form-label">Omschrijving</label>
                                <textarea name="description" rows="5" class="form-textarea"><?php echo htmlspecialchars($editEvent['description'] ?? ''); ?></textarea>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                                <?php if (!empty($editEvent['image'])): ?>
                                    <p class="text-sm mt-2 text-gray-600">Huidige: <?php echo htmlspecialchars($editEvent['image']); ?></p>
                                    <label class="form-checkbox mt-2 inline-flex items-center gap-2">
                                        <input type="checkbox" name="remove_image" value="1">
                                        Verwijder huidige afbeelding
                                    </label>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="show_signup_button" <?php echo $editEvent['show_signup_button'] ? 'checked' : ''; ?> />
                                    Toon inschrijf knop
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Opslaan
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6">
                            <h3 class="font-semibold text-lg">Nieuw Evenement</h3>
                            <input type="hidden" name="action" value="create">

                            <div>
                                <label class="form-label">Titel</label>
                                <input name="title" class="form-input" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="form-label">Wanneer (datum)</label>
                                    <input type="date" name="date" required class="form-input" />
                                </div>
                                <div>
                                    <label class="form-label">Starttijd</label>
                                    <input type="time" name="time" class="form-input" placeholder="bijv. 14:30" />
                                </div>
                                <div>
                                    <label class="form-label">Eindtijd <span class="text-xs text-gray-500">(optioneel)</span></label>
                                    <input type="time" name="time_end" class="form-input" placeholder="bijv. 16:00" />
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Plaats</label>
                                <input name="location" class="form-input" placeholder="bijv. Rotterdam - Hillevliet 90" />
                            </div>

                            <div>
                                <label class="form-label">Omschrijving</label>
                                <textarea name="description" rows="5" class="form-textarea"></textarea>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                            </div>

                            <div>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="show_signup_button" />
                                    Toon inschrijf knop
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Toevoegen
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="card p-6">
                        <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                            <i class="fa-solid fa-list text-2xl text-[#00811F]"></i>
                            <h3 class="text-xl font-bold">Alle Evenementen</h3>
                        </div>
                        <?php if (empty($events)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                                <p>Geen evenementen aangemaakt</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-4 flex gap-2 items-center">
                                <input type="checkbox" id="selectAllEvents" class="w-4 h-4 cursor-pointer" title="Selecteer alles">
                                <label for="selectAllEvents" class="cursor-pointer text-sm font-medium">Selecteer alles</label>
                                <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteEventsBtn" style="display:none;">
                                    <i class="fa-solid fa-trash"></i> Verwijder geselecteerde (<span id="eventCount">0</span>)
                                </button>
                            </div>
                            <form method="POST" id="bulkDeleteEventsForm">
                                <input type="hidden" name="action" value="delete_bulk_events">
                            <div class="space-y-4">
                            <?php foreach ($events as $event): ?>
                                <div class="border border-gray-200 rounded p-4 hover:shadow-md transition">
                                    <div class="flex justify-between items-start gap-4">
                                        <input type="checkbox" class="event-checkbox w-4 h-4 mt-1 flex-shrink-0 cursor-pointer" name="event_ids[]" value="<?php echo (int)$event['id']; ?>">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h4>
                                            <?php 
                                                $dateDisplay = formatEventDateDisplay($event['date']); 
                                                $timeDisplay = $event['time'] ? formatEventTimeDisplay($event['time']) : ''; 
                                                $timeEndDisplay = $event['time_end'] ? formatEventTimeDisplay($event['time_end']) : ''; 
                                            ?>
                                            <p class="text-sm text-gray-600 mt-1"><strong>Wanneer:</strong> <?php echo htmlspecialchars($dateDisplay); ?><?php if ($timeDisplay) { echo ' ' . htmlspecialchars($timeDisplay); } ?><?php if ($timeEndDisplay) { echo ' - ' . htmlspecialchars($timeEndDisplay); } ?></p>
                                            <?php if (!empty($event['location'])): ?>
                                                <p class="text-sm text-gray-600"><strong>Plaats:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-600 mt-2 line-clamp-2"><?php echo nl2br(htmlspecialchars($event['description'] ? mb_strimwidth($event['description'],0,200,'...') : '')); ?></p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <?php if (!empty($event['image'])): ?>
                                                <a href="#" class="btn btn-secondary btn-sm" onclick="openImageModal(event, 'uploads/<?php echo htmlspecialchars($event['image']); ?>')">
                                                    <i class="fa-solid fa-image"></i> Foto
                                                </a>
                                            <?php endif; ?>
                                            <div class="flex flex-col gap-1">
                                                <a href="admin.php?page=agenda&edit=<?php echo (int)$event['id']; ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fa-solid fa-pencil"></i> Bewerk
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Verwijder dit evenement?');" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm w-full">
                                                        <i class="fa-solid fa-trash"></i> Verwijder
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($page != 'banner'): ?>
                <?php
                $pageKey = $page;
                $pageLabelMap = [
                    'index' => 'Homepage',
                    'evenementen' => 'Evenementen',
                    'terugblikken' => 'Terugblikken',
                    'over' => 'Voor wie?',
                    'wie-zijn-we' => 'Wie zijn we?',
                    'verantwoord-ai' => 'Verantwoorde AI',
                    'contact' => 'Contact',
                    'programma-kennis' => 'Wat doen we: Kennis & vaardigheden',
                    'programma-actie' => 'Wat doen we: Actie, onderzoek & ontwerp',
                    'programma-faciliteit' => 'Wat doen we: Faciliteit van het Lab',
                ];
                $pageLabel = $pageLabelMap[$pageKey] ?? $pageKey;
                $isProgrammaPage = (strpos($pageKey, 'programma-') === 0);
                $itemLabel = $isProgrammaPage ? 'Kaart' : 'Item';
                $fields = ['title','body','image'];
                $extraLabels = [];
                switch ($pageKey) {
                    case 'contact':
                        $fields = ['title','body','address','email','map_embed','image'];
                        $extraLabels['address']='Adres';
                        $extraLabels['email']='E-mail';
                        $extraLabels['map_embed']='Map embed (iframe)';
                        break;
                    default:
                        $fields = ['title','body','image'];
                }
                $editMeta = [];
                if ($editPage && !empty($editPage['meta'])) {
                    $decodedEditMeta = json_decode($editPage['meta'], true);
                    if (is_array($decodedEditMeta)) {
                        $editMeta = $decodedEditMeta;
                    }
                }
                $currentImagePosition = $editMeta['image_position'] ?? 'normal';
                $currentGreenText = trim((string)($editMeta['green_text'] ?? ($editMeta['green_heading'] ?? '')));
                if (!in_array($currentImagePosition, ['normal', 'left', 'right'], true)) {
                    $currentImagePosition = 'normal';
                }
                ?>

                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-file-pen text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Beheer: <?php echo htmlspecialchars($pageLabel); ?></h2>
                    </div>
                    <?php if (strpos($pageKey, 'programma-') === 0): ?>
                        <p class="text-sm text-gray-600 mb-4">Hier kun je kaarten toevoegen, wijzigen en verwijderen voor de pagina onder de tab "Wat doen we". De eerste kaart wordt als brede intro bovenaan getoond; de rest komt in de rij met kaarten.</p>
                    <?php endif; ?>

                    <?php if ($editPage): ?>
                        <a href="admin.php?page=<?php echo urlencode($pageKey); ?>" class="btn btn-secondary mb-6">
                            <i class="fa-solid fa-arrow-left"></i> Terug
                        </a>
                    <?php endif; ?>

                    <?php if ($editPage): ?>
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6">
                            <h3 class="font-semibold text-lg">Bewerk <?php echo htmlspecialchars($itemLabel); ?></h3>
                            <input type="hidden" name="page_action" value="update_page">
                            <input type="hidden" name="id" value="<?php echo (int)$editPage['id']; ?>">
                            <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">

                            <div class="border-t-2 border-green-300 pt-4 mt-4">
                                <h4 class="font-semibold text-base text-green-700 mb-3">Groen tekst</h4>
                                <div>
                                    <label class="form-label">Groene tekst (optioneel)</label>
                                    <textarea name="green_text" rows="3" class="form-textarea" placeholder="Alles wat je hier invult wordt als groen blok boven de titel getoond."><?php echo htmlspecialchars($currentGreenText); ?></textarea>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Titel</label>
                                <input name="title" class="form-input" value="<?php echo htmlspecialchars($editPage['title']); ?>" />
                            </div>

                            <div>
                                <label class="form-label">Inhoud</label>
                                <textarea name="body" rows="6" class="form-textarea"><?php echo htmlspecialchars($editPage['body']); ?></textarea>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                                <?php if (!empty($editPage['image'])): ?>
                                    <p class="text-sm mt-2 text-gray-600">Huidige afbeelding: <?php echo htmlspecialchars($editPage['image']); ?></p>
                                    <label class="form-checkbox mt-2 inline-flex items-center gap-2">
                                        <input type="checkbox" name="remove_image" value="1">
                                        Verwijder huidige afbeelding
                                    </label>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="form-label">Foto positie</label>
                                <select name="image_position" class="form-input">
                                    <option value="normal" <?php echo $currentImagePosition === 'normal' ? 'selected' : ''; ?>>Normaal (foto onder)</option>
                                    <option value="left" <?php echo $currentImagePosition === 'left' ? 'selected' : ''; ?>>Foto links</option>
                                    <option value="right" <?php echo $currentImagePosition === 'right' ? 'selected' : ''; ?>>Foto rechts</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Opslaan
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6">
                            <h3 class="font-semibold text-lg">Nieuw <?php echo htmlspecialchars($itemLabel); ?></h3>
                            <input type="hidden" name="page_action" value="create_page">
                            <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">

                            <div class="border-t-2 border-green-300 pt-4 mt-4">
                                <h4 class="font-semibold text-base text-green-700 mb-3">Groen tekst</h4>
                                <div>
                                    <label class="form-label">Groene tekst (optioneel)</label>
                                    <textarea name="green_text" rows="3" class="form-textarea" placeholder="Alles wat je hier invult wordt als groen blok boven de titel getoond."></textarea>
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Titel</label>
                                <input name="title" class="form-input" />
                            </div>

                            <div>
                                <label class="form-label">Inhoud</label>
                                <textarea name="body" rows="6" class="form-textarea"></textarea>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                            </div>

                            <div>
                                <label class="form-label">Foto positie</label>
                                <select name="image_position" class="form-input">
                                    <option value="normal" selected>Normaal (foto onder)</option>
                                    <option value="left">Foto links</option>
                                    <option value="right">Foto rechts</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Positie</label>
                                <select name="insert_position" class="form-input">
                                    <option value="bottom" selected>Onderaan toevoegen</option>
                                    <option value="top">Bovenaan toevoegen</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> <?php echo $isProgrammaPage ? 'Kaart toevoegen' : 'Toevoegen'; ?>
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="card p-6">
                        <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                            <i class="fa-solid fa-list text-2xl text-[#00811F]"></i>
                            <h3 class="text-xl font-bold">Bestaande <?php echo $isProgrammaPage ? 'Kaarten' : 'Items'; ?></h3>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Bij elk item staat een vak <strong>Groene tekst boven titel</strong>. Vul daar je tekst in en klik op <strong>Opslaan</strong>.</p>
                        <?php if (empty($pageItems)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                                <p>Geen <?php echo $isProgrammaPage ? 'kaarten' : 'items'; ?> toegevoegd voor deze pagina</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-4 flex gap-2 items-center">
                                <input type="checkbox" id="selectAllPages_<?php echo htmlspecialchars($pageKey); ?>" class="w-4 h-4 cursor-pointer selectAllPages" data-page="<?php echo htmlspecialchars($pageKey); ?>" title="Selecteer alles">
                                <label for="selectAllPages_<?php echo htmlspecialchars($pageKey); ?>" class="cursor-pointer text-sm font-medium">Selecteer alles</label>
                                <button type="button" class="btn btn-danger btn-sm" id="bulkDeletePagesBtn_<?php echo htmlspecialchars($pageKey); ?>" style="display:none;">
                                    <i class="fa-solid fa-trash"></i> Verwijder geselecteerde (<span id="pageCount_<?php echo htmlspecialchars($pageKey); ?>">0</span>)
                                </button>
                            </div>
                            <form method="POST" id="bulkDeletePagesForm_<?php echo htmlspecialchars($pageKey); ?>">
                                <input type="hidden" name="action" value="delete_bulk_pages">
                                <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">
                            <div class="space-y-4">
                            <?php foreach ($pageItems as $index => $it): ?>
                                <?php
                                    $isFirst = ($index === 0);
                                    $isLast = ($index === (count($pageItems) - 1));
                                    $itemMeta = [];
                                    if (!empty($it['meta'])) {
                                        $decodedItemMeta = json_decode($it['meta'], true);
                                        if (is_array($decodedItemMeta)) {
                                            $itemMeta = $decodedItemMeta;
                                        }
                                    }
                                    $itemGreenText = trim((string)($itemMeta['green_text'] ?? ($itemMeta['green_heading'] ?? '')));
                                ?>
                                <div class="border border-gray-200 rounded p-4 hover:shadow-md transition">
                                    <div class="flex justify-between items-start gap-4">
                                        <input type="checkbox" class="page-checkbox page-checkbox-<?php echo htmlspecialchars($pageKey); ?> w-4 h-4 mt-1 flex-shrink-0 cursor-pointer" name="page_ids[]" value="<?php echo (int)$it['id']; ?>">
                                        <div class="flex-1">
                                            <form method="POST" class="mb-4 p-3 bg-gray-50 rounded border border-gray-300">
                                                <label class="text-sm font-semibold text-gray-700 block mb-2">Groene tekst boven titel:</label>
                                                <input type="hidden" name="page_action" value="update_page_green_text">
                                                <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                                <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">
                                                
                                                <?php if ($itemGreenText !== ''): ?>
                                                    <div class="mb-3">
                                                        <span class="text-xs text-gray-600 font-semibold">VOORBEELD:</span>
                                                        <div class="green-highlight"><?php echo nl2br(htmlspecialchars($itemGreenText)); ?></div>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <textarea name="green_text" rows="2" class="form-textarea text-sm w-full" placeholder="Groene tekst boven de titel"><?php echo htmlspecialchars($itemGreenText); ?></textarea>
                                                <button type="submit" class="btn btn-secondary btn-sm mt-2">
                                                    <i class="fa-solid fa-pen"></i> Opslaan
                                                </button>
                                            </form>
                                            <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($it['title'] ?? ''); ?></h4>
                                            <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?php echo nl2br(htmlspecialchars($it['body'] ? mb_strimwidth($it['body'],0,150,'...') : '')); ?></p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <?php if (!empty($it['image'])): ?>
                                                <a href="#" class="btn btn-secondary btn-sm" onclick="openImageModal(event, 'uploads/<?php echo htmlspecialchars($it['image']); ?>')">
                                                    <i class="fa-solid fa-image"></i> Foto
                                                </a>
                                            <?php endif; ?>
                                            <div class="flex flex-col gap-1">
                                                <div class="flex gap-1">
                                                    <form method="POST">
                                                        <input type="hidden" name="page_action" value="reorder_page">
                                                        <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">
                                                        <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                                        <input type="hidden" name="direction" value="up">
                                                        <button type="submit" class="btn btn-secondary btn-sm" <?php echo $isFirst ? 'disabled' : ''; ?> title="Omhoog">
                                                            <i class="fa-solid fa-arrow-up"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST">
                                                        <input type="hidden" name="page_action" value="reorder_page">
                                                        <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">
                                                        <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                                        <input type="hidden" name="direction" value="down">
                                                        <button type="submit" class="btn btn-secondary btn-sm" <?php echo $isLast ? 'disabled' : ''; ?> title="Omlaag">
                                                            <i class="fa-solid fa-arrow-down"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                <a href="admin.php?edit_page=<?php echo (int)$it['id']; ?>&page=<?php echo urlencode($pageKey); ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fa-solid fa-pencil"></i> Bewerk
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Verwijder dit item?');" style="display:inline;">
                                                    <input type="hidden" name="page_action" value="delete_page">
                                                    <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">
                                                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm w-full">
                                                        <i class="fa-solid fa-trash"></i> Verwijder
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-image text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Banner Beheer</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <p class="font-semibold mb-2">Banner 1</p>
                            <img src="<?php echo htmlspecialchars($banner1); ?>" alt="Banner 1" class="w-full h-auto border rounded-lg shadow">
                        </div>
                        <div>
                            <p class="font-semibold mb-2">Banner 2</p>
                            <img src="<?php echo htmlspecialchars($banner2); ?>" alt="Banner 2" class="w-full h-auto border rounded-lg shadow">
                        </div>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" value="update_banner">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label">Vervang Banner 1</label>
                                <input type="file" name="banner1" accept="image/*" class="form-input" />
                                <label class="form-checkbox mt-2 inline-flex items-center gap-2">
                                    <input type="checkbox" name="remove_banner1" value="1">
                                    Verwijder huidige banner 1
                                </label>
                                <small class="text-gray-600">Aanbevolen formaat: 1920x600px</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vervang Banner 2</label>
                                <input type="file" name="banner2" accept="image/*" class="form-input" />
                                <label class="form-checkbox mt-2 inline-flex items-center gap-2">
                                    <input type="checkbox" name="remove_banner2" value="1">
                                    Verwijder huidige banner 2
                                </label>
                                <small class="text-gray-600">Aanbevolen formaat: 1920x600px</small>
                            </div>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Banners Bijwerken
                            </button>
                        </div>
                    </form>

                    <form method="POST" onsubmit="return confirm('Weet je zeker dat je de banners wilt resetten naar de standaard afbeeldingen?');" class="mt-4 pt-4">
                        <input type="hidden" name="action" value="reset_banners">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fa-solid fa-rotate-left"></i> Reset naar Standaard Banners
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<script>
// Bulk selection for events
(function() {
  const selectAllEvents = document.getElementById('selectAllEvents');
  const eventCheckboxes = document.querySelectorAll('.event-checkbox');
  const bulkDeleteEventsBtn = document.getElementById('bulkDeleteEventsBtn');
  const bulkDeleteEventsForm = document.getElementById('bulkDeleteEventsForm');
  const eventCount = document.getElementById('eventCount');
  
  if (!selectAllEvents || !eventCheckboxes.length) return;
  
  function updateEventBulkDelete() {
    const checked = document.querySelectorAll('.event-checkbox:checked').length;
    if (checked > 0) {
      bulkDeleteEventsBtn.style.display = 'inline-flex';
      eventCount.textContent = checked;
    } else {
      bulkDeleteEventsBtn.style.display = 'none';
    }
    selectAllEvents.checked = checked === eventCheckboxes.length && eventCheckboxes.length > 0;
  }
  
  selectAllEvents.addEventListener('change', function() {
    eventCheckboxes.forEach(cb => cb.checked = this.checked);
    updateEventBulkDelete();
  });
  
  eventCheckboxes.forEach(cb => {
    cb.addEventListener('change', updateEventBulkDelete);
  });
  
  if (bulkDeleteEventsBtn && bulkDeleteEventsForm) {
    bulkDeleteEventsBtn.addEventListener('click', function(e) {
      const checked = document.querySelectorAll('.event-checkbox:checked').length;
      if (checked === 0) {
        e.preventDefault();
        alert('Selecteer minimaal één item');
        return false;
      }
      if (!confirm('Verwijder alle geselecteerde evenementen?')) {
        e.preventDefault();
        return false;
      }
      bulkDeleteEventsForm.submit();
    });
  }
})();

// Bulk selection for page items
(function() {
  const selectAllPageButtons = document.querySelectorAll('.selectAllPages');
  if (!selectAllPageButtons.length) return;
  
  selectAllPageButtons.forEach(btn => {
    const pageKey = btn.dataset.page;
    const pageCheckboxes = document.querySelectorAll(`.page-checkbox-${pageKey}`);
    const bulkDeletePagesBtn = document.getElementById(`bulkDeletePagesBtn_${pageKey}`);
    const bulkDeletePagesForm = document.getElementById(`bulkDeletePagesForm_${pageKey}`);
    const pageCount = document.getElementById(`pageCount_${pageKey}`);
    
    if (!pageCheckboxes.length || !bulkDeletePagesForm) return;
    
    function updatePageBulkDelete() {
      const checked = document.querySelectorAll(`.page-checkbox-${pageKey}:checked`).length;
      if (checked > 0) {
        bulkDeletePagesBtn.style.display = 'inline-flex';
        pageCount.textContent = checked;
      } else {
        bulkDeletePagesBtn.style.display = 'none';
      }
      btn.checked = checked === pageCheckboxes.length && pageCheckboxes.length > 0;
    }
    
    btn.addEventListener('change', function() {
      pageCheckboxes.forEach(cb => cb.checked = this.checked);
      updatePageBulkDelete();
    });
    
    pageCheckboxes.forEach(cb => {
      cb.addEventListener('change', updatePageBulkDelete);
    });
    
    if (bulkDeletePagesBtn) {
      bulkDeletePagesBtn.addEventListener('click', function(e) {
        const checked = document.querySelectorAll(`.page-checkbox-${pageKey}:checked`).length;
        if (checked === 0) {
          e.preventDefault();
          alert('Selecteer minimaal één item');
          return false;
        }
        if (!confirm('Verwijder alle geselecteerde items?')) {
          e.preventDefault();
          return false;
        }
        bulkDeletePagesForm.submit();
      });
    }
  });
})();

// Toggle image visibility in modal
function openImageModal(event, imageSrc) {
  event.preventDefault();
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    if (modal && modalImg) {
        modalImg.src = imageSrc;
        modal.classList.add('active');
  }
}

// Close modal
function closeImageModal() {
  const modal = document.getElementById('imageModal');
  if (modal) {
    modal.classList.remove('active');
  }
}

// Close modal on background click
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const sidebarHeader = document.querySelector('.sidebar-header');
    const sidebarScroll = document.querySelector('.sidebar-nav-scroll');
  const modal = document.getElementById('imageModal');

    function syncAdminSidebarScroll() {
        if (!sidebar || !sidebarHeader || !sidebarScroll) {
            return;
        }

        if (window.innerWidth < 1024) {
            sidebar.style.height = '';
            sidebar.style.maxHeight = '';
            sidebar.style.overflowY = '';
            sidebarHeader.style.position = '';
            sidebarHeader.style.top = '';
            sidebarScroll.style.maxHeight = '';
            sidebarScroll.style.overflowY = '';
            return;
        }

        const topOffset = 110;
        const bottomSpacing = 30;
        const sidebarHeight = Math.max(320, window.innerHeight - topOffset - bottomSpacing);
        const headerHeight = sidebarHeader.offsetHeight;
        const scrollHeight = Math.max(180, sidebarHeight - headerHeight);

        sidebar.style.height = `${sidebarHeight}px`;
        sidebar.style.maxHeight = `${sidebarHeight}px`;
        sidebar.style.overflowY = 'auto';
        sidebarHeader.style.position = 'sticky';
        sidebarHeader.style.top = '0';
        sidebarScroll.style.maxHeight = `${scrollHeight}px`;
        sidebarScroll.style.overflowY = 'auto';
    }

    syncAdminSidebarScroll();
    window.addEventListener('resize', syncAdminSidebarScroll);

    if (sidebar && sidebarScroll) {
        sidebar.addEventListener('wheel', function(e) {
            if (window.innerWidth < 1024) {
                return;
            }

            const canScroll = sidebarScroll.scrollHeight > sidebarScroll.clientHeight;
            if (!canScroll) {
                return;
            }

            const isInsideSidebar = sidebar.contains(e.target);
            if (!isInsideSidebar) {
                return;
            }

            e.preventDefault();
            sidebarScroll.scrollTop += e.deltaY;
        }, { passive: false });
    }

  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeImageModal();
      }
    });
  }
  
  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeImageModal();
    }
  });
});
</script>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
  <div class="image-modal-content">
    <button class="image-modal-close" onclick="closeImageModal()" aria-label="Sluiten">
      <i class="fa-solid fa-xmark"></i>
    </button>
    <img id="modalImage" src="" alt="Preview">
  </div>
</div>

</body>
</html>


