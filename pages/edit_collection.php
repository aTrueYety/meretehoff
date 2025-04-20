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

// Handle updating collection name, description, and adding/removing paintings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $removePaintingIds = $_POST['remove_picture_ids'] ?? [];
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

    // Remove selected paintings
    foreach ($removePaintingIds as $removePaintingId) {
      $stmt = $pdo->prepare("DELETE FROM collection_painting WHERE collection_id = :collection_id AND painting_id = :painting_id");
      $stmt->execute([
        'collection_id' => $collectionId,
        'painting_id' => $removePaintingId
      ]);
    }

    // Add selected paintings
    foreach ($paintingIds as $position => $paintingId) {
      $stmt = $pdo->prepare("
          INSERT INTO collection_painting (painting_id, collection_id, position) 
          VALUES (:painting_id, :collection_id, :position)
      ");
      $stmt->execute([
          'painting_id' => $paintingId,
          'collection_id' => $collectionId,
          'position' => $position + 1,
      ]);
    }

    $successMessage = "Collection updated successfully!";
  }
}

// Fetch all paintings with their first image
$stmt = $pdo->prepare("
    SELECT 
        p.id AS painting_id,
        p.title AS painting_title,
        i.file_path AS image_path
    FROM painting p
    LEFT JOIN painting_image pi ON p.id = pi.painting_id AND pi.position = 1
    LEFT JOIN image i ON pi.image_id = i.id
");
$stmt->execute();
$paintings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current paintings in the collection
$stmt = $pdo->prepare("
    SELECT 
        p.id AS painting_id,
        i.file_path AS image_path
    FROM collection_painting cp
    JOIN painting p ON cp.painting_id = p.id
    LEFT JOIN painting_image pi ON p.id = pi.painting_id AND pi.position = 1
    LEFT JOIN image i ON pi.image_id = i.id
    WHERE cp.collection_id = :collection_id
");
$stmt->execute(['collection_id' => $collectionId]);
$currentPaintings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Edit Collection</h1>

<?php if ($successMessage): ?>
  <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
  <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST">
  <input name="name" value="<?= htmlspecialchars($collection['name']) ?>" placeholder="Collection Name" required><br>
  <textarea name="description" placeholder="Description"><?= htmlspecialchars($collection['description']) ?></textarea><br>
  <input name="started_at" type="datetime-local" value="<?= htmlspecialchars($collection['started_at']) ?>" placeholder="Start Date"><br>
  <input name="ended_at" type="datetime-local" value="<?= htmlspecialchars($collection['finished_at']) ?>" placeholder="End Date"><br>

  <h3>Choose paintings to Add or Remove</h3>
  <div style="display: flex; flex-wrap: wrap; gap: 10px;">
    <?php foreach ($paintings as $painting): ?>
      <div style="text-align: center; width: 120px;">
        <img src="../uploads/<?= htmlspecialchars($painting['image_path']) ?>"
          alt="<?= htmlspecialchars($painting['painting_title']) ?>" width="100"
          style="<?= in_array($painting['painting_id'], array_column($currentPaintings, 'painting_id')) ? 'opacity: 0.5;' : '' ?>">
        <br>
        <?php if (!in_array($painting['painting_id'], array_column($currentPaintings, 'painting_id'))): ?>
          <input type="checkbox" name="picture_ids[]" value="<?= $painting['painting_id'] ?>"> Add
        <?php else: ?>
          <input type="checkbox" name="remove_picture_ids[]" value="<?= $painting['painting_id'] ?>"> Remove
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="submit">Update Collection</button>
</form>

<h3>Current paintings in This Collection</h3>
<div style="display: flex; flex-wrap: wrap; gap: 10px;">
  <?php foreach ($currentPaintings as $painting): ?>
    <div style="text-align: center; width: 120px;">
      <img src="../uploads/<?= htmlspecialchars($painting['image_path']) ?>" alt="Painting" width="100">
    </div>
  <?php endforeach; ?>
</div>