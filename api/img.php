<?php
// Simple image proxy - serves product images from the uploads directory
// Works both locally (proxies from remote) and on production (serves local files)
$path = $_GET['path'] ?? '';
if (empty($path) || strpos($path, '..') !== false) {
    http_response_code(400);
    exit;
}

$localFile = __DIR__ . '/../' . $path;

// If file exists locally, serve it directly
if (file_exists($localFile)) {
    $ext = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));
    $mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=2592000');
    readfile($localFile);
    exit;
}

// Otherwise, proxy from production server
$remoteUrl = 'https://fofisartes.com.br/' . $path;
$ch = curl_init($remoteUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$data = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode === 200 && $data) {
    // Cache locally for future requests
    $dir = dirname($localFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($localFile, $data);

    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=2592000');
    echo $data;
} else {
    http_response_code(404);
}
