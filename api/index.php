<?php

include(__DIR__ . '/conn.php');


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function parseDetails($details)
{
    if (empty($details)) {
        return [];
    }

    $decoded = json_decode(
        $details,
        true
    );

    if (is_array($decoded)) {
        return $decoded;
    }

    return array_values(
        array_filter(
            array_map(
                'trim',
                preg_split(
                    '/\r\n|\r|\n/',
                    $details
                )
            )
        )
    );
}


function getVideoMimeType($url)
{
    $path = parse_url(
        $url,
        PHP_URL_PATH
    );

    $extension = strtolower(
        pathinfo(
            $path,
            PATHINFO_EXTENSION
        )
    );

    switch ($extension) {

        case 'webm':
            return 'video/webm';

        case 'ogg':
            return 'video/ogg';

        case 'mov':
            return 'video/quicktime';

        case 'm4v':
            return 'video/mp4';

        case 'mp4':
        default:
            return 'video/mp4';
    }
}


/*
|--------------------------------------------------------------------------
| LOAD EVENTS
|--------------------------------------------------------------------------
*/

$events = [];

try {

    $stmt = $pdo->query(
        "SELECT
            id,
            title,
            type,
            file_url,
            thumbnail_url,
            is_visible,
            created_at
         FROM events
         WHERE is_visible = 1
         ORDER BY id DESC"
    );

    $events =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    error_log(
        'Event query error: ' .
        $e->getMessage()
    );

}


/*
|--------------------------------------------------------------------------
| LOAD SERVICES
|--------------------------------------------------------------------------
|
| Services are managed from /admin/services
|
*/

$services = [];

try {

    $stmt = $pdo->query(
        "SELECT
            id,
            name,
            slug,
            image_url,
            description,
            details,
            is_available,
            sort_order,
            created_at
         FROM services
         ORDER BY sort_order ASC, id ASC"
    );

    $services =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (PDOException $e) {

    error_log(
        'Service query error: ' .
        $e->getMessage()
    );

}


/*
|--------------------------------------------------------------------------
| FIRST EVENT
|--------------------------------------------------------------------------
*/

$firstEvent =
    !empty($events)
        ? $events[0]
        : null;

$firstType =
    $firstEvent
        ? $firstEvent['type']
        : '';

$firstFile =
    $firstEvent
        ? $firstEvent['file_url']
        : '';

$firstTitle =
    $firstEvent
        ? $firstEvent['title']
        : '';

$firstThumbnail =
    $firstEvent
        ? $firstEvent['thumbnail_url']
        : '';

$firstMimeType =
    $firstType === 'video'
        ? getVideoMimeType($firstFile)
        : '';

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
    ABAA Entertainment
</title>


<link
    rel="stylesheet"
    href="/style.css"
>


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>


<style>

/*
|--------------------------------------------------------------------------
| FEATURED EVENT
|--------------------------------------------------------------------------
*/

.featured-event {

    position:
        relative;

}


.featured-event video,
.featured-event > img {

    width:
        100%;

    display:
        block;

}


.featured-play-button {

    position:
        absolute;

    left:
        50%;

    top:
        50%;

    transform:
        translate(-50%, -50%);

    width:
        70px;

    height:
        70px;

    border:
        none;

    border-radius:
        50%;

    background:
        rgba(255, 90, 31, .95);

    color:
        white;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    cursor:
        pointer;

    z-index:
        20;

    box-shadow:
        0 8px 30px rgba(0,0,0,.30);

    transition:
        transform .2s ease,
        background .2s ease;

}


.featured-play-button:hover {

    transform:
        translate(-50%, -50%)
        scale(1.08);

    background:
        #ff4510;

}


.featured-play-button i {

    font-size:
        25px;

    margin-left:
        4px;

}


.video-thumbnail {

    position:
        relative;

}


.video-thumbnail img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    display:
        block;

}


.video-thumbnail-play {

    position:
        absolute;

    left:
        50%;

    top:
        50%;

    transform:
        translate(-50%, -50%);

    width:
        42px;

    height:
        42px;

    border-radius:
        50%;

    display:
        flex;

    align-items:
        center;

    justify-content:
        center;

    background:
        rgba(255, 90, 31, .92);

    color:
        white;

}


.featured-video-loading {

    position:
        absolute;

    left:
        50%;

    top:
        50%;

    transform:
        translate(-50%, -50%);

    width:
        48px;

    height:
        48px;

    border-radius:
        50%;

    border:
        4px solid rgba(255, 255, 255, .35);

    border-top-color:
        #ff5a1f;

    animation:
        featuredVideoSpin .8s linear infinite;

    z-index:
        30;

    display:
        none;

}


@keyframes featuredVideoSpin {

    to {

        transform:
            translate(-50%, -50%)
            rotate(360deg);

    }

}


/*
|--------------------------------------------------------------------------
| SERVICE FALLBACK
|--------------------------------------------------------------------------
*/

.service-unavailable {
    cursor: not-allowed;
    border-color: #7f1d1d;
    filter: grayscale(.15);
}

.service-unavailable:hover {
    transform: none;
    border-color: #dc2626;
    box-shadow: 0 15px 35px rgba(220, 38, 38, .22);
}

.service-unavailable::before {
    width: 100%;
    background: #dc2626;
}

.service-unavailable img {
    filter: grayscale(.75) brightness(.42);
}

.service-unavailable:hover img {
    transform: none;
    filter: grayscale(.75) brightness(.42);
}

.service-unavailable .image-title {
    color: #fca5a5;
    background: #180707;
}

.service-unavailable-overlay {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(80, 0, 0, .30);
    pointer-events: none;
}

.service-unavailable-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid rgba(248, 113, 113, .65);
    border-radius: 999px;
    background: rgba(127, 29, 29, .90);
    color: #fee2e2;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: .8px;
    text-transform: uppercase;
    box-shadow: 0 8px 25px rgba(0, 0, 0, .35);
}

.service-unavailable-badge i {
    color: #f87171;
}

.service-empty {

    width:
        100%;

    padding:
        40px 20px;

    text-align:
        center;

    color:
        #777;

}


/*
|--------------------------------------------------------------------------
| SERVICE IMAGE
|--------------------------------------------------------------------------
*/

.image-button img {

    width:
        100%;

    height:
        100%;

    object-fit:
        cover;

    display:
        block;

}

</style>

</head>


<body>


<!-- =====================================================
     HEADER
===================================================== -->

<header class="header">


<a
    href="/"
    class="logo"
>

    <img
        src="/logo.png"
        alt="ABAA Entertainment Logo"
    >

</a>


<nav>

    <a href="/">
        Home
    </a>


    <a href="/#events">
        Events
    </a>


    <a href="/#services">
        Services
    </a>


    <a href="/about">
        About
    </a>


    <a
        href="#"
        class="book-button"
        onclick="openBookingModal(event)"
    >

        Book

    </a>

</nav>


</header>


<!-- =====================================================
     HERO
===================================================== -->

<section class="about-hero">


<div class="about-hero-content">


    <p class="small-title">

        ABAA ENTERTAINMENT

    </p>


    <h1>

        <span>
            Powered by Passion.
        </span>

        <br>

        <span>
            Driven by Excellence.
        </span>

    </h1>


    <p class="hero-description">

        We create unforgettable experiences through
        creativity, technology, passion, and professional
        event production.

    </p>


</div>


</section>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="main-container">


<!-- =====================================================
     LOGO / FEATURED BOX
===================================================== -->

<section class="big-box">

    <img
        src="/logo.png"
        alt="ABAA Entertainment"
    >

</section>


<!-- =====================================================
     SERVICES
===================================================== -->

<section
    class="services"
    id="services"
>


    <h2>
        Services
    </h2>


    <p class="services-description">

        Professional entertainment and event services
        designed to create memorable experiences.

    </p>


    <div class="boxes">


        <?php if (empty($services)): ?>


            <div class="service-empty">

                <p>
                    No services are currently available.
                </p>

            </div>


        <?php else: ?>


            <?php foreach ($services as $service): ?>

                <?php
                $serviceAvailable =
                    (int) ($service['is_available'] ?? 0) === 1;
                ?>

                <?php if ($serviceAvailable): ?>

                    <a
                        href="/service?service=<?= e($service['slug']) ?>"
                        class="image-button"
                    >

                        <?php if (!empty($service['image_url'])): ?>

                            <img
                                src="<?= e($service['image_url']) ?>"
                                alt="<?= e($service['name']) ?>"
                            >

                        <?php else: ?>

                            <img
                                src="/logo.png"
                                alt="<?= e($service['name']) ?>"
                            >

                        <?php endif; ?>

                        <div class="image-title">
                            <?= e($service['name']) ?>
                        </div>

                    </a>

                <?php else: ?>

                    <div
                        class="image-button service-unavailable"
                        aria-disabled="true"
                    >

                        <?php if (!empty($service['image_url'])): ?>

                            <img
                                src="<?= e($service['image_url']) ?>"
                                alt="<?= e($service['name']) ?>"
                            >

                        <?php else: ?>

                            <img
                                src="/logo.png"
                                alt="<?= e($service['name']) ?>"
                            >

                        <?php endif; ?>

                        <div class="service-unavailable-overlay">

                            <span class="service-unavailable-badge">
                                <i class="fa-solid fa-circle-xmark"></i>
                                Not Available
                            </span>

                        </div>

                        <div class="image-title">
                            <?= e($service['name']) ?>
                        </div>

                    </div>

                <?php endif; ?>

            <?php endforeach; ?>


        <?php endif; ?>


    </div>


</section>


<!-- =====================================================
     EVENTS
===================================================== -->

<section
    class="events"
    id="events"
>


    <h2>
        Events
    </h2>


    <p class="events-description">

        Explore our latest events, performances,
        and memorable experiences.

    </p>


    <?php if (empty($events)): ?>


        <div class="featured-event">


            <img
                id="featuredImage"
                src="/logo.png"
                alt="ABAA Entertainment"
            >


            <video
                id="featuredVideo"
                controls
                playsinline
                preload="metadata"
                style="display:none;"
            >

                Your browser does not support
                the video tag.

            </video>


            <button
                type="button"
                id="featuredPlayButton"
                class="featured-play-button"
                style="display:none;"
                onclick="playFeaturedVideo()"
                aria-label="Play video"
            >

                <i class="fa-solid fa-play"></i>

            </button>


            <div
                class="featured-title"
                id="featuredTitle"
            >

                No events available

            </div>


        </div>


    <?php else: ?>


        <div
            class="featured-event"
            id="featuredEvent"
        >


            <img
                id="featuredImage"
                src="<?= e(
                    $firstType === 'video'
                        ? (
                            $firstThumbnail
                            ?: '/logo.png'
                        )
                        : $firstFile
                ) ?>"
                alt="<?= e($firstTitle) ?>"
                <?= (
                    $firstType === 'video'
                    && !$firstThumbnail
                )
                    ? 'style="display:none;"'
                    : ''
                ?>
            >


            <video
                id="featuredVideo"
                controls
                playsinline
                preload="metadata"
                <?= (
                    $firstType === 'video'
                    && !$firstThumbnail
                )
                    ? ''
                    : 'style="display:none;"'
                ?>
            >


                <?php if (
                    $firstType === 'video'
                ): ?>


                    <source
                        id="featuredVideoSource"
                        src="<?= e($firstFile) ?>"
                        type="<?= e($firstMimeType) ?>"
                    >


                <?php endif; ?>


                Your browser does not support
                the video tag.


            </video>


            <div
                id="featuredVideoLoading"
                class="featured-video-loading"
            ></div>


            <button
                type="button"
                id="featuredPlayButton"
                class="featured-play-button"
                <?= (
                    $firstType === 'video'
                    && $firstThumbnail
                )
                    ? ''
                    : 'style="display:none;"'
                ?>
                onclick="playFeaturedVideo()"
                aria-label="Play video"
            >

                <i class="fa-solid fa-play"></i>

            </button>


            <div
                class="featured-title"
                id="featuredTitle"
            >

                <?= e($firstTitle) ?>

            </div>


        </div>


        <div class="event-thumbnails">


            <?php foreach (
                $events as $index => $event
            ): ?>


                <?php

                $eventType =
                    $event['type'];

                $eventFile =
                    $event['file_url'];

                $eventTitle =
                    $event['title'];

                $eventThumbnail =
                    $event['thumbnail_url'];

                $eventMimeType =
                    $eventType === 'video'
                        ? getVideoMimeType(
                            $eventFile
                        )
                        : '';

                ?>


                <button
                    type="button"
                    class="event-thumbnail <?= $index === 0
                        ? 'active'
                        : ''
                    ?>"
                    onclick="showEvent(
                        <?= htmlspecialchars(
                            json_encode(
                                $eventType
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>,
                        <?= htmlspecialchars(
                            json_encode(
                                $eventFile
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>,
                        <?= htmlspecialchars(
                            json_encode(
                                $eventTitle
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>,
                        this,
                        <?= htmlspecialchars(
                            json_encode(
                                $eventThumbnail
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>,
                        <?= htmlspecialchars(
                            json_encode(
                                $eventMimeType
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    )"
                >


                    <?php if (
                        $eventType === 'video'
                    ): ?>


                        <div class="video-thumbnail">


                            <img
                                src="<?= e(
                                    $eventThumbnail
                                    ?: '/logo.png'
                                ) ?>"
                                alt="<?= e(
                                    $eventTitle
                                ) ?>"
                            >


                            <span
                                class="video-thumbnail-play"
                            >

                                <i
                                    class="fa-solid fa-play"
                                ></i>

                            </span>


                        </div>


                    <?php else: ?>


                        <img
                            src="<?= e(
                                $eventFile
                            ) ?>"
                            alt="<?= e(
                                $eventTitle
                            ) ?>"
                        >


                    <?php endif; ?>


                    <span class="event-thumbnail-title">

                        <?= e(
                            $eventTitle
                        ) ?>

                    </span>


                </button>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</section>


</main>


<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="footer">


<div class="footer-container">


    <!-- BRAND -->

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


    <!-- QUICK LINKS -->

    <div class="footer-section">


        <h3>
            Quick Links
        </h3>


        <a href="/">
            Home
        </a>


        <a href="/#events">
            Events
        </a>


        <a href="/#services">
            Services
        </a>


        <a href="/about">
            About Us
        </a>


        <a
            href="/booking-status"
            class="booking-status-link"
        >

            Check Booking Status

        </a>


    </div>


    <!-- DYNAMIC SERVICES -->

    <div class="footer-section">


        <h3>
            Our Services
        </h3>


        <?php if (empty($services)): ?>


            <span>
                No services available
            </span>


        <?php else: ?>


            <?php foreach (
                $services as $service
            ): ?>


                <a
                    href="/service?service=<?= e(
                        $service['slug']
                    ) ?>"
                >

                    <?= e(
                        $service['name']
                    ) ?>

                </a>


            <?php endforeach; ?>


        <?php endif; ?>


    </div>


    <!-- CONTACT -->

    <div class="footer-section">


        <h3>
            Contact Us
        </h3>


        <a
            href="https://www.google.com/maps/place/ABAA+Entertainment/@14.4652755,121.1915078,19z"
            target="_blank"
            rel="noopener noreferrer"
            class="contact-item"
        >


            <i class="fa-solid fa-location-dot"></i>


            <span>

                2F, Casa Ynares, P. Gomez,
                Libis, Binangonan, Rizal

            </span>


        </a>


        <a
            href="tel:+639231476552"
            class="contact-item"
        >


            <i class="fa-solid fa-phone"></i>


            <span>
                +63 923 147 6552
            </span>


        </a>


        <a
            href="mailto:abaaentertainment@gmail.com"
            class="contact-item"
        >


            <i class="fa-solid fa-envelope"></i>


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

                <i class="fa-brands fa-facebook-f"></i>

            </a>


            <a
                href="#"
                aria-label="Instagram"
            >

                <i class="fa-brands fa-instagram"></i>

            </a>


            <a
                href="https://www.tiktok.com/@markebpmbta?_r=1&_t=ZS-99DpdJXY5sD"
                target="_blank"
                rel="noopener noreferrer"
                aria-label="TikTok"
            >

                <i class="fa-brands fa-tiktok"></i>

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


</footer>


<!-- =====================================================
     BOOKING MODAL
===================================================== -->

<div
    class="booking-overlay"
    id="bookingModal"
    aria-hidden="true"
>


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

            Tell us about your event and our team
            will get back to you.

        </p>


    </div>


    <form
        action="/booking"
        method="POST"
        class="booking-form"
    >


        <!-- NAME + PHONE -->

        <div class="form-row">


            <div class="form-group">


                <label for="booking_name">
                    Full Name
                </label>


                <input
                    type="text"
                    id="booking_name"
                    name="name"
                    placeholder="Enter your full name"
                    required
                >


            </div>


            <div class="form-group">


                <label for="booking_phone">
                    Contact Number
                </label>


                <input
                    type="tel"
                    id="booking_phone"
                    name="phone"
                    placeholder="09XX XXX XXXX"
                    required
                >


            </div>


        </div>


        <!-- EMAIL + CONTACT PERSON -->

        <div class="form-row">


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


            <div class="form-group">


                <label for="booking_contact_person">
                    Contact Person
                </label>


                <input
                    type="text"
                    id="booking_contact_person"
                    name="contact_person"
                    placeholder="Contact person's name"
                    required
                >


            </div>


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


        <!-- SERVICES -->

        <div class="form-group">


            <label>
                Services Needed
            </label>


            <div class="service-checkboxes">


                <?php if (
                    !empty($services)
                ): ?>


                    <?php foreach (
                        $services as $service
                    ): ?>


                        <label class="service-checkbox">


                            <input
                                type="checkbox"
                                name="service[]"
                                value="<?= e(
                                    $service['name']
                                ) ?>"
                            >


                            <span>

                                <?= e(
                                    $service['name']
                                ) ?>

                            </span>


                        </label>


                    <?php endforeach; ?>


                <?php endif; ?>


                <!-- FULL EVENT PRODUCTION -->

                <label class="service-checkbox">


                    <input
                        type="checkbox"
                        name="service[]"
                        value="Full Event Production"
                    >


                    <span>
                        Full Event Production
                    </span>


                </label>


            </div>


        </div>


        <!-- MESSAGE -->

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


            <i class="fa-solid fa-arrow-right"></i>


        </button>


    </form>


</div>


</div>


<!-- =====================================================
     JAVASCRIPT
===================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| SHOW EVENT
|--------------------------------------------------------------------------
*/

function showEvent(
    type,
    source,
    title,
    button,
    thumbnail,
    mimeType
) {


    const image =
        document.getElementById(
            "featuredImage"
        );


    const video =
        document.getElementById(
            "featuredVideo"
        );


    const featuredTitle =
        document.getElementById(
            "featuredTitle"
        );


    const playButton =
        document.getElementById(
            "featuredPlayButton"
        );


    const loading =
        document.getElementById(
            "featuredVideoLoading"
        );


    if (
        !image ||
        !video ||
        !featuredTitle
    ) {


        console.error(
            "Featured event elements are missing."
        );


        return;

    }


    document
        .querySelectorAll(
            ".event-thumbnail"
        )
        .forEach(
            function(item) {


                item.classList.remove(
                    "active"
                );


            }
        );


    if (button) {


        button.classList.add(
            "active"
        );


    }


    featuredTitle.textContent =
        title;


    try {


        video.pause();


    } catch (error) {


        console.log(
            "Could not pause video:",
            error
        );


    }


    video.removeAttribute(
        "src"
    );


    video.load();


    if (loading) {


        loading.style.display =
            "none";


    }


    if (type === "image") {


        image.src =
            source;


        image.alt =
            title;


        image.style.display =
            "block";


        video.style.display =
            "none";


        if (playButton) {


            playButton.style.display =
                "none";


        }


        return;

    }


    if (type === "video") {


        if (thumbnail) {


            image.src =
                thumbnail;


            image.alt =
                title;


            image.style.display =
                "block";


        } else {


            image.style.display =
                "none";


        }


        video.src =
            source;


        if (mimeType) {


            video.setAttribute(
                "type",
                mimeType
            );


        }


        video.load();


        if (thumbnail) {


            video.style.display =
                "none";


            if (playButton) {


                playButton.style.display =
                    "flex";


            }


        } else {


            image.style.display =
                "none";


            video.style.display =
                "block";


            if (playButton) {


                playButton.style.display =
                    "none";


            }


        }


    }


}


/*
|--------------------------------------------------------------------------
| PLAY FEATURED VIDEO
|--------------------------------------------------------------------------
*/

function playFeaturedVideo()
{


    const image =
        document.getElementById(
            "featuredImage"
        );


    const video =
        document.getElementById(
            "featuredVideo"
        );


    const playButton =
        document.getElementById(
            "featuredPlayButton"
        );


    const loading =
        document.getElementById(
            "featuredVideoLoading"
        );


    if (!video) {


        return;


    }


    if (image) {


        image.style.display =
            "none";


    }


    video.style.display =
        "block";


    if (playButton) {


        playButton.style.display =
            "none";


    }


    if (loading) {


        loading.style.display =
            "block";


    }


    video.muted =
        false;


    const playPromise =
        video.play();


    if (
        playPromise !== undefined
    ) {


        playPromise
            .then(
                function() {


                    if (loading) {


                        loading.style.display =
                            "none";


                    }


                }
            )
            .catch(
                function(error) {


                    if (loading) {


                        loading.style.display =
                            "none";


                    }


                    console.error(
                        "Video play failed:",
                        error
                    );


                }
            );


    }


}


/*
|--------------------------------------------------------------------------
| FEATURED VIDEO EVENTS
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function() {


        const video =
            document.getElementById(
                "featuredVideo"
            );


        const loading =
            document.getElementById(
                "featuredVideoLoading"
            );


        const playButton =
            document.getElementById(
                "featuredPlayButton"
            );


        if (!video) {


            return;


        }


        video.addEventListener(
            "canplay",
            function() {


                if (loading) {


                    loading.style.display =
                        "none";


                }


            }
        );


        video.addEventListener(
            "waiting",
            function() {


                if (
                    video.style.display !==
                    "none"
                ) {


                    if (loading) {


                        loading.style.display =
                            "block";


                    }


                }


            }
        );


        video.addEventListener(
            "playing",
            function() {


                if (loading) {


                    loading.style.display =
                        "none";


                }


            }
        );


        video.addEventListener(
            "ended",
            function() {


                if (playButton) {


                    playButton.style.display =
                        "none";


                }


            }
        );


        video.addEventListener(
            "error",
            function() {


                if (loading) {


                    loading.style.display =
                        "none";


                }


                console.error(
                    "VIDEO LOAD ERROR"
                );


                console.error(
                    "Video URL:",
                    video.currentSrc
                );


                console.error(
                    "Video error:",
                    video.error
                );


            }
        );


    }
);


/*
|--------------------------------------------------------------------------
| BOOKING MODAL
|--------------------------------------------------------------------------
*/

function openBookingModal(event)
{


    if (event) {


        event.preventDefault();


    }


    const modal =
        document.getElementById(
            "bookingModal"
        );


    if (!modal) {


        return;


    }


    modal.classList.add(
        "active"
    );


    modal.setAttribute(
        "aria-hidden",
        "false"
    );


    document.body.style.overflow =
        "hidden";


}


function closeBookingModal()
{


    const modal =
        document.getElementById(
            "bookingModal"
        );


    if (!modal) {


        return;


    }


    modal.classList.remove(
        "active"
    );


    modal.setAttribute(
        "aria-hidden",
        "true"
    );


    document.body.style.overflow =
        "";


}


/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE BOOKING MODAL
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "click",
    function(event) {


        const modal =
            document.getElementById(
                "bookingModal"
            );


        if (
            modal &&
            event.target === modal
        ) {


            closeBookingModal();


        }


    }
);


/*
|--------------------------------------------------------------------------
| ESC KEY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "keydown",
    function(event) {


        if (
            event.key === "Escape"
        ) {


            closeBookingModal();


        }


    }
);


</script>


</body>

</html>
