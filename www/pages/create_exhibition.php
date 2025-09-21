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
  $location = trim($_POST['location'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $startedAt = $_POST['started_at'] ?? null;
  $finishedAt = $_POST['finished_at'] ?? null;

  if (empty($location)) {
    $errors[] = "Location is required.";
  }

  if (empty($errors)) {
    // Generate a UUID for the exhibition
    $exhibitionId = bin2hex(random_bytes(16)); // Generate a 36-character UUID

    // Insert the exhibition into the database
    $stmt = $pdo->prepare("
            INSERT INTO exhibition (id, location, description, started_at, finished_at) 
            VALUES (:id, :location, :description, :started_at, :finished_at)
        ");
    $stmt->execute([
      'id' => $exhibitionId,
      'location' => $location,
      'description' => $description,
      'started_at' => $startedAt,
      'finished_at' => $finishedAt,
    ]);

    // Handle new image uploads
    if (!empty($_FILES['new_images']['name'][0])) {
      $files = $_FILES['new_images'];
      $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
      $maxSize = 5 * 1024 * 1024; // 5MB max size

      $position = 1;
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
          // Save the image to the exhibition_image table
          $stmt = $pdo->prepare("
                        INSERT INTO exhibition_image (exhibition_id, position, title, file_path) 
                        VALUES (:exhibition_id, :position, :title, :file_path)
                    ");
          $stmt->execute([
            'exhibition_id' => $exhibitionId,
            'position' => $position++,
            'title' => $location, // Use location as the title for now
            'file_path' => $filename,
          ]);
        } else {
          $errors[] = "Failed to upload file {$files['name'][$i]}.";
        }
      }
    }

    if (empty($errors)) {
      $successMessage = "Exhibition created successfully!";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Opprett utstilling</title>
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
      <h1>Opprett utstilling</h1>
  <a href="../index.php#exhibitions-anchor">Tilbake</a>
    </div>

    <?php if ($successMessage): ?>
      <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
      <p style="color:red"><?= htmlspecialchars($error) ?></p>
    <?php endforeach; ?>

    <form method="POST" enctype="multipart/form-data">
      <label for="location">Sted</label>
      <input id="location" name="location" placeholder="Sted" required><br>
      <label for="description">Beskrivelse</label>
      <textarea id="description" name="description" placeholder="Beskrivelse"></textarea><br>
      <label for="started_at">Startdato</label>
      <input id="started_at" name="started_at" type="date" placeholder="Startdato"><br>
      <label for="finished_at">Sluttdato</label>
      <input id="finished_at" name="finished_at" type="date" placeholder="Sluttdato"><br>

      <label>Last opp bilder til utstillingen</label>
      <div id="fileInputs">
        <input type="file" name="new_images[]"><br>
      </div>
      <button type="button" onclick="addFileInput()">Legg til flere bilder</button><br>

      <button type="submit">Opprett utstilling</button>
    </form>
    <script>
      function addFileInput() {
        const fileInputs = document.getElementById('fileInputs');
        const newInput = document.createElement('input');
        newInput.type = 'file';
        newInput.name = 'new_images[]';
        fileInputs.appendChild(newInput);
        fileInputs.appendChild(document.createElement('br'));
      }
    </script>
</body>

</html>