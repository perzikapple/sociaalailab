<?php
session_start();
require 'db.php';
require 'helpers.php';
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header('Location: login.php');
    exit;
}

$message = '';
// Helper voor upload
function handleUpload($fileField) {
    if (empty($_FILES[$fileField]['name'])) return null;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($_FILES[$fileField]['type'], $allowed) || $_FILES[$fileField]['size'] > 10 * 1024 * 1024) {
        return ['error' => 'Ongeldig afbeeldingsbestand (jpg/png/gif/webp, max 10MB).'];
    }
    if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);
    $ext = pathinfo($_FILES[$fileField]['name'], PATHINFO_EXTENSION);
    $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = __DIR__ . '/uploads/' . $imageName;
    if (!move_uploaded_file($_FILES[$fileField]['tmp_name'], $dest)) {
        return ['error' => 'Kon afbeelding niet opslaan.'];
    }
    return ['name' => $imageName];
}

function sanitizeEditorText($value) {
    return sanitizeEditorPlainText($value);
}

function sanitizeEditorInlineInput($value) {
    return sanitizeEditorHtml($value, 'inline');
}

function sanitizeEditorBlockInput($value) {
    return sanitizeEditorHtml($value, 'block');
}

// Fetch current banners
$banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: 'images/banner_website_01.jpg';
$banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: 'images/banner_website_02.jpg';

// Verwerk POST acties
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $currentUser = $_SESSION['user'] ?? null; // email van ingelogde gebruiker (kan null zijn)

    if ($action === 'create') {
        $title = sanitizeEditorInlineInput($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $end_date = isset($_POST['add_end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $time = $_POST['time'] ?? null;
        $time_end = isset($_POST['add_end_time']) && !empty($_POST['time_end']) ? $_POST['time_end'] : null;
        $description = sanitizeEditorBlockInput($_POST['description'] ?? '');
        $location = sanitizeEditorPlainText($_POST['location'] ?? '');
        $showSignupButton = isset($_POST['show_signup_button']) ? 1 : 0;

        if ($date === '') {
            $message = 'Datum is verplicht.';
        } else {
            if ($title === '') {
                $title = ' ';
            }
            $upload = handleUpload('image');
            if (isset($upload['error'])) {
                $message = $upload['error'];
            } else {
                $imageName = $upload['name'] ?? null;
                // voeg updated_at en updated_by toe bij insert
                $stmt = $pdo->prepare('INSERT INTO events (title, date, end_date, time, time_end, description, image, location, show_signup_button, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
                $stmt->execute([$title, $date, $end_date, $time ?: null, $time_end ?: null, $description, $imageName, $location ?: null, $showSignupButton, $currentUser]);
                $eventId = $pdo->lastInsertId();
                // Audit log: event created
                audit_log($pdo, 'create', 'events', $eventId, 'title: ' . $title, $currentUser);
                header('Location: admin.php?page=agenda&ok=create');
                exit;
            }
        }

    } elseif ($action === 'update' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $title = sanitizeEditorInlineInput($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $end_date = isset($_POST['add_end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $time = $_POST['time'] ?? null;
        $time_end = isset($_POST['add_end_time']) && !empty($_POST['time_end']) ? $_POST['time_end'] : null;
        $description = sanitizeEditorBlockInput($_POST['description'] ?? '');
        $location = sanitizeEditorPlainText($_POST['location'] ?? '');
        $showSignupButton = isset($_POST['show_signup_button']) ? 1 : 0;
        $removeImage = isset($_POST['remove_image']) ? 1 : 0;

        if ($date === '') {
            $message = 'Datum is verplicht.';
        } else {
            if ($title === '') {
                $title = ' ';
            }
            // check existing image
            $stmt = $pdo->prepare('SELECT image FROM events WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $oldImage = $row ? $row['image'] : null;

            $upload = handleUpload('image');
            if (isset($upload['error'])) {
                $message = $upload['error'];
            } else {
                if ($removeImage) {
                    $imageName = null;
                } else {
                    $imageName = $upload['name'] ?? $oldImage;
                }
                // update nu ook updated_at en updated_by
                $stmt = $pdo->prepare('UPDATE events SET title=?, date=?, end_date=?, time=?, time_end=?, description=?, image=?, location=?, show_signup_button=?, updated_at=NOW(), updated_by=? WHERE id=?');
                $stmt->execute([$title, $date, $end_date, $time ?: null, $time_end ?: null, $description, $imageName, $location ?: null, $showSignupButton, $currentUser, $id]);
                // Audit log: event updated
                audit_log($pdo, 'update', 'events', $id, 'title: ' . $title, $currentUser);

                // indien nieuwe upload en oud bestaat: verwijderen
                if (!empty($upload['name']) && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
                    @unlink(__DIR__ . '/uploads/' . $oldImage);
                }
                if ($removeImage && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
                    @unlink(__DIR__ . '/uploads/' . $oldImage);
                }
                header('Location: admin.php?page=agenda&ok=update');
                exit;
            }
        }

    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        // haal image op en delete
        $stmt = $pdo->prepare('SELECT image FROM events WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            if ($row['image'] && file_exists(__DIR__ . '/uploads/' . $row['image'])) {
                @unlink(__DIR__ . '/uploads/' . $row['image']);
            }
            $stmt = $pdo->prepare('DELETE FROM events WHERE id = ?');
            $stmt->execute([$id]);
            // Audit log: event deleted
            audit_log($pdo, 'delete', 'events', $id, null, $currentUser);
            header('Location: admin.php?page=agenda&ok=delete');
            exit;
        } else {
            $message = 'Evenement niet gevonden.';
        }

    } elseif ($action === 'delete_bulk_events' && !empty($_POST['event_ids'])) {
        $ids = $_POST['event_ids'];
        if (!is_array($ids)) $ids = [$ids];
        $ids = array_map('intval', $ids);
        
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
    $title = sanitizeEditorInlineInput($_POST['title'] ?? '');
    $body = sanitizeEditorBlockInput($_POST['body'] ?? '');
    $insertPosition = $_POST['insert_position'] ?? 'bottom';
    $imagePosition = $_POST['image_position'] ?? 'normal';
    if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) {
        $imagePosition = 'normal';
    }

    if ($title === '') {
        $title = ' ';
    }

    if (empty($pageKey)) {
        $message = 'Page key is verplicht.';
    } else {
        $meta = [
            'image_position' => $imagePosition,
        ];

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
    $title = sanitizeEditorInlineInput($_POST['title'] ?? '');
    $body = sanitizeEditorBlockInput($_POST['body'] ?? '');
    $removeImage = isset($_POST['remove_image']) ? 1 : 0;

    if ($title === '') {
        $title = ' ';
    }

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
if ($page !== 'banner' && $page !== 'agenda' && $page !== 'audit') {
    $stmt = $pdo->prepare(
        'SELECT * FROM pages WHERE page_key = ?
         ORDER BY (sort_order IS NULL OR sort_order = 0) ASC, sort_order ASC, created_at ASC, id ASC'
    );
    $stmt->execute([$page]);
    $pageItems = $stmt->fetchAll();
}

$auditLogs = [];
$auditActions = [];
$auditTables = [];
$auditTotal = 0;
$auditPage = max(1, (int)($_GET['audit_page'] ?? 1));
$auditPerPageOptions = [25, 50, 100, 200];
$auditPerPage = (int)($_GET['audit_per_page'] ?? 50);
if (!in_array($auditPerPage, $auditPerPageOptions, true)) {
    $auditPerPage = 50;
}
$auditTotalPages = 1;
$auditActionFilter = trim((string)($_GET['audit_action'] ?? ''));
$auditTableFilter = trim((string)($_GET['audit_table'] ?? ''));

if ($page === 'audit') {
    try {
        $auditActions = $pdo->query("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL AND action <> '' ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
        $auditTables = $pdo->query("SELECT DISTINCT table_name FROM audit_logs WHERE table_name IS NOT NULL AND table_name <> '' ORDER BY table_name ASC")->fetchAll(PDO::FETCH_COLUMN);

        $whereParts = [];
        $params = [];

        if ($auditActionFilter !== '') {
            $whereParts[] = 'action = ?';
            $params[] = $auditActionFilter;
        }
        if ($auditTableFilter !== '') {
            $whereParts[] = 'table_name = ?';
            $params[] = $auditTableFilter;
        }

        $whereSql = $whereParts ? (' WHERE ' . implode(' AND ', $whereParts)) : '';

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM audit_logs' . $whereSql);
        $countStmt->execute($params);
        $auditTotal = (int)$countStmt->fetchColumn();
        $auditTotalPages = max(1, (int)ceil($auditTotal / $auditPerPage));
        if ($auditPage > $auditTotalPages) {
            $auditPage = $auditTotalPages;
        }
        $offset = ($auditPage - 1) * $auditPerPage;

        $listSql = 'SELECT id, action, table_name, record_id, details, performed_by, created_at FROM audit_logs'
            . $whereSql
            . ' ORDER BY created_at DESC, id DESC'
            . ' LIMIT ' . (int)$auditPerPage . ' OFFSET ' . (int)$offset;

        $listStmt = $pdo->prepare($listSql);
        $listStmt->execute($params);
        $auditLogs = $listStmt->fetchAll();
    } catch (Exception $e) {
        $message = 'Auditlogboek kon niet worden geladen.';
    }
}
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin - SociaalAI Lab</title>
<link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__.'/style.css'); ?>">
<link rel="stylesheet" href="admin-styles.css?v=<?php echo filemtime(__DIR__.'/admin-styles.css'); ?>">
<link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="icon" type="image/png" href="images/Pixels_icon.png">
<script src="custom.js"></script>
</head>
<!-- TinyMCE toevoegen met API key -->
<script src="https://cdn.tiny.cloud/1/1ui5rgslm5rlya4exbujnv26e5j6xyq87233fv56zmvcq39e/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const isCompactEditor = window.matchMedia('(max-width: 1023.98px)').matches;
        const compactToolbar = 'undo redo | bold italic | bullist numlist | link';
        const fullToolbar = 'undo redo | bold italic underline | bullist numlist | link image | table';
        const compactPlugins = 'lists link';
        const fullPlugins = 'lists link image table';

        // TinyMCE for title fields - compact
        tinymce.init({
            selector: 'textarea[name="title"]',
            plugins: isCompactEditor ? compactPlugins : fullPlugins,
            toolbar: isCompactEditor ? compactToolbar : fullToolbar,
            menubar: false,
            statusbar: !isCompactEditor,
            toolbar_mode: isCompactEditor ? 'scrolling' : 'wrap',
            branding: false,
            height: isCompactEditor ? 42 : 50,
            resize: true
        });
        
        // TinyMCE for content fields - larger
        tinymce.init({
            selector: 'textarea:not([name="title"])',
            plugins: isCompactEditor ? compactPlugins : fullPlugins,
            toolbar: isCompactEditor ? compactToolbar : fullToolbar,
            menubar: false,
            statusbar: !isCompactEditor,
            toolbar_mode: isCompactEditor ? 'scrolling' : 'wrap',
            branding: false,
            height: isCompactEditor ? 220 : 300,
            resize: true
        });
    });
</script>
</head>
<body class="admin-page">
<!-- Sidebar Toggle Button -->
<button class="sidebar-toggle-btn" id="sidebarToggle">
    <i class="fa-solid fa-bars"></i>
</button>
<nav class="admin-header text-white p-4 sticky top-0 z-50">
    <div class="admin-header-inner flex justify-end items-center px-8">
        <div class="admin-header-brand">
            <i class="fa-solid fa-gears text-2xl"></i>
            <h1 class="text-2xl font-bold">Admin Panel</h1>
        </div>
        <div class="admin-header-actions flex items-center gap-3 flex-nowrap">
            <a href="admin.php?page=audit" class="btn <?php echo $page==='audit' ? 'btn-primary' : 'btn-secondary'; ?> text-sm">
                <i class="fa-solid fa-clipboard-list"></i> Auditlogboek
            </a>
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

    <div class="admin-layout-grid grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-bars"></i> Navigatie
            </div>
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
                                <textarea name="title" rows="1" class="form-textarea"><?php echo htmlspecialchars($editEvent['title'] ?? ''); ?></textarea>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="form-label">Wanneer (datum)</label>
                                    <input type="date" name="date" required class="form-input" value="<?php echo htmlspecialchars($editEvent['date']); ?>" />
                                    <div class="mt-2">
                                        <label class="form-checkbox">
                                            <input type="checkbox" id="add-end-date-edit" name="add_end_date" <?php echo !empty($editEvent['end_date']) ? 'checked' : ''; ?> />
                                            Einddatum toevoegen
                                        </label>
                                        <div id="end-date-container-edit" class="admin-mt-8 <?php echo empty($editEvent['end_date']) ? 'admin-hidden' : ''; ?>">
                                            <label class="form-label">Einddatum <span class="text-xs text-gray-500">(optioneel)</span></label>
                                            <input type="date" name="end_date" class="form-input" value="<?php echo htmlspecialchars($editEvent['end_date'] ?? ''); ?>" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Starttijd</label>
                                    <input type="time" name="time" class="form-input" value="<?php echo htmlspecialchars($editEvent['time'] ?? ''); ?>" />
                                    <div class="mt-2">
                                        <label class="form-checkbox">
                                            <input type="checkbox" id="add-end-time-edit" name="add_end_time" <?php echo !empty($editEvent['time_end']) ? 'checked' : ''; ?> />
                                            Eindtijd toevoegen
                                        </label>
                                        <div id="end-time-container-edit" class="admin-mt-8 <?php echo empty($editEvent['time_end']) ? 'admin-hidden' : ''; ?>">
                                            <label class="form-label">Eindtijd <span class="text-xs text-gray-500">(optioneel)</span></label>
                                            <input type="time" name="time_end" class="form-input" value="<?php echo htmlspecialchars($editEvent['time_end'] ?? ''); ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                            <div>
                                <label class="form-label">Plaats</label>
                                <input name="location" class="form-input admin-input-surface admin-input-h-48" value="<?php echo htmlspecialchars($editEvent['location'] ?? ''); ?>" />
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
                                <textarea name="title" rows="1" class="form-textarea"></textarea>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="form-label">Wanneer (datum)</label>
                                    <input type="date" name="date" required class="form-input" />
                                    <div class="mt-2">
                                        <label class="form-checkbox">
                                            <input type="checkbox" id="add-end-date-create" name="add_end_date" />
                                            Einddatum toevoegen
                                        </label>
                                        <div id="end-date-container-create" class="admin-mt-8 admin-hidden">
                                            <label class="form-label">Einddatum <span class="text-xs text-gray-500">(optioneel)</span></label>
                                            <input type="date" name="end_date" class="form-input" />
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label">Starttijd</label>
                                    <input type="time" name="time" class="form-input" value="<?php echo htmlspecialchars($editEvent['time'] ?? ''); ?>" />
                                    <div class="mt-2">
                                        <label class="form-checkbox">
                                            <input type="checkbox" id="add-end-time-create" name="add_end_time" />
                                            Eindtijd toevoegen
                                        </label>
                                        <div id="end-time-container-create" class="admin-mt-8 admin-hidden">
                                            <label class="form-label">Eindtijd <span class="text-xs text-gray-500">(optioneel)</span></label>
                                            <input type="time" name="time_end" class="form-input" value="<?php echo htmlspecialchars($editEvent['time_end'] ?? ''); ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
<script>
// Toggle end date/time for create/edit
document.addEventListener('DOMContentLoaded', function() {
    var addEndDateCreate = document.getElementById('add-end-date-create');
    var endDateContainerCreate = document.getElementById('end-date-container-create');
    if (addEndDateCreate && endDateContainerCreate) {
        addEndDateCreate.addEventListener('change', function() {
            endDateContainerCreate.style.display = this.checked ? 'block' : 'none';
        });
    }
    var addEndDateEdit = document.getElementById('add-end-date-edit');
    var endDateContainerEdit = document.getElementById('end-date-container-edit');
    if (addEndDateEdit && endDateContainerEdit) {
        addEndDateEdit.addEventListener('change', function() {
            endDateContainerEdit.style.display = this.checked ? 'block' : 'none';
        });
    }
    var addEndTimeCreate = document.getElementById('add-end-time-create');
    var endTimeContainerCreate = document.getElementById('end-time-container-create');
    if (addEndTimeCreate && endTimeContainerCreate) {
        addEndTimeCreate.addEventListener('change', function() {
            endTimeContainerCreate.style.display = this.checked ? 'block' : 'none';
        });
    }
    var addEndTimeEdit = document.getElementById('add-end-time-edit');
    var endTimeContainerEdit = document.getElementById('end-time-container-edit');
    if (addEndTimeEdit && endTimeContainerEdit) {
        addEndTimeEdit.addEventListener('change', function() {
            endTimeContainerEdit.style.display = this.checked ? 'block' : 'none';
        });
    }
});
</script>

                            <div>
                                <label class="form-label">Plaats</label>
                                <input name="location" class="form-input admin-input-surface" />
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
                                <button type="button" class="btn btn-danger btn-sm admin-hidden" id="bulkDeleteEventsBtn">
                                    <i class="fa-solid fa-trash"></i> Verwijder geselecteerde (<span id="eventCount">0</span>)
                                </button>
                            </div>
                            <form method="POST" id="bulkDeleteEventsForm">
                                <input type="hidden" name="action" value="delete_bulk_events">
                            <div class="space-y-4">
                            <?php foreach ($events as $event): ?>
                                <div class="border border-gray-200 rounded p-4 hover:shadow-md transition">
                                    <div class="admin-row flex justify-between items-start gap-4">
                                        <input type="checkbox" class="event-checkbox w-4 h-4 mt-1 flex-shrink-0 cursor-pointer" name="event_ids[]" value="<?php echo (int)$event['id']; ?>">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars(sanitizeEditorText($event['title'])); ?></h4>
                                            <?php 
                                                $dateDisplay = formatEventDateDisplay($event['date']); 
                                                $timeDisplay = $event['time'] ? formatEventTimeDisplay($event['time']) : ''; 
                                                $timeEndDisplay = $event['time_end'] ? formatEventTimeDisplay($event['time_end']) : ''; 
                                                $eventDescriptionPreview = editorPreviewText($event['description'] ?? '', 200);
                                            ?>
                                            <p class="text-sm text-gray-600 mt-1"><strong>Wanneer:</strong> <?php echo htmlspecialchars($dateDisplay); ?><?php if ($timeDisplay) { echo ' ' . htmlspecialchars($timeDisplay); } ?><?php if ($timeEndDisplay) { echo ' - ' . htmlspecialchars($timeEndDisplay); } ?></p>
                                            <?php if (!empty($event['location'])): ?>
                                                <p class="text-sm text-gray-600"><strong>Plaats:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-600 mt-2 line-clamp-2"><?php echo nl2br(htmlspecialchars($eventDescriptionPreview)); ?></p>
                                        </div>
                                        <div class="admin-row-actions flex gap-2 flex-shrink-0">
                                            <?php if (!empty($event['image'])): ?>
                                                <a href="#" class="btn btn-secondary btn-sm" onclick="openImageModal(event, 'uploads/<?php echo htmlspecialchars($event['image']); ?>')">
                                                    <i class="fa-solid fa-image"></i> Foto
                                                </a>
                                            <?php endif; ?>
                                            <div class="admin-row-actions-stack flex flex-col gap-1">
                                                <a href="admin.php?page=agenda&edit=<?php echo (int)$event['id']; ?>" class="btn btn-secondary btn-sm">
                                                    <i class="fa-solid fa-pencil"></i> Bewerk
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Verwijder dit evenement?');" class="admin-inline-form">
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

            <?php elseif ($page === 'audit'): ?>
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-clipboard-list text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Auditlogboek</h2>
                    </div>

                    <form method="GET" class="bg-white p-4 rounded border border-gray-200 mb-6">
                        <input type="hidden" name="page" value="audit">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <div>
                                <label class="form-label">Actie</label>
                                <select name="audit_action" class="form-input">
                                    <option value="">Alle acties</option>
                                    <?php foreach ($auditActions as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $auditActionFilter === $action ? 'selected' : ''; ?>><?php echo htmlspecialchars($action); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Tabel</label>
                                <select name="audit_table" class="form-input">
                                    <option value="">Alle tabellen</option>
                                    <?php foreach ($auditTables as $tableName): ?>
                                        <option value="<?php echo htmlspecialchars($tableName); ?>" <?php echo $auditTableFilter === $tableName ? 'selected' : ''; ?>><?php echo htmlspecialchars($tableName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Per pagina</label>
                                <select name="audit_per_page" class="form-input">
                                    <?php foreach ($auditPerPageOptions as $perPageOption): ?>
                                        <option value="<?php echo (int)$perPageOption; ?>" <?php echo $auditPerPage === (int)$perPageOption ? 'selected' : ''; ?>>
                                            <?php echo (int)$perPageOption; ?> regels
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="audit-filter-actions flex gap-2">
                                <button type="submit" class="btn btn-audit-accent">
                                    <i class="fa-solid fa-filter"></i> Filter
                                </button>
                                <a href="admin.php?page=audit" class="btn btn-audit-accent">
                                    <i class="fa-solid fa-rotate-left"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>

                    <div class="mb-4 text-sm text-gray-600">
                        Totaal logregels: <strong><?php echo (int)$auditTotal; ?></strong> - Per pagina: <strong><?php echo (int)$auditPerPage; ?></strong>
                    </div>

                    <?php if (empty($auditLogs)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                            <p>Geen auditregels gevonden voor deze filter.</p>
                        </div>
                    <?php else: ?>
                        <div class="audit-table-wrap overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="audit-table w-full text-sm">
                                <thead class="bg-gray-100 text-left">
                                    <tr>
                                        <th class="p-3">Datum</th>
                                        <th class="p-3">Actie</th>
                                        <th class="p-3">Tabel</th>
                                        <th class="p-3">Record ID</th>
                                        <th class="p-3">Details</th>
                                        <th class="p-3">Gebruiker</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($auditLogs as $log): ?>
                                        <?php
                                            $createdAt = !empty($log['created_at']) ? date('d-m-Y H:i:s', strtotime($log['created_at'])) : '-';
                                            $detailsRaw = trim((string)($log['details'] ?? ''));
                                            $detailsPreview = $detailsRaw;
                                            if (strlen($detailsPreview) > 200) {
                                                $detailsPreview = substr($detailsPreview, 0, 197) . '...';
                                            }
                                        ?>
                                        <tr class="border-t border-gray-200 align-top">
                                            <td class="p-3 whitespace-nowrap" data-label="Datum"><?php echo htmlspecialchars($createdAt); ?></td>
                                            <td class="p-3" data-label="Actie"><?php echo htmlspecialchars((string)$log['action']); ?></td>
                                            <td class="p-3" data-label="Tabel"><?php echo htmlspecialchars((string)$log['table_name']); ?></td>
                                            <td class="p-3" data-label="Record ID"><?php echo htmlspecialchars((string)($log['record_id'] ?? '-')); ?></td>
                                            <td class="p-3 text-gray-700 audit-details" data-label="Details">
                                                <div class="audit-details-content"><?php echo nl2br(htmlspecialchars($detailsPreview !== '' ? $detailsPreview : '-')); ?></div>
                                                <?php if ($detailsRaw !== '' && $detailsRaw !== $detailsPreview): ?>
                                                    <details class="audit-details-more">
                                                        <summary>Meer details</summary>
                                                        <div class="audit-details-content"><?php echo nl2br(htmlspecialchars($detailsRaw)); ?></div>
                                                    </details>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3" data-label="Gebruiker"><?php echo htmlspecialchars((string)($log['performed_by'] ?? '-')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php
                        $queryBase = 'page=audit';
                        if ($auditActionFilter !== '') {
                            $queryBase .= '&audit_action=' . urlencode($auditActionFilter);
                        }
                        if ($auditTableFilter !== '') {
                            $queryBase .= '&audit_table=' . urlencode($auditTableFilter);
                        }
                        if ($auditPerPage !== 50) {
                            $queryBase .= '&audit_per_page=' . (int)$auditPerPage;
                        }
                        ?>
                        <div class="audit-pagination flex items-center justify-between mt-4">
                            <div class="text-sm text-gray-600">
                                Pagina <?php echo (int)$auditPage; ?> van <?php echo (int)$auditTotalPages; ?>
                            </div>
                            <div class="flex gap-2">
                                <?php if ($auditPage > 1): ?>
                                    <a href="admin.php?<?php echo $queryBase; ?>&audit_page=<?php echo (int)($auditPage - 1); ?>" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-chevron-left"></i> Vorige
                                    </a>
                                <?php endif; ?>
                                <?php if ($auditPage < $auditTotalPages): ?>
                                    <a href="admin.php?<?php echo $queryBase; ?>&audit_page=<?php echo (int)($auditPage + 1); ?>" class="btn btn-secondary btn-sm">
                                        Volgende <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
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

                            <div>
                                <label class="form-label">Titel</label>
                                <textarea name="title" rows="1" class="form-textarea"><?php echo htmlspecialchars($editPage['title'] ?? ''); ?></textarea>
                            </div>

                            <div>
                                <label class="form-label">Inhoud</label>
                                <textarea name="body" rows="6" class="form-textarea"><?php echo htmlspecialchars($editPage['body'] ?? ''); ?></textarea>
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

                            <div>
                                <label class="form-label">Titel</label>
                                <textarea name="title" rows="1" class="form-textarea"></textarea>
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
                        <?php if (empty($pageItems)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                                <p>Geen <?php echo $isProgrammaPage ? 'kaarten' : 'items'; ?> toegevoegd voor deze pagina</p>
                            </div>
                        <?php else: ?>
                            <div class="mb-4 flex gap-2 items-center">
                                <input type="checkbox" id="selectAllPages_<?php echo htmlspecialchars($pageKey); ?>" class="w-4 h-4 cursor-pointer selectAllPages" data-page="<?php echo htmlspecialchars($pageKey); ?>" title="Selecteer alles">
                                <label for="selectAllPages_<?php echo htmlspecialchars($pageKey); ?>" class="cursor-pointer text-sm font-medium">Selecteer alles</label>
                                <button type="button" class="btn btn-danger btn-sm admin-hidden" id="bulkDeletePagesBtn_<?php echo htmlspecialchars($pageKey); ?>">
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
                                ?>
                                <div class="border border-gray-200 rounded p-4 hover:shadow-md transition">
                                    <div class="admin-row flex justify-between items-start gap-4">
                                        <input type="checkbox" class="page-checkbox page-checkbox-<?php echo htmlspecialchars($pageKey); ?> w-4 h-4 mt-1 flex-shrink-0 cursor-pointer" name="page_ids[]" value="<?php echo (int)$it['id']; ?>">
                                        <div class="flex-1">
                                            <?php $pageBodyPreview = editorPreviewText($it['body'] ?? '', 150); ?>
                                            <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars(sanitizeEditorText($it['title'] ?? '')); ?></h4>
                                            <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?php echo nl2br(htmlspecialchars($pageBodyPreview)); ?></p>
                                        </div>
                                        <div class="admin-row-actions flex gap-2 flex-shrink-0">
                                            <?php if (!empty($it['image'])): ?>
                                                <a href="#" class="btn btn-secondary btn-sm" onclick="openImageModal(event, 'uploads/<?php echo htmlspecialchars($it['image']); ?>')">
                                                    <i class="fa-solid fa-image"></i> Foto
                                                </a>
                                            <?php endif; ?>
                                            <div class="admin-row-actions-stack flex flex-col gap-1">
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
                                                <form method="POST" onsubmit="return confirm('Verwijder dit item?');" class="admin-inline-form">
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
(function () {
  const wraps = document.querySelectorAll('.event-image-wrap');
  if (!wraps.length) return;

  let openWrap = null;

  function closePreview(wrap) {
    if (!wrap) return;
    wrap.classList.remove('is-open');
    const button = wrap.querySelector('.event-image-toggle');
    const preview = wrap.querySelector('.event-image-preview');
    if (button) button.setAttribute('aria-expanded', 'false');
    if (preview) preview.setAttribute('aria-hidden', 'true');
    if (openWrap === wrap) openWrap = null;
  }

  function openPreview(wrap) {
    if (!wrap) return;
    if (openWrap && openWrap !== wrap) {
      closePreview(openWrap);
    }
    wrap.classList.add('is-open');
    const button = wrap.querySelector('.event-image-toggle');
    const preview = wrap.querySelector('.event-image-preview');
    if (button) button.setAttribute('aria-expanded', 'true');
    if (preview) preview.setAttribute('aria-hidden', 'false');
    openWrap = wrap;
  }

  wraps.forEach(function (wrap) {
    const button = wrap.querySelector('.event-image-toggle');
    button?.addEventListener('click', function (e) {
      e.stopPropagation();
      if (wrap.classList.contains('is-open')) {
        closePreview(wrap);
      } else {
        openPreview(wrap);
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (openWrap && !openWrap.contains(e.target)) {
      closePreview(openWrap);
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && openWrap) {
      closePreview(openWrap);
    }
  });
})();

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
  const modal = document.getElementById('imageModal');
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

// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.querySelector('.sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
    const grid = document.querySelector('.admin-layout-grid');
    const sidebarLinks = document.querySelectorAll('.sidebar .sidebar-link');

  if (!sidebar || !toggleBtn || !grid) return;

    function setToggleExpanded(isExpanded) {
        toggleBtn.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
    }

  // Check if sidebar should be hidden on load (e.g., on mobile)
  function updateSidebarVisibility() {
    const isMobile = window.innerWidth < 1024;
    if (isMobile) {
      sidebar.classList.add('hidden');
      grid.classList.add('sidebar-hidden');
      toggleBtn.classList.remove('hidden');
            setToggleExpanded(false);
    } else {
      sidebar.classList.remove('hidden');
      grid.classList.remove('sidebar-hidden');
      toggleBtn.classList.add('hidden');
            setToggleExpanded(false);
    }
  }

  // Toggle sidebar
  toggleBtn.addEventListener('click', function() {
    sidebar.classList.toggle('hidden');
    grid.classList.toggle('sidebar-hidden');
        setToggleExpanded(!sidebar.classList.contains('hidden'));
  });

  // Also allow header click to toggle on mobile
  const sidebarHeader = document.querySelector('.sidebar-header');
  if (sidebarHeader) {
    sidebarHeader.addEventListener('click', function() {
      if (window.innerWidth < 1024) {
        sidebar.classList.toggle('hidden');
        grid.classList.toggle('sidebar-hidden');
                setToggleExpanded(!sidebar.classList.contains('hidden'));
      }
    });
  }

    // Close sidebar after choosing a destination on mobile.
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth < 1024) {
                sidebar.classList.add('hidden');
                grid.classList.add('sidebar-hidden');
                setToggleExpanded(false);
            }
    });
    });

  // Update on resize
  window.addEventListener('resize', updateSidebarVisibility);

  // Initial check
  updateSidebarVisibility();
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


