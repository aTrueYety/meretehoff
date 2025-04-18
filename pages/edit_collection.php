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
$stmt = $pdo->prepare("SELECT * FROM Collections WHERE id = :id");
$stmt->execute(['id' => $collectionId]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$collection) {
  die("Collection not found.");
}

// Handle updating collection name, description, and adding/removing pictures
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $removePictureIds = $_POST['remove_picture_ids'] ?? [];
  $pictureIds = $_POST['picture_ids'] ?? [];

  if (empty($name)) {
    $errors[] = "Collection name is required.";
  }

  if (empty($errors)) {
    // Update collection data in the database
    $stmt = $pdo->prepare("
        UPDATE Collections 
        SET name = :name, description = :description, started_at = :started_at, ended_at = :ended_at 
        WHERE id = :id
    ");
    $stmt->execute([
        'id' => $collectionId,
        'name' => $name,
        'description' => $description,
        'started_at' => $_POST['started_at'],
        'ended_at' => $_POST['ended_at'] ?? null,
    ]);

    // Remove selected pictures
    foreach ($removePictureIds as $removePictureId) {
      $stmt = $pdo->prepare("DELETE FROM CollectionPaintings WHERE collection_id = :collection_id AND painting_id = :painting_id");
      $stmt->execute([
        'collection_id' => $collectionId,
        'painting_id' => $removePictureId
      ]);
    }

    // Add selected pictures
    foreach ($pictureIds as $position => $pictureId) {
      $stmt = $pdo->prepare("INSERT INTO CollectionPaintings (collection_id, painting_id, position) VALUES (:collection_id, :painting_id, :position)");
      $stmt->execute([
        'collection_id' => $collectionId,
        'painting_id' => $pictureId,
        'position' => $position + 1,
      ]);
    }

    $successMessage = "Collection updated successfully!";
  }
}

// Fetch all pictures uploaded
$stmt = $pdo->prepare("SELECT * FROM Paintings");
$stmt->execute();
$pictures = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current pictures in the collection
$stmt = $pdo->prepare("SELECT painting_id FROM CollectionPaintings WHERE collection_id = :collection_id");
$stmt->execute(['collection_id' => $collectionId]);
$currentpictures = $stmt->fetchAll(PDO::FETCH_COLUMN);
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
  <input name="ended_at" type="datetime-local" value="<?= htmlspecialchars($collection['ended_at']) ?>" placeholder="End Date"><br>

  <h3>Choose pictures to Add or Remove</h3>
  <div style="display: flex; flex-wrap: wrap; gap: 10px;">
    <?php foreach ($pictures as $picture): ?>
      <div style="text-align: center; width: 120px;">
        <img src="../uploads/<?= htmlspecialchars($picture['file_path']) ?>"
          alt="<?= htmlspecialchars($picture['file_path']) ?>" width="100"
          style="<?= in_array($picture['id'], $currentpictures) ? 'opacity: 0.5;' : '' ?>">
        <br>
        <?php if (!in_array($picture['id'], $currentpictures)): ?>
          <input type="checkbox" name="picture_ids[]" value="<?= $picture['id'] ?>"> Add
        <?php else: ?>
          <input type="checkbox" name="remove_picture_ids[]" value="<?= $picture['id'] ?>"> Remove
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="submit">Update Collection</button>
</form>

<h3>Current pictures in This Collection</h3>
<div style="display: flex; flex-wrap: wrap; gap: 10px;">
  <?php foreach ($currentpictures as $pictureId): ?>
    <?php
    $stmt = $pdo->prepare("SELECT * FROM Paintings WHERE id = :id");
    $stmt->execute(['id' => $pictureId]);
    $picture = $stmt->fetch(PDO::FETCH_ASSOC);
    ?>
    <div style="text-align: center; width: 120px;">
      <img src="../uploads/<?= htmlspecialchars($picture['file_path']) ?>" alt="<?= htmlspecialchars($picture['file_path']) ?>" width="100">
    </div>
  <?php endforeach; ?>
</div>