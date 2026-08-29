<?php

/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

include(__DIR__ . '/conn.php');


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

$authSecret = getenv('ADMIN_AUTH_SECRET');

if (!$authSecret) {

    // IMPORTANT:
    // Set ADMIN_AUTH_SECRET in your Vercel environment variables.
    $authSecret = 'ABAA_CHANGE_THIS_SECRET_2026';

}

$cookieName = 'abaa_admin_auth';

$secureCookie = (
    isset($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
);


/*
|--------------------------------------------------------------------------
| BASE64 URL ENCODE
|--------------------------------------------------------------------------
*/

function base64UrlEncode($data)
{
    return rtrim(
        strtr(
            base64_encode($data),
            '+/',
            '-_'
        ),
        '='
    );
}


/*
|--------------------------------------------------------------------------
| BASE64 URL DECODE
|--------------------------------------------------------------------------
*/

function base64UrlDecode($data)
{
    return base64_decode(
        strtr(
            $data . str_repeat(
                '=',
                (4 - strlen($data) % 4) % 4
            ),
            '-_',
            '+/'
        ),
        true
    );
}


/*
|--------------------------------------------------------------------------
| CREATE LOGIN COOKIE
|--------------------------------------------------------------------------
*/

function createAdminCookie(
    $adminId,
    $username,
    $secret
) {

    $payload = [
        'id' => (int) $adminId,
        'username' => $username,
        'exp' => time() + (60 * 60 * 8)
    ];

    $payloadEncoded = base64UrlEncode(
        json_encode($payload)
    );

    $signature = hash_hmac(
        'sha256',
        $payloadEncoded,
        $secret
    );

    return $payloadEncoded . '.' . $signature;
}


/*
|--------------------------------------------------------------------------
| VERIFY LOGIN COOKIE
|--------------------------------------------------------------------------
*/

function verifyAdminCookie(
    $cookie,
    $secret
) {

    if (!$cookie) {
        return false;
    }

    $parts = explode('.', $cookie);

    if (count($parts) !== 2) {
        return false;
    }

    $payloadEncoded = $parts[0];
    $providedSignature = $parts[1];

    $expectedSignature = hash_hmac(
        'sha256',
        $payloadEncoded,
        $secret
    );

    if (
        !hash_equals(
            $expectedSignature,
            $providedSignature
        )
    ) {
        return false;
    }

    $payloadJson = base64UrlDecode(
        $payloadEncoded
    );

    if (!$payloadJson) {
        return false;
    }

    $payload = json_decode(
        $payloadJson,
        true
    );

    if (!is_array($payload)) {
        return false;
    }

    if (
        !isset($payload['id']) ||
        !isset($payload['username']) ||
        !isset($payload['exp'])
    ) {
        return false;
    }

    if (
        (int) $payload['exp'] < time()
    ) {
        return false;
    }

    return $payload;
}


/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['logout'])
) {

    setcookie(
        $cookieName,
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );

    header('Location: /admin');

    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

$admin = false;

if (
    isset($_COOKIE[$cookieName])
) {

    $admin = verifyAdminCookie(
        $_COOKIE[$cookieName],
        $authSecret
    );

}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

$loginError = '';

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['login'])
) {

    $username = trim(
        $_POST['username'] ?? ''
    );

    $password = $_POST['password'] ?? '';

    if (
        $username === '' ||
        $password === ''
    ) {

        $loginError =
            'Please enter your username and password.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | FIND ADMIN
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                "SELECT id, username, password
                 FROM admins
                 WHERE username = :username
                 LIMIT 1"
            );

            $stmt->execute([
                ':username' => $username
            ]);

            $adminUser = $stmt->fetch();


            /*
            |--------------------------------------------------------------------------
            | CHECK PASSWORD
            |--------------------------------------------------------------------------
            |
            | Current database setup uses plain-text passwords.
            |
            | Recommended later:
            | password_hash()
            | password_verify()
            |
            |--------------------------------------------------------------------------
            */

            if (
                $adminUser &&
                $password === $adminUser['password']
            ) {

                $authCookie = createAdminCookie(
                    $adminUser['id'],
                    $adminUser['username'],
                    $authSecret
                );

                setcookie(
                    $cookieName,
                    $authCookie,
                    [
                        'expires' =>
                            time() + (60 * 60 * 8),

                        'path' => '/',

                        'secure' =>
                            $secureCookie,

                        'httponly' =>
                            true,

                        'samesite' =>
                            'Strict'
                    ]
                );

                header('Location: /admin');

                exit;

            } else {

                $loginError =
                    'Invalid username or password.';

            }

        } catch (PDOException $e) {

            error_log(
                'Admin login error: ' .
                $e->getMessage()
            );

            $loginError =
                'Unable to connect to the database.';

        }

    }

}


/*
|--------------------------------------------------------------------------
| LOAD BOOKINGS
|--------------------------------------------------------------------------
*/

$bookings = [];

$bookingColumns = [];


if ($admin) {

    try {

        $stmt = $pdo->query(
            "SELECT *
             FROM bookings
             ORDER BY id DESC"
        );

        $bookings = $stmt->fetchAll();

        if (!empty($bookings)) {

            $bookingColumns = array_keys(
                $bookings[0]
            );

        }

    } catch (PDOException $e) {

        error_log(
            'Booking query error: ' .
            $e->getMessage()
        );

    }

}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="port"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#ff5a1f"
    >

    <title>ABAA Admin</title>


    <!-- ADMIN CSS -->

    <link
        rel="stylesheet"
        href="/admin.css"
    >


    <!-- FONT AWESOME -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

</head>


<body>


<?php if (!$admin): ?>


<!-- ==================================================
     LOGIN PAGE
================================================== -->

<div class="login-page">


    <div class="login-card">


        <!-- TOP BRAND -->

        <div class="login-logo">

            <div class="login-logo-ring">

                <img
                    src="/logo.png"
                    alt="ABAA Entertainment"
                >

            </div>

        </div>


        <span class="login-label">

            ABAA ENTERTAINMENT

        </span>


        <h1>

            Admin Login

        </h1>


        <p class="login-description">

            Sign in to manage your booking requests,
            events and inquiries.

        </p>


        <?php if ($loginError): ?>

            <div class="login-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <span>

                    <?= htmlspecialchars($loginError) ?>

                </span>

            </div>

        <?php endif; ?>


        <!-- LOGIN FORM -->

        <form
            method="POST"
            action="/admin"
            class="login-form"
        >


            <div class="form-group">


                <label for="username">

                    Username

                </label>


                <div class="input-wrapper">


                    <i class="fa-solid fa-user"></i>


                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        autocomplete="username"
                        required
                    >


                </div>


            </div>


            <div class="form-group">


                <label for="password">

                    Password

                </label>


                <div class="input-wrapper">


                    <i class="fa-solid fa-lock"></i>


                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >


                </div>


            </div>


            <button
                type="submit"
                name="login"
                class="login-button"
            >

                <span>

                    Sign In

                </span>


                <i class="fa-solid fa-arrow-right"></i>

            </button>


        </form>


        <a
            href="/"
            class="back-home"
        >

            <i class="fa-solid fa-arrow-left"></i>

            Back to website

        </a>


        <div class="login-footer">

            <i class="fa-solid fa-shield-halved"></i>

            Secure administrator access

        </div>


    </div>


</div>


<?php else: ?>


<!-- ==================================================
     ADMIN DASHBOARD
================================================== -->

<div class="admin-layout">


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">


        <!-- BRAND -->

        <div class="sidebar-brand">


            <div class="sidebar-logo">

                <img
                    src="/logo.png"
                    alt="ABAA Entertainment"
                >

            </div>


            <div>

                <strong>

                    ABAA

                </strong>


                <span>

                    ADMIN PANEL

                </span>

            </div>


        </div>


        <!-- NAVIGATION -->

        <nav class="sidebar-nav">


            <a
                href="/admin"
                class="active"
            >

                <i class="fa-solid fa-chart-pie"></i>

                <span>

                    Dashboard

                </span>

            </a>

    <a href="/admin/bookings">

        <i class="fa-solid fa-calendar-check"></i>

        <span>
            Bookings
        </span>

    </a>


    <a href="/admin/events">

            <i class="fa-solid fa-photo-film"></i>

            <span>
                Events
            </span>

        </a>


        <a href="/admin/services">

            <i class="fa-solid fa-screwdriver-wrench"></i>

            <span>
                Services
            </span>

        </a>


           


        </nav>


        <!-- SIDEBAR INFO -->

        <div class="sidebar-info">


            <div class="sidebar-info-icon">

                <i class="fa-solid fa-bolt"></i>

            </div>


            <div>

                <strong>

                    ABAA Entertainment

                </strong>


                <span>

                    Booking management system

                </span>

            </div>


        </div>


        <!-- SIDEBAR BOTTOM -->

        <div class="sidebar-bottom">


            <div class="admin-user">


                <div class="admin-avatar">

                    <i class="fa-solid fa-user"></i>

                </div>


                <div>

                    <strong>

                        <?= htmlspecialchars(
                            $admin['username']
                        ) ?>

                    </strong>


                    <span>

                        Administrator

                    </span>

                </div>


            </div>


            <form
                method="POST"
                action="/admin"
            >

                <button
                    type="submit"
                    name="logout"
                    class="logout-button"
                >

                    <i class="fa-solid fa-right-from-bracket"></i>

                    <span>

                        Logout

                    </span>

                </button>

            </form>


        </div>


    </aside>


    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="admin-main">


        <!-- ==================================================
             TOP PANEL
        ================================================== -->

        <div class="top-panel">


            <div class="top-panel-left">


                <div class="top-panel-icon">

                    <i class="fa-solid fa-layer-group"></i>

                </div>


                <div>

                    <span>

                        ABAA ENTERTAINMENT

                    </span>


                    <strong>

                        Booking Management

                    </strong>

                </div>


            </div>


            <div class="top-panel-right">


                <div class="online-status">

                    <span></span>

                    System Online

                </div>


                <div class="top-admin">

                    <i class="fa-solid fa-circle-user"></i>

                    <?= htmlspecialchars(
                        $admin['username']
                    ) ?>

                </div>


            </div>


        </div>


        <!-- ==================================================
             HEADER
        ================================================== -->

        <header class="admin-header">


            <div>


                <span class="dashboard-label">

                    DASHBOARD

                </span>


                <h1>

                    Booking Over

                </h1>


                <p>

                    Re and manage your latest event
                    booking requests.

                </p>


            </div>


            <a
                href="/"
                target="_blank" 
                rel="noopener noreferrer"
                class="view-site-button"
            >

                 <i class="fa-solid fa-globe"></i>

                View Website

            </a>


        </header>


        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <!-- TOTAL BOOKINGS -->

            <div class="stat-card">


                <div class="stat-icon orange">

                    <i class="fa-solid fa-calendar-check"></i>

                </div>


                <div class="stat-content">

                    <span>

                        Total Bookings

                    </span>


                    <strong>

                        <?= count($bookings) ?>

                    </strong>


                    <small>

                        All booking requests

                    </small>

                </div>


            </div>


            <!-- REQUESTS -->

            <div class="stat-card">


                <div class="stat-icon dark-orange">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <div class="stat-content">

                    <span>

                        Requests

                    </span>


                    <strong>

                        <?= count($bookings) ?>

                    </strong>


                    <small>

                        Awaiting review

                    </small>

                </div>


            </div>


            <!-- ADMIN -->

            <div class="stat-card">


                <div class="stat-icon dark">

                    <i class="fa-solid fa-user-shield"></i>

                </div>


                <div class="stat-content">

                    <span>

                        Administrators

                    </span>


                    <strong>

                        1

                    </strong>


                    <small>

                        Active administrator

                    </small>

                </div>


            </div>


        </section>


        <!-- ==================================================
             BOOKINGS SECTION
        ================================================== -->

        <section class="bookings-section">


            <!-- SECTION HEADER -->

            <div class="section-header">


                <div>


                    <div class="section-title-row">


                        <span class="section-icon">

                            <i class="fa-solid fa-calendar-days"></i>

                        </span>


                        <div>

                            <span class="section-label">

                                BOOKINGS

                            </span>


                            <h2>

                                Recent Booking Requests

                            </h2>

                        </div>


                    </div>


                </div>


                <div class="booking-count">

                    <i class="fa-solid fa-inbox"></i>

                    <?= count($bookings) ?>

                    booking<?= count($bookings) === 1 ? '' : 's' ?>

                </div>


            </div>


            <?php if (empty($bookings)): ?>


                <!-- ==================================================
                     EMPTY STATE
                ================================================== -->

                <div class="empty-state">


                    <div class="empty-icon">

                        <i class="fa-regular fa-calendar-xmark"></i>

                    </div>


                    <h3>

                        No bookings yet

                    </h3>


                    <p>

                        New booking requests from your website
                        will appear here.

                    </p>


                    <a
                        href="/"
                        class="empty-button"
                    >

                        <i class="fa-solid fa-globe"></i>

                        Visit Website

                    </a>


                </div>


            <?php else: ?>


                <!-- ==================================================
                     BOOKING TABLE
                ================================================== -->

                <div class="table-wrapper">


                    <table class="booking-table">


                        <thead>

                            <tr>


                                <?php foreach (
                                    $bookingColumns
                                    as $column
                                ): ?>


                                    <th>


                                        <?php

                                        $columnLabel =
                                            str_replace(
                                                '_',
                                                ' ',
                                                $column
                                            );

                                        echo htmlspecialchars(
                                            ucwords(
                                                $columnLabel
                                            )
                                        );

                                        ?>


                                    </th>


                                <?php endforeach; ?>


                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $bookings
                                as $booking
                            ): ?>


                                <tr>


                                    <?php foreach (
                                        $bookingColumns
                                        as $column
                                    ): ?>


                                        <td>


                                            <?php if (
                                                $column === 'message'
                                            ): ?>


                                                <div class="message-cell">


                                                    <?= nl2br(
                                                        htmlspecialchars(
                                                            $booking[$column] ?? ''
                                                        )
                                                    ) ?>


                                                </div>


                                            <?php elseif (
                                                $column === 'status'
                                            ): ?>


                                                <span class="status-badge">

                                                    <span></span>

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $booking[$column] ?? ''
                                                        )
                                                    ) ?>

                                                </span>


                                            <?php else: ?>


                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $booking[$column] ?? ''
                                                    )
                                                ) ?>


                                            <?php endif; ?>


                                        </td>


                                    <?php endforeach; ?>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>


                    </table>


                </div>


            <?php endif; ?>


        </section>


        <!-- ==================================================
             FOOTER
        ================================================== -->

        <footer class="admin-footer">


            <span>

                © <?= date('Y') ?> ABAA Entertainment

            </span>


            <span>

                Admin Dashboard

            </span>


        </footer>


    </main>


</div>


<?php endif; ?>


</body>

</html>
