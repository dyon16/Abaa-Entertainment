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
        $data .= str_repeat('=', 4 - $remainder);
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


function parseServiceDetails($details)
{
    if (empty($details)) {
        return [];
    }

    $decoded = json_decode(
        $details,
        true
    );

    if (is_array($decoded)) {
        return array_values(
            array_filter(
                array_map(
                    'trim',
                    $decoded
                ),
                static function ($item) {
                    return $item !== '';
                }
            )
        );
    }

    return array_values(
        array_filter(
            array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', $details)
            ),
            static function ($item) {
                return $item !== '';
            }
        )
    );
}

function slugify($value)
{
    $value = strtolower(trim((string) $value));

    $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
    $value = trim($value, '-');

    return $value !== '' ? $value : 'service';
}

/*
|--------------------------------------------------------------------------
| GENERATE UNIQUE SERVICE SLUG
|--------------------------------------------------------------------------
*/

function generateUniqueServiceSlug($pdo, $name, $excludeId = 0)
{
    $baseSlug = slugify($name);
    $slug = $baseSlug;
    $counter = 2;

    while (true) {
        $sql =
            "SELECT id
             FROM services
             WHERE slug = :slug";

        $params = [
            ':slug' => $slug
        ];

        if ((int) $excludeId > 0) {
            $sql .= " AND id != :id";
            $params[':id'] = (int) $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            return $slug;
        }

        $slug = $baseSlug . '-' . $counter;
        $counter++;
    }
}

/*
|--------------------------------------------------------------------------
| VERCEL BLOB UPLOAD
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

    $url =
        'https://blob.vercel-storage.com/' .
        rawurlencode($fileName);

    $ch = curl_init($url);

    if ($ch === false) {
        return [
            'success' => false,
            'error' => 'Unable to initialize Blob upload.'
        ];
    }

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

    // PHP 8.5: curl_close() is deprecated.
    unset($ch);

    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Blob upload failed: ' . $curlError
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
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

    $data = json_decode($response, true);

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
| DELETE VERCEL BLOB
|--------------------------------------------------------------------------
*/

function deleteFromVercelBlob($blobUrl, $blobToken)
{
    if (empty($blobUrl) || empty($blobToken)) {
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
    $_SESSION['service_status_message'] ?? '';

$statusError =
    $_SESSION['service_status_error'] ?? '';

unset(
    $_SESSION['service_status_message'],
    $_SESSION['service_status_error']
);

/*
|--------------------------------------------------------------------------
| LOAD SERVICE FOR EDITING
|--------------------------------------------------------------------------
*/

$editService = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['edit'])
) {
    $editId = (int) $_GET['edit'];

    if ($editId > 0) {
        try {
            $stmt = $pdo->prepare(
                "SELECT
                    id,
                    name,
                    slug,
                    image_url,
                    description,
                    details,
                    is_available,
                    created_at
                 FROM services
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmt->execute([
                ':id' => $editId
            ]);

            $editService = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$editService) {
                $statusError = 'Service not found.';
            }
        } catch (PDOException $e) {
            error_log(
                'Service edit load error: ' .
                $e->getMessage()
            );

            $statusError = 'Unable to load service for editing.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| DELETE SERVICE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['delete_service'])
) {
    $serviceId = (int) ($_POST['service_id'] ?? 0);

    if ($serviceId > 0) {
        try {
            $stmt = $pdo->prepare(
                "SELECT image_url
                 FROM services
                 WHERE id = :id
                 LIMIT 1"
            );

            $stmt->execute([
                ':id' => $serviceId
            ]);

            $service = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($service) {
                if (!empty($service['image_url'])) {
                    deleteFromVercelBlob(
                        $service['image_url'],
                        $blobToken
                    );
                }

                $stmt = $pdo->prepare(
                    "DELETE FROM services
                     WHERE id = :id"
                );

                $stmt->execute([
                    ':id' => $serviceId
                ]);

                $statusMessage = 'Service deleted successfully.';
            } else {
                $statusError = 'Service not found.';
            }
        } catch (PDOException $e) {
            error_log(
                'Service delete error: ' .
                $e->getMessage()
            );

            $statusError = 'Unable to delete service.';
        }
    } else {
        $statusError = 'Invalid service.';
    }
}

/*
|--------------------------------------------------------------------------
| TOGGLE AVAILABILITY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['toggle_availability'])
) {
    $serviceId = (int) ($_POST['service_id'] ?? 0);

    if ($serviceId > 0) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE services
                 SET is_available =
                    CASE
                        WHEN is_available = 1 THEN 0
                        ELSE 1
                    END
                 WHERE id = :id"
            );

            $stmt->execute([
                ':id' => $serviceId
            ]);

            $statusMessage = 'Service availability updated.';
        } catch (PDOException $e) {
            error_log(
                'Service availability error: ' .
                $e->getMessage()
            );

            $statusError = 'Unable to update availability.';
        }
    } else {
        $statusError = 'Invalid service.';
    }
}

/*
|--------------------------------------------------------------------------
| ADD / EDIT SERVICE
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        isset($_POST['create_service']) ||
        isset($_POST['update_service'])
    )
) {
    $isUpdate = isset($_POST['update_service']);

    $serviceId = (int) ($_POST['service_id'] ?? 0);

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $detailInputs = $_POST['details'] ?? [];

    if (!is_array($detailInputs)) {
        $detailInputs = [$detailInputs];
    }

    $detailInputs = array_values(
        array_filter(
            array_map(
                'trim',
                $detailInputs
            ),
            static function ($item) {
                return $item !== '';
            }
        )
    );

    $details = json_encode(
        $detailInputs,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    if ($details === false) {
        $details = '[]';
    }

    $isAvailable = isset($_POST['is_available']) ? 1 : 0;

    if ($name === '') {
        $statusError = 'Please enter a service name.';
    } else {
        try {
            /*
             * Slug is generated automatically from the service name.
             * Duplicate names receive -2, -3, etc.
             */
            $slug = generateUniqueServiceSlug(
                $pdo,
                $name,
                $isUpdate ? $serviceId : 0
            );

                $existingImageUrl = null;

                if ($isUpdate && $serviceId > 0) {
                    $existingStmt = $pdo->prepare(
                        "SELECT image_url
                         FROM services
                         WHERE id = :id
                         LIMIT 1"
                    );

                    $existingStmt->execute([
                        ':id' => $serviceId
                    ]);

                    $existingService =
                        $existingStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$existingService) {
                        $statusError = 'Service not found.';
                    } else {
                        $existingImageUrl =
                            $existingService['image_url'] ?? null;
                    }
                }

                if ($statusError === '') {
                    $imageUrl = $existingImageUrl;

                    /*
                     * Optional image upload.
                     */
                    if (
                        isset($_FILES['image']) &&
                        $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
                    ) {
                        if (
                            $_FILES['image']['error'] !==
                            UPLOAD_ERR_OK
                        ) {
                            $statusError =
                                'Unable to upload the service image.';
                        } else {
                            $image = $_FILES['image'];

                            $maxFileSize =
                                10 * 1024 * 1024;

                            if (
                                (int) $image['size'] >
                                $maxFileSize
                            ) {
                                $statusError =
                                    'Service image is too large. Maximum is 10MB.';
                            } else {
                                $extension = strtolower(
                                    pathinfo(
                                        $image['name'],
                                        PATHINFO_EXTENSION
                                    )
                                );

                                $allowedExtensions = [
                                    'jpg',
                                    'jpeg',
                                    'png',
                                    'webp'
                                ];

                                if (
                                    !in_array(
                                        $extension,
                                        $allowedExtensions,
                                        true
                                    )
                                ) {
                                    $statusError =
                                        'Invalid service image type. Use JPG, PNG, or WEBP.';
                                } else {
                                    $mimeType =
                                        mime_content_type(
                                            $image['tmp_name']
                                        );

                                    if (!$mimeType) {
                                        $mimeType =
                                            'application/octet-stream';
                                    }

                                    $uniqueId =
                                        bin2hex(
                                            random_bytes(12)
                                        );

                                    $fileName =
                                        'services/' .
                                        $slug .
                                        '-' .
                                        $uniqueId .
                                        '.' .
                                        $extension;

                                    $blobResult =
                                        uploadToVercelBlob(
                                            $image['tmp_name'],
                                            $fileName,
                                            $mimeType,
                                            $blobToken
                                        );

                                    if (
                                        !$blobResult['success']
                                    ) {
                                        $statusError =
                                            $blobResult['error'];
                                    } else {
                                        $imageUrl =
                                            $blobResult['url'];
                                    }
                                }
                            }
                        }
                    }

                    if ($statusError === '') {
                        if ($isUpdate) {
                            $stmt = $pdo->prepare(
                                "UPDATE services
                                 SET
                                    name = :name,
                                    slug = :slug,
                                    image_url = :image_url,
                                    description = :description,
                                    details = :details,
                                    is_available = :is_available
                                 WHERE id = :id"
                            );

                            $stmt->execute([
                                ':name' =>
                                    $name,

                                ':slug' =>
                                    $slug,

                                ':image_url' =>
                                    $imageUrl,

                                ':description' =>
                                    $description,

                                ':details' =>
                                    $details,

                                ':is_available' =>
                                    $isAvailable,

                                ':id' =>
                                    $serviceId
                            ]);

                            /*
                             * Remove old image only after the database
                             * successfully points to the new image.
                             */
                            if (
                                !empty($existingImageUrl) &&
                                $imageUrl !== $existingImageUrl
                            ) {
                                deleteFromVercelBlob(
                                    $existingImageUrl,
                                    $blobToken
                                );
                            }

                            $statusMessage =
                                'Service updated successfully.';
                        } else {
                            $stmt = $pdo->prepare(
                                "INSERT INTO services
                                (
                                    name,
                                    slug,
                                    image_url,
                                    description,
                                    details,
                                    is_available,
                                    sort_order,
                                    created_at
                                )
                                VALUES
                                (
                                    :name,
                                    :slug,
                                    :image_url,
                                    :description,
                                    :details,
                                    :is_available,
                                    (
                                        SELECT COALESCE(MAX(s.sort_order), 0) + 1
                                        FROM services s
                                    ),
                                    NOW()
                                )"
                            );

                            $stmt->execute([
                                ':name' =>
                                    $name,

                                ':slug' =>
                                    $slug,

                                ':image_url' =>
                                    $imageUrl,

                                ':description' =>
                                    $description,

                                ':details' =>
                                    $details,

                                ':is_available' =>
                                    $isAvailable
                            ]);

                            $statusMessage =
                                'Service created successfully.';
                        }
                    }
                }
        } catch (PDOException $e) {
            /*
             * If a new image was uploaded but the database operation
             * failed, remove that orphaned Blob.
             */
            if (
                isset($imageUrl) &&
                !empty($imageUrl) &&
                (!$isUpdate || $imageUrl !== $existingImageUrl)
            ) {
                deleteFromVercelBlob(
                    $imageUrl,
                    $blobToken
                );
            }

            error_log(
                'Service save error: ' .
                $e->getMessage()
            );

            $statusError =
                'Unable to save service.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| POST → REDIRECT → GET
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($statusMessage !== '') {
        $_SESSION['service_status_message'] =
            $statusMessage;
    }

    if ($statusError !== '') {
        $_SESSION['service_status_error'] =
            $statusError;
    }

    header('Location: /admin/services');
    exit;
}

/*
|--------------------------------------------------------------------------
| LOAD EDIT SERVICE
|--------------------------------------------------------------------------
*/

$editService = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'GET' &&
    isset($_GET['edit'])
) {
    $editId = (int) $_GET['edit'];

    if ($editId > 0) {
        try {
            $editStmt = $pdo->prepare(
                "SELECT
                    id,
                    name,
                    slug,
                    image_url,
                    description,
                    details,
                    is_available,
                    sort_order,
                    created_at
                 FROM services
                 WHERE id = :id
                 LIMIT 1"
            );

            $editStmt->execute([
                ':id' => $editId
            ]);

            $editService =
                $editStmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if (!$editService) {
                $statusError = 'Service not found.';
            }
        } catch (PDOException $e) {
            error_log(
                'Service edit query error: ' .
                $e->getMessage()
            );

            $statusError = 'Unable to load service for editing.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD SERVICES
|--------------------------------------------------------------------------
*/

$services = [];

try {
    $stmt = $pdo->query(
        "SELECT
            id,
            name,
            slug,
            image_url,
            description,
            details,
            is_available,
            sort_order,
            created_at
         FROM services
         ORDER BY sort_order ASC, id ASC"
    );

    $services =
        $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log(
        'Service query error: ' .
        $e->getMessage()
    );

    $statusError =
        'Unable to load services.';
}

/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalServices = count($services);

$availableServices = 0;

foreach ($services as $service) {
    if ((int) $service['is_available'] === 1) {
        $availableServices++;
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
    Services - ABAA Admin
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

.service-form-card {
    background: white;
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,.03);
}

.service-form-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 22px;
}

.service-form-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--orange);
    background: var(--orange-light);
}

.service-form-header span {
    display: block;
    color: var(--orange);
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.5px;
}

.service-form-header h2 {
    margin-top: 4px;
    font-size: 20px;
    color: var(--dark);
}

.service-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.service-form-group {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.service-form-group.full {
    grid-column: 1 / -1;
}

.service-form-group label {
    font-size: 12px;
    font-weight: 700;
    color: #374151;
}

.service-form-group input,
.service-form-group textarea {
    width: 100%;
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 11px 12px;
    background: #fafafa;
    color: var(--text);
    outline: none;
    font-family: inherit;
    box-sizing: border-box;
}

.service-form-group input {
    height: 45px;
}

.service-form-group textarea {
    min-height: 110px;
    resize: vertical;
    line-height: 1.5;
}

.service-form-group input:focus,
.service-form-group textarea:focus {
    background: white;
    border-color: var(--orange);
    box-shadow: 0 0 0 3px rgba(255,90,31,.08);
}

.service-form-group input[type="file"] {
    width: 100%;
    padding: 9px;
    height: auto;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #fafafa;
    color: var(--text);
    box-sizing: border-box;
    cursor: pointer;
}

.service-help {
    color: #9ca3af;
    font-size: 11px;
}

.service-checkbox {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: 45px;
}

.service-checkbox input {
    width: 17px;
    height: 17px;
}

.service-checkbox label {
    margin: 0;
}

.service-details-editor-header {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 10px;
}

.service-details-editor-header > div {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.add-detail-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 40px;
    padding: 9px 14px;
    border: 1px solid var(--orange);
    border-radius: 8px;
    background: var(--orange);
    color: white;
    font-size: 12px;
    font-weight: 800;
    cursor: pointer;
    transition: .2s ease;
}

.add-detail-button:hover {
    transform: translateY(-1px);
    filter: brightness(1.05);
}

.service-details-editor {
    display: flex;
    flex-direction: column;
    gap: 9px;
}

.service-detail-row {
    display: grid;
    grid-template-columns: 1fr 40px;
    align-items: center;
    gap: 8px;
}

.service-detail-drag {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
}

.service-detail-row input {
    min-width: 0;
}

.remove-detail-button {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fff5f5;
    color: #dc2626;
    cursor: pointer;
    transition: .2s ease;
}

.remove-detail-button:hover {
    background: #fee2e2;
}

.service-details-list {
    list-style: none;
    padding: 0;
    margin: 16px 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.service-details-list li {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 13px;
    line-height: 1.45;
    color: #374151;
}

.service-details-list i {
    color: var(--orange);
    margin-top: 3px;
    flex: 0 0 auto;
}

.service-cancel-button {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    margin-top: 10px;
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: #fff;
    color: #374151;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
}

.service-cancel-button:hover {
    background: #f8fafc;
}

.service-form-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
}

.service-submit-button,
.service-cancel-button {
    border: none;
    border-radius: 9px;
    padding: 12px 18px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    text-decoration: none;
}

.service-submit-button {
    background: var(--orange);
    color: white;
}

.service-submit-button:hover {
    background: var(--orange-dark);
    transform: translateY(-1px);
}

.service-cancel-button {
    background: #f3f4f6;
    color: #374151;
}

.service-edit-card {
    background: #fff7ed;
    border: 1px solid #fed7aa;
}

.service-admin-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    padding: 25px;
}

.service-admin-card {
    border: 1px solid var(--border);
    border-radius: 13px;
    overflow: hidden;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,.04);
}

.service-preview {
    height: 190px;
    background: #111;
    position: relative;
    overflow: hidden;
}

.service-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.service-no-image {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 38px;
}

.service-availability {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 6px 9px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 800;
    color: white;
}

.service-availability.available {
    background: #16a34a;
}

.service-availability.unavailable {
    background: #6b7280;
}

.service-admin-content {
    padding: 16px;
}

.service-admin-content h3 {
    font-size: 16px;
    color: var(--dark);
    margin-bottom: 5px;
}

.service-slug {
    color: #9ca3af;
    font-size: 11px;
    margin-bottom: 10px;
}

.service-description {
    color: #6b7280;
    font-size: 12px;
    line-height: 1.5;
    margin-bottom: 14px;
}

.service-admin-meta {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    color: #9ca3af;
    font-size: 11px;
    margin-bottom: 15px;
}

.service-admin-actions {
    display: flex;
    gap: 8px;
}

.service-action-button {
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
    text-decoration: none;
}

.service-action-button:hover {
    border-color: var(--orange);
    color: var(--orange);
}

.service-action-button.delete {
    flex: 0 0 40px;
    color: #dc2626;
    border-color: #fecaca;
    background: #fef2f2;
}

.service-action-button.delete:hover {
    background: #dc2626;
    color: white;
}

@media (max-width: 1000px) {
    .service-admin-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .service-details-editor-header {
        align-items: stretch;
        flex-direction: column;
    }

    .add-detail-button {
        width: 100%;
    }

    .service-form-grid {
        grid-template-columns: 1fr;
    }

    .service-form-group.full {
        grid-column: auto;
    }

    .service-admin-grid {
        grid-template-columns: 1fr;
        padding: 18px;
    }

    .service-form-card {
        padding: 20px;
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

    <a href="/admin/events">
        <i class="fa-solid fa-photo-film"></i>
        <span>Events</span>
    </a>

    <a
        href="/admin/services"
        class="active"
    >
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
        <span>Service management system</span>
    </div>

</div>

<div class="sidebar-bottom">

    <div class="admin-user">

        <div class="admin-avatar">
            <i class="fa-solid fa-user"></i>
        </div>

        <div>
            <strong><?= e($admin['username']) ?></strong>
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

</aside>

<!-- =====================================================
     MAIN
===================================================== -->

<main class="admin-main">

<div class="top-panel">

    <div class="top-panel-left">

        <div class="top-panel-icon">
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <div>
            <span>ABAA ENTERTAINMENT</span>
            <strong>Service Management</strong>
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
        <span class="dashboard-label">SERVICES</span>

        <h1>Service Management</h1>

        <p>
            Create, edit, organize, and manage
            your available services.
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
            <i class="fa-solid fa-screwdriver-wrench"></i>
        </div>

        <div class="stat-content">
            <span>Total Services</span>
            <strong><?= $totalServices ?></strong>
            <small>Configured services</small>
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon dark-orange">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <div class="stat-content">
            <span>Available</span>
            <strong><?= $availableServices ?></strong>
            <small>Showing as available</small>
        </div>

    </div>

    <div class="stat-card">

        <div class="stat-icon dark">
            <i class="fa-solid fa-list-ol"></i>
        </div>

        <div class="stat-content">
            <span>Services</span>
            <strong><?= $totalServices ?></strong>
            <small>In service library</small>
        </div>

    </div>

</section>

<?php

$detailItems = parseServiceDetails(
    $editService['details'] ?? ''
);

if (empty($detailItems)) {
    $detailItems = [''];
}

?>

<!-- =====================================================
     SERVICE FORM
===================================================== -->

<section
    class="service-form-card"
    id="serviceForm"
>

    <div class="service-form-header">

        <div class="service-form-icon">
            <i class="fa-solid fa-plus"></i>
        </div>

        <div>
            <span>SERVICE CONTENT</span>
            <h2><?= $editService ? 'Edit Service' : 'Add New Service' ?></h2>
        </div>

    </div>

    <form
        method="POST"
        enctype="multipart/form-data"
        action="/admin/services"
    >

        <?php if ($editService): ?>

            <input
                type="hidden"
                name="service_id"
                value="<?= (int) $editService['id'] ?>"
            >

        <?php endif; ?>

        <div class="service-form-grid">

            <div class="service-form-group">

                <label for="name">
                    Service Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="<?= e($editService['name'] ?? '') ?>"
                    placeholder="e.g. Event Coordination"
                    required
                >

                <span class="service-help">
                    URL slug is generated automatically from the service name.
                </span>

            </div>

            <div class="service-form-group">
                <label>
                    Availability
                </label>

                <div class="service-checkbox">
                    <input
                        type="checkbox"
                        id="is_available"
                        name="is_available"
                        value="1"
                        <?= (!$editService || (int) $editService['is_available'] === 1) ? 'checked' : '' ?>
                    >

                    <label for="is_available">
                        Service is available
                    </label>
                </div>

            </div>

            <div class="service-form-group full">

                <label for="image">
                    Service Image
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

                <span class="service-help">
                    JPG, PNG, WEBP · Maximum 10MB
                    <?php if (!empty($editService['image_url'])): ?>
                        Current image will be kept if you do not upload a new one.
                    <?php endif; ?>
                </span>

            </div>

            <div class="service-form-group full">

                <label for="description">
                    Short Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    placeholder="A short description displayed with the service."
                ><?= e($editService['description'] ?? '') ?></textarea>

            </div>

            <div class="service-form-group full">

                <div class="service-details-editor-header">
                    <div>
                        <label>
                            Service Details
                        </label>

                        <span class="service-help">
                            Add as many service details as you need. Click + to add another detail.
                        </span>
                    </div>

                    <button
                        type="button"
                        class="add-detail-button"
                        onclick="addServiceDetail()"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Add Detail
                    </button>
                </div>

                <div
                    id="serviceDetailsList"
                    class="service-details-editor"
                >

                    <?php foreach ($detailItems as $detail): ?>

                        <div class="service-detail-row">

                            <input
                                type="text"
                                name="details[]"
                                value="<?= e($detail) ?>"
                                placeholder="e.g. Professional setup and technical support"
                            >

                            <button
                                type="button"
                                class="remove-detail-button"
                                onclick="removeServiceDetail(this)"
                                title="Remove detail"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        </div>

        <div class="service-form-actions">

            <button
                type="submit"
                name="<?= $editService ? 'update_service' : 'create_service' ?>"
                class="service-submit-button"
            >
                <i class="fa-solid <?= $editService ? 'fa-save' : 'fa-plus' ?>"></i>
                <?= $editService ? 'Save Changes' : 'Add Service' ?>
            </button>

        </div>

        <?php if ($editService): ?>

            <a
                href="/admin/services"
                class="service-cancel-button"
            >
                <i class="fa-solid fa-xmark"></i>
                Cancel Editing
            </a>

        <?php endif; ?>

    </form>

</section>

<!-- =====================================================
     SERVICE LIBRARY
===================================================== -->

<section class="bookings-section">

    <div class="section-header">

        <div>

            <div class="section-title-row">

                <span class="section-icon">
                    <i class="fa-solid fa-screwdriver-wrench"></i>
                </span>

                <div>
                    <span class="section-label">
                        SERVICE LIBRARY
                    </span>

                    <h2>
                        Services
                    </h2>
                </div>

            </div>

        </div>

        <div class="booking-count">
            <i class="fa-solid fa-screwdriver-wrench"></i>
            <?= $totalServices ?>
            service<?= $totalServices === 1 ? '' : 's' ?>
        </div>

    </div>

    <?php if (empty($services)): ?>

        <div class="empty-state">

            <div class="empty-icon">
                <i class="fa-solid fa-screwdriver-wrench"></i>
            </div>

            <h3>No services yet</h3>

            <p>
                Add your first service using the form above.
            </p>

        </div>

    <?php else: ?>

        <div class="service-admin-grid">

            <?php foreach ($services as $service): ?>

                <div class="service-admin-card">

                    <div class="service-preview">

                        <?php if (!empty($service['image_url'])): ?>

                            <img
                                src="<?= e($service['image_url']) ?>"
                                alt="<?= e($service['name']) ?>"
                            >

                        <?php else: ?>

                            <div class="service-no-image">
                                <i class="fa-regular fa-image"></i>
                            </div>

                        <?php endif; ?>

                        <?php if (
                            (int) $service['is_available'] === 1
                        ): ?>

                            <span class="service-availability available">
                                <i class="fa-solid fa-circle-check"></i>
                                Available
                            </span>

                        <?php else: ?>

                            <span class="service-availability unavailable">
                                <i class="fa-solid fa-circle-xmark"></i>
                                Unavailable
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="service-admin-content">

                        <h3>
                            <?= e($service['name']) ?>
                        </h3>

                        <div class="service-slug">
                            /<?= e($service['slug']) ?>
                        </div>

                        <?php if (!empty($service['description'])): ?>

                            <div class="service-description">
                                <?= e($service['description']) ?>
                            </div>

                        <?php endif; ?>

                        <?php
                        $serviceDetails = preg_split(
                            '/\\r\\n|\\r|\\n/',
                            (string) ($service['details'] ?? '')
                        );

                        $serviceDetails = array_values(
                            array_filter(
                                array_map('trim', $serviceDetails),
                                static function ($item) {
                                    return $item !== '';
                                }
                            )
                        );
                        ?>

                        <?php if (!empty($serviceDetails)): ?>

                            <ul class="service-details-list">
                                <?php foreach ($serviceDetails as $detail): ?>

                                    <li>
                                        <i class="fa-solid fa-circle-check"></i>
                                        <span><?= e($detail) ?></span>
                                    </li>

                                <?php endforeach; ?>
                            </ul>

                        <?php endif; ?>

                        <div class="service-admin-meta">

                            <span>
                                <i class="fa-regular fa-clock"></i>
                                <?= e($service['created_at']) ?>
                            </span>

                        </div>

                        <div class="service-admin-actions">

                            <a
                                href="/admin/services?edit=<?= (int) $service['id'] ?>#serviceForm"
                                class="service-action-button"
                            >
                                <i class="fa-solid fa-pen"></i>
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="/admin/services"
                                style="flex:1;"
                            >

                                <input
                                    type="hidden"
                                    name="service_id"
                                    value="<?= (int) $service['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="toggle_availability"
                                    class="service-action-button"
                                    style="width:100%;"
                                >

                                    <?php if (
                                        (int) $service['is_available'] === 1
                                    ): ?>

                                        <i class="fa-solid fa-eye-slash"></i>
                                        Disable

                                    <?php else: ?>

                                        <i class="fa-solid fa-eye"></i>
                                        Enable

                                    <?php endif; ?>

                                </button>

                            </form>

                            <form
                                method="POST"
                                action="/admin/services"
                                onsubmit="return confirm('Are you sure you want to delete this service?');"
                            >

                                <input
                                    type="hidden"
                                    name="service_id"
                                    value="<?= (int) $service['id'] ?>"
                                >

                                <button
                                    type="submit"
                                    name="delete_service"
                                    class="service-action-button delete"
                                    title="Delete service"
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
        Service Management
    </span>

</footer>

</main>

</div>

<script>
function addServiceDetail(value = '') {
    const list = document.getElementById('serviceDetailsList');
    if (!list) return;

    const row = document.createElement('div');
    row.className = 'service-detail-row';
    row.innerHTML = `
        <input
            type="text"
            name="details[]"
            value=""
            placeholder="e.g. Professional setup and technical support"
        >
        <button
            type="button"
            class="remove-detail-button"
            onclick="removeServiceDetail(this)"
            title="Remove detail"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;

    list.appendChild(row);
    const input = row.querySelector('input');
    input.value = value || '';
    input.focus();
}

function removeServiceDetail(button) {
    const list = document.getElementById('serviceDetailsList');
    if (!list) return;

    const rows = list.querySelectorAll('.service-detail-row');

    if (rows.length === 1) {
        rows[0].querySelector('input').value = '';
        return;
    }

    button.closest('.service-detail-row').remove();
}
</script>

</body>

</html>
