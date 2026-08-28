<?php

include(__DIR__ . '/conn.php');

/*
|--------------------------------------------------------------------------
| LOAD VISIBLE EVENTS
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

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        'Event query error: ' .
        $e->getMessage()
    );

}


/*
|--------------------------------------------------------------------------
| HELPER
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


/*
|--------------------------------------------------------------------------
| GET VIDEO MIME TYPE
|--------------------------------------------------------------------------
*/

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
        case 'ogv':
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

</head>


<body>


<!-- ==================================================
     HEADER
================================================== -->

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



<!-- ==================================================
     ABOUT
================================================== -->

<section class="about-hero">

    <div class="about-hero-content">

        <p class="small-title">
            ABOUT ABAA ENTERTAINMENT
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



<main class="main-container">


    <!-- ==================================================
         ABOUT / LOGO
    ================================================== -->

    <section class="big-box">

        <img
            src="/logo.png"
            alt="ABAA Entertainment"
        >

    </section>



    <!-- ==================================================
         SERVICES
    ================================================== -->

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


            <a
                href="/service?service=led-wall"
                class="image-button"
            >

                <img
                    src="/service1.png"
                    alt="LED Wall"
                >

                <div class="image-title">
                    LED Wall
                </div>

            </a>


            <a
                href="/service?service=lights-sound"
                class="image-button"
            >

                <img
                    src="/service2.png"
                    alt="Lights and Sound"
                >

                <div class="image-title">
                    Lights and Sound
                </div>

            </a>


            <a
                href="/service?service=live-feed"
                class="image-button"
            >

                <img
                    src="/service3.png"
                    alt="Live Feed"
                >

                <div class="image-title">
                    Live Feed
                </div>

            </a>


            <a
                href="/service?service=stage"
                class="image-button"
            >

                <img
                    src="/service4.png"
                    alt="Stage Production"
                >

                <div class="image-title">
                    Stage
                </div>

            </a>


            <a
                href="/service?service=music-studio"
                class="image-button"
            >

                <img
                    src="/service5.png"
                    alt="Music Studio"
                >

                <div class="image-title">
                    Music Studio
                </div>

            </a>


            <a
                href="/service?service=trusses"
                class="image-button"
            >

                <img
                    src="/service6.png"
                    alt="Trusses"
                >

                <div class="image-title">
                    Trusses
                </div>

            </a>

        </div>

    </section>



    <!-- ==================================================
         EVENTS
    ================================================== -->

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


            <!-- ==================================================
                 NO EVENTS
            ================================================== -->

            <div class="featured-event">

                <img
                    id="featuredImage"
                    src="/logo.png"
                    alt="ABAA Entertainment"
                >

                <video
                    id="featuredVideo"
                    controls
                    loop
                    playsinline
                    preload="metadata"
                    style="display:none;"
                >

                    <source
                        id="featuredVideoSource"
                        src=""
                        type="video/mp4"
                    >

                    Your browser does not support
                    the video tag.

                </video>


                <div
                    class="featured-title"
                    id="featuredTitle"
                >

                    No events available

                </div>

            </div>


        <?php else: ?>


            <!-- ==================================================
                 FIRST EVENT
            ================================================== -->

            <?php

            $firstEvent =
                $events[0];

            $firstFile =
                $firstEvent['file_url'];

            $firstTitle =
                $firstEvent['title'];

            $firstType =
                $firstEvent['type'];

            $firstThumbnail =
                $firstEvent['thumbnail_url'];

            $firstMimeType =
                $firstType === 'video'
                    ? getVideoMimeType($firstFile)
                    : '';

            ?>


            <!-- ==================================================
                 FEATURED EVENT
            ================================================== -->

            <div class="featured-event">


                <?php if ($firstType === 'video'): ?>


                    <!-- ==========================================
                         VIDEO THUMBNAIL
                    =========================================== -->

                    <img
                        id="featuredImage"
                        src="<?= e(
                            $firstThumbnail ?: '/logo.png'
                        ) ?>"
                        alt="<?= e($firstTitle) ?>"
                        <?= $firstThumbnail
                            ? ''
                            : 'style="display:none;"'
                        ?>
                    >


                    <!-- ==========================================
                         VIDEO PLAYER
                    =========================================== -->

                    <video
                        id="featuredVideo"
                        controls
                        loop
                        playsinline
                        preload="metadata"
                        <?= $firstThumbnail
                            ? 'style="display:none;"'
                            : ''
                        ?>
                    >

                        <source
                            id="featuredVideoSource"
                            src="<?= e($firstFile) ?>"
                            type="<?= e($firstMimeType) ?>"
                        >

                        Your browser does not support
                        the video tag.

                    </video>


                    <!-- ==========================================
                         BIG VIDEO PLAY BUTTON
                    =========================================== -->

                    <?php if ($firstThumbnail): ?>

                        <button
                            type="button"
                            id="featuredPlayButton"
                            class="featured-play-button"
                            onclick="playFeaturedVideo()"
                            aria-label="Play video"
                        >

                            <i class="fa-solid fa-play"></i>

                        </button>

                    <?php endif; ?>


                <?php else: ?>


                    <!-- ==========================================
                         IMAGE
                    =========================================== -->

                    <img
                        id="featuredImage"
                        src="<?= e($firstFile) ?>"
                        alt="<?= e($firstTitle) ?>"
                    >


                    <!-- ==========================================
                         HIDDEN VIDEO
                    =========================================== -->

                    <video
                        id="featuredVideo"
                        controls
                        loop
                        playsinline
                        preload="metadata"
                        style="display:none;"
                    >

                        <source
                            id="featuredVideoSource"
                            src=""
                            type="video/mp4"
                        >

                        Your browser does not support
                        the video tag.

                    </video>


                <?php endif; ?>


                <!-- ==========================================
                     TITLE
                =========================================== -->

                <div
                    class="featured-title"
                    id="featuredTitle"
                >

                    <?= e($firstTitle) ?>

                </div>

            </div>



            <!-- ==================================================
                 EVENT THUMBNAILS
            ================================================== -->

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
                            ? getVideoMimeType($eventFile)
                            : '';

                    ?>


                    <button
                        type="button"
                        class="event-thumbnail <?= $index === 0
                            ? 'active'
                            : ''
                        ?>"
                        onclick="showEvent(
                            <?= json_encode($eventType) ?>,
                            <?= json_encode($eventFile) ?>,
                            <?= json_encode($eventTitle) ?>,
                            this,
                            <?= json_encode($eventThumbnail) ?>,
                            <?= json_encode($eventMimeType) ?>
                        )"
                    >


                        <?php if (
                            $eventType === 'video'
                        ): ?>


                            <!-- ==================================
                                 VIDEO SMALL THUMBNAIL
                            =================================== -->

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


                                <!-- ORANGE PLAY ICON ONLY -->

                                <span
                                    class="video-thumbnail-play"
                                >

                                    <i
                                        class="fa-solid fa-play"
                                    ></i>

                                </span>

                            </div>


                        <?php else: ?>


                            <!-- ==================================
                                 IMAGE SMALL THUMBNAIL
                            =================================== -->

                            <img
                                src="<?= e(
                                    $eventFile
                                ) ?>"
                                alt="<?= e(
                                    $eventTitle
                                ) ?>"
                            >


                        <?php endif; ?>


                        <span>

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



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="footer">

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


        <div class="footer-section">

            <h3>
                Our Services
            </h3>

            <a href="/service?service=led-wall">
                LED Wall
            </a>

            <a href="/service?service=lights-sound">
                Lights & Sound
            </a>

            <a href="/service?service=live-feed">
                Live Feed
            </a>

            <a href="/service?service=stage">
                Stage Production
            </a>

            <a href="/service?service=music-studio">
                Music Studio
            </a>

            <a href="/service?service=trusses">
                Trusses
            </a>

        </div>


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



<!-- ==================================================
     BOOKING MODAL
================================================== -->

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



            <!-- ==================================================
                 SERVICES
            ================================================== -->

            <div class="form-group">

                <label>
                    Services Needed
                </label>


                <div class="service-checkboxes">


                    <label class="service-checkbox">

                        <input
                            type="checkbox"
                            name="service[]"
                            value="LED Wall"
                        >

                        <span>
                            LED Wall
                        </span>

                    </label>


                    <label class="service-checkbox">

                        <input
                            type="checkbox"
                            name="service[]"
                            value="Lights & Sound"
                        >

                        <span>
                            Lights & Sound
                        </span>

                    </label>


                    <label class="service-checkbox">

                        <input
                            type="checkbox"
                            name="service[]"
                            value="Live Feed"
                        >

                        <span>
                            Live Feed
                        </span>

                    </label>


                    <label class="service-checkbox">

                        <input
                            type="checkbox"
                            name="service[]"
                            value="Stage Production"
                        >

                        <span>
                            Stage Production
                        </span>

                    </label>


                    <label class="service-checkbox">

                        <input
                            type="checkbox"
                            name="service[]"
                            value="Music Studio"
                        >

                        <span>
                            Music Studio
                        </span>

                    </label>


                    <label class="service-checkbox">

                        <input
                            type="checkbox"
                            name="service[]"
                            value="Trusses"
                        >

                        <span>
                            Trusses
                        </span>

                    </label>


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



<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script>


/*
|--------------------------------------------------------------------------
| EVENT SWITCHER
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

    /*
    |--------------------------------------------------------------------------
    | GET ELEMENTS
    |--------------------------------------------------------------------------
    */

    const image =
        document.getElementById(
            "featuredImage"
        );

    const video =
        document.getElementById(
            "featuredVideo"
        );

    const videoSource =
        document.getElementById(
            "featuredVideoSource"
        );

    const featuredTitle =
        document.getElementById(
            "featuredTitle"
        );

    const playButton =
        document.getElementById(
            "featuredPlayButton"
        );


    /*
    |--------------------------------------------------------------------------
    | REMOVE ACTIVE STATE
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            ".event-thumbnail"
        )
        .forEach(function(item) {

            item.classList.remove(
                "active"
            );

        });


    /*
    |--------------------------------------------------------------------------
    | ADD ACTIVE STATE
    |--------------------------------------------------------------------------
    */

    if (button) {

        button.classList.add(
            "active"
        );

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE TITLE
    |--------------------------------------------------------------------------
    */

    if (featuredTitle) {

        featuredTitle.textContent =
            title;

    }


    /*
    |--------------------------------------------------------------------------
    | STOP CURRENT VIDEO
    |--------------------------------------------------------------------------
    */

    if (video) {

        video.pause();

    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE EVENT
    |--------------------------------------------------------------------------
    */

    if (type === "image") {


        /*
        | Clear video source
        */

        if (videoSource) {

            videoSource.removeAttribute(
                "src"
            );

        }

        if (video) {

            video.removeAttribute(
                "src"
            );

            video.load();

            video.style.display =
                "none";

        }


        /*
        | Hide play button
        */

        if (playButton) {

            playButton.style.display =
                "none";

        }


        /*
        | Show image
        */

        if (image) {

            image.src =
                source;

            image.style.display =
                "block";

        }


        return;

    }


    /*
    |--------------------------------------------------------------------------
    | VIDEO EVENT
    |--------------------------------------------------------------------------
    */

    if (type === "video") {


        /*
        | Hide image
        */

        if (image) {

            image.style.display =
                "none";

        }


        /*
        | Set video source
        */

        if (videoSource) {

            videoSource.src =
                source;


            if (mimeType) {

                videoSource.type =
                    mimeType;

            } else {

                videoSource.type =
                    "video/mp4";

            }

        }


        /*
        | Reload video
        */

        if (video) {

            video.load();

            video.style.display =
                "block";

        }


        /*
        | Hide old play overlay
        |
        | The browser's own controls
        | will handle play/pause.
        */

        if (playButton) {

            playButton.style.display =
                "none";

        }


        /*
        | IMPORTANT:
        |
        | Do NOT call video.play() here.
        |
        | This allows the user to decide
        | when to play and control volume.
        */

        return;

    }

}



/*
|--------------------------------------------------------------------------
| PLAY FEATURED VIDEO
|--------------------------------------------------------------------------
|
| This is only used for the orange
| play button displayed over the
| first video's thumbnail.
|
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


    if (!video) {

        return;

    }


    /*
    | Hide thumbnail
    */

    if (image) {

        image.style.display =
            "none";

    }


    /*
    | Show video
    */

    video.style.display =
        "block";


    /*
    | Hide orange button
    */

    if (playButton) {

        playButton.style.display =
            "none";

    }


    /*
    | Start video.
    |
    | IMPORTANT:
    | There is NO muted attribute,
    | so the browser's normal volume
    | controls remain available.
    */

    video.play().catch(
        function(error) {

            console.log(
                "Unable to play video:",
                error
            );

        }
    );

}



/*
|--------------------------------------------------------------------------
| VIDEO ERROR HANDLER
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function() {

        const video =
            document.getElementById(
                "featuredVideo"
            );


        if (!video) {

            return;

        }


        video.addEventListener(
            "error",
            function() {

                console.error(
                    "Unable to load event video."
                );

                console.error(
                    "Video source:",
                    video.currentSrc
                );

            }
        );


        /*
        | Debug information
        */

        video.addEventListener(
            "loadedmetadata",
            function() {

                console.log(
                    "Video loaded successfully."
                );

                console.log(
                    "Video URL:",
                    video.currentSrc
                );

                console.log(
                    "Has audio:",
                    video.mozHasAudio ||
                    video.webkitAudioDecodedByteCount > 0 ||
                    video.audioTracks?.length > 0
                );

            }
        );

    }
);



/*
|--------------------------------------------------------------------------
| OPEN BOOKING MODAL
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



/*
|--------------------------------------------------------------------------
| CLOSE BOOKING MODAL
|--------------------------------------------------------------------------
*/

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
| CLICK OUTSIDE MODAL
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



<!-- ==================================================
     EVENT VIDEO CSS
================================================== -->

<style>

/*
|--------------------------------------------------------------------------
| FEATURED EVENT
|--------------------------------------------------------------------------
*/

.featured-event {

    position: relative;

}


/*
|--------------------------------------------------------------------------
| FEATURED IMAGE
|--------------------------------------------------------------------------
*/

.featured-event > img {

    display: block;

}


/*
|--------------------------------------------------------------------------
| FEATURED VIDEO
|--------------------------------------------------------------------------
*/

.featured-event > video {

    display: block;

    width: 100%;

    max-width: 100%;

}


/*
|--------------------------------------------------------------------------
| BIG ORANGE PLAY BUTTON
|--------------------------------------------------------------------------
*/

.featured-play-button {

    position: absolute;

    left: 50%;
    top: 50%;

    transform:
        translate(-50%, -50%);

    width: 70px;
    height: 70px;

    border: none;

    border-radius: 50%;

    background:
        rgba(
            255,
            90,
            31,
            0.95
        );

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    cursor: pointer;

    font-size: 24px;

    box-shadow:
        0 8px 30px
        rgba(
            0,
            0,
            0,
            0.30
        );

    z-index: 5;

    transition:
        transform .2s ease,
        background .2s ease;

}


/*
|--------------------------------------------------------------------------
| BIG PLAY HOVER
|--------------------------------------------------------------------------
*/

.featured-play-button:hover {

    transform:
        translate(-50%, -50%)
        scale(1.08);

    background:
        rgba(
            255,
            90,
            31,
            1
        );

}


/*
|--------------------------------------------------------------------------
| VIDEO THUMBNAIL
|--------------------------------------------------------------------------
|
| IMPORTANT:
| No black background here.
|
*/

.video-thumbnail {

    position: relative;

    width: 100%;
    height: 100%;

    overflow: hidden;

}


/*
|--------------------------------------------------------------------------
| VIDEO THUMBNAIL IMAGE
|--------------------------------------------------------------------------
*/

.video-thumbnail img {

    width: 100%;
    height: 100%;

    object-fit: cover;

    display: block;

}


/*
|--------------------------------------------------------------------------
| SMALL ORANGE PLAY ICON
|--------------------------------------------------------------------------
|
| No black circle.
| No black background.
|
*/

.video-thumbnail-play {

    position: absolute;

    left: 50%;
    top: 50%;

    transform:
        translate(-50%, -50%);

    width: 38px;
    height: 38px;

    border-radius: 50%;

    background:
        rgba(
            255,
            90,
            31,
            0.95
        );

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    pointer-events: none;

}


/*
|--------------------------------------------------------------------------
| SMALL PLAY ICON
|--------------------------------------------------------------------------
*/

.video-thumbnail-play i {

    font-size: 13px;

}


/*
|--------------------------------------------------------------------------
| EVENT THUMBNAIL BUTTON
|--------------------------------------------------------------------------
|
| Make sure the button itself does
| not create an unwanted black area.
|
*/

.event-thumbnail {

    background: transparent;

}


/*
|--------------------------------------------------------------------------
| VIDEO THUMBNAIL BUTTON IMAGE
|--------------------------------------------------------------------------
*/

.event-thumbnail .video-thumbnail {

    background: transparent;

}

</style>


</body>

</html>
