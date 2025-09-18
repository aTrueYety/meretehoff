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
            SELECT pi2.file_path
            FROM painting_image pi2
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
      'collection_id' => $cid,
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

// Fetch all exhibitions with all their images
$stmt = $pdo->query("
    SELECT 
        e.id AS exhibition_id,
        e.location AS exhibition_location,
        e.description AS exhibition_description,
        e.started_at,
        e.finished_at,
        ei.file_path AS filename
    FROM exhibition e
    LEFT JOIN exhibition_image ei ON e.id = ei.exhibition_id
    ORDER BY e.started_at DESC, ei.position ASC
");

$exhibitions = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $eid = htmlspecialchars($row['exhibition_id']);
  if (!isset($exhibitions[$eid])) {
    $exhibitions[$eid] = [
      'exhibition_id' => $eid,
      'exhibition_location' => htmlspecialchars($row['exhibition_location']),
      'exhibition_description' => htmlspecialchars($row['exhibition_description']),
      'started_at' => htmlspecialchars($row['started_at']),
      'finished_at' => htmlspecialchars($row['finished_at']),
      'images' => []
    ];
  }
  if ($row['filename']) {
    $exhibitions[$eid]['images'][] = htmlspecialchars($row['filename']);
  }
}
$exhibitions = array_values($exhibitions); // Re-index for foreach
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Merete Hoff</title>
  <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
  <link rel="stylesheet" href="css/index.css">
</head>

<body>
  <div class="header">
    <div>
      <div class="blur"></div>
      <h1>Merete Hoff</h1>
      <p>Kunst</p>
    </div>
  </div>

  <div id="navbar" class="navbar">
    <span class="hamburger" tabindex="0" role="button" aria-label="Åpne/lukk meny"
      onclick="document.querySelector('.navbar').classList.toggle('open');"
      onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();document.querySelector('.navbar').classList.toggle('open');}">
      <img src="/img/menu.svg" alt="meny">
    </span>
    <span class="menu">
      <a href="/index.php#about-anchor" onclick="document.querySelector('.navbar').classList.remove('open');">OM</a>
      <a href="/index.php#collections-anchor"
        onclick="document.querySelector('.navbar').classList.remove('open');">KUNST</a>
      <a href="/index.php#exhibitions-anchor"
        onclick="document.querySelector('.navbar').classList.remove('open');">UTSTILLINGER</a>
      <a href="/index.php#contact-anchor"
        onclick="document.querySelector('.navbar').classList.remove('open');">KONTAKT</a>
    </span>
    <span class="logo">
      <h2 tabindex="0" role="button" aria-label="Gå til toppen"
        onclick="window.location.href='/index.php#';document.querySelector('.navbar').classList.remove('open');"
        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='/index.php#';document.querySelector('.navbar').classList.remove('open');}">
        Merete Hoff</h2>
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

    <div class="collections">
      <div id="collections-anchor"></div>
      <div class="head">
        <h1>Kunst</h1>
        <?php if (!empty($_SESSION['username'])): ?>
          <!-- Admin Actions -->
          <button 
            class="admin-action positive" 
            onclick="window.location.href='pages/create_collection.php'"
          >
            <img src="img/add.svg" alt="Legg til samling">
            <span>Legg til samling</span>
          </button>
          <button 
            class="admin-action positive" 
            onclick="window.location.href='pages/create_painting.php'"
          >
            <img src="img/add.svg" alt="Legg til kunstverk">
            <span>Legg til kunstverk</span>
          </button>
        <?php endif; ?>
      </div>
      <?php if (!empty($collections)): ?>
        <div class="wrapper">
          <?php foreach ($collections as $collection): ?>
            <div class="collection">
              <div class="section-header">
                <div class="title-container">
                  <div class="title"><?= $collection['name'] ?></div>
                  <?php if (!empty($_SESSION['username'])): ?>
                    <button 
                      class="admin-action netrual"
                      onclick="window.location.href='pages/edit_collection.php?id=<?= $collection['collection_id'] ?>'"
                    >
                      <img src="img/edit.svg" alt="Rediger samling">
                      <span>Rediger</span>
                    </button>
                    <button 
                      class="admin-action negative"
                      onclick="window.location.href='pages/delete_collection.php?id=<?= $collection['collection_id'] ?>'"
                    >
                      <img src="img/delete.svg" alt="Slett samling">
                      <span>Slett</span>
                    </button>
                  <?php endif; ?>
                </div>
                <p class="date"><?= $collection['started_at'] ?> - <?= $collection['finished_at'] ?></p>
              </div>
              <p class="description"><?= $collection['description'] ?></p>
              <div class="gallery">
                <?php foreach ($collection['paintings'] as $painting): ?>
                  <div class="painting" tabindex="0" role="button" aria-label="Se detaljer for <?= $painting['title'] ?>"
                    onclick="window.location.href = 'pages/painting.php?id=<?= $painting['id'] ?>'"
                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location.href='pages/painting.php?id=<?= $painting['id'] ?>';}">
                    <img src="uploads/<?= $painting['filename'] ?>" alt="<?= $painting['title'] ?>">
                    <div class="overlay">
                      <p class="title"><?= $painting['title'] ?></p>
                      <p class="date"><?= $painting['date'] ?></p>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>Ingen kunstverk funnet.</p>
      <?php endif; ?>
    </div>

    <div class="exhibitions">
      <div id="exhibitions-anchor"></div>
      <div class="head">
        <?php if (!empty($_SESSION['username'])): ?>
          <!-- Admin Actions -->
          <button 
            class="admin-action positive" 
            onclick="window.location.href='pages/create_exhibition.php'"
          >
            <img src="img/add.svg" alt="Legg til utstilling">
            <span>Legg til utstilling</span>
          </button>
        <?php endif; ?>
        <h1>Utstillinger</h1>
      </div>
      <?php if (!empty($exhibitions)): ?>
        <div class="wrapper">
          <?php foreach ($exhibitions as $exhibition): ?>
            <div class="exhibition">
              <div class="section-header">
                <div class="title-container">
                  <div class="title"><?= $exhibition['exhibition_location'] ?></div>
                  <?php if (!empty($_SESSION['username'])): ?>
                    <button 
                      class="admin-action netrual"
                      onclick="window.location.href='pages/edit_exhibition.php?id=<?= $exhibition['exhibition_id'] ?>'"
                    >
                      <img src="img/edit.svg" alt="Rediger utstilling">
                      <span>Rediger</span>
                    </button>
                    <button 
                      class="admin-action negative"
                      onclick="window.location.href='pages/delete_exhibition.php?id=<?= $exhibition['exhibition_id'] ?>'"  
                    >
                      <img src="img/delete.svg" alt="Slett utstilling">
                      <span>Slett</span>
                    </button>
                  <?php endif; ?>
                </div>
                <p class="date"><?= $exhibition['started_at'] ?> -
                  <?= $exhibition['finished_at'] ?>
                </p>
              </div>
              <p class="description"><?= $exhibition['exhibition_description'] ?></p>
              <div class="gallery">
                <?php foreach ($exhibition['images'] as $img): ?>
                  <div class="exhibition-image">
                    <img src="uploads/<?= $img ?>" alt="<?= $exhibition['exhibition_location'] ?>">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p>Ingen utstillinger funnet.</p>
      <?php endif; ?>
    </div>


    <div class="contact">
      <div id="contact-anchor"></div>
      <h1>Kontakt</h1>
      <div class="wrapper">
        <img src="img/merete.jpeg" alt="Merete Hoff" class="profile-pic">
        <div class="contact-details">
          <H2>Merete Hoff</H2>
          <button onclick="window.location.href='tel:+4799999999'">
            <img src="img/phone.svg" alt="Telefon">
            <p>+47 99999999</p>
          </button>
          <button onclick="window.location.href='mailto:post@gmail.com'">
            <img src="img/mail.svg" alt="E-post">
            <p>post@gmail.com</p>
          </button>
          <button
            onclick="window.location.href='https://www.google.com/maps/place/Aker+brygge,+Oslo/@59.9099508,10.7208394,16z/data=!3m1!4b1!4m6!3m5!1s0x46416e81bceae4f9:0xe68ffef57f364675!8m2!3d59.9099584!4d10.7258053!16s%2Fm%2F02qjqd1?entry=ttu&g_ep=EgoyMDI1MDYxMS4wIKXMDSoASAFQAw%3D%3D'">
            <img src="img/address.svg" alt="Adresse">
            <p>Eksempelveien 1, 1234 Oslo</p>
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="footer">
    <p>© 2023 Merete Hoff. Alle rettigheter reservert.</p>
    <?php if (!empty($_SESSION['username'])): ?>
      <p>Logget inn som <?= htmlspecialchars($_SESSION['username']) ?></p>
      <span>
        <a href="pages/logout.php">Logg ut</a>
      </span>
    <?php else: ?>
      <span>
        <a href="pages/login.php">Log inn</a>
        <p> / </p>
        <a href="pages/register.php">Register</a>
      </span>
    <?php endif; ?>
  </div>


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