<?php

include __DIR__ . "/conn.php";

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function parseDetails($details)
{
    if (empty($details)) {
        return [];
    }

    $decoded = json_decode($details, true);

    if (is_array($decoded)) {
        return array_values(array_filter(array_map('trim', $decoded), static function ($item) {
            return $item !== '';
        }));
    }

    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $details)), static function ($item) {
        return $item !== '';
    }));
}

$serviceKey = trim($_GET['service'] ?? '');

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
    error_log('Service page query error: ' . $e->getMessage());
}

$service = null;

foreach ($services as $item) {
    if ((string)$item['slug'] === $serviceKey) {
        $service = $item;
        break;
    }
}

if (!$service && !empty($services)) {
    $service = $services[0];
    $serviceKey = $service['slug'];
}

if (!$service) {
    http_response_code(404);
    $service = [
        'name' => 'Service Not Found',
        'slug' => '',
        'image_url' => '/logo.png',
        'description' => 'The requested service could not be found.',
        'details' => '[]',
        'is_available' => 0,
    ];
}

$serviceAvailable = (int)($service['is_available'] ?? 0) === 1;
$serviceDetails = parseDetails($service['details'] ?? '');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($service['name']) ?> | ABAA Entertainment</title>

    <link rel="stylesheet" href="/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        .service-page {
            width: 95%;
            max-width: 1400px;
            margin: 40px auto 80px;
        }

        .service-hero {
            min-height: 620px;
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 50px;
            align-items: center;
            padding: 50px;
            background: linear-gradient(135deg, rgba(8,8,8,.98), rgba(30,8,3,.95));
            border-left: 5px solid #ff3d02;
            border-radius: 6px;
            box-shadow: 0 15px 50px rgba(0,0,0,.65);
        }

        .service-hero.service-hero-unavailable {
            border-left-color: #dc2626;
            background: linear-gradient(135deg, rgba(25,5,5,.98), rgba(45,5,5,.95));
        }

        .service-image {
            width: 100%;
            height: 520px;
            overflow: hidden;
            position: relative;
            border: 2px solid #333;
            border-radius: 6px;
            background: #050505;
            box-shadow: 0 0 30px rgba(255,61,2,.2);
        }

        .service-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            transition: transform .5s ease;
        }

        .service-image:hover {
            border-color: #ff3d02;
            box-shadow: 0 0 35px rgba(255,61,2,.4);
        }

        .service-image:hover img {
            transform: scale(1.05);
        }

        .service-image-unavailable img {
            filter: grayscale(.8) brightness(.42);
        }

        .service-image-unavailable:hover img {
            transform: none;
            filter: grayscale(.8) brightness(.42);
        }

        .service-unavailable-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(100,0,0,.30);
        }

        .service-unavailable-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border: 1px solid rgba(248,113,113,.75);
            border-radius: 999px;
            background: rgba(127,29,29,.92);
            color: #fee2e2;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 10px 30px rgba(0,0,0,.45);
        }

        .service-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .service-label {
            color: #ff3d02;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .service-label.unavailable {
            color: #f87171;
        }

        .service-content h1 {
            color: white;
            font-size: 58px;
            line-height: 1.05;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 25px;
        }

        .service-content h1::after {
            content: "";
            display: block;
            width: 80px;
            height: 4px;
            background: #ff3d02;
            margin-top: 18px;
        }

        .service-hero-unavailable .service-content h1::after {
            background: #dc2626;
        }

        .service-description {
            color: #cfcfcf;
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 30px;
        }

        .service-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin: 0 0 35px;
            padding: 0;
        }

        .service-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ddd;
            font-size: 16px;
        }

        .service-list li i {
            color: #ff3d02;
            font-size: 15px;
            width: 20px;
        }

        .service-buttons {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .service-book-button,
        .service-back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 50px;
            padding: 0 28px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: .3s;
        }

        .service-book-button {
            color: white;
            background: #ff3d02;
            border: 2px solid #ff3d02;
            box-shadow: 0 0 20px rgba(255,61,2,.25);
        }

        .service-book-button:hover {
            color: #ff3d02;
            background: transparent;
            transform: translateY(-3px);
            box-shadow: 0 0 30px rgba(255,61,2,.45);
        }

        .service-unavailable-button {
            color: #fecaca;
            background: #7f1d1d;
            border: 2px solid #dc2626;
            cursor: not-allowed;
            opacity: .95;
        }

        .service-back-button {
            color: #aaa;
            background: #080808;
            border: 2px solid #333;
        }

        .service-back-button:hover {
            color: white;
            border-color: #ff3d02;
            transform: translateY(-3px);
        }

        .other-services {
            margin-top: 70px;
            text-align: center;
        }

        .other-services h2 {
            color: white;
            font-size: 38px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }

        .other-services h2::after {
            content: "";
            display: block;
            width: 70px;
            height: 4px;
            background: #ff3d02;
            margin: 12px auto 35px;
        }

        .other-services-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
        }

        .other-service-card {
            min-height: 230px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            text-decoration: none;
            background: #080808;
            border: 2px solid #333;
            border-radius: 5px;
            transition: .3s;
        }

        .other-service-card img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            display: block;
            filter: brightness(.7);
            transition: .4s;
        }

        .other-service-card span {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #080808;
        }

        .other-service-card:hover {
            border-color: #ff3d02;
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(255,61,2,.3);
        }

        .other-service-card:hover img {
            filter: brightness(1);
            transform: scale(1.05);
        }

        .other-service-card:hover span {
            color: #ff3d02;
        }

        .other-service-card.unavailable {
            cursor: not-allowed;
            border-color: #7f1d1d;
        }

        .other-service-card.unavailable:hover {
            transform: none;
            border-color: #dc2626;
            box-shadow: 0 15px 35px rgba(220,38,38,.18);
        }

        .other-service-card.unavailable img {
            filter: grayscale(.8) brightness(.4);
        }

        .other-service-card.unavailable:hover img {
            transform: none;
            filter: grayscale(.8) brightness(.4);
        }

        .other-service-card.unavailable span {
            color: #fca5a5;
        }

        .other-service-status {
            padding: 6px 10px;
            color: #fca5a5;
            background: #450a0a;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        @media (max-width: 1000px) {
            .service-hero {
                grid-template-columns: 1fr;
                padding: 35px;
                gap: 35px;
            }

            .service-image {
                height: 450px;
                order: 1;
            }

            .service-content {
                order: 2;
            }

            .service-content h1 {
                font-size: 48px;
            }

            .other-services-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .service-page {
                width: 94%;
                margin-top: 25px;
            }

            .service-hero {
                padding: 25px 20px;
                gap: 25px;
            }

            .service-image {
                height: 350px;
            }

            .service-label {
                font-size: 10px;
                letter-spacing: 2px;
            }

            .service-content h1 {
                font-size: 32px;
                letter-spacing: 1px;
            }

            .service-description {
                font-size: 15px;
                line-height: 1.7;
            }

            .service-list li {
                font-size: 14px;
            }

            .service-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .service-book-button,
            .service-back-button {
                width: 100%;
            }

            .other-services {
                margin-top: 45px;
            }

            .other-services h2 {
                font-size: 27px;
            }

            .other-services-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .other-service-card {
                min-height: 180px;
            }

            .other-service-card img {
                height: 130px;
            }

            .other-service-card span {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <a href="/" class="logo">
        <img src="/logo.png" alt="ABAA Entertainment Logo">
    </a>

    <nav>
        <a href="/">Home</a>
        <a href="/#events">Events</a>
        <a href="/#services">Services</a>
        <a href="/about">About</a>
        <a href="#" class="book-button" onclick="openBookingModal(event)">Book</a>
    </nav>
</header>

<main class="service-page">

    <section class="service-hero <?= !$serviceAvailable ? 'service-hero-unavailable' : '' ?>">

        <div class="service-image <?= !$serviceAvailable ? 'service-image-unavailable' : '' ?>">
            <img
                src="<?= e($service['image_url'] ?: '/logo.png') ?>"
                alt="<?= e($service['name']) ?>"
            >

            <?php if (!$serviceAvailable): ?>
                <div class="service-unavailable-overlay">
                    <span class="service-unavailable-badge">
                        <i class="fa-solid fa-circle-xmark"></i>
                        Not Available
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="service-content">

            <span class="service-label <?= !$serviceAvailable ? 'unavailable' : '' ?>">
                <?= $serviceAvailable ? 'ABAA Entertainment Service' : 'Service Currently Unavailable' ?>
            </span>

            <h1><?= e($service['name']) ?></h1>

            <p class="service-description">
                <?= e($service['description']) ?>
            </p>

            <?php if (!empty($serviceDetails)): ?>
                <ul class="service-list">
                    <?php foreach ($serviceDetails as $detail): ?>
                        <li>
                            <i class="fa-solid fa-circle-check"></i>
                            <span><?= e($detail) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <div class="service-buttons">

                <?php if ($serviceAvailable): ?>
                    <a
                        href="#"
                        class="service-book-button"
                        onclick="openBookingModal(event)"
                    >
                        Book This Service
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                <?php else: ?>
                    <span class="service-book-button service-unavailable-button">
                        <i class="fa-solid fa-ban"></i>
                        Not Available
                    </span>
                <?php endif; ?>

                <a href="/#services" class="service-back-button">
                    <i class="fa-solid fa-arrow-left"></i>
                    All Services
                </a>

            </div>
        </div>
    </section>

    <section class="other-services">
        <h2>Other Services</h2>

        <div class="other-services-grid">
            <?php foreach ($services as $item): ?>
                <?php if ((string)$item['slug'] !== (string)$service['slug']): ?>
                    <?php $otherAvailable = (int)$item['is_available'] === 1; ?>

                    <?php if ($otherAvailable): ?>
                        <a
                            href="/service?service=<?= urlencode($item['slug']) ?>"
                            class="other-service-card"
                        >
                            <img src="<?= e($item['image_url'] ?: '/logo.png') ?>" alt="<?= e($item['name']) ?>">
                            <span><?= e($item['name']) ?></span>
                        </a>
                    <?php else: ?>
                        <div class="other-service-card unavailable" aria-disabled="true">
                            <img src="<?= e($item['image_url'] ?: '/logo.png') ?>" alt="<?= e($item['name']) ?>">
                            <div class="other-service-status">
                                <i class="fa-solid fa-circle-xmark"></i>
                                Not Available
                            </div>
                            <span><?= e($item['name']) ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </section>

</main>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-section footer-brand">
            <img src="/logo.png" alt="ABAA Entertainment Logo">
            <p>
                Creating unforgettable events, entertainment, and experiences
                through creativity, technology, and professional event services.
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
            <?php foreach ($services as $item): ?>
                <a href="<?= (int)$item['is_available'] === 1 ? '/service?service=' . urlencode($item['slug']) : '#services' ?>">
                    <?= e($item['name']) ?>
                    <?php if ((int)$item['is_available'] !== 1): ?>
                        <small style="color:#f87171;"> (Not Available)</small>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="footer-section">
            <h3>Contact Us</h3>
            <a href="https://www.google.com/maps/place/ABAA+Entertainment/@14.4652755,121.1915078,19z" target="_blank" rel="noopener noreferrer" class="contact-item">
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

<!-- BOOKING MODAL -->
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
                    <label for="booking_name">Full Name</label>
                    <input type="text" id="booking_name" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="booking_phone">Phone Number</label>
                    <input type="tel" id="booking_phone" name="phone" placeholder="09XX XXX XXXX" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="booking_email">Email Address</label>
                    <input type="email" id="booking_email" name="email" placeholder="your@email.com" required>
                </div>
                <div class="form-group">
                    <label for="booking_contact_person">Contact Person</label>
                    <input type="text" id="booking_contact_person" name="contact_person" placeholder="Enter contact person's name" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="booking_event">Event Type</label>
                    <select id="booking_event" name="event_type" required>
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

            <div class="form-group">
                <label for="booking_company">Company Name</label>
                <input type="text" id="booking_company" name="cname" placeholder="Enter company name" required>
            </div>

            <div class="form-group">
                <label>Services Needed</label>
                <div class="service-checkboxes">
                    <?php foreach ($services as $item): ?>
                        <?php if ((int)$item['is_available'] === 1): ?>
                            <label class="service-checkbox">
                                <input
                                    type="checkbox"
                                    name="service[]"
                                    value="<?= e($item['name']) ?>"
                                    <?= (string)$item['slug'] === (string)$service['slug'] ? 'checked' : '' ?>
                                >
                                <span><?= e($item['name']) ?></span>
                            </label>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <label class="service-checkbox">
                        <input type="checkbox" name="service[]" value="Full Event Production">
                        <span>Full Event Production</span>
                    </label>
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
function openBookingModal(event) {
    if (event) event.preventDefault();

    const modal = document.getElementById('bookingModal');
    if (!modal) return;

    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeBookingModal() {
    const modal = document.getElementById('bookingModal');
    if (!modal) return;

    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(event) {
    const modal = document.getElementById('bookingModal');
    if (modal && event.target === modal) closeBookingModal();
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') closeBookingModal();
});
</script>

</body>
</html>
