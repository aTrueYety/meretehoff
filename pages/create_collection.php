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
        // Insert the collection into the database
        $stmt = $pdo->prepare("INSERT INTO collections (user_id, name, description) VALUES (:user_id, :name, :description)");
        $stmt->execute([
            'user_id' => $_SESSION['user_id'],
            'name' => $name,
            'description' => $description,
        ]);

        // Get the collection ID
        $collectionId = $pdo->lastInsertId();

        // Insert pictures into the collection
        foreach ($pictureIds as $position => $pictureId) {
            $stmt = $pdo->prepare("INSERT INTO collection_pictures (collection_id, picture_id, position) VALUES (:collection_id, :picture_id, :position)");
            $stmt->execute([
                'collection_id' => $collectionId,
                'picture_id' => $pictureId,
                'position' => $position + 1,  // Position pictures in order
            ]);
        }

        $successMessage = "Collection created successfully!";
    }
}

// Fetch all pictures uploaded by the user
$stmt = $pdo->prepare("SELECT * FROM pictures WHERE user_id = :user_id");
$stmt->execute(['user_id' => $_SESSION['user_id']]);
$pictures = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1>Create Collection</h1>

<?php if ($successMessage): ?>
    <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST">
    <input name="name" placeholder="Collection Name" required><br>
    <textarea name="description" placeholder="Description"></textarea><br>

    <h3>Choose pictures to Add</h3>
    <select name="picture_ids[]" multiple>
        <?php foreach ($pictures as $picture): ?>
            <option value="<?= $picture['id'] ?>"><?= htmlspecialchars($picture['filename']) ?></option>
        <?php endforeach; ?>
    </select><br>

    <button type="submit">Create Collection</button>
</form>
