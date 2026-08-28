<?php include(__DIR__ . '/conn.php'); 

/*
|--------------------------------------------------------------------------

ADMIN AUTHENTICATION
*/

$authSecret = getenv('ADMIN_AUTH_SECRET');

if (!$authSecret) {
$authSecret = 'ABAA_CHANGE_THIS_SECRET_2026';
}

$cookieName = 'abaa_admin_auth';

/*
|--------------------------------------------------------------------------

VERCEL BLOB
*/

$blobToken = getenv('BLOB_READ_WRITE_TOKEN');

/*
|--------------------------------------------------------------------------

BASE64 URL DECODE
*/

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

/*
|--------------------------------------------------------------------------

VERIFY ADMIN COOKIE
*/

function verifyAdminCookie($cookie, $secret)
{
if (empty($cookie)) {
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

$payloadJson = base64UrlDecode($payloadEncoded);

if ($payloadJson === false) {
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

CHECK LOGIN
*/

$admin = false;

if (isset($_COOKIE[$cookieName])) {
$admin = verifyAdminCookie(
$_COOKIE[$cookieName],
$authSecret
);
}

if (!$admin) {
header('Location: /admin');
exit;
}

/*
|--------------------------------------------------------------------------

HELPERS
*/

function e($value)
{
return htmlspecialchars(
(string) ($value ?? ''),
ENT_QUOTES,
'UTF-8'
);
}

/*
|--------------------------------------------------------------------------

BLOB UPLOAD FUNCTION
*/

function uploadToVercelBlob(
$tmpFile,
$fileName,
$mimeType,
$blobToken
) {
if (!$blobToken) {
return [
'success' => false,
'error' => 'BLOB_READ_WRITE_TOKEN is not configured.'
];
}

if (!is_file($tmpFile)) {
    return [
        'success' => false,
        'error' => 'Temporary upload file was not found.'
    ];
}

$fileContents = file_get_contents($tmpFile);

if ($fileContents === false) {
    return [
        'success' => false,
        'error' => 'Unable to read uploaded file.'
    ];
}

/*
|--------------------------------------------------------------------------
| VERCEL BLOB API
|--------------------------------------------------------------------------
*/

$url =
    'https://blob.vercel-storage.com/' .
    rawurlencode($fileName);

$ch = curl_init($url);

curl_setopt_array(
    $ch,
    [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $fileContents,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $blobToken,
            'Content-Type: ' . $mimeType,
            'x-api-version: 7',
        ],

        CURLOPT_TIMEOUT => 300,
        CURLOPT_CONNECTTIMEOUT => 30,
    ]
);

$response = curl_exec($ch);

$curlError = curl_error($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);



if ($response === false) {
    return [
        'success' => false,
        'error' => 'Blob upload failed: ' . $curlError
    ];
}

if (
    $httpCode < 200 ||
    $httpCode >= 300
) {
    error_log(
        'Vercel Blob upload error: HTTP ' .
        $httpCode .
        ' Response: ' .
        $response
    );

    return [
        'success' => false,
        'error' => 'Vercel Blob rejected the upload.'
    ];
}

$data = json_decode(
    $response,
    true
);

if (!is_array($data)) {
    return [
        'success' => false,
        'error' => 'Invalid response from Vercel Blob.'
    ];
}

$blobUrl =
    $data['url'] ??
    $data['downloadUrl'] ??
    null;

if (!$blobUrl) {
    return [
        'success' => false,
        'error' => 'Vercel Blob did not return a file URL.'
    ];
}

return [
    'success' => true,
    'url' => $blobUrl
];

}

/*
|--------------------------------------------------------------------------

DELETE FROM VERCEL BLOB
*/

function deleteFromVercelBlob(
$blobUrl,
$blobToken
) {
if (
empty($blobUrl) ||
empty($blobToken)
) {
return false;
}

$ch = curl_init($blobUrl);

curl_setopt_array(
    $ch,
    [
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $blobToken,
            'x-api-version: 7',
        ],

        CURLOPT_TIMEOUT => 60,
    ]
);

$response = curl_exec($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);



return (
    $response !== false &&
    $httpCode >= 200 &&
    $httpCode < 300
);

}

/*
|--------------------------------------------------------------------------

MESSAGES
*/

$statusMessage = '';
$statusError = '';

/*
|--------------------------------------------------------------------------

DELETE EVENT
*/

if (
$_SERVER['REQUEST_METHOD'] === 'POST' &&
isset($_POST['delete_event'])
) {
$eventId = (int) (
$_POST['event_id'] ?? 0
);

if ($eventId > 0) {
    try {

        $stmt = $pdo->prepare(
            "SELECT
                file_url,
                thumbnail_url
             FROM events
             WHERE id = :id
             LIMIT 1"
        );

        $stmt->execute([
            ':id' => $eventId
        ]);

        $event = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        if ($event) {

            /*
            |--------------------------------------------------------------------------
            | DELETE MAIN BLOB
            |--------------------------------------------------------------------------
            */

            if (!empty($event['file_url'])) {
                deleteFromVercelBlob(
                    $event['file_url'],
                    $blobToken
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DELETE THUMBNAIL BLOB
            |--------------------------------------------------------------------------
            */

            if (!empty($event['thumbnail_url'])) {
                deleteFromVercelBlob(
                    $event['thumbnail_url'],
                    $blobToken
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DELETE DATABASE RECORD
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->prepare(
                "DELETE FROM events
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $eventId
            ]);

            $statusMessage =
                'Event deleted successfully.';

        } else {

            $statusError =
                'Event not found.';
        }

    } catch (PDOException $e) {

        error_log(
            'Event delete error: ' .
            $e->getMessage()
        );

        $statusError =
            'Unable to delete event.';
    }
}

}

/*
|--------------------------------------------------------------------------

TOGGLE VISIBILITY
*/

if (
$_SERVER['REQUEST_METHOD'] === 'POST' &&
isset($_POST['toggle_visibility'])
) {
$eventId = (int) (
$_POST['event_id'] ?? 0
);

if ($eventId > 0) {
    try {

        $stmt = $pdo->prepare(
            "UPDATE events
             SET is_visible =
                CASE
                    WHEN is_visible = 1
                    THEN 0
                    ELSE 1
                END
             WHERE id = :id"
        );

        $stmt->execute([
            ':id' => $eventId
        ]);

        $statusMessage =
            'Event visibility updated.';

    } catch (PDOException $e) {

        error_log(
            'Event visibility error: ' .
            $e->getMessage()
        );

        $statusError =
            'Unable to update visibility.';
    }
}

}

/*
|--------------------------------------------------------------------------

UPLOAD EVENT
*/

if (
$_SERVER['REQUEST_METHOD'] === 'POST' &&
isset($_POST['upload_event'])
) {

$title = trim(
    $_POST['title'] ?? ''
);

$type = trim(
    $_POST['type'] ?? ''
);

/*
|--------------------------------------------------------------------------
| VALIDATE BLOB TOKEN
|--------------------------------------------------------------------------
*/

if (!$blobToken) {

    $statusError =
        'Vercel Blob is not configured. Please add BLOB_READ_WRITE_TOKEN.';

}

/*
|--------------------------------------------------------------------------
| VALIDATE TITLE
|--------------------------------------------------------------------------
*/

elseif ($title === '') {

    $statusError =
        'Please enter an event title.';

}

/*
|--------------------------------------------------------------------------
| VALIDATE TYPE
|--------------------------------------------------------------------------
*/

elseif (
    !in_array(
        $type,
        [
            'image',
            'video'
        ],
        true
    )
) {

    $statusError =
        'Invalid event type.';

}

/*
|--------------------------------------------------------------------------
| CHECK MAIN FILE
|--------------------------------------------------------------------------
*/

elseif (
    !isset($_FILES['event_file']) ||
    $_FILES['event_file']['error'] !== UPLOAD_ERR_OK
) {

    $statusError =
        'Please select an event file.';

}

else {

    $file = $_FILES['event_file'];

    $originalName =
        $file['name'];

    $tmpName =
        $file['tmp_name'];

    $fileSize =
        (int) $file['size'];

    /*
    |--------------------------------------------------------------------------
    | MAX FILE SIZE
    |--------------------------------------------------------------------------
    */

    $maxFileSize =
        4 * 1024 * 1024;

    if ($fileSize > $maxFileSize) {

        $statusError =
            'File is too large for this PHP upload endpoint. Maximum is 4MB. For large videos, use direct Vercel Blob client uploads.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | EXTENSION
        |--------------------------------------------------------------------------
        */

        $extension = strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );

        /*
        |--------------------------------------------------------------------------
        | ALLOWED EXTENSIONS
        |--------------------------------------------------------------------------
        */

        $imageExtensions = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];

        $videoExtensions = [
            'mp4',
            'webm',
            'mov'
        ];

        if ($type === 'image') {

            $allowedExtensions =
                $imageExtensions;

        } else {

            $allowedExtensions =
                $videoExtensions;
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATE EXTENSION
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $extension,
                $allowedExtensions,
                true
            )
        ) {

            $statusError =
                'Invalid file type for ' .
                e($type) .
                '.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | MIME TYPE
            |--------------------------------------------------------------------------
            */

            $mimeType =
                mime_content_type($tmpName);

            if (!$mimeType) {
                $mimeType =
                    'application/octet-stream';
            }

            /*
            |--------------------------------------------------------------------------
            | SAFE FILE NAME
            |--------------------------------------------------------------------------
            */

            $safeTitle = preg_replace(
                '/[^a-zA-Z0-9_-]+/',
                '-',
                $title
            );

            $safeTitle = trim(
                $safeTitle,
                '-'
            );

            if ($safeTitle === '') {
                $safeTitle = 'event';
            }

            $uniqueId = bin2hex(
                random_bytes(12)
            );

            $newFileName =
                'events/' .
                $safeTitle .
                '-' .
                $uniqueId .
                '.' .
                $extension;

            /*
            |--------------------------------------------------------------------------
            | UPLOAD MAIN FILE TO BLOB
            |--------------------------------------------------------------------------
            */

            $blobResult =
                uploadToVercelBlob(
                    $tmpName,
                    $newFileName,
                    $mimeType,
                    $blobToken
                );

            if (!$blobResult['success']) {

                $statusError =
                    $blobResult['error'];

            } else {

                $fileUrl =
                    $blobResult['url'];

                /*
                |--------------------------------------------------------------------------
                | THUMBNAIL
                |--------------------------------------------------------------------------
                */

                $thumbnailUrl = null;

                if (
                    $type === 'video' &&
                    isset($_FILES['thumbnail']) &&
                    $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK
                ) {

                    $thumbnail =
                        $_FILES['thumbnail'];

                    $thumbnailExtension =
                        strtolower(
                            pathinfo(
                                $thumbnail['name'],
                                PATHINFO_EXTENSION
                            )
                        );

                    $allowedThumbnailExtensions = [
                        'jpg',
                        'jpeg',
                        'png',
                        'webp'
                    ];

                    if (
                        in_array(
                            $thumbnailExtension,
                            $allowedThumbnailExtensions,
                            true
                        )
                    ) {

                        $thumbnailMime =
                            mime_content_type(
                                $thumbnail['tmp_name']
                            );

                        if (!$thumbnailMime) {
                            $thumbnailMime =
                                'image/jpeg';
                        }

                        $thumbnailFileName =
                            'events/' .
                            $safeTitle .
                            '-thumb-' .
                            $uniqueId .
                            '.' .
                            $thumbnailExtension;

                        $thumbnailResult =
                            uploadToVercelBlob(
                                $thumbnail['tmp_name'],
                                $thumbnailFileName,
                                $thumbnailMime,
                                $blobToken
                            );

                        if (
                            $thumbnailResult['success']
                        ) {

                            $thumbnailUrl =
                                $thumbnailResult['url'];
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | INSERT DATABASE RECORD
                |--------------------------------------------------------------------------
                */

                try {

                    $stmt = $pdo->prepare(
                        "INSERT INTO events
                        (
                            title,
                            type,
                            file_url,
                            thumbnail_url,
                            is_visible,
                            created_at
                        )
                        VALUES
                        (
                            :title,
                            :type,
                            :file_url,
                            :thumbnail_url,
                            1,
                            NOW()
                        )"
                    );

                    $stmt->execute([
                        ':title' =>
                            $title,

                        ':type' =>
                            $type,

                        ':file_url' =>
                            $fileUrl,

                        ':thumbnail_url' =>
                            $thumbnailUrl
                    ]);

                    $statusMessage =
                        'Event uploaded successfully.';

                } catch (PDOException $e) {

                    /*
                    |--------------------------------------------------------------------------
                    | DATABASE FAILED
                    |--------------------------------------------------------------------------
                    */

                    deleteFromVercelBlob(
                        $fileUrl,
                        $blobToken
                    );

                    if (!empty($thumbnailUrl)) {

                        deleteFromVercelBlob(
                            $thumbnailUrl,
                            $blobToken
                        );
                    }

                    error_log(
                        'Event insert error: ' .
                        $e->getMessage()
                    );

                    $statusError =
                        'Unable to save event.';
                }
            }
        }
    }
}

}

/*
|--------------------------------------------------------------------------

LOAD EVENTS
*/

$events = [];

try {

$stmt = $pdo->query(
    "SELECT
        id,
        title,
        type,
        file_url,
        thumbnail_url,
        is_visible,
        created_at
     FROM events
     ORDER BY id DESC"
);

$events =
    $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (PDOException $e) {

error_log(
    'Event query error: ' .
    $e->getMessage()
);

$statusError =
    'Unable to load events.';

}

/*
|--------------------------------------------------------------------------

STATISTICS
*/

$totalEvents =
count($events);

$visibleEvents = 0;

foreach ($events as $event) {

if (
    (int) $event['is_visible'] === 1
) {
    $visibleEvents++;
}

}

?>

<!DOCTYPE html> <html lang="en"> <head>
<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<meta
    name="theme-color"
    content="#ff5a1f"
>

<title>Events - ABAA Admin</title>

<link
    rel="stylesheet"
    href="/admin.css"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

    .event-upload-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,.03);
    }

    .event-upload-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
    }

    .event-upload-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--orange);
        background: var(--orange-light);
    }

    .event-upload-header span {
        display: block;
        color: var(--orange);
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.5px;
    }

    .event-upload-header h2 {
        margin-top: 4px;
        font-size: 20px;
        color: var(--dark);
    }

    .event-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .event-form-group {
        display: flex;
        flex-direction: column;
        gap: 7px;
    }

    .event-form-group.full {
        grid-column: 1 / -1;
    }

    .event-form-group label {
        font-size: 12px;
        font-weight: 700;
        color: #374151;
    }

    .event-form-group input,
    .event-form-group select {
        width: 100%;
        height: 45px;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 0 12px;
        background: #fafafa;
        color: var(--text);
        outline: none;
    }

    .event-form-group input:focus,
    .event-form-group select:focus {
        background: white;
        border-color: var(--orange);
        box-shadow: 0 0 0 3px rgba(255,90,31,.08);
    }

    .event-form-group input[type="file"] {
        padding: 9px;
        height: auto;
    }

    .event-help {
        color: #9ca3af;
        font-size: 11px;
    }

    .event-upload-button {
        margin-top: 20px;
        border: none;
        border-radius: 9px;
        padding: 12px 18px;
        background: var(--orange);
        color: white;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 9px;
        transition: .2s;
    }

    .event-upload-button:hover {
        background: var(--orange-dark);
        transform: translateY(-1px);
    }

    .event-admin-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        padding: 25px;
    }

    .event-admin-card {
        border: 1px solid var(--border);
        border-radius: 13px;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,.04);
    }

    .event-preview {
        height: 190px;
        background: #111;
        position: relative;
        overflow: hidden;
    }

    .event-preview img,
    .event-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .event-video-preview {
        position: relative;
        width: 100%;
        height: 100%;
    }

    .event-video-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .event-video-icon {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        background: rgba(255,90,31,.92);
        box-shadow: 0 5px 20px rgba(0,0,0,.25);
    }

    .event-type-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 6px 9px;
        border-radius: 6px;
        background: rgba(0,0,0,.72);
        color: white;
        font-size: 10px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .event-visibility {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 6px 9px;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 800;
        color: white;
    }

    .event-visibility.visible {
        background: #16a34a;
    }

    .event-visibility.hidden {
        background: #6b7280;
    }

    .event-admin-content {
        padding: 16px;
    }

    .event-admin-content h3 {
        font-size: 16px;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .event-admin-date {
        color: #9ca3af;
        font-size: 11px;
        margin-bottom: 15px;
    }

    .event-admin-actions {
        display: flex;
        gap: 8px;
    }

    .event-action-button {
        flex: 1;
        min-height: 36px;
        border-radius: 7px;
        border: 1px solid var(--border);
        background: white;
        color: #374151;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .event-action-button:hover {
        border-color: var(--orange);
        color: var(--orange);
    }

    .event-action-button.delete {
        flex: 0 0 40px;
        color: #dc2626;
        border-color: #fecaca;
        background: #fef2f2;
    }

    .event-action-button.delete:hover {
        background: #dc2626;
        color: white;
    }

    @media (max-width: 1000px) {
        .event-admin-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 600px) {
        .event-form-grid {
            grid-template-columns: 1fr;
        }

        .event-form-group.full {
            grid-column: auto;
        }

        .event-admin-grid {
            grid-template-columns: 1fr;
            padding: 18px;
        }

        .event-upload-card {
            padding: 20px;
        }
    }

</style>

</head> <body> <div class="admin-layout"> <!-- ===================================================== SIDEBAR ===================================================== --> <aside class="sidebar">
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

    <a href="/admin">

        <i class="fa-solid fa-chart-pie"></i>

        <span>Dashboard</span>

    </a>

    <a href="/admin/bookings">

        <i class="fa-solid fa-calendar-check"></i>

        <span>Bookings</span>

    </a>

    <a
        href="#"
        class="active"
    >

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

        <span>Event management system</span>

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
        >

            <i class="fa-solid fa-right-from-bracket"></i>

            <span>Logout</span>

        </button>

    </form>

</div>

</aside> <!-- ===================================================== MAIN ===================================================== --> <main class="admin-main">
<div class="top-panel">

    <div class="top-panel-left">

        <div class="top-panel-icon">

            <i class="fa-solid fa-photo-film"></i>

        </div>

        <div>

            <span>ABAA ENTERTAINMENT</span>

            <strong>Event Management</strong>

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
            EVENTS
        </span>

        <h1>Event Management</h1>

        <p>
            Upload and manage your event images
            and videos.
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

<!-- =====================================================
     NOTIFICATIONS
===================================================== -->

<?php if ($statusMessage): ?>

    <div class="admin-notification success">

        <i class="fa-solid fa-circle-check"></i>

        <?= e($statusMessage) ?>

    </div>

<?php endif; ?>

<?php if ($statusError): ?>

    <div class="admin-notification error">

        <i class="fa-solid fa-circle-exclamation"></i>

        <?= e($statusError) ?>

    </div>

<?php endif; ?>

<!-- =====================================================
     STATISTICS
===================================================== -->

<section class="stats-grid">

    <div class="stat-card">

        <div class="stat-icon orange">

            <i class="fa-solid fa-photo-film"></i>

        </div>

        <div class="stat-content">

            <span>Total Events</span>

            <strong>
                <?= $totalEvents ?>
            </strong>

            <small>Uploaded events</small>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon dark-orange">

            <i class="fa-solid fa-eye"></i>

        </div>

        <div class="stat-content">

            <span>Visible</span>

            <strong>
                <?= $visibleEvents ?>
            </strong>

            <small>Showing on website</small>

        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon dark">

            <i class="fa-solid fa-cloud-arrow-up"></i>

        </div>

        <div class="stat-content">

            <span>Uploads</span>

            <strong>
                <?= $totalEvents ?>
            </strong>

            <small>Images & videos</small>

        </div>

    </div>

</section>

<!-- =====================================================
     UPLOAD EVENT
===================================================== -->

<section class="event-upload-card">

    <div class="event-upload-header">

        <div class="event-upload-icon">

            <i class="fa-solid fa-cloud-arrow-up"></i>

        </div>

        <div>

            <span>EVENT CONTENT</span>

            <h2>Upload New Event</h2>

        </div>

    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
    >

        <div class="event-form-grid">

            <div class="event-form-group">

                <label for="title">
                    Event Title
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    placeholder="Example: Android18 x UYRE"
                    required
                >

            </div>

            <div class="event-form-group">

                <label for="type">
                    Media Type
                </label>

                <select
                    id="type"
                    name="type"
                    onchange="toggleThumbnail()"
                    required
                >

                    <option value="image">
                        Image
                    </option>

                    <option value="video">
                        Video
                    </option>

                </select>

            </div>

            <div class="event-form-group">

                <label for="event_file">
                    Event File
                </label>

                <input
                    type="file"
                    id="event_file"
                    name="event_file"
                    accept=".jpg,.jpeg,.png,.webp,.mp4,.webm,.mov"
                    required
                >

                <span class="event-help">
                    Images: JPG, PNG, WEBP ·
                    Videos: MP4, WEBM, MOV
                </span>

            </div>

            <div
                class="event-form-group"
                id="thumbnailGroup"
                style="display:none;"
            >

                <label for="thumbnail">
                    Video Thumbnail
                </label>

                <input
                    type="file"
                    id="thumbnail"
                    name="thumbnail"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <span class="event-help">
                    Optional image displayed before the video plays.
                </span>

            </div>

        </div>

        <button
            type="submit"
            name="upload_event"
            class="event-upload-button"
        >

            <i class="fa-solid fa-cloud-arrow-up"></i>

            Upload Event

        </button>

    </form>

</section>

<!-- =====================================================
     EVENTS

===================================================== -->

<section class="bookings-section">

    <div class="section-header">

        <div>

            <div class="section-title-row">

                <span class="section-icon">

                    <i class="fa-solid fa-photo-film"></i>

                </span>

                <div>

                    <span class="section-label">
                        EVENT LIBRARY
                    </span>

                    <h2>
                        Uploaded Events
                    </h2>

                </div>

            </div>

        </div>

        <div class="booking-count">

            <i class="fa-solid fa-photo-film"></i>

            <?= $totalEvents ?>

            event<?= $totalEvents === 1 ? '' : 's' ?>

        </div>

    </div>

    <?php if (empty($events)): ?>

        <div class="empty-state">

            <div class="empty-icon">

                <i class="fa-regular fa-images"></i>

            </div>

            <h3>
                No events yet
            </h3>

            <p>
                Upload your first event image
                or video above.
            </p>

        </div>

    <?php else: ?>

        <div class="event-admin-grid">

            <?php foreach ($events as $event): ?>

                <div class="event-admin-card">

                    <!-- =================================================
                         PREVIEW
                    ================================================= -->

                    <div class="event-preview">

                        <?php if ($event['type'] === 'video'): ?>

                            <?php if (!empty($event['thumbnail_url'])): ?>

                                <div class="event-video-preview">

                                    <img
                                        src="<?= e($event['thumbnail_url']) ?>"
                                        alt="<?= e($event['title']) ?>"
                                    >

                                    <div class="event-video-icon">

                                        <i class="fa-solid fa-play"></i>

                                    </div>

                                </div>

                            <?php else: ?>

                                <video
                                    src="<?= e($event['file_url']) ?>"
                                    muted
                                    preload="metadata"
                                    controls
                                ></video>

                            <?php endif; ?>

                        <?php else: ?>

                            <img
                                src="<?= e($event['file_url']) ?>"
                                alt="<?= e($event['title']) ?>"
                            >

                        <?php endif; ?>

                        <span class="event-type-badge">

                            <?= e(strtoupper($event['type'])) ?>

                        </span>

                        <?php if ((int) $event['is_visible'] === 1): ?>

                            <span class="event-visibility visible">

                                <i class="fa-solid fa-eye"></i>

                                Visible

                            </span>

                        <?php else: ?>

                            <span class="event-visibility hidden">

                                <i class="fa-solid fa-eye-slash"></i>

                                Hidden

                            </span>

                        <?php endif; ?>

                    </div>

                    <!-- =================================================
                         CONTENT
                    ================================================= -->

                    <div class="event-admin-content">

                        <h3>
                            <?= e($event['title']) ?>
                        </h3>

                        <div class="event-admin-date">

                            <i class="fa-regular fa-clock"></i>

                            <?= e($event['created_at']) ?>

                        </div>

                        <div class="event-admin-actions">

                            <!-- VISIBILITY -->

                            <form
                                method="POST"
                                style="flex:1;"
                            >

                                <input
                                    type="hidden"
                                    name="event_id"
                                    value="<?= (int) $event['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="toggle_visibility"
                                    class="event-action-button"
                                >

                                    <?php if ((int) $event['is_visible'] === 1): ?>

                                        <i class="fa-solid fa-eye-slash"></i>

                                        Hide

                                    <?php else: ?>

                                        <i class="fa-solid fa-eye"></i>

                                        Show

                                    <?php endif; ?>

                                </button>

                            </form>

                            <!-- DELETE -->

                            <form
                                method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this event?');"
                            >

                                <input
                                    type="hidden"
                                    name="event_id"
                                    value="<?= (int) $event['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="delete_event"
                                    class="event-action-button delete"
                                    title="Delete event"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>

<!-- =====================================================
     FOOTER
===================================================== -->

<footer class="admin-footer">

    <span>

        © <?= date('Y') ?>

        ABAA Entertainment

    </span>

    <span>
        Event Management
    </span>

</footer>

</main> </div> <script>
function toggleThumbnail()
{
const type =
document.getElementById('type').value;

const thumbnailGroup =
    document.getElementById('thumbnailGroup');

const thumbnail =
    document.getElementById('thumbnail');

if (type === 'video') {

    thumbnailGroup.style.display = 'flex';

} else {

    thumbnailGroup.style.display = 'none';

    thumbnail.value = '';

}

}

</script> </body> </html>
