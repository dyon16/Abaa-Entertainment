<?php session_start(); include(__DIR__ . '/conn.php'); /* |-------------------------------------------------------------------------- | ADMIN AUTHENTICATION |-------------------------------------------------------------------------- */ $authSecret = getenv('ADMIN_AUTH_SECRET'); if (!$authSecret) { http_response_code(500); exit('ADMIN_AUTH_SECRET is not configured.'); } $cookieName = 'abaa_admin_auth'; /* |-------------------------------------------------------------------------- | VERCEL BLOB |-------------------------------------------------------------------------- */ $blobToken = getenv('BLOB_READ_WRITE_TOKEN'); /* |-------------------------------------------------------------------------- | BASE64 URL DECODE |-------------------------------------------------------------------------- */ function base64UrlDecode($data) { $remainder = strlen($data) % 4; if ($remainder > 0) { $data .= str_repeat('=', 4 - $remainder); } return base64_decode( strtr($data, '-_', '+/'), true ); } /* |-------------------------------------------------------------------------- | VERIFY ADMIN COOKIE |-------------------------------------------------------------------------- */ function verifyAdminCookie($cookie, $secret) { if (empty($cookie)) { return false; } $parts = explode('.', $cookie); if (count($parts) !== 2) { return false; } $payloadEncoded = $parts[0]; $providedSignature = $parts[1]; $expectedSignature = hash_hmac( 'sha256', $payloadEncoded, $secret ); if (!hash_equals( $expectedSignature, $providedSignature )) { return false; } $payloadJson = base64UrlDecode( $payloadEncoded ); if ($payloadJson === false) { return false; } $payload = json_decode( $payloadJson, true ); if (!is_array($payload)) { return false; } if ( !isset($payload['id']) || !isset($payload['username']) || !isset($payload['exp']) ) { return false; } if ((int)$payload['exp'] < time()) { return false; } return $payload; } /* |-------------------------------------------------------------------------- | CHECK LOGIN |-------------------------------------------------------------------------- */ $admin = false; if (isset($_COOKIE[$cookieName])) { $admin = verifyAdminCookie( $_COOKIE[$cookieName], $authSecret ); } /* |-------------------------------------------------------------------------- | REDIRECT IF NOT LOGGED IN |-------------------------------------------------------------------------- */ if (!$admin) { header('Location: /admin'); exit; } /* |-------------------------------------------------------------------------- | HELPER |-------------------------------------------------------------------------- */ function e($value) { return htmlspecialchars( (string)($value ?? ''), ENT_QUOTES, 'UTF-8' ); } /* |-------------------------------------------------------------------------- | CREATE SLUG |-------------------------------------------------------------------------- */ function createSlug($text) { $text = strtolower(trim($text)); $text = preg_replace( '/[^a-z0-9]+/', '-', $text ); $text = trim( $text, '-' ); return $text ?: 'service'; } /* |-------------------------------------------------------------------------- | PARSE DETAILS |-------------------------------------------------------------------------- */ function parseDetails($details) { if (empty($details)) { return []; } $decoded = json_decode( $details, true ); if (is_array($decoded)) { return $decoded; } return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $details ) ) ) ); } /* |-------------------------------------------------------------------------- | GENERATE SAFE BLOB FILE NAME |-------------------------------------------------------------------------- | | Files will look like: | | LED-Wall-a83f92d1.jpg | | Files are uploaded directly to Blob root. | */ function createBlobFileName($serviceName, $extension) { $safeName = preg_replace( '/[^a-zA-Z0-9_-]+/', '-', $serviceName ); $safeName = trim( $safeName, '-' ); if ($safeName === '') { $safeName = 'service'; } return $safeName . '-' . bin2hex( random_bytes(8) ) . '.' . strtolower($extension); } /* |-------------------------------------------------------------------------- | BLOB UPLOAD |-------------------------------------------------------------------------- */ function uploadToVercelBlob( $tmpFile, $fileName, $mimeType, $blobToken ) { if (!$blobToken) { return [ 'success' => false, 'error' => 'BLOB_READ_WRITE_TOKEN is not configured.' ]; } if (!is_file($tmpFile)) { return [ 'success' => false, 'error' => 'Temporary uploaded file was not found.' ]; } $fileContents = file_get_contents( $tmpFile ); if ($fileContents === false) { return [ 'success' => false, 'error' => 'Unable to read uploaded file.' ]; } /* |-------------------------------------------------------------------------- | UPLOAD DIRECTLY TO VERCEL BLOB ROOT |-------------------------------------------------------------------------- */ $url = 'https://blob.vercel-storage.com/' . rawurlencode($fileName); $ch = curl_init($url); if ($ch === false) { return [ 'success' => false, 'error' => 'Unable to initialize Blob connection.' ]; } curl_setopt_array( $ch, [ CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => $fileContents, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_HTTPHEADER => [ 'Authorization: Bearer ' . $blobToken, 'Content-Type: ' . $mimeType, 'x-api-version: 7', 'x-content-type: ' . $mimeType, ], CURLOPT_TIMEOUT => 300, CURLOPT_CONNECTTIMEOUT => 30, ] ); $response = curl_exec($ch); $curlError = curl_error($ch); $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); /* |-------------------------------------------------------------------------- | PHP 8.5+ |-------------------------------------------------------------------------- | | curl_close() is deprecated in PHP 8.5. | cURL handles are automatically cleaned up. | */ if ($response === false) { return [ 'success' => false, 'error' => 'Blob upload failed: ' . $curlError ]; } if ( $httpCode < 200 || $httpCode >= 300 ) { error_log( 'Vercel Blob upload error: HTTP ' . $httpCode . ' Response: ' . $response ); return [ 'success' => false, 'error' => 'Vercel Blob rejected the upload. HTTP ' . $httpCode . '.' ]; } $data = json_decode( $response, true ); if (!is_array($data)) { return [ 'success' => false, 'error' => 'Invalid response from Vercel Blob.' ]; } $blobUrl = $data['url'] ?? $data['downloadUrl'] ?? null; if (!$blobUrl) { if ( isset($data['pathname']) && isset($data['url']) ) { $blobUrl = $data['url']; } } if (!$blobUrl) { error_log( 'Vercel Blob response: ' . $response ); return [ 'success' => false, 'error' => 'Vercel Blob did not return a file URL.' ]; } return [ 'success' => true, 'url' => $blobUrl ]; } /* |-------------------------------------------------------------------------- | DELETE BLOB |-------------------------------------------------------------------------- */ function deleteFromVercelBlob( $blobUrl, $blobToken ) { if ( empty($blobUrl) || empty($blobToken) ) { return false; } /* |-------------------------------------------------------------------------- | ONLY DELETE VERCEL BLOB URLS |-------------------------------------------------------------------------- */ if ( strpos( $blobUrl, 'blob.vercel-storage.com' ) === false ) { return false; } $ch = curl_init($blobUrl); if ($ch === false) { return false; } curl_setopt_array( $ch, [ CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => [ 'Authorization: Bearer ' . $blobToken, 'x-api-version: 7', ], CURLOPT_TIMEOUT => 60, ] ); $response = curl_exec($ch); $httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE ); /* |-------------------------------------------------------------------------- | PHP 8.5+ |-------------------------------------------------------------------------- | | curl_close() intentionally removed. | */ return ( $response !== false && $httpCode >= 200 && $httpCode < 300 ); } /* |-------------------------------------------------------------------------- | FLASH MESSAGES |-------------------------------------------------------------------------- */ $statusMessage = $_SESSION['service_status_message'] ?? ''; $statusError = $_SESSION['service_status_error'] ?? ''; unset( $_SESSION['service_status_message'], $_SESSION['service_status_error'] ); /* |-------------------------------------------------------------------------- | DELETE SERVICE |-------------------------------------------------------------------------- */ if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service']) ) { $serviceId = (int)( $_POST['service_id'] ?? 0 ); if ($serviceId <= 0) { $statusError = 'Invalid service ID.'; } else { try { $stmt = $pdo->prepare( "SELECT image_url FROM services WHERE id = :id LIMIT 1" ); $stmt->execute([ ':id' => $serviceId ]); $service = $stmt->fetch( PDO::FETCH_ASSOC ); if (!$service) { $statusError = 'Service not found.'; } else { /* |-------------------------------------------------------------------------- | DELETE BLOB IMAGE |-------------------------------------------------------------------------- */ if ( !empty( $service['image_url'] ) ) { deleteFromVercelBlob( $service['image_url'], $blobToken ); } /* |-------------------------------------------------------------------------- | DELETE DATABASE RECORD |-------------------------------------------------------------------------- */ $stmt = $pdo->prepare( "DELETE FROM services WHERE id = :id" ); $stmt->execute([ ':id' => $serviceId ]); $statusMessage = 'Service deleted successfully.'; } } catch (PDOException $e) { error_log( 'Service delete error: ' . $e->getMessage() ); $statusError = 'Unable to delete service.'; } } } /* |-------------------------------------------------------------------------- | TOGGLE AVAILABILITY |-------------------------------------------------------------------------- */ if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_availability']) ) { $serviceId = (int)( $_POST['service_id'] ?? 0 ); if ($serviceId <= 0) { $statusError = 'Invalid service ID.'; } else { try { $stmt = $pdo->prepare( "UPDATE services SET is_available = CASE WHEN is_available = 1 THEN 0 ELSE 1 END WHERE id = :id" ); $stmt->execute([ ':id' => $serviceId ]); $statusMessage = 'Service availability updated.'; } catch (PDOException $e) { error_log( 'Service availability error: ' . $e->getMessage() ); $statusError = 'Unable to update availability.'; } } } /* |-------------------------------------------------------------------------- | SAVE / EDIT SERVICE |-------------------------------------------------------------------------- */ if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_service']) ) { $serviceId = (int)( $_POST['service_id'] ?? 0 ); $name = trim( $_POST['name'] ?? '' ); $description = trim( $_POST['description'] ?? '' ); $sortOrder = (int)( $_POST['sort_order'] ?? 0 ); $detailsInput = $_POST['details'] ?? []; /* |-------------------------------------------------------------------------- | VALIDATE NAME |-------------------------------------------------------------------------- */ if ($name === '') { $statusError = 'Please enter a service name.'; } else { /* |-------------------------------------------------------------------------- | DETAILS |-------------------------------------------------------------------------- */ if (!is_array($detailsInput)) { $detailsInput = []; } $details = []; foreach ( $detailsInput as $detail ) { $detail = trim( $detail ); if ($detail !== '') { $details[] = $detail; } } $detailsJson = json_encode( $details, JSON_UNESCAPED_UNICODE ); if ($detailsJson === false) { $detailsJson = '[]'; } /* |-------------------------------------------------------------------------- | SLUG |-------------------------------------------------------------------------- */ $slug = createSlug( $name ); try { /* |-------------------------------------------------------------------------- | CHECK DUPLICATE SLUG |-------------------------------------------------------------------------- */ $stmt = $pdo->prepare( "SELECT id FROM services WHERE slug = :slug AND id != :id LIMIT 1" ); $stmt->execute([ ':slug' => $slug, ':id' => $serviceId ]); if ($stmt->fetch()) { $slug .= '-' . substr( bin2hex( random_bytes(3) ), 0, 6 ); } /* |-------------------------------------------------------------------------- | EDIT EXISTING SERVICE |-------------------------------------------------------------------------- */ if ($serviceId > 0) { $stmt = $pdo->prepare( "SELECT id, image_url FROM services WHERE id = :id LIMIT 1" ); $stmt->execute([ ':id' => $serviceId ]); $existing = $stmt->fetch( PDO::FETCH_ASSOC ); if (!$existing) { $statusError = 'Service not found.'; } else { $hasNewImage = isset( $_FILES['image'] ) && isset( $_FILES['image']['error'] ) && $_FILES['image']['error'] === UPLOAD_ERR_OK; /* |-------------------------------------------------------------------------- | NEW IMAGE |-------------------------------------------------------------------------- */ if ($hasNewImage) { $file = $_FILES['image']; $maxFileSize = 10 * 1024 * 1024; if ( (int)$file['size'] > $maxFileSize ) { $statusError = 'Image is too large. Maximum size is 10 MB.'; } else { $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ); $allowedExtensions = [ 'jpg', 'jpeg', 'png', 'webp' ]; if ( !in_array( $extension, $allowedExtensions, true ) ) { $statusError = 'Invalid image type. Please use JPG, JPEG, PNG, or WEBP.'; } else { $mimeType = mime_content_type( $file['tmp_name'] ); $allowedMimeTypes = [ 'image/jpeg', 'image/png', 'image/webp' ]; if ( !in_array( $mimeType, $allowedMimeTypes, true ) ) { $statusError = 'Invalid image file.'; } else { $newFileName = createBlobFileName( $name, $extension ); $uploadResult = uploadToVercelBlob( $file['tmp_name'], $newFileName, $mimeType, $blobToken ); if ( !$uploadResult['success'] ) { $statusError = $uploadResult['error']; } else { $newImageUrl = $uploadResult['url']; $stmt = $pdo->prepare( "UPDATE services SET name = :name, slug = :slug, image_url = :image_url, description = :description, details = :details, sort_order = :sort_order WHERE id = :id" ); $stmt->execute([ ':name' => $name, ':slug' => $slug, ':image_url' => $newImageUrl, ':description' => $description, ':details' => $detailsJson, ':sort_order' => $sortOrder, ':id' => $serviceId ]); /* |-------------------------------------------------------------------------- | DELETE OLD IMAGE |-------------------------------------------------------------------------- */ if ( !empty( $existing['image_url'] ) && $existing['image_url'] !== $newImageUrl ) { deleteFromVercelBlob( $existing['image_url'], $blobToken ); } $statusMessage = 'Service updated successfully.'; } } } } } else { /* |-------------------------------------------------------------------------- | UPDATE WITHOUT IMAGE |-------------------------------------------------------------------------- */ if ($statusError === '') { $stmt = $pdo->prepare( "UPDATE services SET name = :name, slug = :slug, description = :description, details = :details, sort_order = :sort_order WHERE id = :id" ); $stmt->execute([ ':name' => $name, ':slug' => $slug, ':description' => $description, ':details' => $detailsJson, ':sort_order' => $sortOrder, ':id' => $serviceId ]); $statusMessage = 'Service updated successfully.'; } } } } else { /* |-------------------------------------------------------------------------- | CREATE NEW SERVICE |-------------------------------------------------------------------------- */ if ( !isset( $_FILES['image'] ) || !isset( $_FILES['image']['error'] ) || $_FILES['image']['error'] !== UPLOAD_ERR_OK ) { $uploadErrorCode = $_FILES['image']['error'] ?? null; if ( $uploadErrorCode === UPLOAD_ERR_INI_SIZE ) { $statusError = 'The uploaded image is larger than the server allows.'; } elseif ( $uploadErrorCode === UPLOAD_ERR_FORM_SIZE ) { $statusError = 'The uploaded image is too large.'; } elseif ( $uploadErrorCode === UPLOAD_ERR_NO_FILE || $uploadErrorCode === null ) { $statusError = 'Please select a service image.'; } else { $statusError = 'Image upload failed. Upload error code: ' . $uploadErrorCode; } } else { $file = $_FILES['image']; $maxFileSize = 10 * 1024 * 1024; if ( (int)$file['size'] > $maxFileSize ) { $statusError = 'Image is too large. Maximum size is 10 MB.'; } else { $extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ); $allowedExtensions = [ 'jpg', 'jpeg', 'png', 'webp' ]; if ( !in_array( $extension, $allowedExtensions, true ) ) { $statusError = 'Invalid image type. Please use JPG, JPEG, PNG, or WEBP.'; } else { $mimeType = mime_content_type( $file['tmp_name'] ); $allowedMimeTypes = [ 'image/jpeg', 'image/png', 'image/webp' ]; if ( !in_array( $mimeType, $allowedMimeTypes, true ) ) { $statusError = 'Invalid image file.'; } else { $newFileName = createBlobFileName( $name, $extension ); $uploadResult = uploadToVercelBlob( $file['tmp_name'], $newFileName, $mimeType, $blobToken ); if ( !$uploadResult['success'] ) { $statusError = $uploadResult['error']; } else { $imageUrl = $uploadResult['url']; try { /* |-------------------------------------------------------------------------- | INSERT SERVICE |-------------------------------------------------------------------------- */ $stmt = $pdo->prepare( "INSERT INTO services ( name, slug, image_url, description, details, is_available, sort_order, created_at ) VALUES ( :name, :slug, :image_url, :description, :details, 1, :sort_order, NOW() )" ); $stmt->execute([ ':name' => $name, ':slug' => $slug, ':image_url' => $imageUrl, ':description' => $description, ':details' => $detailsJson, ':sort_order' => $sortOrder ]); $statusMessage = 'Service created successfully.'; } catch (PDOException $e) { /* |-------------------------------------------------------------------------- | DATABASE FAILED |-------------------------------------------------------------------------- */ deleteFromVercelBlob( $imageUrl, $blobToken ); error_log( 'Service insert error: ' . $e->getMessage() ); $statusError = 'Unable to save service.'; } } } } } } } } catch (PDOException $e) { error_log( 'Service save error: ' . $e->getMessage() ); $statusError = 'Database error while saving service.'; } } } /* |-------------------------------------------------------------------------- | POST → REDIRECT → GET |-------------------------------------------------------------------------- */ if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) { if ($statusMessage !== '') { $_SESSION['service_status_message'] = $statusMessage; } if ($statusError !== '') { $_SESSION['service_status_error'] = $statusError; } header( 'Location: /admin/services' ); exit; } /* |-------------------------------------------------------------------------- | LOAD SERVICES |-------------------------------------------------------------------------- */ $services = []; try { $stmt = $pdo->query( "SELECT id, name, slug, image_url, description, details, is_available, sort_order, created_at FROM services ORDER BY sort_order ASC, id ASC" ); $services = $stmt->fetchAll( PDO::FETCH_ASSOC ); } catch (PDOException $e) { error_log( 'Service query error: ' . $e->getMessage() ); $statusError = 'Unable to load services.'; } /* |-------------------------------------------------------------------------- | STATISTICS |-------------------------------------------------------------------------- */ $totalServices = count($services); $availableServices = 0; foreach ( $services as $service ) { if ( (int)$service['is_available'] === 1 ) { $availableServices++; } } ?> <!DOCTYPE html> <html lang="en"> <head> <meta charset="UTF-8">
<meta
name="viewport"
content="width=device-width, initial-scale=1.0"

<meta
name="theme-color"
content="#ff5a1f"

<title> Services - ABAA Admin </title> <link rel="stylesheet" href="/admin.css" > <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" > <style> .service-upload-card { background: white; border: 1px solid var(--border); border-radius: 16px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 8px rgba(0,0,0,.03); } .service-upload-header { display: flex; align-items: center; gap: 12px; margin-bottom: 22px; } .service-upload-icon { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--orange); background: var(--orange-light); } .service-upload-header span { display: block; color: var(--orange); font-size: 10px; font-weight: 800; letter-spacing: 1.5px; } .service-upload-header h2 { margin-top: 4px; font-size: 20px; color: var(--dark); } .service-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; } .service-form-group { display: flex; flex-direction: column; gap: 7px; } .service-form-group.full { grid-column: 1 / -1; } .service-form-group label { font-size: 12px; font-weight: 700; color: #374151; } .service-form-group input, .service-form-group textarea { width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 11px 12px; background: #fafafa; color: var(--text); outline: none; font-family: inherit; box-sizing: border-box; } .service-form-group input { height: 45px; } .service-form-group textarea { min-height: 110px; resize: vertical; } .service-form-group input:focus, .service-form-group textarea:focus { background: white; border-color: var(--orange); box-shadow: 0 0 0 3px rgba(255,90,31,.08); } .service-help { color: #9ca3af; font-size: 11px; } .details-editor { display: flex; flex-direction: column; gap: 9px; } .detail-row { display: flex; gap: 8px; } .detail-row input { flex: 1; } .remove-detail { width: 42px; border: 1px solid #fecaca; background: #fef2f2; color: #dc2626; border-radius: 8px; cursor: pointer; } .remove-detail:hover { background: #dc2626; color: white; } .add-detail { align-self: flex-start; border: 1px solid #fed7aa; background: #fff7ed; color: var(--orange); border-radius: 8px; padding: 9px 13px; font-size: 11px; font-weight: 700; cursor: pointer; } .service-submit { margin-top: 20px; border: none; border-radius: 9px; padding: 12px 18px; background: var(--orange); color: white; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 9px; } .service-submit:hover { background: var(--orange-dark); transform: translateY(-1px); } .service-admin-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; padding: 25px; } .service-admin-card { border: 1px solid var(--border); border-radius: 13px; overflow: hidden; background: white; box-shadow: 0 2px 8px rgba(0,0,0,.04); } .service-preview { height: 190px; background: #111; position: relative; overflow: hidden; } .service-preview img { width: 100%; height: 100%; object-fit: cover; display: block; } .service-availability { position: absolute; top: 10px; right: 10px; padding: 6px 9px; border-radius: 6px; color: white; font-size: 10px; font-weight: 800; } .service-availability.available { background: #16a34a; } .service-availability.unavailable { background: #6b7280; } .service-sort { position: absolute; left: 10px; top: 10px; padding: 6px 9px; border-radius: 6px; background: rgba(0,0,0,.75); color: white; font-size: 10px; font-weight: 800; } .service-admin-content { padding: 16px; } .service-admin-content h3 { font-size: 17px; color: var(--dark); margin-bottom: 6px; } .service-admin-slug { color: #9ca3af; font-size: 11px; margin-bottom: 12px; } .service-admin-description { color: #6b7280; font-size: 12px; line-height: 1.6; min-height: 38px; margin-bottom: 14px; } .service-admin-actions { display: grid; grid-template-columns: 1fr 1fr 40px; gap: 8px; } .service-admin-actions form { margin: 0; } .service-action-button { width: 100%; min-height: 36px; border-radius: 7px; border: 1px solid var(--border); background: white; color: #374151; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; } .service-action-button:hover { border-color: var(--orange); color: var(--orange); } .service-action-button.delete { color: #dc2626; border-color: #fecaca; background: #fef2f2; } .service-action-button.delete:hover { background: #dc2626; color: white; } .service-modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.65); z-index: 9999; display: none; align-items: center; justify-content: center; padding: 20px; } .service-modal-overlay.active { display: flex; } .service-modal { width: 100%; max-width: 760px; max-height: 90vh; overflow-y: auto; background: white; border-radius: 16px; padding: 25px; position: relative; } .service-modal-close { position: absolute; top: 15px; right: 15px; width: 38px; height: 38px; border: none; border-radius: 50%; background: #f3f4f6; color: #374151; cursor: pointer; } .service-modal h2 { margin: 0 0 20px; color: var(--dark); } .service-error-details { margin-top: 8px; font-family: monospace; font-size: 11px; white-space: pre-wrap; word-break: break-word; color: #991b1b; } @media (max-width: 1000px) { .service-admin-grid { grid-template-columns: repeat(2, 1fr); } } @media (max-width: 600px) { .service-form-grid { grid-template-columns: 1fr; } .service-form-group.full { grid-column: auto; } .service-admin-grid { grid-template-columns: 1fr; padding: 18px; } .service-upload-card { padding: 20px; } } </style> </head> <body> <div class="admin-layout"> <aside class="sidebar">
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


    <a href="/admin/events">

        <i class="fa-solid fa-photo-film"></i>

        <span>
            Events
        </span>

    </a>


    <a
        href="/admin/services"
        class="active"
    >

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

</aside> <main class="admin-main"> <div class="top-panel">
<div class="top-panel-left">


    <div class="top-panel-icon">

        <i class="fa-solid fa-screwdriver-wrench"></i>

    </div>


    <div>

        <span>
            ABAA ENTERTAINMENT
        </span>

        <strong>
            Service Management
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
        SERVICES
    </span>


    <h1>
        Service Management
    </h1>


    <p>
        Manage your services, availability,
        images, descriptions, and ordering.
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

</header> <?php if ($statusMessage): ?>
<div class="admin-notification success">


    <i class="fa-solid fa-circle-check"></i>


    <?= e($statusMessage) ?>


</div>

<?php endif; ?> <?php if ($statusError): ?>
<div class="admin-notification error">


    <i class="fa-solid fa-circle-exclamation"></i>


    <div>

        <?= e($statusError) ?>


    </div>


</div>

<?php endif; ?> <section class="stats-grid">
<div class="stat-card">


    <div class="stat-icon orange">

        <i class="fa-solid fa-screwdriver-wrench"></i>

    </div>


    <div class="stat-content">

        <span>
            Total Services
        </span>

        <strong>
            <?= $totalServices ?>
        </strong>

        <small>
            Services configured
        </small>

    </div>


</div>


<div class="stat-card">


    <div class="stat-icon dark-orange">

        <i class="fa-solid fa-circle-check"></i>

    </div>


    <div class="stat-content">

        <span>
            Available
        </span>

        <strong>
            <?= $availableServices ?>
        </strong>

        <small>
            Currently available
        </small>

    </div>


</div>


<div class="stat-card">


    <div class="stat-icon dark">

        <i class="fa-solid fa-eye-slash"></i>

    </div>


    <div class="stat-content">

        <span>
            Unavailable
        </span>

        <strong>
            <?= $totalServices - $availableServices ?>
        </strong>

        <small>
            Hidden from website
        </small>

    </div>


</div>

</section> <section class="service-upload-card">
<div class="service-upload-header">


    <div class="service-upload-icon">

        <i class="fa-solid fa-plus"></i>

    </div>


    <div>

        <span>
            SERVICE CONTENT
        </span>

        <h2>
            Add New Service
        </h2>

    </div>


</div>


<form
    method="POST"
    enctype="multipart/form-data"
    action="/admin/services"
>


    <div class="service-form-grid">


        <div class="service-form-group">


            <label>
                Service Name
            </label>


            <input
                type="text"
                name="name"
                placeholder="Example: LED Wall"
                required
            >


        </div>


        <div class="service-form-group">


            <label>
                Sort Order
            </label>


            <input
                type="number"
                name="sort_order"
                value="0"
                min="0"
            >


            <span class="service-help">
                Lower numbers appear first.
            </span>


        </div>


        <div class="service-form-group full">


            <label>
                Service Description
            </label>


            <textarea
                name="description"
                placeholder="Describe this service..."
            ></textarea>


        </div>


        <div class="service-form-group">


            <label>
                Service Image
            </label>


            <input
                type="file"
                name="image"
                accept="image/jpeg,image/png,image/webp"
                required
            >


            <span class="service-help">
                JPG, PNG, WEBP — maximum 10 MB.
            </span>


        </div>


        <div class="service-form-group">


            <label>
                Service Details
            </label>


            <div
                class="details-editor"
                id="newDetails"
            >


                <div class="detail-row">


                    <input
                        type="text"
                        name="details[]"
                        placeholder="Example: Professional LED display systems"
                    >


                    <button
                        type="button"
                        class="remove-detail"
                        onclick="removeDetail(this)"
                    >

                        <i class="fa-solid fa-xmark"></i>

                    </button>


                </div>


            </div>


            <button
                type="button"
                class="add-detail"
                onclick="addDetail('newDetails')"
            >

                <i class="fa-solid fa-plus"></i>

                Add Detail

            </button>


        </div>


    </div>


    <button
        type="submit"
        name="save_service"
        class="service-submit"
    >

        <i class="fa-solid fa-plus"></i>

        Add Service

    </button>


</form>

</section> <section class="bookings-section">
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
                    Your Services
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


        <h3>
            No services yet
        </h3>


        <p>
            Add your first service above.
        </p>


    </div>


<?php else: ?>


    <div class="service-admin-grid">


        <?php foreach (
            $services as $service
        ): ?>


            <?php

            $details =
                parseDetails(
                    $service['details'] ?? ''
                );

            ?>


            <div class="service-admin-card">


                <div class="service-preview">


                    <?php if (
                        !empty(
                            $service['image_url']
                        )
                    ): ?>


                        <img
                            src="<?= e(
                                $service['image_url']
                            ) ?>"
                            alt="<?= e(
                                $service['name']
                            ) ?>"
                        >


                    <?php else: ?>


                        <img
                            src="/logo.png"
                            alt="ABAA Entertainment"
                        >


                    <?php endif; ?>


                    <span class="service-sort">

                        #<?= (int)$service['sort_order'] ?>

                    </span>


                    <?php if (
                        (int)$service['is_available'] === 1
                    ): ?>


                        <span
                            class="service-availability available"
                        >

                            <i class="fa-solid fa-check"></i>

                            Available

                        </span>


                    <?php else: ?>


                        <span
                            class="service-availability unavailable"
                        >

                            <i class="fa-solid fa-ban"></i>

                            Unavailable

                        </span>


                    <?php endif; ?>


                </div>


                <div class="service-admin-content">


                    <h3>
                        <?= e(
                            $service['name']
                        ) ?>
                    </h3>


                    <div class="service-admin-slug">

                        /service?service=<?= e(
                            $service['slug']
                        ) ?>

                    </div>


                    <div class="service-admin-description">

                        <?= e(
                            mb_strimwidth(
                                $service['description'] ?? '',
                                0,
                                100,
                                '...'
                            )
                        ) ?>

                    </div>


                    <div class="service-admin-actions">


                        <form
                            method="POST"
                            action="/admin/services"
                        >


                            <input
                                type="hidden"
                                name="service_id"
                                value="<?= (int)$service['id'] ?>"
                            >


                            <button
                                type="submit"
                                name="toggle_availability"
                                class="service-action-button"
                            >


                                <?php if (
                                    (int)$service['is_available'] === 1
                                ): ?>


                                    <i class="fa-solid fa-ban"></i>

                                    Disable


                                <?php else: ?>


                                    <i class="fa-solid fa-check"></i>

                                    Enable


                                <?php endif; ?>


                            </button>


                        </form>


                        <button
                            type="button"
                            class="service-action-button"
                            onclick='openEditService(<?= json_encode(
                                $service,
                                JSON_HEX_TAG |
                                JSON_HEX_AMP |
                                JSON_HEX_APOS |
                                JSON_HEX_QUOT |
                                JSON_UNESCAPED_UNICODE
                            ) ?>)'
                        >


                            <i class="fa-solid fa-pen"></i>

                            Edit


                        </button>


                        <form
                            method="POST"
                            action="/admin/services"
                            onsubmit="return confirm('Are you sure you want to delete this service?');"
                        >


                            <input
                                type="hidden"
                                name="service_id"
                                value="<?= (int)$service['id'] ?>"
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

</section> <div class="service-modal-overlay" id="editServiceModal" >
<div class="service-modal">


    <button
        type="button"
        class="service-modal-close"
        onclick="closeEditService()"
    >

        <i class="fa-solid fa-xmark"></i>

    </button>


    <h2>
        Edit Service
    </h2>


    <form
        method="POST"
        enctype="multipart/form-data"
        action="/admin/services"
    >


        <input
            type="hidden"
            name="service_id"
            id="edit_service_id"
        >


        <div class="service-form-grid">


            <div class="service-form-group">


                <label>
                    Service Name
                </label>


                <input
                    type="text"
                    name="name"
                    id="edit_name"
                    required
                >


            </div>


            <div class="service-form-group">


                <label>
                    Sort Order
                </label>


                <input
                    type="number"
                    name="sort_order"
                    id="edit_sort_order"
                    min="0"
                >


            </div>


            <div class="service-form-group full">


                <label>
                    Description
                </label>


                <textarea
                    name="description"
                    id="edit_description"
                ></textarea>


            </div>


            <div class="service-form-group">


                <label>
                    Replace Image
                </label>


                <input
                    type="file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                >


                <span class="service-help">
                    Leave empty to keep the current image.
                </span>


            </div>


            <div class="service-form-group">


                <label>
                    Current Image
                </label>


                <img
                    id="edit_current_image"
                    src="/logo.png"
                    alt="Current service image"
                    style="
                        width:100%;
                        height:120px;
                        object-fit:cover;
                        border-radius:8px;
                        border:1px solid var(--border);
                    "
                >


            </div>


            <div class="service-form-group full">


                <label>
                    Service Details
                </label>


                <div
                    class="details-editor"
                    id="editDetails"
                ></div>


                <button
                    type="button"
                    class="add-detail"
                    onclick="addDetail('editDetails')"
                >

                    <i class="fa-solid fa-plus"></i>

                    Add Detail

                </button>


            </div>


        </div>


        <button
            type="submit"
            name="save_service"
            class="service-submit"
        >

            <i class="fa-solid fa-floppy-disk"></i>

            Save Changes

        </button>


    </form>


</div>

</div> <footer class="admin-footer">
<span>

    © <?= date('Y') ?>

    ABAA Entertainment

</span>


<span>
    Service Management
</span>

</footer> </main> </div> <script> /* |-------------------------------------------------------------------------- | ADD DETAIL |-------------------------------------------------------------------------- */ function addDetail( containerId, value = "" ) { const container = document.getElementById( containerId ); if (!container) { return; } const row = document.createElement( "div" ); row.className = "detail-row"; const input = document.createElement( "input" ); input.type = "text"; input.name = "details[]"; input.placeholder = "Service detail"; input.value = value; const button = document.createElement( "button" ); button.type = "button"; button.className = "remove-detail"; button.innerHTML = '<i class="fa-solid fa-xmark"></i>'; button.addEventListener( "click", function() { removeDetail( button ); } ); row.appendChild( input ); row.appendChild( button ); container.appendChild( row ); } /* |-------------------------------------------------------------------------- | REMOVE DETAIL |-------------------------------------------------------------------------- */ function removeDetail(button) { const row = button.closest( ".detail-row" ); if (row) { row.remove(); } } /* |-------------------------------------------------------------------------- | OPEN EDIT |-------------------------------------------------------------------------- */ function openEditService(service) { const modal = document.getElementById( "editServiceModal" ); document.getElementById( "edit_service_id" ).value = service.id || ""; document.getElementById( "edit_name" ).value = service.name || ""; document.getElementById( "edit_sort_order" ).value = service.sort_order || 0; document.getElementById( "edit_description" ).value = service.description || ""; document.getElementById( "edit_current_image" ).src = service.image_url || "/logo.png"; const detailsContainer = document.getElementById( "editDetails" ); detailsContainer.innerHTML = ""; let details = []; try { if ( service.details ) { const parsed = JSON.parse( service.details ); if ( Array.isArray(parsed) ) { details = parsed; } } } catch (error) { details = []; } if ( details.length === 0 ) { addDetail( "editDetails" ); } else { details.forEach( function(detail) { addDetail( "editDetails", detail ); } ); } modal.classList.add( "active" ); document.body.style.overflow = "hidden"; } /* |-------------------------------------------------------------------------- | CLOSE EDIT |-------------------------------------------------------------------------- */ function closeEditService() { const modal = document.getElementById( "editServiceModal" ); modal.classList.remove( "active" ); document.body.style.overflow = ""; } /* |-------------------------------------------------------------------------- | CLICK OUTSIDE |-------------------------------------------------------------------------- */ document.addEventListener( "click", function(event) { const modal = document.getElementById( "editServiceModal" ); if ( event.target === modal ) { closeEditService(); } } ); /* |-------------------------------------------------------------------------- | ESC KEY |-------------------------------------------------------------------------- */ document.addEventListener( "keydown", function(event) { if ( event.key === "Escape" ) { closeEditService(); } } ); </script> </body> </html>
