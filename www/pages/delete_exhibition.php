<?php
session_start();
require_once __DIR__ . '/../db/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$id = $_GET['id'];

// Fetch all image file paths before deleting
$stmt = $pdo->prepare('SELECT file_path FROM exhibition_image WHERE exhibition_id = ?');
$stmt->execute([$id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($images) {
    $uploadsDir = realpath(__DIR__ . '/../uploads/');
    foreach ($images as $image) {
        $filePath = realpath(__DIR__ . '/../uploads/' . $image);
        // Only delete if file exists and is inside uploads directory
        if ($filePath && strpos($filePath, $uploadsDir) === 0 && file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

// Delete the exhibition record (exhibition_image rows will be deleted via ON DELETE CASCADE)
$stmt = $pdo->prepare('DELETE FROM exhibition WHERE id = ?');
$stmt->execute([$id]);

header('Location: ../index.php');
exit;
