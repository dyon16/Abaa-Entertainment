<?php

include(__DIR__ . '/conn.php');


/*
|--------------------------------------------------------------------------
| SERVICES
|--------------------------------------------------------------------------
*/

$services = [

    "led-wall" => [

        "title" => "LED Wall",

        "image" => "service1.png",

        "description" =>
            "Professional LED wall solutions designed to make your event visually impressive and engaging.",

        "details" => [

            "High-quality LED display systems",

            "Indoor and outdoor LED wall options",

            "Event branding and visual presentations",

            "Concert and stage applications",

            "Corporate events and product launches",

            "Professional setup and technical support"

        ]

    ],


    "lights-sound" => [

        "title" => "Lights & Sound",

        "image" => "service2.png",

        "description" =>
            "Complete professional lighting and sound solutions for concerts, parties, corporate events, and special occasions.",

        "details" => [

            "Professional sound systems",

            "Stage and event lighting",

            "Wireless microphones",

            "Speakers and amplifiers",

            "Moving heads and effect lights",

            "Technical operators and support"

        ]

    ],


    "live-feed" => [

        "title" => "Live Feed",

        "image" => "service3.png",

        "description" =>
            "Professional live video production and event streaming services that help audiences experience your event from anywhere.",

        "details" => [

            "Multi-camera event coverage",

            "Live video switching",

            "LED screen live feed",

            "Online event streaming",

            "Professional camera operators",

            "Event recording and documentation"

        ]

    ],


    "stage" => [

        "title" => "Stage Production",

        "image" => "service4.png",

        "description" =>
            "Complete stage production solutions built to provide safe, professional, and visually impressive event setups.",

        "details" => [

            "Stage platform setup",

            "Stage backdrop installation",

            "Stage lighting integration",

            "LED screen integration",

            "Event stage design",

            "Professional production crew"

        ]

    ],


    "music-studio" => [

        "title" => "Music Studio",

        "image" => "service5.png",

        "description" =>
            "A creative environment for artists, musicians, and creators to develop and produce high-quality audio content.",

        "details" => [

            "Music recording",

            "Vocal recording",

            "Audio editing",

            "Mixing and mastering",

            "Voice-over recording",

            "Creative production support"

        ]

    ],


    "trusses" => [

        "title" => "Trusses",

        "image" => "service6.png",

        "description" =>
            "Professional truss systems for supporting lighting, LED walls, banners, and other event production equipment.",

        "details" => [

            "Lighting truss systems",

            "LED wall support structures",

            "Event booth structures",

            "Stage truss installation",

            "Equipment mounting",

            "Professional setup and dismantling"

        ]

    ]

];


/*
|--------------------------------------------------------------------------
| CURRENT SERVICE
|--------------------------------------------------------------------------
*/

$serviceKey = $_GET["service"] ?? "led-wall";

if (!isset($services[$serviceKey])) {

    $serviceKey = "led-wall";

}

$service = $services[$serviceKey];

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
        <?= htmlspecialchars($service["title"]) ?>
        | ABAA Entertainment
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

        <a href="index.php#events">
            Events
        </a>

        <a href="index.php#services">
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


<main class="service-page">


    <section class="service-hero">


        <div class="service-image">

            <img
                src="<?= htmlspecialchars($service["image"]) ?>"
                alt="<?= htmlspecialchars($service["title"]) ?>"
            >

        </div>


        <div class="service-content">

            <span class="service-label">
                ABAA Entertainment Service
            </span>

            <h1>
                <?= htmlspecialchars($service["title"]) ?>
            </h1>

            <p class="service-description">
                <?= htmlspecialchars($service["description"]) ?>
            </p>


            <ul class="service-list">

                <?php foreach ($service["details"] as $detail): ?>

                    <li>

                        <i class="fa-solid fa-circle-check"></i>

                        <span>
                            <?= htmlspecialchars($detail) ?>
                        </span>

                    </li>

                <?php endforeach; ?>

            </ul>


            <div class="service-buttons">

                <a
                    href="#"
                    class="service-book-button"
                    onclick="openBookingModal(event)"
                >

                    Book This Service

                    <i class="fa-solid fa-arrow-right"></i>

                </a>


                <a
                    href="index.php#services"
                    class="service-back-button"
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    All Services

                </a>

            </div>

        </div>

    </section>


    <section class="other-services">

        <h2>
            Other Services
        </h2>


        <div class="other-services-grid">

            <?php foreach ($services as $key => $item): ?>

                <?php if ($key !== $serviceKey): ?>

                    <a
                        href="service.php?service=<?= urlencode($key) ?>"
                        class="other-service-card"
                    >

                        <img
                            src="<?= htmlspecialchars($item["image"]) ?>"
                            alt="<?= htmlspecialchars($item["title"]) ?>"
                        >

                        <span>
                            <?= htmlspecialchars($item["title"]) ?>
                        </span>

                    </a>

                <?php endif; ?>

            <?php endforeach; ?>

        </div>

    </section>

</main>


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

            <a href="index.php#events">
                Events
            </a>

            <a href="index.php#services">
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

            <?php foreach ($services as $key => $item): ?>

                <a
                    href="service.php?service=<?= urlencode($key) ?>"
                >

                    <?= htmlspecialchars($item["title"]) ?>

                </a>

            <?php endforeach; ?>

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

                    <label for="service_booking_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="service_booking_name"
                        name="name"
                        placeholder="Enter your full name"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="service_booking_phone">
                        Contact Number
                    </label>

                    <input
                        type="tel"
                        id="service_booking_phone"
                        name="phone"
                        placeholder="09XX XXX XXXX"
                        required
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label for="service_booking_email">
                        Email Address
                    </label>

                    <input
                        type="email"
                        id="service_booking_email"
                        name="email"
                        placeholder="your@email.com"
                        required
                    >

                </div>


                <div class="form-group">

                    <label for="service_booking_contact">
                        Contact Person
                    </label>

                    <input
                        type="text"
                        id="service_booking_contact"
                        name="contact_person"
                        placeholder="Contact person's name"
                        required
                    >

                </div>

            </div>


            <div class="form-row">

                <div class="form-group">

                    <label for="service_booking_event">
                        Event Type
                    </label>

                    <select
                        id="service_booking_event"
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

                    <label for="service_booking_date">
                        Event Date
                    </label>

                    <input
                        type="date"
                        id="service_booking_date"
                        name="event_date"
                        required
                    >

                </div>

            </div>


            <div class="form-group">

                <label for="service_booking_company">
                    Company Name
                </label>

                <input
                    type="text"
                    id="service_booking_company"
                    name="cname"
                    placeholder="Enter company name"
                    required
                >

            </div>


            <div class="form-group">

                <label for="service_booking_service">
                    Service Needed
                </label>

                <select
                    id="service_booking_service"
                    name="service"
                    required
                >

                    <option
                        value="<?= htmlspecialchars($service["title"]) ?>"
                        selected
                    >
                        <?= htmlspecialchars($service["title"]) ?>
                    </option>

                    <?php foreach ($services as $item): ?>

                        <?php if ($item["title"] !== $service["title"]): ?>

                            <option
                                value="<?= htmlspecialchars($item["title"]) ?>"
                            >
                                <?= htmlspecialchars($item["title"]) ?>
                            </option>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    <option value="Full Event Production">
                        Full Event Production
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label for="service_booking_message">
                    Event Details
                </label>

                <textarea
                    id="service_booking_message"
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
        document.getElementById("bookingModal");

    if (!modal) {

        return;

    }

    modal.classList.add("active");

    modal.setAttribute(
        "aria-hidden",
        "false"
    );

    document.body.style.overflow = "hidden";

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

    document.body.style.overflow = "";

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


</body>

</html>
