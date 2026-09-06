<?php

session_start();

include(__DIR__ . '/conn.php');

/*
|--------------------------------------------------------------------------
| ADMIN AUTHENTICATION
|--------------------------------------------------------------------------
*/

$authSecret = getenv('ADMIN_AUTH_SECRET');

if (!$authSecret) {
    $authSecret = 'ABAA_CHANGE_THIS_SECRET_2026';
}

$cookieName = 'abaa_admin_auth';

/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function base64UrlEncode($data)
{
    return rtrim(
        strtr(base64_encode($data), '+/', '-_'),
        '='
    );
}

function base64UrlDecode($data)
{
    $remainder = strlen($data) % 4;

    if ($remainder > 0) {
        $data .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode(
        strtr($data, '-_', '+/'),
        true
    );
}

function createAdminCookie($admin, $secret)
{
    $payload = [
        'id'       => (int) $admin['id'],
        'username' => (string) $admin['username'],
        'exp'      => time() + (7 * 24 * 60 * 60),
    ];

    $payloadEncoded = base64UrlEncode(
        json_encode($payload, JSON_UNESCAPED_SLASHES)
    );

    $signature = hash_hmac(
        'sha256',
        $payloadEncoded,
        $secret
    );

    return $payloadEncoded . '.' . $signature;
}

function verifyAdminCookie($cookie, $secret)
{
    if (empty($cookie)) {
        return false;
    }

    $parts = explode('.', $cookie);

    if (count($parts) !== 2) {
        return false;
    }

    [$payloadEncoded, $providedSignature] = $parts;

    $expectedSignature = hash_hmac(
        'sha256',
        $payloadEncoded,
        $secret
    );

    if (!hash_equals($expectedSignature, $providedSignature)) {
        return false;
    }

    $payloadJson = base64UrlDecode($payloadEncoded);

    if ($payloadJson === false) {
        return false;
    }

    $payload = json_decode($payloadJson, true);

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
| LOGIN / LOGOUT
|--------------------------------------------------------------------------
*/

$loginError = '';

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
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );

    header('Location: /admin');
    exit;
}

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['login'])
) {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $loginError = 'Please enter your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "SELECT id, username, password
                 FROM admins
                 WHERE username = :username
                 LIMIT 1"
            );

            $stmt->execute([
                ':username' => $username,
            ]);

            $adminRow = $stmt->fetch(PDO::FETCH_ASSOC);

            $validPassword = false;

            if ($adminRow) {
                $storedPassword = (string) ($adminRow['password'] ?? '');

                /*
                 * Supports both password_hash() values and existing
                 * plaintext passwords so the current admin database
                 * keeps working while allowing a later migration.
                 */
                if (
                    str_starts_with($storedPassword, '$2y$') ||
                    str_starts_with($storedPassword, '$2a$') ||
                    str_starts_with($storedPassword, '$2b$') ||
                    str_starts_with($storedPassword, '$argon2')
                ) {
                    $validPassword = password_verify(
                        $password,
                        $storedPassword
                    );
                } else {
                    $validPassword = hash_equals(
                        $storedPassword,
                        $password
                    );
                }
            }

            if ($adminRow && $validPassword) {
                $cookieValue = createAdminCookie(
                    $adminRow,
                    $authSecret
                );

                setcookie(
                    $cookieName,
                    $cookieValue,
                    [
                        'expires'  => time() + (7 * 24 * 60 * 60),
                        'path'     => '/',
                        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                        'httponly' => true,
                        'samesite' => 'Lax',
                    ]
                );

                header('Location: /admin');
                exit;
            }

            $loginError = 'Invalid username or password.';
        } catch (PDOException $e) {
            error_log(
                'Admin login error: ' . $e->getMessage()
            );

            $loginError = 'Unable to sign in right now.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| CHECK LOGIN
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
| LOGIN PAGE
|--------------------------------------------------------------------------
*/

if (!$admin):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <meta name="theme-color" content="#ff5a1f">
    <title>Admin Login - ABAA</title>

    <link rel="stylesheet" href="/admin.css">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(255, 90, 31, 0.12),
                    transparent 38%
                ),
                #f7f7f8;
        }

        .login-shell {
            width: min(430px, calc(100% - 32px));
        }

        .login-card {
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 24px;
            padding: 34px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08);
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .login-brand img {
            width: 52px;
            height: 52px;
            object-fit: contain;
        }

        .login-brand strong {
            display: block;
            font-size: 22px;
            letter-spacing: 0.04em;
        }

        .login-brand span {
            color: #777;
            font-size: 12px;
            letter-spacing: 0.14em;
        }

        .login-card h1 {
            margin: 0 0 8px;
            font-size: 30px;
        }

        .login-card p {
            margin: 0 0 24px;
            color: #666;
        }

        .login-field {
            margin-bottom: 16px;
        }

        .login-field label {
            display: block;
            margin-bottom: 7px;
            font-size: 13px;
            font-weight: 700;
        }

        .login-field input {
            width: 100%;
            box-sizing: border-box;
            padding: 13px 14px;
            border: 1px solid #ddd;
            border-radius: 12px;
            font: inherit;
            outline: none;
        }

        .login-field input:focus {
            border-color: #ff5a1f;
            box-shadow: 0 0 0 4px rgba(255, 90, 31, 0.10);
        }

        .login-button {
            width: 100%;
            margin-top: 6px;
            border: 0;
            border-radius: 12px;
            padding: 14px 18px;
            background: #ff5a1f;
            color: #fff;
            font-weight: 800;
            cursor: pointer;
        }

        .login-error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fff1f1;
            color: #b42318;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand">
                <img src="/logo.png" alt="ABAA Entertainment">
                <div>
                    <strong>ABAA</strong>
                    <span>ADMIN PANEL</span>
                </div>
            </div>

            <h1>Welcome back</h1>
            <p>Sign in to manage bookings, events, and services.</p>

            <?php if ($loginError): ?>
                <div class="login-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= e($loginError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin">
                <div class="login-field">
                    <label for="username">Username</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="login-field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button
                    class="login-button"
                    type="submit"
                    name="login"
                    value="1"
                >
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Sign In
                </button>
            </form>
        </div>
    </div>
</body>
</html>
<?php
exit;
endif;

/*
|--------------------------------------------------------------------------
| DASHBOARD DATA
|--------------------------------------------------------------------------
*/

$dashboardError = '';

$bookingStatusCounts = [
    'Pending'     => 0,
    'Confirmed'   => 0,
    'In Progress' => 0,
    'Completed'   => 0,
    'Cancelled'   => 0,
];

$bookingTrend = [];
$eventTypeCounts = [];
$serviceAvailability = [
    'Available'   => 0,
    'Unavailable' => 0,
];
$recentBookings = [];

$totalBookings = 0;
$totalEvents = 0;
$visibleEvents = 0;
$totalServices = 0;
$availableServices = 0;
$adminCount = 0;

try {
    /*
     * Booking totals / statuses
     */
    $stmt = $pdo->query(
        "SELECT
            status,
            COUNT(*) AS total
         FROM bookings
         GROUP BY status"
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $status = trim((string) ($row['status'] ?? ''));

        if ($status === '') {
            $status = 'Pending';
        }

        if (array_key_exists($status, $bookingStatusCounts)) {
            $bookingStatusCounts[$status] =
                (int) $row['total'];
        }
    }

    $totalBookings = array_sum($bookingStatusCounts);

    /*
     * Booking trend: last 12 calendar months, including zero months.
     */
    $stmt = $pdo->query(
        "SELECT
            DATE_FORMAT(event_date, '%Y-%m-01') AS month_key,
            COUNT(*) AS total
         FROM bookings
         WHERE event_date IS NOT NULL
           AND event_date >= DATE_SUB(
               DATE_FORMAT(CURDATE(), '%Y-%m-01'),
               INTERVAL 11 MONTH
           )
         GROUP BY DATE_FORMAT(event_date, '%Y-%m-01')
         ORDER BY month_key ASC"
    );

    $trendRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $trendMap = [];

    foreach ($trendRows as $row) {
        $trendMap[$row['month_key']] = (int) $row['total'];
    }

    for ($i = 11; $i >= 0; $i--) {
        $timestamp = strtotime(
            date('Y-m-01') . " -{$i} months"
        );

        $key = date('Y-m-01', $timestamp);

        $bookingTrend[] = [
            'label' => date('M Y', $timestamp),
            'total' => $trendMap[$key] ?? 0,
        ];
    }

    /*
     * Recent bookings
     */
    $stmt = $pdo->query(
        "SELECT
            id,
            contact_person,
            event_type,
            event_date,
            service,
            created_at,
            status
         FROM bookings
         ORDER BY id DESC
         LIMIT 6"
    );

    $recentBookings =
        $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
     * Events
     */
    $stmt = $pdo->query(
        "SELECT
            type,
            is_visible,
            COUNT(*) AS total
         FROM events
         GROUP BY type, is_visible"
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $type = trim((string) ($row['type'] ?? ''));

        if ($type === '') {
            $type = 'Other';
        }

        if (!isset($eventTypeCounts[$type])) {
            $eventTypeCounts[$type] = 0;
        }

        $eventTypeCounts[$type] +=
            (int) $row['total'];

        $totalEvents += (int) $row['total'];

        if ((int) $row['is_visible'] === 1) {
            $visibleEvents += (int) $row['total'];
        }
    }

    arsort($eventTypeCounts);

    /*
     * Services
     */
    $stmt = $pdo->query(
        "SELECT
            is_available,
            COUNT(*) AS total
         FROM services
         GROUP BY is_available"
    );

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ((int) $row['is_available'] === 1) {
            $serviceAvailability['Available'] =
                (int) $row['total'];
        } else {
            $serviceAvailability['Unavailable'] =
                (int) $row['total'];
        }
    }

    $totalServices =
        array_sum($serviceAvailability);

    $availableServices =
        $serviceAvailability['Available'];

    /*
     * Admin count
     */
    $stmt = $pdo->query(
        "SELECT COUNT(*) FROM admins"
    );

    $adminCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log(
        'Admin dashboard query error: ' .
        $e->getMessage()
    );

    $dashboardError =
        'Some dashboard information could not be loaded.';
}

$pendingBookings =
    $bookingStatusCounts['Pending'];

$confirmedBookings =
    $bookingStatusCounts['Confirmed'];

$inProgressBookings =
    $bookingStatusCounts['In Progress'];

$completedBookings =
    $bookingStatusCounts['Completed'];

$cancelledBookings =
    $bookingStatusCounts['Cancelled'];

$statusPercentages = [];

if ($totalBookings > 0) {
    foreach ($bookingStatusCounts as $status => $count) {
        $statusPercentages[$status] =
            round(($count / $totalBookings) * 100, 1);
    }
}

$eventLabels =
    array_keys($eventTypeCounts);

$eventValues =
    array_values($eventTypeCounts);

$trendLabels =
    array_column($bookingTrend, 'label');

$trendValues =
    array_column($bookingTrend, 'total');

$statusLabels =
    array_keys($bookingStatusCounts);

$statusValues =
    array_values($bookingStatusCounts);

$serviceLabels =
    array_keys($serviceAvailability);

$serviceValues =
    array_values($serviceAvailability);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#ff5a1f"
    >

    <title>
        Dashboard - ABAA Admin
    </title>

    <link
        rel="stylesheet"
        href="/admin.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"
    ></script>

    <style>
        .dashboard-grid {
            display: grid;
            gap: 20px;
        }

        .dashboard-chart-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.55fr) minmax(280px, 0.95fr);
            gap: 20px;
        }

        .dashboard-secondary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .dashboard-card {
            min-width: 0;
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
        }

        .dashboard-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }

        .dashboard-card-title {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .dashboard-card-title-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #fff2ec;
            color: #ff5a1f;
            flex: 0 0 auto;
        }

        .dashboard-card-title strong {
            display: block;
            font-size: 16px;
        }

        .dashboard-card-title span {
            display: block;
            margin-top: 3px;
            color: #777;
            font-size: 12px;
        }

        .dashboard-link {
            color: #ff5a1f;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            white-space: nowrap;
        }

        .chart-wrap {
            position: relative;
            height: 310px;
        }

        .chart-wrap.small {
            height: 260px;
        }

        .dashboard-mini-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .dashboard-mini-stat {
            padding: 12px;
            border-radius: 14px;
            background: #f8f8f8;
        }

        .dashboard-mini-stat span {
            display: block;
            color: #777;
            font-size: 11px;
        }

        .dashboard-mini-stat strong {
            display: block;
            margin-top: 4px;
            font-size: 19px;
        }

        .recent-table-wrap {
            overflow-x: auto;
        }

        .recent-table {
            width: 100%;
            border-collapse: collapse;
        }

        .recent-table th,
        .recent-table td {
            padding: 13px 10px;
            border-bottom: 1px solid #f0f0f0;
            text-align: left;
            font-size: 13px;
            vertical-align: middle;
        }

        .recent-table th {
            color: #777;
            font-size: 11px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .recent-table tr:last-child td {
            border-bottom: 0;
        }

        .booking-name {
            font-weight: 700;
        }

        .booking-meta {
            margin-top: 3px;
            color: #888;
            font-size: 11px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .status-badge span {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-pending {
            color: #a15c00;
            background: #fff5df;
        }

        .status-confirmed {
            color: #176b45;
            background: #e9f8f0;
        }

        .status-in-progress {
            color: #2355a6;
            background: #ecf3ff;
        }

        .status-completed {
            color: #4e4e4e;
            background: #eeeeee;
        }

        .status-cancelled {
            color: #b42318;
            background: #fff0ee;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .quick-action {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 15px;
            border-radius: 15px;
            border: 1px solid #ececec;
            text-decoration: none;
            color: inherit;
            transition:
                transform 0.15s ease,
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }

        .quick-action:hover {
            transform: translateY(-2px);
            border-color: #ffb99e;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.05);
        }

        .quick-action i {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            color: #ff5a1f;
            background: #fff2ec;
            flex: 0 0 auto;
        }

        .quick-action strong {
            display: block;
            font-size: 13px;
        }

        .quick-action span {
            display: block;
            margin-top: 2px;
            color: #777;
            font-size: 11px;
        }

        .dashboard-alert {
            margin-bottom: 20px;
            padding: 13px 15px;
            border-radius: 13px;
            background: #fff1f1;
            color: #b42318;
            border: 1px solid #ffd1cc;
        }

        .dashboard-empty {
            height: 100%;
            min-height: 150px;
            display: grid;
            place-items: center;
            text-align: center;
            color: #777;
        }

        .dashboard-empty i {
            display: block;
            margin-bottom: 9px;
            font-size: 28px;
            color: #bbb;
        }

        @media (max-width: 1100px) {
            .dashboard-chart-grid,
            .dashboard-secondary-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-mini-stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .dashboard-mini-stats,
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }

            .chart-wrap {
                height: 260px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-mini-stats,
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<div class="admin-layout">

    <!-- ==================================================
         SIDEBAR
    ================================================== -->

    <aside class="sidebar">

        <div class="sidebar-brand">

            <div class="sidebar-logo">
                <img
                    src="/logo.png"
                    alt="ABAA Entertainment"
                >
            </div>

            <div>
                <strong>ABAA</strong>
                <span>ADMIN PANEL</span>
            </div>

        </div>

        <nav class="sidebar-nav">

            <a
                href="/admin"
                class="active"
            >
                <i class="fa-solid fa-chart-pie"></i>
                <span>Dashboard</span>
            </a>

            <a href="/admin/bookings">
                <i class="fa-solid fa-calendar-check"></i>
                <span>Bookings</span>
            </a>

            <a href="/admin/events">
                <i class="fa-solid fa-photo-film"></i>
                <span>Events</span>
            </a>

            <a href="/admin/services">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <span>Services</span>
            </a>

        </nav>

        <div class="sidebar-info">
            <div class="sidebar-info-icon">
                <i class="fa-solid fa-bolt"></i>
            </div>

            <div>
                <strong>ABAA Entertainment</strong>
                <span>Booking management system</span>
            </div>
        </div>

        <div class="sidebar-bottom">

            <div class="admin-user">

                <div class="admin-avatar">
                    <i class="fa-solid fa-user"></i>
                </div>

                <div>
                    <strong>
                        <?= e($admin['username']) ?>
                    </strong>
                    <span>Administrator</span>
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
                    value="1"
                >
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>

        </div>

    </aside>

    <!-- ==================================================
         MAIN
    ================================================== -->

    <main class="admin-main">

        <div class="top-panel">

            <div class="top-panel-left">

                <div class="top-panel-icon">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>

                <div>
                    <span>ABAA ENTERTAINMENT</span>
                    <strong>Admin Dashboard</strong>
                </div>

            </div>

            <div class="top-panel-right">

                <div class="online-status">
                    <span></span>
                    System Online
                </div>

                <div class="top-admin">
                    <i class="fa-solid fa-circle-user"></i>
                    <?= e($admin['username']) ?>
                </div>

            </div>

        </div>

        <header class="admin-header">

            <div>
                <span class="dashboard-label">
                    OVERVIEW
                </span>

                <h1>
                    Dashboard
                </h1>

                <p>
                    A quick view of bookings, events, services,
                    and your latest activity.
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

        <?php if ($dashboardError): ?>

            <div class="dashboard-alert">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= e($dashboardError) ?>
            </div>

        <?php endif; ?>

        <!-- ==================================================
             STAT CARDS
        ================================================== -->

        <section class="stats-grid">

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>

                <div class="stat-content">
                    <span>Total Bookings</span>
                    <strong><?= $totalBookings ?></strong>
                    <small>All booking requests</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon dark-orange">
                    <i class="fa-solid fa-clock"></i>
                </div>

                <div class="stat-content">
                    <span>Pending</span>
                    <strong><?= $pendingBookings ?></strong>
                    <small>Awaiting review</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon dark">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div class="stat-content">
                    <span>Confirmed</span>
                    <strong><?= $confirmedBookings ?></strong>
                    <small>Confirmed requests</small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange">
                    <i class="fa-solid fa-photo-film"></i>
                </div>

                <div class="stat-content">
                    <span>Events</span>
                    <strong><?= $totalEvents ?></strong>
                    <small>
                        <?= $visibleEvents ?> visible
                    </small>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon dark">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </div>

                <div class="stat-content">
                    <span>Services</span>
                    <strong><?= $totalServices ?></strong>
                    <small>
                        <?= $availableServices ?> available
                    </small>
                </div>
            </div>

        </section>

        <div
            class="dashboard-grid"
            style="margin-top: 20px;"
        >

            <!-- ==================================================
                 BOOKING CHARTS
            ================================================== -->

            <div class="dashboard-chart-grid">

                <section class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div class="dashboard-card-title">

                            <div class="dashboard-card-title-icon">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>

                            <div>
                                <strong>Booking Trend</strong>
                                <span>
                                    Requests by event month · last 12 months
                                </span>
                            </div>

                        </div>

                        <a
                            href="/admin/bookings"
                            class="dashboard-link"
                        >
                            View bookings
                        </a>

                    </div>

                    <div class="chart-wrap">
                        <canvas id="bookingTrendChart"></canvas>
                    </div>

                    <div class="dashboard-mini-stats">

                        <div class="dashboard-mini-stat">
                            <span>Pending</span>
                            <strong><?= $pendingBookings ?></strong>
                        </div>

                        <div class="dashboard-mini-stat">
                            <span>Confirmed</span>
                            <strong><?= $confirmedBookings ?></strong>
                        </div>

                        <div class="dashboard-mini-stat">
                            <span>In Progress</span>
                            <strong><?= $inProgressBookings ?></strong>
                        </div>

                        <div class="dashboard-mini-stat">
                            <span>Completed</span>
                            <strong><?= $completedBookings ?></strong>
                        </div>

                        <div class="dashboard-mini-stat">
                            <span>Cancelled</span>
                            <strong><?= $cancelledBookings ?></strong>
                        </div>

                    </div>

                </section>

                <section class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div class="dashboard-card-title">

                            <div class="dashboard-card-title-icon">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>

                            <div>
                                <strong>Booking Status</strong>
                                <span>
                                    Current request distribution
                                </span>
                            </div>

                        </div>

                    </div>

                    <div class="chart-wrap small">
                        <canvas id="bookingStatusChart"></canvas>
                    </div>

                </section>

            </div>

            <!-- ==================================================
                 EVENTS + SERVICES
            ================================================== -->

            <div class="dashboard-secondary-grid">

                <section class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div class="dashboard-card-title">

                            <div class="dashboard-card-title-icon">
                                <i class="fa-solid fa-photo-film"></i>
                            </div>

                            <div>
                                <strong>Events by Type</strong>
                                <span>
                                    Uploaded event content
                                </span>
                            </div>

                        </div>

                        <a
                            href="/admin/events"
                            class="dashboard-link"
                        >
                            Manage
                        </a>

                    </div>

                    <div class="chart-wrap small">
                        <canvas id="eventTypeChart"></canvas>
                    </div>

                </section>

                <section class="dashboard-card">

                    <div class="dashboard-card-header">

                        <div class="dashboard-card-title">

                            <div class="dashboard-card-title-icon">
                                <i class="fa-solid fa-screwdriver-wrench"></i>
                            </div>

                            <div>
                                <strong>Service Availability</strong>
                                <span>
                                    Active vs unavailable services
                                </span>
                            </div>

                        </div>

                        <a
                            href="/admin/services"
                            class="dashboard-link"
                        >
                            Manage
                        </a>

                    </div>

                    <div class="chart-wrap small">
                        <canvas id="serviceAvailabilityChart"></canvas>
                    </div>

                </section>

            </div>

            <!-- ==================================================
                 RECENT BOOKINGS
            ================================================== -->

            <section class="dashboard-card">

                <div class="dashboard-card-header">

                    <div class="dashboard-card-title">

                        <div class="dashboard-card-title-icon">
                            <i class="fa-solid fa-inbox"></i>
                        </div>

                        <div>
                            <strong>Recent Bookings</strong>
                            <span>
                                Latest requests received from the website
                            </span>
                        </div>

                    </div>

                    <a
                        href="/admin/bookings"
                        class="dashboard-link"
                    >
                        View all
                    </a>

                </div>

                <?php if (empty($recentBookings)): ?>

                    <div class="dashboard-empty">
                        <div>
                            <i class="fa-regular fa-calendar-xmark"></i>
                            No booking requests yet.
                        </div>
                    </div>

                <?php else: ?>

                    <div class="recent-table-wrap">

                        <table class="recent-table">

                            <thead>
                                <tr>
                                    <th>Contact</th>
                                    <th>Event Type</th>
                                    <th>Event Date</th>
                                    <th>Service</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php foreach ($recentBookings as $booking): ?>

                                <?php
                                    $bookingStatus =
                                        trim(
                                            (string) (
                                                $booking['status'] ??
                                                'Pending'
                                            )
                                        );

                                    if ($bookingStatus === '') {
                                        $bookingStatus = 'Pending';
                                    }

                                    $statusCss =
                                        strtolower(
                                            str_replace(
                                                ' ',
                                                '-',
                                                $bookingStatus
                                            )
                                        );
                                ?>

                                <tr>

                                    <td>
                                        <div class="booking-name">
                                            <?= e(
                                                $booking['contact_person'] ??
                                                ''
                                            ) ?>
                                        </div>

                                        <div class="booking-meta">
                                            #<?= (int) $booking['id'] ?>
                                            ·
                                            <?= e(
                                                $booking['created_at'] ??
                                                ''
                                            ) ?>
                                        </div>
                                    </td>

                                    <td>
                                        <?= e(
                                            $booking['event_type'] ??
                                            ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $booking['event_date'] ??
                                            ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= e(
                                            $booking['service'] ??
                                            ''
                                        ) ?>
                                    </td>

                                    <td>
                                        <span
                                            class="status-badge status-<?= e($statusCss) ?>"
                                        >
                                            <span></span>
                                            <?= e($bookingStatus) ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </section>

            <!-- ==================================================
                 QUICK ACTIONS
            ================================================== -->

            <section class="dashboard-card">

                <div class="dashboard-card-header">

                    <div class="dashboard-card-title">

                        <div class="dashboard-card-title-icon">
                            <i class="fa-solid fa-bolt"></i>
                        </div>

                        <div>
                            <strong>Quick Actions</strong>
                            <span>
                                Jump to the tools you use most
                            </span>
                        </div>

                    </div>

                </div>

                <div class="quick-actions">

                    <a
                        href="/admin/bookings"
                        class="quick-action"
                    >
                        <i class="fa-solid fa-calendar-check"></i>

                        <div>
                            <strong>Manage Bookings</strong>
                            <span>
                                Review and update requests
                            </span>
                        </div>
                    </a>

                    <a
                        href="/admin/events"
                        class="quick-action"
                    >
                        <i class="fa-solid fa-photo-film"></i>

                        <div>
                            <strong>Manage Events</strong>
                            <span>
                                Upload and publish media
                            </span>
                        </div>
                    </a>

                    <a
                        href="/admin/services"
                        class="quick-action"
                    >
                        <i class="fa-solid fa-screwdriver-wrench"></i>

                        <div>
                            <strong>Manage Services</strong>
                            <span>
                                Add or update services
                            </span>
                        </div>
                    </a>

                </div>

            </section>

        </div>

    </main>

</div>

<script>
    const chartFont = {
        family:
            getComputedStyle(document.body).fontFamily ||
            'Arial, sans-serif'
    };

    const chartGridColor = 'rgba(0, 0, 0, 0.07)';
    const chartTickColor = '#777';
    const accentColor = '#ff5a1f';
    const darkAccent = '#1e1e1e';

    const bookingTrendLabels =
        <?= json_encode($trendLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const bookingTrendValues =
        <?= json_encode($trendValues) ?>;

    const bookingStatusLabels =
        <?= json_encode($statusLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const bookingStatusValues =
        <?= json_encode($statusValues) ?>;

    const eventLabels =
        <?= json_encode($eventLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const eventValues =
        <?= json_encode($eventValues) ?>;

    const serviceLabels =
        <?= json_encode($serviceLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const serviceValues =
        <?= json_encode($serviceValues) ?>;

    /*
     * Booking trend
     */
    new Chart(
        document.getElementById('bookingTrendChart'),
        {
            type: 'line',

            data: {
                labels: bookingTrendLabels,

                datasets: [
                    {
                        label: 'Bookings',
                        data: bookingTrendValues,

                        borderColor: accentColor,
                        backgroundColor:
                            'rgba(255, 90, 31, 0.10)',

                        fill: true,
                        tension: 0.35,

                        pointRadius: 3,
                        pointHoverRadius: 5,

                        borderWidth: 2
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    intersect: false,
                    mode: 'index'
                },

                plugins: {
                    legend: {
                        display: false
                    },

                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return (
                                    ' Bookings: ' +
                                    context.parsed.y
                                );
                            }
                        }
                    }
                },

                scales: {
                    x: {
                        grid: {
                            display: false
                        },

                        ticks: {
                            color: chartTickColor,
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 6,

                            font: chartFont
                        }
                    },

                    y: {
                        beginAtZero: true,

                        ticks: {
                            precision: 0,
                            color: chartTickColor,
                            font: chartFont
                        },

                        grid: {
                            color: chartGridColor
                        }
                    }
                }
            }
        }
    );

    /*
     * Booking status doughnut
     */
    new Chart(
        document.getElementById('bookingStatusChart'),
        {
            type: 'doughnut',

            data: {
                labels: bookingStatusLabels,

                datasets: [
                    {
                        data: bookingStatusValues,

                        backgroundColor: [
                            '#ffb347',
                            '#39a96b',
                            '#5a82d6',
                            '#888888',
                            '#d9534f'
                        ],

                        borderWidth: 3,
                        borderColor: '#ffffff',

                        hoverOffset: 5
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                cutout: '64%',

                plugins: {
                    legend: {
                        position: 'bottom',

                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 14,
                            font: chartFont
                        }
                    },

                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const values =
                                    context.dataset.data;

                                const total =
                                    values.reduce(
                                        (sum, value) =>
                                            sum + value,
                                        0
                                    );

                                const value =
                                    Number(context.raw || 0);

                                const percent =
                                    total > 0
                                        ? (
                                            value /
                                            total *
                                            100
                                        ).toFixed(1)
                                        : '0.0';

                                return (
                                    ' ' +
                                    context.label +
                                    ': ' +
                                    value +
                                    ' (' +
                                    percent +
                                    '%)'
                                );
                            }
                        }
                    }
                }
            }
        }
    );

    /*
     * Event types
     */
    new Chart(
        document.getElementById('eventTypeChart'),
        {
            type: 'bar',

            data: {
                labels: eventLabels.length
                    ? eventLabels
                    : ['No events'],

                datasets: [
                    {
                        label: 'Events',

                        data: eventValues.length
                            ? eventValues
                            : [0],

                        backgroundColor:
                            'rgba(255, 90, 31, 0.78)',

                        borderRadius: 9,

                        maxBarThickness: 40
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                plugins: {
                    legend: {
                        display: false
                    }
                },

                scales: {
                    x: {
                        grid: {
                            display: false
                        },

                        ticks: {
                            color: chartTickColor,
                            font: chartFont
                        }
                    },

                    y: {
                        beginAtZero: true,

                        ticks: {
                            precision: 0,
                            color: chartTickColor,
                            font: chartFont
                        },

                        grid: {
                            color: chartGridColor
                        }
                    }
                }
            }
        }
    );

    /*
     * Services
     */
    new Chart(
        document.getElementById('serviceAvailabilityChart'),
        {
            type: 'doughnut',

            data: {
                labels: serviceLabels,

                datasets: [
                    {
                        data: serviceValues,

                        backgroundColor: [
                            darkAccent,
                            '#cfcfcf'
                        ],

                        borderWidth: 3,
                        borderColor: '#ffffff',
                        hoverOffset: 5
                    }
                ]
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                cutout: '64%',

                plugins: {
                    legend: {
                        position: 'bottom',

                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 14,
                            font: chartFont
                        }
                    },

                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const value =
                                    Number(context.raw || 0);

                                return (
                                    ' ' +
                                    context.label +
                                    ': ' +
                                    value
                                );
                            }
                        }
                    }
                }
            }
        }
    );
</script>

</body>
</html>
