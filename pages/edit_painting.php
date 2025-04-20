<?php
require_once __DIR__ . '/../db/db.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$paintingId = $_GET['id'] ?? null;
$errors = [];
$successMessage = '';

if (!$paintingId) {
    die("Painting ID is required.");
}

// Fetch existing painting data
$stmt = $pdo->prepare("SELECT * FROM painting WHERE id = :id");
$stmt->execute(['id' => $paintingId]);
$painting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$painting) {
    die("Painting not found.");
}

// Fetch existing images for the painting
$stmt = $pdo->prepare("
    SELECT i.id AS image_id, i.file_path 
    FROM painting_image pi
    JOIN image i ON pi.image_id = i.id
    WHERE pi.painting_id = :painting_id
    ORDER BY pi.position
");
$stmt->execute(['painting_id' => $paintingId]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $price = $_POST['price'] ?? null;
    $description = trim($_POST['description'] ?? '');
    $sizeV = $_POST['size_v'] ?? null;
    $sizeH = $_POST['size_h'] ?? null;
    $finishedAt = $_POST['finished_at'] ?? null;
    $isSold = isset($_POST['is_sold']) ? 1 : 0;

    if (empty($title)) {
        $errors[] = "Title is required.";
    }

    if (empty($errors)) {
        // Update painting details in the database
        $stmt = $pdo->prepare("
            UPDATE painting 
            SET title = :title, price = :price, description = :description, 
                size_v = :size_v, size_h = :size_h, finished_at = :finished_at, is_sold = :is_sold
            WHERE id = :id
        ");
        $stmt->execute([
            'id' => $paintingId,
            'title' => $title,
            'price' => $price,
            'description' => $description,
            'size_v' => $sizeV,
            'size_h' => $sizeH,
            'finished_at' => $finishedAt,
            'is_sold' => $isSold,
        ]);
    }

    // Handle image removal
    $removeImageIds = $_POST['remove_image_ids'] ?? [];
    foreach ($removeImageIds as $imageId) {
        $stmt = $pdo->prepare("DELETE FROM image WHERE id = :id");
        $stmt->execute(['id' => $imageId]);
    }

    // Handle new image uploads
    if (!empty($_FILES['new_images']['name'][0])) {
        $files = $_FILES['new_images'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB max size

        for ($i = 0; $i < count($files['name']); $i++) {
            if (!in_array($files['type'][$i], $allowedTypes)) {
                $errors[] = "File {$files['name'][$i]} is not a valid image type.";
                continue;
            }

            if ($files['size'][$i] > $maxSize) {
                $errors[] = "File {$files['name'][$i]} exceeds the maximum size of 5MB.";
                continue;
            }

            // Generate unique filename
            $filename = uniqid('', true) . '.' . pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $uploadDir = __DIR__ . '/../uploads/';
            $uploadPath = $uploadDir . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $uploadPath)) {
                // Save the image to the database
                $imageId = bin2hex(random_bytes(16));
                $stmt = $pdo->prepare("
                    INSERT INTO image (id, title, file_path) 
                    VALUES (:id, :title, :file_path)
                ");
                $stmt->execute([
                    'id' => $imageId,
                    'title' => $title,
                    'file_path' => $filename,
                ]);

                // Link the image to the painting
                $stmt = $pdo->prepare("
                    INSERT INTO painting_image (image_id, painting_id, position) 
                    VALUES (:image_id, :painting_id, :position)
                ");
                $stmt->execute([
                    'image_id' => $imageId,
                    'painting_id' => $paintingId,
                    'position' => $i + 1,
                ]);
            } else {
                $errors[] = "Failed to upload file {$files['name'][$i]}.";
            }
        }
    }

    if (empty($errors)) {
        $successMessage = "Painting updated successfully!";
    }
}
?>

<h1>Edit Painting</h1>

<?php if ($successMessage): ?>
    <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data">
    <input name="title" value="<?= htmlspecialchars($painting['title']) ?>" placeholder="Title" required><br>
    <input name="price" type="number" step="0.01" value="<?= htmlspecialchars($painting['price']) ?>" placeholder="Price"><br>
    <textarea name="description" placeholder="Description"><?= htmlspecialchars($painting['description']) ?></textarea><br>
    <input name="size_v" type="number" step="0.01" value="<?= htmlspecialchars($painting['size_v']) ?>" placeholder="Vertical Size (cm)"><br>
    <input name="size_h" type="number" step="0.01" value="<?= htmlspecialchars($painting['size_h']) ?>" placeholder="Horizontal Size (cm)"><br>
    <input name="finished_at" type="date" value="<?= htmlspecialchars($painting['finished_at']) ?>" placeholder="Finished Date"><br>
    <label>
        <input type="checkbox" name="is_sold" <?= $painting['is_sold'] ? 'checked' : '' ?>> Sold
    </label><br>

    <h3>Existing Images</h3>
    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
        <?php foreach ($images as $image): ?>
            <div style="text-align: center; width: 120px;">
                <img src="../uploads/<?= htmlspecialchars($image['file_path']) ?>" alt="Image" width="100">
                <br>
                <label>
                    <input type="checkbox" name="remove_image_ids[]" value="<?= $image['image_id'] ?>"> Remove
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <h3>Add New Images</h3>
    <input type="file" name="new_images[]" multiple><br>

    <button type="submit">Update Painting</button>
</form>
