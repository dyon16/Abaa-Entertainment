<?php

include(__DIR__ . '/conn.php');


/* ==================================================
   GET SEARCH DATA
================================================== */

$email = trim($_GET['email'] ?? '');

$bookingId = (int) ($_GET['id'] ?? 0);

$booking = null;

$error = '';


/* ==================================================
   SEARCH BOOKING
================================================== */

if ($email !== '' && $bookingId > 0) {

    try {

        $stmt = $pdo->prepare(
            "SELECT *
             FROM bookings
             WHERE id = :id
             AND email = :email
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $bookingId,
            ':email' => $email
        ]);

        $booking = $stmt->fetch();

        if (!$booking) {

            $error =
                'No booking was found with that Booking ID and email address.';

        }

    } catch (PDOException $e) {

        error_log(
            'Booking status search error: ' .
            $e->getMessage()
        );

        $error =
            'Unable to check your booking right now. Please try again later.';

    }

}


/* ==================================================
   STATUS
================================================== */

$currentStatus =
    $booking['status'] ?? '';

$statusClass =
    strtolower(
        str_replace(
            ' ',
            '-',
            $currentStatus
        )
    );


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
        content="#ff3d02"
    >

    <title>
        Check Booking Status | ABAA Entertainment
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }


        body {

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 25px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: white;

            background:
                linear-gradient(
                    rgba(0, 0, 0, 0.90),
                    rgba(0, 0, 0, 0.90)
                ),
                url("/background.jpg");

            background-size: cover;

            background-position: center;

        }


        .status-container {

            width: 100%;

            max-width: 600px;

        }


        .status-card {

            padding: 45px 35px;

            background:
                linear-gradient(
                    135deg,
                    #090909,
                    #180704
                );

            border:
                1px solid #333;

            border-top:
                4px solid #ff3d02;

            border-radius:
                12px;

            box-shadow:
                0 20px 70px
                rgba(0, 0, 0, 0.8),

                0 0 40px
                rgba(255, 61, 2, 0.12);

        }


        .logo {

            text-align: center;

            margin-bottom: 25px;

        }


        .logo img {

            width: 90px;

            height: 90px;

            object-fit: contain;

        }


        h1 {

            text-align: center;

            font-size: 30px;

            text-transform: uppercase;

            margin-bottom: 10px;

        }


        .subtitle {

            text-align: center;

            color: #999;

            line-height: 1.6;

            margin-bottom: 30px;

        }


        .form-group {

            margin-bottom: 20px;

        }


        label {

            display: block;

            margin-bottom: 8px;

            font-weight: bold;

            color: #ddd;

        }


        input {

            width: 100%;

            padding: 14px 16px;

            background: #111;

            border: 1px solid #444;

            border-radius: 6px;

            color: white;

            font-size: 16px;

            outline: none;

        }


        input:focus {

            border-color: #ff3d02;

            box-shadow:
                0 0 0 2px
                rgba(255, 61, 2, 0.15);

        }


        .check-button {

            width: 100%;

            padding: 15px;

            border: 2px solid #ff3d02;

            border-radius: 50px;

            background: #ff3d02;

            color: white;

            font-size: 15px;

            font-weight: bold;

            text-transform: uppercase;

            letter-spacing: 1px;

            cursor: pointer;

            transition: 0.3s;

        }


        .check-button:hover {

            background: transparent;

            color: #ff3d02;

        }


        .error {

            margin-bottom: 25px;

            padding: 14px 16px;

            background: rgba(220, 38, 38, 0.15);

            border: 1px solid #dc2626;

            border-radius: 6px;

            color: #ff8a8a;

            line-height: 1.5;

        }


        .booking-result {

            margin-top: 30px;

            padding-top: 30px;

            border-top: 1px solid #333;

        }


        .result-title {

            color: #aaa;

            font-size: 13px;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 8px;

        }


        .booking-name {

            font-size: 24px;

            font-weight: bold;

            margin-bottom: 20px;

        }


        .status-box {

            padding: 25px;

            text-align: center;

            border-radius: 10px;

            background: #111;

            border: 1px solid #333;

        }


        .status-box small {

            display: block;

            color: #888;

            text-transform: uppercase;

            letter-spacing: 1px;

            margin-bottom: 10px;

        }


        .status {

            display: inline-flex;

            align-items: center;

            gap: 9px;

            padding: 10px 18px;

            border-radius: 50px;

            font-size: 17px;

            font-weight: bold;

        }


        .status span {

            width: 9px;

            height: 9px;

            border-radius: 50%;

            background: currentColor;

            box-shadow:
                0 0 10px currentColor;

        }


        .status-pending {

            color: #ffb020;

            background: rgba(255, 176, 32, 0.15);

            border: 1px solid #ffb020;

        }


        .status-confirmed {

            color: #38bdf8;

            background: rgba(56, 189, 248, 0.15);

            border: 1px solid #38bdf8;

        }


        .status-in-progress {

            color: #c084fc;

            background: rgba(192, 132, 252, 0.15);

            border: 1px solid #c084fc;

        }


        .status-completed {

            color: #22c55e;

            background: rgba(34, 197, 94, 0.15);

            border: 1px solid #22c55e;

        }


        .status-cancelled {

            color: #ef4444;

            background: rgba(239, 68, 68, 0.15);

            border: 1px solid #ef4444;

        }


        .details {

            margin-top: 20px;

            display: grid;

            gap: 10px;

        }


        .detail {

            display: flex;

            justify-content: space-between;

            gap: 20px;

            padding: 12px 0;

            border-bottom: 1px solid #222;

        }


        .detail span:first-child {

            color: #888;

        }


        .detail span:last-child {

            text-align: right;

            color: #ddd;

        }


        .back-button {

            display: block;

            margin-top: 25px;

            text-align: center;

            color: #ff3d02;

            text-decoration: none;

            font-weight: bold;

        }


        .back-button:hover {

            text-decoration: underline;

        }


        @media (max-width: 600px) {

            .status-card {

                padding: 35px 22px;

            }


            h1 {

                font-size: 25px;

            }


            .detail {

                flex-direction: column;

                gap: 4px;

            }


            .detail span:last-child {

                text-align: left;

            }

        }

    </style>

</head>


<body>


<div class="status-container">


    <div class="status-card">


        <div class="logo">

            <img
                src="/logo.png"
                alt="ABAA Entertainment"
            >

        </div>


        <h1>
            Booking Status
        </h1>


        <p class="subtitle">

            Enter your Booking ID and email address
            to check the current status of your request.

        </p>


        <?php if ($error): ?>

            <div class="error">

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form
            method="GET"
            action="/booking-status"
        >


            <div class="form-group">

                <label for="id">
                    Booking ID
                </label>

                <input
                    type="number"
                    id="id"
                    name="id"
                    min="1"
                    value="<?= htmlspecialchars(
                        (string) $bookingId
                    ) ?>"
                    placeholder="Example: 123"
                    required
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="your@email.com"
                    required
                >

            </div>


            <button
                type="submit"
                class="check-button"
            >

                Check Booking Status

            </button>


        </form>


        <?php if ($booking): ?>


            <div class="booking-result">


                <div class="result-title">
                    Booking For
                </div>


                <div class="booking-name">

                    <?= htmlspecialchars(
                        $booking['name']
                    ) ?>

                </div>


                <div class="status-box">


                    <small>
                        Current Status
                    </small>


                    <div
                        class="
                            status
                            status-<?= htmlspecialchars(
                                $statusClass
                            )
                            ?>"
                    >

                        <span></span>

                        <?= htmlspecialchars(
                            $currentStatus
                        ) ?>

                    </div>


                </div>


                <div class="details">


                    <div class="detail">

                        <span>
                            Booking ID
                        </span>

                        <span>
                            #<?= (int) $booking['id'] ?>
                        </span>

                    </div>


                    <div class="detail">

                        <span>
                            Event Type
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $booking['event_type']
                            ) ?>
                        </span>

                    </div>


                    <div class="detail">

                        <span>
                            Event Date
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $booking['event_date']
                            ) ?>
                        </span>

                    </div>


                    <div class="detail">

                        <span>
                            Service
                        </span>

                        <span>
                            <?= htmlspecialchars(
                                $booking['service']
                            ) ?>
                        </span>

                    </div>


                </div>


            </div>


        <?php endif; ?>


        <a
            href="/"
            class="back-button"
        >
            ← Back To Home
        </a>


    </div>


</div>


</body>

</html>
