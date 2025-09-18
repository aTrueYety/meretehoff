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

// Delete the collection (collection_painting rows will be deleted via ON DELETE CASCADE)
$stmt = $pdo->prepare('DELETE FROM collection WHERE id = ?');
$stmt->execute([$id]);

header('Location: ../index.php');
exit;
