<?php
session_start();
require_once __DIR__ . '/db/db.php';

// Fetch all collections with paintings
$stmt = $pdo->query("
    SELECT 
        c.id AS collection_id,
        c.name AS collection_name,
        c.description AS collection_description,
        c.started_at,
        c.finished_at,
        p.id AS painting_id,
        p.title AS painting_title,
        p.price,
        p.description AS painting_description,
        p.size_v,
        p.size_h,
        p.finished_at AS painting_finished_at,
        cp.position,
        (
            SELECT i.file_path
            FROM painting_image pi2
            LEFT JOIN image i ON pi2.image_id = i.id
            WHERE pi2.painting_id = p.id
            ORDER BY pi2.position ASC
            LIMIT 1
        ) AS filename
    FROM collection c
    LEFT JOIN collection_painting cp ON c.id = cp.collection_id
    LEFT JOIN painting p ON cp.painting_id = p.id
    ORDER BY c.id, cp.position ASC
");

$collections = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $cid = htmlspecialchars($row['collection_id']);
  if (!isset($collections[$cid])) {
    $collections[$cid] = [
      'name' => htmlspecialchars($row['collection_name']),
      'description' => htmlspecialchars($row['collection_description']),
      'started_at' => htmlspecialchars($row['started_at'] ?? ''),
      'finished_at' => htmlspecialchars($row['finished_at'] ?? ''),
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
      'date' => htmlspecialchars($row['painting_finished_at']),
    ];
  }
}

// Fetch all exhibitions with their first image
$stmt = $pdo->query("
    SELECT 
        e.id AS exhibition_id,
        e.location AS exhibition_location,
        e.description AS exhibition_description,
        e.started_at,
        e.finished_at,
        i.file_path AS filename
    FROM exhibition e
    LEFT JOIN exhibition_image ei ON e.id = ei.exhibition_id
    LEFT JOIN image i ON ei.image_id = i.id
    ORDER BY e.started_at DESC
");

$exhibitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
      <a href="/index.php#exhibitions-anchor">UTSTILLINGER</a>
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
            <div class="collection-header">
              <div class="title"><?= $collection['name'] ?></div>
              <p class="date"><?= $collection['started_at'] ?> - <?= $collection['finished_at'] ?></p>
            </div>
            <p class="description"><?= $collection['description'] ?></p>
            <div class="gallery">
              <?php foreach ($collection['paintings'] as $painting): ?>
              <div class="picture" onclick="window.location.href = 'pages/painting.php?id=<?= $painting['id'] ?>'">
                <img src="uploads/<?= $painting['filename'] ?>" alt="<?= $painting['title'] ?>">
                <div class="overlay">
                <p class="title"><?= $painting['title'] ?></p>
                <p class="date"><?= $painting['date']?></p>
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