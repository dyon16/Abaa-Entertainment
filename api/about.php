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

    <!-- IMPORTANT:
         about.php is inside /api/
         but about.css is in the root
    -->
    <link
        rel="stylesheet"
        href="/about.css"
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

        <a
            href="/about"
            class="active"
        >
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
     ABOUT HERO
================================================== -->

<section class="about-hero">

    <div class="about-hero-content">

        <p class="small-title">
            ABOUT ABAA ENTERTAINMENT
        </p>

        <h1>
            <span>Powered by Passion.</span><br>
            <span>Driven by Excellence.</span>
        </h1>

        <p class="hero-description">
            We create unforgettable experiences through
            creativity, technology, passion, and professional
            event production.
        </p>

    </div>


</section>



<!-- ==================================================
     MAIN ABOUT PAGE
================================================== -->

<main class="about-page">


    <!-- ==================================================
         ABOUT COMPANY
    ================================================== -->

    <section class="about-company">

        <div class="about-text">

            <span class="section-label">
                WHO WE ARE
            </span>

            <h2>
                ABAA Entertainment
            </h2>

            <p>
                ABAA Entertainment is a forward-thinking
                entertainment company committed to developing
                talent, producing high-quality entertainment,
                and creating meaningful opportunities within
                the industry.
            </p>

            <p>
                Built on creativity, professionalism, and
                innovation, ABAA Entertainment provides a
                platform where artists, performers, event
                professionals, and creative individuals can
                showcase their talents and reach wider audiences.
            </p>

            <p>
                We believe that every event should be more
                than just a gathering. It should be an
                experience that people remember.
            </p>

        </div>


        <div class="about-image">

            <img
                src="/logo.png"
                alt="ABAA Entertainment"
            >

        </div>

    </section>



    <!-- ==================================================
         MISSION & VISION
    ================================================== -->

    <section class="mission-section">

        <div class="info-card">

            <div class="info-icon">
                <i class="fa-solid fa-bullseye"></i>
            </div>

            <h3>
                Our Mission
            </h3>

            <p>
                To deliver professional, creative, and
                high-quality entertainment services while
                providing opportunities for artists and
                creative professionals to grow and succeed.
            </p>

        </div>


        <div class="info-card">

            <div class="info-icon">
                <i class="fa-solid fa-eye"></i>
            </div>

            <h3>
                Our Vision
            </h3>

            <p>
                To become a trusted and influential
                entertainment company known for excellence,
                innovation, and unforgettable experiences.
            </p>

        </div>


        <div class="info-card">

            <div class="info-icon">
                <i class="fa-solid fa-star"></i>
            </div>

            <h3>
                Our Values
            </h3>

            <p>
                Creativity, professionalism, teamwork,
                innovation, integrity, and dedication
                guide everything we do.
            </p>

        </div>

    </section>



    <!-- ==================================================
         WHAT WE DO
    ================================================== -->

    <section
        class="what-we-do"
        id="services"
    >

        <div class="section-heading">

            <span class="section-label">
                WHAT WE DO
            </span>

            <h2>
                Creating Experiences
            </h2>

            <p>
                From technical production to live
                entertainment, we provide the tools
                and expertise needed to bring events
                to life.
            </p>

        </div>


        <div class="service-grid">

            <div class="service-card">

                <i class="fa-solid fa-display"></i>

                <h3>
                    LED Wall
                </h3>

                <p>
                    High-quality LED wall solutions
                    for concerts, corporate events,
                    celebrations, and productions.
                </p>

            </div>


            <div class="service-card">

                <i class="fa-solid fa-lightbulb"></i>

                <h3>
                    Lights & Sound
                </h3>

                <p>
                    Professional lighting and sound
                    production designed to enhance
                    every event.
                </p>

            </div>


            <div class="service-card">

                <i class="fa-solid fa-video"></i>

                <h3>
                    Live Feed
                </h3>

                <p>
                    Reliable live video production
                    and projection solutions for
                    large-scale events.
                </p>

            </div>


            <div class="service-card">

                <i class="fa-solid fa-layer-group"></i>

                <h3>
                    Stage Production
                </h3>

                <p>
                    Complete stage production and
                    technical support for memorable
                    performances.
                </p>

            </div>


            <div class="service-card">

                <i class="fa-solid fa-music"></i>

                <h3>
                    Music Studio
                </h3>

                <p>
                    Creative spaces and professional
                    equipment for music and audio
                    production.
                </p>

            </div>


            <div class="service-card">

                <i class="fa-solid fa-cubes"></i>

                <h3>
                    Trusses
                </h3>

                <p>
                    Safe and professional truss
                    solutions for lighting, LED walls,
                    and event equipment.
                </p>

            </div>

        </div>

    </section>



    <!-- ==================================================
         WHY CHOOSE US
    ================================================== -->

    <section class="why-us">

        <div class="why-image">

            <img
                src="/event5.jpg"
                alt="ABAA Entertainment Event"
            >

        </div>


        <div class="why-content">

            <span class="section-label">
                WHY ABAA ENTERTAINMENT
            </span>

            <h2>
                Built For Unforgettable Events
            </h2>

            <p>
                We combine creativity, technology,
                and professional event production
                to create experiences that leave
                a lasting impression.
            </p>


            <div class="feature">

                <i class="fa-solid fa-check"></i>

                <div>

                    <h4>
                        Professional Production
                    </h4>

                    <p>
                        Reliable equipment and experienced
                        event professionals.
                    </p>

                </div>

            </div>


            <div class="feature">

                <i class="fa-solid fa-check"></i>

                <div>

                    <h4>
                        Creative Solutions
                    </h4>

                    <p>
                        Customized entertainment solutions
                        designed around your event.
                    </p>

                </div>

            </div>


            <div class="feature">

                <i class="fa-solid fa-check"></i>

                <div>

                    <h4>
                        Memorable Experiences
                    </h4>

                    <p>
                        We focus on creating events that
                        audiences will remember.
                    </p>

                </div>

            </div>

        </div>

    </section>



    <!-- ==================================================
         CALL TO ACTION
    ================================================== -->

    <section class="about-cta">

        <span class="section-label">
            LET'S WORK TOGETHER
        </span>

        <h2>
            Ready To Create Something Amazing?
        </h2>

        <p>
            Let ABAA Entertainment help bring your next
            event, performance, or project to life.
        </p>


        <a
            href="#"
            class="cta-button"
            onclick="openBookingModal(event)"
        >

            Book An Event

            <i class="fa-solid fa-arrow-right"></i>

        </a>

    </section>

</main>



<!-- ==================================================
     FOOTER
================================================== -->

<footer class="footer">

    <div class="footer-container">


        <!-- COMPANY -->

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

        </div>



        <!-- SERVICES -->

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
                Stage
            </a>

            <a href="/service?service=music-studio">
                Music Studio
            </a>

            <a href="/service?service=trusses">
                Trusses
            </a>

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
                    2F, Casa Ynares, P. Gomez, Libis,
                    Binangonan, Rizal
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
                    href="https://www.tiktok.com/@markebpmbta?_r=1&_t=ZS-99DpdJXY5sDh"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="TikTok"
                >

                    <i class="fa-brands fa-tiktok"></i>

                </a>

            </div>

        </div>

    </div>



    <!-- FOOTER BOTTOM -->

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
     BOOKING POPUP
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
                        placeholder="Enter contact person's name"
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



<!-- ==================================================
     JAVASCRIPT
================================================== -->

<script>

function openBookingModal(event) {

    if (event) {
        event.preventDefault();
    }

    const modal =
        document.getElementById("bookingModal");

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
        document.getElementById("bookingModal");

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
            document.getElementById("bookingModal");

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



<?php

if (isset($conn)) {
    $conn->close();
}

?>

</body>

</html>
