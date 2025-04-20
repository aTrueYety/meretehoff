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
        i.file_path AS image_path
    FROM painting p
    LEFT JOIN painting_image pi ON p.id = pi.painting_id AND pi.position = 1
    LEFT JOIN image i ON pi.image_id = i.id
");
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

    <h3>Choose paintings to Add</h3>
    <div class="painting-preview">
        <?php foreach ($pictures as $picture): ?>
            <label style="display: inline-block; margin: 10px; text-align: center;">
                <input type="checkbox" name="picture_ids[]" value="<?= $picture['painting_id'] ?>">
                <?php if (!empty($picture['image_path'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($picture['image_path']) ?>" alt="Preview" style="width:100px;height:auto;display:block;">
                <?php else: ?>
                    <div style="width:100px;height:100px;background-color:#ccc;display:flex;align-items:center;justify-content:center;">
                        No Image
                    </div>
                <?php endif; ?>
                <span><?= htmlspecialchars($picture['painting_title'] ?? 'Untitled') ?></span>
            </label>
        <?php endforeach; ?>
    </div>

    <button type="submit">Create Collection</button>
</form>
