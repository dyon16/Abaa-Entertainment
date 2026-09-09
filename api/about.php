<?php

include(__DIR__ . '/conn.php');

function e($value)
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

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
    error_log('About page service query error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#ff3d02">
    <title>About Us | ABAA Entertainment</title>

    <link rel="stylesheet" href="/about.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>

/* ==================================================
   MOBILE HAMBURGER MENU
================================================== */

.menu-toggle {
    display: none;
    width: 46px;
    height: 46px;
    padding: 8px;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 5px;
    background: transparent;
    border: 2px solid #ff3d02;
    border-radius: 6px;
    cursor: pointer;
    flex-shrink: 0;
    z-index: 1100;
}

.menu-toggle span {
    display: block;
    width: 24px;
    height: 3px;
    background: #fff;
    border-radius: 2px;
    transition: transform .3s ease, opacity .3s ease;
}

.menu-toggle:hover {
    background: rgba(255, 61, 2, .12);
}

.menu-toggle.active span:nth-child(1) {
    transform: translateY(8px) rotate(45deg);
}

.menu-toggle.active span:nth-child(2) {
    opacity: 0;
}

.menu-toggle.active span:nth-child(3) {
    transform: translateY(-8px) rotate(-45deg);
}

@media (max-width: 600px) {
    .menu-toggle {
        display: flex;
    }

    .header {
        position: fixed;
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
        display: flex;
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
        font-size: 14px;
        border-radius: 50px;
    }

    .header nav a:last-child {
        border-bottom: none;
    }
}

        #otherEventTypeGroup { display:none; }

        .service-unavailable-checkbox { opacity:.65; cursor:not-allowed; }
        .service-unavailable-checkbox input { cursor:not-allowed; }
        .service-unavailable-text { color:#dc2626; font-size:.8em; margin-left:4px; }
        .booking-no-services { margin:0; color:#777; }
        .service-unavailable-link { color:#dc2626 !important; cursor:not-allowed; opacity:.85; }
        .footer-empty { color:#777; font-size:13px; }

        /* ==================================================
           HERO INTRO
        ================================================== */
        .about-content {
            flex:1;
            min-width:0;
            display:flex;
            flex-direction:column;
            justify-content:center;
        }

        .about-intro {
            max-width:950px;
            margin-top:25px;
            color:#e0e0e0;
            font-size:clamp(20px, 2.3vw, 30px);
            line-height:1.7;
            font-weight:500;
            letter-spacing:.3px;
        }


        .about-hero-note {
            width:min(100%, 360px);
            padding:35px 30px;
            align-self:center;
            background:linear-gradient(145deg, rgba(10,10,10,.96), rgba(25,7,4,.94));
            border:1px solid #333;
            border-top:3px solid #ff3d02;
            border-radius:8px;
            box-shadow:0 15px 45px rgba(0,0,0,.65);
        }

        .hero-note-icon {
            width:48px;
            height:48px;
            display:flex;
            align-items:center;
            justify-content:center;
            margin-bottom:18px;
            color:#ff3d02;
            background:#160704;
            border:1px solid #61200d;
            border-radius:50%;
        }

        .about-hero-note p {
            margin:0 0 18px;
            color:#ddd;
            font-size:18px;
            line-height:1.7;
            font-weight:600;
        }

        .about-hero-note span {
            color:#ff3d02;
            font-size:10px;
            font-weight:700;
            letter-spacing:3px;
        }

        @media (max-width: 900px) {
            .about-hero-note {
                width:min(100%, 520px);
                margin-top:5px;
            }
        }

        @media (max-width: 600px) {
            .about-hero-note {
                width:100%;
                padding:25px 22px;
            }
            .about-hero-note p {
                font-size:15px;
            }
        }

        /* ==================================================
           FOUNDERS
        ================================================== */
        .founders-section {
            width:min(95%, 1400px);
            margin:20px auto 0;
            padding:clamp(60px, 8vw, 90px) 5%;
          

        }

        .founders-heading {
            max-width:800px;
            margin:0 auto 60px;
            text-align:center;
        }

        .founders-heading .section-label {
            display:inline-block;
            margin-bottom:12px;
            color:#ff3d02;
            font-size:12px;
            font-weight:bold;
            letter-spacing:3px;
        }

        .founders-heading h2 {
            margin-bottom:15px;
            color:white;
            font-size:clamp(32px, 4vw, 48px);
            font-weight:900;
            line-height:1.1;
            text-transform:uppercase;
        }

        .founders-heading p {
            color:#999;
            font-size:15px;
            line-height:1.8;
        }

        .founder-list {
            display:flex;
            flex-direction:column;
            gap:35px;
            max-width:1050px;
            margin:0 auto;
        }

        .founder-row {
            display:grid;
            grid-template-columns:minmax(0, 1fr) 330px;
            align-items:center;
            gap:55px;
            padding:25px;
            background:linear-gradient(135deg, rgba(15,15,15,.95), rgba(25,7,4,.92));
            border:1px solid #292929;
            border-left:4px solid #ff3d02;
            border-radius:8px;
            box-shadow:0 15px 45px rgba(0,0,0,.55);
            transition:transform .3s ease,border-color .3s ease,box-shadow .3s ease;
        }

        .founder-row:hover {
            transform:translateY(-5px);
            border-color:#ff3d02;
            box-shadow:0 20px 55px rgba(0,0,0,.7),0 0 30px rgba(255,61,2,.12);
        }

        .founder-content { min-width:0; }

        .founder-role,
        .history-label {
            display:inline-block;
            margin-bottom:10px;
            color:#ff3d02;
            font-size:11px;
            font-weight:bold;
            letter-spacing:2px;
            text-transform:uppercase;
        }

        .founder-content h3 {
            margin-bottom:18px;
            color:white;
            font-size:clamp(22px, 2.4vw, 31px);
            line-height:1.2;
            font-weight:900;
            text-transform:uppercase;
        }

        .founder-content p {
            max-width:600px;
            color:#999;
            font-size:15px;
            line-height:1.8;
        }

        .founder-image {
            width:100%;
            height:300px;
            overflow:hidden;
            background:#080808;
            border:1px solid #333;
            border-radius:7px;
            box-shadow:0 10px 30px rgba(0,0,0,.6);
            transition:border-color .3s ease,transform .3s ease;
        }

        .founder-row:hover .founder-image {
            border-color:#ff3d02;
            transform:scale(1.02);
        }

        .founder-image img {
            width:100%;
            height:100%;
            display:block;
            object-fit:cover;
            object-position:center;
        }

        /* ==================================================
           HISTORY
        ================================================== */
        .history-section {
            width:min(95%, 1400px);
            margin:0 auto;
            padding:clamp(65px, 9vw, 100px) 5% clamp(70px, 10vw, 110px);
            position:relative;
        }

        .history-heading {
            max-width:800px;
            margin:0 auto 65px;
            text-align:center;
        }

        .history-heading .section-label {
            display:inline-block;
            margin-bottom:12px;
            color:#ff3d02;
            font-size:12px;
            font-weight:bold;
            letter-spacing:3px;
        }

        .history-heading h2 {
            margin-bottom:15px;
            color:white;
            font-size:clamp(32px, 4vw, 48px);
            font-weight:900;
            text-transform:uppercase;
            line-height:1.1;
        }

        .history-heading h2::after {
            content:"";
            display:block;
            width:80px;
            height:4px;
            margin:18px auto 0;
            background:#ff3d02;
        }

        .history-heading p {
            color:#999;
            font-size:15px;
            line-height:1.8;
        }

        .history-timeline { position:relative; }

        .history-timeline::before {
            content:"";
            position:absolute;
            top:0;
            bottom:0;
            left:50%;
            width:2px;
            background:linear-gradient(to bottom, transparent, #ff3d02 8%, #ff3d02 92%, transparent);
            transform:translateX(-50%);
        }

        .history-item {
            position:relative;
            width:100%;
            display:grid;
            grid-template-columns:minmax(0,1fr) minmax(0,1fr);
            align-items:center;
            column-gap:90px;
            margin-bottom:85px;
        }

        .history-item:last-child { margin-bottom:0; }

        .history-item:nth-child(even) { direction:rtl; }
        .history-item:nth-child(even) > * { direction:ltr; }

        .history-number {
            position:absolute;
            top:50%;
            left:50%;
            width:62px;
            height:62px;
            display:flex;
            align-items:center;
            justify-content:center;
            color:white;
            background:linear-gradient(135deg,#ff3d02,#b72500);
            border:5px solid #090909;
            border-radius:50%;
            box-shadow:0 0 0 1px #ff3d02,0 0 25px rgba(255,61,2,.25);
            font-size:15px;
            font-weight:900;
            transform:translate(-50%,-50%);
            z-index:5;
        }

        .history-content { padding:12px 0; }

        .history-content h3 {
            margin-bottom:15px;
            color:white;
            font-size:clamp(21px,2.5vw,29px);
            line-height:1.2;
            text-transform:uppercase;
        }

        .history-content p {
            color:#aaa;
            font-size:15px;
            line-height:1.85;
        }

        .history-image {
            width:100%;
            height:330px;
            overflow:hidden;
            background:#080808;
            border:1px solid #303030;
            border-radius:8px;
            box-shadow:0 15px 40px rgba(0,0,0,.55);
            transition:border-color .3s ease,transform .3s ease,box-shadow .3s ease;
        }

        .history-image:hover {
            border-color:#ff3d02;
            transform:translateY(-5px);
            box-shadow:0 20px 45px rgba(0,0,0,.7),0 0 25px rgba(255,61,2,.1);
        }

        .history-image img {
            width:100%;
            height:100%;
            display:block;
            object-fit:cover;
        }

        /* ==================================================
           RESPONSIVE
        ================================================== */
        @media (max-width:900px) {
            .about-intro {
                max-width:100%;
                font-size:clamp(18px,2.6vw,24px);
                line-height:1.7;
            }

            .founder-row {
                grid-template-columns:1fr;
                gap:25px;
            }

            .founder-image {
                width:100%;
                max-width:360px;
                height:320px;
                margin:0 auto;
            }

            .history-timeline::before {
                left:31px;
                transform:none;
            }

            .history-item,
            .history-item:nth-child(even) {
                display:flex;
                flex-direction:column;
                align-items:stretch;
                gap:25px;
                margin-bottom:65px;
                padding-left:75px;
                direction:ltr;
            }

            .history-item:nth-child(even) > * { direction:ltr; }

            .history-number {
                top:20px;
                left:31px;
                width:52px;
                height:52px;
                transform:translate(-50%,0);
            }

            .history-image { height:290px; }
        }

        @media (max-width:600px) {
            .about-content { text-align:center; }

            .about-intro {
                max-width:100%;
                margin-top:20px;
                font-size:18px;
                line-height:1.7;
            }

            .founders-section {
                width:94%;
                padding:50px 5% 65px;
            }

            .founders-heading { margin-bottom:40px; }
            .founders-heading h2 { font-size:29px; }
            .founders-heading p { font-size:13px; }
            .founder-row { padding:20px; gap:20px; }
            .founder-content h3 { font-size:20px; }
            .founder-content p { font-size:13px; }
            .founder-image { height:260px; }

            .history-section {
                width:94%;
                padding:55px 5% 70px;
            }

            .history-heading { margin-bottom:45px; }
            .history-heading h2 { font-size:29px; }
            .history-heading p { font-size:13px; }

            .history-item,
            .history-item:nth-child(even) {
                padding-left:62px;
                gap:18px;
            }

            .history-timeline::before { left:24px; }

            .history-number {
                left:24px;
                width:46px;
                height:46px;
                font-size:12px;
            }

            .history-content h3 { font-size:20px; }
            .history-content p { font-size:13px; line-height:1.75; }
            .history-image { height:230px; }
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
        aria-controls="mainNav"
        aria-expanded="false"
    >
        <span></span>
        <span></span>
        <span></span>
    </button>

    <nav id="mainNav">
        <a href="/">Home</a>
        <a href="/#events">Events</a>
        <a href="/#services">Services</a>
        <a href="/about" class="active">About</a>
        <a href="#" class="book-button" onclick="openBookingModal(event)">Book</a>
    </nav>
</header>

<!-- ==================================================
     ABOUT HERO
================================================== -->
<section class="about-us" id="about">
    <div class="about-content">
        <span class="section-label">ABOUT ABAA ENTERTAINMENT</span>

        <h1>
            Creating Experiences.
            <br>
            Supporting Talent.
        </h1>

        <p class="about-intro">
          Founded in 2022 by Mr. Russel Ynares and Mr. Koy Quevedo, ABAA Entertainment Inc. began with a simple passion for music, live performances, and creating memorable experiences.
What started as a shared passion for music gradually evolved into a professional entertainment company dedicated to bringing ideas, artists, and events to life. Through hard work, creativity, and a commitment to quality, Abaa Entertainment grew from its musical roots into a company capable of handling a wide range of entertainment and production requirements.
        </p>
    </div>

    <div class="about-hero-note">
        <div class="hero-note-icon">
            <i class="fa-solid fa-quote-left"></i>
        </div>
        <p>
            Entertainment is more than an event — it is an experience,
            a platform for talent, and a story worth remembering.
        </p>
        <span>ABAA ENTERTAINMENT</span>
    </div>
</section>

<!-- ==================================================
     FOUNDERS
================================================== -->
<section class="founders-section">
    <div class="founders-heading">
        <span class="section-label">OUR FOUNDERS</span>
        <h2>The People Behind ABAA</h2>
        <p>
            Founded with a passion for entertainment, creativity,
            and opportunity, ABAA Entertainment continues to grow
            under the leadership and vision of its founders.
        </p>
    </div>

    <div class="founder-list">

        <div class="founder-row">
            <div class="founder-content">
                <span class="founder-role">Founder</span>
                <h3>MR. RUSSEL GUILLER YNARES</h3>
                <p>
                    A driving force behind ABAA Entertainment's
                    vision of discovering, developing, and promoting
                    local talents while creating professional
                    entertainment opportunities for artists and
                    creative individuals.
                </p>
            </div>

            <div class="founder-image">
                <img src="/russel.png" alt="Mr. Russel Guiller Ynares">
            </div>
        </div>

        <div class="founder-row">
            <div class="founder-content">
                <span class="founder-role">Founder</span>
                <h3>MR. RAMON EMMANUEL C. QUEVEDO</h3>
                <p>
                    A key part of ABAA Entertainment's growth,
                    helping shape the company's commitment to
                    professional production, creative entertainment,
                    and opportunities for emerging Filipino talent.
                </p>
            </div>

            <div class="founder-image">
                <img src="/koy.png" alt="Mr. Ramon Emmanuel C. Quevedo">
            </div>
        </div>
    </div>
</section>

<!-- ==================================================
     HISTORY
================================================== -->
<section class="history-section" id="history">
    <div class="history-heading">
        <span class="section-label">OUR JOURNEY</span>
        <h2>The History of ABAA Entertainment</h2>
        <p>
            From a small initiative focused on discovering local
            talent to a growing entertainment, production, and film
            company, ABAA Entertainment continues to build
            opportunities and create unforgettable experiences.
        </p>
    </div>

    <div class="history-timeline">

        <article class="history-item">
            <div class="history-image">
                <img src="/history1.png" alt="ABAA Entertainment beginning">
            </div>
            <div class="history-content">
                <span class="history-label">Chapter 01</span>
                <h3>Our Beginning</h3>
                <p>
                    ABAA Entertainment Inc. was established with the vision of discovering, developing, and promoting local talents from the Province of Rizal. Founded by Russel Guiller Ynares and Ramon Emmanuel Quevedo, the company began as a small initiative focused on recruiting aspiring local artists and providing them with opportunities to showcase their talents in the entertainment industry.
                </p>
            </div>
            <div class="history-number">01</div>
        </article>

        <article class="history-item">
            <div class="history-image">
                <img src="/history2.png" alt="ABAA Entertainment early years">
            </div>
            <div class="history-content">
                <span class="history-label">Chapter 02</span>
                <h3>Building The Foundation</h3>
                <p>
                    In its early years, ABAA Entertainment Inc. worked closely with singers, dancers, performers, musicians, and creative individuals within local communities. The founders believed that many talented artists in Rizal deserved a platform where they could express themselves, gain experience, and reach wider audiences. Through talent searches, live events, community programs, and collaborations, the company gradually became recognized for supporting homegrown talents and creating opportunities for emerging performers.
                </p>
            </div>
            <div class="history-number">02</div>
        </article>

        <article class="history-item">
            <div class="history-image">
                <img src="/history3.png" alt="ABAA Entertainment services expansion">
            </div>
            <div class="history-content">
                <span class="history-label">Chapter 03</span>
                <h3>Expanding Our Services</h3>
                <p>
                    As the organization continued to grow, ABAA Entertainment Inc. expanded its services beyond talent management and artist recruitment. The company ventured into event production, creative media services, film production, stage and technical production, audio and lighting solutions, LED wall rentals, photo and video coverage, and live entertainment setups for various occasions such as concerts, corporate events, festivals, school activities, private celebrations, events management, presscon and press releases, film projects, and etc.
                </p>
            </div>
            <div class="history-number">03</div>
        </article>

        <article class="history-item">
            <div class="history-image">
                <img src="/history4.png" alt="ABAA Entertainment film production">
            </div>
            <div class="history-content">
                <span class="history-label">Chapter 04</span>
                <h3>Film &amp; Creative Production</h3>
                <p>
                    ABAA Entertainment Inc. also caters to film production by supporting independent films, creative storytelling projects, music videos, documentaries, and other multimedia productions. The company provides production assistance, technical equipment, creative direction, and multimedia services to help bring cinematic projects to life while continuing to promote local artistry and creativity.
                </p>
            </div>
            <div class="history-number">04</div>
        </article>

        <article class="history-item">
            <div class="history-image">
                <img src="/history5.png" alt="ABAA Entertainment major events">
            </div>
            <div class="history-content">
                <span class="history-label">Chapter 05</span>
                <h3>Growing Beyond Rizal</h3>
                <p>
                    Over the years, the company successfully handled and participated in several major events not only within Rizal but also in different parts of the Philippines and abroad. Through its growing network and commitment to quality service, ABAA Entertainment Inc. was able to extend its reach outside the province and outside the country, showcasing Filipino creativity, talent, and world-class production services to wider audiences.
                </p>
            </div>
            <div class="history-number">05</div>
        </article>

        <article class="history-item">
            <div class="history-image">
                <img src="/history6.png" alt="ABAA Entertainment today">
            </div>
            <div class="history-content">
                <span class="history-label">Chapter 06</span>
                <h3>Today &amp; The Future</h3>
                <p>
                    Today, ABAA Entertainment Inc. continues to uphold its mission of empowering local artists while delivering quality entertainment, production, and film services. Guided by the leadership and vision of its founders, the company remains committed to promoting creativity, professionalism, and excellence in the entertainment industry while proudly representing the talents of Rizal and the Philippines in both local and international events.
                </p>
            </div>
            <div class="history-number">06</div>
        </article>

    </div>
</section>

<!-- ==================================================
     WHY ABAA ENTERTAINMENT
================================================== -->
<section class="why-us">
    <div class="why-image">
        <img src="/event5.jpg" alt="ABAA Entertainment Event">
    </div>

    <div class="why-content">
        <span class="section-label">WHY ABAA ENTERTAINMENT</span>
        <h2>Built For Unforgettable Events</h2>
        <p>
            We combine creativity, technology,
            and professional event production
            to create experiences that leave
            a lasting impression.
        </p>

        <div class="feature">
            <i class="fa-solid fa-check"></i>
            <div>
                <h4>Professional Production</h4>
                <p>Reliable equipment and experienced event professionals.</p>
            </div>
        </div>

        <div class="feature">
            <i class="fa-solid fa-check"></i>
            <div>
                <h4>Creative Solutions</h4>
                <p>Customized entertainment solutions designed around your event.</p>
            </div>
        </div>

        <div class="feature">
            <i class="fa-solid fa-check"></i>
            <div>
                <h4>Memorable Experiences</h4>
                <p>We focus on creating events that audiences will remember.</p>
            </div>
        </div>
    </div>
</section>

<!-- ==================================================
     CTA
================================================== -->
<section class="about-cta">
    <span class="section-label">LET'S WORK TOGETHER</span>
    <h2>Ready To Create Something Amazing?</h2>
    <p>From Lights & Sounds to Full Event Production, ABAA Entertainment is ready to bring your vision to life.</p>

    <a href="#" class="cta-button" onclick="openBookingModal(event)">
        Book An Event
        <i class="fa-solid fa-arrow-right"></i>
    </a>
</section>

<!-- ==================================================
     FOOTER
================================================== -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-section footer-brand">
            <img src="/logo.png" alt="ABAA Entertainment Logo">
            <p>
                Creating unforgettable events,
                entertainment, and experiences
                through creativity, technology,
                and professional event production.
            </p>
        </div>

        <div class="footer-section">
            <h3>Quick Links</h3>
            <a href="/">Home</a>
            <a href="/#events">Events</a>
            <a href="/#services">Services</a>
            <a href="/about">About Us</a>
            <a href="/booking-status" class="booking-status-link">Check Booking Status</a>
        </div>

        <div class="footer-section">
            <h3>Our Services</h3>

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
                <span class="footer-empty">No services available.</span>
            <?php endif; ?>
        </div>

        <div class="footer-section">
            <h3>Contact Us</h3>

            <a
                href="https://www.google.com/maps/place/ABAA+Entertainment/@14.4652755,121.1915078,19z"
                target="_blank"
                rel="noopener noreferrer"
                class="contact-item"
            >
                <i class="fa-solid fa-location-dot"></i>
                <span>2F, Casa Ynares, P. Gomez, Libis, Binangonan, Rizal</span>
            </a>

            <a href="tel:+639231476552" class="contact-item">
                <i class="fa-solid fa-phone"></i>
                <span>+63 923 147 6552</span>
            </a>

            <a href="mailto:abaaentertainment@gmail.com" class="contact-item">
                <i class="fa-solid fa-envelope"></i>
                <span>abaaentertainment@gmail.com</span>
            </a>

            <div class="social-links">
                <a href="https://www.facebook.com/ABAAEntertainment" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                <a href="#" aria-label="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a href="https://www.tiktok.com/@markebpmbta?_r=1&_t=ZS-99DpdJXY5sD" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>© <?= date('Y') ?> ABAA Entertainment. All Rights Reserved.</p>
        <p>Entertainment • Events • Experiences</p>
    </div>
</footer>

<!-- ==================================================
     BOOKING MODAL
================================================== -->
<div class="booking-overlay" id="bookingModal" aria-hidden="true">
    <div class="booking-modal">
        <button type="button" class="booking-close" onclick="closeBookingModal()" aria-label="Close booking form">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="booking-header">
            <span class="booking-label">ABAA ENTERTAINMENT</span>
            <h2>Book An Event</h2>
            <p>Tell us about your event and our team will get back to you.</p>
        </div>

        <form action="/booking" method="POST" class="booking-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="booking_contact_person">Contact Person</label>
                    <input type="text" id="booking_contact_person" name="contact_person" placeholder="Contact person's name" required>
                </div>
                <div class="form-group">
                    <label for="booking_phone">Contact Number</label>
                    <input type="tel" id="booking_phone" name="phone" placeholder="09XX XXX XXXX" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="booking_email">Email Address</label>
                    <input type="email" id="booking_email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label for="booking_company">Company Name</label>
                    <input type="text" id="booking_company" name="cname" placeholder="Enter company name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="booking_event">Event Type</label>
                    <select id="booking_event" name="event_type" required onchange="toggleOtherEventType()">
                        <option value="" disabled selected>Select event type</option>
                        <option value="Birthday">Birthday</option>
                        <option value="Wedding">Wedding</option>
                        <option value="Concert">Concert</option>
                        <option value="Corporate Event">Corporate Event</option>
                        <option value="Festival">Festival</option>
                        <option value="Product Launch">Product Launch</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="booking_date">Event Date</label>
                    <input type="date" id="booking_date" name="event_date" required>
                </div>
            </div>

            <div class="form-group" id="otherEventTypeGroup">
                <label for="other_event_type">Please Specify Event Type</label>
                <input type="text" id="other_event_type" name="other_event_type" placeholder="Enter your event type">
            </div>

            <div class="form-group">
                <label>Services Needed</label>
                <div class="service-checkboxes">
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <?php $serviceAvailable = (int)($service['is_available'] ?? 0) === 1; ?>
                            <label class="service-checkbox<?= !$serviceAvailable ? ' service-unavailable-checkbox' : '' ?>">
                                <input type="checkbox" name="service[]" value="<?= e($service['name']) ?>" <?= !$serviceAvailable ? 'disabled' : '' ?>>
                                <span>
                                    <?= e($service['name']) ?>
                                    <?php if (!$serviceAvailable): ?>
                                        <small class="service-unavailable-text">(Unavailable)</small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="booking-no-services">No services are currently available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="booking_message">Event Details</label>
                <textarea id="booking_message" name="message" rows="4" placeholder="Tell us about your event, location, preferred setup, budget, or other requirements..."></textarea>
            </div>

            <button type="submit" class="booking-submit">
                <span>Submit Booking Request</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

<script>
function toggleOtherEventType() {
    const eventType = document.getElementById("booking_event");
    const otherGroup = document.getElementById("otherEventTypeGroup");
    const otherInput = document.getElementById("other_event_type");

    if (!eventType || !otherGroup || !otherInput) return;

    if (eventType.value === "Other") {
        otherGroup.style.display = "block";
        otherInput.required = true;
    } else {
        otherGroup.style.display = "none";
        otherInput.required = false;
        otherInput.value = "";
    }
}

function openBookingModal(event) {
    if (event) event.preventDefault();

    const modal = document.getElementById("bookingModal");
    if (!modal) return;

    modal.classList.add("active");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
}

function closeBookingModal() {
    const modal = document.getElementById("bookingModal");
    if (!modal) return;

    modal.classList.remove("active");
    modal.setAttribute("aria-hidden", "true");
    document.body.style.overflow = "";
}

document.addEventListener("click", function(event) {
    const modal = document.getElementById("bookingModal");
    if (modal && event.target === modal) closeBookingModal();
});

document.addEventListener("keydown", function(event) {
    if (event.key === "Escape") closeBookingModal();
});
</script>

<?php
if (isset($conn)) {
    $conn->close();
}
?>


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
