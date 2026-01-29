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
                $stmt = $pdo->prepare('INSERT INTO events (title, date, time, description, image, location) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$title, $date, $time ?: null, $description, $imageName, $location ?: null]);
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
                $stmt = $pdo->prepare('UPDATE events SET title=?, date=?, time=?, description=?, image=?, location=? WHERE id=?');
                $stmt->execute([$title, $date, $time ?: null, $description, $imageName, $location ?: null, $id]);
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
<!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin - Evenementen</title>
<link rel="stylesheet" href="build/assets/app-DozK-03z.css">
</head>
<body class="bg-gradient-to-br from-[#00811F] to-[#b9eb34]">
<nav class="bg-white shadow-md p-4 max-w-6xl mx-auto">
    <div class="flex justify-between items-center">
        <h1 class="text-lg font-bold">Admin - Evenementen</h1>
        <a href="index.php" class="text-[#00811F]">Terug naar site</a>
    </div>
</nav>

<main class="max-w-4xl mx-auto p-6">
    <?php if (!empty($_GET['ok'])): ?>
        <div class="mb-4 p-3 bg-green-100">Actie succesvol: <?php echo htmlspecialchars($_GET['ok']); ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="mb-4 p-3 bg-red-100"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

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
            <div>
                <label>Vervang foto (optioneel)</label>
                <input type="file" name="image" accept="image/*" />
                <?php if ($editEvent['image']): ?>
                    <div class="mt-2"><small>Huidige afbeelding:</small><br><img src="uploads/<?php echo htmlspecialchars($editEvent['image']); ?>" class="w-48 mt-2" alt=""></div>
                <?php endif; ?>
            </div>
            <div class="flex gap-2">
                <button class="bg-[#00811F] text-white px-4 py-2 rounded">Opslaan</button>
                <a href="admin.php" class="px-4 py-2 border rounded">Annuleer</a>
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
        <div class="space-y-6"> <!-- wrapper voegt consistente ruimte tussen items -->
        <?php foreach ($events as $e): ?>
            <div class="bg-white p-6 shadow-md border border-gray-200 rounded-lg flex justify-between items-start">
                <div>
                    <h3 class="font-bold"><?php echo htmlspecialchars($e['title']); ?></h3>
                    <?php if (!empty($e['location'])): ?>
                        <p class="text-sm text-gray-700"><?php echo 'Locatie: ' . htmlspecialchars($e['location']); ?></p>
                    <?php endif; ?>
                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($e['date'] . ' ' . ($e['time'] ?: '')); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($e['description'])); ?></p>
                </div>
                <div class="flex flex-col items-end gap-2">
                    <?php if ($e['image']): ?>
                        <img src="uploads/<?php echo htmlspecialchars($e['image']); ?>" alt="" class="w-32 h-20 object-cover mb-2">
                    <?php endif; ?>
                    <div class="flex gap-2">
                        <a href="admin.php?edit=<?php echo (int)$e['id']; ?>" class="px-3 py-1 border rounded">Bewerk</a>
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
</main>
</body>
</html>
