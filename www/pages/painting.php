<?php
require_once __DIR__ . '/../db/db.php';
session_start();

// Get painting ID from URL
if (!isset($_GET['id'])) {
  die('Invalid painting ID.');
}
$paintingId = $_GET['id'];

// Fetch painting details
$query = $pdo->prepare("
    SELECT 
        p.id, 
        p.title, 
        p.price, 
        p.description, 
        p.size_v, 
        p.size_h, 
        p.finished_at, 
        p.is_sold
    FROM painting p
    WHERE p.id = :id
");
$query->execute(['id' => $paintingId]);
$painting = $query->fetch(PDO::FETCH_ASSOC);

if (!$painting) {
  die('Painting not found.');
}

// Fetch associated images
$imageQuery = $pdo->prepare("
    SELECT file_path 
    FROM painting_image
    WHERE painting_id = :painting_id
    ORDER BY position
");
$imageQuery->execute(['painting_id' => $paintingId]);
$images = $imageQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($painting['title']); ?> - Merete Hoff</title>
  <link rel="apple-touch-icon" sizes="180x180" href="/img/favicon/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/img/favicon/favicon-16x16.png">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
  <link rel="stylesheet" href="../css/index.css">
  <link rel="stylesheet" href="../css/painting.css">
</head>

<body>
  <div id="navbar" class="navbar sticky">
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

  <button 
    class="return"
    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.history.back();}"
    onclick="window.history.back();"
    aria-label="Tilbake"
    tabindex="0"
  >
    <img 
      src="/img/return.svg" 
      alt="Tilbake" 
    >
  </button>

  <div class="slideshow-wrapper">
    <?php if (!empty($images)): ?>
      <div class="slideshow-container">
        <?php foreach ($images as $index => $image): ?>
          <div class="mySlides fade">
            <div class="numbertext"><?php echo $index + 1; ?> / <?php echo count($images); ?></div>
            <img 
              src="/../uploads/<?php echo htmlspecialchars($image['file_path']); ?>" 
              style="width:100%" 
              alt="Bilde av maleriet <?php echo htmlspecialchars($painting['title']); ?>"
            >
          </div>
        <?php endforeach; ?>

        <!-- Next and previous buttons -->
        <a class="prev" tabindex="0" role="button" aria-label="Forrige bilde"
           onclick="plusSlides(-1)"
           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();plusSlides(-1);}">&#10094;
        </a>
        <a class="next" tabindex="0" role="button" aria-label="Neste bilde"
           onclick="plusSlides(1)"
           onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();plusSlides(1);}">&#10095;
        </a>
      </div>
      <br>

      <!-- The dots/circles -->
      <div style="text-align:center">
        <?php foreach ($images as $index => $image): ?>
          <span 
            class="dot" 
            tabindex="0" 
            role="button" 
            aria-label="Bilde <?php echo $index + 1; ?>" 
            onclick="currentSlide(<?php echo $index + 1; ?>)"
            onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();currentSlide(<?php echo $index + 1; ?>);}"
          ></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="details">
    <div class="details-header">
      <h1><?php echo htmlspecialchars($painting['title']); ?></h1>
      <p class="weak"><?php echo number_format($painting['price'], 0); ?>,-</p>
      <?php if ($painting['is_sold']): ?>
        <p class="weak">Solgt</p>
      <?php else: ?>
        <p class="weak">Tilgjengelig</p>
      <?php endif; ?>
      <?php if (!empty($_SESSION['username'])): ?>
        <button 
          class="admin-action netrual"
          onclick="window.location.href='/pages/edit_painting.php?id=<?php echo $paintingId; ?>'"
        >
          <img src="../img/edit.svg" alt="Rediger bilde">
          <span>Rediger</span>
        </button>
        <button 
          class="admin-action negative"
          onclick="window.location.href='/pages/delete_painting.php?id=<?php echo $paintingId; ?>'"
        >
          <img src="../img/delete.svg" alt="Slett bilde">
          <span>Slett</span>
        </button>
      <?php endif; ?>
    </div>
    <p><?php echo nl2br(htmlspecialchars($painting['description'])); ?></p>
    <p><strong>Størrelse:</strong>
      <?php echo htmlspecialchars($painting['size_v']) . ' x ' . htmlspecialchars($painting['size_h']); ?> cm</p>
    <p><strong>Ferdigstillt:</strong> <?php echo htmlspecialchars($painting['finished_at']); ?></p>
  </div>

  <script>
    let slideIndex = 1;
    showSlides(slideIndex);

    function plusSlides(n) {
      showSlides(slideIndex += n);
    }

    function currentSlide(n) {
      showSlides(slideIndex = n);
    }

    function showSlides(n) {
      let i;
      let slides = document.getElementsByClassName("mySlides");
      let dots = document.getElementsByClassName("dot");
      if (n > slides.length) { slideIndex = 1 }
      if (n < 1) { slideIndex = slides.length }
      for (i = 0; i < slides.length; i++) {
        slides[i].style.display = "none";
      }
      for (i = 0; i < dots.length; i++) {
        dots[i].className = dots[i].className.replace(" active", "");
      }
      slides[slideIndex - 1].style.display = "block";
      dots[slideIndex - 1].className += " active";
    }
  </script>
</body>

</html>