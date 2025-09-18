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
    // Validate files
    if (!isset($_FILES['images'])) {
        $errors[] = "No files uploaded.";
    } else {
        $files = $_FILES['images'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB max size

        $uploadedFiles = [];
        for ($i = 0; $i < count($files['name']); $i++) {
            // Skip empty file inputs
            if (empty($files['name'][$i])) {
                continue;
            }

            if (!in_array($files['type'][$i], $allowedTypes)) {
                $errors[] = "File {$files['name'][$i]} is not a valid image type.";
                continue;
            }

            if ($files['size'][$i] > $maxSize) {
                $errors[] = "File {$files['name'][$i]} exceeds the maximum size of 5MB.";
                continue;
            }

            // Generate unique filename to prevent overwriting
            $filename = uniqid('', true) . '.' . pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $uploadDir = __DIR__ . '/../uploads/';
            $uploadPath = $uploadDir . $filename;

            if (move_uploaded_file($files['tmp_name'][$i], $uploadPath)) {
                $uploadedFiles[] = $filename;
            } else {
                $errors[] = "Failed to upload file {$files['name'][$i]}.";
            }
        }

        // Check if there are any errors
        if (empty($errors) && !empty($uploadedFiles)) {
            // Generate a UUID for the painting
            $paintingId = bin2hex(random_bytes(16)); // Generate a 36-character UUID

            // Save painting details to the database
            $stmt = $pdo->prepare("
                INSERT INTO painting (id, title, price, description, size_v, size_h, finished_at) 
                VALUES (:id, :title, :price, :description, :size_v, :size_h, :finished_at)
            ");
            $stmt->execute([
                'id' => $paintingId,
                'title' => $_POST['title'] ?? 'Untitled', // Optional title input
                'price' => $_POST['price'],
                'description' => $_POST['description'] ?? null, // Optional description input
                'size_v' => $_POST['size_v'] ?? null, // Optional vertical size input
                'size_h' => $_POST['size_h'] ?? null, // Optional horizontal size input
                'finished_at' => $_POST['finished_at'], // Finished date input
            ]);

            // Save each image and link it to the painting
            $position = 1;
            foreach ($uploadedFiles as $filename) {
                $stmt = $pdo->prepare("
                    INSERT INTO painting_image (painting_id, position, title, file_path) 
                    VALUES (:painting_id, :position, :title, :file_path)
                ");
                $stmt->execute([
                    'painting_id' => $paintingId,
                    'position' => $position++,
                    'title' => $_POST['title'] ?? 'Untitled',
                    'file_path' => $filename,
                ]);
            }

            $successMessage = "Images uploaded successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Opprett maleri</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/form.css">
</head>
<body>
<div class="form-head">
  <h1>Opprett maleri</h1>
  <a href="../index.php">Tilbake</a>
</div>

<?php if ($successMessage): ?>
    <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data" id="uploadForm">
    <label for="title">Tittel</label>
    <input id="title" name="title" placeholder="Tittel" required><br>
    <label for="price">Pris</label>
    <input id="price" name="price" type="number" step="0.01" placeholder="Pris" required><br>
    <label for="description">Beskrivelse</label>
    <textarea id="description" name="description" placeholder="Beskrivelse"></textarea><br>
    <label for="size_v">Høyde (cm)</label>
    <input id="size_v" name="size_v" type="number" step="0.01" placeholder="Høyde (cm)"><br>
    <label for="size_h">Bredde (cm)</label>
    <input id="size_h" name="size_h" type="number" step="0.01" placeholder="Bredde (cm)"><br>
    <label for="finished_at">Dato ferdigstilt</label>
    <input id="finished_at" name="finished_at" type="date" placeholder="Dato ferdigstilt" required><br>
    
    <label>Bilder</label>
    <div id="fileInputs">
        <input type="file" name="images[]" required><br>
    </div>
    <button type="button" onclick="addFileInput()">Legg til et bilde til</button><br>
    <button type="submit">Opprett maleri</button>
</form>

<script>
    function addFileInput() {
        const fileInputs = document.getElementById('fileInputs');
        const newInput = document.createElement('input');
        newInput.type = 'file';
        newInput.name = 'images[]';
        fileInputs.appendChild(newInput);
        fileInputs.appendChild(document.createElement('br'));
    }
</script>
</body>
</html>
