<?php
require_once __DIR__ . '/../db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$errors = [];
$successMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pictureIds = $_POST['picture_ids'] ?? []; // Get picture IDs to add to collection

    if (empty($name)) {
        $errors[] = "Collection name is required.";
    }

    if (empty($errors)) {
        // Generate a UUID for the collection
        $collectionId = bin2hex(random_bytes(16)); // Generate a 36-character UUID

        // Insert the collection into the database
        $stmt = $pdo->prepare("
            INSERT INTO collection (id, name, description, started_at, finished_at) 
            VALUES (:id, :name, :description, :started_at, :finished_at)
        ");
        $stmt->execute([
            'id' => $collectionId,
            'name' => $name,
            'description' => $description,
            'started_at' => $_POST['started_at'] ?? null,
            'finished_at' => $_POST['ended_at'] ?? null,
        ]);

        // Insert paintings into the collection
        foreach ($pictureIds as $position => $pictureId) {
            $stmt = $pdo->prepare("
                INSERT INTO collection_painting (painting_id, collection_id, position) 
                VALUES (:painting_id, :collection_id, :position)
            ");
            $stmt->execute([
                'painting_id' => $pictureId,
                'collection_id' => $collectionId,
                'position' => $position + 1,
            ]);
        }

        $successMessage = "Collection created successfully!";
    }
}

// Fetch all paintings with their first image
$stmt = $pdo->prepare("
    SELECT 
        p.id AS painting_id,
        p.title AS painting_title,
        pi.file_path AS image_path
    FROM painting p
    LEFT JOIN painting_image pi ON p.id = pi.painting_id AND pi.position = 1
");
$stmt->execute();
$pictures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Opprett sammling</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="/css/form.css">
</head>
<body>
<h1>Opprett en samling</h1>

<?php if ($successMessage): ?>
    <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST">
    <label for="name">Navn på samling</label>
    <input id="name" name="name" placeholder="Navn på samling" required><br>
    <label for="description">Beskrivelse</label>
    <textarea id="description" name="description" placeholder="Beskrivelse"></textarea><br>
    <label for="started_at">Startdato</label>
    <input name="started_at" id="started_at" type="date" placeholder="Startdato"><br>
    <label for="ended_at">Sluttdato</label>
    <input name="ended_at" id="ended_at" type="date" placeholder="Sluttdato"><br>

    <h3>Velg malerier å legge til</h3>
    <div class="painting-preview" id="paintingPreview">
        <?php foreach ($pictures as $picture): ?>
            <div class="painting-thumb" 
                 data-painting-id="<?= htmlspecialchars($picture['painting_id']) ?>" 
                 style="display: inline-block; margin: 10px; text-align: center; cursor: pointer;">
                <?php if (!empty($picture['image_path'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($picture['image_path']) ?>" alt="Preview" style="width:100px;height:auto;display:block;">
                <?php else: ?>
                    <div style="width:100px;height:100px;background-color:#ccc;display:flex;align-items:center;justify-content:center;">
                        No Image
                    </div>
                <?php endif; ?>
                <span><?= htmlspecialchars($picture['painting_title'] ?? 'Untitled') ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <!-- Hidden container for selected painting ids -->
    <div id="selectedPaintings"></div>
    <button type="submit">Opprett samling</button>
</form>
<script>
    // Add highlight class on click and manage hidden inputs
    document.addEventListener('DOMContentLoaded', function () {
        const preview = document.getElementById('paintingPreview');
        const selectedPaintings = document.getElementById('selectedPaintings');
        const selected = new Set();

        preview.querySelectorAll('.painting-thumb').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                const id = thumb.getAttribute('data-painting-id');
                if (selected.has(id)) {
                    selected.delete(id);
                    thumb.classList.remove('highlight');
                    // Remove hidden input
                    const input = selectedPaintings.querySelector('input[value="' + id + '"]');
                    if (input) input.remove();
                } else {
                    selected.add(id);
                    thumb.classList.add('highlight');
                    // Add hidden input
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'picture_ids[]';
                    input.value = id;
                    selectedPaintings.appendChild(input);
                }
            });
        });
    });
</script>
<style>
    .painting-thumb.highlight {
        outline: 3px solid #007bff;
        background: #e6f0ff;
        border-radius: 6px;
    }
</style>
</body>
</html>
