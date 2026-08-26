<?php

include(__DIR__ . '/conn.php');


/* ==================================================
   ONLY ACCEPT POST REQUESTS
================================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: /api/index.php");
    exit;

}


/* ==================================================
   GET FORM DATA
================================================== */

$name = trim($_POST["name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$event_type = trim($_POST["event_type"] ?? "");
$event_date = trim($_POST["event_date"] ?? "");
$cname = trim($_POST["cname"] ?? "");
$contact_person = trim($_POST["contact_person"] ?? "");
$service = trim($_POST["service"] ?? "");
$message = trim($_POST["message"] ?? "");


/* ==================================================
   VALIDATE REQUIRED FIELDS
================================================== */

if (
    $name === "" ||
    $phone === "" ||
    $email === "" ||
    $event_type === "" ||
    $event_date === "" ||
    $cname === "" ||
    $contact_person === "" ||
    $service === ""
) {

    die("Please complete all required fields.");

}


/* ==================================================
   VALIDATE EMAIL
================================================== */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    die("Please enter a valid email address.");

}


/* ==================================================
   VALIDATE EVENT DATE
================================================== */

$dateObject = DateTime::createFromFormat(
    "Y-m-d",
    $event_date
);

if (
    !$dateObject ||
    $dateObject->format("Y-m-d") !== $event_date
) {

    die("Please enter a valid event date.");

}


/* ==================================================
   INSERT BOOKING
================================================== */

$sql = "INSERT INTO bookings
        (
            name,
            phone,
            email,
            event_type,
            event_date,
            cname,
            contact_person,
            service,
            message
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";


try {

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $name,
        $phone,
        $email,
        $event_type,
        $event_date,
        $cname,
        $contact_person,
        $service,
        $message
    ]);

} catch (PDOException $e) {

    error_log(
        "Booking insert failed: " . $e->getMessage()
    );

    die(
        "Something went wrong while submitting your booking. " .
        "Please try again later."
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
        Booking Submitted | ABAA Entertainment
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

            padding: 20px;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color: white;

            background:
                linear-gradient(
                    rgba(0, 0, 0, 0.92),
                    rgba(0, 0, 0, 0.92)
                ),
                url("background.jpg");

            background-size: cover;
            background-position: center;

        }


        .success-box {

            width: 100%;
            max-width: 550px;

            padding: 50px 35px;

            text-align: center;

            background:
                linear-gradient(
                    135deg,
                    #080808,
                    #160704
                );

            border: 1px solid #333;

            border-top:
                4px solid #ff3d02;

            border-radius: 8px;

            box-shadow:
                0 20px 70px
                rgba(0, 0, 0, 0.8),

                0 0 40px
                rgba(255, 61, 2, 0.15);

        }


        .success-icon {

            width: 75px;
            height: 75px;

            margin:
                0 auto 25px;

            display: flex;

            align-items: center;
            justify-content: center;

            border-radius: 50%;

            background: #ff3d02;

            color: white;

            font-size: 35px;

            font-weight: bold;

        }


        .success-box h1 {

            font-size: 32px;

            text-transform: uppercase;

            margin-bottom: 15px;

        }


        .success-box p {

            color: #aaa;

            font-size: 16px;

            line-height: 1.7;

            margin-bottom: 30px;

        }


        .back-button {

            display: inline-block;

            padding:
                14px 28px;

            background: #ff3d02;

            color: white;

            text-decoration: none;

            font-weight: bold;

            text-transform: uppercase;

            letter-spacing: 1px;

            border-radius: 50px;

            border:
                2px solid #ff3d02;

            transition: 0.3s;

        }


        .back-button:hover {

            background: transparent;

            color: #ff3d02;

            transform:
                translateY(-2px);

        }


        @media (max-width: 600px) {

            .success-box {

                padding:
                    40px 25px;

            }

            .success-box h1 {

                font-size: 25px;

            }

            .success-box p {

                font-size: 14px;

            }

        }

    </style>

</head>


<body>

    <div class="success-box">

        <div class="success-icon">
            ✓
        </div>

        <h1>
            Booking Submitted!
        </h1>

        <p>

            Thank you,
            <strong>
                <?= htmlspecialchars($name) ?>
            </strong>.

            Your booking request has been
            successfully submitted to
            ABAA Entertainment.

            Our team will review your request
            and contact you soon.

        </p>

        <a
            href="/api/index.php"
            class="back-button"
        >
            Back To Home
        </a>

    </div>

</body>

</html>
