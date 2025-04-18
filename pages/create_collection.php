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
        $stmt = $pdo->prepare("
            INSERT INTO Collections (name, description, started_at, ended_at) 
            VALUES (:name, :description, :started_at, :ended_at)
        ");
        $stmt->execute([
            'name' => $name,
            'description' => $description,
            'started_at' => $_POST['started_at'] ?? null,
            'ended_at' => $_POST['ended_at'] ?? null,
        ]);

        // Get the collection ID
        $collectionId = $pdo->lastInsertId();

        // Insert pictures into the collection
        foreach ($pictureIds as $position => $pictureId) {
            $stmt = $pdo->prepare("INSERT INTO CollectionPaintings (collection_id, painting_id) VALUES (:collection_id, :painting_id)");
            $stmt->execute([
                'collection_id' => $collectionId,
                'painting_id' => $pictureId,
            ]);
        }

        $successMessage = "Collection created successfully!";
    }
}

// Fetch all pictures uploaded by the user
$stmt = $pdo->prepare("SELECT * FROM Paintings");
$stmt->execute();
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
    <input name="started_at" type="datetime-local" placeholder="Start Date"><br>
    <input name="ended_at" type="datetime-local" placeholder="End Date"><br>

    <h3>Choose pictures to Add</h3>
    <select name="picture_ids[]" multiple>
        <?php foreach ($pictures as $picture): ?>
            <option value="<?= $picture['id'] ?>"><?= htmlspecialchars($picture['file_path']) ?></option>
        <?php endforeach; ?>
    </select><br>

    <button type="submit">Create Collection</button>
</form>
