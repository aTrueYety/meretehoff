<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Fetch all collections with pictures
$stmt = $pdo->query("
    SELECT 
        c.id AS collection_id,
        c.name AS collection_name,
        p.id AS picture_id,
        p.file_path AS filename,
        p.title,
        p.price,
        p.description,
        p.size_v,
        p.size_h,
        p.created_at,
        cp.position
    FROM Collections c
    LEFT JOIN CollectionPaintings cp ON c.id = cp.collection_id
    LEFT JOIN Paintings p ON cp.painting_id = p.id
    ORDER BY c.id, cp.position ASC
");

$collections = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $cid = htmlspecialchars($row['collection_id']);
  if (!isset($collections[$cid])) {
    $collections[$cid] = [
      'name' => htmlspecialchars($row['collection_name']),
      'pictures' => []
    ];
  }
  if ($row['picture_id']) {
    $collections[$cid]['pictures'][] = [
      'id' => htmlspecialchars($row['picture_id']),
      'title' => htmlspecialchars($row['title']),
      'filename' => htmlspecialchars($row['filename']),
      'price' => htmlspecialchars($row['price']),
      'description' => htmlspecialchars($row['description']),
      'size_v' => htmlspecialchars($row['size_v']),
      'size_h' => htmlspecialchars($row['size_h']),
      'date' => htmlspecialchars($row['created_at']),
    ];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>My Collections</title>
  <link rel="stylesheet" href="css/index.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
</head>

<body>
  <div class="header">
  </div>

  <div class="body">
    
    <div class="about">
      <h1>Om meg og kunsten min</h1>
      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla placerat mi purus, vel interdum sem finibus vel.
        Nam laoreet laoreet nunc sed elementum. Aenean a enim ut est ultrices dictum in ac libero. Donec mattis, orci
        vitae faucibus suscipit, erat lectus tempus erat, vel pulvinar urna diam in magna. Vestibulum ante ipsum primis
        in faucibus orci luctus et ultrices posuere cubilia curae; Phasellus rutrum erat aliquet lacus viverra rhoncus.
        Praesent faucibus rutrum ligula, vel ullamcorper nisl venenatis non. Nunc efficitur sagittis quam. Aenean at
        faucibus mi. Etiam venenatis mattis elementum. Aliquam accumsan turpis erat, in vulputate dui maximus id.</p>
    </div>

    <div class="collections">
      <?php foreach ($collections as $collection): ?>
        <div class="collection">
          <h2><?= $collection['name'] ?></h2>
          <div class="gallery">
            <?php foreach ($collection['pictures'] as $pic): ?>
              <div class="picture"
                onclick="openModal('<?= $pic['title'] ?>', 'uploads/<?= $pic['filename'] ?>', '<?= $pic['price'] ?>', '<?= $pic['description'] ?>', '<?= $pic['size_v'] ?> x <?= $pic['size_h'] ?>', '<?= $pic['date'] ?>')">
                <img src="uploads/<?= $pic['filename'] ?>" alt="<?= $pic['title'] ?>">
                <div class="overlay">
                  <p class="title"><?= $pic['title'] ?></p>
                </div>
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
    </div>

    <div class="contact">
      <h2>Kontakt</h2>
      <img src="img/merete.jpeg" alt="Merete Hoff" class="profile-pic">
      <div class="contact-details">
        <div>
          <p>Navn</p>
          <p>Merete Hoff</p>
        </div>
        <div>
          <p>Tlf</p>
          <p>+47 99999999</p>
        </div>
        <div>
          <p>E-post</p>
          <p>post@gmail.com</p>
        </div>
      </div>
    </div>
  </div>

  <div class="footer">
    <p>© 2023 Merete Hoff</p>
  </div>

  <div id="modal" class="modal">
    <div class="modal-backdrop" onclick="closeModal()"></div>
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <img id="modal-image" src="" alt="">
      <p id="modal-title"></p>
      <p id="modal-price"></p>
      <p id="modal-description"></p>
      <p id="modal-size"></p>
      <p id="modal-date"></p>
    </div>
  </div>

  <script>
    function openModal(title, imagePath, price, description, size, date) {
      document.getElementById('modal-title').textContent = title;
      document.getElementById('modal-image').src = imagePath;
      document.getElementById('modal-price').textContent = `Price: ${price}`;
      document.getElementById('modal-description').textContent = `Description: ${description}`;
      document.getElementById('modal-size').textContent = `Size: ${size}`;
      document.getElementById('modal-date').textContent = `Date: ${date}`;
      
      document.getElementById('modal').style.display = 'block';
    }

    function closeModal() {
      document.getElementById('modal').style.display = 'none';
    }
  </script>
</body>

</html>