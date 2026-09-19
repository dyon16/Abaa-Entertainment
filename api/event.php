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

$services = [];

try {
$serviceStmt = $pdo->query(
"SELECT
id,
name,
slug,
image_url,
description,
details,
is_available,
created_at
FROM services
ORDER BY id ASC"
);

$services = $serviceStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $serviceError) {
error_log(
'Event page services query error: ' .
$serviceError->getMessage()
);

$services = [];

}

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

$serviceProvided = $event['service_provided'] ?? '';

$displayDate = $date;

if (!empty($date)) {
$timestamp = strtotime($date);
if ($timestamp !== false) {
$displayDate = date('F j, Y', $timestamp);
}
}

?>

<!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8"> <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title><?= e($title) ?> | ABAA Entertainment</title> <link rel="stylesheet" href="/style.css"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"> <style> .event-details-page { width: min(1200px, calc(100% - 40px)); margin: 0 auto; padding: 130px 0 70px; }
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

    /* ==================================================
       EVENT PHOTO VIEWER
    ================================================== */

    .event-detail-media {
        position: relative;
    }

    .event-media-stage {
        position: relative;
        width: 100%;
        min-height: 520px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #050505;
    }

    .event-media-stage img,
    .event-media-stage video {
        width: 100%;
        max-height: 650px;
        height: auto;
        display: block;
        object-fit: contain;
        background: #050505;
    }

    .event-media-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 5;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 50%;
        background: rgba(0,0,0,.72);
        color: #fff;
        cursor: pointer;
        transition: .25s ease;
    }

    .event-media-arrow:hover {
        background: #ff3d02;
        border-color: #ff3d02;
    }

    .event-media-arrow.left { left: 18px; }
    .event-media-arrow.right { right: 18px; }

    .event-media-counter {
        position: absolute;
        right: 18px;
        bottom: 18px;
        z-index: 5;
        padding: 7px 11px;
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 999px;
        background: rgba(0,0,0,.72);
        color: #fff;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 1px;
    }

    .event-photo-item {
        position: relative;
    }

    .event-photo-item.active {
        border-color: #ff3d02;
        box-shadow: 0 0 0 2px rgba(255,61,2,.18);
    }

    .event-photo-item .event-photo-play {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 42px;
        height: 42px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255,61,2,.95);
        color: #fff;
        pointer-events: none;
    }

    .event-photo-empty {
        margin-top: 30px;
        padding: 20px;
        border: 1px solid #292929;
        background: #080808;
        color: #777;
        text-align: center;
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

/* ==================================================
MOBILE HEADER / BURGER
================================================== */
@media (max-width: 768px) {
.header {
height: 90px;
min-height: 90px;
padding: 0 20px;
}

.menu-toggle {
    display: flex !important;
    position: relative;
    width: 46px;
    height: 46px;
    margin-left: auto;
    flex-shrink: 0;
    z-index: 1101;
}

.header nav {
    position: absolute;
    top: 100%;
    left: 0;
    width: 100%;
    display: none;
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    padding: 10px 20px 20px;
    background: rgba(0, 0, 0, .98);
    border-bottom: 2px solid #ff3d02;
    box-shadow: 0 10px 25px rgba(0, 0, 0, .65);
}

.header nav.mobile-open {
    display: flex !important;
}

.header nav a {
    width: 100%;
    padding: 15px 10px;
    text-align: center;
    font-size: 14px;
    letter-spacing: 1px;
    white-space: nowrap;
    border-bottom: 1px solid #222;
}

.header nav a:not(.book-button)::after,
.header nav a.active::after {
    display: none;
}

.header nav a.book-button {
    margin-top: 10px;
    padding: 12px;
    border-radius: 50px;
}

}

</style>

</head> <body> <header class="header">
<a href="/" class="logo">

    <img
        src="/logo.png"
        alt="ABAA Entertainment Logo"
    >

</a>

<nav>

    <a href="/">Home</a>

    <a href="/#events">Events</a>

    <a href="/#services">Services</a>

    <a href="/about">About</a>

    <a
        href="#"
        class="book-button"
        onclick="openBookingModal(event)"
    >
        Book
    </a>

</nav>

</header> <main class="event-details-page">
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

    <?php
    /*
     * Build one media list so the main event media and every
     * uploaded event photo can be viewed from the same viewer.
     */
    $mediaItems = [];

    if (!empty($file)) {
        $mediaItems[] = [
            'type' => $type === 'video' ? 'video' : 'image',
            'source' => $file,
            'thumbnail' => $type === 'video'
                ? ($thumbnail ?: '/logo.png')
                : $file,
            'title' => $title
        ];
    }

    foreach ($eventPhotos as $photo) {
        $photoUrl = trim((string)($photo['image_url'] ?? ''));

        if ($photoUrl === '') {
            continue;
        }

        $mediaItems[] = [
            'type' => 'image',
            'source' => $photoUrl,
            'thumbnail' => $photoUrl,
            'title' => $title . ' event photo'
        ];
    }

    $mediaCount = count($mediaItems);
    ?>

    <a href="/#events" class="event-back-link">
        <i class="fa-solid fa-arrow-left"></i>
        Back to Events
    </a>

    <section class="event-detail-card">

        <div class="event-detail-media">

            <div class="event-media-stage" id="eventMediaStage">

                <?php if ($mediaCount > 0): ?>

                    <?php if ($mediaItems[0]['type'] === 'video'): ?>
                        <video
                            id="featuredVideo"
                            controls
                            playsinline
                            preload="metadata"
                            poster="<?= e($mediaItems[0]['thumbnail']) ?>"
                        >
                            <source
                                src="<?= e($mediaItems[0]['source']) ?>"
                                type="<?= e(getVideoMimeType($mediaItems[0]['source'])) ?>"
                            >
                            Your browser does not support the video tag.
                        </video>

                        <img
                            id="featuredImage"
                            src=""
                            alt=""
                            style="display:none;"
                        >
                    <?php else: ?>
                        <img
                            id="featuredImage"
                            src="<?= e($mediaItems[0]['source']) ?>"
                            alt="<?= e($mediaItems[0]['title']) ?>"
                        >

                        <video
                            id="featuredVideo"
                            controls
                            playsinline
                            preload="metadata"
                            style="display:none;"
                        ></video>
                    <?php endif; ?>

                    <?php if ($mediaCount > 1): ?>
                        <button
                            type="button"
                            class="event-media-arrow left"
                            onclick="changeEventMedia(-1)"
                            aria-label="Previous event photo"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <button
                            type="button"
                            class="event-media-arrow right"
                            onclick="changeEventMedia(1)"
                            aria-label="Next event photo"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                        <span class="event-media-counter" id="eventMediaCounter">
                            1 / <?= $mediaCount ?>
                        </span>
                    <?php endif; ?>

                <?php else: ?>
                    <img
                        src="/logo.png"
                        alt="<?= e($title) ?>"
                    >
                <?php endif; ?>

            </div>

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

                <div class="event-info-item">
                    <span class="event-info-icon">
                        <i class="fa-solid fa-briefcase"></i>
                    </span>
                    <div>
                        <small>Services Provided</small>
                        <strong>
                            <?= e($serviceProvided ?: 'Not specified') ?>
                        </strong>
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

                <?php foreach ($mediaItems as $index => $media): ?>

                    <button
                        type="button"
                        class="event-photo-item<?= $index === 0 ? ' active' : '' ?>"
                        onclick="showEventMedia(<?= $index ?>)"
                        aria-label="View event media <?= $index + 1 ?>"
                    >
                        <img
                            src="<?= e($media['thumbnail']) ?>"
                            alt="<?= e($media['title']) ?>"
                            loading="lazy"
                        >

                        <?php if ($media['type'] === 'video'): ?>
                            <span class="event-photo-play">
                                <i class="fa-solid fa-play"></i>
                            </span>
                        <?php endif; ?>
                    </button>

                <?php endforeach; ?>

            </div>
        </section>
    <?php endif; ?>

<?php endif; ?>

</main> <footer class="footer">
<div class="footer-container">

    <div class="footer-section footer-brand">

        <img
            src="/logo.png"
            alt="ABAA Entertainment Logo"
        >

        <p>
            Creating unforgettable events,
            entertainment, and experiences
            through creativity, technology,
            and professional event services.
        </p>

    </div>

    <div class="footer-section">

        <h3>Quick Links</h3>

        <a href="/">Home</a>

        <a href="/#events">Events</a>

        <a href="/#services">Services</a>

        <a href="/about">About Us</a>

        <a
            href="/booking-status"
            class="booking-status-link"
        >
            Check Booking Status
        </a>

    </div>

    <div class="footer-section">

        <h3>Our Services</h3>

        <?php foreach ($services as $item): ?>

            <a
                href="<?= (int)$item['is_available'] === 1
                    ? '/service?service='
                        . urlencode($item['slug'])
                    : '#services'
                ?>"
            >

                <?= e($item['name']) ?>

                <?php if (
                    (int)$item['is_available'] !== 1
                ): ?>

                    <small style="color:#f87171;">
                        (Not Available)
                    </small>

                <?php endif; ?>

            </a>

        <?php endforeach; ?>

    </div>

    <div class="footer-section">

        <h3>Contact Us</h3>

        <a
            href="https://www.google.com/maps/place/ABAA+Entertainment/@14.4652755,121.1915078,19z"
            target="_blank"
            rel="noopener noreferrer"
            class="contact-item"
        >

            <i
                class="fa-solid
                fa-location-dot"
            ></i>

            <span>
                2F, Casa Ynares, P. Gomez,
                Libis, Binangonan, Rizal
            </span>

        </a>

        <a
            href="tel:+639231476552"
            class="contact-item"
        >

            <i
                class="fa-solid
                fa-phone"
            ></i>

            <span>
                +63 923 147 6552
            </span>

        </a>

        <a
            href="mailto:abaaentertainment@gmail.com"
            class="contact-item"
        >

            <i
                class="fa-solid
                fa-envelope"
            ></i>

            <span>
                abaaentertainment@gmail.com
            </span>

        </a>

        <div class="social-links">

            <a
                href="https://www.facebook.com/ABAAEntertainment"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="Facebook"
            >

                <i
                    class="fa-brands
                    fa-facebook-f"
                ></i>

            </a>

            <a
                href="#"
                aria-label="Instagram"
            >

                <i
                    class="fa-brands
                    fa-instagram"
                ></i>

            </a>

            <a
                href="https://www.tiktok.com/@markebpmbta?_r=1&_t=ZS-99DpdJXY5sD"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="TikTok"
            >

                <i
                    class="fa-brands
                    fa-tiktok"
                ></i>

            </a>

        </div>

    </div>

</div>

<div class="footer-bottom">

    <p>
        © <?= date('Y') ?>
        ABAA Entertainment.
        All Rights Reserved.
    </p>

    <p>
        Entertainment • Events • Experiences
    </p>

</div>

</footer> <!-- ================================================== BOOKING MODAL ================================================== --> <div class="booking-overlay" id="bookingModal" aria-hidden="true" >
<div class="booking-modal">

    <button
        type="button"
        class="booking-close"
        onclick="closeBookingModal()"
        aria-label="Close booking form"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>

    <div class="booking-header">

        <span class="booking-label">
            ABAA ENTERTAINMENT
        </span>

        <h2>
            Book An Event
        </h2>

        <p>
            Tell us about your event
            and our team will get back to you.
        </p>

    </div>

    <form
        action="/booking"
        method="POST"
        class="booking-form"
    >

        <!-- CONTACT PERSON FIRST -->

        <div class="form-group">

            <label for="booking_contact_person">
                Contact Person
            </label>

            <input
                type="text"
                id="booking_contact_person"
                name="contact_person"
                placeholder="Enter contact person's name"
                required
            >

        </div>

        <!-- PHONE + EMAIL -->

        <div class="form-row">

            <div class="form-group">

                <label for="booking_phone">
                    Phone Number
                </label>

                <input
                    type="tel"
                    id="booking_phone"
                    name="phone"
                    placeholder="09XX XXX XXXX"
                    required
                >

            </div>

            <div class="form-group">

                <label for="booking_email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="booking_email"
                    name="email"
                    placeholder="your@email.com"
                    required
                >

            </div>

        </div>

        <!-- COMPANY -->

        <div class="form-group">

            <label for="booking_company">
                Company Name
            </label>

            <input
                type="text"
                id="booking_company"
                name="cname"
                placeholder="Enter company name"
                required
            >

        </div>

        <!-- EVENT TYPE + DATE -->

        <div class="form-row">

            <div class="form-group">

                <label for="booking_event">
                    Event Type
                </label>

                <select
                    id="booking_event"
                    name="event_type"
                    required
                    onchange="toggleOtherEventType()"
                >

                    <option
                        value=""
                        disabled
                        selected
                    >
                        Select event type
                    </option>

                    <option value="Birthday">
                        Birthday
                    </option>

                    <option value="Wedding">
                        Wedding
                    </option>

                    <option value="Concert">
                        Concert
                    </option>

                    <option value="Corporate Event">
                        Corporate Event
                    </option>

                    <option value="Festival">
                        Festival
                    </option>

                    <option value="Product Launch">
                        Product Launch
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label for="booking_date">
                    Event Date
                </label>

                <input
                    type="date"
                    id="booking_date"
                    name="event_date"
                    required
                >

            </div>

        </div>

        <!-- OTHER EVENT TYPE -->

        <div
            class="form-group"
            id="otherEventTypeGroup"
            style="display:none;"
        >

            <label for="other_event_type">
                Please Specify Event Type
            </label>

            <input
                type="text"
                id="other_event_type"
                name="other_event_type"
                placeholder="Enter your event type"
            >

        </div>

        <!-- SERVICES -->

        <div class="form-group">

            <label>
                Services Needed
            </label>

            <div class="service-checkboxes">

                <?php if (!empty($services)): ?>

                    <?php foreach ($services as $serviceItem): ?>

                        <?php
                        $serviceAvailable =
                            (int)(
                                $serviceItem['is_available']
                                ?? 0
                            ) === 1;
                        ?>

                        <label
                            class="service-checkbox
                            <?= !$serviceAvailable
                                ? 'service-unavailable-checkbox'
                                : ''
                            ?>"
                        >

                            <input
                                type="checkbox"
                                name="service[]"
                                value="<?= e(
                                    $serviceItem['name']
                                ) ?>"
                                <?= !$serviceAvailable
                                    ? 'disabled'
                                    : ''
                                ?>
                            >

                            <span>

                                <?= e(
                                    $serviceItem['name']
                                ) ?>

                                <?php if (!$serviceAvailable): ?>

                                    <small
                                        class="service-unavailable-text"
                                    >
                                        (Unavailable)
                                    </small>

                                <?php endif; ?>

                            </span>

                        </label>

                    <?php endforeach; ?>

                <?php else: ?>

                    <p class="booking-no-services">
                        No services are currently available.
                    </p>

                <?php endif; ?>

            </div>

        </div>

        <!-- EVENT DETAILS -->

        <div class="form-group">

            <label for="booking_message">
                Event Details
            </label>

            <textarea
                id="booking_message"
                name="message"
                rows="4"
                placeholder="Tell us about your event, location, preferred setup, budget, or other requirements..."
            ></textarea>

        </div>

        <!-- SUBMIT -->

        <button
            type="submit"
            class="booking-submit"
        >

            <span>
                Submit Booking Request
            </span>

            <i
                class="fa-solid
                fa-arrow-right"
            ></i>

        </button>

    </form>

</div>

</div> <script> /* ================================================== EVENT MEDIA VIEWER ================================================== */
const eventMedia = <?= json_encode( $mediaItems ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) ?>;

let currentEventMedia = 0;

function showEventMedia(index) {
if (!eventMedia.length) return;

if (index < 0) {
    index = eventMedia.length - 1;
}

if (index >= eventMedia.length) {
    index = 0;
}

currentEventMedia = index;

const item = eventMedia[index];
const image = document.getElementById('featuredImage');
const video = document.getElementById('featuredVideo');
const counter = document.getElementById('eventMediaCounter');

if (!image || !video) return;

if (item.type === 'video') {
    image.style.display = 'none';
    image.removeAttribute('src');

    video.pause();
    video.style.display = 'block';
    video.poster = item.thumbnail || '';
    video.innerHTML = '';

    const source = document.createElement('source');
    source.src = item.source;
    source.type = getVideoMimeTypeFromUrl(item.source);
    video.appendChild(source);
    video.load();
} else {
    video.pause();
    video.removeAttribute('src');
    video.innerHTML = '';
    video.style.display = 'none';

    image.style.display = 'block';
    image.src = item.source;
    image.alt = item.title || 'Event photo';
}

if (counter) {
    counter.textContent = (index + 1) + ' / ' + eventMedia.length;
}

document.querySelectorAll('.event-photo-item').forEach(function(button, buttonIndex) {
    button.classList.toggle('active', buttonIndex === index);
});

}

function changeEventMedia(direction) {
showEventMedia(currentEventMedia + direction);
}

function getVideoMimeTypeFromUrl(url) {
const cleanUrl = String(url || '').split('?')[0].split('#')[0];
const extension = cleanUrl.split('.').pop().toLowerCase();

if (extension === 'webm') return 'video/webm';
if (extension === 'ogg') return 'video/ogg';
if (extension === 'mov') return 'video/quicktime';
return 'video/mp4';

}

document.addEventListener('keydown', function(event) {
if (event.key === 'ArrowLeft') {
changeEventMedia(-1);
}

if (event.key === 'ArrowRight') {
    changeEventMedia(1);
}

});
</script>

<script>
/* ==================================================
BOOKING MODAL
================================================== */

function openBookingModal(event)
{
if (event) {

    event.preventDefault();

}

const modal =
    document.getElementById('bookingModal');

if (!modal) {

    return;

}

modal.classList.add('active');

modal.setAttribute(
    'aria-hidden',
    'false'
);

document.body.style.overflow =
    'hidden';

}

/* ==================================================
CLOSE BOOKING MODAL
================================================== */

function closeBookingModal()
{
const modal =
document.getElementById('bookingModal');

if (!modal) {

    return;

}

modal.classList.remove('active');

modal.setAttribute(
    'aria-hidden',
    'true'
);

document.body.style.overflow =
    '';

}

/* ==================================================
OTHER EVENT TYPE
================================================== */

function toggleOtherEventType()
{
const eventType =
document.getElementById(
'booking_event'
);

const otherGroup =
    document.getElementById(
        'otherEventTypeGroup'
    );

const otherInput =
    document.getElementById(
        'other_event_type'
    );

if (
    !eventType ||
    !otherGroup ||
    !otherInput
) {

    return;

}

if (eventType.value === 'Other') {

    otherGroup.style.display =
        'block';

    otherInput.required =
        true;

} else {

    otherGroup.style.display =
        'none';

    otherInput.required =
        false;

    otherInput.value =
        '';

}

}

/* ==================================================
CLOSE WHEN CLICKING OVERLAY
================================================== */

document.addEventListener(
'click',
function(event)
{
const modal =
document.getElementById(
'bookingModal'
);

    if (
        modal &&
        event.target === modal
    ) {

        closeBookingModal();

    }

}

);

/* ==================================================
ESC KEY
================================================== */

document.addEventListener(
'keydown',
function(event)
{
if (event.key === 'Escape') {

        closeBookingModal();

    }

}

);

</script> </body> </html>
