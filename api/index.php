<?php

include(__DIR__ . '/conn.php');

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
        href="style.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>


<header class="header">

    <a
        href="index.php"
        class="logo"
    >

        <img
            src="logo.png"
            alt="ABAA Entertainment Logo"
        >

    </a>


    <nav>

        <a href="index.php">
            Home
        </a>

        <a href="#events">
            Events
        </a>

        <a href="#services">
            Services
        </a>

        <a href="about.php">
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

<section
    class="about-us"
    id="about"
>

    <div class="about-content">

        <h1>
            ABAA Entertainment
        </h1>

        <p>

            ABAA Entertainment is a forward-thinking
            entertainment company committed to developing
            talent, producing high-quality entertainment,
            and creating meaningful opportunities within
            the industry.

            <br><br>

            Built on creativity, professionalism, and
            innovation, ABAA Entertainment provides a
            platform where artists, performers, and creative
            professionals can showcase their talents and
            reach wider audiences.

            <br><br>

            The company focuses on nurturing emerging talent,
            developing engaging entertainment projects, and
            building strong partnerships that contribute to
            the growth of the entertainment community.

        </p>

    </div>


    <div class="about-video">

        <video
            controls
            autoplay
            muted
            loop
            playsinline
        >

            <source
                src="ads.mp4"
                type="video/mp4"
            >

            Your browser does not support the video tag.

        </video>

    </div>

</section>


<main class="main-container">


    <section class="big-box">

        <img
            src="logo.png"
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
                href="service.php?service=led-wall"
                class="image-button"
            >

                <img
                    src="service1.png"
                    alt="LED Wall"
                >

                <div class="image-title">
                    LED Wall
                </div>

            </a>


            <a
                href="service.php?service=lights-sound"
                class="image-button"
            >

                <img
                    src="service2.png"
                    alt="Lights and Sound"
                >

                <div class="image-title">
                    Lights and Sound
                </div>

            </a>


            <a
                href="service.php?service=live-feed"
                class="image-button"
            >

                <img
                    src="service3.png"
                    alt="Live Feed"
                >

                <div class="image-title">
                    Live Feed
                </div>

            </a>


            <a
                href="service.php?service=stage"
                class="image-button"
            >

                <img
                    src="service4.png"
                    alt="Stage Production"
                >

                <div class="image-title">
                    Stage
                </div>

            </a>


            <a
                href="service.php?service=music-studio"
                class="image-button"
            >

                <img
                    src="service5.png"
                    alt="Music Studio"
                >

                <div class="image-title">
                    Music Studio
                </div>

            </a>


            <a
                href="service.php?service=trusses"
                class="image-button"
            >

                <img
                    src="service6.png"
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


        <div class="featured-event">

            <img
                id="featuredImage"
                src="event1.jpg"
                alt="Android18 x UYRE"
            >


            <video
                id="featuredVideo"
                controls
                muted
                loop
                playsinline
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
                Android18 x UYRE
            </div>

        </div>


        <div class="event-thumbnails">


            <button
                type="button"
                class="event-thumbnail active"
                onclick="showEvent(
                    'image',
                    'event1.jpg',
                    'Android18 x UYRE',
                    this
                )"
            >

                <img
                    src="event1.jpg"
                    alt="Android18 x UYRE"
                >

                <span>
                    Android18 x UYRE
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event2.jpg',
                    'Android18 x UYRE',
                    this
                )"
            >

                <img
                    src="event2.jpg"
                    alt="Android18 x UYRE"
                >

                <span>
                    Android18 x UYRE
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event3.jpg',
                    'Music Festival - Cavite',
                    this
                )"
            >

                <img
                    src="event3.jpg"
                    alt="Music Festival - Cavite"
                >

                <span>
                    Music Festival
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event4.jpg',
                    'Wofex',
                    this
                )"
            >

                <img
                    src="event4.jpg"
                    alt="Wofex"
                >

                <span>
                    Wofex
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event5.jpg',
                    'Grand Youth Day',
                    this
                )"
            >

                <img
                    src="event5.jpg"
                    alt="Grand Youth Day"
                >

                <span>
                    Grand Youth Day
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event6.jpg',
                    'FirstFilm Project',
                    this
                )"
            >

                <img
                    src="event6.jpg"
                    alt="FirstFilm Project"
                >

                <span>
                    FirstFilm Project
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event7.jpg',
                    'Concert Event',
                    this
                )"
            >

                <img
                    src="event7.jpg"
                    alt="Concert Event"
                >

                <span>
                    Concert
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event8.jpg',
                    'Product Launch',
                    this
                )"
            >

                <img
                    src="event8.jpg"
                    alt="Product Launch"
                >

                <span>
                    Product Launch
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'image',
                    'event9.jpg',
                    'Festival Celebration',
                    this
                )"
            >

                <img
                    src="event9.jpg"
                    alt="Festival Celebration"
                >

                <span>
                    Festival
                </span>

            </button>


            <button
                type="button"
                class="event-thumbnail"
                onclick="showEvent(
                    'video',
                    'event10.mp4',
                    'Live Event Highlights',
                    this
                )"
            >

                <div class="video-thumbnail">

                    <img
                        src="event10-img.jpg"
                        alt="Live Event Highlights"
                    >

                    <i class="fa-solid fa-play"></i>

                </div>

                <span>
                    Highlights
                </span>

            </button>

        </div>

    </section>

</main>


<!-- ==================================================
     FOOTER
================================================== -->

<footer class="footer">

    <div class="footer-container">


        <div class="footer-section footer-brand">

            <img
                src="logo.png"
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

            <a href="index.php">
                Home
            </a>

            <a href="#events">
                Events
            </a>

            <a href="#services">
                Services
            </a>

            <a href="about.php">
                About Us
            </a>

        </div>


        <div class="footer-section">

            <h3>
                Our Services
            </h3>

            <a href="service.php?service=led-wall">
                LED Wall
            </a>

            <a href="service.php?service=lights-sound">
                Lights & Sound
            </a>

            <a href="service.php?service=live-feed">
                Live Feed
            </a>

            <a href="service.php?service=stage">
                Stage Production
            </a>

            <a href="service.php?service=music-studio">
                Music Studio
            </a>

            <a href="service.php?service=trusses">
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
                    href="https://www.tiktok.com/@malupiton_officialph"
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
            © 2026 ABAA Entertainment.
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
            action="booking.php"
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


            <div class="form-group">

                <label for="booking_service">
                    Service Needed
                </label>

                <select
                    id="booking_service"
                    name="service"
                    required
                >

                    <option
                        value=""
                        disabled
                        selected
                    >
                        Select a service
                    </option>

                    <option value="LED Wall">
                        LED Wall
                    </option>

                    <option value="Lights & Sound">
                        Lights & Sound
                    </option>

                    <option value="Live Feed">
                        Live Feed
                    </option>

                    <option value="Stage Production">
                        Stage Production
                    </option>

                    <option value="Music Studio">
                        Music Studio
                    </option>

                    <option value="Trusses">
                        Trusses
                    </option>

                    <option value="Full Event Production">
                        Full Event Production
                    </option>

                </select>

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
    button
) {

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


    document
        .querySelectorAll(".event-thumbnail")
        .forEach(function(item) {

            item.classList.remove("active");

        });


    button.classList.add("active");


    featuredTitle.textContent = title;


    if (type === "image") {

        video.pause();

        video.style.display = "none";

        image.src = source;

        image.style.display = "block";

    }


    if (type === "video") {

        image.style.display = "none";

        videoSource.src = source;

        video.load();

        video.style.display = "block";

        video.play().catch(
            function() {}
        );

    }

}


/*
|--------------------------------------------------------------------------
| OPEN BOOKING MODAL
|--------------------------------------------------------------------------
*/

function openBookingModal(event) {

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

    modal.classList.add("active");

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

function closeBookingModal() {

    const modal =
        document.getElementById(
            "bookingModal"
        );

    if (!modal) {

        return;

    }

    modal.classList.remove("active");

    modal.setAttribute(
        "aria-hidden",
        "true"
    );

    document.body.style.overflow =
        "";

}


/*
|--------------------------------------------------------------------------
| CLICK OUTSIDE
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

        if (event.key === "Escape") {

            closeBookingModal();

        }

    }
);

</script>


</body>

</html>
