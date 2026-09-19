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
| VERCEL BLOB
|--------------------------------------------------------------------------
*/

$blobToken = getenv('BLOB_READ_WRITE_TOKEN');

/*
|--------------------------------------------------------------------------
| BASE64 URL DECODE
|--------------------------------------------------------------------------
*/

function base64UrlDecode($data)
{
    $remainder = strlen($data) % 4;

    if ($remainder > 0) {
        $data .= str_repeat(
            '=',
            4 - $remainder
        );
    }

    return base64_decode(
        strtr($data, '-_', '+/'),
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

    $payloadJson = base64UrlDecode(
        $payloadEncoded
    );

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

    if (
        (int) $payload['exp'] < time()
    ) {
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

    header(
        'Location: /admin'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| HELPER
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

/*
|--------------------------------------------------------------------------
| EVENT DETAILS COLUMNS
|--------------------------------------------------------------------------
|
| These are kept here so existing installations automatically receive
| the required columns if they do not already exist.
|
*/

try {

    $pdo->exec(
        "ALTER TABLE events
         ADD COLUMN IF NOT EXISTS place VARCHAR(255) NULL AFTER title"
    );

    $pdo->exec(
        "ALTER TABLE events
         ADD COLUMN IF NOT EXISTS event_date DATE NULL AFTER place"
    );

    $pdo->exec(
        "ALTER TABLE events
         ADD COLUMN IF NOT EXISTS service_provided VARCHAR(255) NULL AFTER event_date"
    );

} catch (PDOException $e) {

    error_log(
        'Event details schema update error: ' .
        $e->getMessage()
    );
}

/*
|--------------------------------------------------------------------------
| EVENT PHOTOS TABLE
|--------------------------------------------------------------------------
|
| One event can have multiple additional photos.
|
*/

try {

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS event_photos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id INT UNSIGNED NOT NULL,
            image_url TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_event_id (event_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

} catch (PDOException $e) {

    error_log(
        'Event photos table error: ' .
        $e->getMessage()
    );
}

/*
|--------------------------------------------------------------------------
| BLOB UPLOAD FUNCTION
|--------------------------------------------------------------------------
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
            'error' =>
                'BLOB_READ_WRITE_TOKEN is not configured.'
        ];
    }

    if (!is_file($tmpFile)) {

        return [
            'success' => false,
            'error' =>
                'Temporary upload file was not found.'
        ];
    }

    $fileContents = file_get_contents(
        $tmpFile
    );

    if ($fileContents === false) {

        return [
            'success' => false,
            'error' =>
                'Unable to read uploaded file.'
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

    if ($ch === false) {

        return [
            'success' => false,
            'error' =>
                'Unable to initialize Blob upload.'
        ];
    }

    curl_setopt_array(
        $ch,
        [
            CURLOPT_CUSTOMREQUEST =>
                'PUT',

            CURLOPT_POSTFIELDS =>
                $fileContents,

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' .
                    $blobToken,

                'Content-Type: ' .
                    $mimeType,

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

    /*
    |--------------------------------------------------------------------------
    | PHP 8.5
    |--------------------------------------------------------------------------
    |
    | curl_close() is deprecated in PHP 8.5.
    |
    */

    unset($ch);

    if ($response === false) {

        return [
            'success' => false,
            'error' =>
                'Blob upload failed: ' .
                $curlError
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
            'error' =>
                'Vercel Blob rejected the upload.'
        ];
    }

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
| DELETE FROM VERCEL BLOB
|--------------------------------------------------------------------------
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
            CURLOPT_CUSTOMREQUEST =>
                'DELETE',

            CURLOPT_RETURNTRANSFER =>
                true,

            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' .
                    $blobToken,

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

    /*
    |--------------------------------------------------------------------------
    | PHP 8.5
    |--------------------------------------------------------------------------
    */

    unset($ch);

    return (
        $response !== false &&
        $httpCode >= 200 &&
        $httpCode < 300
    );
}

/*
|--------------------------------------------------------------------------
| FLASH MESSAGES
|--------------------------------------------------------------------------
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
| DELETE EVENT
|--------------------------------------------------------------------------
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

            /*
            |--------------------------------------------------------------------------
            | GET EVENT MEDIA
            |--------------------------------------------------------------------------
            */

            $stmt =
                $pdo->prepare(
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
                | LOAD ADDITIONAL EVENT PHOTOS
                |--------------------------------------------------------------------------
                */

                $photoStmt =
                    $pdo->prepare(
                        "SELECT
                            id,
                            image_url
                         FROM event_photos
                         WHERE event_id = :event_id"
                    );

                $photoStmt->execute([
                    ':event_id' => $eventId
                ]);

                $eventPhotos =
                    $photoStmt->fetchAll(
                        PDO::FETCH_ASSOC
                    );

                /*
                |--------------------------------------------------------------------------
                | DELETE MAIN BLOB
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $event['file_url']
                    )
                ) {

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

                if (
                    !empty(
                        $event['thumbnail_url']
                    )
                ) {

                    deleteFromVercelBlob(
                        $event['thumbnail_url'],
                        $blobToken
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | DELETE ADDITIONAL PHOTO BLOBS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $eventPhotos as $photo
                ) {

                    if (
                        !empty(
                            $photo['image_url']
                        )
                    ) {

                        deleteFromVercelBlob(
                            $photo['image_url'],
                            $blobToken
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | DELETE PHOTO RECORDS
                |--------------------------------------------------------------------------
                */

                $photoDeleteStmt =
                    $pdo->prepare(
                        "DELETE FROM event_photos
                         WHERE event_id = :event_id"
                    );

                $photoDeleteStmt->execute([
                    ':event_id' => $eventId
                ]);

                /*
                |--------------------------------------------------------------------------
                | DELETE EVENT
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $pdo->prepare(
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
| DELETE ADDITIONAL PHOTO
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_event_photo'])
) {

    $photoId =
        (int) (
            $_POST['photo_id'] ?? 0
        );

    $eventId =
        (int) (
            $_POST['event_id'] ?? 0
        );

    if (
        $photoId > 0 &&
        $eventId > 0
    ) {

        try {

            /*
            |--------------------------------------------------------------------------
            | FIND PHOTO
            |--------------------------------------------------------------------------
            */

            $stmt =
                $pdo->prepare(
                    "SELECT
                        id,
                        image_url
                     FROM event_photos
                     WHERE id = :id
                     AND event_id = :event_id
                     LIMIT 1"
                );

            $stmt->execute([
                ':id' =>
                    $photoId,

                ':event_id' =>
                    $eventId
            ]);

            $photo =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (!$photo) {

                $statusError =
                    'Additional photo not found.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | DELETE BLOB
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $photo['image_url']
                    )
                ) {

                    deleteFromVercelBlob(
                        $photo['image_url'],
                        $blobToken
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | DELETE DATABASE RECORD
                |--------------------------------------------------------------------------
                */

                $deleteStmt =
                    $pdo->prepare(
                        "DELETE FROM event_photos
                         WHERE id = :id
                         AND event_id = :event_id"
                    );

                $deleteStmt->execute([
                    ':id' =>
                        $photoId,

                    ':event_id' =>
                        $eventId
                ]);

                $statusMessage =
                    'Additional photo removed successfully.';
            }

        } catch (PDOException $e) {

            error_log(
                'Additional photo delete error: ' .
                $e->getMessage()
            );

            $statusError =
                'Unable to remove additional photo.';
        }

    } else {

        $statusError =
            'Invalid photo.';
    }
}

/*
|--------------------------------------------------------------------------
| TOGGLE VISIBILITY
|--------------------------------------------------------------------------
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

            $stmt =
                $pdo->prepare(
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
| EDIT EVENT DETAILS + ADD MORE PHOTOS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_event'])
) {

    $eventId =
        (int) (
            $_POST['event_id'] ?? 0
        );

    $editTitle =
        trim(
            $_POST['edit_title'] ?? ''
        );

    $editPlace =
        trim(
            $_POST['edit_place'] ?? ''
        );

    $editDate =
        trim(
            $_POST['edit_event_date'] ?? ''
        );

    $editServiceProvided =
        trim(
            $_POST['edit_service_provided'] ?? ''
        );

    /*
    |--------------------------------------------------------------------------
    | VALIDATE EVENT
    |--------------------------------------------------------------------------
    */

    if ($eventId <= 0) {

        $statusError =
            'Invalid event.';

    } elseif ($editTitle === '') {

        $statusError =
            'Please enter an event title.';

    } elseif (
        $editDate !== '' &&
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $editDate
        )
    ) {

        $statusError =
            'Please enter a valid event date.';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | UPDATE EVENT DETAILS
            |--------------------------------------------------------------------------
            */

            $stmt =
                $pdo->prepare(
                    "UPDATE events
                     SET
                        title = :title,
                        place = :place,
                        event_date = :event_date,
                        service_provided = :service_provided
                     WHERE id = :id"
                );

            $stmt->execute([
                ':title' =>
                    $editTitle,

                ':place' =>
                    (
                        $editPlace !== ''
                        ? $editPlace
                        : null
                    ),

                ':event_date' =>
                    (
                        $editDate !== ''
                        ? $editDate
                        : null
                    ),

                ':service_provided' =>
                    (
                        $editServiceProvided !== ''
                        ? $editServiceProvided
                        : null
                    ),

                ':id' =>
                    $eventId
            ]);

            /*
            |--------------------------------------------------------------------------
            | ADD MORE PHOTOS
            |--------------------------------------------------------------------------
            */

            $additionalPhotoCount = 0;

            if (
                isset(
                    $_FILES['edit_event_photos']
                ) &&
                is_array(
                    $_FILES['edit_event_photos']['name'] ?? null
                )
            ) {

                $photoCount =
                    count(
                        $_FILES['edit_event_photos']['name']
                    );

                $allowedPhotoExtensions = [
                    'jpg',
                    'jpeg',
                    'png',
                    'webp'
                ];

                /*
                |--------------------------------------------------------------------------
                | CHECK EVENT EXISTS
                |--------------------------------------------------------------------------
                */

                $eventCheckStmt =
                    $pdo->prepare(
                        "SELECT id
                         FROM events
                         WHERE id = :id
                         LIMIT 1"
                    );

                $eventCheckStmt->execute([
                    ':id' => $eventId
                ]);

                $existingEvent =
                    $eventCheckStmt->fetch(
                        PDO::FETCH_ASSOC
                    );

                if ($existingEvent) {

                    /*
                    |--------------------------------------------------------------------------
                    | UPLOAD EACH PHOTO
                    |--------------------------------------------------------------------------
                    */

                    for (
                        $i = 0;
                        $i < $photoCount;
                        $i++
                    ) {

                        $uploadError =
                            $_FILES['edit_event_photos']['error'][$i]
                            ??
                            UPLOAD_ERR_NO_FILE;

                        if (
                            $uploadError !==
                            UPLOAD_ERR_OK
                        ) {
                            continue;
                        }

                        $photoTmp =
                            $_FILES['edit_event_photos']['tmp_name'][$i]
                            ??
                            '';

                        $photoOriginal =
                            $_FILES['edit_event_photos']['name'][$i]
                            ??
                            '';

                        $photoSize =
                            (int) (
                                $_FILES['edit_event_photos']['size'][$i]
                                ??
                                0
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDATE SIZE
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !is_file($photoTmp) ||
                            $photoSize >
                            50 * 1024 * 1024
                        ) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDATE EXTENSION
                        |--------------------------------------------------------------------------
                        */

                        $photoExtension =
                            strtolower(
                                pathinfo(
                                    $photoOriginal,
                                    PATHINFO_EXTENSION
                                )
                            );

                        if (
                            !in_array(
                                $photoExtension,
                                $allowedPhotoExtensions,
                                true
                            )
                        ) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | MIME
                        |--------------------------------------------------------------------------
                        */

                        $photoMime =
                            mime_content_type(
                                $photoTmp
                            );

                        if (!$photoMime) {

                            $photoMime =
                                'image/jpeg';
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | SAFE EVENT TITLE
                        |--------------------------------------------------------------------------
                        */

                        $safeTitle =
                            preg_replace(
                                '/[^a-zA-Z0-9_-]+/',
                                '-',
                                $editTitle
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
                        | UNIQUE FILE NAME
                        |--------------------------------------------------------------------------
                        */

                        $photoFileName =
                            'events/' .
                            $safeTitle .
                            '-photo-' .
                            bin2hex(
                                random_bytes(8)
                            ) .
                            '.' .
                            $photoExtension;

                        /*
                        |--------------------------------------------------------------------------
                        | UPLOAD TO VERCEL BLOB
                        |--------------------------------------------------------------------------
                        */

                        $photoResult =
                            uploadToVercelBlob(
                                $photoTmp,
                                $photoFileName,
                                $photoMime,
                                $blobToken
                            );

                        if (
                            !$photoResult['success']
                        ) {
                            continue;
                        }

                        $photoUrl =
                            $photoResult['url'];

                        /*
                        |--------------------------------------------------------------------------
                        | SAVE PHOTO DATABASE RECORD
                        |--------------------------------------------------------------------------
                        */

                        $photoStmt =
                            $pdo->prepare(
                                "INSERT INTO event_photos
                                (
                                    event_id,
                                    image_url,
                                    created_at
                                )
                                VALUES
                                (
                                    :event_id,
                                    :image_url,
                                    NOW()
                                )"
                            );

                        $photoStmt->execute([
                            ':event_id' =>
                                $eventId,

                            ':image_url' =>
                                $photoUrl
                        ]);

                        $additionalPhotoCount++;
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | SUCCESS MESSAGE
            |--------------------------------------------------------------------------
            */

            if (
                $additionalPhotoCount > 0
            ) {

                $statusMessage =
                    'Event details updated with ' .
                    $additionalPhotoCount .
                    ' additional photo' .
                    (
                        $additionalPhotoCount === 1
                        ? ''
                        : 's'
                    ) .
                    '.';

            } else {

                $statusMessage =
                    'Event details updated successfully.';
            }

        } catch (PDOException $e) {

            error_log(
                'Event edit error: ' .
                $e->getMessage()
            );

            $statusError =
                'Unable to update event details.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| UPLOAD NEW EVENT
|--------------------------------------------------------------------------
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

    $place =
        trim(
            $_POST['place'] ?? ''
        );

    $eventDate =
        trim(
            $_POST['event_date'] ?? ''
        );

    $serviceProvided =
        trim(
            $_POST['service_provided'] ?? ''
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
    | VALIDATE DATE
    |--------------------------------------------------------------------------
    */

    } elseif (
        $eventDate !== '' &&
        !preg_match(
            '/^\d{4}-\d{2}-\d{2}$/',
            $eventDate
        )
    ) {

        $statusError =
            'Please enter a valid event date.';

    /*
    |--------------------------------------------------------------------------
    | VALIDATE TYPE
    |--------------------------------------------------------------------------
    */

    } elseif (
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

    /*
    |--------------------------------------------------------------------------
    | CHECK MAIN FILE
    |--------------------------------------------------------------------------
    */

    } elseif (
        !isset(
            $_FILES['event_file']
        ) ||
        $_FILES['event_file']['error'] !==
            UPLOAD_ERR_OK
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

        if (
            $fileSize >
            $maxFileSize
        ) {

            $statusError =
                'File is too large for this PHP upload endpoint. Maximum is 500MB. For large videos, use direct Vercel Blob client uploads.';

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
                    mime_content_type(
                        $tmpName
                    );

                if (!$mimeType) {

                    $mimeType =
                        'application/octet-stream';
                }

                /*
                |--------------------------------------------------------------------------
                | SAFE FILE NAME
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

                $uniqueId =
                    bin2hex(
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
                | UPLOAD MAIN FILE
                |--------------------------------------------------------------------------
                */

                $blobResult =
                    uploadToVercelBlob(
                        $tmpName,
                        $newFileName,
                        $mimeType,
                        $blobToken
                    );

                if (
                    !$blobResult['success']
                ) {

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

                    $thumbnailUrl =
                        null;

                    if (
                        $type === 'video' &&
                        isset(
                            $_FILES['thumbnail']
                        ) &&
                        $_FILES['thumbnail']['error'] ===
                            UPLOAD_ERR_OK
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
                    | INSERT EVENT
                    |--------------------------------------------------------------------------
                    */

                    try {

                        $stmt =
                            $pdo->prepare(
                                "INSERT INTO events
                                (
                                    title,
                                    place,
                                    event_date,
                                    service_provided,
                                    type,
                                    file_url,
                                    thumbnail_url,
                                    is_visible,
                                    created_at
                                )
                                VALUES
                                (
                                    :title,
                                    :place,
                                    :event_date,
                                    :service_provided,
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

                            ':place' =>
                                (
                                    $place !== ''
                                    ? $place
                                    : null
                                ),

                            ':event_date' =>
                                (
                                    $eventDate !== ''
                                    ? $eventDate
                                    : null
                                ),

                            ':service_provided' =>
                                (
                                    $serviceProvided !== ''
                                    ? $serviceProvided
                                    : null
                                ),

                            ':type' =>
                                $type,

                            ':file_url' =>
                                $fileUrl,

                            ':thumbnail_url' =>
                                $thumbnailUrl
                        ]);

                        $eventId =
                            (int) $pdo->lastInsertId();

                        /*
                        |--------------------------------------------------------------------------
                        | UPLOAD ADDITIONAL EVENT PHOTOS
                        |--------------------------------------------------------------------------
                        */

                        $additionalPhotoCount = 0;

                        if (
                            isset(
                                $_FILES['event_photos']
                            ) &&
                            is_array(
                                $_FILES['event_photos']['name'] ?? null
                            )
                        ) {

                            $photoCount =
                                count(
                                    $_FILES['event_photos']['name']
                                );

                            $allowedPhotoExtensions = [
                                'jpg',
                                'jpeg',
                                'png',
                                'webp'
                            ];

                            for (
                                $i = 0;
                                $i < $photoCount;
                                $i++
                            ) {

                                if (
                                    (
                                        $_FILES['event_photos']['error'][$i]
                                        ??
                                        UPLOAD_ERR_NO_FILE
                                    ) !==
                                    UPLOAD_ERR_OK
                                ) {
                                    continue;
                                }

                                $photoTmp =
                                    $_FILES['event_photos']['tmp_name'][$i]
                                    ??
                                    '';

                                $photoOriginal =
                                    $_FILES['event_photos']['name'][$i]
                                    ??
                                    '';

                                $photoSize =
                                    (int) (
                                        $_FILES['event_photos']['size'][$i]
                                        ??
                                        0
                                    );

                                if (
                                    !is_file($photoTmp) ||
                                    $photoSize >
                                    50 * 1024 * 1024
                                ) {
                                    continue;
                                }

                                $photoExtension =
                                    strtolower(
                                        pathinfo(
                                            $photoOriginal,
                                            PATHINFO_EXTENSION
                                        )
                                    );

                                if (
                                    !in_array(
                                        $photoExtension,
                                        $allowedPhotoExtensions,
                                        true
                                    )
                                ) {
                                    continue;
                                }

                                $photoMime =
                                    mime_content_type(
                                        $photoTmp
                                    );

                                if (!$photoMime) {

                                    $photoMime =
                                        'image/jpeg';
                                }

                                $photoFileName =
                                    'events/' .
                                    $safeTitle .
                                    '-photo-' .
                                    bin2hex(
                                        random_bytes(8)
                                    ) .
                                    '.' .
                                    $photoExtension;

                                $photoResult =
                                    uploadToVercelBlob(
                                        $photoTmp,
                                        $photoFileName,
                                        $photoMime,
                                        $blobToken
                                    );

                                if (
                                    !$photoResult['success']
                                ) {
                                    continue;
                                }

                                $photoUrl =
                                    $photoResult['url'];

                                $photoStmt =
                                    $pdo->prepare(
                                        "INSERT INTO event_photos
                                         (
                                            event_id,
                                            image_url,
                                            created_at
                                         )
                                         VALUES
                                         (
                                            :event_id,
                                            :image_url,
                                            NOW()
                                         )"
                                    );

                                $photoStmt->execute([
                                    ':event_id' =>
                                        $eventId,

                                    ':image_url' =>
                                        $photoUrl
                                ]);

                                $additionalPhotoCount++;
                            }
                        }

                        $statusMessage =
                            'Event uploaded successfully' .
                            (
                                $additionalPhotoCount > 0
                                ? ' with ' .
                                    $additionalPhotoCount .
                                    ' additional photo' .
                                    (
                                        $additionalPhotoCount === 1
                                        ? ''
                                        : 's'
                                    ) .
                                    '.'
                                : '.'
                            );

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

                        if (
                            !empty(
                                $thumbnailUrl
                            )
                        ) {

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
| POST → REDIRECT → GET
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
) {

    if (
        $statusMessage !== ''
    ) {

        $_SESSION['event_status_message'] =
            $statusMessage;
    }

    if (
        $statusError !== ''
    ) {

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
| LOAD EVENTS
|--------------------------------------------------------------------------
*/

$events = [];

try {

    $stmt =
        $pdo->query(
            "SELECT
                id,
                title,
                place,
                event_date,
                service_provided,
                type,
                file_url,
                thumbnail_url,
                is_visible,
                created_at,
                (
                    SELECT COUNT(*)
                    FROM event_photos ep
                    WHERE ep.event_id = events.id
                ) AS photo_count
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
| LOAD ADDITIONAL PHOTOS FOR EACH EVENT
|--------------------------------------------------------------------------
*/

foreach (
    $events as &$event
) {

    $event['additional_photos'] = [];

    try {

        $photoStmt =
            $pdo->prepare(
                "SELECT
                    id,
                    image_url,
                    created_at
                 FROM event_photos
                 WHERE event_id = :event_id
                 ORDER BY id ASC"
            );

        $photoStmt->execute([
            ':event_id' =>
                (int) $event['id']
        ]);

        $event['additional_photos'] =
            $photoStmt->fetchAll(
                PDO::FETCH_ASSOC
            );

    } catch (PDOException $e) {

        error_log(
            'Event photos query error: ' .
            $e->getMessage()
        );
    }
}

unset($event);

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalEvents =
    count($events);

$visibleEvents = 0;

foreach (
    $events as $event
) {

    if (
        (int) $event['is_visible'] === 1
    ) {

        $visibleEvents++;
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

<meta
    name="theme-color"
    content="#ff5a1f"
>

<title>
    Events - ABAA Admin
</title>

<link
    rel="stylesheet"
    href="/admin.css"
>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
>

<style>

/* ==================================================
   UPLOAD CARD
================================================== */

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
    box-sizing: border-box;
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


/* ==================================================
   EVENT GRID
================================================== */

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


/* ==================================================
   EVENT CONTENT
================================================== */

.event-admin-content {
    padding: 16px;
}

.event-admin-content h3 {
    font-size: 16px;
    color: var(--dark);
    margin-bottom: 5px;
}

.event-photo-count {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin: 3px 0 4px;
    color: var(--orange);
    font-size: 11px;
    font-weight: 700;
}

.event-admin-date {
    color: #9ca3af;
    font-size: 11px;
    margin-bottom: 15px;
}

.event-admin-details {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin: 8px 0 14px;
    color: #6b7280;
    font-size: 11px;
    line-height: 1.4;
}

.event-admin-details div {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.event-admin-details i {
    width: 14px;
    margin-top: 2px;
    color: var(--orange);
    text-align: center;
    flex-shrink: 0;
}

.event-admin-details strong {
    color: #374151;
    font-weight: 700;
}


/* ==================================================
   EDIT EVENT PANEL
================================================== */

.event-edit-panel {
    margin-top: 14px;
    border: 1px solid #ff5a1f;
    border-radius: 8px;
    background: #111;
    overflow: hidden;
}

.event-edit-panel summary {
    list-style: none;
    cursor: pointer;
    padding: 12px 14px;
    color: #ff5a1f;
    background: #090909;
    font-size: 12px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .7px;
    transition: .2s;
}

.event-edit-panel summary::-webkit-details-marker {
    display: none;
}

.event-edit-panel summary i {
    margin-right: 7px;
}

.event-edit-panel summary:hover {
    background: #171717;
    color: #ff7a4d;
}

.event-edit-panel[open] summary {
    border-bottom: 1px solid #292929;
}

.event-edit-form {
    display: grid;
    gap: 12px;
    padding: 14px;
}

.event-edit-form label {
    display: grid;
    gap: 6px;
    color: #bcbcbc;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .6px;
}

.event-edit-form input {
    width: 100%;
    box-sizing: border-box;
    padding: 10px 11px;
    color: #fff;
    background: #050505;
    border: 1px solid #333;
    border-radius: 5px;
    outline: none;
    transition: .2s;
}

.event-edit-form input:focus {
    border-color: #ff5a1f;
    box-shadow: 0 0 0 2px rgba(255,90,31,.12);
}


/* ==================================================
   EDIT DATE
================================================== */

.event-edit-form input[type="date"] {
    color-scheme: dark;
    color: #fff;
    border-color: #ff5a1f;
    accent-color: #ff5a1f;
}

.event-edit-form input[type="date"]:focus {
    border-color: #ff7a4d;
    box-shadow: 0 0 0 3px rgba(255,90,31,.14);
}

.event-edit-form input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(46%) sepia(96%) saturate(3588%) hue-rotate(347deg) brightness(101%) contrast(101%);
    cursor: pointer;
}


/* ==================================================
   SAVE BUTTON
================================================== */

.event-save-button {
    border: 2px solid #ff5a1f;
    border-radius: 6px;
    padding: 10px 13px;
    color: #fff;
    background: #ff5a1f;
    cursor: pointer;
    font-weight: 800;
    transition: .2s;
}

.event-save-button:hover {
    color: #ff5a1f;
    background: transparent;
}


/* ==================================================
   ADDITIONAL PHOTOS EDITOR
================================================== */

.event-edit-photos-section {
    border-top: 1px solid #292929;
    margin-top: 3px;
    padding-top: 14px;
}

.event-edit-photos-title {
    display: flex;
    align-items: center;
    gap: 7px;
    color: #ff5a1f;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .7px;
    margin-bottom: 10px;
}

.event-existing-photos {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 12px;
}

.event-existing-photo {
    position: relative;
    height: 85px;
    border-radius: 5px;
    overflow: hidden;
    background: #050505;
    border: 1px solid #333;
}

.event-existing-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.event-existing-photo-delete {
    position: absolute;
    top: 5px;
    right: 5px;
    width: 27px;
    height: 27px;
    border: none;
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(220,38,38,.95);
    color: white;
    cursor: pointer;
    transition: .2s;
}

.event-existing-photo-delete:hover {
    background: #ef4444;
    transform: scale(1.05);
}

.event-no-photos {
    padding: 14px;
    border: 1px dashed #333;
    border-radius: 5px;
    color: #777;
    text-align: center;
    font-size: 10px;
    margin-bottom: 12px;
}

.event-add-photos-label {
    display: grid;
    gap: 6px;
    color: #aaa;
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .6px;
}

.event-add-photos-label input[type="file"] {
    padding: 9px;
    color: #aaa;
    background: #050505;
    border: 1px solid #333;
    border-radius: 5px;
    cursor: pointer;
}

.event-add-photos-label input[type="file"]:hover {
    border-color: #ff5a1f;
}

.event-photo-help {
    color: #777;
    font-size: 9px;
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
}


/* ==================================================
   ACTION BUTTONS
================================================== */

.event-admin-actions {
    display: flex;
    gap: 14px;
    margin-top: 14px;
}

.event-admin-actions form {
    flex: 1;
}

.event-action-button {
    width: 100%;
    min-height: 38px;
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
    transition: .2s;
}

.event-action-button:hover {
    border-color: var(--orange);
    color: var(--orange);
}

.event-action-button.delete {
    flex: 0 0 auto;
    width: 42px;
    color: #dc2626;
    border-color: #fecaca;
    background: #fef2f2;
}

.event-action-button.delete:hover {
    background: #dc2626;
    color: white;
}


/* ==================================================
   RESPONSIVE
================================================== */

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

    .event-admin-actions {
        gap: 12px;
    }

    .event-existing-photos {
        grid-template-columns: repeat(3, 1fr);
    }

}

</style>

</head>

<body>

<div class="admin-layout">


<!-- =====================================================
     SIDEBAR
===================================================== -->

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
            Event management system
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

</div>

</aside>


<!-- =====================================================
     MAIN
===================================================== -->

<main class="admin-main">


<div class="top-panel">

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

</div>


<header class="admin-header">

    <div>

        <span class="dashboard-label">
            EVENTS
        </span>

        <h1>
            Event Management
        </h1>

        <p>
            Upload and manage event images, videos,
            places, services, dates, and galleries.
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
    >

        <div class="event-form-grid">


            <!-- TITLE -->

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


            <!-- PLACE -->

            <div class="event-form-group">

                <label for="place">
                    Event Place
                </label>

                <input
                    type="text"
                    id="place"
                    name="place"
                    placeholder="e.g. Casa Ynares, Binangonan"
                    maxlength="255"
                >

                <span class="event-help">
                    Venue or location shown on the event details page.
                </span>

            </div>


            <!-- DATE -->

            <div class="event-form-group">

                <label for="event_date">
                    Event Date
                </label>

                <input
                    type="date"
                    id="event_date"
                    name="event_date"
                >

                <span class="event-help">
                    Date shown on the event details page.
                </span>

            </div>


            <!-- SERVICE -->

            <div class="event-form-group">

                <label for="service_provided">
                    Service Provided
                </label>

                <input
                    type="text"
                    id="service_provided"
                    name="service_provided"
                    placeholder="e.g. Event Photography"
                    maxlength="255"
                >

                <span class="event-help">
                    Service provided by ABAA Entertainment for this event.
                </span>

            </div>


            <!-- TYPE -->

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


            <!-- MAIN FILE -->

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


            <!-- ADDITIONAL PHOTOS -->

            <div class="event-form-group full">

                <label for="event_photos">
                    Additional Event Photos
                </label>

                <input
                    type="file"
                    id="event_photos"
                    name="event_photos[]"
                    accept=".jpg,.jpeg,.png,.webp"
                    multiple
                >

                <span class="event-help">
                    Select multiple photos to create a gallery.
                    JPG, PNG, WEBP · Maximum 50MB each.
                </span>

            </div>


            <!-- THUMBNAIL -->

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
                Upload your first event image or video above.
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


                        <?php if (
                            $event['type'] === 'video'
                        ): ?>


                            <?php if (
                                !empty(
                                    $event['thumbnail_url']
                                )
                            ): ?>

                                <div
                                    class="event-video-preview"
                                >

                                    <img
                                        src="<?= e($event['thumbnail_url']) ?>"
                                        alt="<?= e($event['title']) ?>"
                                    >

                                    <div
                                        class="event-video-icon"
                                    >

                                        <i
                                            class="fa-solid fa-play"
                                        ></i>

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


                        <span
                            class="event-type-badge"
                        >

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

                                <i
                                    class="fa-solid fa-eye"
                                ></i>

                                Visible

                            </span>

                        <?php else: ?>

                            <span
                                class="event-visibility hidden"
                            >

                                <i
                                    class="fa-solid fa-eye-slash"
                                ></i>

                                Hidden

                            </span>

                        <?php endif; ?>


                    </div>


                    <!-- =================================================
                         CONTENT
                    ================================================= -->

                    <div
                        class="event-admin-content"
                    >

                        <h3>

                            <?= e(
                                $event['title']
                            ) ?>

                        </h3>


                        <div class="event-photo-count">

                            <i
                                class="fa-regular fa-images"
                            ></i>

                            <?= (int) (
                                $event['photo_count'] ?? 0
                            ) ?>

                            additional photo<?= (
                                (int) (
                                    $event['photo_count'] ?? 0
                                ) === 1
                                ? ''
                                : 's'
                            ) ?>

                        </div>


                        <div class="event-admin-details">


                            <!-- PLACE -->

                            <div>

                                <i
                                    class="fa-solid fa-location-dot"
                                ></i>

                                <span>

                                    <strong>
                                        Place:
                                    </strong>

                                    <?= e(
                                        !empty(
                                            $event['place']
                                        )
                                        ? $event['place']
                                        : 'Not specified'
                                    ) ?>

                                </span>

                            </div>


                            <!-- SERVICE -->

                            <div>

                                <i
                                    class="fa-solid fa-screwdriver-wrench"
                                ></i>

                                <span>

                                    <strong>
                                        Service:
                                    </strong>

                                    <?= e(
                                        !empty(
                                            $event['service_provided']
                                        )
                                        ? $event['service_provided']
                                        : 'Not specified'
                                    ) ?>

                                </span>

                            </div>


                            <!-- DATE -->

                            <div>

                                <i
                                    class="fa-regular fa-calendar"
                                ></i>

                                <span>

                                    <strong>
                                        Date:
                                    </strong>

                                    <?php if (
                                        !empty(
                                            $event['event_date']
                                        )
                                    ): ?>

                                        <?= e(
                                            date(
                                                'F j, Y',
                                                strtotime(
                                                    $event['event_date']
                                                )
                                            )
                                        ) ?>

                                    <?php else: ?>

                                        Not specified

                                    <?php endif; ?>

                                </span>

                            </div>


                        </div>


                        <div
                            class="event-admin-date"
                        >

                            <i
                                class="fa-regular fa-clock"
                            ></i>

                            Uploaded:

                            <?= e(
                                $event['created_at']
                            ) ?>

                        </div>


                        <!-- =================================================
                             EDIT EVENT
                        ================================================= -->

                        <details
                            class="event-edit-panel"
                        >

                            <summary>

                                <i
                                    class="fa-solid fa-pen-to-square"
                                ></i>

                                Edit Event Details

                            </summary>


                            <form
                                method="POST"
                                action="/admin/events"
                                enctype="multipart/form-data"
                                class="event-edit-form"
                            >

                                <input
                                    type="hidden"
                                    name="event_id"
                                    value="<?= (int) $event['id'] ?>"
                                >


                                <!-- TITLE -->

                                <label>

                                    Event Title

                                    <input
                                        type="text"
                                        name="edit_title"
                                        value="<?= e($event['title']) ?>"
                                        maxlength="255"
                                        required
                                    >

                                </label>


                                <!-- PLACE -->

                                <label>

                                    Event Place

                                    <input
                                        type="text"
                                        name="edit_place"
                                        value="<?= e($event['place'] ?? '') ?>"
                                        maxlength="255"
                                        placeholder="Venue or location"
                                    >

                                </label>


                                <!-- DATE -->

                                <label>

                                    Event Date

                                    <input
                                        type="date"
                                        name="edit_event_date"
                                        value="<?= e($event['event_date'] ?? '') ?>"
                                    >

                                </label>


                                <!-- SERVICE -->

                                <label>

                                    Service Provided

                                    <input
                                        type="text"
                                        name="edit_service_provided"
                                        value="<?= e($event['service_provided'] ?? '') ?>"
                                        maxlength="255"
                                        placeholder="e.g. Event Photography"
                                    >

                                </label>


                                <!-- =================================================
                                     ADDITIONAL PHOTOS
                                ================================================= -->

                                <div class="event-edit-photos-section">

                                    <div
                                        class="event-edit-photos-title"
                                    >

                                        <i
                                            class="fa-regular fa-images"
                                        ></i>

                                        Additional Event Photos

                                    </div>


                                    <?php if (
                                        !empty(
                                            $event['additional_photos']
                                        )
                                    ): ?>


                                        <div
                                            class="event-existing-photos"
                                        >


                                            <?php foreach (
                                                $event['additional_photos']
                                                as $photo
                                            ): ?>


                                                <div
                                                    class="event-existing-photo"
                                                >

                                                    <img
                                                        src="<?= e($photo['image_url']) ?>"
                                                        alt="Event photo"
                                                        loading="lazy"
                                                    >


                                                    <!-- DELETE PHOTO -->

                                                    <form
                                                        method="POST"
                                                        action="/admin/events"
                                                        onsubmit="return confirm('Remove this photo from the event?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="event_id"
                                                            value="<?= (int) $event['id'] ?>"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="photo_id"
                                                            value="<?= (int) $photo['id'] ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            name="delete_event_photo"
                                                            class="event-existing-photo-delete"
                                                            title="Remove photo"
                                                        >

                                                            <i
                                                                class="fa-solid fa-trash"
                                                            ></i>

                                                        </button>

                                                    </form>

                                                </div>


                                            <?php endforeach; ?>


                                        </div>


                                    <?php else: ?>


                                        <div
                                            class="event-no-photos"
                                        >

                                            No additional photos yet.

                                        </div>


                                    <?php endif; ?>


                                    <!-- ADD MORE PHOTOS -->

                                    <label
                                        class="event-add-photos-label"
                                    >

                                        Add More Photos

                                        <input
                                            type="file"
                                            name="edit_event_photos[]"
                                            accept=".jpg,.jpeg,.png,.webp"
                                            multiple
                                        >

                                        <span
                                            class="event-photo-help"
                                        >
                                            Select one or multiple photos.
                                            JPG, PNG, WEBP · Maximum 50MB each.
                                        </span>

                                    </label>


                                </div>


                                <!-- SAVE -->

                                <button
                                    type="submit"
                                    name="update_event"
                                    class="event-save-button"
                                >

                                    <i
                                        class="fa-solid fa-floppy-disk"
                                    ></i>

                                    Save Changes

                                </button>

                            </form>

                        </details>


                        <!-- =================================================
                             ACTIONS
                        ================================================= -->

                        <div
                            class="event-admin-actions"
                        >


                            <!-- VISIBILITY -->

                            <form
                                method="POST"
                                action="/admin/events"
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

                                        <i
                                            class="fa-solid fa-eye-slash"
                                        ></i>

                                        Hide

                                    <?php else: ?>

                                        <i
                                            class="fa-solid fa-eye"
                                        ></i>

                                        Show

                                    <?php endif; ?>

                                </button>

                            </form>


                            <!-- DELETE EVENT -->

                            <form
                                method="POST"
                                action="/admin/events"
                                onsubmit="return confirm('Are you sure you want to delete this entire event and all of its additional photos?');"
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

                                    <i
                                        class="fa-solid fa-trash"
                                    ></i>

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


</main>

</div>


<script>

/*
|--------------------------------------------------------------------------
| VIDEO THUMBNAIL
|--------------------------------------------------------------------------
*/

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
| PREVENT ACCIDENTAL DOUBLE SUBMISSION
|--------------------------------------------------------------------------
*/

document.querySelectorAll(
    'form'
).forEach(
    function(form)
    {

        form.addEventListener(
            'submit',
            function(event)
            {

                const submitButton =
                    event.submitter;

                if (!submitButton) {
                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Do not disable delete/photo buttons immediately because
                | browser confirmation forms need to finish normally.
                |--------------------------------------------------------------------------
                */

                if (
                    submitButton.name ===
                    'delete_event' ||
                    submitButton.name ===
                    'delete_event_photo'
                ) {
                    return;
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent repeated clicks
                |--------------------------------------------------------------------------
                */

                if (
                    submitButton.dataset.submitted ===
                    '1'
                ) {

                    event.preventDefault();

                    return;
                }

                submitButton.dataset.submitted =
                    '1';

                submitButton.style.opacity =
                    '.65';

                submitButton.style.pointerEvents =
                    'none';

            }
        );

    }
);

</script>


</body>

</html>
