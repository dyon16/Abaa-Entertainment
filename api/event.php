<?php

include(__DIR__ . '/conn.php');

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function getVideoMimeType($url)
{
    $path = parse_url($url, PHP_URL_PATH);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    switch ($extension) {
        case 'webm': return 'video/webm';
        case 'ogg': return 'video/ogg';
        case 'mov': return 'video/quicktime';
        case 'm4v':
        case 'mp4':
        default: return 'video/mp4';
    }
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$event = null;
$eventPhotos = [];

if ($id > 0) {
    try {
        $stmt = $pdo->prepare(
            "SELECT * FROM events
             WHERE id = :id AND is_visible = 1
             LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            try {
                $photoStmt = $pdo->prepare(
                    "SELECT id, image_url, created_at
                     FROM event_photos
                     WHERE event_id = :event_id
                     ORDER BY id ASC"
                );
                $photoStmt->execute([':event_id' => $id]);
                $eventPhotos = $photoStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $photoError) {
                // Allows older installations to continue showing the main event media.
                error_log('Event photos query error: ' . $photoError->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log('Event details query error: ' . $e->getMessage());
    }
}

if (!$event) {
    http_response_code(404);
}

$title = $event['title'] ?? 'Event Not Found';
$type = $event['type'] ?? 'image';
$file = $event['file_url'] ?? '';
$thumbnail = $event['thumbnail_url'] ?? '';

$place =
    $event['place'] ??
    $event['event_place'] ??
    $event['location'] ??
    $event['event_location'] ??
    $event['venue'] ??
    '';

$date =
    $event['event_date'] ??
    $event['date'] ??
    $event['date_event'] ??
    '';

$displayDate = $date;

if (!empty($date)) {
    $timestamp = strtotime($date);
    if ($timestamp !== false) {
        $displayDate = date('F j, Y', $timestamp);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> | ABAA Entertainment</title>
    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .event-details-page {
            width: min(1200px, calc(100% - 40px));
            margin: 0 auto;
            padding: 130px 0 70px;
        }

        .event-back-link,
        .event-back-button {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            color: #fff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 22px;
            transition: color .25s ease;
        }

        .event-back-link:hover,
        .event-back-button:hover {
            color: #ff3d02;
        }

        .event-detail-card {
            overflow: hidden;
            background: linear-gradient(135deg, rgba(8,8,8,.98), rgba(30,8,3,.96));
            border: 1px solid #2b2b2b;
            border-left: 5px solid #ff3d02;
            box-shadow: 0 20px 50px rgba(0,0,0,.35);
        }

        .event-detail-media {
            width: 100%;
            min-height: 520px;
            background: #050505;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .event-detail-media img,
        .event-detail-media video {
            width: 100%;
            max-height: 650px;
            display: block;
            object-fit: contain;
            background: #050505;
        }

        .event-detail-content {
            padding: 34px 38px 38px;
        }

        .event-detail-label {
            display: inline-block;
            margin-bottom: 9px;
            color: #ff3d02;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.6px;
        }

        .event-detail-content h1 {
            margin: 0 0 28px;
            color: #fff;
            font-size: clamp(30px, 5vw, 56px);
            line-height: 1.05;
            text-transform: uppercase;
        }

        .event-detail-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .event-info-item {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 16px;
            background: rgba(255,255,255,.035);
            border: 1px solid #292929;
        }

        .event-info-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff3d02;
            border: 1px solid rgba(255,61,2,.35);
            background: rgba(255,61,2,.07);
        }

        .event-info-item small {
            display: block;
            margin-bottom: 4px;
            color: #888;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .event-info-item strong {
            color: #fff;
            font-size: 14px;
            line-height: 1.4;
        }

        .event-photo-gallery {
            margin-top: 35px;
        }

        .event-photo-gallery-heading {
            margin-bottom: 18px;
        }

        .event-photo-gallery-heading span {
            color: #ff3d02;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
        }

        .event-photo-gallery-heading h2 {
            margin: 5px 0 0;
            color: #fff;
            font-size: 26px;
            text-transform: uppercase;
        }

        .event-photo-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .event-photo-item {
            aspect-ratio: 4 / 3;
            overflow: hidden;
            background: #080808;
            border: 1px solid #2d2d2d;
            cursor: pointer;
        }

        .event-photo-item img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            transition: transform .35s ease, filter .35s ease;
            filter: brightness(.82);
        }

        .event-photo-item:hover img {
            transform: scale(1.06);
            filter: brightness(1);
        }

        .event-not-found {
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
        }

        .event-not-found i {
            margin-bottom: 18px;
            color: #ff3d02;
            font-size: 42px;
        }

        .event-not-found h1 {
            margin: 0 0 10px;
            font-size: 36px;
        }

        .event-not-found p {
            margin: 0 0 25px;
            color: #999;
        }

        .event-back-button {
            padding: 12px 18px;
            margin: 0;
            background: #ff3d02;
            color: #fff;
            border: 1px solid #ff3d02;
        }

        .event-back-button:hover {
            color: #fff;
            background: #d93100;
        }

        .event-footer {
            padding: 45px 30px 25px;
            background: #050505;
            border-top: 1px solid #252525;
        }

        .event-footer-inner {
            width: min(1200px, 100%);
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 35px;
        }

        .event-footer-brand img {
            width: 130px;
            height: auto;
            display: block;
            margin-bottom: 15px;
        }

        .event-footer h3 {
            margin: 0 0 12px;
            color: #fff;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .event-footer p,
        .event-footer a,
        .event-footer span {
            color: #888;
            font-size: 12px;
            line-height: 1.7;
        }

        .event-footer a {
            display: block;
            text-decoration: none;
            margin-bottom: 5px;
        }

        .event-footer a:hover {
            color: #ff3d02;
        }

        .event-footer-bottom {
            width: min(1200px, 100%);
            margin: 30px auto 0;
            padding-top: 18px;
            border-top: 1px solid #202020;
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .event-photo-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .event-footer-inner {
                grid-template-columns: 1fr 1fr;
            }

            .event-footer-brand {
                grid-column: 1 / -1;
            }
        }

        @media (max-width: 600px) {
            .event-details-page {
                width: min(100% - 24px, 1200px);
                padding: 105px 0 45px;
            }

            .event-detail-media {
                min-height: 260px;
            }

            .event-detail-content {
                padding: 25px 20px 25px;
            }

            .event-detail-info {
                grid-template-columns: 1fr;
            }

            .event-photo-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 9px;
            }

            .event-footer-inner {
                grid-template-columns: 1fr;
            }

            .event-footer-brand {
                grid-column: auto;
            }

            .event-footer-bottom {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <a href="/" class="logo">
        <img src="/logo.png" alt="ABAA Entertainment Logo">
    </a>

    <button
        type="button"
        class="menu-toggle"
        onclick="toggleMobileMenu()"
        aria-label="Open menu"
        aria-expanded="false"
        aria-controls="mainNav"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav id="mainNav">
        <a href="/">Home</a>
        <a href="/#events">Events</a>
        <a href="/#services">Services</a>
        <a href="/about">About</a>
        <a href="/#booking" class="book-button">Book</a>
    </nav>
</header>

<main class="event-details-page">

    <?php if (!$event): ?>

        <div class="event-not-found">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h1>Event Not Found</h1>
            <p>The event may have been removed or is no longer available.</p>
            <a href="/#events" class="event-back-button">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Events
            </a>
        </div>

    <?php else: ?>

        <a href="/#events" class="event-back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Back to Events
        </a>

        <section class="event-detail-card">
            <div class="event-detail-media">
                <?php if ($type === 'video'): ?>
                    <video controls playsinline preload="metadata" poster="<?= e($thumbnail) ?>">
                        <source src="<?= e($file) ?>" type="<?= e(getVideoMimeType($file)) ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <img src="<?= e($file ?: $thumbnail ?: '/logo.png') ?>" alt="<?= e($title) ?>">
                <?php endif; ?>
            </div>

            <div class="event-detail-content">
                <span class="event-detail-label">ABAA ENTERTAINMENT EVENT</span>
                <h1><?= e($title) ?></h1>

                <div class="event-detail-info">
                    <div class="event-info-item">
                        <span class="event-info-icon">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <div>
                            <small>Place</small>
                            <strong><?= e($place ?: 'Not specified') ?></strong>
                        </div>
                    </div>

                    <div class="event-info-item">
                        <span class="event-info-icon">
                            <i class="fa-regular fa-calendar"></i>
                        </span>
                        <div>
                            <small>Date</small>
                            <strong><?= e($displayDate ?: 'Not specified') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php if (!empty($eventPhotos)): ?>
            <section class="event-photo-gallery">
                <div class="event-photo-gallery-heading">
                    <span>EVENT GALLERY</span>
                    <h2>More Photos</h2>
                </div>

                <div class="event-photo-grid">
                    <?php foreach ($eventPhotos as $photo): ?>
                        <a
                            href="<?= e($photo['image_url']) ?>"
                            class="event-photo-item"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="View event photo"
                        >
                            <img
                                src="<?= e($photo['image_url']) ?>"
                                alt="<?= e($title) ?> event photo"
                                loading="lazy"
                            >
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    <?php endif; ?>

</main>

<footer class="event-footer">
    <div class="event-footer-inner">
        <div class="event-footer-brand">
            <img src="/logo.png" alt="ABAA Entertainment Logo">
            <p>
                Creating unforgettable events, entertainment, and experiences through creativity,
                technology, and professional event services.
            </p>
        </div>

        <div>
            <h3>Quick Links</h3>
            <a href="/">Home</a>
            <a href="/#events">Events</a>
            <a href="/#services">Services</a>
            <a href="/about">About</a>
            <a href="/#booking">Book</a>
        </div>

        <div>
            <h3>Contact</h3>
            <span>2F, Casa Ynares, P. Gomez, Libis, Binangonan, Rizal</span>
            <a href="mailto:abaaentertainment@gmail.com">abaaentertainment@gmail.com</a>
            <a href="https://www.facebook.com/ABAAEntertainment" target="_blank" rel="noopener noreferrer">Facebook</a>
        </div>
    </div>

    <div class="event-footer-bottom">
        <span>© <?= date('Y') ?> ABAA Entertainment. All Rights Reserved.</span>
        <span>Entertainment • Events • Experiences</span>
    </div>
</footer>

<script>
function toggleMobileMenu() {
    const nav = document.getElementById("mainNav");
    const button = document.querySelector(".menu-toggle");
    if (!nav || !button) return;

    const isOpen = nav.classList.toggle("mobile-open");
    button.classList.toggle("active", isOpen);
    button.setAttribute("aria-expanded", isOpen ? "true" : "false");
    button.setAttribute("aria-label", isOpen ? "Close menu" : "Open menu");
}

document.addEventListener("click", function (event) {
    const nav = document.getElementById("mainNav");
    const button = document.querySelector(".menu-toggle");
    if (!nav || !button || !nav.classList.contains("mobile-open")) return;

    if (!nav.contains(event.target) && !button.contains(event.target)) {
        nav.classList.remove("mobile-open");
        button.classList.remove("active");
        button.setAttribute("aria-expanded", "false");
        button.setAttribute("aria-label", "Open menu");
    }
});

document.querySelectorAll("#mainNav a").forEach(function (link) {
    link.addEventListener("click", function () {
        const nav = document.getElementById("mainNav");
        const button = document.querySelector(".menu-toggle");
        if (!nav || !button) return;

        nav.classList.remove("mobile-open");
        button.classList.remove("active");
        button.setAttribute("aria-expanded", "false");
        button.setAttribute("aria-label", "Open menu");
    });
});

window.addEventListener("resize", function () {
    if (window.innerWidth > 600) {
        const nav = document.getElementById("mainNav");
        const button = document.querySelector(".menu-toggle");
        if (nav) nav.classList.remove("mobile-open");
        if (button) {
            button.classList.remove("active");
            button.setAttribute("aria-expanded", "false");
            button.setAttribute("aria-label", "Open menu");
        }
    }
});
</script>

</body>
</html>
