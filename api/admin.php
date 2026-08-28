<?php

include(__DIR__ . '/conn.php');


/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
|
| Vercel-friendly authentication.
|
| We use a signed cookie instead of relying on PHP sessions because
| Vercel functions are serverless.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/

// IMPORTANT:
// Add ADMIN_AUTH_SECRET to your Vercel Environment Variables.
//
// Example:
// ADMIN_AUTH_SECRET = a-long-random-secret-string
//
$authSecret = getenv('ADMIN_AUTH_SECRET');

if (!$authSecret) {

    // For local development only.
    // On production, always set ADMIN_AUTH_SECRET in Vercel.
    $authSecret = 'CHANGE_THIS_TO_A_LONG_RANDOM_SECRET';

}


/*
|--------------------------------------------------------------------------
| COOKIE SETTINGS
|--------------------------------------------------------------------------
*/

$cookieName = 'abaa_admin_auth';

$secureCookie = (
    isset($_SERVER['HTTPS']) &&
    $_SERVER['HTTPS'] !== 'off'
);


/*
|--------------------------------------------------------------------------
| HELPER: BASE64 URL
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
| CREATE AUTH COOKIE
|--------------------------------------------------------------------------
*/

function createAdminCookie($adminId, $username, $secret)
{

    $payload = [
        'id'       => (int) $adminId,
        'username' => $username,
        'exp'      => time() + (60 * 60 * 8)
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
| VERIFY AUTH COOKIE
|--------------------------------------------------------------------------
*/

function verifyAdminCookie($cookie, $secret)
{

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


    if ((int) $payload['exp'] < time()) {

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
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $secureCookie,
            'httponly' => true,
            'samesite' => 'Strict'
        ]
    );


    header('Location: /admin.php');

    exit;

}


/*
|--------------------------------------------------------------------------
| CHECK CURRENT LOGIN
|--------------------------------------------------------------------------
*/

$admin = false;


if (isset($_COOKIE[$cookieName])) {

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


        /*
        |--------------------------------------------------------------------------
        | FIND ADMIN
        |--------------------------------------------------------------------------
        */

        $stmt = $conn->prepare(
            "SELECT id, username, password
             FROM admins
             WHERE username = ?
             LIMIT 1"
        );


        if ($stmt) {

            $stmt->bind_param(
                's',
                $username
            );


            $stmt->execute();


            $result = $stmt->get_result();


            $adminUser = $result->fetch_assoc();


            $stmt->close();


            /*
            |--------------------------------------------------------------------------
            | VERIFY PASSWORD
            |--------------------------------------------------------------------------
            */

            if (
                $adminUser &&
                password_verify(
                    $password,
                    $adminUser['password']
                )
            ) {


                /*
                |--------------------------------------------------------------------------
                | CREATE SIGNED COOKIE
                |--------------------------------------------------------------------------
                */

                $authCookie = createAdminCookie(
                    $adminUser['id'],
                    $adminUser['username'],
                    $authSecret
                );


                setcookie(
                    $cookieName,
                    $authCookie,
                    [
                        'expires'  => time() + (60 * 60 * 8),
                        'path'     => '/',
                        'secure'   => $secureCookie,
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]
                );


                header(
                    'Location: /admin.php'
                );

                exit;


            } else {

                $loginError =
                    'Invalid username or password.';

            }


        } else {

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


    $result = $conn->query(
        "SELECT *
         FROM bookings
         ORDER BY id DESC"
    );


    if ($result) {

        $bookingColumns =
            $result->fetch_fields();


        while (
            $row = $result->fetch_assoc()
        ) {

            $bookings[] = $row;

        }

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
     LOGIN
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
            action="/admin.php"
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


    <!-- ==================================================
         SIDEBAR
    ================================================== -->

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
                href="/admin.php"
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
                        <?= htmlspecialchars($admin['username']) ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>


            <form
                method="POST"
                action="/admin.php"
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


    <!-- ==================================================
         MAIN
    ================================================== -->

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
                    <?= htmlspecialchars($admin['username']) ?>
                </span>

            </div>

        </header>


        <!-- ==================================================
             STATISTICS
        ================================================== -->

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


        <!-- ==================================================
             BOOKINGS
        ================================================== -->

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

                                <?php foreach ($bookingColumns as $column): ?>

                                    <th>

                                        <?= htmlspecialchars($column->name) ?>

                                    </th>

                                <?php endforeach; ?>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($bookings as $booking): ?>

                                <tr>

                                    <?php foreach ($bookingColumns as $column): ?>

                                        <?php

                                        $columnName =
                                            $column->name;

                                        $value =
                                            $booking[$columnName] ?? '';

                                        ?>


                                        <td>

                                            <?php if (
                                                $columnName === 'message'
                                            ): ?>

                                                <div class="message-cell">

                                                    <?= nl2br(
                                                        htmlspecialchars(
                                                            $value
                                                        )
                                                    ) ?>

                                                </div>

                                            <?php elseif (
                                                is_array($value)
                                            ): ?>

                                                <?= htmlspecialchars(
                                                    implode(
                                                        ', ',
                                                        $value
                                                    )
                                                ) ?>

                                            <?php else: ?>

                                                <?= htmlspecialchars(
                                                    (string) $value
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

<?php

if (isset($conn)) {

    $conn->close();

}

?>
