<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Fetch all collections with pictures
$stmt = $pdo->query("
    SELECT 
        c.id AS collection_id,
        c.name AS collection_name,
        p.id AS picture_id,
        p.filename,
        p.title,
        cp.position
    FROM collections c
    LEFT JOIN collection_pictures cp ON c.id = cp.collection_id
    LEFT JOIN pictures p ON cp.picture_id = p.id
    ORDER BY c.id, cp.position ASC
");

$collections = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cid = $row['collection_id'];
    if (!isset($collections[$cid])) {
        $collections[$cid] = [
            'name' => $row['collection_name'],
            'pictures' => []
        ];
    }
    if ($row['picture_id']) {
        $collections[$cid]['pictures'][] = [
            'id' => $row['picture_id'],
            'title' => $row['title'],
            'filename' => $row['filename']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Collections</title>
    <style>
        body { font-family: sans-serif; padding: 2rem; }
        .collection { margin-bottom: 3rem; }
        .collection h2 { margin-bottom: 1rem; }
        .gallery { display: flex; flex-wrap: wrap; gap: 10px; }
        .gallery img { max-width: 200px; border-radius: 5px; box-shadow: 0 0 5px #ccc; }
    </style>
</head>
<body>
    <h1>My Picture Collections</h1>

    <?php foreach ($collections as $collection): ?>
        <div class="collection">
            <h2><?= htmlspecialchars($collection['name']) ?></h2>
            <div class="gallery">
                <?php foreach ($collection['pictures'] as $pic): ?>
                    <div>
                        <img src="uploads/<?= htmlspecialchars($pic['filename']) ?>" alt="<?= htmlspecialchars($pic['title']) ?>">
                        <p><?= htmlspecialchars($pic['title']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (!empty($_SESSION['user_id'])): ?>
      <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
      <a href="pages/logout.php">Logout</a>
    <?php else: ?>
      <p><a href="pages/login.php">Login</a> or <a href="pages/register.php">Register</a></p>
    <?php endif; ?>
</body>
</html>
