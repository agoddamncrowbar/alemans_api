<?php
require_once __DIR__ . '/../includes/headers.php';

$filename = basename($_GET['filename'] ?? '');

if (!$filename) {
    http_response_code(400);
    echo json_encode(["message" => "Missing filename"]);
    exit;
}

$filepath = __DIR__ . '/../uploads/' . $filename;

if (!file_exists($filepath)) {
    http_response_code(404);
    echo json_encode(["message" => "File not found"]);
    exit;
}

// Override the JSON content type set in headers.php
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($filepath));
header('Content-Disposition: inline; filename="' . $filename . '"');

readfile($filepath);
exit;