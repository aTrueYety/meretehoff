<?php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../db/load_env.php';
loadEnv(__DIR__ . '/../.env');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $expectedKey = $_ENV['REGISTER_SECRET'];
    $secretKey = $_POST['secret_key'] ?? '';


    if (!$username || !$password || !$secretKey) {
        $errors[] = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        if ($stmt->fetch()) {
            $errors[] = "Username already taken.";
        } else {
            if ($secretKey !== $expectedKey) {
                $errors[] = "Invalid registration key.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO Users (username, password_hash) VALUES (:username, :hash)");
                $stmt->execute(['username' => $username, 'hash' => $hash]);
                header("Location: login.php");
                exit;
            }
        }
    }
}
?>

<h1>Register</h1>
<?php foreach ($errors as $error): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endforeach; ?>

<form method="POST">
    <input name="username" placeholder="Username" required><br>
    <input name="password" type="password" placeholder="Password" required><br>
    <input name="secret_key" placeholder="Registration Key" required><br>
    <button type="submit">Register</button>
</form>