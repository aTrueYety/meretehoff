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
                    // Save the image to the database
                    $imageId = bin2hex(random_bytes(16));
                    $stmt = $pdo->prepare("
                        INSERT INTO image (id, title, file_path) 
                        VALUES (:id, :title, :file_path)
                    ");
                    $stmt->execute([
                        'id' => $imageId,
                        'title' => $location, // Use location as the title for now
                        'file_path' => $filename,
                    ]);

                    // Link the image to the exhibition
                    $stmt = $pdo->prepare("
                        INSERT INTO exhibition_image (image_id, exhibition_id, position) 
                        VALUES (:image_id, :exhibition_id, :position)
                    ");
                    $stmt->execute([
                        'image_id' => $imageId,
                        'exhibition_id' => $exhibitionId,
                        'position' => $position++,
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

<h1>Create Exhibition</h1>

<?php if ($successMessage): ?>
    <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data">
    <input name="location" placeholder="Location" required><br>
    <textarea name="description" placeholder="Description"></textarea><br>
    <input name="started_at" type="datetime-local" placeholder="Start Date"><br>
    <input name="finished_at" type="datetime-local" placeholder="End Date"><br>

    <h3>Upload Images for the Exhibition</h3>
    <input type="file" name="new_images[]" multiple required><br>

    <button type="submit">Create Exhibition</button>
</form>
