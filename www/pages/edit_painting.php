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
  $removeImagePositions = $_POST['remove_image_positions'] ?? [];
  foreach ($removeImagePositions as $position) {
    $stmt = $pdo->prepare("DELETE FROM painting_image WHERE painting_id = :painting_id AND position = :position");
    $stmt->execute(['painting_id' => $paintingId, 'position' => $position]);
  }

  // Handle new image uploads
  if (!empty($_FILES['new_images']['name'][0])) {
    $files = $_FILES['new_images'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB max size

    // Find the next available position
    $stmt = $pdo->prepare("SELECT MAX(position) AS max_pos FROM painting_image WHERE painting_id = :painting_id");
    $stmt->execute(['painting_id' => $paintingId]);
    $maxPos = (int) ($stmt->fetchColumn() ?? 0);
    $position = $maxPos + 1;

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
        // Save the image to the painting_image table
        $stmt = $pdo->prepare("
                    INSERT INTO painting_image (painting_id, position, title, file_path) 
                    VALUES (:painting_id, :position, :title, :file_path)
                ");
        $stmt->execute([
          'painting_id' => $paintingId,
          'position' => $position++,
          'title' => $title,
          'file_path' => $filename,
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

// Fetch existing painting data
$stmt = $pdo->prepare("SELECT * FROM painting WHERE id = :id");
$stmt->execute(['id' => $paintingId]);
$painting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$painting) {
  die("Painting not found.");
}

// Fetch existing images for the painting
$stmt = $pdo->prepare("
    SELECT position, file_path 
    FROM painting_image
    WHERE painting_id = :painting_id
    ORDER BY position
");
$stmt->execute(['painting_id' => $paintingId]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Rediger maleri</title>
  <link rel="apple-touch-icon" sizes="180x180" href="../img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../img/favicon/favicon-16x16.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/form.css">
</head>

<body>
  <div>
    <div class="form-head">
      <h1>Rediger maleri</h1>
      <a href="javascript:history.back()">Tilbake</a>
    </div>

    <?php if ($successMessage): ?>
      <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
      <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data">
      <label for="title">Tittel</label>
      <input id="title" name="title" value="<?= htmlspecialchars($painting['title']) ?>" placeholder="Tittel"
        required><br>
      <label for="price">Pris</label>
      <input id="price" name="price" type="number" step="1" value="<?= htmlspecialchars($painting['price']) ?>"
        placeholder="Pris"><br>
      <label for="description">Beskrivelse</label>
      <textarea id="description" name="description"
        placeholder="Beskrivelse"><?= htmlspecialchars($painting['description']) ?></textarea><br>
      <label for="size_v">Høyde (cm)</label>
      <input id="size_v" name="size_v" type="number" step="0.01" value="<?= htmlspecialchars($painting['size_v']) ?>"
        placeholder="Høyde (cm)"><br>
      <label for="size_h">Bredde (cm)</label>
      <input id="size_h" name="size_h" type="number" step="0.01" value="<?= htmlspecialchars($painting['size_h']) ?>"
        placeholder="Bredde (cm)"><br>
      <label for="finished_at">Ferdigstilt dato</label>
      <input id="finished_at" name="finished_at" type="date" value="<?= htmlspecialchars($painting['finished_at']) ?>"
        placeholder="Ferdigstilt dato"><br>
      <label>
        <input type="checkbox" name="is_sold" <?= $painting['is_sold'] ? 'checked' : '' ?>> Solgt
      </label><br>

      <label>Fjern bilder</label>
      <div id="imagePreview" style="display: flex; flex-wrap: wrap; gap: 10px;">
        <?php foreach ($images as $image): ?>
          <div class="image-thumb" data-image-position="<?= htmlspecialchars($image['position']) ?>"
            style="text-align: center; width: 120px; cursor: pointer;">
            <img src="../uploads/<?= htmlspecialchars($image['file_path']) ?>" alt="Image" width="100">
          </div>
        <?php endforeach; ?>
      </div>
      <div id="removeImages"></div>

      <label>Legg til bilder</label>
      <input type="file" name="new_images[]" multiple><br>

      <button type="submit">Oppdater maleri</button>
    </form>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const preview = document.getElementById('imagePreview');
        const removeImages = document.getElementById('removeImages');
        const selected = new Set();

        preview.querySelectorAll('.image-thumb').forEach(function (thumb) {
          thumb.addEventListener('click', function () {
            const pos = thumb.getAttribute('data-image-position');
            if (selected.has(pos)) {
              selected.delete(pos);
              thumb.classList.remove('highlight');
              // Remove hidden input
              const input = removeImages.querySelector('input[value="' + pos + '"]');
              if (input) input.remove();
            } else {
              selected.add(pos);
              thumb.classList.add('highlight');
              // Add hidden input
              const input = document.createElement('input');
              input.type = 'hidden';
              input.name = 'remove_image_positions[]';
              input.value = pos;
              removeImages.appendChild(input);
            }
          });
        });
      });
    </script>
    <style>
      .image-thumb.highlight {
        outline: 3px solid #dc3545;
        background: #ffeaea;
        border-radius: 6px;
      }
    </style>
</body>

</html>