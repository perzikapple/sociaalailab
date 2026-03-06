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

function formatEventDateDisplay($dateValue) {
    if (empty($dateValue)) return '';
    $ts = strtotime((string)$dateValue);
    if ($ts === false) return (string)$dateValue;
    $monthsNl = [
        1 => 'januari',
        2 => 'februari',
        3 => 'maart',
        4 => 'april',
        5 => 'mei',
        6 => 'juni',
        7 => 'juli',
        8 => 'augustus',
        9 => 'september',
        10 => 'oktober',
        11 => 'november',
        12 => 'december',
    ];
    $day = (int)date('j', $ts);
    $month = $monthsNl[(int)date('n', $ts)] ?? date('F', $ts);
    $year = date('Y', $ts);
    return $day . ' ' . $month . ' ' . $year;
}

function formatEventTimeDisplay($timeValue) {
    if (empty($timeValue)) return '';
    $ts = strtotime((string)$timeValue);
    if ($ts === false) return (string)$timeValue;
    return date('H:i', $ts);
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

        // Change 'page_image' to 'image' to match the form field name
        $upload = handleUpload('image');
        $imageName = $upload['name'] ?? null;

        $stmt = $pdo->prepare('INSERT INTO pages (page_key, title, body, image, meta, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())');
        $stmt->execute([$pageKey, $title, $body, $imageName, json_encode($meta)]);
        audit_log($pdo, 'create', 'pages', $pdo->lastInsertId(), 'page_key: ' . $pageKey, $currentUser);
        $message = 'Pagina item aangemaakt.';
    }
} elseif ($pageAction === 'update_page') {
    $id = (int)($_POST['id'] ?? 0);
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

        // Change 'page_image' to 'image' to match the form field name
        $upload = handleUpload('image');
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
<link rel="stylesheet" href="admin-styles.css">
<link rel="stylesheet" href="ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-list"></i> Navigatie
            </div>
            <nav class="divide-y">
                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Beheer</div>
                <a href="admin.php?page=agenda" class="sidebar-link <?php echo $page==='agenda' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-calendar"></i> Agenda
                </a>
                <a href="admin.php?page=banner" class="sidebar-link <?php echo $page==='banner' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-image"></i> Banners
                </a>

                <div class="px-4 py-2 bg-gray-100 text-sm font-semibold text-gray-700">Pagina's</div>
                <a href="admin.php?page=index" class="sidebar-link <?php echo $page==='index' ? 'active' : ''; ?>">
                    <i class="fa-solid fa-house"></i> Homepage
                </a>
                <a href="admin.php?page=event" class="sidebar-link <?php echo $page==='event' ? 'active' : ''; ?>">
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
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4">
                        <i class="fa-solid fa-calendar text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Beheer: Agenda</h2>
                    </div>

                <?php if ($editEvent): ?>
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <h2 class="font-semibold">Bewerk evenement</h2>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo (int)$editEvent['id']; ?>">
                        <div>
                            <label>Titel</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($editEvent['title']); ?>" required class="w-full border px-3 py-2" />
                        </div>
                        <div>
                            <label>Locatie</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($editEvent['location']); ?>" class="w-full border px-3 py-2" />
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
                            <input type="file" name="page_image" accept="image/*" />
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
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <h2 class="font-semibold">Nieuw evenement</h2>
                        <input type="hidden" name="action" value="create">
                        <div>
                            <label>Titel</label>
                            <input type="text" name="title" required class="w-full border px-3 py-2" />
                        </div>
                        <div>
                            <label>Locatie</label>
                            <input type="text" name="location" class="w-full border px-3 py-2" />
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
                        <button class="bg-[#00811F] text-white px-7 py-4 rounded text-base font-semibold inline-flex items-center justify-center gap-2 whitespace-nowrap" style="min-width:220px;"><i class="fa-solid fa-plus"></i> Maak evenement</button>
                    </form>
                <?php endif; ?>

                <div class="event-list mt-6">
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
                                        <i class="fa-solid fa-calendar"></i> <?php echo htmlspecialchars(formatEventDateDisplay($e['date'])); ?>
                                        <?php if (!empty($e['time'])): ?>
                                            <i class="fa-solid fa-clock ml-2"></i> <?php echo htmlspecialchars(formatEventTimeDisplay($e['time'])); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($e['location'])): ?>
                                            <?php $mapsLocationUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode((string)$e['location']); ?>
                                            <i class="fa-solid fa-location-dot ml-2"></i> <a href="<?php echo htmlspecialchars($mapsLocationUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($e['location']); ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($e['description'])): ?>
                                        <p class="text-sm mt-2 line-clamp-2"><?php echo htmlspecialchars(mb_strimwidth($e['description'], 0, 150, '...')); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div class="event-actions flex-shrink-0">
                                    <?php if (!empty($e['image'])): ?>
                                        <div class="event-image-wrap">
                                            <button
                                                type="button"
                                                class="event-image-toggle bg-[#00811F] hover:bg-[#006817] text-white px-3 py-2 rounded text-sm transition"
                                                aria-expanded="false"
                                            >
                                                <i class="fa-solid fa-image cursor-p"></i> Bekijk afbeelding
                                            </button>
                                            <div class="event-image-preview" aria-hidden="true">
                                                <img src="uploads/<?php echo htmlspecialchars($e['image'], ENT_QUOTES); ?>" alt="Event afbeelding">
                                            </div>
                                        </div>
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
                    case 'contact':
                        $fields = ['title','body','address','email','map_embed','image'];
                        $extraLabels['address']='Adres';
                        $extraLabels['email']='E-mail';
                        $extraLabels['map_embed']='Map embed (iframe)';
                        break;
                    default:
                        $fields = ['title','body','image'];
                }

                $stmt = $pdo->prepare('SELECT * FROM pages WHERE page_key = ? ORDER BY created_at DESC');
                $stmt->execute([$pageKey]);
                $pageItems = $stmt->fetchAll();

                $pageNames = [
                    'index' => 'Homepage',
                    'event' => 'Evenementen',
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
                <div class="card p-6">
                    <div class="flex items-center gap-2 mb-4 pb-4">
                        <i class="fa-solid fa-file-pen text-2xl text-[#00811F]"></i>
                        <h2 class="text-2xl font-bold">Beheer: <?php echo htmlspecialchars($pageName); ?></h2>
                    </div>

                    <?php if ($editPage): ?>
                        <a href="admin.php?page=<?php echo urlencode($pageKey); ?>" class="btn btn-secondary mb-6">
                            <i class="fa-solid fa-arrow-left"></i> Terug
                        </a>
                    <?php endif; ?>

                    <?php if ($editPage): ?>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4 mb-6">
                            <h3 class="font-semibold text-lg">Bewerk Item</h3>
                            <input type="hidden" name="page_action" value="update_page">
                            <input type="hidden" name="id" value="<?php echo (int)$editPage['id']; ?>">
                            <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">

                            <div>
                                <label class="form-label">Titel</label>
                                <input type="text" name="title" required class="form-input" value="<?php echo htmlspecialchars($editPage['title']); ?>" />
                            </div>

                            <div>
                                <label class="form-label">Inhoud</label>
                                <textarea name="body" rows="6" class="form-textarea"><?php echo htmlspecialchars($editPage['body']); ?></textarea>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                                <?php if (!empty($editPage['image'])): ?>
                                    <div class="mt-2 flex items-center gap-3">
                                        <img src="uploads/<?php echo htmlspecialchars($editPage['image']); ?>" alt="Huidige afbeelding" class="w-24 h-16 object-cover rounded shadow">
                                        <p class="text-sm text-gray-600">Huidige afbeelding: <?php echo htmlspecialchars($editPage['image']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Opslaan
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4 mb-6">
                            <h3 class="font-semibold text-lg">Nieuw Item</h3>
                            <input type="hidden" name="page_action" value="create_page">
                            <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey); ?>">

                            <div>
                                <label class="form-label">Titel</label>
                                <input type="text" name="title" required class="form-input" />
                            </div>

                            <div>
                                <label class="form-label">Inhoud</label>
                                <textarea name="body" rows="6" class="form-textarea"></textarea>
                            </div>

                            <div>
                                <label class="form-label">Afbeelding (optioneel)</label>
                                <input type="file" name="image" accept="image/*" class="form-input" />
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus"></i> Toevoegen
                            </button>
                        </form>
                    <?php endif; ?>

                    <div class="card p-6">
                        <div class="flex items-center gap-2 mb-4 pb-4">
                            <i class="fa-solid fa-list text-2xl text-[#00811F]"></i>
                            <h3 class="text-xl font-bold">Bestaande Items</h3>
                        </div>
                        <?php if (empty($pageItems)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fa-solid fa-inbox text-4xl mb-2"></i>
                                <p>Geen items toegevoegd voor deze pagina</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                            <?php foreach ($pageItems as $it): ?>
                                <div class="border border-gray-200 rounded p-4 hover:shadow-md transition">
                                    <div class="flex justify-between items-start gap-4">
                                        <div class="flex-1">
                                            <h4 class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($it['title'] ?? ''); ?></h4>
                                            <p class="text-sm text-gray-600 mt-1 line-clamp-2"><?php echo nl2br(htmlspecialchars($it['body'] ? mb_strimwidth($it['body'],0,150,'...') : '')); ?></p>
                                        </div>
                                        <div class="flex gap-2 flex-shrink-0">
                                            <?php if (!empty($it['image'])): ?>
                                                <a href="#" class="btn btn-secondary btn-sm" onclick="openImageModal(event, 'uploads/<?php echo htmlspecialchars($it['image']); ?>')">
                                                    <img src="uploads/<?php echo htmlspecialchars($it['image']); ?>" alt="foto" class="w-8 h-8 object-cover rounded inline-block mr-1">Foto
                                                </a>
                                            <?php endif; ?>
                                            <div class="flex flex-col gap-1">
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
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($page == 'banner'): ?>
                <div class="card p-6">
                        <div class="flex items-center gap-2 mb-4 pb-4">
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
</script>

</body>
</html>


