<?php
session_start();
require_once __DIR__ . '/../db/db.php';

// Only allow logged-in users
if (empty($_SESSION['username'])) {
  header('Location: login.php');
  exit;
}

$exhibition = null;
$error = '';
$success = '';

if (!isset($_GET['id'])) {
  $error = 'Ugyldig utstillings-ID.';
} else {
  $id = $_GET['id'];

  // Fetch exhibition data FIRST
  $stmt = $pdo->prepare("SELECT * FROM exhibition WHERE id=?");
  $stmt->execute([$id]);
  $exhibition = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$exhibition) {
    $error = 'Utstillingen ble ikke funnet.';
  }

  // Handle exhibition update
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_image_id']) && !isset($_FILES['new_image'])) {
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $started_at = trim($_POST['started_at'] ?? '');
    $finished_at = trim($_POST['finished_at'] ?? '');

    if ($location === '' || $started_at === '') {
      $error = 'Sted og startdato må fylles ut.';
    } else {
      $stmt = $pdo->prepare("UPDATE exhibition SET location=?, description=?, started_at=?, finished_at=? WHERE id=?");
      if ($stmt->execute([$location, $description, $started_at, $finished_at, $id])) {
        $success = 'Utstillingen er oppdatert.';
      } else {
        $error = 'Kunne ikke oppdatere utstillingen.';
      }
    }
  }

  // Handle image deletion
  if (isset($_POST['delete_image_id'])) {
    $position = $_POST['delete_image_id'];
    // Get file_path before deleting
    $imgStmt = $pdo->prepare("SELECT file_path FROM exhibition_image WHERE exhibition_id=? AND position=?");
    $imgStmt->execute([$id, $position]);
    $imageData = $imgStmt->fetch(PDO::FETCH_ASSOC);
    $deleteStmt = $pdo->prepare("DELETE FROM exhibition_image WHERE exhibition_id=? AND position=?");
    if ($deleteStmt->execute([$id, $position])) {
      // Also delete the image file from the server
      if ($imageData) {
        $filePath = '../uploads/' . $imageData['file_path'];
        if (file_exists($filePath)) {
          unlink($filePath);
        }
      }
      $success = 'Bilde slettet.';
    } else {
      $error = 'Kunne ikke slette bildet.';
    }
  }

  // Handle a new image uploads
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_image']) && $_FILES['new_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($exhibition) { // Only proceed if exhibition exists
      $file = $_FILES['new_image'];
      $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
      $maxSize = 5 * 1024 * 1024; // 5MB

      if (!in_array($file['type'], $allowedTypes)) {
        $error = "Filen er ikke et gyldig bildeformat.";
      } elseif ($file['size'] > $maxSize) {
        $error = "Filen er for stor (maks 5MB).";
      } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "Det oppstod en feil ved opplasting av filen.";
      } else {
        $filename = uniqid('', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $uploadDir = __DIR__ . '/../uploads/';
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
          // Find max position for this exhibition
          $posStmt = $pdo->prepare("SELECT MAX(position) AS max_pos FROM exhibition_image WHERE exhibition_id=?");
          $posStmt->execute([$id]);
          $maxPos = $posStmt->fetch(PDO::FETCH_ASSOC)['max_pos'] ?? 0;
          $position = $maxPos + 1;

          $stmt = $pdo->prepare("INSERT INTO exhibition_image (exhibition_id, position, title, file_path) VALUES (?, ?, ?, ?)");
          $stmt->execute([$id, $position, $exhibition['location'], $filename]);

          $success = "Bilde lastet opp.";
        } else {
          $error = "Kunne ikke laste opp bildet.";
        }
      }
    } else {
      $error = "Utstillingen finnes ikke.";
    }
  }

  // Fetch exhibition images
  $images = [];
  if ($exhibition) {
    $imgStmt = $pdo->prepare("
            SELECT position, file_path
            FROM exhibition_image
            WHERE exhibition_id=?
            ORDER BY position
        ");
    $imgStmt->execute([$id]);
    $images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

$images_to_delete = [];
$images_to_add = [];
?>
<!DOCTYPE html>
<html lang="no">

<head>
  <meta charset="UTF-8">
  <title>Rediger utstilling</title>
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
      <h1>Rediger utstilling</h1>
  <a href="../index.php#exhibitions-anchor">Tilbake</a>
    </div>

    <?php if ($error): ?>
      <div style="color:red;"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($success): ?>
      <div style="color:green;"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($exhibition): ?>

      <form method="post">
        <h2>Fjern bilder</h2>
        <div style="display: flex; flex-direction: row; gap: 1rem;">
          <?php foreach ($images as $image): ?>
            <div style="display: flex; flex-direction: column; align-items: center;">
              <img src="../uploads/<?= htmlspecialchars($image['file_path']) ?>" alt="Utstillingsbilde"
                style="max-width: 200px; max-height: 200px;">
              <button type="submit" name="delete_image_id" value="<?= htmlspecialchars($image['position']) ?>"
                style="width: 100%;">Slett</button>
            </div>
          <?php endforeach; ?>
        </div>
      </form>

      <form method="post" enctype="multipart/form-data">
        <h2>Legg till bilder</h2>
        <input type="file" name="new_image" accept="image/*" required><br>
        <button type="submit">Last opp bilde</button>
      </form>

      <form method="post">
        <h2>Endre informasjon</h2>
        <label>Sted:<br>
          <input type="text" name="location" value="<?= htmlspecialchars($exhibition['location']) ?>" required>
        </label><br><br>
        <label>Beskrivelse:<br>
          <textarea name="description" rows="4"><?= htmlspecialchars($exhibition['description']) ?></textarea>
        </label><br><br>
        <label>Startdato:<br>
          <input type="date" name="started_at" value="<?= htmlspecialchars($exhibition['started_at']) ?>" required>
        </label><br><br>
        <label>Sluttdato:<br>
          <input type="date" name="finished_at" value="<?= htmlspecialchars($exhibition['finished_at']) ?>">
        </label><br><br>
        <button type="submit">Lagre endringer</button>
      </form>
    </div>
  <?php endif; ?>
  </div>
</body>

</html>