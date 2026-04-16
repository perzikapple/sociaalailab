<?php
session_start();
require 'db.php';
require 'helpers.php';

$sessionRole = trim((string)($_SESSION['role'] ?? ''));
if ($sessionRole === '') {
    $sessionRole = (isset($_SESSION['admin']) && (int)$_SESSION['admin'] === 1) ? 'superadmin' : 'viewer';
    $_SESSION['role'] = $sessionRole;
}
$canAccessAdmin = (bool)($_SESSION['can_access_admin'] ?? false);
if (!$canAccessAdmin) {
    $canAccessAdmin = (isset($_SESSION['admin']) && (int)$_SESSION['admin'] === 1)
        || in_array($sessionRole, ['superadmin', 'content_manager', 'editor'], true);
    $_SESSION['can_access_admin'] = $canAccessAdmin;
}

if (!$canAccessAdmin) {
    header('Location: login.php');
    exit;
}

$rolePermissions = [
    'superadmin' => ['manage_users', 'view_audit', 'manage_banners', 'manage_events', 'manage_pages', 'delete_events', 'delete_pages', 'optimize_images'],
    'content_manager' => ['manage_banners', 'manage_events', 'manage_pages', 'delete_events', 'delete_pages', 'optimize_images'],
    'editor' => ['manage_events'],
    'viewer' => [],
];

$hasPermission = function ($permission) use (&$sessionRole, &$rolePermissions) {
    $permissions = $rolePermissions[$sessionRole] ?? [];
    return in_array($permission, $permissions, true);
};

$message = '';
// Helper voor upload
function handleUpload($fileField) {
    if (empty($_FILES[$fileField]['name'])) return null;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($_FILES[$fileField]['type'], $allowed) || $_FILES[$fileField]['size'] > 50 * 1024 * 1024) {
        return ['error' => 'Ongeldig afbeeldingsbestand (jpg/png/gif/webp, max 50MB).'];
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

function handleMultiUpload($fileField) {
    if (empty($_FILES[$fileField]) || !isset($_FILES[$fileField]['name']) || !is_array($_FILES[$fileField]['name'])) {
        return ['names' => []];
    }

    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $saved = [];

    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0755, true);
    }

    $count = count($_FILES[$fileField]['name']);
    for ($i = 0; $i < $count; $i++) {
        $name = $_FILES[$fileField]['name'][$i] ?? '';
        if ($name === '') {
            continue;
        }

        $tmp = $_FILES[$fileField]['tmp_name'][$i] ?? '';
        $type = $_FILES[$fileField]['type'][$i] ?? '';
        $size = (int)($_FILES[$fileField]['size'][$i] ?? 0);
        $error = (int)($_FILES[$fileField]['error'][$i] ?? UPLOAD_ERR_OK);

        if ($error !== UPLOAD_ERR_OK) {
            foreach ($saved as $fileName) {
                $path = __DIR__ . '/uploads/' . $fileName;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            return ['error' => 'Een van de galerijfoto\'s kon niet worden geupload.'];
        }

        if (!in_array($type, $allowed, true) || $size > 50 * 1024 * 1024) {
            foreach ($saved as $fileName) {
                $path = __DIR__ . '/uploads/' . $fileName;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            return ['error' => 'Ongeldig galerijbestand (jpg/png/gif/webp, max 50MB per foto).'];
        }

        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = __DIR__ . '/uploads/' . $imageName;
        if (!move_uploaded_file($tmp, $dest)) {
            foreach ($saved as $fileName) {
                $path = __DIR__ . '/uploads/' . $fileName;
                if (file_exists($path)) {
                    @unlink($path);
                }
            }
            return ['error' => 'Kon een galerijafbeelding niet opslaan.'];
        }

        $saved[] = $imageName;
    }

    return ['names' => $saved];
}

// Image optimizer function
function optimizeImage($imagePath, $quality = 85) {
    if (!file_exists($imagePath)) {
        return ['error' => 'Afbeelding niet gevonden.'];
    }

    $originalSize = filesize($imagePath);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $imagePath);
    finfo_close($finfo);

    $ext = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

    // Check if GD library is available
    if (!extension_loaded('gd')) {
        return ['error' => 'GD-bibliotheek niet geïnstalleerd op server.', 'size_original' => $originalSize];
    }

    try {
        $image = null;

        // Load image based on type
        if (in_array($mimeType, ['image/jpeg', 'image/jpg'])) {
            $image = imagecreatefromjpeg($imagePath);
        } elseif ($mimeType === 'image/png') {
            $image = imagecreatefrompng($imagePath);
        } elseif ($mimeType === 'image/gif') {
            $image = imagecreatefromgif($imagePath);
        } elseif ($mimeType === 'image/webp') {
            $image = imagecreatefromwebp($imagePath);
        }

        if (!$image) {
            return ['error' => 'Kon afbeelding niet laden.', 'size_original' => $originalSize];
        }

        // Save optimized JPEG (best compatibility)
        $optimizedPath = str_replace('.' . $ext, '_optimized.jpg', $imagePath);
        imagejpeg($image, $optimizedPath, $quality);
        imagedestroy($image);

        $newSize = filesize($optimizedPath);
        $saved = $originalSize - $newSize;
        $savedPercent = $originalSize > 0 ? round(($saved / $originalSize) * 100, 1) : 0;

        // Replace original with optimized
        @unlink($imagePath);
        rename($optimizedPath, $imagePath);

        return [
            'success' => true,
            'size_original' => $originalSize,
            'size_optimized' => $newSize,
            'saved_bytes' => $saved,
            'saved_percent' => $savedPercent,
            'format' => 'JPEG'
        ];
    } catch (Exception $e) {
        return ['error' => 'Fout bij optimalisatie: ' . $e->getMessage(), 'size_original' => $originalSize];
    }
}

function decodeEventGallery($value) {
    if (!is_string($value) || $value === '') {
        return [];
    }

    $decoded = json_decode($value, true);
    if (!is_array($decoded)) {
        return [];
    }

    $items = [];
    foreach ($decoded as $fileName) {
        $fileName = trim((string)$fileName);
        if ($fileName !== '') {
            $items[] = $fileName;
        }
    }

    return $items;
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

    $actionPermissionMap = [
        'create' => 'manage_events',
        'update' => 'manage_events',
        'delete' => 'delete_events',
        'delete_bulk_events' => 'delete_events',
        'reorder_event' => 'manage_events',
        'delete_bulk_pages' => 'delete_pages',
        'update_banner' => 'manage_banners',
        'reset_banners' => 'manage_banners',
        'create_user' => 'manage_users',
        'update_user_role' => 'manage_users',
        'optimize_image' => 'optimize_images',
    ];

    if ($action !== '' && isset($actionPermissionMap[$action]) && !$hasPermission($actionPermissionMap[$action])) {
        $message = 'Je hebt geen rechten om deze actie uit te voeren.';
        $action = '';
    }

    if ($action === 'create') {
        $title = sanitizeEditorInlineInput($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $end_date = isset($_POST['add_end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        $time = $_POST['time'] ?? null;
        $time_end = isset($_POST['add_end_time']) && !empty($_POST['time_end']) ? $_POST['time_end'] : null;
        $description = sanitizeEditorBlockInput($_POST['description'] ?? '');
        $eventSummary = sanitizeEditorBlockInput($_POST['event_summary'] ?? '');
        $meerInfo = sanitizeEditorBlockInput($_POST['meer_info'] ?? '');
        $location = sanitizeEditorPlainText($_POST['location'] ?? '');
        $showSignupButton = isset($_POST['show_signup_button']) ? 1 : 0;
        $signupEmbed = trim((string)($_POST['signup_embed'] ?? ''));
        $showOnHomepage = isset($_POST['show_on_homepage']) ? 1 : 0;
        $infoLink = $_POST['info_link'] ?? '';

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
                $galleryUpload = handleMultiUpload('gallery_images');
                if (isset($galleryUpload['error'])) {
                    if (!empty($imageName) && file_exists(__DIR__ . '/uploads/' . $imageName)) {
                        @unlink(__DIR__ . '/uploads/' . $imageName);
                    }
                    $message = $galleryUpload['error'];
                } else {
                    $galleryJson = !empty($galleryUpload['names'])
                        ? json_encode(array_values($galleryUpload['names']), JSON_UNESCAPED_SLASHES)
                        : null;

                    // voeg updated_at en updated_by toe bij insert
                    $stmt = $pdo->prepare('INSERT INTO events (title, date, end_date, time, time_end, description, event_summary, meer_info, image, event_gallery, location, show_signup_button, signup_embed, show_on_homepage, info_link, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)');
                    $stmt->execute([$title, $date, $end_date, $time ?: null, $time_end ?: null, $description, $eventSummary ?: null, $meerInfo ?: null, $imageName, $galleryJson, $location ?: null, $showSignupButton, $signupEmbed ?: null, $showOnHomepage, $infoLink, $currentUser]);
                    $eventId = $pdo->lastInsertId();
                    // Audit log: event created
                    audit_log($pdo, 'create', 'events', $eventId, 'title: ' . $title, $currentUser);
                    header('Location: admin.php?page=agenda&ok=create');
                    exit;
                }
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
        $eventSummary = sanitizeEditorBlockInput($_POST['event_summary'] ?? '');
        $meerInfo = sanitizeEditorBlockInput($_POST['meer_info'] ?? '');
        $location = sanitizeEditorPlainText($_POST['location'] ?? '');
        $showSignupButton = isset($_POST['show_signup_button']) ? 1 : 0;
        $signupEmbed = trim((string)($_POST['signup_embed'] ?? ''));
        $showOnHomepage = isset($_POST['show_on_homepage']) ? 1 : 0;
        $infoLink = $_POST['info_link'] ?? '';
        $removeImage = isset($_POST['remove_image']) ? 1 : 0;

        if ($date === '') {
            $message = 'Datum is verplicht.';
        } else {
            if ($title === '') {
                $title = ' ';
            }
            // check existing image
            $stmt = $pdo->prepare('SELECT image, event_gallery FROM events WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $oldImage = $row ? $row['image'] : null;
            $oldGallery = decodeEventGallery($row['event_gallery'] ?? null);
            $removeGallery = $_POST['remove_gallery_images'] ?? [];
            if (!is_array($removeGallery)) {
                $removeGallery = [];
            }
            $removeGallery = array_values(array_intersect($oldGallery, array_map('strval', $removeGallery)));
            $keptGallery = array_values(array_diff($oldGallery, $removeGallery));

            $upload = handleUpload('image');
            if (isset($upload['error'])) {
                $message = $upload['error'];
            } else {
                $galleryUpload = handleMultiUpload('gallery_images');
                if (isset($galleryUpload['error'])) {
                    if (!empty($upload['name']) && file_exists(__DIR__ . '/uploads/' . $upload['name'])) {
                        @unlink(__DIR__ . '/uploads/' . $upload['name']);
                    }
                    $message = $galleryUpload['error'];
                } else {

                    if ($removeImage) {
                        $imageName = null;
                    } else {
                        $imageName = $upload['name'] ?? $oldImage;
                    }

                    $galleryNames = array_values(array_merge($keptGallery, $galleryUpload['names'] ?? []));
                    $galleryJson = !empty($galleryNames)
                        ? json_encode($galleryNames, JSON_UNESCAPED_SLASHES)
                        : null;

                    // update nu ook updated_at en updated_by
                    $stmt = $pdo->prepare('UPDATE events SET title=?, date=?, end_date=?, time=?, time_end=?, description=?, event_summary=?, meer_info=?, image=?, event_gallery=?, location=?, show_signup_button=?, signup_embed=?, show_on_homepage=?, info_link=?, updated_at=NOW(), updated_by=? WHERE id=?');
                    $stmt->execute([$title, $date, $end_date, $time ?: null, $time_end ?: null, $description, $eventSummary ?: null, $meerInfo ?: null, $imageName, $galleryJson, $location ?: null, $showSignupButton, $signupEmbed ?: null, $showOnHomepage, $infoLink, $currentUser, $id]);
                    // Audit log: event updated
                    audit_log($pdo, 'update', 'events', $id, 'title: ' . $title, $currentUser);

                    // indien nieuwe upload en oud bestaat: verwijderen
                    if (!empty($upload['name']) && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
                        @unlink(__DIR__ . '/uploads/' . $oldImage);
                    }
                    if ($removeImage && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
                        @unlink(__DIR__ . '/uploads/' . $oldImage);
                    }
                    foreach ($removeGallery as $galleryFile) {
                        $galleryPath = __DIR__ . '/uploads/' . $galleryFile;
                        if (file_exists($galleryPath)) {
                            @unlink($galleryPath);
                        }
                    }
                    header('Location: admin.php?page=agenda&ok=update');
                    exit;
                }
            }
        }

    } elseif ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        // haal image op en delete
        $stmt = $pdo->prepare('SELECT image, event_gallery FROM events WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            if ($row['image'] && file_exists(__DIR__ . '/uploads/' . $row['image'])) {
                @unlink(__DIR__ . '/uploads/' . $row['image']);
            }
            foreach (decodeEventGallery($row['event_gallery'] ?? null) as $galleryFile) {
                $galleryPath = __DIR__ . '/uploads/' . $galleryFile;
                if (file_exists($galleryPath)) {
                    @unlink($galleryPath);
                }
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
            $stmt = $pdo->prepare('SELECT image, event_gallery FROM events WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if ($row && $row['image'] && file_exists(__DIR__ . '/uploads/' . $row['image'])) {
                @unlink(__DIR__ . '/uploads/' . $row['image']);
            }
            if ($row) {
                foreach (decodeEventGallery($row['event_gallery'] ?? null) as $galleryFile) {
                    $galleryPath = __DIR__ . '/uploads/' . $galleryFile;
                    if (file_exists($galleryPath)) {
                        @unlink($galleryPath);
                    }
                }
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

    } elseif ($action === 'create_user') {
        $newEmail = trim((string)($_POST['new_email'] ?? ''));
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newFirstName = trim((string)($_POST['new_first_name'] ?? ''));
        $newLastName = trim((string)($_POST['new_last_name'] ?? ''));
        $newRole = trim((string)($_POST['new_role'] ?? 'viewer'));
        $allowedRoles = ['superadmin', 'content_manager', 'editor', 'viewer'];

        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'Vul een geldig e-mailadres in.';
        } elseif ($newPassword === '') {
            $message = 'Wachtwoord is verplicht.';
        } elseif (!in_array($newRole, $allowedRoles, true)) {
            $message = 'Ongeldige rol gekozen.';
        } else {
            $existsStmt = $pdo->prepare('SELECT id FROM accounts WHERE email = ?');
            $existsStmt->execute([$newEmail]);
            if ($existsStmt->fetch()) {
                $message = 'Er bestaat al een gebruiker met dit e-mailadres.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $adminFlag = in_array($newRole, ['superadmin', 'content_manager', 'editor'], true) ? 1 : 0;
                $insertStmt = $pdo->prepare('INSERT INTO accounts (email, wachtwoord, first_name, last_name, admin, role) VALUES (?, ?, ?, ?, ?, ?)');
                $insertStmt->execute([
                    $newEmail,
                    $hashedPassword,
                    $newFirstName !== '' ? $newFirstName : null,
                    $newLastName !== '' ? $newLastName : null,
                    $adminFlag,
                    $newRole,
                ]);

                audit_log($pdo, 'create', 'accounts', $newEmail, 'new user with role ' . $newRole, $currentUser);
                $message = 'Nieuwe gebruiker toegevoegd.';
            }
        }

    } elseif ($action === 'update_user_role') {
        $targetEmail = trim((string)($_POST['target_email'] ?? ''));
        $newRole = trim((string)($_POST['role'] ?? 'viewer'));
        $allowedRoles = ['superadmin', 'content_manager', 'editor', 'viewer'];

        if ($targetEmail === '' || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            $message = 'Ongeldig e-mailadres.';
        } elseif (!in_array($newRole, $allowedRoles, true)) {
            $message = 'Ongeldige rol gekozen.';
        } else {
            $adminFlag = in_array($newRole, ['superadmin', 'content_manager', 'editor'], true) ? 1 : 0;
            $stmt = $pdo->prepare('UPDATE accounts SET role = ?, admin = ? WHERE email = ?');
            $stmt->execute([$newRole, $adminFlag, $targetEmail]);

            if ($targetEmail === ($_SESSION['email'] ?? '')) {
                $_SESSION['role'] = $newRole;
                $_SESSION['admin'] = $adminFlag;
                $_SESSION['can_access_admin'] = ($adminFlag === 1) || in_array($newRole, ['superadmin', 'content_manager', 'editor'], true);
            }

            audit_log($pdo, 'update', 'accounts', $targetEmail, 'role set to ' . $newRole, $currentUser);
            $message = 'Gebruikersrol bijgewerkt.';
        }
    } elseif ($action === 'optimize_image') {
        $imagePath = trim((string)($_POST['image_path'] ?? ''));

        // Validate the image path to prevent directory traversal
        $uploadDir = __DIR__ . '/uploads/';
        $fullPath = realpath($uploadDir . $imagePath);

        if (!$fullPath || strpos($fullPath, realpath($uploadDir)) !== 0) {
            $message = 'Ongeldig pad voor afbeelding.';
        } else if (!file_exists($fullPath)) {
            $message = 'Afbeelding niet gevonden.';
        } else {
            $quality = intval($_POST['quality'] ?? 85);
            $quality = max(60, min(95, $quality)); // Limit between 60-95

            $result = optimizeImage($fullPath, $quality);

            if (isset($result['error'])) {
                $message = $result['error'];
            } else {
                $saved_mb = number_format($result['saved_bytes'] / 1024 / 1024, 2, '.', '');
                $message = sprintf(
                    'Afbeelding geoptimaliseerd! %s MB bespaard (%d%%).',
                    $saved_mb,
                    $result['saved_percent']
                );
                $_SESSION['optimize_success'] = true;
                $_SESSION['optimize_data'] = $result;
            }
        }
    }
}

// Handle page management (create_page, update_page, delete_page)
$pageAction = $_POST['page_action'] ?? '';
$pageActionPermissionMap = [
    'create_page' => 'manage_pages',
    'update_page' => 'manage_pages',
    'reorder_page' => 'manage_pages',
    'delete_page' => 'delete_pages',
];
if ($pageAction !== '' && isset($pageActionPermissionMap[$pageAction]) && !$hasPermission($pageActionPermissionMap[$pageAction])) {
    $message = 'Je hebt geen rechten om deze pagina-actie uit te voeren.';
    $pageAction = '';
}
if ($pageAction === 'create_page') {
    $pageKey = $_POST['page_key'] ?? '';
    $title = sanitizeEditorInlineInput($_POST['title'] ?? '');
    $body = sanitizeEditorBlockInput($_POST['body'] ?? '');
    $greenText = sanitizeEditorPlainText($_POST['green_text'] ?? '');
    $greenTextPosition = $_POST['green_text_position'] ?? 'above';
    if (!in_array($greenTextPosition, ['above', 'below'], true)) {
        $greenTextPosition = 'above';
    }
    $insertPosition = $_POST['insert_position'] ?? 'bottom';
    $imagePosition = $_POST['image_position'] ?? 'normal';
    if (!in_array($imagePosition, ['normal', 'left', 'right'], true)) {
        $imagePosition = 'normal';
    }

    // Optional URL used by cards/pages that need click-through behavior.
    $infoLink = $_POST['info_link'] ?? '';

    if ($title === '') {
        $title = ' ';
    }

    if (empty($pageKey)) {
        $message = 'Page key is verplicht.';
    } else {
        $layout = $_POST['layout'] ?? 'custom';
        if (!in_array($layout, ['welcome', 'card', 'info', 'contact', 'custom'], true)) {
            $layout = 'custom';
        }
        $meta = [
            'layout' => $layout,
            'image_position' => $imagePosition,
        ];
        if ($greenText !== '') {
            $meta['green_text'] = $greenText;
            $meta['green_text_position'] = $greenTextPosition;
        }

        if ($infoLink !== '') {
            $meta['info_link'] = $infoLink;
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
    $title = sanitizeEditorInlineInput($_POST['title'] ?? '');
    $body = sanitizeEditorBlockInput($_POST['body'] ?? '');
    $greenText = sanitizeEditorPlainText($_POST['green_text'] ?? '');
    $greenTextPosition = $_POST['green_text_position'] ?? 'above';
    if (!in_array($greenTextPosition, ['above', 'below'], true)) {
        $greenTextPosition = 'above';
    }
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

        $layout = $_POST['layout'] ?? 'custom';
        if (!in_array($layout, ['welcome', 'card', 'info', 'contact', 'custom'], true)) {
            $layout = 'custom';
        }
        $meta = [];
        if (!empty($row['meta'])) {
            $decodedMeta = json_decode($row['meta'], true);
            if (is_array($decodedMeta)) {
                $meta = $decodedMeta;
            }
        }
        $meta['layout'] = $layout;
        $meta['image_position'] = $imagePosition;
        if ($greenText !== '') {
            $meta['green_text'] = $greenText;
            $meta['green_text_position'] = $greenTextPosition;
        } else {
            unset($meta['green_text']);
            unset($meta['green_text_position']);
        }

        // Optional URL used by cards/pages that need click-through behavior.
        $infoLink = $_POST['info_link'] ?? '';
        if ($infoLink !== '') {
            $meta['info_link'] = $infoLink;
        } else {
            unset($meta['info_link']);
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
$editPageInfoLink = '';
if (!empty($_GET['edit_page'])) {
    $id = (int)$_GET['edit_page'];
    $stmt = $pdo->prepare('SELECT * FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    $editPage = $stmt->fetch();
    if ($editPage && $editPage['page_key'] === 'terugblikken') {
        $metaArr = $editPage['meta'] ? json_decode($editPage['meta'], true) : [];
        $editPageInfoLink = $metaArr['info_link'] ?? '';
    }
}

// Haal events voor overzicht (datum-gestuurd)
$stmt = $pdo->prepare('SELECT * FROM events ORDER BY date ASC, time ASC, created_at ASC, id ASC');
$stmt->execute();
$events = $stmt->fetchAll();
?>
<?php
// Welke admin pagina tonen (standaard index)
$page = $_GET['page'] ?? 'index';

$editorViewOnlyPages = [
    'banner',
    'index',
    'evenementen',
    'terugblikken',
    'over',
    'wie-zijn-we',
    'verantwoord-ai',
    'contact',
    'programma-kennis',
    'programma-actie',
    'programma-faciliteit',
];

$allowedPagesByPermission = [
    'banner' => 'manage_banners',
    'agenda' => 'manage_events',
    'audit' => 'view_audit',
    'users' => 'manage_users',
    'index' => 'manage_pages',
    'evenementen' => 'manage_pages',
    'terugblikken' => 'manage_pages',
    'over' => 'manage_pages',
    'wie-zijn-we' => 'manage_pages',
    'verantwoord-ai' => 'manage_pages',
    'contact' => 'manage_pages',
    'programma-kennis' => 'manage_pages',
    'programma-actie' => 'manage_pages',
    'programma-faciliteit' => 'manage_pages',
];

$canViewPage = function ($candidatePage) use (&$allowedPagesByPermission, &$hasPermission, &$sessionRole, &$editorViewOnlyPages) {
    if (!isset($allowedPagesByPermission[$candidatePage])) {
        return true;
    }

    if ($hasPermission($allowedPagesByPermission[$candidatePage])) {
        return true;
    }

    if ($sessionRole === 'editor' && in_array($candidatePage, $editorViewOnlyPages, true)) {
        return true;
    }

    return false;
};

if (!$canViewPage($page)) {
    $fallbackPage = null;
    foreach ($allowedPagesByPermission as $candidatePage => $candidatePermission) {
        if ($canViewPage($candidatePage)) {
            $fallbackPage = $candidatePage;
            break;
        }
    }

    if ($fallbackPage === null) {
        $message = 'Je account heeft momenteel geen toegewezen beheerrechten.';
    } else {
        $page = $fallbackPage;
        $message = 'Je hebt geen toegang tot de gevraagde beheersectie.';
    }
}

$canDeleteEvents = $hasPermission('delete_events');
$canDeletePages = $hasPermission('delete_pages');
$isEditorReadOnlyPage = ($sessionRole === 'editor' && $page !== 'agenda');

$pageItems = [];
if ($page !== 'banner' && $page !== 'agenda' && $page !== 'audit' && $page !== 'users') {
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
$userAccounts = [];

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

if ($page === 'users') {
    try {
        $stmt = $pdo->query('SELECT email, first_name, last_name, admin, role FROM accounts ORDER BY email ASC');
        $userAccounts = $stmt->fetchAll();
    } catch (Exception $e) {
        $message = 'Gebruikers konden niet worden geladen.';
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
<!-- TinyMCE toevoegen met API key -->
<script src="https://cdn.tiny.cloud/1/qnkn0kjik1i39qbzy3vn798sz5jjf0brz2sp43v420o1rnqx/tinymce/8/tinymce.min.js" referrerpolicy="origin" crossorigin="anonymous"></script>
<script>
  tinymce.init({
    selector: 'textarea',
    plugins: [
      // Core editing features
      'anchor', 'autolink', 'charmap', 'codesample', 'emoticons', 'link', 'lists', 'media', 'searchreplace', 'table', 'visualblocks', 'wordcount',
      // Your account includes a free trial of TinyMCE premium features
      // Try the most popular premium features until Apr 27, 2026:
      'checklist', 'mediaembed', 'casechange', 'formatpainter', 'pageembed', 'a11ychecker', 'tinymcespellchecker', 'permanentpen', 'powerpaste', 'advtable', 'advcode', 'advtemplate', 'tinymceai', 'uploadcare', 'mentions', 'tinycomments', 'tableofcontents', 'footnotes', 'mergetags', 'autocorrect', 'typography', 'inlinecss', 'markdown','importword', 'exportword', 'exportpdf'
    ],
    toolbar: 'undo redo | tinymceai-chat tinymceai-quickactions tinymceai-review | blocks fontfamily fontsize | bold italic underline strikethrough | link media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography uploadcare | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat',
    tinycomments_mode: 'embedded',
    tinycomments_author: 'Author name',
    mergetags_list: [
      { value: 'First.Name', title: 'First Name' },
      { value: 'Email', title: 'Email' },
    ],
    tinymceai_token_provider: async () => {
      await fetch(`https://demo.api.tiny.cloud/1/qnkn0kjik1i39qbzy3vn798sz5jjf0brz2sp43v420o1rnqx/auth/random`, { method: "POST", credentials: "include" });
      return { token: await fetch(`https://demo.api.tiny.cloud/1/qnkn0kjik1i39qbzy3vn798sz5jjf0brz2sp43v420o1rnqx/jwt/tinymceai`, { credentials: "include" }).then(r => r.text()) };
    },
    uploadcare_public_key: '7802a2584c2144503210',
  });
</script>
<style>
.btn-readonly-disabled {
    opacity: 0.55;
    cursor: not-allowed !important;
    pointer-events: none !important;
    filter: grayscale(0.2);
}
</style>
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
            <?php if ($hasPermission('view_audit')): ?>
                <a href="admin.php?page=audit" class="btn <?php echo $page==='audit' ? 'btn-primary' : 'btn-secondary'; ?> text-sm">
                    <i class="fa-solid fa-clipboard-list"></i> Auditlogboek
                </a>
            <?php endif; ?>
            <a href="booking.php" class="btn btn-secondary text-sm">
                <i class="fa-solid fa-calendar-check"></i> Booking
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
                <?php if ($hasPermission('manage_banners') || $sessionRole === 'editor'): ?>
                    <a href="admin.php?page=banner" class="sidebar-link <?php echo $page==='banner' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-image"></i> Banners
                    </a>
                <?php endif; ?>
                <?php if ($hasPermission('manage_events')): ?>
                    <a href="admin.php?page=agenda" class="sidebar-link <?php echo $page==='agenda' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-calendar"></i> Agenda
                    </a>
                <?php endif; ?>
                <?php if ($hasPermission('manage_users')): ?>
                    <a href="admin.php?page=users" class="sidebar-link <?php echo $page==='users' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-users"></i> Gebruikers & Rollen
                    </a>
                <?php endif; ?>
                <?php if ($hasPermission('optimize_images')): ?>
                    <a href="admin.php?page=image-optimizer" class="sidebar-link <?php echo $page==='image-optimizer' ? 'active' : ''; ?>">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Afbeelding Optimizer
                    </a>
                <?php endif; ?>
                
                <?php if ($hasPermission('manage_pages') || $sessionRole === 'editor'): ?>
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
                <?php endif; ?>
            </nav>
        </aside>

        <div class="lg:col-span-3 <?php echo $isEditorReadOnlyPage ? 'admin-readonly-scope' : ''; ?>">

            <?php if ($isEditorReadOnlyPage): ?>
                <div class="alert alert-error" style="background:#f3f4f6; color:#1f2937; border-left-color:#9ca3af;">
                    <i class="fa-solid fa-eye"></i> Alleen-lezen modus: is dit een fout? Neem contact op met een beheerder om je rechten te controleren.
                </div>
            <?php endif; ?>

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
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6 js-content-preview-form">
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
                                <label class="form-label">Meer info tekst (optioneel)</label>
                                <textarea name="meer_info" rows="5" class="form-textarea"><?php echo htmlspecialchars($editEvent['meer_info'] ?? ''); ?></textarea>
                                <p class="text-xs text-gray-500 mt-2">Deze tekst wordt op de evenement detailpagina getoond boven de samenvatting.</p>
                            </div>

                            <div>
                                <label class="form-label">Samenvatting na afloop (optioneel)</label>
                                <textarea name="event_summary" rows="5" class="form-textarea"><?php echo htmlspecialchars($editEvent['event_summary'] ?? ''); ?></textarea>
                                <p class="text-xs text-gray-500 mt-2">Deze samenvatting wordt op de evenement detailpagina getoond zodra deze is ingevuld.</p>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editEvent['image'] ?? ''); ?>" />
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
                                <label class="form-label">Foto's tijdens event (optioneel, meerdere bestanden)</label>
                                <input type="file" name="gallery_images[]" accept="image/*" multiple class="form-input" />
                                <?php $editGallery = decodeEventGallery($editEvent['event_gallery'] ?? null); ?>
                                <?php if (!empty($editGallery)): ?>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                                        <?php foreach ($editGallery as $galleryImage): ?>
                                            <label class="border border-gray-200 rounded p-2 block">
                                                <img src="uploads/<?php echo htmlspecialchars($galleryImage); ?>" alt="Eventfoto" class="w-full h-24 object-cover rounded">
                                                <span class="form-checkbox mt-2 inline-flex items-center gap-2">
                                                    <input type="checkbox" name="remove_gallery_images[]" value="<?php echo htmlspecialchars($galleryImage); ?>">
                                                    Verwijder
                                                </span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <label class="form-label" for="info_link">Meer info link (optioneel):</label>
                                <input
                                    type="url"
                                    name="info_link"
                                    id="info_link"
                                    class="form-input admin-input-surface"
                                    value="<?php echo htmlspecialchars($editEvent['info_link'] ?? ''); ?>"
                                    placeholder="https://voorbeeld.nl"
                                />
                                <?php if (!empty($editEvent['info_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($editEvent['info_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info bg-[#00811F] text-white px-3 py-1 rounded shadow hover:bg-[#00691A] transition mt-2 inline-block">
                                        <i class="fa-solid fa-circle-info mr-1"></i> Meer info
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="form-label" for="signup_embed">Aanmelder.nl link (optioneel):</label>
                                <input
                                    type="url"
                                    name="signup_embed"
                                    id="signup_embed"
                                    class="form-input admin-input-surface"
                                    value="<?php echo htmlspecialchars($editEvent['signup_embed'] ?? ''); ?>"
                                    placeholder="https://aanmelder.nl/subscribe/..."
                                />
                                <?php if (!empty($editEvent['signup_embed'])): ?>
                                    <a href="<?php echo htmlspecialchars($editEvent['signup_embed']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info bg-[#00811F] text-white px-3 py-1 rounded shadow hover:bg-[#00691A] transition mt-2 inline-block">
                                        <i class="fa-solid fa-arrow-up-right-from-square mr-1"></i> Aanmelden
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="show_signup_button" <?php echo $editEvent['show_signup_button'] ? 'checked' : ''; ?> />
                                    Toon inschrijf knop
                                </label>
                            </div>

                            <div>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="show_on_homepage" <?php echo ($editEvent['show_on_homepage'] ?? 0) ? 'checked' : ''; ?> />
                                    Toon op homepage
                                </label>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <button type="button" class="btn btn-secondary js-content-preview-btn">
                                    <i class="fa-solid fa-eye"></i> Preview
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Opslaan
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6 js-content-preview-form">
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
                            <div>
                                <label class="form-label">Plaats</label>
                                <input name="location" class="form-input admin-input-surface" />
                            </div>

                            <div>
                                <label class="form-label">Omschrijving</label>
                                <textarea name="description" rows="5" class="form-textarea"></textarea>
                            </div>

                            <div>
                                <label class="form-label">Meer info tekst (optioneel)</label>
                                <textarea name="meer_info" rows="5" class="form-textarea"><?php echo htmlspecialchars($_POST['meer_info'] ?? ''); ?></textarea>
                                <p class="text-xs text-gray-500 mt-2">Deze tekst wordt op de evenement detailpagina getoond boven de samenvatting.</p>
                            </div>

                            <div>
                                <label class="form-label">Samenvatting na afloop (optioneel)</label>
                                <textarea name="event_summary" rows="5" class="form-textarea"><?php echo htmlspecialchars($_POST['event_summary'] ?? ''); ?></textarea>
                                <p class="text-xs text-gray-500 mt-2">Deze samenvatting wordt op de evenement detailpagina getoond zodra deze is ingevuld.</p>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                            </div>

                            <div>
                                <label class="form-label">Foto's tijdens event (optioneel, meerdere bestanden)</label>
                                <input type="file" name="gallery_images[]" accept="image/*" multiple class="form-input" />
                            </div>
                            <div>
                                <label class="form-label" for="info_link">Meer info link (optioneel):</label>
                                <input
                                    type="url"
                                    name="info_link"
                                    id="info_link"
                                    class="form-input admin-input-surface"
                                    value=""
                                    placeholder="https://voorbeeld.nl"
                                />
                            </div>

                            <div>
                                <label class="form-label" for="signup_embed">Aanmelder.nl link (optioneel):</label>
                                <input
                                    type="url"
                                    name="signup_embed"
                                    id="signup_embed"
                                    class="form-input admin-input-surface"
                                    value=""
                                    placeholder="https://aanmelder.nl/subscribe/..."
                                />
                            </div>

                            <div>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="show_signup_button" checked />
                                    Toon inschrijf knop
                                </label>
                            </div>

                            <div>
                                <label class="form-checkbox">
                                    <input type="checkbox" name="show_on_homepage" checked />
                                    Toon op homepage
                                </label>
                            </div>

                            <div class="flex gap-2 pt-2">
                                <button type="button" class="btn btn-secondary js-content-preview-btn">
                                    <i class="fa-solid fa-eye"></i> Preview
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-plus"></i> Toevoegen
                                </button>
                            </div>
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
                            <?php if ($canDeleteEvents): ?>
                                <div class="mb-4 flex gap-2 items-center">
                                    <input type="checkbox" id="selectAllEvents" class="w-4 h-4 cursor-pointer" title="Selecteer alles">
                                    <label for="selectAllEvents" class="cursor-pointer text-sm font-medium">Selecteer alles</label>
                                    <button type="button" class="btn btn-danger btn-sm admin-hidden" id="bulkDeleteEventsBtn">
                                        <i class="fa-solid fa-trash"></i> Verwijder geselecteerde (<span id="eventCount">0</span>)
                                    </button>
                                </div>
                            <?php endif; ?>
                            <form method="POST" id="bulkDeleteEventsForm">
                                <input type="hidden" name="action" value="delete_bulk_events">
                            <div class="space-y-4">
                            <?php foreach ($events as $event): ?>
                                <div class="border border-gray-200 rounded p-4 hover:shadow-md transition">
                                    <div class="admin-row flex justify-between items-start gap-4">
                                        <?php if ($canDeleteEvents): ?>
                                            <input type="checkbox" class="event-checkbox w-4 h-4 mt-1 flex-shrink-0 cursor-pointer" name="event_ids[]" value="<?php echo (int)$event['id']; ?>">
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars(sanitizeEditorText($event['title'])); ?></h4>
                                            <?php
                                                $dateDisplay = formatEventDateDisplay($event['date']);
                                                $timeDisplay = $event['time'] ? formatEventTimeDisplay($event['time']) : '';
                                                $timeEndDisplay = $event['time_end'] ? formatEventTimeDisplay($event['time_end']) : '';
                                                $eventDescriptionPreview = editorPreviewText($event['description'] ?? '', 200);
                                                $eventGalleryCount = count(decodeEventGallery($event['event_gallery'] ?? null));
                                            ?>
                                            <p class="text-sm text-gray-600 mt-1"><strong>Wanneer:</strong> <?php echo htmlspecialchars($dateDisplay); ?><?php if ($timeDisplay) { echo ' ' . htmlspecialchars($timeDisplay); } ?><?php if ($timeEndDisplay) { echo ' - ' . htmlspecialchars($timeEndDisplay); } ?></p>
                                            <?php if (!empty($event['location'])): ?>
                                                <p class="text-sm text-gray-600"><strong>Plaats:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-600 mt-2 line-clamp-2"><?php echo nl2br(htmlspecialchars($eventDescriptionPreview)); ?></p>
                                            <?php if ($eventGalleryCount > 0): ?>
                                                <p class="text-sm text-gray-600 mt-1"><strong>Eventfoto's:</strong> <?php echo (int)$eventGalleryCount; ?></p>
                                            <?php endif; ?>
                                            <?php if (!empty($event['info_link'])): ?>
                                                <a href="<?php echo htmlspecialchars($event['info_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info bg-[#00811F] text-white px-3 py-1 rounded shadow hover:bg-[#00691A] transition mt-2 inline-block">
                                                    <i class="fa-solid fa-circle-info mr-1"></i> Meer info
                                                </a>
                                            <?php endif; ?>
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
                                                <?php if ($canDeleteEvents): ?>
                                                    <form method="POST" onsubmit="return confirm('Verwijder dit evenement?');" class="admin-inline-form">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int)$event['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm w-full">
                                                            <i class="fa-solid fa-trash"></i> Verwijder
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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

            <?php elseif ($page === 'users'): ?>
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-users text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Gebruikers & Rollen</h2>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">Wijs per gebruiker een rol toe. Rollen bepalen welke onderdelen van het adminpaneel zichtbaar en bewerkbaar zijn.</p>

                    <form method="POST" class="bg-white p-6 shadow-md space-y-4 mb-6 border border-gray-200 rounded">
                        <h3 class="font-semibold text-lg">Gebruiker toevoegen</h3>
                        <input type="hidden" name="action" value="create_user">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label" for="new_first_name">Voornaam</label>
                                <input id="new_first_name" type="text" name="new_first_name" class="form-input" maxlength="120" placeholder="Voornaam">
                            </div>
                            <div>
                                <label class="form-label" for="new_last_name">Achternaam</label>
                                <input id="new_last_name" type="text" name="new_last_name" class="form-input" maxlength="120" placeholder="Achternaam">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="form-label" for="new_email">E-mailadres</label>
                                <input id="new_email" type="email" name="new_email" class="form-input" required placeholder="naam@voorbeeld.nl">
                            </div>
                            <div>
                                <label class="form-label" for="new_role">Rol</label>
                                <select id="new_role" name="new_role" class="form-input">
                                    <option value="editor">Bewerker</option>
                                    <option value="content_manager">Content Manager</option>
                                    <option value="superadmin">Administrator</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="form-label" for="new_password">Wachtwoord</label>
                            <input id="new_password" type="text" name="new_password" class="form-input" required placeholder="Voer wachtwoord in">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-user-plus"></i> Gebruiker toevoegen
                        </button>
                    </form>

                    <?php if (empty($userAccounts)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                            <p>Geen gebruikers gevonden.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($userAccounts as $acc): ?>
                                <?php
                                    $accEmail = (string)($acc['email'] ?? '');
                                    $accFirstName = trim((string)($acc['first_name'] ?? ''));
                                    $accLastName = trim((string)($acc['last_name'] ?? ''));
                                    $accFullName = trim($accFirstName . ' ' . $accLastName);
                                    $accRole = trim((string)($acc['role'] ?? ''));
                                    if ($accRole === '') {
                                        $accRole = ((int)($acc['admin'] ?? 0) === 1) ? 'superadmin' : 'viewer';
                                    }
                                ?>
                                <form method="POST" class="border border-gray-200 rounded p-4 bg-white flex flex-col md:flex-row md:items-center gap-3 md:justify-between">
                                    <input type="hidden" name="action" value="update_user_role">
                                    <input type="hidden" name="target_email" value="<?php echo htmlspecialchars($accEmail); ?>">

                                    <div>
                                        <?php if ($accFullName !== ''): ?>
                                            <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($accFullName); ?></p>
                                        <?php endif; ?>
                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($accEmail); ?></p>
                                        <p class="text-xs text-gray-500">Huidige rol: <?php echo htmlspecialchars($accRole); ?></p>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <label class="text-sm text-gray-700" for="role_<?php echo md5($accEmail); ?>">Rol</label>
                                        <select id="role_<?php echo md5($accEmail); ?>" name="role" class="form-input">
                                            <option value="superadmin" <?php echo $accRole === 'superadmin' ? 'selected' : ''; ?>>Administrator</option>
                                            <option value="content_manager" <?php echo $accRole === 'content_manager' ? 'selected' : ''; ?>>Content Manager</option>
                                            <option value="editor" <?php echo $accRole === 'editor' ? 'selected' : ''; ?>>Bewerker</option>

                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-save"></i> Opslaan
                                        </button>
                                    </div>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($page === 'image-optimizer'): ?>
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-wand-magic-sparkles text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Afbeelding Optimizer</h2>
                    </div>
                    <p class="text-sm text-gray-600 mb-6">Optimaliseer afbeeldingen om de laadsnelheid van uw website te verbeteren. Afbeeldingen worden gecomprimeerd tot een smaller bestandsformaat.</p>

                    <div class="bg-white p-6 shadow-md border border-gray-200 rounded mb-6">
                        <h3 class="font-semibold text-lg mb-4">Afbeelding selecteren</h3>

                        <div class="mb-6">
                            <label class="form-label" for="image_select">Selecteer een afbeelding uit uploads</label>
                            <select id="image_select" name="image_path" class="form-input">
                                <option value="">-- Selecteer een afbeelding --</option>
                                <?php
                                    $uploadDir = __DIR__ . '/uploads/';
                                    if (is_dir($uploadDir)) {
                                        $files = array_diff(scandir($uploadDir), ['.', '..']);
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                        foreach ($files as $file) {
                                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                            if (in_array($ext, $imageExtensions)) {
                                                $fileSize = filesize($uploadDir . $file);
                                                $fileSizeMB = number_format($fileSize / 1024 / 1024, 2, '.', '');
                                                echo '<option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($file) . ' (' . $fileSizeMB . ' MB)</option>';
                                            }
                                        }
                                    }
                                ?>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="form-label" for="quality">Compressiekwaliteit</label>
                                <div class="flex items-center gap-3">
                                    <input type="range" id="quality" name="quality" min="60" max="95" value="85" class="flex-1">
                                    <span id="quality_value" class="text-sm font-semibold text-gray-700 w-12">85</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Lager = kleiner bestand, hogere compressie. Hoger = betere kwaliteit.</p>
                            </div>
                        </div>

                        <form method="POST" id="optimize_form" class="flex gap-3">
                            <input type="hidden" name="action" value="optimize_image">
                            <input type="hidden" name="image_path" id="form_image_path" value="">
                            <input type="hidden" name="quality" id="form_quality" value="85">
                            <button type="submit" id="optimize_btn" class="btn btn-primary" disabled>
                                <i class="fa-solid fa-wand-magic-sparkles"></i> Afbeelding optimaliseren
                            </button>
                        </form>
                    </div>

                    <div id="results_container" style="display: none;" class="bg-blue-50 border border-blue-200 rounded p-6">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-2xl text-blue-600 mt-1"></i>
                            <div>
                                <h4 class="font-semibold text-blue-900 mb-2">Optimalisatie voltooid!</h4>
                                <p id="results_text" class="text-sm text-blue-800"></p>
                            </div>
                        </div>
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
                $currentLayout = $editMeta['layout'] ?? 'custom';
                if (!in_array($currentLayout, ['welcome', 'card', 'info', 'contact', 'custom'], true)) {
                    $currentLayout = 'custom';
                }
                $currentImagePosition = $editMeta['image_position'] ?? 'normal';
                if (!in_array($currentImagePosition, ['normal', 'left', 'right'], true)) {
                    $currentImagePosition = 'normal';
                }
                $currentGreenText = $editMeta['green_text'] ?? ($editMeta['green_heading'] ?? '');
                $currentGreenTextPosition = $editMeta['green_text_position'] ?? 'above';
                if (!in_array($currentGreenTextPosition, ['above', 'below'], true)) {
                    $currentGreenTextPosition = 'above';
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
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6 js-content-preview-form">
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
                                <label class="form-label">Layout</label>
                                <select name="layout" class="form-input">
                                    <option value="custom" <?php echo $currentLayout === 'custom' ? 'selected' : ''; ?>>Custom (standaard)</option>
                                    <option value="card" <?php echo $currentLayout === 'card' ? 'selected' : ''; ?>>Kaart</option>
                                    <option value="welcome" <?php echo $currentLayout === 'welcome' ? 'selected' : ''; ?>>Welkom sectie</option>
                                    <option value="info" <?php echo $currentLayout === 'info' ? 'selected' : ''; ?>>Info blok</option>
                                    <option value="contact" <?php echo $currentLayout === 'contact' ? 'selected' : ''; ?>>Contact</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Groene tekst (optioneel)</label>
                                <textarea name="green_text" rows="2" class="form-textarea"><?php echo htmlspecialchars($currentGreenText); ?></textarea>
                            </div>

                            <div>
                                <label class="form-label">Positie groene tekst</label>
                                <select name="green_text_position" class="form-input">
                                    <option value="above" <?php echo $currentGreenTextPosition === 'above' ? 'selected' : ''; ?>>Boven het kopje</option>
                                    <option value="below" <?php echo $currentGreenTextPosition === 'below' ? 'selected' : ''; ?>>Onderaan</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($editPage['image'] ?? ''); ?>" />
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
                                <label class="form-label" for="info_link">Meer info link (optioneel):</label>
                                <input
                                    type="url"
                                    name="info_link"
                                    id="info_link"
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($editMeta['info_link'] ?? ''); ?>"
                                    placeholder="https://voorbeeld.nl"
                                />
                            </div>

                            <div>
                                <label class="form-label">Foto positie</label>
                                <select name="image_position" class="form-input">
                                    <option value="normal" <?php echo $currentImagePosition === 'normal' ? 'selected' : ''; ?>>Normaal (foto onder)</option>
                                    <option value="left" <?php echo $currentImagePosition === 'left' ? 'selected' : ''; ?>>Foto links</option>
                                    <option value="right" <?php echo $currentImagePosition === 'right' ? 'selected' : ''; ?>>Foto rechts</option>
                                </select>
                            </div>



                            <div class="flex gap-2 pt-2">
                                <button type="button" class="btn btn-secondary js-content-preview-btn">
                                    <i class="fa-solid fa-eye"></i> Preview
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-save"></i> Opslaan
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4 mb-6 js-content-preview-form">
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
                                <label class="form-label">Layout</label>
                                <select name="layout" class="form-input">
                                    <option value="custom" selected>Custom (standaard)</option>
                                    <option value="card">Kaart</option>
                                    <option value="welcome">Welkom sectie</option>
                                    <option value="info">Info blok</option>
                                    <option value="contact">Contact</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Groene tekst (optioneel)</label>
                                <textarea name="green_text" rows="2" class="form-textarea"></textarea>
                            </div>

                            <div>
                                <label class="form-label">Positie groene tekst</label>
                                <select name="green_text_position" class="form-input">
                                    <option value="above" selected>Boven het kopje</option>
                                    <option value="below">Onderaan</option>
                                </select>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                            </div>


                            <div>
                                <label class="form-label" for="info_link">Meer info link (optioneel):</label>
                                <input
                                    type="url"
                                    name="info_link"
                                    id="info_link"
                                    class="form-input"
                                    value="<?php echo htmlspecialchars($_POST['info_link'] ?? ''); ?>"
                                    placeholder="https://voorbeeld.nl"
                                />
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

                            <div class="flex gap-2 pt-2">
                                <button type="button" class="btn btn-secondary js-content-preview-btn">
                                    <i class="fa-solid fa-eye"></i> Preview
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-plus"></i> <?php echo $isProgrammaPage ? 'Kaart toevoegen' : 'Toevoegen'; ?>
                                </button>
                            </div>
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
                            <?php if ($canDeletePages): ?>
                                <div class="mb-4 flex gap-2 items-center">
                                    <input type="checkbox" id="selectAllPages_<?php echo htmlspecialchars($pageKey); ?>" class="w-4 h-4 cursor-pointer selectAllPages" data-page="<?php echo htmlspecialchars($pageKey); ?>" title="Selecteer alles">
                                    <label for="selectAllPages_<?php echo htmlspecialchars($pageKey); ?>" class="cursor-pointer text-sm font-medium">Selecteer alles</label>
                                    <button type="button" class="btn btn-danger btn-sm admin-hidden" id="bulkDeletePagesBtn_<?php echo htmlspecialchars($pageKey); ?>">
                                        <i class="fa-solid fa-trash"></i> Verwijder geselecteerde (<span id="pageCount_<?php echo htmlspecialchars($pageKey); ?>">0</span>)
                                    </button>
                                </div>
                            <?php endif; ?>
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
                                        <?php if ($canDeletePages): ?>
                                            <input type="checkbox" class="page-checkbox page-checkbox-<?php echo htmlspecialchars($pageKey); ?> w-4 h-4 mt-1 flex-shrink-0 cursor-pointer" name="page_ids[]" value="<?php echo (int)$it['id']; ?>">
                                        <?php endif; ?>
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
                                                <?php if ($canDeletePages): ?>
                                                    <form method="POST" onsubmit="return confirm('Verwijder dit item?');" class="admin-inline-form">
                                                        <input type="hidden" name="page_action" value="delete_page">
                                                        <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">
                                                        <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm w-full">
                                                            <i class="fa-solid fa-trash"></i> Verwijder
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
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
    // Tijdelijke test: einddatum/eindtijd tonen/verbergen
    function toggleField(checkboxId, containerId) {
        var checkbox = document.getElementById(checkboxId);
        var container = document.getElementById(containerId);
        if (!checkbox || !container) return;
        function update() {
            if (checkbox.checked) {
                container.classList.remove('admin-hidden');
            } else {
                container.classList.add('admin-hidden');
            }
        }
        checkbox.addEventListener('change', update);
        update();
    }
    toggleField('add-end-date-edit', 'end-date-container-edit');
    toggleField('add-end-time-edit', 'end-time-container-edit');
    toggleField('add-end-date-create', 'end-date-container-create');
    toggleField('add-end-time-create', 'end-time-container-create');
    </script>

<div id="content-preview-modal" class="hidden" style="position: fixed; inset: 0; z-index: 9999; background: rgba(17,24,39,.6); padding: 1rem;">
    <div style="max-width: 860px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 50px rgba(0,0,0,.25); max-height: calc(100vh - 2rem); display: flex; flex-direction: column;">
        <div style="display:flex; align-items:center; justify-content:space-between; padding: .9rem 1rem; border-bottom: 1px solid #e5e7eb;">
            <h3 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin: 0;">Preview zoals op de website</h3>
            <button type="button" id="content-preview-close" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-xmark"></i> Sluiten
            </button>
        </div>
        <div style="padding: 1rem; overflow: auto; background:#f8fafc;">
            <div id="preview-rendered"></div>
            <p id="preview-empty" class="hidden" style="color: #6b7280; margin-top: .75rem;">Nog geen inhoud om te tonen.</p>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('content-preview-modal');
    const closeBtn = document.getElementById('content-preview-close');
    const renderedEl = document.getElementById('preview-rendered');
    const emptyEl = document.getElementById('preview-empty');
    const previewButtons = document.querySelectorAll('.js-content-preview-btn');
    let currentImageUrl = null;

    if (!modal || !closeBtn || !previewButtons.length) return;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function getEditorContentByName(fieldName) {
        if (!window.tinymce || !Array.isArray(window.tinymce.editors)) {
            return null;
        }

        for (const editor of window.tinymce.editors) {
            if (editor && editor.targetElm && editor.targetElm.name === fieldName) {
                return editor.getContent();
            }
        }
        return null;
    }

    function getFieldInput(form, fieldName) {
        return form.querySelector('[name="' + fieldName + '"]');
    }

    function getFieldValue(form, fieldName) {
        const input = getFieldInput(form, fieldName);
        if (!input) return '';

        if (input.tagName === 'TEXTAREA') {
            if (window.tinymce && Array.isArray(window.tinymce.editors)) {
                for (const editor of window.tinymce.editors) {
                    if (!editor) continue;
                    const sameElement = editor.targetElm === input;
                    const sameName = editor.targetElm && editor.targetElm.name === input.name;
                    const sameId = editor.id && input.id && editor.id === input.id;
                    if (sameElement || sameName || sameId) {
                        return editor.getContent();
                    }
                }
            }
            return input.value || '';
        }

        return input.value || '';
    }

    function clearImagePreview() {
        if (currentImageUrl) {
            URL.revokeObjectURL(currentImageUrl);
            currentImageUrl = null;
        }
    }

    function resolveImageSrc(form) {
        const imageInput = getFieldInput(form, 'image');
        if (imageInput && imageInput.files && imageInput.files.length) {
            clearImagePreview();
            currentImageUrl = URL.createObjectURL(imageInput.files[0]);
            return currentImageUrl;
        }

        const removeImage = getFieldInput(form, 'remove_image');
        if (removeImage && removeImage.checked) {
            return '';
        }

        const existingImage = getFieldValue(form, 'existing_image').trim();
        if (existingImage) {
            return 'uploads/' + existingImage;
        }

        return '';
    }

    function isEventForm(form) {
        return !!getFieldInput(form, 'description') && !!getFieldInput(form, 'date');
    }

    function formatDateDisplay(dateValue) {
        if (!dateValue) return '';
        const parsed = new Date(dateValue + 'T00:00:00');
        if (Number.isNaN(parsed.getTime())) return dateValue;
        return parsed.toLocaleDateString('nl-NL', { day: 'numeric', month: 'long', year: 'numeric' });
    }

    function formatTimeDisplay(timeValue) {
        if (!timeValue) return '';
        const normalized = String(timeValue).slice(0, 5);
        return normalized;
    }

    function renderEventPreview(form) {
        const titleHtml = getFieldValue(form, 'title').trim() || 'Preview titel';
        const descriptionHtml = getFieldValue(form, 'description').trim() || '';
        const startDate = getFieldValue(form, 'date').trim();
        const endDate = getFieldValue(form, 'end_date').trim();
        const startTime = formatTimeDisplay(getFieldValue(form, 'time').trim());
        const endTime = formatTimeDisplay(getFieldValue(form, 'time_end').trim());
        const location = getFieldValue(form, 'location').trim() || 'Rotterdam - Hillevliet 90';
        const infoLink = getFieldValue(form, 'info_link').trim();
        const signupEmbed = getFieldValue(form, 'signup_embed').trim();
        const imageSrc = resolveImageSrc(form);
        const mapUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(location);
        const hasEmbed = signupEmbed !== '';
        const hasSignup = !!(getFieldInput(form, 'show_signup_button') && getFieldInput(form, 'show_signup_button').checked);
        const dateDisplay = formatDateDisplay(startDate);
        const endDateDisplay = formatDateDisplay(endDate);

        renderedEl.innerHTML = '' +
            '<section class="flex flex-col md:flex-row items-center gap-10 bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">' +
                '<div class="flex-1">' +
                    '<span class="inline-block text-white text-sm font-medium px-4 py-1 mb-4" style="background-color:#ce0245;">Evenement</span>' +
                    '<h2 class="text-2xl md:text-3xl font-semibold mb-4 text-gray-900">' + titleHtml + '</h2>' +
                    '<div class="space-y-4">' +
                        '<div class="flex items-center space-x-3">' +
                            '<i class="fa-regular fa-calendar text-[#00811F] ml-[2px] text-3xl"></i>' +
                            '<p class="text-gray-700"><strong> Wanneer:</strong> ' + escapeHtml(dateDisplay || startDate) + (endDateDisplay ? ' t/m ' + escapeHtml(endDateDisplay) : '') + '</p>' +
                        '</div>' +
                        ((startTime || endTime) ?
                        '<div class="flex items-center space-x-3">' +
                            '<i class="fa-solid fa-clock text-[#00811F] ml-[2px] text-3xl"></i>' +
                            '<p class="text-gray-700"><strong>Hoelaat:</strong> ' + escapeHtml(startTime) + (endTime ? ' - ' + escapeHtml(endTime) : '') + '</p>' +
                        </div> : '') +
                        '<div class="flex items-center space-x-3">' +
                            '<i class="fa-solid fa-location-dot text-[#00811F] ml-1 text-3xl"></i>' +
                            '<p class="text-gray-700 ml-1"><strong>Waar:</strong> <a href="' + escapeHtml(mapUrl) + '" target="_blank" rel="noopener noreferrer" class="underline hover:text-[#00811F]">' + escapeHtml(location) + '</a></p>' +
                        '</div>' +
                        '<div class="flex mb-6 space-x-3">' +
                            '<i class="fa-solid fa-bullseye text-[#00811F] text-3xl"></i>' +
                            '<div class="text-gray-700 pb-3"><strong> Wat:</strong><div class="mt-1">' + descriptionHtml + '</div></div>' +
                        </div>' +
                    </div>' +
                    (hasEmbed ? '<div class="mt-4 rounded-md border border-dashed border-gray-300 bg-gray-50 p-4 text-sm text-gray-600">Aanmelder.nl embed wordt na opslaan getoond.</div>' : (hasSignup ? '<a href="#" onclick="return false;" class="mt-4 inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">Inschrijven</a>' : '')) +
                    (infoLink ? '<a href="' + escapeHtml(infoLink) + '" target="_blank" rel="noopener noreferrer" class="mt-4 ml-4 inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">Meer info</a>' : '') +
                '</div>' +
                (imageSrc ? '<div class="flex-1"><img src="' + escapeHtml(imageSrc) + '" alt="" class="w-full h-auto object-cover shadow-md"></div>' : '') +
            '</section>';
    }

    function renderPagePreview(form) {
        const titleHtml = getFieldValue(form, 'title').trim() || 'Preview titel';
        const bodyHtml = getFieldValue(form, 'body').trim() || '';
        const greenText = getFieldValue(form, 'green_text').trim();
        const greenTextPos = (getFieldValue(form, 'green_text_position').trim() || 'above');
        const imagePosition = (getFieldValue(form, 'image_position').trim() || 'normal');
        const infoLink = getFieldValue(form, 'info_link').trim();
        const imageSrc = resolveImageSrc(form);
        const hasImage = !!imageSrc;
        const hasText = !!(titleHtml || bodyHtml);
        const sideBySide = hasImage && hasText && imagePosition !== 'normal';
        const textStyle = sideBySide ? 'flex: 1; padding: 0 1.5rem;' : '';

        const leftImage = (imagePosition === 'left' && hasText)
            ? '<div style="flex: 0 0 50%; min-width: 0; max-width: 600px;"><img src="' + escapeHtml(imageSrc) + '" alt="" class="w-full h-auto object-cover shadow-md"></div>' : '';
        const rightImage = (imagePosition === 'right' && hasText)
            ? '<div style="flex: 0 0 50%; min-width: 0; max-width: 600px;"><img src="' + escapeHtml(imageSrc) + '" alt="" class="w-full h-auto object-cover shadow-md"></div>' : '';
        const imageOnly = (hasImage && !hasText)
            ? '<div style="width: 100%;"><img src="' + escapeHtml(imageSrc) + '" alt="" class="w-full h-auto object-cover shadow-md"></div>' : '';
        const belowImage = (hasImage && imagePosition === 'normal' && hasText)
            ? '<div class="mt-6" style="max-width: 600px;"><img src="' + escapeHtml(imageSrc) + '" alt="" class="w-full h-auto object-cover shadow-md"></div>' : '';

        renderedEl.innerHTML = '' +
            '<section class="bg-white shadow-lg p-8 max-w-6xl mx-auto my-12">' +
                '<div style="' + (sideBySide ? ('display: flex; flex-direction: ' + (imagePosition === 'left' ? 'row' : 'row-reverse') + '; align-items: flex-start; gap: 2rem;') : '') + '">' +
                    leftImage +
                    imageOnly +
                    (hasText ?
                        '<div style="' + textStyle + '">' +
                            (greenText && greenTextPos === 'above' ? '<div class="green-highlight mb-3">' + escapeHtml(greenText).replace(/\n/g, '<br>') + '</div>' : '') +
                            '<h1 class="text-3xl font-bold text-gray-900 mb-4">' + titleHtml + '</h1>' +
                            '<div class="text-gray-700 text-lg">' + bodyHtml + '</div>' +
                            (greenText && greenTextPos === 'below' ? '<div class="green-highlight mb-3">' + escapeHtml(greenText).replace(/\n/g, '<br>') + '</div>' : '') +
                            (infoLink ? '<a href="' + escapeHtml(infoLink) + '" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex items-center bg-[#00811F] text-white font-semibold px-6 py-3 rounded-md shadow hover:bg-[#006f19] transition">Meer info</a>' : '') +
                        '</div>'
                        : '') +
                    rightImage +
                '</div>' +
                belowImage +
            '</section>';
    }

    function renderPreview(form) {
        if (!renderedEl) return;

        if (isEventForm(form)) {
            renderEventPreview(form);
            return;
        }
        renderPagePreview(form);
    }

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        clearImagePreview();
        if (renderedEl) renderedEl.innerHTML = '';
    }

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', function (event) {
        if (event.target === modal) closeModal();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });

    previewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const form = button.closest('form.js-content-preview-form');
            if (!form) return;

            // Sync TinyMCE editors back to their textarea values before reading fields.
            if (window.tinymce && typeof window.tinymce.triggerSave === 'function') {
                window.tinymce.triggerSave();
            }

            renderPreview(form);

            if (renderedEl && renderedEl.textContent.trim() !== '') {
                emptyEl.classList.add('hidden');
            } else {
                emptyEl.classList.remove('hidden');
            }

            openModal();
        });
    });
})();

(function () {
    const isReadOnly = <?php echo $isEditorReadOnlyPage ? 'true' : 'false'; ?>;
    if (!isReadOnly) return;

    const scope = document.querySelector('.admin-readonly-scope');
    if (!scope) return;

    const controls = scope.querySelectorAll('button, input, select, textarea');
    controls.forEach(function (el) {
        if (el.tagName === 'INPUT' && (el.type || '').toLowerCase() === 'hidden') return;
        el.disabled = true;
        if (el.tagName === 'BUTTON') {
            el.classList.add('btn-readonly-disabled');
        }
    });

    const actionLinks = scope.querySelectorAll('a.btn, a.sidebar-link');
    actionLinks.forEach(function (link) {
        link.classList.add('btn-readonly-disabled');
        link.setAttribute('aria-disabled', 'true');
        link.addEventListener('click', function (event) {
            event.preventDefault();
        });
    });
})();

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

// Image Optimizer Functionality
document.addEventListener('DOMContentLoaded', function() {
    const imageSelect = document.getElementById('image_select');
    const qualitySlider = document.getElementById('quality');
    const qualityValue = document.getElementById('quality_value');
    const optimizeBtn = document.getElementById('optimize_btn');
    const optimizeForm = document.getElementById('optimize_form');
    const resultsContainer = document.getElementById('results_container');
    const resultsText = document.getElementById('results_text');
    const formImagePath = document.getElementById('form_image_path');
    const formQuality = document.getElementById('form_quality');

    if (!imageSelect || !qualitySlider || !optimizeBtn) return;

    // Update quality display
    qualitySlider.addEventListener('input', function() {
        qualityValue.textContent = this.value;
        formQuality.value = this.value;
    });

    // Enable/disable optimize button based on image selection
    imageSelect.addEventListener('change', function() {
        optimizeBtn.disabled = !this.value;
        formImagePath.value = this.value;
    });

    // Handle form submission
    optimizeForm.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!imageSelect.value) {
            showToast('Selecteer een afbeelding om te optimaliseren.', 'error');
            return;
        }

        optimizeBtn.disabled = true;
        optimizeBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Bezig...';

        fetch('admin.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(response => response.text())
        .then(data => {
            optimizeBtn.disabled = false;
            optimizeBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Afbeelding optimaliseren';

            // Check if optimization was successful
            if (data.includes('alert-success') || data.includes('Afbeelding geoptimaliseerd')) {
                // Extract success message
                const match = data.match(/Afbeelding geoptimaliseerd[^<]*/);
                if (match) {
                    const message = match[0];
                    showToast(message, 'success');
                    resultsText.textContent = message;
                    resultsContainer.style.display = 'block';

                    // Reset form after 2 seconds
                    setTimeout(() => {
                        imageSelect.value = '';
                        optimizeBtn.disabled = true;
                        resultsContainer.style.display = 'none';
                    }, 3000);
                }
            } else {
                // Check for error
                const errorMatch = data.match(/<div[^>]*class="alert alert-error"[^>]*>[\s\S]*?<\/div>/);
                if (errorMatch) {
                    const errorDiv = document.createElement('div');
                    errorDiv.innerHTML = errorMatch[0];
                    const errorText = errorDiv.textContent.trim();
                    showToast(errorText, 'error');
                } else {
                    showToast('Onbekende fout bij optimalisatie.', 'error');
                }
            }
        })
        .catch(error => {
            optimizeBtn.disabled = false;
            optimizeBtn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Afbeelding optimaliseren';
            showToast('Fout bij communicatie met server: ' + error, 'error');
        });
    });

    // Check if optimization was successful after page reload
    <?php if (!empty($_SESSION['optimize_success'])): ?>
        const optimizeData = <?php echo json_encode($_SESSION['optimize_data'] ?? []); ?>;
        if (optimizeData && optimizeData.saved_percent) {
            const message = 'Afbeelding geoptimaliseerd! ' +
                (optimizeData.saved_bytes / 1024 / 1024).toFixed(2) + ' MB bespaard (' +
                optimizeData.saved_percent + '%).';
            showToast(message, 'success');
            resultsText.textContent = message;
            resultsContainer.style.display = 'block';
        }
        <?php unset($_SESSION['optimize_success'], $_SESSION['optimize_data']); ?>
    <?php endif; ?>
});

// Toast Notification Function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        max-width: 400px;
        font-size: 14px;
        animation: slideIn 0.3s ease-out;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Add CSS animations for toast
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

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
<script>
    document.addEventListener("DOMContentLoaded", function () {

        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.querySelector('#sidebarToggle');

        if (!sidebar || !toggleBtn) return;

        // Toggle sidebar
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('open');
        });

        // Klik buiten = sluiten
        document.addEventListener('click', function (e) {
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });

        // klik binnen sidebar = niet sluiten
        sidebar.addEventListener('click', function (e) {
            e.stopPropagation();
        });

    });
</script>
</body>
</html>


