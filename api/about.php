<?php

include(__DIR__ . '/conn.php');

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/*
|--------------------------------------------------------------------------
| LOAD SERVICES
|--------------------------------------------------------------------------
| Services are managed from /admin/services and shared with the
| homepage/service pages.
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

    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log(
        'About page service query error: ' .
        $e->getMessage()
    );

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


<style>
    .service-unavailable-checkbox {
    opacity: 0.65;
    cursor: not-allowed;
}

.service-unavailable-checkbox input {
    cursor: not-allowed;
}

.service-unavailable-text {
    color: #dc2626;
    font-size: 0.8em;
    margin-left: 4px;
}

.booking-no-services {
    margin: 0;
    color: #777;
}
.service-unavailable-link {
    color: #dc2626 !important;
    cursor: not-allowed;
    opacity: .85;
}

.footer-empty {
    color: #777;
    font-size: 13px;
}
</style>

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

<section class="about-us" id="about">

    <div class="about-content">

        <h1>
            ABAA Entertainment
        </h1>

        <p>

           Founded in 2022 by Mr. Russel Ynares and Mr. Koy Quevedo, ABAA Entertainment Inc. 
            began with a simple passion for music, live performances, and creating memorable experiences.

            <br><br>

            What started as a shared passion for music gradually evolved into a professional entertainment company dedicated to bringing
            ideas, artists, and events to life. Through hard work, creativity, and a commitment to quality, 
            Abaa Entertainment grew from its musical roots into a company capable of handling a wide range of 
            entertainment and production requirements.

            <br><br>

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

</section>



<main class="about-page">


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
                high-quality entertainment  while
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


        <div class="footer-section footer-brand">

            <img
                src="/logo.png"
                alt="ABAA Entertainment Logo"
            >

            <p>
                Creating unforgettable events,
                entertainment, and experiences
                through creativity, technology,
                and professional event .
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

            
<?php if (!empty($services)): ?>

<?php foreach ($services as $item): ?>

<?php
    $serviceSlug = trim((string)($item['slug'] ?? ''));
    $serviceName = trim((string)($item['name'] ?? ''));
    $isAvailable = (int)($item['is_available'] ?? 0) === 1;
?>

<?php if ($serviceSlug !== '' && $serviceName !== ''): ?>

<a
    href="<?= $isAvailable ? '/service?service=' . rawurlencode($serviceSlug) : '#' ?>"
    <?= !$isAvailable ? 'aria-disabled="true" onclick="return false;" class="service-unavailable-link"' : '' ?>
>
    <?= e($serviceName) ?>
</a>

<?php endif; ?>

<?php endforeach; ?>

<?php else: ?>

<span class="footer-empty">
    No services available.
</span>

<?php endif; ?>


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



            <!-- ==================================================
                 MULTIPLE SERVICES
            ================================================== -->

          <!-- SERVICES -->

<div class="form-group">

    <label>
        Services Needed
    </label>

    <div class="service-checkboxes">

        <?php if (!empty($services)): ?>

            <?php foreach ($services as $service): ?>

                <?php
                $serviceAvailable =
                    (int)($service['is_available'] ?? 0) === 1;
                ?>

                <label
                    class="service-checkbox<?= !$serviceAvailable
                        ? ' service-unavailable-checkbox'
                        : ''
                    ?>"
                >

                    <input
                        type="checkbox"
                        name="service[]"
                        value="<?= e($service['name']) ?>"
                        <?= !$serviceAvailable ? 'disabled' : '' ?>
                    >

                    <span>

                        <?= e($service['name']) ?>

                        <?php if (!$serviceAvailable): ?>

                            <small class="service-unavailable-text">
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



<?php

if (isset($conn)) {

    $conn->close();

}

?>

</body>

</html>
