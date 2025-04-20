<?php
require_once __DIR__ . '/../db/db.php';
session_start();

// Get painting ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid painting ID.');
}
$paintingId = intval($_GET['id']);

// Fetch painting details
$query = $pdo->prepare('SELECT * FROM Paintings WHERE id = :id');
$query->execute(['id' => $paintingId]);
$painting = $query->fetch(PDO::FETCH_ASSOC);

if (!$painting) {
    die('Painting not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($painting['title']); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($painting['title']); ?></h1>
    <img src="/../uploads/<?php echo htmlspecialchars($painting['file_path']); ?>" alt="<?php echo htmlspecialchars($painting['title']); ?>" style="max-width: 100%; height: auto;">
    <p><strong>Price:</strong> $<?php echo number_format($painting['price'], 2); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($painting['description'])); ?></p>
    <p><strong>Size:</strong> <?php echo htmlspecialchars($painting['size_v']) . ' x ' . htmlspecialchars($painting['size_h']); ?> cm</p>
    <p><strong>Created At:</strong> <?php echo htmlspecialchars($painting['created_at']); ?></p>
</body>
</html>
