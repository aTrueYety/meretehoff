<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Fetch all collections with paintings
$stmt = $pdo->query("
    SELECT 
        c.id AS collection_id,
        c.name AS collection_name,
        c.description AS collection_description,
        p.id AS painting_id,
        p.title AS painting_title,
        p.price,
        p.description AS painting_description,
        p.size_v,
        p.size_h,
        p.finished_at,
        cp.position,
        i.file_path AS filename
    FROM collection c
    LEFT JOIN collection_painting cp ON c.id = cp.collection_id
    LEFT JOIN painting p ON cp.painting_id = p.id
    LEFT JOIN painting_image pi ON p.id = pi.painting_id AND pi.position = 1
    LEFT JOIN image i ON pi.image_id = i.id
    ORDER BY c.id, cp.position ASC
");

$collections = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $cid = htmlspecialchars($row['collection_id']);
  if (!isset($collections[$cid])) {
    $collections[$cid] = [
      'name' => htmlspecialchars($row['collection_name']),
      'description' => htmlspecialchars($row['collection_description']),
      'paintings' => []
    ];
  }
  if ($row['painting_id']) {
    $collections[$cid]['paintings'][] = [
      'id' => htmlspecialchars($row['painting_id']),
      'title' => htmlspecialchars($row['painting_title']),
      'filename' => htmlspecialchars($row['filename']),
      'price' => htmlspecialchars($row['price']),
      'description' => htmlspecialchars($row['painting_description']),
      'size_v' => htmlspecialchars($row['size_v']),
      'size_h' => htmlspecialchars($row['size_h']),
      'date' => htmlspecialchars($row['finished_at']),
    ];
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Merete Hoff</title>
  <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
  <link rel="manifest" href="/site.webmanifest">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
  <link rel="stylesheet" href="css/index.css">
</head>

<body>
  <div class="header">
    <div>
      <h1>Merete Hoff</h1>
      <p>Kunst</p>
    </div>
  </div>

  <div id="navbar" class="navbar">
    <span class="menu">
      <a href="/index.php#about-anchor">OM</a>
      <a href="/index.php#collections-anchor">KUNST</a>
      <a>UTSTILLINGER</a>
      <a>KONTAKT</a>
    </span>
    <span class="logo">
      <h2 onclick="window.location.href='/index.php#'">Merete Hoff</h2>
    </span>
  </div>

  <div class="body">

    <div class="about">
      <div id="about-anchor"></div>
      <div style="align-self: flex-start;">
        <h1>Om meg</h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla placerat mi purus, vel interdum sem finibus
          vel.
          Nam laoreet laoreet nunc sed elementum. Aenean a enim ut est ultrices dictum in ac libero. Donec mattis, orci
          vitae faucibus suscipit, erat lectus tempus erat, vel pulvinar urna diam in magna. Vestibulum ante ipsum
          primis
          in faucibus orci luctus et ultrices posuere cubilia curae; Phasellus rutrum erat aliquet lacus viverra
          rhoncus.
          Praesent faucibus rutrum ligula, vel ullamcorper nisl venenatis non. Nunc efficitur sagittis quam. Aenean at
          faucibus mi. Etiam venenatis mattis elementum. Aliquam accumsan turpis erat, in vulputate dui maximus id.</p>
      </div>
      <div style="align-self: flex-end; text-align: right;">
        <h1>Om kunsten</h1>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla placerat mi purus, vel interdum sem finibus
          vel.
          Nam laoreet laoreet nunc sed elementum. Aenean a enim ut est ultrices dictum in ac libero. Donec mattis, orci
          vitae faucibus suscipit, erat lectus tempus erat, vel pulvinar urna diam in magna. Vestibulum ante ipsum
          primis
          in faucibus orci luctus et ultrices posuere cubilia curae; Phasellus rutrum erat aliquet lacus viverra
          rhoncus.
          Praesent faucibus rutrum ligula, vel ullamcorper nisl venenatis non. Nunc efficitur sagittis quam. Aenean at
          faucibus mi. Etiam venenatis mattis elementum. Aliquam accumsan turpis erat, in vulputate dui maximus id.</p>
      </div>
    </div>

    <?php if (!empty($collections)): ?>
      <div class="collections">
        <div id="collections-anchor"></div>
        <h1>Kunst</h1>
        <div class="wrapper">
        <?php foreach ($collections as $collection): ?>
          <div class="collection">
          <div class="title"><?= $collection['name'] ?></div>
          <p class="description"><?= $collection['description'] ?></p>
          <div class="gallery">
            <?php foreach ($collection['paintings'] as $painting): ?>
            <div class="picture" onclick="window.location.href = 'pages/painting.php?id=<?= $painting['id'] ?>'">
              <img src="uploads/<?= $painting['filename'] ?>" alt="<?= $painting['title'] ?>">
              <div class="overlay">
              <p class="title"><?= $painting['title'] ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          </div>
        <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

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

  <?php if (!empty($_SESSION['user_id'])): ?>
    <p>Hello, <?= htmlspecialchars($_SESSION['username']) ?>!</p>
    <a href="pages/logout.php">Logout</a>
  <?php else: ?>
    <p><a href="pages/login.php">Login</a> or <a href="pages/register.php">Register</a></p>
  <?php endif; ?>

  <script>
    const navbar = document.getElementById('navbar');
    var sticky = navbar.offsetTop;

    window.onresize = function () {
      navbar.classList.remove('sticky');
      sticky = navbar.offsetTop;
      if (window.pageYOffset > sticky) {
        navbar.classList.add('sticky');
      } else {
        navbar.classList.remove('sticky');
      }
    };

    window.onscroll = function () {
      if (window.pageYOffset > sticky) {
        navbar.classList.add('sticky');
      } else {
        navbar.classList.remove('sticky');
      }
    };
  </script>
</body>

</html>