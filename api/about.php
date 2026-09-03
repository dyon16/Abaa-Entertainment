ChatGPT said:
Absolutely. Below is the complete updated about.php, keeping your existing design/content but making the Services section fully clickable and adding Full Event Production.

<?php include(__DIR__ . '/conn.php'); ?> <!DOCTYPE html> <html lang="en"> <head>
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
    href="/about.css"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

</head> <body> <!-- ================================================== HEADER ================================================== --> <header class="header">
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

</header> <!-- ================================================== ABOUT HERO ================================================== --> <section class="about-us" id="about" >
<div class="about-content">

    <h1>
        ABAA Entertainment
    </h1>

    <p>

        Founded in 2022 by Mr. Russel Ynares and
        Mr. Koy Quevedo, ABAA Entertainment Inc.
        began with a simple passion for music,
        live performances, and creating memorable
        experiences.

        <br><br>

        What started as a shared passion for music
        gradually evolved into a professional
        entertainment company dedicated to bringing
        ideas, artists, and events to life.

        Through hard work, creativity, and a commitment
        to quality, ABAA Entertainment grew from its
        musical roots into a company capable of
        handling a wide range of entertainment and
        production requirements.

    </p>

</div>


<div class="about-video">

    <video
        controls
        autoplay
        muted
        loop
        playsinline
        preload="auto"
    >

        <source
            src="/ads.mp4"
            type="video/mp4"
        >

        Your browser does not support
        the video tag.

    </video>

</div>

</section> <!-- ================================================== MAIN ABOUT CONTENT ================================================== --> <main class="about-page">
<!-- ==================================================
     WHO WE ARE
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
            entertainment company committed to
            developing talent, producing high-quality
            entertainment, and creating meaningful
            opportunities within the industry.
        </p>

        <p>
            Built on creativity, professionalism, and
            innovation, ABAA Entertainment provides a
            platform where artists, performers, event
            professionals, and creative individuals can
            showcase their talents and reach wider
            audiences.
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
     MISSION / VISION / VALUES
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
     SERVICES
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
            Our Services
        </h2>

        <p>
            From technical production to live
            entertainment, ABAA Entertainment provides
            professional services designed to bring
            your event to life.
        </p>

    </div>



    <div class="service-grid">


        <!-- ==================================================
             LED WALL
        ================================================== -->

        <a
            href="/service?service=led-wall"
            class="service-card"
        >

            <i class="fa-solid fa-display"></i>

            <h3>
                LED Wall
            </h3>

            <p>
                High-quality LED wall solutions for
                concerts, corporate events, celebrations,
                festivals, and large-scale productions.
            </p>

            <span class="service-link">

                Learn More

                <i class="fa-solid fa-arrow-right"></i>

            </span>

        </a>



        <!-- ==================================================
             LIGHTS & SOUND
        ================================================== -->

        <a
            href="/service?service=lights-sound"
            class="service-card"
        >

            <i class="fa-solid fa-lightbulb"></i>

            <h3>
                Lights & Sound
            </h3>

            <p>
                Professional lighting and sound
                production designed to create an immersive
                and memorable event experience.
            </p>

            <span class="service-link">

                Learn More

                <i class="fa-solid fa-arrow-right"></i>

            </span>

        </a>



        <!-- ==================================================
             LIVE FEED
        ================================================== -->

        <a
            href="/service?service=live-feed"
            class="service-card"
        >

            <i class="fa-solid fa-video"></i>

            <h3>
                Live Feed
            </h3>

            <p>
                Reliable live video production, camera
                systems, screens, and projection solutions
                for events and large audiences.
            </p>

            <span class="service-link">

                Learn More

                <i class="fa-solid fa-arrow-right"></i>

            </span>

        </a>



        <!-- ==================================================
             STAGE PRODUCTION
        ================================================== -->

        <a
            href="/service?service=stage"
            class="service-card"
        >

            <i class="fa-solid fa-layer-group"></i>

            <h3>
                Stage Production
            </h3>

            <p>
                Complete stage production and technical
                support for concerts, performances,
                corporate events, and special occasions.
            </p>

            <span class="service-link">

                Learn More

                <i class="fa-solid fa-arrow-right"></i>

            </span>

        </a>



        <!-- ==================================================
             MUSIC STUDIO
        ================================================== -->

        <a
            href="/service?service=music-studio"
            class="service-card"
        >

            <i class="fa-solid fa-music"></i>

            <h3>
                Music Studio
            </h3>

            <p>
                Professional creative spaces and equipment
                for music recording, audio production,
                rehearsals, and creative projects.
            </p>

            <span class="service-link">

                Learn More

                <i class="fa-solid fa-arrow-right"></i>

            </span>

        </a>



        <!-- ==================================================
             TRUSSES
        ================================================== -->

        <a
            href="/service?service=trusses"
            class="service-card"
        >

            <i class="fa-solid fa-cubes"></i>

            <h3>
                Trusses
            </h3>

            <p>
                Professional truss solutions for LED
                walls, lighting systems, stage equipment,
                and event installations.
            </p>

            <span class="service-link">

                Learn More

                <i class="fa-solid fa-arrow-right"></i>

            </span>

        </a>



        <!-- ==================================================
             FULL EVENT PRODUCTION
        ================================================== -->

        <a
            href="/service?service=full-event-production"
            class="service-card"
        >

            <i class="fa-solid fa-wand-magic-sparkles"></i>

            <h3>
                Full Event Production
            </h3>

            <p>
                End-to-end event production combining
                staging, lights, sound, LED walls,
                live feed, technical crews, and event
                support.
            </p>

            <span class="service-link">

                Learn More

                <i class="fa-solid fa-arrow-right"></i>

            </span>

        </a>


    </div>

</section>



<!-- ==================================================
     WHY ABAA
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
     CTA
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

</main> <!-- ================================================== FOOTER ================================================== --> <footer class="footer">
<div class="footer-container">


    <!-- ==================================================
         FOOTER BRAND
    ================================================== -->

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



    <!-- ==================================================
         QUICK LINKS
    ================================================== -->

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



    <!-- ==================================================
         SERVICES
    ================================================== -->

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

        <a href="/service?service=full-event-production">
            Full Event Production
        </a>

    </div>



    <!-- ==================================================
         CONTACT
    ================================================== -->

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



        <!-- SOCIAL LINKS -->

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



<!-- ==================================================
     FOOTER BOTTOM
================================================== -->

<div class="footer-bottom">

    <p>
        © 2026 ABAA Entertainment.
        All Rights Reserved.
    </p>

    <p>
        Entertainment • Events • Experiences
    </p>

</div>

</footer> <!-- ================================================== BOOKING MODAL ================================================== --> <div class="booking-overlay" id="bookingModal" aria-hidden="true" >
<div class="booking-modal">


    <!-- CLOSE -->

    <button
        type="button"
        class="booking-close"
        onclick="closeBookingModal()"
        aria-label="Close booking form"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>



    <!-- HEADER -->

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



    <!-- ==================================================
         BOOKING FORM
    ================================================== -->

    <form
        action="/booking"
        method="POST"
        class="booking-form"
        id="bookingForm"
    >


        <!-- NAME / PHONE -->

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



        <!-- EMAIL / CONTACT PERSON -->

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



        <!-- EVENT TYPE / DATE -->

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



        <!-- ==================================================
             SERVICES NEEDED
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

            <i class="fa-solid fa-arrow-right"></i>

        </button>


    </form>


</div>

</div> <!-- ================================================== JAVASCRIPT ================================================== --> <script> /* |-------------------------------------------------------------------------- | OPEN BOOKING MODAL |-------------------------------------------------------------------------- */ function openBookingModal(event) { if (event) { event.preventDefault(); } const modal = document.getElementById("bookingModal"); if (!modal) { return; } modal.classList.add("active"); modal.setAttribute( "aria-hidden", "false" ); document.body.style.overflow = "hidden"; } /* |-------------------------------------------------------------------------- | CLOSE BOOKING MODAL |-------------------------------------------------------------------------- */ function closeBookingModal() { const modal = document.getElementById("bookingModal"); if (!modal) { return; } modal.classList.remove("active"); modal.setAttribute( "aria-hidden", "true" ); document.body.style.overflow = ""; } /* |-------------------------------------------------------------------------- | CLICK OUTSIDE MODAL |-------------------------------------------------------------------------- */ document.addEventListener( "click", function(event) { const modal = document.getElementById("bookingModal"); if ( modal && event.target === modal ) { closeBookingModal(); } } ); /* |-------------------------------------------------------------------------- | ESC KEY |-------------------------------------------------------------------------- */ document.addEventListener( "keydown", function(event) { if (event.key === "Escape") { closeBookingModal(); } } ); /* |-------------------------------------------------------------------------- | SERVICE VALIDATION |-------------------------------------------------------------------------- | | Requires the customer to select at least one service. | |-------------------------------------------------------------------------- */ const bookingForm = document.getElementById("bookingForm"); if (bookingForm) { bookingForm.addEventListener( "submit", function(event) { const services = bookingForm.querySelectorAll( 'input[name="service[]"]:checked' ); if (services.length === 0) { event.preventDefault(); alert( "Please select at least one service." ); const firstService = bookingForm.querySelector( 'input[name="service[]"]' ); if (firstService) { firstService.focus(); } return; } } ); } </script> <!-- ================================================== DATABASE CONNECTION CLOSE ================================================== --> <?php if (isset($conn) && $conn instanceof mysqli) { $conn->close(); } ?> </body> </html>
Also update about.css
Because the service cards are now <a> elements, add this to your CSS. Otherwise your existing .service-card styling may not look right:

Writing


/* ==================================================
SERVICES
================================================== */

.service-grid {

display: grid;

grid-template-columns:
    repeat(3, minmax(0, 1fr));

gap: 25px;

}

.service-card {

display: flex;

flex-direction: column;

text-decoration: none;

color: inherit;

position: relative;

padding: 32px 28px;

border-radius: 18px;

background:
    rgba(255, 255, 255, 0.04);

border:
    1px solid rgba(255, 255, 255, 0.08);

transition:
    transform 0.3s ease,
    background 0.3s ease,
    border-color 0.3s ease,
    box-shadow 0.3s ease;

}

.service-card:hover {

transform:
    translateY(-8px);

background:
    rgba(255, 255, 255, 0.08);

border-color:
    rgba(255, 255, 255, 0.25);

box-shadow:
    0 15px 40px
    rgba(0, 0, 0, 0.25);

}

.service-card > i {

font-size: 38px;

margin-bottom: 20px;

color: #ffffff;

}

.service-card h3 {

margin:
    0 0 12px;

font-size: 22px;

color: #ffffff;

}

.service-card p {

margin: 0;

line-height: 1.7;

color:
    rgba(255, 255, 255, 0.7);

}

.service-link {

display: inline-flex;

align-items: center;

gap: 8px;

margin-top: 22px;

font-size: 14px;

font-weight: 600;

color: #ffffff;

transition:
    gap 0.25s ease;

}

.service-card:hover
.service-link {

gap: 13px;

}

.service-link i {

font-size: 12px;

}

/* ==================================================
TABLET
================================================== */

@media (max-width: 900px) {

.service-grid {

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

}

}

/* ==================================================
MOBILE
================================================== */

@media (max-width: 600px) {

.service-grid {

    grid-template-columns: 1fr;

}


.service-card {

    padding: 28px 24px;

}

}
