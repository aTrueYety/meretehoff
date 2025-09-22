<?php
require_once __DIR__ . '/../db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$collectionId = $_GET['id'] ?? null;
$errors = [];
$successMessage = '';

if (!$collectionId) {
  die("Collection ID is required.");
}

// Fetch existing collection data
$stmt = $pdo->prepare("SELECT * FROM collection WHERE id = :id");
$stmt->execute(['id' => $collectionId]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$collection) {
  die("Collection not found.");
}

// Handle updating collection name, description, and paintings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $paintingIds = $_POST['picture_ids'] ?? [];

  if (empty($name)) {
    $errors[] = "Collection name is required.";
  }

  if (empty($errors)) {
    // Update collection data in the database
    $stmt = $pdo->prepare("
        UPDATE collection 
        SET name = :name, description = :description, started_at = :started_at, finished_at = :finished_at 
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $collectionId,
        'name' => $name,
        'description' => $description,
        'started_at' => $_POST['started_at'],
        'finished_at' => $_POST['ended_at'] ?? null,
    ]);

    // Remove all paintings from the collection
    $stmt = $pdo->prepare("DELETE FROM collection_painting WHERE collection_id = :collection_id");
    $stmt->execute(['collection_id' => $collectionId]);

    // Add selected paintings back in order
    // Fix: Only add paintings that actually exist (avoid empty values)
    $paintingIds = array_unique($paintingIds); // Remove duplicates
    $position = 1;
    foreach ($paintingIds as $paintingId) {
        if (!$paintingId) continue;
        $stmt = $pdo->prepare("
            INSERT INTO collection_painting (painting_id, collection_id, position) 
            VALUES (:painting_id, :collection_id, :position)
        ");
        $stmt->execute([
            'painting_id' => $paintingId,
            'collection_id' => $collectionId,
            'position' => $position++,
        ]);
    }

    $successMessage = "Collection updated successfully!";
  }
}

// Fetch all paintings with their first image (fetch all thumbnails, order by position, pick the first)
$stmt = $pdo->prepare("
    SELECT 
        p.id AS painting_id,
        p.title AS painting_title,
        (
            SELECT pi2.file_path
            FROM painting_image pi2
            WHERE pi2.painting_id = p.id
            ORDER BY pi2.position ASC
            LIMIT 1
        ) AS image_path
    FROM painting p
");
$stmt->execute();
$paintings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current paintings in the collection
$stmt = $pdo->prepare("
    SELECT 
        p.id AS painting_id,
        (
            SELECT pi2.file_path
            FROM painting_image pi2
            WHERE pi2.painting_id = p.id
            ORDER BY pi2.position ASC
            LIMIT 1
        ) AS image_path
    FROM collection_painting cp
    JOIN painting p ON cp.painting_id = p.id
    WHERE cp.collection_id = :collection_id
");
$stmt->execute(['collection_id' => $collectionId]);
$currentPaintings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Rediger sammling</title>
    <link rel="apple-touch-icon" sizes="180x180" href="../img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../img/favicon/favicon-16x16.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/form.css">
</head>
<body>
<div class="form-head">
  <h1>Rediger sammling</h1>
  <a href="../index.php#collections-anchor">Tilbake</a>
</div>

<?php if ($successMessage): ?>
  <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
  <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST">
  <label for="name">Navn på samling</label>
  <input id="name" name="name" value="<?= htmlspecialchars($collection['name']) ?>" placeholder="Navn på samling" required><br>
  <label for="description">Beskrivelse</label>
  <textarea id="description" name="description" placeholder="Beskrivelse"><?= htmlspecialchars($collection['description']) ?></textarea><br>
  <label for="started_at">Startdato</label>
  <input id="started_at" name="started_at" type="date" value="<?= htmlspecialchars($collection['started_at']) ?>" placeholder="Startdato"><br>
  <label for="ended_at">Sluttdato</label>
  <input id="ended_at" name="ended_at" type="date" value="<?= htmlspecialchars($collection['finished_at']) ?>" placeholder="Sluttdato"><br>

  <label>Velg malerier</label>
  <div id="paintingPreview" style="display: flex; flex-wrap: wrap; gap: 10px;">
    <?php
      $currentPaintingIds = array_column($currentPaintings, 'painting_id');
      foreach ($paintings as $painting):
        $isSelected = in_array($painting['painting_id'], $currentPaintingIds);
    ?>
      <div class="painting-thumb<?= $isSelected ? ' highlight' : '' ?>"
           data-painting-id="<?= htmlspecialchars($painting['painting_id']) ?>"
           style="text-align: center; width: 120px; cursor: pointer;">
        <img src="../uploads/<?= htmlspecialchars($painting['image_path']) ?>"
          alt="<?= htmlspecialchars($painting['painting_title']) ?>" width="100">
        <br>
        <span><?= htmlspecialchars($painting['painting_title']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div id="selectedPaintings">
    <?php foreach ($currentPaintingIds as $pid): ?>
      <input type="hidden" name="picture_ids[]" value="<?= htmlspecialchars($pid) ?>">
    <?php endforeach; ?>
  </div>
  <button type="submit">Oppdater samling</button>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const preview = document.getElementById('paintingPreview');
    const selectedPaintings = document.getElementById('selectedPaintings');
    // Use a Set to track selected ids
    const selected = new Set();
    // Initialize with current selected
    selectedPaintings.querySelectorAll('input[name="picture_ids[]"]').forEach(function(input) {
      selected.add(input.value);
    });

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