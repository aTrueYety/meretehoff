<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../db/load_env.php';
loadEnv(__DIR__ . '/../../.env');

function generateUuidV4() {
  return sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
  );
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $expectedKey = $_ENV['REGISTER_SECRET'];
  $secretKey = $_POST['secret_key'] ?? '';


  if (!$username || !$password || !$secretKey) {
    $errors[] = "All fields are required.";
  } else {
    $stmt = $pdo->prepare("SELECT id FROM user WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
      $errors[] = "Username already taken.";
    } else {
      if ($secretKey !== $expectedKey) {
        $errors[] = "Invalid registration key.";
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $uuid = generateUuidV4();
        $stmt = $pdo->prepare("INSERT INTO user (id, username, password_hash) VALUES (:id, :username, :hash)");
        $stmt->execute(['id' => $uuid, 'username' => $username, 'hash' => $hash]);
        header("Location: login.php");
        exit;
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="no">

<head>
  <meta charset="UTF-8">
  <title>Registrer bruker</title>
  <link rel="apple-touch-icon" sizes="180x180" href="../img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="../img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="../img/favicon/favicon-16x16.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/form.css">
</head>

<body>
  <div class="form-head">
    <h1>Register</h1>
  <a href="../index.php">Tilbake</a>
  </div>

  <?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
  <?php endforeach; ?>

  <form method="POST">
    <input name="username" placeholder="Username" required><br>
    <input name="password" type="password" placeholder="Password" required><br>
    <input name="secret_key" placeholder="Registration Key" required><br>
    <button type="submit">Register</button>
  </form>
</body>

</html>