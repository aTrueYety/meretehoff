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
    // Validate file
    if (!isset($_FILES['image'])) {
        $errors[] = "No file uploaded.";
    } else {
        $file = $_FILES['image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 5 * 1024 * 1024; // 5MB max size

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Only JPG, PNG, and GIF files are allowed.";
        }

        if ($file['size'] > $maxSize) {
            $errors[] = "The file is too large. Max size is 5MB.";
        }

        // Check if there are any errors
        if (empty($errors)) {
            // Generate unique filename to prevent overwriting
            $filename = uniqid('', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $uploadDir = __DIR__ . '/../uploads/';
            $uploadPath = $uploadDir . $filename;

            // Move file to the server
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Save image details to the database
                $stmt = $pdo->prepare("
                    INSERT INTO Paintings (title, file_path, price, description, size_v, size_h) 
                    VALUES (:title, :file_path, :price, :description, :size_v, :size_h)
                ");
                $stmt->execute([
                    'title' => $_POST['title'] ?? 'Untitled', // Optional title input
                    'file_path' => $filename,
                    'price' => $_POST['price'],
                    'description' => $_POST['description'] ?? null, // Optional description input
                    'size_v' => $_POST['size_v'] ?? null, // Optional vertical size input
                    'size_h' => $_POST['size_h'] ?? null, // Optional horizontal size input
                ]);

                $successMessage = "Image uploaded successfully!";
            } else {
                $errors[] = "Failed to upload the image.";
            }
        }
    }
}
?>

<h1>Upload Image</h1>

<?php if ($successMessage): ?>
    <p style="color: green"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST" enctype="multipart/form-data">
    <input name="title" placeholder="Image Title (Optional)" required><br>
    <input name="price" type="number" step="0.01" placeholder="Price" required><br>
    <textarea name="description" placeholder="Description"></textarea><br>
    <input name="size_v" type="number" step="0.01" placeholder="Vertical Size (cm)"><br>
    <input name="size_h" type="number" step="0.01" placeholder="Horizontal Size (cm)"><br>
    <input type="file" name="image" required><br>
    <button type="submit">Upload Image</button>
</form>
