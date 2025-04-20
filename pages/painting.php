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
    SELECT i.file_path 
    FROM painting_image pi
    JOIN image i ON pi.image_id = i.id
    WHERE pi.painting_id = :painting_id
    ORDER BY pi.position
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
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato">
    <link rel="stylesheet" href="../css/index.css">
    <link rel="stylesheet" href="../css/painting.css">
</head>

<body>
    <div id="navbar" class="navbar sticky">
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

    <div class="slideshow-wrapper">
        <?php if (!empty($images)): ?>
            <div class="slideshow-container">
                <?php foreach ($images as $index => $image): ?>
                    <div class="mySlides fade">
                        <div class="numbertext"><?php echo $index + 1; ?> / <?php echo count($images); ?></div>
                        <img src="/../uploads/<?php echo htmlspecialchars($image['file_path']); ?>" style="width:100%">
                    </div>
                <?php endforeach; ?>

                <!-- Next and previous buttons -->
                <a class="prev" onclick="plusSlides(-1)">&#10094;</a>
                <a class="next" onclick="plusSlides(1)">&#10095;</a>
            </div>
            <br>

            <!-- The dots/circles -->
            <div style="text-align:center">
                <?php foreach ($images as $index => $image): ?>
                    <span class="dot" onclick="currentSlide(<?php echo $index + 1; ?>)"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="details">
        <h1><?php echo htmlspecialchars($painting['title']); ?></h1>

        <p><strong>Price:</strong> <?php echo number_format($painting['price'], 2); ?>,-</p>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($painting['description'])); ?></p>
        <p><strong>Size:</strong>
            <?php echo htmlspecialchars($painting['size_v']) . ' x ' . htmlspecialchars($painting['size_h']); ?> cm</p>
        <p><strong>Finished At:</strong> <?php echo htmlspecialchars($painting['finished_at']); ?></p>
        <p><strong>Status:</strong> <?php echo $painting['is_sold'] ? 'Sold' : 'Available'; ?></p>
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