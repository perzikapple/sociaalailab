<?php
session_start();
require 'db.php';
if (!isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header('Location: login.php');
    exit;
}

$message = '';
// Helper voor upload
function handleUpload($fileField) {
    if (empty($_FILES[$fileField]['name'])) return null;
    $allowed = ['image/jpeg','image/png','image/gif'];
    if (!in_array($_FILES[$fileField]['type'], $allowed) || $_FILES[$fileField]['size'] > 2 * 1024 * 1024) {
        return ['error' => 'Ongeldig afbeeldingsbestand (jpg/png/gif, max 2MB).'];
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

// Fetch current banners
$banner1 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner1'")->fetchColumn() ?: 'images/banner_website_01.jpg';
$banner2 = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'banner2'")->fetchColumn() ?: 'images/banner_website_02.jpg';

// Verwerk POST acties
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $currentUser = $_SESSION['user'] ?? null; // email van ingelogde gebruiker (kan null zijn)
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        if ($title === '' || $date === '') {
            $message = 'Titel en datum zijn verplicht.';
        } else {
            $upload = handleUpload('image');
            if (isset($upload['error'])) {
                $message = $upload['error'];
            } else {
                $imageName = $upload['name'] ?? null;
                // voeg updated_at en updated_by toe bij insert
                $stmt = $pdo->prepare('INSERT INTO events (title, date, time, description, image, location, updated_at, updated_by) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)');
                $stmt->execute([$title, $date, $time ?: null, $description, $imageName, $location ?: null, $currentUser]);
                $eventId = $pdo->lastInsertId();
                // Audit log: event created
                audit_log($pdo, 'create', 'events', $eventId, 'title: ' . $title, $currentUser);
                header('Location: admin.php?ok=create');
                exit;
            }
        }
    } elseif ($action === 'update' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? null;
        $description = trim($_POST['description'] ?? '');
        $location = trim($_POST['location'] ?? '');
        if ($title === '' || $date === '') {
            $message = 'Titel en datum zijn verplicht.';
        } else {
            // check existing image
            $stmt = $pdo->prepare('SELECT image FROM events WHERE id = ?');
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            $oldImage = $row ? $row['image'] : null;

            $upload = handleUpload('image');
            if (isset($upload['error'])) {
                $message = $upload['error'];
            } else {
                $imageName = $upload['name'] ?? $oldImage;
                // update nu ook updated_at en updated_by
                $stmt = $pdo->prepare('UPDATE events SET title=?, date=?, time=?, description=?, image=?, location=?, updated_at=NOW(), updated_by=? WHERE id=?');
                $stmt->execute([$title, $date, $time ?: null, $description, $imageName, $location ?: null, $currentUser, $id]);
                // Audit log: event updated
                audit_log($pdo, 'update', 'events', $id, 'title: ' . $title, $currentUser);
                // indien nieuwe upload en oud bestaat: verwijderen
                if (!empty($upload['name']) && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
                    @unlink(__DIR__ . '/uploads/' . $oldImage);
                }
                header('Location: admin.php?ok=update');
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
            header('Location: admin.php?ok=delete');
            exit;
        } else {
            $message = 'Evenement niet gevonden.';
        }
    } elseif ($action === 'update_banner') {
        $banner1_file = handleUpload('banner1');
        if ($banner1_file) {
            $path = 'uploads/' . $banner1_file['name'];
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner1'")->execute([$path]);
            audit_log($pdo, 'update', 'settings', null, 'banner1 set to ' . $path, $currentUser);
        }
        $banner2_file = handleUpload('banner2');
        if ($banner2_file) {
            $path = 'uploads/' . $banner2_file['name'];
            $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'banner2'")->execute([$path]);
            audit_log($pdo, 'update', 'settings', null, 'banner2 set to ' . $path, $currentUser);
        }
        $message = 'Banners bijgewerkt.';
    }
}

// Handle page management (create_page, update_page, delete_page)
$pageAction = $_POST['page_action'] ?? '';
if ($pageAction === 'create_page') {
    $pageKey = $_POST['page_key'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    
    if (empty($pageKey) || empty($title)) {
        $message = 'Page key en titel zijn verplicht.';
    } else {
        $meta = [];
        if (!empty($_POST['date'])) $meta['date'] = $_POST['date'];
        if (!empty($_POST['time'])) $meta['time'] = $_POST['time'];
        if (!empty($_POST['address'])) $meta['address'] = $_POST['address'];
        if (!empty($_POST['email'])) $meta['email'] = $_POST['email'];
        if (!empty($_POST['map_embed'])) $meta['map_embed'] = $_POST['map_embed'];
        if (!empty($_POST['partners'])) $meta['partners'] = $_POST['partners'];
        
        $upload = handleUpload('page_image');
        $imageName = $upload['name'] ?? null;
        
        $stmt = $pdo->prepare('INSERT INTO pages (page_key, title, body, image, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$pageKey, $title, $body, $imageName, json_encode($meta)]);
        audit_log($pdo, 'create', 'pages', $pdo->lastInsertId(), 'page_key: ' . $pageKey, $currentUser);
        $message = 'Pagina item aangemaakt.';
    }
} elseif ($pageAction === 'update_page') {
    $id = (int)$_POST['id'] ?? 0;
    $pageKey = $_POST['page_key'] ?? '';
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    
    if ($id && $title) {
        $stmt = $pdo->prepare('SELECT image FROM pages WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $oldImage = $row ? $row['image'] : null;
        
        $meta = [];
        if (!empty($_POST['date'])) $meta['date'] = $_POST['date'];
        if (!empty($_POST['time'])) $meta['time'] = $_POST['time'];
        if (!empty($_POST['address'])) $meta['address'] = $_POST['address'];
        if (!empty($_POST['email'])) $meta['email'] = $_POST['email'];
        if (!empty($_POST['map_embed'])) $meta['map_embed'] = $_POST['map_embed'];
        if (!empty($_POST['partners'])) $meta['partners'] = $_POST['partners'];
        
        $upload = handleUpload('page_image');
        $imageName = $upload['name'] ?? $oldImage;
        
        $stmt = $pdo->prepare('UPDATE pages SET title=?, body=?, image=?, meta=?, updated_at=NOW() WHERE id=?');
        $stmt->execute([$title, $body, $imageName, json_encode($meta), $id]);
        
        if (!empty($upload['name']) && $oldImage && file_exists(__DIR__ . '/uploads/' . $oldImage)) {
            @unlink(__DIR__ . '/uploads/' . $oldImage);
        }
        audit_log($pdo, 'update', 'pages', $id, 'page_key: ' . $pageKey, $currentUser);
        $message = 'Pagina item bijgewerkt.';
    }
} elseif ($pageAction === 'delete_page') {
    $id = (int)$_POST['id'] ?? 0;
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
        $message = 'Pagina item verwijderd.';
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

// Haal events voor overzicht
$stmt = $pdo->prepare('SELECT * FROM events ORDER BY date DESC, time DESC');
$stmt->execute();
$events = $stmt->fetchAll();
?>
<?php
// Welke admin pagina tonen (standaard agenda)
$page = $_GET['page'] ?? 'agenda';
?>
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin - SociaalAI Lab</title>
<link rel="stylesheet" href="build/assets/app-DozK-03z.css">
<link rel="stylesheet" href="custom.css">
<link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
    }
    .admin-header {
        background: linear-gradient(135deg, #00811F 0%, #00a030 100%);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .tab-button {
        transition: all 0.3s ease;
        border-bottom: 3px solid transparent;
    }
    .tab-button.active {
        border-bottom-color: #00811F;
        color: #00811F;
        font-weight: 600;
    }
    .card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        transition: box-shadow 0.3s ease;
    }
    .card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-label {
        display: block;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 0.95rem;
    }
    .form-input, .form-textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: inherit;
        font-size: 1rem;
        transition: border-color 0.2s;
    }
    .form-input:focus, .form-textarea:focus {
        outline: none;
        border-color: #00811F;
        box-shadow: 0 0 0 3px rgba(0, 129, 31, 0.1);
    }
    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 6px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-primary {
        background: #00811F;
        color: white;
    }
    .btn-primary:hover {
        background: #006817;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 129, 31, 0.3);
    }
    .btn-secondary {
        background: #e0e0e0;
        color: #333;
    }
    .btn-secondary:hover {
        background: #d0d0d0;
    }
    .btn-danger {
        background: #dc3545;
        color: white;
    }
    .btn-danger:hover {
        background: #c82333;
    }
    .alert {
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1.5rem;
    }
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .event-list {
        display: grid;
        gap: 1rem;
    }
    .event-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem;
        border-left: 4px solid #00811F;
    }
    .event-info h3 {
        margin: 0 0 0.5rem 0;
        color: #333;
        font-size: 1.1rem;
    }
    .event-meta {
        font-size: 0.9rem;
        color: #666;
    }
    .event-actions {
        display: flex;
        gap: 0.5rem;
    }
    .sidebar {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        height: fit-content;
    }
    .sidebar-header {
        background: linear-gradient(135deg, #00811F 0%, #00a030 100%);
        color: white;
        padding: 1rem;
        font-weight: 600;
    }
    .sidebar-link {
        display: block;
        padding: 0.75rem 1rem;
        color: #333;
        text-decoration: none;
        border-left: 3px solid transparent;
        transition: all 0.2s;
    }
    .sidebar-link:hover {
        background: #f5f5f5;
        border-left-color: #00811F;
    }
    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
</head>
<body>
<nav class="admin-header text-white p-4 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-gears text-2xl"></i>
            <h1 class="text-2xl font-bold">Admin Panel</h1>
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
    
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-list"></i> Navigatie
            </div>
            <nav class="divide-y">
                <!-- Hoofdsecties -->
                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Beheer</div>
                <a href="admin.php?page=agenda" class="sidebar-link <?php echo $page==='agenda' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar"></i> Evenementen
                </a>
                <a href="admin.php?page=banner" class="sidebar-link <?php echo $page==='banner' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-image"></i> Banners
                </a>
                
                <!-- Pagina's secties -->
                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Pagina's</div>
                <a href="admin.php?page=index" class="sidebar-link <?php echo $page==='index' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house"></i> Homepage
                </a>
                <a href="admin.php?page=agenda-page" class="sidebar-link <?php echo $page==='agenda-page' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar-list"></i> Agenda Pagina
                </a>
                <a href="admin.php?page=event" class="sidebar-link <?php echo $page==='event' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-star"></i> Event Pagina
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
                
                <!-- Programma's -->
                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Programma's</div>
                <a href="admin.php?page=programma-actie" class="sidebar-link <?php echo $page==='programma-actie' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-bolt"></i> Actie, Onderzoek
                </a>
                <a href="admin.php?page=programma-faciliteit" class="sidebar-link <?php echo $page==='programma-faciliteit' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-building"></i> Faciliteit
                </a>
                <a href="admin.php?page=programma-kennis" class="sidebar-link <?php echo $page==='programma-kennis' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-book"></i> Kennis & Vaardigheden
                </a>
            </nav>
        </aside>

        <div class="lg:col-span-3">

            <?php if ($page === 'agenda'): ?>
                <?php if ($editEvent): ?>
                    <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4">
                        <h2 class="font-semibold">Bewerk evenement</h2>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo (int)$editEvent['id']; ?>">
                        <div>
                            <label>Titel</label>
                            <input name="title" value="<?php echo htmlspecialchars($editEvent['title']); ?>" required class="w-full border px-3 py-2" />
                        </div>
                        <div>
                            <label>Locatie</label>
                            <input name="location" value="<?php echo htmlspecialchars($editEvent['location']); ?>" class="w-full border px-3 py-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label>Datum</label>
                                <input type="date" name="date" value="<?php echo htmlspecialchars($editEvent['date']); ?>" required class="w-full border px-3 py-2" />
                            </div>
                            <div>
                                <label>Tijd</label>
                                <input type="time" name="time" value="<?php echo htmlspecialchars($editEvent['time']); ?>" class="w-full border px-3 py-2" />
                            </div>
                        </div>
                        <div>
                            <label>Beschrijving</label>
                            <textarea name="description" rows="4" class="w-full border px-3 py-2"><?php echo htmlspecialchars($editEvent['description']); ?></textarea>
                        </div>

                        <?php if (!empty($editEvent['updated_at']) || !empty($editEvent['updated_by'])): ?>
                            <div class="text-sm text-gray-600">
                                Laatst aangepast door: <?php echo htmlspecialchars($editEvent['updated_by'] ?? 'onbekend'); ?> op <?php echo htmlspecialchars($editEvent['updated_at'] ?? 'onbekend'); ?>
                            </div>
                        <?php endif; ?>

                        <div>
                            <label>Vervang foto (optioneel)</label>
                            <input type="file" name="image" accept="image/*" />
                            <?php if ($editEvent['image']): ?>
                                <div class="mt-2"><small>Huidige afbeelding:</small><br><img src="uploads/<?php echo htmlspecialchars($editEvent['image']); ?>" class="w-48 mt-2" alt=""></div>
                            <?php endif; ?>
                        </div>
                        <div class="flex gap-2">
                            <button class="bg-[#00811F] text-white px-4 py-2 rounded">Opslaan</button>
                            <a href="admin.php?page=agenda" class="px-4 py-2 border rounded">Annuleer</a>
                        </div>
                    </form>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data" class="bg-white p-6 shadow-md space-y-4">
                        <h2 class="font-semibold">Nieuw evenement</h2>
                        <input type="hidden" name="action" value="create">
                        <div>
                            <label>Titel</label>
                            <input name="title" required class="w-full border px-3 py-2" />
                        </div>
                        <div>
                            <label>Locatie</label>
                            <input name="location" class="w-full border px-3 py-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label>Datum</label>
                                <input type="date" name="date" required class="w-full border px-3 py-2" />
                            </div>
                            <div>
                                <label>Tijd</label>
                                <input type="time" name="time" class="w-full border px-3 py-2" />
                            </div>
                        </div>
                        <div>
                            <label>Beschrijving</label>
                            <textarea name="description" rows="4" class="w-full border px-3 py-2"></textarea>
                        </div>
                        <div>
                            <label>Foto (optioneel)</label>
                            <input type="file" name="image" accept="image/*" />
                        </div>
                        <button class="bg-[#00811F] text-white px-4 py-2 rounded">Maak evenement</button>
                    </form>
                <?php endif; ?>

                
                <div class="event-list">
                    <?php if (empty($events)): ?>
                        <div class="card p-6 text-center text-gray-500">
                            <i class="fa-solid fa-calendar-xmark text-4xl mb-2"></i>
                            <p>Geen evenementen gevonden</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($events as $e): ?>
                            <div class="card event-item">
                                <div class="event-info flex-1">
                                    <h3><?php echo htmlspecialchars($e['title']); ?></h3>
                                    <div class="event-meta">
                                        <i class="fa-solid fa-calendar"></i> <?php echo htmlspecialchars($e['date']); ?>
                                        <?php if (!empty($e['time'])): ?>
                                            <i class="fa-solid fa-clock ml-2"></i> <?php echo htmlspecialchars($e['time']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($e['location'])): ?>
                                            <i class="fa-solid fa-location-dot ml-2"></i> <?php echo htmlspecialchars($e['location']); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($e['description'])): ?>
                                        <p class="text-sm mt-2 line-clamp-2"><?php echo htmlspecialchars(mb_strimwidth($e['description'], 0, 150, '...')); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="event-actions flex-shrink-0">
                                    <?php if ($e['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($e['image']); ?>" class="w-24 h-24 object-cover rounded mb-2" alt="Event">
                                    <?php endif; ?>
                                    <div class="flex gap-2">
                                        <a href="admin.php?edit=<?php echo (int)$e['id']; ?>&page=agenda" class="btn btn-secondary btn-sm" title="Bewerk">
                                            <i class="fa-solid fa-pencil"></i> Bewerk
                                        </a>
                                        <form method="POST" onsubmit="return confirm('Verwijder dit evenement?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
                                            <button class="btn btn-danger btn-sm">
                                                <i class="fa-solid fa-trash"></i> Verwijder
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            
            <?php elseif ($page != 'banner'): ?>
                <?php
                $pageKey = $page;
                $fields = ['title','body','image'];
                $extraLabels = [];
                switch ($pageKey) {
                    case 'index':
                        $fields = ['title','body','image'];
                        break;
                    case 'agenda-page':
                        $fields = ['title','body','image'];
                        break;
                    case 'event':
                        $fields = ['title','body','image'];
                        break;
                    case 'terugblikken':
                        $fields = ['title','body','image'];
                        break;
                    case 'contact':
                        $fields = ['title','body','address','email','map_embed','image'];
                        $extraLabels['address']='Adres';
                        $extraLabels['email']='E-mail';
                        $extraLabels['map_embed']='Map embed (iframe)';
                        break;
                    case 'over':
                        $fields = ['title','body','image'];
                        break;
                    case 'verantwoord-ai':
                        $fields = ['title','body','image'];
                        break;
                    case 'wie-zijn-we':
                        $fields = ['title','body','image'];
                        break;
                    case 'programma-actie':
                        $fields = ['title','body','image'];
                        break;
                    case 'programma-faciliteit':
                        $fields = ['title','body','image'];
                        break;
                    case 'programma-kennis':
                        $fields = ['title','body','image'];
                        break;
                    default:
                        $fields = ['title','body','image'];
                }

                $stmt = $pdo->prepare('SELECT * FROM pages WHERE page_key = ? ORDER BY created_at DESC');
                $stmt->execute([$pageKey]);
                $pageItems = $stmt->fetchAll();

                $pageNames = [
                    'index' => 'Homepage',
                    'agenda-page' => 'Agenda Pagina',
                    'event' => 'Event Pagina',
                    'terugblikken' => 'Terugblikken',
                    'contact' => 'Contact',
                    'over' => 'Voor wie?',
                    'verantwoord-ai' => 'Verantwoord AI',
                    'wie-zijn-we' => 'Wie zijn we?',
                    'programma-actie' => 'Programma: Actie, Onderzoek & Ontwerp',
                    'programma-faciliteit' => 'Programma: Faciliteit van het Lab',
                    'programma-kennis' => 'Programma: Kennis & Vaardigheden',
                ];
                $pageName = $pageNames[$pageKey] ?? ucfirst(str_replace('-', ' ', $pageKey));
                ?>
                <div class="card p-6 mb-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-file-pen text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Beheer: <?php echo htmlspecialchars($pageName); ?></h2>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4 bg-gray-50 p-4 rounded">
                        <input type="hidden" name="page_action" value="<?php echo $editPage ? 'update_page' : 'create_page'; ?>">

                        <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">
                        <?php if ($editPage): ?>
                            <input type="hidden" name="id" value="<?php echo (int)$editPage['id']; ?>">
                        <?php endif; ?>

                        <?php if (in_array('title',$fields)): ?>
                            <div class="form-group">
                                <label class="form-label">Titel *</label>
                                <input name="title" value="<?php echo htmlspecialchars($editPage['title'] ?? ''); ?>" required class="form-input" />
                            </div>
                        <?php endif; ?>

                        <?php if (in_array('body',$fields)): ?>
                            <div class="form-group">
                                <label class="form-label">Beschrijving</label>
                                <textarea name="body" rows="5" class="form-textarea"><?php echo htmlspecialchars($editPage['body'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>

                        <?php if (in_array('address',$fields)): ?>
                            <div class="form-group">
                                <label class="form-label">Adres</label>
                                <input name="address" value="<?php echo htmlspecialchars($editPage ? (json_decode($editPage['meta'] ?? 'null', true)['address'] ?? '') : ''); ?>" class="form-input" />
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('email',$fields)): ?>
                            <div class="form-group">
                                <label class="form-label">E-mail</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($editPage ? (json_decode($editPage['meta'] ?? 'null', true)['email'] ?? '') : ''); ?>" class="form-input" />
                            </div>
                        <?php endif; ?>
                        
                        <?php if (in_array('map_embed',$fields)): ?>
                            <div class="form-group">
                                <label class="form-label">Google Map Embed (iframe code)</label>
                                <textarea name="map_embed" rows="3" class="form-textarea" placeholder="Plak hier de iframe code van Google Maps"><?php echo htmlspecialchars($editPage ? (json_decode($editPage['meta'] ?? 'null', true)['map_embed'] ?? '') : ''); ?></textarea>
                                <small class="text-gray-600">Ga naar Google Maps, zoek een locatie, klik op "Delen", selecteer "Kaart insluiten" en kopieer de code</small>
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Afbeelding (optioneel)</label>
                            <input type="file" name="page_image" accept="image/*" class="form-input" />
                            <?php if (!empty($editPage['image'])): ?>
                                <div class="mt-3">
                                    <small class="text-gray-600">Huidige afbeelding:</small>
                                    <img src="uploads/<?php echo htmlspecialchars($editPage['image']); ?>" class="w-32 h-32 object-cover rounded mt-2" alt="">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex gap-2 pt-4 border-t">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> <?php echo $editPage ? 'Opslaan' : 'Voeg toe'; ?>
                            </button>
                            <?php if ($editPage): ?>
                                <a href="admin.php?page=<?php echo urlencode($pageKey); ?>" class="btn btn-secondary">
                                    <i class="fa-solid fa-times"></i> Annuleer
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4 border-b-2 border-gray-200">
                        <i class="fa-solid fa-list text-2xl text-[#00811F]"></i>
                        <h3 class="text-xl font-bold">Bestaande Content</h3>
                    </div>
                    <?php if (empty($pageItems)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                            <p>Geen content toegevoegd voor deze pagina</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                        <?php foreach ($pageItems as $it): $metaArr = $it['meta'] ? json_decode($it['meta'], true) : []; ?>
                            <div class="border border-gray-200 rounded p-4 hover:shadow-md transition">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($it['title'] ?? ''); ?></h4>
                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?php echo nl2br(htmlspecialchars($it['body'] ? mb_strimwidth($it['body'],0,150,'...') : '')); ?></p>
                                        <?php if (!empty($metaArr['address'])): ?><p class="text-sm text-gray-500 mt-1"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($metaArr['address']); ?></p><?php endif; ?>
                                    </div>
                                    <div class="flex gap-2 flex-shrink-0">
                                        <?php if (!empty($it['image'])): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($it['image']); ?>" class="w-20 h-20 object-cover rounded" alt="">
                                        <?php endif; ?>
                                        <div class="flex flex-col gap-1">
                                            <a href="admin.php?edit_page=<?php echo (int)$it['id']; ?>&page=<?php echo urlencode($pageKey); ?>" class="btn btn-secondary btn-sm">
                                                <i class="fa-solid fa-pencil"></i> Bewerk
                                            </a>
                                            <form method="POST" onsubmit="return confirm('Verwijder deze content?');" style="display:inline;">
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
                    <?php endif; ?>
                </div>
            <?php elseif ($page == 'banner'): ?>
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
                                <small class="text-gray-600">Aanbevolen formaat: 1920x600px</small>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Vervang Banner 2</label>
                                <input type="file" name="banner2" accept="image/*" class="form-input" />
                                <small class="text-gray-600">Aanbevolen formaat: 1920x600px</small>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 pt-4 border-t">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Banners Bijwerken
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>
