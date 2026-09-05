<?php
session_start();

include(__DIR__ . '/conn.php');

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

/*
|--------------------------------------------------------------------------

REDIRECT IF NOT LOGGED IN
*/

if (!$admin) {
header('Location: /admin');
exit;
}

/*
|--------------------------------------------------------------------------

HELPER
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

UPLOAD FILE TO VERCEL BLOB

|
| IMPORTANT:
|
| This version does NOT use:
|
| file_get_contents($tmpFile)
|
| Therefore the entire video is not loaded into PHP memory.
|
| The temporary uploaded file is streamed directly to cURL.
|
|--------------------------------------------------------------------------
*/

function uploadToVercelBlob(
$tmpFile,
$fileName,
$mimeType,
$fileSize,
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

$fileHandle = fopen($tmpFile, 'rb');

if ($fileHandle === false) {
    return [
        'success' => false,
        'error' => 'Unable to open uploaded file.'
    ];
}

/*
|--------------------------------------------------------------------------
| VERCEL BLOB URL
|--------------------------------------------------------------------------
*/

$url =
    'https://blob.vercel-storage.com/' .
    str_replace(
        '%2F',
        '/',
        rawurlencode($fileName)
    );

$ch = curl_init($url);

if ($ch === false) {
    fclose($fileHandle);

    return [
        'success' => false,
        'error' => 'Unable to initialize Blob upload.'
    ];
}

curl_setopt_array(
    $ch,
    [
        CURLOPT_CUSTOMREQUEST => 'PUT',

        /*
        |--------------------------------------------------------------------------
        | STREAM FILE
        |--------------------------------------------------------------------------
        */

        CURLOPT_UPLOAD => true,

        CURLOPT_INFILE => $fileHandle,

        CURLOPT_INFILESIZE => $fileSize,

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $blobToken,
            'Content-Type: ' . $mimeType,
            'x-api-version: 7'
        ],

        /*
        |--------------------------------------------------------------------------
        | LARGE FILE SETTINGS
        |--------------------------------------------------------------------------
        */

        CURLOPT_TIMEOUT => 600,

        CURLOPT_CONNECTTIMEOUT => 30,

        CURLOPT_FOLLOWLOCATION => true,

        CURLOPT_FAILONERROR => false
    ]
);

$response = curl_exec($ch);

$curlError = curl_error($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

fclose($fileHandle);

unset($ch);

/*
|--------------------------------------------------------------------------
| CURL ERROR
|--------------------------------------------------------------------------
*/

if ($response === false) {
    return [
        'success' => false,
        'error' =>
            'Blob upload failed: ' .
            ($curlError ?: 'Unknown cURL error.')
    ];
}

/*
|--------------------------------------------------------------------------
| HTTP ERROR
|--------------------------------------------------------------------------
*/

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
        'error' =>
            'Vercel Blob rejected the upload. HTTP ' .
            $httpCode
    ];
}

/*
|--------------------------------------------------------------------------
| DECODE RESPONSE
|--------------------------------------------------------------------------
*/

$data = json_decode(
    $response,
    true
);

if (!is_array($data)) {
    return [
        'success' => false,
        'error' =>
            'Invalid response from Vercel Blob.'
    ];
}

$blobUrl =
    $data['url'] ??
    $data['downloadUrl'] ??
    null;

if (!$blobUrl) {
    return [
        'success' => false,
        'error' =>
            'Vercel Blob did not return a file URL.'
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

if ($ch === false) {
    return false;
}

curl_setopt_array(
    $ch,
    [
        CURLOPT_CUSTOMREQUEST => 'DELETE',

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $blobToken,
            'x-api-version: 7'
        ],

        CURLOPT_TIMEOUT => 60,

        CURLOPT_CONNECTTIMEOUT => 20
    ]
);

$response = curl_exec($ch);

$httpCode = curl_getinfo(
    $ch,
    CURLINFO_HTTP_CODE
);

unset($ch);

return (
    $response !== false &&
    $httpCode >= 200 &&
    $httpCode < 300
);

}

/*
|--------------------------------------------------------------------------

FLASH MESSAGES
*/

$statusMessage =
$_SESSION['event_status_message'] ?? '';

$statusError =
$_SESSION['event_status_error'] ?? '';

unset(
$_SESSION['event_status_message'],
$_SESSION['event_status_error']
);

/*
|--------------------------------------------------------------------------

DELETE EVENT
*/

if (
$_SERVER['REQUEST_METHOD'] === 'POST' &&
isset($_POST['delete_event'])
) {
$eventId =
(int) (
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

        $event =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if ($event) {

            /*
            |--------------------------------------------------------------------------
            | DELETE MAIN FILE
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
            | DELETE THUMBNAIL
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

} else {

    $statusError =
        'Invalid event.';
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
$eventId =
(int) (
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

} else {

    $statusError =
        'Invalid event.';
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

$title =
    trim(
        $_POST['title'] ?? ''
    );

$type =
    trim(
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

/*
|--------------------------------------------------------------------------
| VALIDATE TITLE
|--------------------------------------------------------------------------
*/

} elseif ($title === '') {

    $statusError =
        'Please enter an event title.';

/*
|--------------------------------------------------------------------------
| VALIDATE TYPE
|--------------------------------------------------------------------------
*/

} elseif (
    !in_array(
        $type,
        ['image', 'video'],
        true
    )
) {

    $statusError =
        'Invalid event type.';

/*
|--------------------------------------------------------------------------
| CHECK MAIN FILE
|--------------------------------------------------------------------------
*/

} elseif (
    !isset($_FILES['event_file']) ||
    $_FILES['event_file']['error'] !== UPLOAD_ERR_OK
) {

    $statusError =
        'Please select an event file.';

} else {

    $file =
        $_FILES['event_file'];

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
        500 * 1024 * 1024;

    if ($fileSize <= 0) {

        $statusError =
            'The uploaded file is empty.';

    } elseif ($fileSize > $maxFileSize) {

        $statusError =
            'File is too large. Maximum size is 500MB.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | EXTENSION
        |--------------------------------------------------------------------------
        */

        $extension =
            strtolower(
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
                'Invalid file extension for ' .
                e($type) .
                '.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | VALIDATE MIME
            |--------------------------------------------------------------------------
            */

            $mimeType =
                mime_content_type(
                    $tmpName
                );

            if (!$mimeType) {

                $mimeType =
                    'application/octet-stream';
            }

            $allowedImageMimes = [
                'image/jpeg',
                'image/png',
                'image/webp'
            ];

            $allowedVideoMimes = [
                'video/mp4',
                'video/webm',
                'video/quicktime'
            ];

            if (
                $type === 'image' &&
                !in_array(
                    $mimeType,
                    $allowedImageMimes,
                    true
                )
            ) {

                $statusError =
                    'The uploaded file is not a valid image.';

            } elseif (
                $type === 'video' &&
                !in_array(
                    $mimeType,
                    $allowedVideoMimes,
                    true
                )
            ) {

                $statusError =
                    'The uploaded file is not a valid video.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | SAFE TITLE
                |--------------------------------------------------------------------------
                */

                $safeTitle =
                    preg_replace(
                        '/[^a-zA-Z0-9_-]+/',
                        '-',
                        $title
                    );

                $safeTitle =
                    trim(
                        $safeTitle,
                        '-'
                    );

                if ($safeTitle === '') {

                    $safeTitle =
                        'event';
                }

                /*
                |--------------------------------------------------------------------------
                | UNIQUE ID
                |--------------------------------------------------------------------------
                */

                $uniqueId =
                    bin2hex(
                        random_bytes(12)
                    );

                /*
                |--------------------------------------------------------------------------
                | MAIN BLOB FILE NAME
                |--------------------------------------------------------------------------
                */

                $newFileName =
                    'events/' .
                    $safeTitle .
                    '-' .
                    $uniqueId .
                    '.' .
                    $extension;

                /*
                |--------------------------------------------------------------------------
                | UPLOAD MAIN FILE
                |--------------------------------------------------------------------------
                */

                $blobResult =
                    uploadToVercelBlob(
                        $tmpName,
                        $newFileName,
                        $mimeType,
                        $fileSize,
                        $blobToken
                    );

                if (!$blobResult['success']) {

                    $statusError =
                        $blobResult['error'];

                } else {

                    $fileUrl =
                        $blobResult['url'];

                    $thumbnailUrl =
                        null;

                    /*
                    |--------------------------------------------------------------------------
                    | VIDEO THUMBNAIL
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $type === 'video' &&
                        isset($_FILES['thumbnail']) &&
                        $_FILES['thumbnail']['error'] ===
                            UPLOAD_ERR_OK
                    ) {

                        $thumbnail =
                            $_FILES['thumbnail'];

                        $thumbnailTmp =
                            $thumbnail['tmp_name'];

                        $thumbnailSize =
                            (int) $thumbnail['size'];

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
                            $thumbnailSize > 0 &&
                            $thumbnailSize <=
                                10 * 1024 * 1024 &&
                            in_array(
                                $thumbnailExtension,
                                $allowedThumbnailExtensions,
                                true
                            )
                        ) {

                            $thumbnailMime =
                                mime_content_type(
                                    $thumbnailTmp
                                );

                            $allowedThumbnailMimes = [
                                'image/jpeg',
                                'image/png',
                                'image/webp'
                            ];

                            if (
                                in_array(
                                    $thumbnailMime,
                                    $allowedThumbnailMimes,
                                    true
                                )
                            ) {

                                $thumbnailFileName =
                                    'events/' .
                                    $safeTitle .
                                    '-thumb-' .
                                    $uniqueId .
                                    '.' .
                                    $thumbnailExtension;

                                $thumbnailResult =
                                    uploadToVercelBlob(
                                        $thumbnailTmp,
                                        $thumbnailFileName,
                                        $thumbnailMime,
                                        $thumbnailSize,
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
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | INSERT DATABASE
                    |--------------------------------------------------------------------------
                    */

                    try {

                        $stmt =
                            $pdo->prepare(
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
                        |
                        | Remove the Blob files so we don't leave
                        | orphaned files in Vercel Blob.
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

}

/*
|--------------------------------------------------------------------------

POST → REDIRECT → GET
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

if ($statusMessage !== '') {

    $_SESSION['event_status_message'] =
        $statusMessage;
}

if ($statusError !== '') {

    $_SESSION['event_status_error'] =
        $statusError;
}

header(
    'Location: /admin/events'
);

exit;

}

/*
|--------------------------------------------------------------------------

LOAD EVENTS
*/

$events = [];

try {

$stmt =
    $pdo->query(
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

$visibleEvents =
0;

foreach ($events as $event) {

if (
    (int) $event['is_visible'] === 1
) {
    $visibleEvents++;
}

}

?>

<!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

<meta
name="theme-color"
content="#ff5a1f"

<title> Events - ABAA Admin </title> <link rel="stylesheet" href="/admin.css" > <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" > <style>
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

.event-upload-button:disabled {
opacity: .65;
cursor: not-allowed;
transform: none;
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

.event-preview video {
background: #111;
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
z-index: 2;
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
z-index: 2;
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

.upload-progress {
display: none;
margin-top: 12px;
color: #6b7280;
font-size: 12px;
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

</style> </head> <body> <div class="admin-layout"> <!-- ===================================================== SIDEBAR ===================================================== --> <aside class="sidebar"> <div class="sidebar-brand">
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

</div> <nav class="sidebar-nav">
<a href="/admin">

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

<a
    href="/admin/events"
    class="active"
>

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

</nav> <div class="sidebar-info">
<div class="sidebar-info-icon">

    <i class="fa-solid fa-bolt"></i>

</div>

<div>

    <strong>
        ABAA Entertainment
    </strong>

    <span>
        Event management system
    </span>

</div>

</div> <div class="sidebar-bottom">
<div class="admin-user">

    <div class="admin-avatar">

        <i class="fa-solid fa-user"></i>

    </div>

    <div>

        <strong>
            <?= e($admin['username']) ?>
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

</div> </aside> <!-- ===================================================== MAIN ===================================================== --> <main class="admin-main"> <div class="top-panel">
<div class="top-panel-left">

    <div class="top-panel-icon">

        <i class="fa-solid fa-photo-film"></i>

    </div>

    <div>

        <span>
            ABAA ENTERTAINMENT
        </span>

        <strong>
            Event Management
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

        <?= e($admin['username']) ?>

    </div>

</div>

</div> <header class="admin-header">
<div>

    <span class="dashboard-label">
        EVENTS
    </span>

    <h1>
        Event Management
    </h1>

    <p>
        Upload and manage your event images
        and videos.
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

</header> <!-- ===================================================== NOTIFICATIONS ===================================================== --> <?php if ($statusMessage): ?> <div class="admin-notification success">
<i class="fa-solid fa-circle-check"></i>

<?= e($statusMessage) ?>

</div> <?php endif; ?> <?php if ($statusError): ?> <div class="admin-notification error">
<i class="fa-solid fa-circle-exclamation"></i>

<?= e($statusError) ?>

</div> <?php endif; ?> <!-- ===================================================== STATISTICS ===================================================== --> <section class="stats-grid">
<div class="stat-card">

    <div class="stat-icon orange">

        <i class="fa-solid fa-photo-film"></i>

    </div>

    <div class="stat-content">

        <span>
            Total Events
        </span>

        <strong>
            <?= $totalEvents ?>
        </strong>

        <small>
            Uploaded events
        </small>

    </div>

</div>

<div class="stat-card">

    <div class="stat-icon dark-orange">

        <i class="fa-solid fa-eye"></i>

    </div>

    <div class="stat-content">

        <span>
            Visible
        </span>

        <strong>
            <?= $visibleEvents ?>
        </strong>

        <small>
            Showing on website
        </small>

    </div>

</div>

<div class="stat-card">

    <div class="stat-icon dark">

        <i class="fa-solid fa-cloud-arrow-up"></i>

    </div>

    <div class="stat-content">

        <span>
            Uploads
        </span>

        <strong>
            <?= $totalEvents ?>
        </strong>

        <small>
            Images & videos
        </small>

    </div>

</div>

</section> <!-- ===================================================== UPLOAD EVENT ===================================================== --> <section class="event-upload-card">
<div class="event-upload-header">

    <div class="event-upload-icon">

        <i class="fa-solid fa-cloud-arrow-up"></i>

    </div>

    <div>

        <span>
            EVENT CONTENT
        </span>

        <h2>
            Upload New Event
        </h2>

    </div>

</div>

<form
    method="POST"
    enctype="multipart/form-data"
    action="/admin/events"
    id="eventUploadForm"
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

                Optional image displayed
                before the video plays.

            </span>

        </div>

    </div>

    <button
        type="submit"
        name="upload_event"
        class="event-upload-button"
        id="uploadButton"
    >

        <i class="fa-solid fa-cloud-arrow-up"></i>

        <span id="uploadButtonText">
            Upload Event
        </span>

    </button>

    <div
        class="upload-progress"
        id="uploadProgress"
    >
        Uploading... Please do not close this page.
    </div>

</form>

</section> <!-- ===================================================== EVENTS ===================================================== --> <section class="bookings-section">
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

                <video
                    src="<?= e($event['file_url']) ?>"
                    <?php if (!empty($event['thumbnail_url'])): ?>
                    poster="<?= e($event['thumbnail_url']) ?>"
                    <?php endif; ?>
                    controls
                    preload="metadata"
                    playsinline
                ></video>

            <?php else: ?>

                <img
                    src="<?= e($event['file_url']) ?>"
                    alt="<?= e($event['title']) ?>"
                    loading="lazy"
                >

            <?php endif; ?>

            <span class="event-type-badge">

                <?= e(
                    strtoupper(
                        $event['type']
                    )
                ) ?>

            </span>

            <?php if (
                (int) $event['is_visible'] === 1
            ): ?>

                <span
                    class="event-visibility visible"
                >

                    <i class="fa-solid fa-eye"></i>

                    Visible

                </span>

            <?php else: ?>

                <span
                    class="event-visibility hidden"
                >

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

                <?= e(
                    $event['title']
                ) ?>

            </h3>

            <div class="event-admin-date">

                <i class="fa-regular fa-clock"></i>

                <?= e(
                    $event['created_at']
                ) ?>

            </div>

            <div class="event-admin-actions">

                <!-- VISIBILITY -->

                <form
                    method="POST"
                    action="/admin/events"
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

                        <?php if (
                            (int) $event['is_visible'] === 1
                        ): ?>

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
                    action="/admin/events"
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

</section> <!-- ===================================================== FOOTER ===================================================== --> <footer class="admin-footer">
<span>

    © <?= date('Y') ?>

    ABAA Entertainment

</span>

<span>
    Event Management
</span>

</footer> </main> </div> <script>
function toggleThumbnail()
{
const type =
document.getElementById('type').value;

const thumbnailGroup =
    document.getElementById(
        'thumbnailGroup'
    );

const thumbnail =
    document.getElementById(
        'thumbnail'
    );

if (type === 'video') {

    thumbnailGroup.style.display =
        'flex';

} else {

    thumbnailGroup.style.display =
        'none';

    thumbnail.value = '';
}

}

/*
|--------------------------------------------------------------------------

PREVENT DOUBLE SUBMISSION
*/

document
.getElementById('eventUploadForm')
.addEventListener(
'submit',
function()
{
const button =
document.getElementById(
'uploadButton'
);

        const buttonText =
            document.getElementById(
                'uploadButtonText'
            );

        const progress =
            document.getElementById(
                'uploadProgress'
            );

        button.disabled = true;

        buttonText.textContent =
            'Uploading...';

        progress.style.display =
            'block';
    }
);

/*
|--------------------------------------------------------------------------

INITIAL THUMBNAIL STATE
*/

toggleThumbnail();

</script> </body> </html>
