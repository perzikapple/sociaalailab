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
            header('Location: admin.php?ok=delete');
            exit;
        } else {
            $message = 'Evenement niet gevonden.';
        }
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
<title>Admin - Evenementen</title>
<link rel="stylesheet" href="build/assets/app-DozK-03z.css">
<link rel="stylesheet" href="custom.css">
</head>
<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">
<nav class="bg-white shadow-md p-4 max-w-6xl mx-auto">
    <div class="flex justify-between items-center">
        <h1 class="text-lg font-bold">Admin - Evenementen</h1>
        <a href="index.php" class="text-[#00811F]">Terug naar site</a>
    </div>
</nav>

<main class="max-w-6xl mx-auto p-6">
    <div class="flex gap-6">
        <aside class="w-64 bg-white p-4 shadow rounded">
            <h3 class="font-semibold text-lg mb-3">Admin Navigatie</h3>
            <ul class="space-y-2 text-sm">
                <?php $active = function($k) use ($page){ return $k===$page ? 'text-white bg-[#00811F] px-3 py-1 rounded block' : 'text-[#00811F] hover:bg-gray-100 px-3 py-1 rounded block'; }; ?>
                <li><a class="<?php echo $active('agenda'); ?>" href="admin.php?page=agenda">Agenda (Evenementen)</a></li>
                <li><a class="<?php echo $active('terugblikken'); ?>" href="admin.php?page=terugblikken">Terugblikken</a></li>
                <li><a class="<?php echo $active('contact'); ?>" href="admin.php?page=contact">Contact</a></li>
                <li><a class="<?php echo $active('over'); ?>" href="admin.php?page=over">Over</a></li>
                <li><a class="<?php echo $active('verantwoord-ai'); ?>" href="admin.php?page=verantwoord-ai">Verantwoord AI</a></li>
                <li><a class="<?php echo $active('wie-zijn-we'); ?>" href="admin.php?page=wie-zijn-we">Wie Zijn We</a></li>
                <li><a class="<?php echo $active('programma-actie'); ?>" href="admin.php?page=programma-actie">Programma: Actie</a></li>
                <li><a class="<?php echo $active('programma-faciliteit'); ?>" href="admin.php?page=programma-faciliteit">Programma: Faciliteit</a></li>
                <li><a class="<?php echo $active('programma-kennis'); ?>" href="admin.php?page=programma-kennis">Programma: Kennis</a></li>
            </ul>
        </aside>

        <div class="flex-1">
            <?php if (!empty($_GET['ok'])): ?>
                <div class="mb-4 p-3 bg-green-100">Actie succesvol: <?php echo htmlspecialchars($_GET['ok']); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="mb-4 p-3 bg-red-100"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

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

                <section class="mt-8">
                    <h2 class="text-xl font-semibold mb-4">Bestaande evenementen</h2>
                    <div class="space-y-6">
                    <?php foreach ($events as $e): ?>
                        <div class="bg-white p-6 shadow-md border border-gray-200 rounded-lg flex justify-between items-start">
                            <div>
                                <h3 class="font-bold"><?php echo htmlspecialchars($e['title']); ?></h3>
                                <?php if (!empty($e['location'])): ?>
                                    <p class="text-sm text-gray-700"><?php echo 'Locatie: ' . htmlspecialchars($e['location']); ?></p>
                                <?php endif; ?>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($e['date'] . ' ' . ($e['time'] ?: '')); ?></p>
                                <p><?php echo nl2br(htmlspecialchars($e['description'])); ?></p>

                                <?php if (!empty($e['updated_at']) || !empty($e['updated_by'])): ?>
                                    <p class="text-xs text-gray-500 mt-2">Laatst aangepast door <?php echo htmlspecialchars($e['updated_by'] ?? 'onbekend'); ?> op <?php echo htmlspecialchars($e['updated_at'] ?? 'onbekend'); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <?php if ($e['image']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($e['image']); ?>" alt="" class="w-32 h-20 object-cover mb-2">
                                <?php endif; ?>
                                <div class="flex gap-2">
                                    <a href="admin.php?edit=<?php echo (int)$e['id']; ?>&page=agenda" class="px-3 py-1 border rounded">Bewerk</a>
                                    <form method="POST" onsubmit="return confirm('Weet je het zeker dat je dit evenement wilt verwijderen?');" style="display:inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
                                        <button class="px-3 py-1 border rounded text-red-600 hover:bg-red-100">Verwijder</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </section>
            <?php else: ?>
                <section class="bg-white shadow-lg p-6 rounded">
                    <h2 class="text-xl font-semibold mb-4">Admin: <?php echo htmlspecialchars($page); ?></h2>
                    <p class="text-sm text-gray-700 mb-4">Deze pagina is een admin-placeholder voor de publieke pagina. Hier kun je later specifieke beheerfuncties toevoegen (content editor, afbeeldingen, instellingen).</p>
                    <div class="bg-gray-50 border border-dashed border-gray-200 p-4 rounded">
                        <p class="text-sm text-gray-600">Voor nu: lijst van relevante items of bewerkformulieren verschijnen hier.</p>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
