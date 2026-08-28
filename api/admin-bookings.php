<?php

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
| VERIFY ADMIN COOKIE
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

    if (!hash_equals(
        $expectedSignature,
        $providedSignature
    )) {
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
| REDIRECT IF NOT LOGGED IN
|--------------------------------------------------------------------------
*/

if (!$admin) {

    header('Location: /admin');

    exit;

}


/*
|--------------------------------------------------------------------------
| UPDATE BOOKING STATUS
|--------------------------------------------------------------------------
*/

$statusMessage = '';
$statusError = '';

$allowedStatuses = [
    'Pending',
    'Confirmed',
    'In Progress',
    'Completed',
    'Cancelled'
];


if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_status'])
) {

    $bookingId = (int) (
        $_POST['booking_id'] ?? 0
    );

    $newStatus = trim(
        $_POST['status'] ?? ''
    );


    if (
        $bookingId <= 0 ||
        !in_array(
            $newStatus,
            $allowedStatuses,
            true
        )
    ) {

        $statusError =
            'Invalid booking status.';

    } else {

        try {

            $stmt = $pdo->prepare(
                "UPDATE bookings
                 SET status = :status
                 WHERE id = :id"
            );

            $stmt->execute([
                ':status' => $newStatus,
                ':id' => $bookingId
            ]);

            $statusMessage =
                'Booking status updated successfully.';

        } catch (PDOException $e) {

            error_log(
                'Booking status update error: ' .
                $e->getMessage()
            );

            $statusError =
                'Unable to update booking status.';

        }

    }

}


/*
|--------------------------------------------------------------------------
| DELETE BOOKING
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_booking'])
) {

    $bookingId = (int) (
        $_POST['booking_id'] ?? 0
    );


    if ($bookingId > 0) {

        try {

            $stmt = $pdo->prepare(
                "DELETE FROM bookings
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $bookingId
            ]);

            $statusMessage =
                'Booking deleted successfully.';

        } catch (PDOException $e) {

            error_log(
                'Booking delete error: ' .
                $e->getMessage()
            );

            $statusError =
                'Unable to delete booking.';

        }

    }

}


/*
|--------------------------------------------------------------------------
| LOAD BOOKINGS
|--------------------------------------------------------------------------
*/

$bookings = [];

try {

    $stmt = $pdo->query(
        "SELECT *
         FROM bookings
         ORDER BY id DESC"
    );

    $bookings = $stmt->fetchAll();

} catch (PDOException $e) {

    error_log(
        'Booking query error: ' .
        $e->getMessage()
    );

    $statusError =
        'Unable to load bookings.';

}


/*
|--------------------------------------------------------------------------
| BOOKING COUNTS
|--------------------------------------------------------------------------
*/

$totalBookings = count($bookings);

$pendingBookings = 0;
$confirmedBookings = 0;
$inProgressBookings = 0;
$completedBookings = 0;
$cancelledBookings = 0;


foreach ($bookings as $booking) {

    $status = $booking['status'] ?? 'Pending';

    switch ($status) {

        case 'Confirmed':
            $confirmedBookings++;
            break;

        case 'In Progress':
            $inProgressBookings++;
            break;

        case 'Completed':
            $completedBookings++;
            break;

        case 'Cancelled':
            $cancelledBookings++;
            break;

        default:
            $pendingBookings++;
            break;

    }

}


/*
|--------------------------------------------------------------------------
| BOOKING COLUMNS
|--------------------------------------------------------------------------
*/

$bookingColumns = [];

if (!empty($bookings)) {

    $bookingColumns = array_keys(
        $bookings[0]
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

    <meta
        name="theme-color"
        content="#ff5a1f"
    >

    <title>
        Bookings - ABAA Admin
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

                <strong>
                    ABAA
                </strong>

                <span>
                    ADMIN PANEL
                </span>

            </div>

        </div>


        <nav class="sidebar-nav">


            <a href="/admin">

                <i class="fa-solid fa-chart-pie"></i>

                <span>
                    Dashboard
                </span>

            </a>


            <a
                href="/admin-bookings"
                class="active"
            >

                <i class="fa-solid fa-calendar-check"></i>

                <span>
                    Bookings
                </span>

            </a>


            <a href="/admin-events">

                <i class="fa-solid fa-photo-film"></i>

                <span>
                    Events
                </span>

            </a>


            <a href="/admin-services">

                <i class="fa-solid fa-screwdriver-wrench"></i>

                <span>
                    Services
                </span>

            </a>


        </nav>


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

                    <i class="fa-solid fa-calendar-check"></i>

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
                    BOOKINGS
                </span>


                <h1>
                    Booking Requests
                </h1>


                <p>
                    Review your booking requests
                    and manage their status.
                </p>

            </div>


            <a
                href="/"
                class="view-site-button"
            >

                <i class="fa-solid fa-globe"></i>

                View Website

            </a>


        </header>



        <!-- ==================================================
             NOTIFICATIONS
        ================================================== -->

        <?php if ($statusMessage): ?>

            <div class="admin-notification success">

                <i class="fa-solid fa-circle-check"></i>

                <?= htmlspecialchars(
                    $statusMessage
                ) ?>

            </div>

        <?php endif; ?>


        <?php if ($statusError): ?>

            <div class="admin-notification error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= htmlspecialchars(
                    $statusError
                ) ?>

            </div>

        <?php endif; ?>



        <!-- ==================================================
             STATISTICS
        ================================================== -->

        <section class="stats-grid">


            <div class="stat-card">

                <div class="stat-icon orange">

                    <i class="fa-solid fa-calendar-check"></i>

                </div>

                <div class="stat-content">

                    <span>
                        Total Bookings
                    </span>

                    <strong>
                        <?= $totalBookings ?>
                    </strong>

                    <small>
                        All requests
                    </small>

                </div>

            </div>



            <div class="stat-card">

                <div class="stat-icon dark-orange">

                    <i class="fa-solid fa-clock"></i>

                </div>

                <div class="stat-content">

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?= $pendingBookings ?>
                    </strong>

                    <small>
                        Awaiting review
                    </small>

                </div>

            </div>



            <div class="stat-card">

                <div class="stat-icon dark">

                    <i class="fa-solid fa-circle-check"></i>

                </div>

                <div class="stat-content">

                    <span>
                        Confirmed
                    </span>

                    <strong>
                        <?= $confirmedBookings ?>
                    </strong>

                    <small>
                        Confirmed events
                    </small>

                </div>

            </div>


        </section>



        <!-- ==================================================
             BOOKING LIST
        ================================================== -->

        <section class="bookings-section">


            <div class="section-header">


                <div>

                    <div class="section-title-row">

                        <span class="section-icon">

                            <i class="fa-solid fa-calendar-days"></i>

                        </span>


                        <div>

                            <span class="section-label">
                                BOOKING MANAGEMENT
                            </span>


                            <h2>
                                All Booking Requests
                            </h2>

                        </div>

                    </div>

                </div>


                <div class="booking-count">

                    <i class="fa-solid fa-inbox"></i>

                    <?= $totalBookings ?>

                    booking<?= $totalBookings === 1 ? '' : 's' ?>

                </div>


            </div>



            <?php if (empty($bookings)): ?>


                <div class="empty-state">

                    <div class="empty-icon">

                        <i class="fa-regular fa-calendar-xmark"></i>

                    </div>


                    <h3>
                        No bookings yet
                    </h3>


                    <p>
                        New booking requests from your
                        website will appear here.
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


                                <th>
                                    Actions
                                </th>


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


                                                <?php

                                                $currentStatus =
                                                    $booking['status']
                                                    ?? 'Pending';

                                                $statusClass =
                                                    strtolower(
                                                        str_replace(
                                                            ' ',
                                                            '-',
                                                            $currentStatus
                                                        )
                                                    );

                                                ?>


                                                <span
                                                    class="
                                                        status-badge
                                                        status-<?= htmlspecialchars(
                                                            $statusClass
                                                        )
                                                    "
                                                >

                                                    <span></span>

                                                    <?= htmlspecialchars(
                                                        $currentStatus
                                                    ) ?>

                                                </span>


                                            <?php else: ?>


                                                <?= htmlspecialchars(
                                                    (string) (
                                                        $booking[$column]
                                                        ?? ''
                                                    )
                                                ) ?>


                                            <?php endif; ?>


                                        </td>


                                    <?php endforeach; ?>



                                    <!-- ACTIONS -->

                                    <td>


                                        <div class="booking-actions">


                                            <!-- STATUS -->

                                            <form
                                                method="POST"
                                                action="/admin-bookings"
                                                class="status-form"
                                            >


                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?= (int) $booking['id'] ?>"
                                                >


                                                <select
                                                    name="status"
                                                    onchange="this.form.submit()"
                                                >


                                                    <?php foreach (
                                                        $allowedStatuses
                                                        as $status
                                                    ): ?>


                                                        <option
                                                            value="<?= htmlspecialchars($status) ?>"
                                                            <?= (
                                                                ($booking['status'] ?? 'Pending')
                                                                === $status
                                                            )
                                                                ? 'selected'
                                                                : ''
                                                            ?>
                                                        >

                                                            <?= htmlspecialchars(
                                                                $status
                                                            ) ?>

                                                        </option>


                                                    <?php endforeach; ?>


                                                </select>


                                                <input
                                                    type="hidden"
                                                    name="update_status"
                                                    value="1"
                                                >


                                            </form>



                                            <!-- DELETE -->

                                            <form
                                                method="POST"
                                                action="/admin-bookings"
                                                onsubmit="
                                                    return confirm(
                                                        'Are you sure you want to delete this booking?'
                                                    );
                                                "
                                            >


                                                <input
                                                    type="hidden"
                                                    name="booking_id"
                                                    value="<?= (int) $booking['id'] ?>"
                                                >


                                                <button
                                                    type="submit"
                                                    name="delete_booking"
                                                    class="delete-button"
                                                    title="Delete booking"
                                                >

                                                    <i class="fa-solid fa-trash"></i>

                                                </button>


                                            </form>


                                        </div>


                                    </td>


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
                Booking Management
            </span>

        </footer>


    </main>


</div>


</body>

</html>
