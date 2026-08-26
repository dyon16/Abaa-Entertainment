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
        About Us | ABAA Entertainment
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
                src="/ads.mp4"
                type="video/mp4"
            >

            Your browser does not support the video tag.

        </video>

    </div>

</section>


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
