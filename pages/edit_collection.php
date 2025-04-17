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
$stmt = $pdo->prepare("SELECT * FROM collections WHERE id = :id AND user_id = :user_id");
$stmt->execute(['id' => $collectionId, 'user_id' => $_SESSION['user_id']]);
$collection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$collection) {
    die("Collection not found.");
}

// Handle updating collection name, description, and adding pictures
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pictureIds = $_POST['picture_ids'] ?? []; // Get picture IDs to add to collection

    if (empty($name)) {
        $errors[] = "Collection name is required.";
    }

    if (empty($errors)) {
        // Update collection data in the database
        $stmt = $pdo->prepare("UPDATE collections SET name = :name, description = :description WHERE id = :id");
        $stmt->execute([
            'id' => $collectionId,
            'name' => $name,
            'description' => $description,
        ]);

        // Add pictures to the collection
        foreach ($pictureIds as $position => $pictureId) {
            $stmt = $pdo->prepare("INSERT INTO collection_pictures (collection_id, picture_id, position) VALUES (:collection_id, :picture_id, :position)");
            $stmt->execute([
                'collection_id' => $collectionId,
                'picture_id' => $pictureId,
                'position' => $position + 1,
            ]);
        }

        $successMessage = "Collection updated successfully!";
    }
}

// Fetch all pictures uploaded by the user
$stmt = $pdo->prepare("SELECT * FROM pictures WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$pictures = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current pictures in the collection
$stmt = $pdo->prepare("SELECT picture_id FROM collection_pictures WHERE collection_id = :collection_id");
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

    <h3>Choose pictures to Add</h3>
    <select name="picture_ids[]" multiple>
        <?php foreach ($pictures as $picture): ?>
            <option value="<?= $picture['id'] ?>" <?= in_array($picture['id'], $currentpictures) ? 'disabled' : '' ?>>
                <?= htmlspecialchars($picture['filename']) ?>
            </option>
        <?php endforeach; ?>
    </select><br>

    <button type="submit">Update Collection</button>
</form>