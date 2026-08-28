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

    // Change this if you have not created
    // ADMIN_AUTH_SECRET in Vercel yet.
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
        )
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


    $parts = explode(
        '.',
        $cookie
    );


    if (count($parts) !== 2) {

        return false;

    }


    $payloadEncoded =
        $parts[0];

    $providedSignature =
        $parts[1];


    $expectedSignature =
        hash_hmac(
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


    $payloadJson =
        base64UrlDecode(
            $payloadEncoded
        );


    if (!$payloadJson) {

        return false;

    }


    $payload =
        json_decode(
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


    header(
        'Location: /admin'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| CHECK LOGIN
|--------------------------------------------------------------------------
*/

$admin = false;


if (
    isset(
        $_COOKIE[$cookieName]
    )
) {

    $admin =
        verifyAdminCookie(
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

    $username =
        trim(
            $_POST['username'] ?? ''
        );


    $password =
        $_POST['password'] ?? '';


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


            $adminUser =
                $stmt->fetch();


            /*
            |--------------------------------------------------------------------------
            | CHECK PASSWORD
            |--------------------------------------------------------------------------
            |
            | Plain-text password comparison.
            |
            | Database:
            |
            | username = admin
            | password = admin123
            |
            |--------------------------------------------------------------------------
            */

            if (
                $adminUser &&
                $password === $adminUser['password']
            ) {

                /*
                |--------------------------------------------------------------------------
                | CREATE LOGIN COOKIE
                |--------------------------------------------------------------------------
                */

                $authCookie =
                    createAdminCookie(
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


                /*
                |--------------------------------------------------------------------------
                | REDIRECT TO ADMIN
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: /admin'
                );

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

        $stmt =
            $pdo->query(
                "SELECT *
                 FROM bookings
                 ORDER BY id DESC"
            );


        $bookings =
            $stmt->fetchAll();


        if (!empty($bookings)) {

            $bookingColumns =
                array_keys(
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
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        ABAA Admin
    </title>


    <link
        rel="stylesheet"
        href="/admin.css"
    >


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


        <div class="login-logo">

            <img
                src="/logo.png"
                alt="ABAA Entertainment"
            >

        </div>


        <span class="login-label">
            ABAA ENTERTAINMENT
        </span>


        <h1>
            Admin Login
        </h1>


        <p class="login-description">
            Sign in to manage booking requests.
        </p>


        <?php if ($loginError): ?>

            <div class="login-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= htmlspecialchars($loginError) ?>

            </div>

        <?php endif; ?>


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
                        placeholder="Enter username"
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
                        placeholder="Enter password"
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
                    Login
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


    </div>

</div>


<?php else: ?>

<!-- ==================================================
     ADMIN DASHBOARD
================================================== -->

<div class="admin-layout">


    <!-- SIDEBAR -->

    <aside class="sidebar">


        <div class="sidebar-brand">

            <img
                src="/logo.png"
                alt="ABAA Entertainment"
            >


            <div>

                <strong>
                    ABAA
                </strong>

                <span>
                    ADMIN PANEL
                </span>

            </div>

        </div>


        <nav class="sidebar-nav">


            <a
                href="/admin"
                class="active"
            >

                <i class="fa-solid fa-chart-line"></i>

                Dashboard

            </a>


            <a href="/">

                <i class="fa-solid fa-globe"></i>

                View Website

            </a>

        </nav>


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

                    Logout

                </button>

            </form>


        </div>

    </aside>


    <!-- MAIN -->

    <main class="admin-main">


        <header class="admin-header">


            <div>

                <span class="dashboard-label">
                    ADMINISTRATION
                </span>


                <h1>
                    Booking Dashboard
                </h1>


                <p>
                    Manage and review your event booking requests.
                </p>

            </div>


            <div class="header-user">

                <i class="fa-solid fa-circle-user"></i>


                <span>

                    <?= htmlspecialchars(
                        $admin['username']
                    ) ?>

                </span>

            </div>


        </header>


        <!-- STATISTICS -->

        <section class="stats-grid">


            <div class="stat-card">


                <div class="stat-icon blue">

                    <i class="fa-solid fa-calendar-check"></i>

                </div>


                <div>

                    <span>
                        Total Bookings
                    </span>


                    <strong>
                        <?= count($bookings) ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">


                <div class="stat-icon orange">

                    <i class="fa-solid fa-clock"></i>

                </div>


                <div>

                    <span>
                        Requests
                    </span>


                    <strong>
                        <?= count($bookings) ?>
                    </strong>

                </div>

            </div>


            <div class="stat-card">


                <div class="stat-icon green">

                    <i class="fa-solid fa-users"></i>

                </div>


                <div>

                    <span>
                        Admin
                    </span>


                    <strong>
                        1
                    </strong>

                </div>

            </div>


        </section>


        <!-- BOOKINGS -->

        <section class="bookings-section">


            <div class="section-header">


                <div>

                    <span>
                        BOOKINGS
                    </span>


                    <h2>
                        Recent Booking Requests
                    </h2>

                </div>


                <div class="booking-count">

                    <?= count($bookings) ?>

                    booking<?= count($bookings) === 1 ? '' : 's' ?>

                </div>


            </div>


            <?php if (empty($bookings)): ?>


                <div class="empty-state">


                    <div class="empty-icon">

                        <i class="fa-solid fa-calendar-xmark"></i>

                    </div>


                    <h3>
                        No bookings yet
                    </h3>


                    <p>
                        New booking requests will appear here.
                    </p>


                </div>


            <?php else: ?>


                <div class="table-wrapper">


                    <table class="booking-table">


                        <thead>

                            <tr>

                                <?php foreach (
                                    $bookingColumns
                                    as $column
                                ): ?>

                                    <th>

                                        <?= htmlspecialchars(
                                            $column
                                        ) ?>

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


    </main>


</div>


<?php endif; ?>


</body>

</html>
